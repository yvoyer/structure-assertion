<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion\Output;

final class EchoOutput implements DumpStrategy
{
    /**
     * @param mixed $data
     */
    public function dump($data): void
    {
        echo $data;
    }
}
