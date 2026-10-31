<?php

namespace Codr\Eventually\Bundle\Tests\Functional;

use Codr\Eventually\Bundle\Tests\EventuallyKernelTestCase;
use Codr\Eventually\Bundle\Tests\Fixture\BlankNameCommand;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

final class ValidationMessageTest extends EventuallyKernelTestCase
{
    public function testCommandBusListsTheViolationsInTheMessage(): void
    {
        try {
            $this->commandBus->dispatch(new BlankNameCommand(''));
            self::fail('Validation must fail.');
        } catch (ValidationFailedException $exception) {
            self::assertStringContainsString('name: This value should not be blank.', $exception->getMessage());
            self::assertCount(1, $exception->getViolations());
        }
    }

    public function testQueryBusListsTheViolationsInTheMessage(): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('name: This value should not be blank.');

        $this->queryBus->ask(new BlankNameCommand(''));
    }
}
