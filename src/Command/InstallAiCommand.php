<?php

namespace Codr\Eventually\Bundle\Command;

use Codr\Eventually\Bundle\Ai\AiFilesInstaller;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;
use function in_array;
use function is_string;
use function sprintf;

#[AsCommand(name: 'eventually:ai:install', description: 'Copy the AI skills and memory docs shipped by the codr packages into the directories your AI coding agent reads')]
final class InstallAiCommand extends Command
{
    private const array AGENT_SKILL_DIRS = ['claude' => '.claude/skills', 'agents' => '.agents/skills'];
    private const string OTHER = 'other';
    private const string DEFAULT_MEMORY_DIR = 'aidd_docs/memory/external';

    public function __construct(private readonly AiFilesInstaller $installer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('agent', 'a', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Install skills for a known agent layout ('.implode(', ', array_keys(self::AGENT_SKILL_DIRS)).')')
            ->addOption('skills-dir', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Install skills into this directory, relative to the project')
            ->addOption('memory-dir', null, InputOption::VALUE_REQUIRED, 'Install memory docs into this directory, relative to the project')
            ->addOption('reconfigure', null, InputOption::VALUE_NONE, 'Ignore the stored choice and ask again')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $config = $this->resolveConfig($input, $io);
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if (null === $config) {
            $io->note(sprintf('No target configured. Run `eventually:ai:install` once interactively, or pass --agent or --skills-dir. The choice is stored in %s.', AiFilesInstaller::CONFIG_FILE));

            return Command::SUCCESS;
        }

        $rows = $this->installer->sync($config);
        $kept = count(array_filter($rows, static fn (array $row): bool => AiFilesInstaller::MODIFIED === $row[2] || AiFilesInstaller::UNTRACKED === $row[2]));

        if ([] === $rows) {
            $io->note('No codr package ships an ai directory.');

            return Command::SUCCESS;
        }

        $io->table(['Package', 'File', 'Status'], $rows);

        if ($kept > 0) {
            $io->warning(sprintf('%d file(s) were kept because they changed locally or are not managed. Delete them to get the shipped version.', $kept));

            return Command::SUCCESS;
        }

        $io->success('AI files are up to date.');

        return Command::SUCCESS;
    }

    /**
     * @return array{skills: list<string>, memory: string}|null
     */
    private function resolveConfig(InputInterface $input, SymfonyStyle $io): ?array
    {
        $stored = $input->getOption('reconfigure') ? null : $this->installer->loadConfig();

        $skills = [];
        /** @var list<string> $agents */
        $agents = $input->getOption('agent');
        foreach ($agents as $agent) {
            if (!isset(self::AGENT_SKILL_DIRS[$agent])) {
                throw new InvalidArgumentException(sprintf('Unknown agent "%s", expected one of: %s. Use --skills-dir for another layout.', $agent, implode(', ', array_keys(self::AGENT_SKILL_DIRS))));
            }
            $skills[] = self::AGENT_SKILL_DIRS[$agent];
        }
        /** @var list<string> $dirs */
        $dirs = $input->getOption('skills-dir');
        $skills = [...$skills, ...$dirs];
        if ([] === $skills) {
            $skills = $stored['skills'] ?? [];
        }

        /** @var string|null $memory */
        $memory = $input->getOption('memory-dir') ?? $stored['memory'] ?? null;

        if ([] === $skills) {
            if (!$input->isInteractive()) {
                return null;
            }
            $skills = $this->askSkillsDirs($io);
        }
        if (null === $memory) {
            $memory = $input->isInteractive()
                ? $this->ask($io, 'Directory for the memory docs, relative to the project', self::DEFAULT_MEMORY_DIR)
                : self::DEFAULT_MEMORY_DIR;
        }

        $config = [
            'skills' => array_values(array_unique(array_map($this->normalizeDir(...), $skills))),
            'memory' => $this->normalizeDir($memory),
        ];

        if ($config !== $stored) {
            $this->installer->saveConfig($config);
        }

        return $config;
    }

    /**
     * @return list<string>
     */
    private function askSkillsDirs(SymfonyStyle $io): array
    {
        $choices = [...array_keys(self::AGENT_SKILL_DIRS), self::OTHER];
        $question = new ChoiceQuestion('Which AI agents should get the skills? Comma separated, e.g. 0,1', $choices, '0');
        $question->setMultiselect(true);

        $dirs = [];
        /** @var list<string> $answers */
        $answers = $io->askQuestion($question);
        foreach ($answers as $answer) {
            if (self::OTHER === $answer) {
                $custom = $this->ask($io, 'Skills directory, relative to the project (comma separated for several)');
                array_push($dirs, ...explode(',', $custom));

                continue;
            }
            $dirs[] = self::AGENT_SKILL_DIRS[$answer];
        }

        return $dirs;
    }

    private function ask(SymfonyStyle $io, string $question, ?string $default = null): string
    {
        $answer = $io->ask($question, $default);

        return is_string($answer) ? $answer : '';
    }

    private function normalizeDir(string $dir): string
    {
        $dir = trim(trim($dir), '/');

        if ('' === $dir || str_starts_with($dir, '/') || in_array('..', explode('/', $dir), true)) {
            throw new InvalidArgumentException(sprintf('Invalid directory "%s": it must be a non-empty path inside the project.', $dir));
        }

        return $dir;
    }
}
