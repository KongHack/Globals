Globals
========================

GLOBALS allows you to easily manage your global variables in PHP by filtering the content automatically or specifically, with batch processing possible in case of array.

This implementation is a fork of the original Coercive/Globals available at https://github.com/Coercive/Globals

I've been using an override in composer.json for a while to use the fork over the original, but seeing as how they've diverged, 
felt it was a better idea to try and separate namespaces and packages.

Note: This package contains a class used to ease transition pains that will conflict with the original.


Get
---
```
composer require gcworld/globals
```

Usage
-----

```php
use GCWorld\Globals;

# LOAD
$oGlobals = new Globals;

# DEFAULT
$oGlobals->filter(true);
# You can turn off by setting 'false'

# SET VAR
$oGlobals->GET('name', 'value');

# GET VAR
$var = $oGlobals->GET('name');

# FOR EXAMPLE :
$_GET['array'] = ['email@email.email', 'not an email'];
$_GET['notInt'] = '01234';
$_GET['int'] = '14244';
$_GET['_int'] = '-14244';
$_GET['float'] = '142.24';
$_GET['_float'] = '+142.24';
$_GET['bool'] = 'false';
$_GET['_bool'] = true;
$_GET['quote'] = '&quot;';

# FILTER ALL
$var = $oGlobals->GET()->filterAll();

/**
      ["array"]
            [0]=> string(17) "email@email.email"
            [1]=> string(12) "not an email"
      ["notInt"]=> string(5) "01234"
      ["int"]=> int(14244)
      ["_int"]=> int(-14244)
      ["float"]=> float(142.24)
      ["_float"]=> float(142.24)
      ["bool"]=> bool(false)
      ["quote"]=> string(10) "&quot;"
*/


# FILTER ONE (including array of elements)
$var = $oGlobals->email()->GET('array');

/**
    ["array"]
        [0]=> string(17) "email@email.email"
        [1]=> bool(false)
*/
```
**Supported Globals**
```
 COOKIE
 ENV
 FILES
 GET
 POST
 REQUEST
 SERVER
 SESSION
```

**Available Filters**
```
->octal()->...
->int()->...
->float()->...
->bool()->...
->ip()->...
->ipv4()->...
->ipv6()->...
->callback(Callable $callback)->...
->json(bool $asArray)->...
->array()->...
->email()->...
->url()->...
->date()->...
->dateTime()->...
->mac()->...
->string()->...
->stringSpecial()->...
->stringFull()->...
->uuid()->...
->noFilter()->...
```

**Additional Features**
```
# You can filter a variable (or a part of global for re-inject)
$Result = $oGlobals->autoFilterManualVar($YourVar);
```

**Notes on Filters**
 - The ``string()`` filter runs a trim(strip_tags()) and may not be what you need.  The ``stringSpecial()`` is the filter equivalent function
 - The ``callback`` filter requires a callable. Previously, this just set the filter type and didn't function properly
 - The ``date()`` and ``dateTime`` filters check against ``strtotime($input) !== false`` before translating to a Y-m-d( H:i:s) format 
