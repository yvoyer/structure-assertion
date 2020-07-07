<?php declare(strict_types=1);

namespace Star\Component\StructureAssertion;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint;
use Psr\Http\Message\ResponseInterface;
use Star\Component\StructureAssertion\Output\EchoOutput;
use Star\Component\StructureAssertion\Output\DumpStrategy;
use Star\Component\StructureAssertion\Output\VarDumpOutput;

class StructureAssertion
{
    private const ALL_DEPTH = -1;

    /**
     * int when navigating array node
     * string when object node
     *
     * @var string|int
     */
    private $nodeIndex;

    /**
     * @var mixed[]
     */
    private $data;

    /**
     * @var static|null
     */
    private $previous;

    /**
     * @param int|string $nodeIndex
     * @param mixed[] $data
     * @param static|null $previous
     */
    final private function __construct($nodeIndex, array $data, StructureAssertion $previous = null)
    {
        $this->nodeIndex = $nodeIndex;
        $this->data = $data;
        $this->previous = $previous;
    }

    /**
     * @param string $node
     *
     * @return static
     */
    final public function enterObjectNode(string $node): self
    {
        return $this->enterArrayNode($node);
    }

    /**
     * @param int $node
     * @return static
     */
    final public function enterArrayElement(int $node = 0): self
    {
        Assert::assertArrayHasKey($node, $this->data);
        Assert::assertIsArray($this->data[$node]);

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
     * @param string $node
     *
     * @return static
     */
    final public function enterArrayNode(string $node): self
    {
        Assert::assertArrayHasKey($node, $this->data);
        Assert::assertIsArray($this->data[$node]);

        return new static($node, $this->data[$node], $this);
    }

    /**
     * @return static
     */
    final public function exitNode(): self
    {
        if (! $this->previous instanceof StructureAssertion) {
            throw new \InvalidArgumentException(
                'No previous node exists, exiting is not possible. use debug to show data.'
            );
        }

        return $this->previous;
    }

    /**
     * @return static
     */
    final public function nextArrayElement(): self
    {
        Assert::assertIsInt(
            $this->nodeIndex,
            'Cannot move to next sibling when current node is object.'
        );
        return $this->exitNode()->enterArrayElement((int) $this->nodeIndex + 1);
    }

    /**
     * @param string $property
     * @param mixed $value
     *
     * @return static
     */
    final public function assertIsSame(string $property, $value): self
    {
        return $this->propertyIs($property, new Constraint\IsIdentical($value));
    }

    /**
     * @param string $property
     * @param mixed $value
     *
     * @return static
     */
    final public function assertIsEqual(string $property, $value): self
    {
        return $this->propertyIs($property, new Constraint\IsEqual($value));
    }

    /**
     * @param string $property
     * @param callable $value
     *
     * @return static
     */
    final public function assertCallback(string $property, callable $value): self
    {
        return $this->propertyIs($property, new Constraint\Callback($value));
    }

    /**
     * @param string $property
     * @param string $value
     *
     * @return static
     */
    final public function assertContains(string $property, string $value): self
    {
        return $this->propertyIs($property, new Constraint\StringContains($value));
    }

    /**
     * @param string $property
     * @param mixed[] $value
     *
     * @return static
     */
    final public function assertEqualToArray(string $property, array $value): self
    {
        return $this->propertyIs($property, new Constraint\IsEqual($value));
    }

    /**
     * @param int $value
     *
     * @return static
     */
    final public function assertCount(int $value): self
    {
        Assert::assertCount(
            $value,
            $this->data,
            \sprintf('Element count in Node "%s" is not as expected.', $this->nodeIndex)
        );

        return $this;
    }

    /**
     * @param string $property
     *
     * @return static
     */
    final public function assertIsNull(string $property): self
    {
        return $this->propertyIs($property, new Constraint\IsNull());
    }

    /**
     * @param string $property
     *
     * @return static
     */
    final public function assertIsNotSet(string $property): self
    {
        Assert::assertArrayNotHasKey($property, $this->data);

        return $this;
    }

    /**
     * @param string $property
     *
     * @return static
     */
    final public function assertIsNotNull(string $property): self
    {
        return $this->propertyIs($property, new Constraint\LogicalNot(new Constraint\IsNull()));
    }

    /**
     * @param string $property
     *
     * @return static
     */
    final public function assertIsTrue(string $property): self
    {
        return $this->propertyIs($property, new Constraint\IsTrue());
    }

    /**
     * @param string $property
     *
     * @return static
     */
    final public function assertIsFalse(string $property): self
    {
        return $this->propertyIs($property, new Constraint\IsFalse());
    }

    /**
     * @param string $property
     * @param Constraint\Constraint $constraint
     * @return static
     */
    private function propertyIs(string $property, Constraint\Constraint $constraint): self
    {
        Assert::assertArrayHasKey($property, $this->data);
        $constraint->evaluate($this->data[$property]);

        return $this;
    }

    /**
     * Dump all the data up to $max depth using the $strategy
     * @param int $max_depth The depth to output, default: 2 level. use -1 for infinite.
     * @param DumpStrategy $strategy
     *
     * @return static
     */
    final public function dump(int $max_depth = 2, DumpStrategy $strategy = null): self
    {
        if (! $strategy) {
            $strategy = new VarDumpOutput();
        }

        $strategy->dump($this->export($this->data, $max_depth));

        return $this;
    }

    final public function dumpPath(DumpStrategy $strategy = null, string $path = ''): self
    {
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

    /**
     * @param DumpStrategy|null $strategy
     * @return static
     */
    final public function dumpKeys(DumpStrategy $strategy = null): self
    {
        if (!$strategy) {
            $strategy = new VarDumpOutput();
        }

        $strategy->dump(\array_keys($this->data));

        return $this;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return static
     */
    public static function fromJsonResponse(ResponseInterface $response): self
    {
        return static::fromArray(\json_decode($response->getBody()->getContents(), true));
    }

    /**
     * @param mixed[] $data
     *
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new static('root', $data);
    }

    /**
     * @param mixed $data
     * @param int $max_depth
     * @return mixed[]
     */
    final private function export($data, int $max_depth)
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
}
