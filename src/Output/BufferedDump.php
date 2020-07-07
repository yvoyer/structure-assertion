<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion\Output;

final class BufferedDump implements DumpStrategy
{
    /**
     * @var array|string[]
     */
    private $data;

    /**
     * @param mixed $data
     */
    public function dump($data): void
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
