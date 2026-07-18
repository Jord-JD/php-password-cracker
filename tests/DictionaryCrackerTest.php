<?php

namespace JordJD\PasswordCracker\Tests;

use JordJD\PasswordCracker\Crackers\DictionaryCracker;
use PHPUnit\Framework\TestCase;

final class DictionaryCrackerTest extends TestCase
{
    public function testCracksPasswordFromCustomDictionaryAndReportsProgress()
    {
        $checked = [];
        $cracker = new DictionaryCracker(['incorrect', 'secret', 'another'], 2);
        $hash = password_hash('secret', PASSWORD_BCRYPT);

        $password = $cracker->crack($hash, function ($candidate) use (&$checked) {
            $checked[] = $candidate;
        });

        $this->assertSame('secret', $password);
        $this->assertContains('secret', $checked);
        $this->assertSame(3, $cracker->getPasswordCount());
        $this->assertSame(2, $cracker->getConcurrency());
    }

    public function testReturnsNullWhenDictionaryDoesNotContainPassword()
    {
        $cracker = new DictionaryCracker(['one', 'two'], 1);

        $this->assertNull($cracker->crack(password_hash('secret', PASSWORD_BCRYPT)));
    }

    public function testAcceptsTraversableDictionary()
    {
        $cracker = new DictionaryCracker(new \ArrayIterator(['secret']));

        $this->assertSame('secret', $cracker->crack(password_hash('secret', PASSWORD_BCRYPT)));
    }

    public function testLoadsDictionaryFile()
    {
        $path = tempnam(sys_get_temp_dir(), 'password-dictionary-');
        file_put_contents($path, "first\nsecret\n");

        try {
            $cracker = new DictionaryCracker($path, 1);

            $this->assertSame(2, $cracker->getPasswordCount());
            $this->assertSame('secret', $cracker->crack(password_hash('secret', PASSWORD_BCRYPT)));
        } finally {
            unlink($path);
        }
    }

    public function testBundledDictionaryIsAvailable()
    {
        $this->assertGreaterThan(10000, (new DictionaryCracker())->getPasswordCount());
    }

    public function testRejectsUnreadableDictionaryFile()
    {
        $this->expectException(\RuntimeException::class);
        new DictionaryCracker(__DIR__.'/missing-password-list.txt');
    }

    public function testRejectsNonStringDictionaryEntry()
    {
        $this->expectException(\InvalidArgumentException::class);
        new DictionaryCracker(['valid', 123]);
    }

    public function testRejectsInvalidConcurrency()
    {
        $this->expectException(\InvalidArgumentException::class);
        new DictionaryCracker([], 0);
    }

    public function testUnsupportedHashRemainsANonMatch()
    {
        $this->assertFalse(DictionaryCracker::supportsHash('not-a-password-hash'));
        $this->assertNull((new DictionaryCracker(['secret']))->crack('not-a-password-hash'));
    }
}
