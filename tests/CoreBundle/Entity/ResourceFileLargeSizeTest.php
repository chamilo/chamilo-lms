<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * The DBAL BIGINT representation is a numeric string, but consumers of
 * ResourceFile::getSize() and Vich uploads rely on integer file sizes.
 */
final class ResourceFileLargeSizeTest extends TestCase
{
    public function testBigintMappingAndSizeLargerThanTwoGigabytes(): void
    {
        $property = new ReflectionProperty(ResourceFile::class, 'size');
        $mapping = $property->getAttributes(Column::class)[0]->newInstance();
        self::assertSame(Types::BIGINT, $mapping->type);

        $file = new ResourceFile();
        self::assertSame(0, $file->getSize());

        $file->setSize(3_758_096_384);
        self::assertSame('3758096384', $property->getValue($file));
        self::assertSame(3_758_096_384, $file->getSize());
    }

    public function testDbalHydrationAndExistingIntegerApiStayCompatible(): void
    {
        $file = new ResourceFile();
        $property = new ReflectionProperty(ResourceFile::class, 'size');
        $property->setValue($file, '4294967296');
        self::assertSame(4_294_967_296, $file->getSize());

        $file->setSize(2_147_483_647);
        self::assertSame('2147483647', $property->getValue($file));
        self::assertSame(2_147_483_647, $file->getSize());

        $file->setSize(null);
        self::assertNull($property->getValue($file));
        self::assertNull($file->getSize());
    }
}
