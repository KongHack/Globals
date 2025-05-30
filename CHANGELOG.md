
# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased](https://github.com/KongHack/Globals)



## [4.0.5](https://github.com/KongHack/Globals/releases/tag/4.0.5)
- @GameCharmer Base64 filter type



## [4.0.4](https://github.com/KongHack/Globals/releases/tag/4.0.4)
- @GameCharmer Composer update



## [4.0.3](https://github.com/KongHack/Globals/releases/tag/4.0.3)
- @GameCharmer Additional array enforcement



## [4.0.2](https://github.com/KongHack/Globals/releases/tag/4.0.2)
- @GameCharmer Disable cast call on array



## [4.0.1](https://github.com/KongHack/Globals/releases/tag/4.0.1)
- @GameCharmer Patch issue with closure2



## [4.0.0](https://github.com/KongHack/Globals/releases/tag/4.0.0)
- @GameCharmer Internal Overhaul, new Enums, better array handling



## [3.3.0](https://github.com/KongHack/Globals/releases/tag/3.3.0)
- @GameCharmer New Globals Interface



## [3.2.0](https://github.com/KongHack/Globals/releases/tag/3.2.0)
- @GameCharmer Update for PHP 8.1
- @GameCharmer neitanod/forceutf8 removed


## [3.1.1](https://github.com/KongHack/Globals/releases/tag/3.1.1)
- @GameCharmer Add WTF method for global loading



## [3.1.0](https://github.com/KongHack/Globals/releases/tag/3.1.0)
- @GameCharmer Update UUID Library



## [3.0.3](https://github.com/KongHack/Globals/releases/tag/3.0.3)
- @GameCharmer add is_scalar check when filtering by standard `filter_var` filters to avoid array injection



## [3.0.2](https://github.com/KongHack/Globals/releases/tag/3.0.2)
- @GameCharmer Fix issue where we were UTF-8 "fixing" Binary16 UUIDs



## [3.0.1](https://github.com/KongHack/Globals/releases/tag/3.0.1)
- @GameCharmer Swap back to non-abstract class, switch private properties/methods to protected



## [3.0.0](https://github.com/KongHack/Globals/releases/tag/3.0.0)
- @GameCharmer Remove deprecated class
- @GameCharmer Change Globals to Abstract class to force people to extend it
- @GameCharmer Disable UTF8 Fix when getting UUID via binary
- @GameCharmer Switch UUID Exception handling



## [2.0.11](https://github.com/KongHack/Globals/releases/tag/2.0.11)
- @GameCharmer compensate for all 0 UUID



## [2.0.10](https://github.com/KongHack/Globals/releases/tag/2.0.10)
- @GameCharmer fix comparator in UUID binary response



## [2.0.9](https://github.com/KongHack/Globals/releases/tag/2.0.9)
- @GameCharmer Fix issue with constant



## [2.0.8](https://github.com/KongHack/Globals/releases/tag/2.0.8)
- @GameCharmer UUID String and Byte options



## [2.0.7](https://github.com/KongHack/Globals/releases/tag/2.0.7)
- @GameCharmer Added uuid filter

- symfony/polyfill-ctype installed in version v1.11.0
  Release notes: https://github.com/symfony/polyfill-ctype/releases/tag/v1.11.0

- paragonie/random_compat installed in version v9.99.99
  Release notes: https://github.com/paragonie/random_compat/releases/tag/v9.99.99

- ramsey/uuid installed in version 3.8.0
  Release notes: https://github.com/ramsey/uuid/releases/tag/3.8.0



## [2.0.6](https://github.com/KongHack/Globals/releases/tag/2.0.6)
- @GameCharmer Add fixUTF8 to data by default
- neitanod/forceutf8 installed in version v2.0.2
  Release notes: https://github.com/neitanod/forceutf8/releases/tag/v2.0.2



## [2.0.5](https://github.com/KongHack/Globals/releases/tag/2.0.5)
- @GameCharmer fix defaults function



## [2.0.4](https://github.com/KongHack/Globals/releases/tag/2.0.4)
- @GameCharmer prevent improper usage of the defaults function



## [2.0.3](https://github.com/KongHack/Globals/releases/tag/2.0.3)
- @GameCharmer add back super hacky filterAll / filterNone stuff



## [2.0.2](https://github.com/KongHack/Globals/releases/tag/2.0.2)
- @GameCharmer Added Defaults System
- Notes on IPv4 and IPv6 functions being complete garbage
 


## [2.0.1](https://github.com/KongHack/Globals/releases/tag/2.0.1)
- @GameCharmer Added json filter
 


## [2.0](https://github.com/KongHack/Globals/releases/tag/2.0)
- @GameCharmer Added date and datetime filters
- @GameCharmer replaced string filter with a trim(strip_tags()) function, kept original functionality as stringSpecial()
- @GameCharmer refactored forced array creation
- @GameCharmer update variable processing to be recursive
- @GameCharmer renamed namespace due to divergence in code
- @GameCharmer fixed issue with FILE call (should have been labeled FILES)
- @GameCharmer properly reset on non-existent variable



## [1.1](https://github.com/KongHack/Globals/releases/tag/1.1)
- No Change Log Available 


