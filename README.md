# GCWorld Globals

GCWorld Globals provides a typed, filterable access layer for PHP superglobals.
It supports automatic scalar coercion, explicit validation and sanitization,
recursive array filtering, JSON decoding, UUID conversion, and configurable
default values.

### Version
4.1.0

## Requirements

- PHP 8.4 or newer
- JSON extension
- mbstring extension

## Installation

```console
composer require gcworld/globals
```

## Basic Usage

```php
<?php

use GCWorld\Globals\Globals;

$globals = new Globals();

$_GET['page'] = '42';
$_GET['email'] = 'person@example.com';

$page = $globals->int()->GET('page');
$email = $globals->email()->GET('email');
```

Each explicit filter applies to the next value read. Filter state resets after
the read, including when a callback throws. When multiple filters are selected
before a read, the most recently selected filter wins.

## Supported Superglobals

The following methods provide access to their corresponding PHP superglobals:

| Method | Superglobal |
| --- | --- |
| `COOKIE()` | `$_COOKIE` |
| `ENV()` | `$_ENV` |
| `FILES()` | `$_FILES` |
| `GET()` | `$_GET` |
| `POST()` | `$_POST` |
| `REQUEST()` | `$_REQUEST` |
| `SERVER()` | `$_SERVER` |
| `SESSION()` | `$_SESSION` |

The target superglobal must exist as an array. For example, initialize the PHP
session before using `SESSION()` in environments where `$_SESSION` has not yet
been created.

### Reading and writing values

```php
$name = $globals->string()->POST('name');

$globals->SESSION('user_id', 42);
$globals->GET('optional', null);
```

Set operations return `true` when the selected global is available. Reads of a
missing value return `null` unless defaults are enabled.

### Inspecting and filtering a complete global

```php
$keys = $globals->getKeys('GET');

$filtered = $globals->GET()->filterAll();
$unfiltered = $globals->GET()->filterNone();
```

`filterAll()` recursively applies automatic filtering. `filterNone()` returns
the selected global without changing its values.

## Automatic Filtering

Automatic filtering is enabled by default for reads that do not select an
explicit filter. It recognizes:

- `true`, `false`, `y`, and `n` as booleans
- Valid integers without ambiguous leading zeroes
- Decimal numbers as floats
- Arrays, recursively
- Remaining scalar values as strings sanitized with
  `FILTER_SANITIZE_SPECIAL_CHARS`

```php
$_GET = [
    'enabled' => 'true',
    'count' => '12',
    'code' => '0012',
];

$enabled = $globals->GET('enabled'); // true
$count = $globals->GET('count');     // 12
$code = $globals->GET('code');       // "0012"
```

Automatic filtering can be disabled persistently for individual reads:

```php
$globals->filter(false);
$value = $globals->GET('value');
```

Use `noFilter()` when only the next read should bypass validation,
sanitization, and type coercion:

```php
$value = $globals->noFilter()->GET('value');
```

The configured UTF-8 conversion still applies to string values returned by
`noFilter()`.

Values outside a superglobal can use the same automatic behavior:

```php
$filtered = $globals->autoFilterManualVar($value);
```

## Explicit Filters

| Filter | Behavior |
| --- | --- |
| `octal()` | Converts an octal string to an integer |
| `int()` | Validates and returns an integer |
| `float()` | Validates and returns a float |
| `bool()` | Validates and returns a boolean |
| `ip()` | Validates an IPv4 or IPv6 address |
| `ipv4()` | Validates an IPv4 address |
| `ipv6()` | Validates an IPv6 address |
| `email()` | Validates an email address |
| `url()` | Validates a URL |
| `mac()` | Validates a MAC address |
| `string()` | Trims the value and removes HTML tags |
| `stringStrict()` | Keeps letters, numbers, spaces, apostrophes, and hyphens |
| `stringSpecial()` | Applies `FILTER_SANITIZE_SPECIAL_CHARS` |
| `stringFull()` | Applies `FILTER_SANITIZE_FULL_SPECIAL_CHARS` |
| `date()` | Normalizes a date to `Y-m-d` |
| `dateTime()` | Normalizes a date and time to `Y-m-d H:i:s` |
| `base64()` | Strictly decodes Base64 input |
| `json(bool $asArray)` | Decodes a JSON container |
| `uuid(bool $asBytes = false)` | Validates a UUID and returns its canonical string or bytes |
| `callback(callable $callback)` | Applies a callback and preserves its return type |
| `noFilter()` | Returns the next value without filtering or coercion |

Invalid validation input is converted to the selected filter's return type. For
example, an invalid integer becomes `0`, while an invalid email address becomes
an empty string.

### Array filtering

Use `array()` before an explicit filter to apply that filter to each array
value. The optional level determines the permitted nesting depth.

```php
$_GET['emails'] = [
    'person@example.com',
    'not-an-email',
];

$emails = $globals->array()->email()->GET('emails');

// ['person@example.com', false]
```

Nested input can be handled by increasing the level:

```php
$values = $globals->array(2)->int()->POST('values');
```

Selecting `array()` for scalar input returns an empty array.

### JSON

JSON filters accept containers only. Scalar JSON values such as `true`, `42`,
and `null` are rejected to the appropriate empty container.

```php
$array = $globals->json(true)->POST('payload');
$object = $globals->json(false)->POST('payload');
```

- `json(true)` returns an array or `[]`.
- `json(false)` returns a `stdClass` instance or an empty `stdClass`.

### UUIDs

```php
$uuid = $globals->uuid()->GET('id');
$bytes = $globals->uuid(true)->GET('id');
```

Invalid UUID strings return `''` in string mode and `null` in byte mode.

### Callbacks

```php
$length = $globals
    ->callback(static fn (string $value): int => strlen($value))
    ->POST('name');
```

Callbacks may return any type. They can also be combined with `array()` to
process every value in an input array.

## Defaults and UTF-8 Handling

Enable filter-specific defaults for missing values:

```php
$globals->defaults(true);

$page = $globals->int()->GET('missing');       // 0
$emails = $globals->array()->GET('missing');   // []
$date = $globals->date()->GET('missing');      // "0000-00-00"
```

UTF-8 conversion is enabled by default for filtered strings. It can be disabled
when handling binary or otherwise encoding-sensitive values:

```php
$globals->utf8(false);
```

## Security

Filtering and sanitization do not make input universally safe. Continue to use
prepared statements for database queries, context-appropriate escaping for
HTML and JavaScript output, and application-level validation and authorization.

## Development

Run the complete local quality suite with:

```console
./dc up -d
./dc exec php composer check
```

The suite includes PHP syntax checks and PHPUnit tests. GitHub Actions runs it
against every supported PHP version.

## Releases

Releases use bare semantic-version tags such as `4.0.6`. The local release
tooling updates `VERSION` and the value below `### Version`, and prevents
tagging until a matching release section exists in `CHANGELOG.md`.

Pushing the tag runs the complete PHP quality matrix. After it passes, GitHub
Actions creates the GitHub Release from that version's changelog section.
Packagist continues to receive the tag through the repository's existing
GitHub integration. Release tags must not be moved or reused.

## Acknowledgements

This project was originally derived from Coercive/Globals and has since evolved
as an independently maintained implementation.

## License

GCWorld Globals is open-source software licensed under the terms in
[LICENSE.txt](LICENSE.txt).
