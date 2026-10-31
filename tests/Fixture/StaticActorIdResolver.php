<?php

namespace Codr\Eventually\Bundle\Tests\Fixture;

use Codr\Eventually\Bundle\Messenger\ActorIdResolverInterface;

final readonly class StaticActorIdResolver implements ActorIdResolverInterface
{
    public function actorId(): string
    {
        return 'actor-1';
    }
}
