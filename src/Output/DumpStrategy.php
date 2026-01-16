<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion\Output;

interface DumpStrategy
{
    public function dump(mixed $data): void;
}
