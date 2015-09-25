<?php

namespace PHPExiftool\Test\Driver\Metadata;

use PHPExiftool\Driver\Metadata\MetadataBag;

class MetadataBagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MetadataBag
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new MetadataBag();
    }

    /**
     * @covers PHPExiftool\Driver\Metadata\MetadataBag::filterKeysByRegExp
     */
    public function testFilterKeysByRegExp()
    {
        $this->object->set('oneKey', 'oneValue');
        $this->object->set('oneSecondKey', 'anotherValue');
        $this->object->set('thirdKey', 'thirdValue');

        $this->assertEquals(2, count($this->object->filterKeysByRegExp('/one.*/')));
    }
}
