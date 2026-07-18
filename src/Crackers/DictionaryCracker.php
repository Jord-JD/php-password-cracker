<?php

namespace JordJD\PasswordCracker\Crackers;

use InvalidArgumentException;
use RuntimeException;
use Spatie\Async\Pool;
use Traversable;

class DictionaryCracker
{
    private $passwords = [];
    private $concurrency;

    /**
     * @param array|Traversable|string|null $passwords Dictionary entries, a dictionary file path, or null for the bundled list.
     * @param int $concurrency Maximum number of password checks to run concurrently.
     */
    public function __construct($passwords = null, $concurrency = 20)
    {
        if (!is_int($concurrency) || $concurrency < 1) {
            throw new InvalidArgumentException('Concurrency must be a positive integer.');
        }

        if ($passwords === null) {
            $passwords = __DIR__.'/../../resources/password-list.txt';
        }

        if (is_string($passwords)) {
            $passwords = $this->readPasswordFile($passwords);
        } elseif (!is_array($passwords) && !$passwords instanceof Traversable) {
            throw new InvalidArgumentException('Passwords must be an array, Traversable dictionary, file path, or null.');
        }

        foreach ($passwords as $password) {
            if (!is_string($password)) {
                throw new InvalidArgumentException('Every dictionary password must be a string.');
            }

            $this->passwords[] = $password;
        }

        $this->concurrency = $concurrency;
    }

    public function getPasswordCount(): int
    {
        return count($this->passwords);
    }

    public function getConcurrency(): int
    {
        return $this->concurrency;
    }

    public static function supportsHash(string $hash): bool
    {
        $hashInformation = password_get_info($hash);

        return isset($hashInformation['algo']) && (bool) $hashInformation['algo'];
    }

    public function crack(string $hash, callable $onProgress = null): ?string
    {
        if (!self::supportsHash($hash) || !$this->passwords) {
            return null;
        }

        $return = null;

        $pool = Pool::create();
        $pool->concurrency($this->concurrency);

        foreach ($this->passwords as $password) {
            // The callback runs immediately when spatie/async uses its
            // synchronous fallback, so do not enqueue work after a match.
            if ($return !== null) {
                break;
            }

            $pool->add(function () use ($password, $hash) {
                return password_verify($password, $hash);
            })->then(function ($passwordFound) use (&$return, $password, $onProgress, $pool) {
                if ($passwordFound) {
                    $return = $password;
                    $pool->stop();
                }
                if ($onProgress) {
                    $onProgress($password);
                }
            });
        }

        $pool->wait();

        return $return;
    }

    private function readPasswordFile($path)
    {
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Password dictionary file is not readable: '.$path);
        }

        $passwords = @file($path, FILE_IGNORE_NEW_LINES);

        if ($passwords === false) {
            throw new RuntimeException('Password dictionary file could not be read: '.$path);
        }

        return $passwords;
    }
}
