<?php

namespace Codr\Eventually\Bundle\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

use function implode;
use function sprintf;

final class DetailedValidationFailedException extends ValidationFailedException
{
    public static function from(ValidationFailedException $exception): self
    {
        return new self($exception->getViolatingMessage(), $exception->getViolations(), $exception->getEnvelope());
    }

    public function __construct(object $violatingMessage, ConstraintViolationListInterface $violations, ?Envelope $envelope = null)
    {
        parent::__construct($violatingMessage, $violations, $envelope);

        $lines = [];
        foreach ($violations as $violation) {
            $lines[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        $this->message = sprintf('Message of type "%s" failed validation:%s%s', $violatingMessage::class, "\n", implode("\n", $lines));
    }
}
