<?php

declare(strict_types=1);

namespace GCWorld\Globals\Tests;

use GCWorld\Globals\Globals;
use Ramsey\Uuid\Uuid;

final class FilterTest extends GlobalsTestCase
{
    public function testNumericAndBooleanFiltersReturnTypedValues(): void
    {
        $_GET = [
            'integer' => '42',
            'invalidInteger' => 'four',
            'float' => '12.5',
            'true' => 'true',
            'false' => 'false',
        ];
        $globals = new Globals();

        self::assertSame(42, $globals->int()->GET('integer'));
        self::assertSame(0, $globals->int()->GET('invalidInteger'));
        self::assertSame(12.5, $globals->float()->GET('float'));
        self::assertTrue($globals->bool()->GET('true'));
        self::assertFalse($globals->bool()->GET('false'));
    }

    public function testStandardValidationFiltersAcceptAndRejectValues(): void
    {
        $_GET = [
            'ip' => '192.0.2.1',
            'email' => 'person@example.com',
            'invalidEmail' => 'not-an-email',
            'url' => 'https://example.com/path',
            'mac' => '00:11:22:33:44:55',
        ];
        $globals = new Globals();

        self::assertSame('192.0.2.1', $globals->ip()->GET('ip'));
        self::assertSame('person@example.com', $globals->email()->GET('email'));
        self::assertSame('', $globals->email()->GET('invalidEmail'));
        self::assertSame('https://example.com/path', $globals->url()->GET('url'));
        self::assertSame('00:11:22:33:44:55', $globals->mac()->GET('mac'));
    }

    public function testIpVersionFiltersAcceptOnlyTheRequestedVersion(): void
    {
        $_GET = [
            'ipv4' => '192.0.2.1',
            'ipv6' => '2001:db8::1',
        ];
        $globals = new Globals();

        self::assertSame('192.0.2.1', $globals->ipv4()->GET('ipv4'));
        self::assertSame('', $globals->ipv4()->GET('ipv6'));
        self::assertSame('2001:db8::1', $globals->ipv6()->GET('ipv6'));
        self::assertSame('', $globals->ipv6()->GET('ipv4'));

        $globals->defaults(true);
        self::assertSame('0.0.0.0', $globals->ipv4()->GET('missing'));
        self::assertSame('::/0', $globals->ipv6()->GET('missing'));
    }

    public function testStringFiltersApplyTheirDocumentedTransformations(): void
    {
        $_GET = [
            'tags' => '  <b>Example</b>  ',
            'strict' => "  O'Reilly! -- Example_2  ",
            'special' => 'Tom & Jerry',
            'full' => '<b>Tom & Jerry</b>',
        ];
        $globals = new Globals();

        self::assertSame('Example', $globals->string()->GET('tags'));
        self::assertSame("O'Reilly -- Example2", $globals->stringStrict()->GET('strict'));
        self::assertSame('Tom &#38; Jerry', $globals->stringSpecial()->GET('special'));
        self::assertSame('&lt;b&gt;Tom &amp; Jerry&lt;/b&gt;', $globals->stringFull()->GET('full'));
    }

    public function testDateFiltersNormalizeValidValuesAndDefaultInvalidValues(): void
    {
        $_GET = [
            'date' => '2025-03-14',
            'dateTime' => '2025-03-14 15:09:26',
            'invalidDate' => 'not-a-date',
        ];
        $globals = new Globals();

        self::assertSame('2025-03-14', $globals->date()->GET('date'));
        self::assertSame('2025-03-14 15:09:26', $globals->dateTime()->GET('dateTime'));
        self::assertSame('0000-00-00', $globals->date()->GET('invalidDate'));
    }

    public function testJsonFiltersDecodeArraysAndObjects(): void
    {
        $_GET = [
            'array' => '{"name":"Ada","roles":["admin"]}',
            'object' => '{"name":"Ada"}',
            'invalid' => '{',
        ];
        $globals = new Globals();

        self::assertSame(
            ['name' => 'Ada', 'roles' => ['admin']],
            $globals->json(true)->GET('array'),
        );

        $object = $globals->json(false)->GET('object');
        self::assertInstanceOf(\stdClass::class, $object);
        self::assertSame('Ada', $object->name);

        self::assertSame([], $globals->json(true)->GET('invalid'));
        self::assertEquals(new \stdClass(), $globals->json(false)->GET('invalid'));
    }

    public function testJsonFiltersRejectScalarAndMismatchedContainerValues(): void
    {
        $_GET = [
            'true' => 'true',
            'number' => '42',
            'null' => 'null',
            'array' => '["first","second"]',
        ];
        $globals = new Globals();

        foreach (['true', 'number', 'null'] as $key) {
            self::assertSame([], $globals->json(true)->GET($key));
            self::assertEquals(new \stdClass(), $globals->json(false)->GET($key));
        }

        self::assertEquals(new \stdClass(), $globals->json(false)->GET('array'));
    }

    public function testMostRecentlySelectedFilterReplacesEarlierFilterState(): void
    {
        $_GET = [
            'htmlNumber' => '<b>42</b>',
            'plainText' => 'forty-two',
            'email' => 'person@example.com',
            'mixedCaseHtml' => '<b>Mixed</b>',
        ];
        $globals = new Globals();

        self::assertSame(0, $globals->string()->int()->GET('htmlNumber'));
        self::assertSame('forty-two', $globals->int()->string()->GET('plainText'));
        self::assertSame('person@example.com', $globals->ipv6()->email()->GET('email'));
        self::assertSame('Mixed', $globals->callback(strtoupper(...))->string()->GET('mixedCaseHtml'));
    }

    public function testUuidFiltersReturnCanonicalStringsAndBytes(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $_GET = ['uuid' => strtoupper($uuid), 'invalid' => 'not-a-uuid'];
        $globals = new Globals();

        self::assertSame($uuid, $globals->uuid()->GET('uuid'));
        self::assertSame(Uuid::fromString($uuid)->getBytes(), $globals->uuid(true)->GET('uuid'));
        self::assertSame('', $globals->uuid()->GET('invalid'));
        self::assertNull($globals->uuid(true)->GET('invalid'));
    }

    public function testBase64FilterDecodesValidInput(): void
    {
        $_GET = [
            'encoded' => base64_encode('hello world'),
            'zero' => base64_encode('0'),
        ];
        $globals = new Globals();

        self::assertSame('hello world', $globals->base64()->GET('encoded'));
        self::assertSame('0', $globals->base64()->GET('zero'));
    }

    public function testCallbackFilterReturnsTheCallbacksNativeType(): void
    {
        $_GET['value'] = 'four';

        self::assertSame(
            4,
            (new Globals())->callback(strlen(...))->GET('value'),
        );
    }

    public function testCallbackFilterAppliesToArrayValues(): void
    {
        $_GET['values'] = ['first', 'second'];

        self::assertSame(
            ['FIRST', 'SECOND'],
            (new Globals())->array()->callback(strtoupper(...))->GET('values'),
        );
    }

    public function testFilterStateResetsWhenACallbackThrows(): void
    {
        $_GET = ['invalid' => 'value', 'next' => '42'];
        $globals = new Globals();

        try {
            $globals
                ->callback(static fn (): never => throw new \RuntimeException('Callback failed'))
                ->GET('invalid');
            self::fail('The callback should have thrown an exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Callback failed', $exception->getMessage());
        }

        self::assertSame(42, $globals->GET('next'));
    }

    public function testNoFilterPreservesTheOriginalString(): void
    {
        $_GET['value'] = '0042';

        self::assertSame('0042', (new Globals())->noFilter()->GET('value'));
    }
}
