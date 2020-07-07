<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion\Output;

use PHPUnit\Framework\TestCase;

final class VarDumpOutputTest extends TestCase
{
    public function test_should_var_dump_dumped_value(): void
    {
        $output = new VarDumpOutput();
        \ob_start();
        $output->dump('string');
        $actual = (string) \ob_get_clean();

        $this->assertStringContainsString('string(6) "string"', $actual);
    }
}
