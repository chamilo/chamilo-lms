<?php

/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class UserCredentialNormalizationTest extends TestCase
{
    public function testSurroundingSpacesAndInvisibleCharactersAreRemoved()
    {
        $this->assertSame('secret', User::normalizeCredential("  secret\t\n"));
        $this->assertSame('secret', User::normalizeCredential("secret\u{00A0}"));
        $this->assertSame('secret', User::normalizeCredential("\u{FEFF}sec\u{200B}ret\u{2060}"));
        $this->assertSame('se cret', User::normalizeCredential(' se cret '));
    }

    public function testInvalidUtf8AndNonStringsAreLeftAlone()
    {
        $this->assertSame("bad\xff", User::normalizeCredential("bad\xff"));
        $this->assertNull(User::normalizeCredential(null));
        $this->assertSame(12, User::normalizeCredential(12));
    }

    public function testSettersStoreNormalizedValues()
    {
        $user = new User();
        $user->setUsername(" jdoe\u{00A0}");
        $user->setPlainPassword("pass \u{200B}");

        $this->assertSame('jdoe', $user->getUsername());
        $this->assertSame('pass', $user->getPlainPassword());
    }
}
