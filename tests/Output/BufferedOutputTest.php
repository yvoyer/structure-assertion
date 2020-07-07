<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion\Output;

use PHPUnit\Framework\TestCase;

final class BufferedOutputTest extends TestCase
{
    public function test_something(): void
    {
        $output = new BufferedDump();
        $this->assertNull($output->getData());

        $output->dump('data');

        $this->assertSame('data', $output->getData());
    }
}
