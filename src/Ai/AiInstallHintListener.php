<?php

namespace Codr\Eventually\Bundle\Ai;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

use function sprintf;

final readonly class AiInstallHintListener
{
    public function __construct(
        private AiFilesInstaller $installer,
        private bool $debug,
    ) {
    }

    public function __invoke(ConsoleTerminateEvent $event): void
    {
        if (!$this->debug || Command::SUCCESS !== $event->getExitCode() || 'cache:clear' !== $event->getCommand()?->getName()) {
            return;
        }
        if (!$this->installer->shipsFiles()) {
            return;
        }

        $config = $this->installer->loadConfig();
        if (null !== $config && !$this->installer->hasPendingChanges($config)) {
            return;
        }

        $event->getOutput()->writeln(sprintf(
            '<comment>The AI skills and docs shipped by the codr packages are %s. Run `bin/console eventually:ai:install`.</comment>',
            null === $config ? 'not installed' : 'out of date',
        ));
    }
}
