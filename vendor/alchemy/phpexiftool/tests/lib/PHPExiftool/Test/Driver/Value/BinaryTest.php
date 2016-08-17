<?php
/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Test\Driver\Value;

use PHPExiftool\Driver\Value\Binary;
use PHPExiftool\Driver\Value\ValueInterface;

class BinaryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Binary
     */
    protected $object;

    /**
     * @covers PHPExiftool\Driver\Value\Binary::__construct
     */
    protected function setUp()
    {
        $this->object = new Binary('Binary');
    }

    /**
     * @covers PHPExiftool\Driver\Value\Binary::getType
     */
    public function testGetType()
    {
        $this->assertEquals(ValueInterface::TYPE_BINARY, $this->object->getType());
    }

    /**
     * @covers PHPExiftool\Driver\Value\Binary::asString
     */
    public function testAsString()
    {
        $this->assertEquals('Binary', $this->object->asString());
    }

    /**
     * @covers PHPExiftool\Driver\Value\Binary::asBase64
     */
    public function testAsBase64()
    {
        $this->assertEquals(base64_encode('Binary'), $this->object->asBase64());
    }

    /**
     * @covers PHPExiftool\Driver\Value\Binary::set
     */
    public function testSetValue()
    {
        $this->object->set('Daisy');
        $this->assertEquals('Daisy', $this->object->asString());
    }

    /**
     * @covers PHPExiftool\Driver\Value\Binary::setBase64Value
     */
    public function testSetBase64Value()
    {
        $this->object->setBase64Value('UmlyaSBGaWZpIGV0IExvdWxvdQ==');
        $this->assertEquals('Riri Fifi et Loulou', $this->object->asString());
    }

    /**
     * @covers PHPExiftool\Driver\Value\Binary::setBase64Value
     * @covers \PHPExiftool\Exception\InvalidArgumentException
     * @expectedException \PHPExiftool\Exception\InvalidArgumentException
     */
    public function testSetWrongBase64Value()
    {
        $this->object->setBase64Value('Riri Fifi et Loulou !');
    }

    /**
     * @covers PHPExiftool\Driver\Value\Binary::loadFromBase64
     */
    public function testLoadFromBase64()
    {
        $object = Binary::loadFromBase64('VW5jbGUgU2Nyb29nZQ==');
        $this->assertEquals('Uncle Scrooge', $object->asString());
        $this->assertEquals('VW5jbGUgU2Nyb29nZQ==', $object->asBase64());
    }

    /**
     * @covers PHPExiftool\Driver\Value\Binary::loadFromBase64
     * @covers \PHPExiftool\Exception\InvalidArgumentException
     * @expectedException \PHPExiftool\Exception\InvalidArgumentException
     */
    public function testLoadFromWrongBase64()
    {
        $object = Binary::loadFromBase64('Uncle Scrooge !!!');
    }
}
