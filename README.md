Coercive Globals Utility
========================

GLOBALS allows you to easily manage your global variables in PHP by filtering the content automatically or specifically, with batch processing possible in case of array.

Get
---
```
composer require coercive/globals
```

Usage
-----

```php
use Coercive\Utility\Globals;

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

**FILTER**
```
->octal()->...
->int()->...
->float()->...
->bool()->...
->ip()->...
->ipv4()->...
->ipv6()->...
->callback()->...
->rArray()->...
->email()->...
->url()->...
->mac()->...
->string()->...
->stringFull()->...
->noFilter()->...
```
