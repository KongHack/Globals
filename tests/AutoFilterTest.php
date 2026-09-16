<?php

declare(strict_types=1);

namespace GCWorld\Globals\Tests;

use GCWorld\Globals\Globals;

final class AutoFilterTest extends GlobalsTestCase
{
    public function testAutomaticFilteringCoercesRecognizedScalarValues(): void
    {
        $globals = new Globals();

        self::assertNull($globals->autoFilterManualVar(null));
        self::assertTrue($globals->autoFilterManualVar('true'));
        self::assertTrue($globals->autoFilterManualVar('y'));
        self::assertFalse($globals->autoFilterManualVar('false'));
        self::assertFalse($globals->autoFilterManualVar('n'));
        self::assertSame(42, $globals->autoFilterManualVar('42'));
        self::assertSame(-12, $globals->autoFilterManualVar('-12'));
        self::assertSame(12.5, $globals->autoFilterManualVar('12.5'));
        self::assertSame('012', $globals->autoFilterManualVar('012'));
    }

    public function testAutomaticFilteringRecursesThroughArrays(): void
    {
        $globals = new Globals();

        self::assertSame(
            [
                'enabled' => true,
                'count' => 3,
                'nested' => ['ratio' => 1.5, 'code' => '007'],
            ],
            $globals->autoFilterManualVar([
                'enabled' => 'true',
                'count' => '3',
                'nested' => ['ratio' => '1.5', 'code' => '007'],
            ]),
        );
    }

    public function testAutomaticFilteringLeavesObjectsUntouched(): void
    {
        $value = new \stdClass();

        self::assertSame($value, (new Globals())->autoFilterManualVar($value));
    }
}
