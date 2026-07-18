# PHP Password Cracker

[![Tests](https://github.com/Jord-JD/php-password-cracker/actions/workflows/tests.yml/badge.svg)](https://github.com/Jord-JD/php-password-cracker/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/jord-jd/php-password-cracker.svg)](https://packagist.org/packages/jord-jd/php-password-cracker)

PHP package to crack passwords

Only use this package to audit password hashes and dictionaries you are
authorized to test.

## Installation

```bash
composer require jord-jd/php-password-cracker
```

## Usage

```php
use JordJD\PasswordCracker\Crackers\DictionaryCracker;

$hash = password_hash('secret', PASSWORD_BCRYPT);

$password = (new DictionaryCracker())->crack($hash);

/*
$password = (new DictionaryCracker())->crack($hash, function($passwordBeingChecked) {
    echo 'Checking password '.$passwordBeingChecked.'...'.PHP_EOL;
});
*/

echo 'Password found: '.$password.PHP_EOL;

```

`crack()` returns the matching plain-text candidate, or `null` when no candidate
matches or the hash format is unsupported. You can check a hash without running
the dictionary using `DictionaryCracker::supportsHash($hash)`.

## Custom dictionaries and concurrency

Pass an array, `Traversable`, or readable file path to use your own dictionary.
The optional second argument controls the maximum number of concurrent checks.

```php
$cracker = new DictionaryCracker(
    ['/known/password', 'another candidate', 'secret'],
    8
);

$password = $cracker->crack($hash);
```

```php
$cracker = new DictionaryCracker('/secure/path/passwords.txt', 4);
```

Dictionary entries are used exactly as supplied, except that line endings are
removed when a file is loaded. Unreadable files, non-string entries, and invalid
concurrency produce clear exceptions.

## Parallel support

When the `pcntl` and `posix` extensions are available, checks run in parallel
through `spatie/async`. Other platforms use its synchronous fallback. The
progress callback runs as candidates finish, so callback order is not guaranteed
in parallel mode and processing may stop early after a match.

## Compatibility

PHP 7.1 through the current PHP 8.x releases are supported. Composer selects the
newest compatible `spatie/async` release for the PHP runtime in use.
