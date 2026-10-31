<?php

namespace Codr\Eventually\Bundle\Tests\Unit\Command;

use Codr\Eventually\Bundle\Ai\AiFilesInstaller;
use Codr\Eventually\Bundle\Command\InstallAiCommand;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Tester\CommandTester;

use function dirname;

final class InstallAiCommandTest extends TestCase
{
    private string $root;
    private string $version = '0.1.0';

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/eventually-ai-'.bin2hex(random_bytes(4));
        $this->ship('skills/demo/SKILL.md', "---\nname: demo\ndescription: Demo skill.\n---\n\n# Demo\n");
        $this->ship('skills/demo/actions/01-frame.md', "# Frame\n\nBody.\n");
        $this->ship('memory/eventually.md', "# Eventually\n\nRules.\n");
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testFreshInstallCopiesSkillsAndMemoryWithMarkers(): void
    {
        $tester = $this->runCommand();

        self::assertSame(0, $tester->getStatusCode());
        $skill = $this->read('.claude/skills/demo/SKILL.md');
        self::assertStringContainsString("name: demo\n", $skill);
        self::assertStringContainsString("package: codr/eventually\n", $skill);
        self::assertStringContainsString("version: 0.1.0\n", $skill);
        self::assertMatchesRegularExpression('/^hash: [0-9a-f]{64}$/m', $skill);
        self::assertStringStartsWith("---\npackage: codr/eventually\n", $this->read('.claude/skills/demo/actions/01-frame.md'));
        self::assertFileExists($this->root.'/project/aidd_docs/memory/external/eventually.md');
        self::assertStringContainsString('created', $tester->getDisplay());
    }

    public function testRerunChangesNothing(): void
    {
        $this->runCommand();
        $before = $this->snapshot();

        $tester = $this->runCommand();

        self::assertSame($before, $this->snapshot());
        self::assertStringNotContainsString('created', $tester->getDisplay());
        self::assertStringNotContainsString('updated', $tester->getDisplay());
        self::assertStringContainsString('up to date', $tester->getDisplay());
    }

    public function testLocallyEditedFileIsReportedAndKept(): void
    {
        $this->runCommand();
        $path = $this->root.'/project/.claude/skills/demo/actions/01-frame.md';
        file_put_contents($path, (string) file_get_contents($path)."\nLocal note.\n");

        $this->ship('skills/demo/actions/01-frame.md', "# Frame\n\nNew body.\n");
        $this->version = '0.2.0';
        $tester = $this->runCommand();

        self::assertStringContainsString('Local note.', $this->read('.claude/skills/demo/actions/01-frame.md'));
        self::assertStringNotContainsString('New body.', $this->read('.claude/skills/demo/actions/01-frame.md'));
        self::assertStringContainsString('modified (kept)', $tester->getDisplay());
    }

    public function testUpgradeOverwritesUnmodifiedFilesAndBumpsVersion(): void
    {
        $this->runCommand();

        $this->ship('memory/eventually.md', "# Eventually\n\nNew rules.\n");
        $this->version = '0.2.0';
        $tester = $this->runCommand();

        $memory = $this->read('aidd_docs/memory/external/eventually.md');
        self::assertStringContainsString('New rules.', $memory);
        self::assertStringContainsString("version: 0.2.0\n", $memory);
        self::assertStringContainsString("version: 0.2.0\n", $this->read('.claude/skills/demo/SKILL.md'));
        self::assertStringContainsString('updated', $tester->getDisplay());
    }

    public function testFileWithoutMarkerIsKept(): void
    {
        $path = $this->root.'/project/aidd_docs/memory/external/eventually.md';
        mkdir(dirname($path), 0o775, true);
        file_put_contents($path, "# Mine\n");

        $tester = $this->runCommand();

        self::assertSame("# Mine\n", $this->read('aidd_docs/memory/external/eventually.md'));
        self::assertStringContainsString('untracked (kept)', $tester->getDisplay());
    }

    public function testNoteWhenNoPackageShipsAi(): void
    {
        $this->remove($this->root.'/project/vendor/codr/eventually/ai');

        $tester = $this->runCommand();

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('No codr package ships an ai directory.', $tester->getDisplay());
    }

    public function testAgentsLayoutAndCustomDirectoryReceiveSkills(): void
    {
        $this->runCommand(['--agent' => ['claude', 'agents'], '--skills-dir' => ['tools/skills'], '--memory-dir' => 'docs/ext']);

        self::assertFileExists($this->root.'/project/.claude/skills/demo/SKILL.md');
        self::assertFileExists($this->root.'/project/.agents/skills/demo/SKILL.md');
        self::assertFileExists($this->root.'/project/tools/skills/demo/SKILL.md');
        self::assertFileExists($this->root.'/project/docs/ext/eventually.md');
        self::assertFileDoesNotExist($this->root.'/project/aidd_docs');
    }

    public function testChoiceIsStoredAndReusedWithoutInput(): void
    {
        $this->runCommand(['--agent' => ['agents'], '--memory-dir' => 'docs/ext']);

        $config = json_decode($this->read('.eventually-ai.json'), true);
        self::assertSame(['skills' => ['.agents/skills'], 'memory' => 'docs/ext'], $config);

        $tester = $this->runCommand([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('up to date', $tester->getDisplay());
        self::assertFileDoesNotExist($this->root.'/project/.claude');
    }

    public function testInteractiveRunAsksForAgentsAndMemoryDirectory(): void
    {
        $this->runCommand([], ['interactive' => true], ['0,1', 'docs/ext']);

        self::assertFileExists($this->root.'/project/.claude/skills/demo/SKILL.md');
        self::assertFileExists($this->root.'/project/.agents/skills/demo/SKILL.md');
        self::assertFileExists($this->root.'/project/docs/ext/eventually.md');
        self::assertSame(['skills' => ['.claude/skills', '.agents/skills'], 'memory' => 'docs/ext'], json_decode($this->read('.eventually-ai.json'), true));
    }

    public function testInteractiveOtherChoiceAsksForADirectory(): void
    {
        $this->runCommand([], ['interactive' => true], ['2', 'tools/skills, more/skills', '']);

        self::assertFileExists($this->root.'/project/tools/skills/demo/SKILL.md');
        self::assertFileExists($this->root.'/project/more/skills/demo/SKILL.md');
        self::assertFileExists($this->root.'/project/aidd_docs/memory/external/eventually.md');
    }

    public function testNonInteractiveRunWithoutChoiceSkipsWithANote(): void
    {
        $tester = $this->runCommand([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('No target configured', $tester->getDisplay());
        self::assertFileDoesNotExist($this->root.'/project/.claude');
    }

    public function testInvalidDirectoryFails(): void
    {
        $tester = $this->runCommand(['--skills-dir' => ['../outside']]);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Invalid directory', $tester->getDisplay());
    }

    public function testUnknownAgentFails(): void
    {
        $tester = $this->runCommand(['--agent' => ['nope']]);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Unknown agent "nope"', $tester->getDisplay());
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $options
     * @param list<string>         $inputs
     */
    private function runCommand(array $input = ['--agent' => ['claude']], array $options = ['interactive' => false], array $inputs = []): CommandTester
    {
        $tester = new CommandTester(new InstallAiCommand(new AiFilesInstaller(
            $this->root.'/project',
            $this->root.'/project/vendor',
            fn (): string => $this->version,
        )));
        $tester->setInputs($inputs);
        $tester->execute($input, $options);

        return $tester;
    }

    private function ship(string $relative, string $content): void
    {
        $path = $this->root.'/project/vendor/codr/eventually/ai/'.$relative;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0o775, true);
        }
        file_put_contents($path, $content);
    }

    private function read(string $relative): string
    {
        return (string) file_get_contents($this->root.'/project/'.$relative);
    }

    /**
     * @return array<string, string>
     */
    private function snapshot(): array
    {
        $files = [];
        foreach (['.claude', 'aidd_docs'] as $dir) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root.'/project/'.$dir, FilesystemIterator::SKIP_DOTS));
            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                $files[$file->getPathname()] = (string) file_get_contents($file->getPathname());
            }
        }
        ksort($files);

        return $files;
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($path);
    }
}
