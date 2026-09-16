<?php

declare(strict_types=1);

namespace GCWorld\Globals\Tests;

use GCWorld\Globals\Globals;
use GCWorld\Globals\GlobalsInterface;
use ReflectionClass;
use ReflectionMethod;

final class GlobalsInterfaceTest extends GlobalsTestCase
{
    public function testInterfaceDeclaresTheConcretePublicApi(): void
    {
        $classMethods = array_map(
            static fn (ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(Globals::class))->getMethods(ReflectionMethod::IS_PUBLIC),
        );
        $interfaceMethods = array_map(
            static fn (ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(GlobalsInterface::class))->getMethods(),
        );

        self::assertSame([], array_values(array_diff($classMethods, $interfaceMethods)));
    }

    public function testArrayFilterDepthIsPartOfTheInterfaceContract(): void
    {
        $parameter = (new ReflectionMethod(GlobalsInterface::class, 'array'))->getParameters()[0];

        self::assertSame('levels', $parameter->getName());
        self::assertSame('int', (string) $parameter->getType());
        self::assertTrue($parameter->isDefaultValueAvailable());
        self::assertSame(1, $parameter->getDefaultValue());
    }
}
