<?php

namespace Exporter\Test\Source;

use Exporter\Source\PropelCollectionSourceIterator;


/**
 * Tests the PropelCollectionSourceIterator class
 *
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class PropelCollectionSourceIteratorTest extends \PHPUnit_Framework_TestCase
{
    protected $collection;

    public function setUp()
    {

        if (!class_exists('PropelCollection')) {
            $this->markTestIncomplete('Propel is not available');
        }

        $data = array(
            array('id' => 1, 'name' => 'john',   'mail' => 'john@foo.bar', 'created_at' => new \DateTime()),
            array('id' => 2, 'name' => 'john 2', 'mail' => 'john@foo.bar', 'created_at' => new \DateTime()),
            array('id' => 3, 'name' => 'john 3', 'mail' => 'john@foo.bar', 'created_at' => new \DateTime()),
        );

        $this->collection = new \PropelCollection();
        $this->collection->setData($data);
    }

    public function testIterator()
    {
        $data = $this->extract($this->collection, array('id' => '[id]', 'name' => '[name]'));

        $this->assertCount(3, $data);
    }

    public function testFieldsExtraction()
    {
        $data = $this->extract($this->collection, array('id' => '[id]', 'name' => '[name]'));

        $this->assertSame(array(
             array(
                'id'    => 1,
                'name'  => 'john'
            ),
            array(
                'id'    => 2,
                'name'  => 'john 2',
            ),
            array(
                'id'    => 3,
                'name'  => 'john 3',
            )
        ), $data);
    }

    public function testDateTimeTransformation()
    {
        $data = $this->extract($this->collection, array('id' => '[id]', 'created_at' => '[created_at]'));

        foreach ($data as $row) {
            $this->assertArrayHasKey('created_at', $row);
            $this->assertInternalType('string', $row['created_at']);
        }
    }

    protected function extract(\PropelCollection $collection, array $fields)
    {
        $iterator = new PropelCollectionSourceIterator($collection, $fields);
        return iterator_to_array($iterator);
    }
}
