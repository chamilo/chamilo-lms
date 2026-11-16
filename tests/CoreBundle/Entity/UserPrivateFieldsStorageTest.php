<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping\Column;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class UserPrivateFieldsStorageTest extends TestCase
{
    /**
     * @dataProvider fields
     */
    public function testUnencryptedValuesAreReadableAndMappedToText(string $field): void
    {
        $user = new User();
        $setter = 'set'.ucfirst($field);
        $getter = 'get'.ucfirst($field);
        $user->{$setter}('Private QA value');

        self::assertSame('Private QA value', $user->{$getter}());
        $storageMethod = $field.'StorageValue';
        self::assertSame('Private QA value', $user->{$storageMethod}());

        $property = new ReflectionProperty(User::class, $field);
        $attribute = $property->getAttributes(Column::class);
        self::assertCount(1, $attribute);
        $mapping = $attribute[0]->newInstance();
        self::assertSame($field, $mapping->name);
        self::assertSame('text', $mapping->type);
    }

    /**
     * @dataProvider fields
     */
    public function testCiphertextCannotLeakWithoutPostLoad(string $field): void
    {
        $user = new User();
        $storage = 'set'.ucfirst($field).'StorageValue';
        $getter = 'get'.ucfirst($field);
        $user->{$storage}('chamilo-encrypted:v1:example');

        $this->expectException(LogicException::class);
        $user->{$getter}();
    }

    /**
     * @dataProvider fields
     */
    public function testPlaintextViewDoesNotChangeStoredCiphertext(string $field): void
    {
        $user = new User();
        $storage = 'set'.ucfirst($field).'StorageValue';
        $hydrate = 'hydrate'.ucfirst($field);
        $getter = 'get'.ucfirst($field);
        $value = $field.'StorageValue';
        $user->{$storage}('chamilo-encrypted:v1:example');
        $user->{$hydrate}('Decrypted value');

        self::assertSame('Decrypted value', $user->{$getter}());
        self::assertSame('chamilo-encrypted:v1:example', $user->{$value}());
    }

    /**
     * @dataProvider fields
     */
    public function testSetterRejectsReservedPrefix(string $field): void
    {
        $user = new User();
        $setter = 'set'.ucfirst($field);

        $this->expectException(InvalidArgumentException::class);
        $user->{$setter}('chamilo-encrypted:v1:malicious');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function fields(): iterable
    {
        yield 'phone' => ['phone'];

        yield 'address' => ['address'];
    }
}
