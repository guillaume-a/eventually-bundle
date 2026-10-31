<?php

namespace Codr\Eventually\Bundle\Tests\Unit\Ai;

use Codr\Eventually\Bundle\Ai\AiFilesInstaller;
use Codr\Eventually\Bundle\Ai\AiInstallHintListener;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class AiInstallHintListenerTest extends TestCase
{
    private string $root;
    private AiFilesInstaller $installer;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/eventually-hint-'.bin2hex(random_bytes(4));
        mkdir($this->root.'/vendor/codr/eventually/ai/memory', 0o775, true);
        file_put_contents($this->root.'/vendor/codr/eventually/ai/memory/eventually.md', "# Eventually\n");
        $this->installer = new AiFilesInstaller($this->root, $this->root.'/vendor', static fn (): string => '0.1.0');
    }

    protected function tearDown(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);
    }

    public function testHintsWhenNothingIsInstalled(): void
    {
        self::assertStringContainsString('not installed', $this->terminate());
    }

    public function testHintsWhenInstalledFilesAreOutOfDate(): void
    {
        $config = ['skills' => ['.claude/skills'], 'memory' => 'docs/ext'];
        $this->installer->saveConfig($config);
        $this->installer->sync($config);
        file_put_contents($this->root.'/vendor/codr/eventually/ai/memory/eventually.md', "# Eventually\n\nMore.\n");

        self::assertStringContainsString('out of date', $this->terminate());
    }

    public function testSilentWhenUpToDate(): void
    {
        $config = ['skills' => ['.claude/skills'], 'memory' => 'docs/ext'];
        $this->installer->saveConfig($config);
        $this->installer->sync($config);

        self::assertSame('', $this->terminate());
    }

    public function testSilentOutsideDebug(): void
    {
        self::assertSame('', $this->terminate(debug: false));
    }

    public function testSilentForOtherCommands(): void
    {
        self::assertSame('', $this->terminate(command: 'cache:warmup'));
    }

    public function testSilentWhenNoPackageShipsFiles(): void
    {
        unlink($this->root.'/vendor/codr/eventually/ai/memory/eventually.md');
        rmdir($this->root.'/vendor/codr/eventually/ai/memory');
        rmdir($this->root.'/vendor/codr/eventually/ai');

        self::assertSame('', $this->terminate());
    }

    public function testSilentWhenCommandFailed(): void
    {
        self::assertSame('', $this->terminate(exitCode: Command::FAILURE));
    }

    private function terminate(bool $debug = true, string $command = 'cache:clear', int $exitCode = Command::SUCCESS): string
    {
        $output = new BufferedOutput();
        (new AiInstallHintListener($this->installer, $debug))(
            new ConsoleTerminateEvent(new Command($command), new ArrayInput([]), $output, $exitCode),
        );

        return $output->fetch();
    }
}
