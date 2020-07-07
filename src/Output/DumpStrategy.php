<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion\Output;

interface DumpStrategy
{
    /**
     * @param mixed $data
     */
    public function dump($data): void;
}
