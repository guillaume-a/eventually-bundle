<?php

namespace Codr\Eventually\Bundle\Messenger;

interface ActorIdResolverInterface
{
    public function actorId(): ?string;
}
