<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion\Output;

use PHPUnit\Framework\TestCase;
use function ob_get_clean;
use function ob_start;

final class EchoOutputTest extends TestCase
{
    public function test_should_var_dump_dumped_value(): void
    {
        $output = new EchoOutput();
        ob_start();
        $output->dump('string');
        $actual = ob_get_clean();

        self::assertSame('string', $actual);
    }
}
