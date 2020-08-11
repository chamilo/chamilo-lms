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

use PHPExiftool\Driver\Value\Mono;
use PHPExiftool\Driver\Value\ValueInterface;

class MonoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Mono
     */
    protected $object;

    /**
     * @covers PHPExiftool\Driver\Value\Mono::__construct
     */
    protected function setUp()
    {
        $this->object = new Mono('Hello !');
    }

    /**
     * @covers PHPExiftool\Driver\Value\Mono::getType
     */
    public function testGetType()
    {
        $this->assertEquals(ValueInterface::TYPE_MONO, $this->object->getType());
    }

    /**
     * @covers PHPExiftool\Driver\Value\Mono::asString
     */
    public function testAsString()
    {
        $this->assertEquals('Hello !', $this->object->asString());
    }

    /**
     * @covers PHPExiftool\Driver\Value\Mono::set
     */
    public function testSetValue()
    {
        $this->object->set('World !');
        $this->assertEquals('World !', $this->object->asString());
    }
}
