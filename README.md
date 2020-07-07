# Structure assertion

PHP Tool for asserting array structures using the fluent interface.

[![Build Status](https://travis-ci.org/yvoyer/structure-assertion.svg?branch=master)](https://travis-ci.org/yvoyer/structure-assertion)

## Installation

Using [Composer](https://getcomposer.org/), run `composer require --dev star/structure-assertion`.

## Usage

This library is usefull when you have [PHPUnit tests](https://phpunit.de), and you want to assert some nodes
of an array.

```php
StructureAssertion::fromArray(
    [
        'data' => [
            'id' => 11,
            'array' => [
                1, 
                2,
                3,
            ],
        ],
    ]
)
    ->enterObjectNode('data') // Assert the node 'data' exists and is an object
    ->assertIsSame('id', 11) // Assert the object's property 'id' exists and match the exact value
    ->enterArrayNode('array') // Assert the node 'data' exists and is an array
    ->assertCount(3); // Assert the number of item is exactly 3
``` 

## Construction methods

* `StructureAssertion::fromArray($array);`: Using the given array.
* `StructureAssertion::fromJsonResponse($response);`: Build with a [Response](https://github.com/php-fig/http-message/blob/master/src/ResponseInterface.php) object that is assumed to have a JSON content.

## Assertions methods

By convention, all assertion methods are named `assert*`.
 Under the hood, StructureAssertion uses PHPUnit assertions. 
 Many of the assertions are named after the PHPUnit's constraint.
 
You can use your own assertion if a specific one is not defined by using the `StructureAssertion::assertCallback()` method. 

ie. 

```php
StructureAssertion::fromArray($data)
    ->assertCallback('property', function ($value): bool {
        // When it evaluates to false, the expectation will fail
        // return true | false
    });
```

## Navigation methods

To navigate the nodes, you can use the methods that starts with `enter*`. We assume that the current node is at the correct position.

* `StructureAssertion::exitNode()`: Move the internal pointer to the parent node.
* `StructureAssertion::nextArrayElement()`: Shortcut for `exitNode()->enterArrayElement($current + 1)`. Enters the next element at index + 1. Works only on integer indexed element.

## Debugging methods

The lib also provide debugging method, in order to know the current position of the cursor.

* `StructureAssertion::dump($maxDepth = 2, $dumpStrategy)`: Will dump the current node using the `$strategy` and up to `$maxDepth`.
* `StructureAssertion::dumpPath($dumpStrategy)`: Will dump the current node path using the `$strategy`.
* `StructureAssertion::dumpKeys($dumpStrategy)`: Will dump the current node keys using the `$strategy`.

## Contributing

Any contribution is welcome, just propose a Pull request, and we'll look into it.
