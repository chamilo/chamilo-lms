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

use PHPExiftool\Driver\Value\Multi;
use PHPExiftool\Driver\Value\ValueInterface;

class MultiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Multi
     */
    protected $object;

    /**
     * @covers PHPExiftool\Driver\Value\Multi::__construct
     */
    protected function setUp()
    {
        $this->object = new Multi(array('hello', 'world !'));
    }

    /**
     * @covers PHPExiftool\Driver\Value\Multi::getType
     */
    public function testGetType()
    {
        $this->assertEquals(ValueInterface::TYPE_MULTI, $this->object->getType());
    }

    /**
     * @covers PHPExiftool\Driver\Value\Multi::asArray
     */
    public function testAsArray()
    {
        $this->assertEquals(array('hello', 'world !'), $this->object->asArray());
    }

    /**
     * @covers PHPExiftool\Driver\Value\Multi::addValue
     */
    public function testAddValue()
    {
        $this->object->addValue('tim');
        $this->assertEquals(array('hello', 'world !', 'tim'), $this->object->asArray());
    }

    /**
     * @covers PHPExiftool\Driver\Value\Multi::reset
     */
    public function testReset()
    {
        $this->object->reset();
        $this->assertEquals(array(), $this->object->asArray());
    }
}
