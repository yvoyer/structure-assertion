<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion;

use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint;
use Psr\Http\Message\ResponseInterface;
use Star\Component\StructureAssertion\Output\EchoOutput;
use Star\Component\StructureAssertion\Output\DumpStrategy;
use Star\Component\StructureAssertion\Output\VarDumpOutput;
use function array_keys;
use function get_class;
use function get_resource_type;
use function gettype;
use function is_array;
use function is_bool;
use function is_object;
use function is_resource;
use function is_scalar;
use function json_decode;
use function mb_strlen;
use function mb_substr;
use function sprintf;

class StructureAssertion
{
    private const ALL_DEPTH = -1;

    /**
     * int when navigating array node
     * string when object node
     */
    private string|int $nodeIndex;

    /**
     * @var array<string|int, mixed>
     */
    private readonly array $data;

    private ?StructureAssertion $previous;

    /**
     * @param array<string|int, mixed> $data
     */
    final private function __construct(
        int|string $nodeIndex,
        array $data,
        ?StructureAssertion $previous = null
    ) {
        $this->nodeIndex = $nodeIndex;
        $this->data = $data;
        $this->previous = $previous;
    }

    /**
     * @return static
     */
    final public function enterObjectNode(string $node): self
    {
        return $this->enterArrayNode($node);
    }

    /**
     * @return static
     */
    final public function enterArrayElement(int $node = 0): self
    {
        Assert::assertArrayHasKey($node, $this->data);
        Assert::assertIsArray(
            $this->data[$node],
            sprintf(
                'Failed asserting that "%s" is of type "array".',
                self::stringify($this->data[$node]),
            )
        );

        return new static($node, $this->data[$node], $this);
    }

    /**
     * @return StructureAssertion
     */
    final public function enterDataNode(): self
    {
        return $this->enterArrayNode('data');
    }

    /**
     * @return static
     */
    final public function enterArrayNode(string $node): self
    {
        Assert::assertArrayHasKey($node, $this->data);
        Assert::assertIsArray(
            $this->data[$node],
            sprintf(
                'Failed asserting that "%s" is of type "array".',
                self::stringify($this->data[$node]),
            )
        );

        return new static($node, $this->data[$node], $this);
    }

    final public function exitNode(): static
    {
        if (! $this->previous instanceof static) {
            throw new InvalidArgumentException(
                'No previous node exists, exiting is not possible. use debug to show data.'
            );
        }

        return $this->previous;
    }

    final public function nextArrayElement(): static
    {
        Assert::assertIsInt(
            $this->nodeIndex,
            'Cannot move to next sibling when current node is object.'
        );
        return $this->exitNode()->enterArrayElement($this->nodeIndex + 1);
    }

    final public function assertIsSame(string $property, mixed $value): static
    {
        return $this->propertyIs($property, new Constraint\IsIdentical($value));
    }

    final public function assertIsEqual(string $property, mixed $value): static
    {
        return $this->propertyIs($property, new Constraint\IsEqual($value));
    }

    final public function assertCallback(string $property, callable $value): static
    {
        return $this->propertyIs($property, new Constraint\Callback($value));
    }

    final public function assertContains(string $property, string $value): static
    {
        return $this->propertyIs($property, new Constraint\StringContains($value));
    }

    /**
     * @param array<string|int, mixed> $value
     */
    final public function assertEqualToArray(string $property, array $value): static
    {
        return $this->propertyIs($property, new Constraint\IsEqual($value));
    }

    final public function assertCount(int $value): static
    {
        Assert::assertCount(
            $value,
            $this->data,
            sprintf('Element count in Node "%s" is not as expected.', $this->nodeIndex)
        );

        return $this;
    }

    final public function assertIsNull(string $property): static
    {
        return $this->propertyIs($property, new Constraint\IsNull());
    }

    final public function assertIsNotSet(string $property): static
    {
        Assert::assertArrayNotHasKey($property, $this->data);

        return $this;
    }

    final public function assertIsNotNull(string $property): static
    {
        return $this->propertyIs($property, new Constraint\LogicalNot(new Constraint\IsNull()));
    }

    final public function assertIsTrue(string $property): static
    {
        return $this->propertyIs($property, new Constraint\IsTrue());
    }

    final public function assertIsFalse(string $property): static
    {
        return $this->propertyIs($property, new Constraint\IsFalse());
    }

    private function propertyIs(string $property, Constraint\Constraint $constraint): static
    {
        Assert::assertArrayHasKey($property, $this->data);
        $constraint->evaluate($this->data[$property]);

        return $this;
    }

    /**
     * Dump all the data up to $max depth using the $strategy
     * @param int $max_depth The depth to output, default: 2 level. use -1 for infinite.
     */
    final public function dump(
        int $max_depth = 2,
        ?DumpStrategy $strategy = null,
    ): static {
        if (! $strategy) {
            $strategy = new VarDumpOutput();
        }

        $strategy->dump($this->export($this->data, $max_depth));

        return $this;
    }

    final public function dumpPath(
        ?DumpStrategy $strategy = null,
        string $path = '',
    ): static {
        if (!$strategy) {
            $strategy = new EchoOutput();
        }

        if (! $this->previous) {
            $strategy->dump('root' . $path);
            return $this;
        }

        $this->previous->dumpPath($strategy, '.' . $this->nodeIndex . $path);

        return $this;
    }

    final public function dumpKeys(
        ?DumpStrategy $strategy = null,
    ): static {
        if (!$strategy) {
            $strategy = new VarDumpOutput();
        }

        $strategy->dump(array_keys($this->data));

        return $this;
    }

    public static function fromJsonResponse(
        ResponseInterface $response,
    ): static {
        $json = $response->getBody()->getContents();
        Assert::assertJson($json);
        $data = json_decode($json, true);
        Assert::assertIsArray($data);

        return static::fromArray($data);
    }

    /**
     * @param array<string|int, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static('root', $data);
    }

    private function export(mixed $data, int $max_depth): mixed
    {
        if (! is_array($data) || $max_depth === self::ALL_DEPTH) {
            return $data;
        }

        if ($max_depth === 0) {
            return ['...'];
        }

        $return = [];
        foreach ($data as $key => $value) {
            $return[$key] = $this->export($value, $max_depth - 1);
        }

        return $return;
    }

    public static function stringify(mixed $value): string
    {
        $result = gettype($value);

        if (is_bool($value)) {
            $result = $value ? '<TRUE>' : '<FALSE>';
        } elseif (is_scalar($value)) {
            $val = (string) $value;

            if (mb_strlen($val) > 100) {
                $val = mb_substr($val, 0, 97).'...';
            }

            $result = $val;
        } elseif (is_array($value)) {
            $result = '<ARRAY>';
        } elseif (is_object($value)) {
            $result = get_class($value);
        } elseif (is_resource($value)) {
            $result = get_resource_type($value);
        } elseif (null === $value) {
            $result = '<NULL>';
        }

        return $result;
    }
}
