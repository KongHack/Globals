<?php

declare(strict_types=1);

namespace GCWorld\Globals\Tests;

use PHPUnit\Framework\TestCase;

abstract class GlobalsTestCase extends TestCase
{
    private const SUPERGLOBALS = [
        '_COOKIE',
        '_ENV',
        '_FILES',
        '_GET',
        '_POST',
        '_REQUEST',
        '_SERVER',
        '_SESSION',
    ];

    /** @var array<string, array<mixed>> */
    private array $originalValues = [];

    /** @var array<string, true> */
    private array $originallyDefined = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::SUPERGLOBALS as $name) {
            if (array_key_exists($name, $GLOBALS)) {
                $this->originalValues[$name] = $GLOBALS[$name];
                $this->originallyDefined[$name] = true;
            }

            $GLOBALS[$name] = [];
        }
    }

    protected function tearDown(): void
    {
        foreach (self::SUPERGLOBALS as $name) {
            if (isset($this->originallyDefined[$name])) {
                $GLOBALS[$name] = $this->originalValues[$name];
            } else {
                unset($GLOBALS[$name]);
            }
        }

        parent::tearDown();
    }
}
