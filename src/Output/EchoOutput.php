<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion\Output;

use Star\Component\StructureAssertion\StructureAssertion;

final readonly class EchoOutput implements DumpStrategy
{
    public function dump(mixed $data): void
    {
        echo StructureAssertion::stringify($data);
    }
}
