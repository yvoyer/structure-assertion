<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Star\Component\StructureAssertion\Output\BufferedDump;

final class StructureAssertionTest extends TestCase
{
    /**
     * @var StructureAssertion
     */
    private $assertion;

    /**
     * @var array[]
     */
    private $fixture = [
        'data' => [
            'string' => 'level 0',
            'object' => [
                'data' => [
                    'string' => 'level 1',
                    'object' => [
                        'data' => [
                            'string' => 'level 2',
                            'object' => [
                                'data' => [
                                    'string' => 'level 3',
                                ],
                            ],
                            'array' => [
                                [
                                    'string' => 'level 3',
                                ],
                            ],
                        ],
                    ],
                    'array' => [
                        [
                            'string' => 'level 2',
                            'object' => [
                                'data' => [
                                    'string' => 'level 3',
                                ],
                            ],
                            'array' => [
                                [
                                    'string' => 'level 3',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'array' => [
                [
                    'string' => 'level 1',
                    'object' => [
                        'data' => [
                            'string' => 'level 2',
                            'object' => [
                                'data' => [
                                    'string' => 'level 3',
                                ],
                            ],
                            'array' => [
                                [
                                    'string' => 'level 3',
                                ],
                            ],
                        ],
                    ],
                    'array' => [
                        [
                            'string' => 'level 2',
                            'object' => [
                                'data' => [
                                    'string' => 'level 3',
                                ],
                            ],
                            'array' => [
                                [
                                    'string' => 'level 3',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->assertion = StructureAssertion::fromArray($this->fixture);
    }

    public function test_it_should_dump_first_level(): void
    {
        $this->assertion->dump(1, $content = new BufferedDump());

        $this->assertSame(
            [
                'data' => [
                    '...',
                ],
            ],
            $content->getData()
        );
    }

    public function test_it_should_dump_up_to_second_level_as_default(): void
    {
        $this->assertion->dump(2, $content = new BufferedDump());

        $this->assertSame(
            [
                'data' => [
                    'string' => 'level 0',
                    'object' => [
                        '...',
                    ],
                    'array' => [
                        '...'
                    ],
                ],
            ],
            $content->getData()
        );
    }

    public function test_it_should_dump_the_path_of_root_node(): void
    {
        $this->assertion->dumpPath($content = new BufferedDump());

        $this->assertSame('root', $content->getData());
    }

    public function test_it_should_dump_the_path_of_current_node(): void
    {
        $this->assertion
            ->enterDataNode()
            ->enterObjectNode('object')
            ->enterDataNode()
            ->enterObjectNode('array')
            ->enterArrayElement(0)
            ->enterObjectNode('object')
            ->enterDataNode()
            ->assertIsSame('string', 'level 3')
            ->dumpPath($content = new BufferedDump())
        ;

        $this->assertSame('root.data.object.data.array.0.object.data', $content->getData());
    }

    public function test_it_should_dump_all_data(): void
    {
        $this->assertion->dump(-1, $content = new BufferedDump());
        $this->assertSame($this->fixture, $content->getData());
    }

    public function test_it_should_assert_array_node(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that \'\' is of type "array".');
        StructureAssertion::fromArray(
            [
                'array' => '',
            ]
        )
            ->enterArrayNode('array');
    }

    public function test_it_should_enter_next_array_element(): void
    {
        StructureAssertion::fromArray(
            [
                'array' => [
                    [
                        'id' => 1,
                    ],
                    [
                        'id' => 2,
                    ],
                    [
                        'id' => 3,
                    ],
                ],
            ]
        )
            ->enterArrayNode('array')
            ->assertCount(3)
            ->enterArrayElement()->assertIsSame('id', 1)
            ->nextArrayElement()->assertIsSame('id', 2)
            ->nextArrayElement()->assertIsSame('id', 3)
        ;
    }

    public function test_it_should_assert_equals(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that two strings are equal.');
        StructureAssertion::fromArray(['data' => ''])
            ->assertIsEqual('data', 'value')
        ;
    }

    public function test_it_should_assert_contains(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that \'\' contains "value".');
        StructureAssertion::fromArray(['data' => ''])
            ->assertContains('data', 'value')
        ;
    }

    public function test_it_should_assert_callback(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("Failed asserting that 'value' is accepted by specified callback.");
        StructureAssertion::fromArray(['data' => 'value'])
            ->assertCallback('data', function () {
                return false;
            })
        ;
    }

    public function test_it_should_assert_equals_array(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that two arrays are equal.');
        StructureAssertion::fromArray(['data' => ['value']])
            ->assertEqualToArray('data', [''])
        ;
    }

    public function test_it_should_assert_is_null(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that 0 is null.');
        StructureAssertion::fromArray(['data' => 0])
            ->assertIsNull('data')
        ;
    }

    public function test_it_should_assert_is_not_set(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("Failed asserting that an array does not have the key 'data'.");
        StructureAssertion::fromArray(['data' => 1])
            ->assertIsNotSet('data')
        ;
    }

    public function test_it_should_assert_is_not_null(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that null is not null.');
        StructureAssertion::fromArray(['data' => null])
            ->assertIsNotNull('data')
        ;
    }

    public function test_it_should_assert_is_true(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that false is true.');
        StructureAssertion::fromArray(['data' => false])
            ->assertIsTrue('data')
        ;
    }

    public function test_it_should_assert_is_false(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that true is false.');
        StructureAssertion::fromArray(['data' => true])
            ->assertIsFalse('data')
        ;
    }

    public function test_it_should_dump_current_keys(): void
    {
        $this->assertion->dumpKeys($output = new BufferedDump());
        $this->assertSame(['data'], $output->getData());
    }

    public function test_it_should_build_from_response(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->method('getContents')
            ->willReturn('{"id":22}')
        ;
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getBody')
            ->willReturn($stream)
        ;

        StructureAssertion::fromJsonResponse($response)
            ->assertIsSame('id', 22)
        ;
    }

    public function test_it_should_throw_exception_when_object_node_is_not_set(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("Failed asserting that an array has the key 'data'.");
        StructureAssertion::fromArray([])->enterObjectNode('data');
    }

    public function test_it_should_throw_exception_when_object_node_is_not_array(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that \'\' is of type "array".');
        StructureAssertion::fromArray(['data' => ''])->enterObjectNode('data');
    }

    public function test_it_should_throw_exception_when_array_node_is_not_set(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("Failed asserting that an array has the key 'data'.");
        StructureAssertion::fromArray([])->enterArrayNode('data');
    }

    public function test_it_should_throw_exception_when_array_node_is_not_array(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that \'\' is of type "array".');
        StructureAssertion::fromArray(['data' => ''])->enterArrayNode('data');
    }

    public function test_it_should_throw_exception_when_array_element_is_not_set(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that an array has the key 0.');
        StructureAssertion::fromArray([])->enterArrayElement();
    }

    public function test_it_should_throw_exception_when_array_element_is_not_array(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that \'data\' is of type "array".');
        StructureAssertion::fromArray(['data'])->enterArrayElement();
    }

    public function test_it_should_throw_exception_when_last_node(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No previous node exists, exiting is not possible. use debug to show data.');
        StructureAssertion::fromArray([])->exitNode();
    }

    public function test_it_should_throw_exception_when_next_array_element_is_not_int(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that an array has the key 1.');
        StructureAssertion::fromArray([[], 'two' => []])
            ->enterArrayElement()
            ->nextArrayElement();
    }

    public function test_it_should_cast_array_element_index_to_int(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that an array has the key 1.');
        StructureAssertion::fromArray([[], '2' => []])
            ->enterArrayElement()
            ->nextArrayElement();
    }

    public function test_it_should_throw_exception_when_next_current_node_is_string(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Cannot move to next sibling when current node is object.');
        StructureAssertion::fromArray(['one' => ['id' => 1], 'two' => ['id' => 2],])
            ->nextArrayElement();
    }

    public function test_it_should_assert_count(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Element count in Node "root" is not as expected.');
        StructureAssertion::fromArray([])
            ->assertCount(1);
    }

    public function test_it_should_throw_exception_when_property_not_exists_on_assert(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that an array has the key \'id\'.');
        StructureAssertion::fromArray([])
            ->assertIsSame('id', 1);
    }

    public function test_it_should_allow_extending(): void
    {
        ExtendDataAssertion::fromArray(['data' => ['id' => false]])
            ->assertMyCustomStuff('id');
    }
}

final class ExtendDataAssertion extends StructureAssertion
{
    public function assertMyCustomStuff(string $index): ExtendDataAssertion
    {
        /**
         * @var static $assert
         */
        $assert = $this->enterDataNode();

        return $assert->assertIsFalse($index);
    }
}
