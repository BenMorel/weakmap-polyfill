# WeakMap polyfill for PHP 7.4

This polyfill aims to be 100% compatible with upcoming `WeakMap` in PHP 8.

[![Build Status](https://secure.travis-ci.org/BenMorel/weakmap-polyfill.svg?branch=master)](http://travis-ci.org/BenMorel/weakmap-polyfill)
[![Coverage Status](https://coveralls.io/repos/BenMorel/weakmap-polyfill/badge.svg?branch=master)](https://coveralls.io/r/BenMorel/weakmap-polyfill?branch=master)
[![Latest Stable Version](https://poser.pugx.org/benmorel/weakmap-polyfill/v/stable)](https://packagist.org/packages/benmorel/weakmap-polyfill)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

## Introduction

PHP 7.4 introduced `WeakReference`, but didn't include a `WeakMap` implementation. [This has been implemented](https://wiki.php.net/rfc/weak_maps) since, but will only be available in PHP 8.

The RFC author, Nikita Popov, highlights why a userland `WeakMap` is suboptimal:

> Weak maps require first-class language support and cannot be implemented using existing functionality provided by PHP.
> 
> At first sight, it may seem that an array mapping from spl_object_id() to arbitrary values could serve the purpose of a weak map. This is not the case for multiple reasons:
> 
> - spl_object_id() values are reused after the object is destroyed. Two different objects can have the same object ID – just not at the same time.
> - The object ID cannot be converted back into an object, so iteration over the map is not possible.
> - The value stored under the ID will not be released when the object is destroyed.
>
> Using the WeakReference class introduced in PHP 7.4, it is possible to avoid the first two issues (…). However, this does not solve the third problem:
> The data will not be released when the object is destroyed. It will only be released on the next access with an object that has the same reused ID,
> or if a garbage collection mechanism, which performs regular sweeps of the whole map, is implemented.
> 
> A native weak map implementation will instead remove the value from the weak map as soon as the object key is destroyed.

This is the trade-off this library offers: a 100% compatible implementation, but:

- slower
- whose values are not removed as soon as the object key is destroyed, but when you use the `WeakMap` again; note that
  this affects when object destructors are called as well

Here is how it works:

- calls to `count()` will always garbage collect dangling references immediately
- using array-like features: set, get, `isset()`, `unset()` will perform garbage collection every 100 operations

This offers a reasonable trade-off between performance and memory usage.

## Installation

This library is installable via [Composer](https://getcomposer.org/):

```bash
composer require benmorel/weakmap-polyfill
```

## Requirements

This library requires PHP 7.4 or later.

## Quickstart

```php
$weakMap = new WeakMap();

$a = new stdClass();
$b = new stdClass();

$weakMap[$a] = 123;

var_export(isset($weakMap[$a])); // true
var_export(isset($weakMap[$b])); // false

echo $weakMap[$a]; // 123
echo $weakMap[$b]; // Error

echo count($weakMap); // 1

// removing the last reference to the object will remove it from the WeakMap
unset($a);

echo count($weakMap); // 0
```
