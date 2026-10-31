<?php

namespace Codr\Eventually\Bundle\Tests\Fixture;

final readonly class IncrementCounter
{
    public function __construct(public string $id)
    {
    }
}
