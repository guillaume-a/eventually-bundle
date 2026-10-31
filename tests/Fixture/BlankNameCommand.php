<?php

namespace Codr\Eventually\Bundle\Tests\Fixture;

use Symfony\Component\Validator\Constraints\NotBlank;

final readonly class BlankNameCommand
{
    public function __construct(
        #[NotBlank]
        public string $name,
    ) {
    }
}
