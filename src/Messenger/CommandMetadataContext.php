<?php

namespace Codr\Eventually\Bundle\Messenger;

use Codr\Eventually\Application\Metadata\MetadataProviderInterface;
use Codr\Eventually\Domain\EventMetadata;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

use function is_string;
use function preg_match;

final class CommandMetadataContext implements MetadataProviderInterface
{
    private ?string $correlationId = null;
    private ?string $commandId = null;

    public function __construct(
        private readonly ActorIdResolverInterface $actorIdResolver,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @param callable(): mixed $handle
     */
    public function around(callable $handle): mixed
    {
        $previousCorrelation = $this->correlationId;
        $previousCommand = $this->commandId;

        $this->commandId = Uuid::v7()->toRfc4122();
        $this->correlationId ??= $this->requestCorrelationId() ?? $this->commandId;

        try {
            return $handle();
        } finally {
            $this->correlationId = $previousCorrelation;
            $this->commandId = $previousCommand;
        }
    }

    public function current(): EventMetadata
    {
        return new EventMetadata(
            $this->correlationId,
            $this->commandId,
            $this->actorIdResolver->actorId(),
        );
    }

    private function requestCorrelationId(): ?string
    {
        $header = $this->requestStack->getMainRequest()?->headers->get('X-Correlation-Id');

        return is_string($header) && 1 === preg_match('/^[A-Za-z0-9._-]{1,64}$/', $header) ? $header : null;
    }
}
