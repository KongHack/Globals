<?php

declare(strict_types=1);

namespace GCWorld\Globals\Tests;

use GCWorld\Globals\Globals;

final class GlobalsAccessTest extends GlobalsTestCase
{
    public function testValuesCanBeSetAndRetrievedFromSupportedSuperglobals(): void
    {
        $globals = new Globals();

        self::assertTrue($globals->GET('query', 'search'));
        self::assertTrue($globals->POST('count', '5'));
        self::assertTrue($globals->SESSION('authenticated', true));

        self::assertSame('search', $globals->noFilter()->GET('query'));
        self::assertSame(5, $globals->int()->POST('count'));
        self::assertTrue($globals->bool()->SESSION('authenticated'));
    }

    public function testEmptyAndNumericZeroKeysCanBeRetrieved(): void
    {
        $_GET = [0 => 'first', '' => 'empty'];
        $globals = new Globals();

        self::assertSame('first', $globals->noFilter()->GET(0));
        self::assertSame('empty', $globals->noFilter()->GET(''));
    }

    public function testNullCanBeAssignedToAGlobal(): void
    {
        $globals = new Globals();

        self::assertTrue($globals->GET('nullable', null));
        self::assertArrayHasKey('nullable', $_GET);
        self::assertNull($_GET['nullable']);
    }

    public function testCallingGlobalWithoutAKeySelectsItForBatchAccess(): void
    {
        $_GET = ['count' => '5', 'enabled' => 'true', 'code' => '007'];
        $globals = new Globals();

        self::assertSame($globals, $globals->GET());
        self::assertSame(
            ['count' => 5, 'enabled' => true, 'code' => '007'],
            $globals->filterAll(),
        );

        self::assertSame($globals, $globals->GET());
        self::assertSame($_GET, $globals->filterNone());
    }

    public function testGetKeysAcceptsNamesWithOrWithoutLeadingUnderscore(): void
    {
        $_POST = ['first' => 1, 'second' => 2];
        $globals = new Globals();

        self::assertSame(['first', 'second'], $globals->getKeys('POST'));
        self::assertSame(['first', 'second'], $globals->getKeys('_POST'));
        self::assertNull($globals->getKeys('MISSING'));
    }

    public function testMissingValuesUseTheSelectedFilterDefaultWhenEnabled(): void
    {
        $globals = new Globals();
        $globals->defaults(true);

        self::assertSame(0, $globals->int()->GET('missing'));
        self::assertSame('', $globals->email()->GET('missing'));
        self::assertSame([], $globals->array()->GET('missing'));
        self::assertNull($globals->noFilter()->GET('missing'));
    }

    public function testMissingValuesReturnNullWhenDefaultsAreDisabled(): void
    {
        self::assertNull((new Globals())->int()->GET('missing'));
    }

    public function testAutomaticFilteringIsStableAcrossRepeatedReads(): void
    {
        $_GET['value'] = '42';
        $globals = new Globals();

        self::assertSame(42, $globals->GET('value'));
        self::assertSame(42, $globals->GET('value'));
    }

    public function testArrayFilteringAppliesAFilterToEveryValue(): void
    {
        $_GET['values'] = ['1', 'invalid', '3'];

        self::assertSame(
            [1, false, 3],
            (new Globals())->array()->int()->GET('values'),
        );
    }

    public function testArrayFilteringHonorsTheConfiguredDepth(): void
    {
        $_GET['values'] = [['1', '2'], ['3']];

        self::assertSame(
            [[1, 2], [3]],
            (new Globals())->array(2)->int()->GET('values'),
        );
    }

    public function testArrayFilteringRejectsScalarInput(): void
    {
        $_GET['value'] = '1';

        self::assertSame([], (new Globals())->array()->int()->GET('value'));
    }
}
