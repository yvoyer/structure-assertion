<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion\Output;

final class VarDumpOutput implements DumpStrategy
{
    public function dump($data): void
    {
        \var_dump($data);
    }
}
