<?php

namespace Codr\Eventually\Bundle\Command;

use Codr\Eventually\Application\Event\RecordedEventReaderInterface;
use Codr\Eventually\Application\ReadModel\ProjectorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(name: 'app:read-models:rebuild', description: 'Truncate every read model and replay all stored events, ordered by position, through the projectors')]
final class RebuildReadModelsCommand extends Command
{
    private const int FLUSH_EVERY = 500;

    public function __construct(
        private readonly RecordedEventReaderInterface $reader,
        private readonly EntityManagerInterface $entityManager,
        /** @var iterable<ProjectorInterface> */
        private readonly iterable $projectors,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $replayed = $this->entityManager->wrapInTransaction(function (): int {
            foreach ($this->projectors as $projector) {
                $projector->reset();
            }

            $count = 0;
            foreach ($this->reader->readAll() as $recorded) {
                foreach ($this->projectors as $projector) {
                    $projector->handle($recorded);
                }
                ++$count;

                if (0 === $count % self::FLUSH_EVERY) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }

            return $count;
        });

        $io->success(sprintf('Read models rebuilt from %d events.', $replayed));

        return Command::SUCCESS;
    }
}
