<?php

namespace Codr\Eventually\Bundle\Ai;

use Closure;
use Composer\InstalledVersions;
use FilesystemIterator;
use OutOfBoundsException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function dirname;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function strlen;

use const GLOB_ONLYDIR;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

final class AiFilesInstaller
{
    public const string CREATED = 'created';
    public const string UPDATED = 'updated';
    public const string UNCHANGED = 'unchanged';
    public const string MODIFIED = 'modified (kept)';
    public const string UNTRACKED = 'untracked (kept)';
    public const string CONFIG_FILE = '.eventually-ai.json';

    private const array MANAGED_KEYS = ['package', 'version', 'hash'];

    /** @var Closure(string): string */
    private readonly Closure $versionResolver;

    public function __construct(
        private readonly string $projectDir,
        private readonly string $vendorDir,
        ?Closure $versionResolver = null,
    ) {
        $this->versionResolver = $versionResolver ?? static function (string $package): string {
            try {
                return InstalledVersions::getPrettyVersion($package) ?? 'unknown';
            } catch (OutOfBoundsException) {
                return 'unknown';
            }
        };
    }

    public function shipsFiles(): bool
    {
        return [] !== $this->aiDirectories();
    }

    /**
     * @param array{skills: list<string>, memory: string} $config
     */
    public function saveConfig(array $config): void
    {
        file_put_contents($this->projectDir.'/'.self::CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    /**
     * @param array{skills: list<string>, memory: string} $config
     *
     * @return list<array{string, string, string}>
     */
    public function sync(array $config, bool $dryRun = false): array
    {
        $rows = [];
        foreach ($this->aiDirectories() as $aiDir) {
            $package = 'codr/'.basename(dirname($aiDir));
            /** @var string $version */
            $version = ($this->versionResolver)($package);

            foreach ($this->shippedFiles($aiDir, $config['skills'], $config['memory']) as $source => $targets) {
                foreach ($targets as $target) {
                    $rows[] = [$package, $this->relative($target), $this->install($source, $target, $package, $version, $dryRun)];
                }
            }
        }

        return $rows;
    }

    /**
     * @param array{skills: list<string>, memory: string} $config
     */
    public function hasPendingChanges(array $config): bool
    {
        foreach ($this->sync($config, true) as [, , $status]) {
            if (self::CREATED === $status || self::UPDATED === $status) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function aiDirectories(): array
    {
        return glob($this->vendorDir.'/codr/*/ai', GLOB_ONLYDIR) ?: [];
    }

    /**
     * @return array{skills: list<string>, memory: string}|null
     */
    public function loadConfig(): ?array
    {
        $path = $this->projectDir.'/'.self::CONFIG_FILE;
        if (!is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data) || !isset($data['memory'], $data['skills']) || !is_string($data['memory']) || !is_array($data['skills'])) {
            return null;
        }

        $skills = array_values(array_filter($data['skills'], is_string(...)));

        return [] === $skills ? null : ['skills' => $skills, 'memory' => $data['memory']];
    }

    /**
     * @param list<string> $skillsDirs
     *
     * @return array<string, list<string>>
     */
    private function shippedFiles(string $aiDir, array $skillsDirs, string $memoryDir): array
    {
        $files = [];

        foreach ($this->markdownFiles($aiDir.'/skills') as $relative) {
            foreach ($skillsDirs as $skillsDir) {
                $files[$aiDir.'/skills/'.$relative][] = $this->projectDir.'/'.$skillsDir.'/'.$relative;
            }
        }
        foreach ($this->markdownFiles($aiDir.'/memory') as $relative) {
            $files[$aiDir.'/memory/'.$relative][] = $this->projectDir.'/'.$memoryDir.'/'.$relative;
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private function markdownFiles(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $relatives = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && 'md' === $file->getExtension()) {
                $relatives[] = substr($file->getPathname(), strlen($root) + 1);
            }
        }
        sort($relatives);

        return $relatives;
    }

    private function install(string $source, string $target, string $package, string $version, bool $dryRun): string
    {
        $shipped = (string) file_get_contents($source);
        $shippedHash = hash('sha256', $shipped);

        if (!is_file($target)) {
            $dryRun || $this->write($target, $this->render($shipped, $package, $version, $shippedHash));

            return self::CREATED;
        }

        $installed = $this->parse((string) file_get_contents($target));
        if (null === $installed) {
            return self::UNTRACKED;
        }
        if (hash('sha256', $installed['content']) !== $installed['hash']) {
            return self::MODIFIED;
        }
        if ($installed['hash'] === $shippedHash && $installed['version'] === $version) {
            return self::UNCHANGED;
        }

        $dryRun || $this->write($target, $this->render($shipped, $package, $version, $shippedHash));

        return self::UPDATED;
    }

    private function render(string $shipped, string $package, string $version, string $hash): string
    {
        $managed = sprintf("package: %s\nversion: %s\nhash: %s\n", $package, $version, $hash);

        if (1 === preg_match('/\A---\n(.*?)^---\n/ms', $shipped, $match)) {
            return '---'."\n".$match[1].$managed.'---'."\n".substr($shipped, strlen($match[0]));
        }

        return '---'."\n".$managed.'---'."\n\n".$shipped;
    }

    /**
     * @return array{content: string, hash: string, version: string}|null
     */
    private function parse(string $installed): ?array
    {
        if (1 !== preg_match('/\A---\n(.*?)^---\n/ms', $installed, $match)) {
            return null;
        }

        $managed = [];
        $remaining = '';
        foreach (explode("\n", rtrim($match[1], "\n")) as $line) {
            $key = strstr($line, ':', true);
            if (false !== $key && in_array($key, self::MANAGED_KEYS, true)) {
                $managed[$key] = trim(substr($line, strlen($key) + 1));

                continue;
            }
            $remaining .= $line."\n";
        }

        if (!isset($managed['hash'])) {
            return null;
        }

        $rest = substr($installed, strlen($match[0]));
        $content = '' === trim($remaining)
            ? substr($rest, 1)
            : '---'."\n".$remaining.'---'."\n".$rest;

        return ['content' => $content, 'hash' => $managed['hash'], 'version' => $managed['version'] ?? ''];
    }

    private function write(string $target, string $content): void
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0o775, true);
        }

        file_put_contents($target, $content);
    }

    private function relative(string $path): string
    {
        return substr($path, strlen($this->projectDir) + 1);
    }
}
