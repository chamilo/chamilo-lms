<?php

namespace MediaAlchemyst\Tests\Specification;

use MediaAlchemyst\Specification\Flash;
use MediaAlchemyst\Specification\SpecificationInterface;

class FlashTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers MediaAlchemyst\Specification\Flash::getType
     */
    public function testGetType()
    {
        $specs = new Flash;
        $this->assertEquals(SpecificationInterface::TYPE_SWF, $specs->getType());
    }
}
