<?php

namespace Codr\Eventually\Bundle\Messenger;

use Codr\Eventually\Application\Bus\QueryBusInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerQueryBus implements QueryBusInterface
{
    use HandleTrait;

    public function __construct(MessageBusInterface $queryBus)
    {
        $this->messageBus = $queryBus;
    }

    public function ask(object $query): mixed
    {
        try {
            return $this->handle($query);
        } catch (ValidationFailedException $exception) {
            throw DetailedValidationFailedException::from($exception);
        } catch (HandlerFailedException $exception) {
            throw $exception->getPrevious() ?? $exception;
        }
    }
}
