<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion\Output;

use PHPUnit\Framework\TestCase;

final class BufferedOutputTest extends TestCase
{
    public function test_dumping(): void
    {
        $output = new BufferedDump();
        self::assertNull($output->getData());

        $output->dump('data');

        self::assertSame('data', $output->getData());
    }
}
