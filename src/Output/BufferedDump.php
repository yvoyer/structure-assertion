<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion\Output;

final class BufferedDump implements DumpStrategy
{
    private mixed $data = null;

    public function dump(mixed $data): void
    {
        $this->data = $data;
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}
