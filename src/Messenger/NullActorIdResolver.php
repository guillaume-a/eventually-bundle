<?php

namespace Codr\Eventually\Bundle\Messenger;

final readonly class NullActorIdResolver implements ActorIdResolverInterface
{
    public function actorId(): ?string
    {
        return null;
    }
}
