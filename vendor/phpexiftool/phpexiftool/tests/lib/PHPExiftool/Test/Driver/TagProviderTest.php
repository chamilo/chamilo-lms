<?php

namespace PHPExiftool\Test\Driver;

use PHPExiftool\Driver\TagProvider;

class TagProviderTest extends \PHPUnit_Framework_TestCase
{
    private $object;
    protected function setUp()
    {
        $this->object = new TagProvider;
    }

    public function testGetAll()
    {
        $this->assertInternalType('array', $this->object->getAll());
    }

    public function testGetLookupTable()
    {
        $this->assertInternalType('array', $this->object->getLookupTable());
    }
}
