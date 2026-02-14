<?php

namespace JordJD\PasswordCracker\Crackers;

use Spatie\Async\Pool;

class DictionaryCracker
{
    private $passwords = [];

    public function __construct()
    {
        $this->passwords = file(__DIR__ . '/../../resources/password-list.txt', FILE_IGNORE_NEW_LINES);
    }

    public function getPasswordCount(): int
    {
        return count($this->passwords);
    }

    public function crack(string $hash, callable $onProgress = null): ?string
    {
        $return = null;

        $pool = Pool::create();
        $pool->concurrency(20);

        foreach($this->passwords as $password) {
            $pool->add(function () use ($password, $hash) {
                return password_verify($password, $hash);
            })->then(function($passwordFound) use (&$return, $password, $onProgress, $pool) {
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
}
