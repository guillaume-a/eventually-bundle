<?php

namespace Codr\Eventually\Bundle\Tests;

use Codr\Eventually\Application\Bus\CommandBusInterface;
use Codr\Eventually\Application\Bus\QueryBusInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

use function assert;

abstract class EventuallyKernelTestCase extends KernelTestCase
{
    protected CommandBusInterface $commandBus;
    protected QueryBusInterface $queryBus;
    protected EntityManagerInterface $entityManager;
    protected MockClock $clock;

    protected function setUp(): void
    {
        $container = self::getContainer();

        $commandBus = $container->get(CommandBusInterface::class);
        $queryBus = $container->get(QueryBusInterface::class);
        $entityManager = $container->get(EntityManagerInterface::class);
        $clock = $container->get(MockClock::class);
        assert($commandBus instanceof CommandBusInterface);
        assert($queryBus instanceof QueryBusInterface);
        assert($entityManager instanceof EntityManagerInterface);
        assert($clock instanceof MockClock);

        $this->commandBus = $commandBus;
        $this->queryBus = $queryBus;
        $this->entityManager = $entityManager;
        $this->clock = $clock;

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
    }
}
