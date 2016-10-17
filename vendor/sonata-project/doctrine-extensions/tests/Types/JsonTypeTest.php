<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Doctrine\Types\Tests;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class JsonTypeTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (Type::hasType('json')) {
            Type::overrideType('json', 'Sonata\Doctrine\Types\JsonType');
        } else {
            Type::addType('json', 'Sonata\Doctrine\Types\JsonType');
        }

    }

    public function testConvertToDatabaseValue()
    {
        $plateform = new MockPlatform();

        $this->assertEquals(
            '{"foo":"bar"}',
            Type::getType('json')->convertToDatabaseValue(array('foo' => 'bar'), $plateform)
        );
    }

    public function testConvertToPHPValue()
    {
        $plateform = new MockPlatform();

        $this->assertEquals(
            array('foo' => 'bar'),
            Type::getType('json')->convertToPHPValue('{"foo":"bar"}', $plateform)
        );
    }
}

class MockPlatform extends \Doctrine\DBAL\Platforms\AbstractPlatform
{
    /**
     * Gets the SQL Snippet used to declare a BLOB column type.
     */
    public function getBlobTypeDeclarationSQL(array $field)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getBooleanTypeDeclarationSQL(array $columnDef) {}
    public function getIntegerTypeDeclarationSQL(array $columnDef) {}
    public function getBigIntTypeDeclarationSQL(array $columnDef) {}
    public function getSmallIntTypeDeclarationSQL(array $columnDef) {}
    public function _getCommonIntegerTypeDeclarationSQL(array $columnDef) {}

    public function getVarcharTypeDeclarationSQL(array $field)
    {
        return "DUMMYVARCHAR()";
    }

    /** @override */
    public function getClobTypeDeclarationSQL(array $field)
    {
        return 'DUMMYCLOB';
    }

    public function getVarcharDefaultLength()
    {
        return 255;
    }

    public function getName()
    {
        return 'mock';
    }
    protected function initializeDoctrineTypeMappings() {
    }
    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed)
    {

    }
}