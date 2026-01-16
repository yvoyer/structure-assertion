<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion\Output;

use function var_dump;

final readonly class VarDumpOutput implements DumpStrategy
{
    public function dump(mixed $data): void
    {
        var_dump($data);
    }
}
