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

final class UserBiographyStorageTest extends TestCase
{
    public function testChangingBiographyUpdatesTheMappedStorageField(): void
    {
        $user = new User();
        $user->setBiography('Private details');

        self::assertSame('Private details', $user->getBiography());
        self::assertSame('Private details', $user->biographyStorageValue());
    }

    public function testDoctrineKeepsOriginalBiographyFieldName(): void
    {
        $property = new ReflectionProperty(User::class, 'biography');
        $attributes = $property->getAttributes(Column::class);

        self::assertCount(1, $attributes);
        self::assertSame('biography', $attributes[0]->newInstance()->name);

        $plaintext = new ReflectionProperty(User::class, 'biographyPlaintext');
        self::assertSame([], $plaintext->getAttributes(Column::class));
    }

    public function testCiphertextIsNeverExposedWithoutHydration(): void
    {
        $user = new User();
        $user->setBiographyStorageValue('chamilo-encrypted:v1:example');

        $this->expectException(LogicException::class);
        $user->getBiography();
    }

    public function testReservedStoragePrefixCannotBeSavedAsUserContent(): void
    {
        $user = new User();

        $this->expectException(InvalidArgumentException::class);
        $user->setBiography('chamilo-encrypted:v1:malicious');
    }

    public function testHydratingPlaintextDoesNotMarkTheStoredCiphertextDirty(): void
    {
        $user = new User();
        $user->setBiographyStorageValue('chamilo-encrypted:v1:example');
        $user->hydrateBiography('Decrypted details');

        self::assertSame('Decrypted details', $user->getBiography());
        self::assertSame('chamilo-encrypted:v1:example', $user->biographyStorageValue());
    }
}
