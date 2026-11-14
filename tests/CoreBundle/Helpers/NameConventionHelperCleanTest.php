<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\NameConventionHelper;
use PHPUnit\Framework\TestCase;

/**
 * #1783: with registration.firstname_required off, a user can have a last name only. The formatted name must not
 * keep the space or the comma meant to separate the missing first name.
 */
final class NameConventionHelperCleanTest extends TestCase
{
    /**
     * @dataProvider names
     */
    public function testCleanPersonName(string $raw, string $expected): void
    {
        $this->assertSame($expected, NameConventionHelper::cleanPersonName($raw));
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function names(): iterable
    {
        yield 'western, both names' => [' John Doe', 'John Doe'];

        yield 'library order, both names' => ['Doe, John', 'Doe, John'];

        yield 'western, no first name' => ['  Doe', 'Doe'];

        yield 'eastern, no first name' => [' Doe ', 'Doe'];

        yield 'library order, no first name' => [' Doe, ', 'Doe'];

        yield 'library order, no last name' => [' , John', 'John'];

        yield 'library order with title, no first name' => ['Dr. Doe,  ', 'Dr. Doe'];

        yield 'empty parts between commas' => ['Doe, , John', 'Doe, John'];

        yield 'space before a comma' => ['Doe , John', 'Doe, John'];

        yield 'nothing at all' => [' , ', ''];

        yield 'multibyte' => ["  Ångström,\u{00A0}", 'Ångström'];

        yield 'invalid UTF-8 is kept' => ["Doe, \xE9", "Doe, \xE9"];
    }

    public function testFullNameWithoutFirstNameHasNoStraySpace(): void
    {
        $this->assertSame('Doe', (new User())->setLastname('Doe')->getFullName());
        $this->assertSame('Doe', (new User())->setFirstname('')->setLastname('Doe')->getFullName());
        $this->assertSame('John Doe', (new User())->setFirstname('John')->setLastname('Doe')->getFullName());
    }
}
