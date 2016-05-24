<?php

namespace Ddeboer\DataImport\Tests\Reader;

use Ddeboer\DataImport\Reader\ArrayReader;
use Ddeboer\DataImport\Reader\OneToManyReader;

/**
 * Class OneToManyReaderTest
 * @package Ddeboer\DataImport\Tests\Reader
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class OneToManyReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testReaderMergesOneToMany()
    {
        $leftData = array(
            array(
                'OrderId'   => 1,
                'Price'     => 30,
            ),
            array(
                'OrderId'   => 2,
                'Price'     => 15,
            ),
        );

        $rightData = array(
            array(
                'OrderId'   => 1,
                'Name'      => 'Super Cool Item 1',
            ),
            array(
                'OrderId'   => 1,
                'Name'      => 'Super Cool Item 2',
            ),
            array(
                'OrderId'   => 2,
                'Name'      => 'Super Cool Item 1',
            ),
        );

        $leftReader = new ArrayReader($leftData);
        $rightReader = new ArrayReader($rightData);
        $oneToManyReader = new OneToManyReader($leftReader, $rightReader, 'items', 'OrderId', 'OrderId');

        $expected = array(
            array(
                'OrderId'   => 1,
                'Price'     => 30,
                'items'     => array(
                    array(
                        'OrderId'   => 1,
                        'Name'      => 'Super Cool Item 1',
                    ),
                    array(
                        'OrderId'   => 1,
                        'Name'      => 'Super Cool Item 2',
                    ),
                ),
            ),
            array(
                'OrderId'   => 2,
                'Price'     => 15,
                'items'     => array(
                    array(
                        'OrderId'   => 2,
                        'Name'      => 'Super Cool Item 1',
                    ),
                )
            ),
        );

        $i = 0;
        foreach($oneToManyReader as $row) {
            $this->assertEquals($row, $expected[$i++]);
        }
    }

    public function testIfRightReaderIdFieldIsMissingLeftIsUsed()
    {
        $leftData = array(
            array(
                'OrderId'   => 1,
                'Price'     => 30,
            ),
            array(
                'OrderId'   => 2,
                'Price'     => 15,
            ),
        );

        $rightData = array(
            array(
                'OrderId'   => 1,
                'Name'      => 'Super Cool Item 1',
            ),
            array(
                'OrderId'   => 1,
                'Name'      => 'Super Cool Item 2',
            ),
            array(
                'OrderId'   => 2,
                'Name'      => 'Super Cool Item 1',
            ),
        );

        $leftReader = new ArrayReader($leftData);
        $rightReader = new ArrayReader($rightData);
        $oneToManyReader = new OneToManyReader($leftReader, $rightReader, 'items', 'OrderId');

        $expected = array(
            array(
                'OrderId'   => 1,
                'Price'     => 30,
                'items'     => array(
                    array(
                        'OrderId'   => 1,
                        'Name'      => 'Super Cool Item 1',
                    ),
                    array(
                        'OrderId'   => 1,
                        'Name'      => 'Super Cool Item 2',
                    ),
                ),
            ),
            array(
                'OrderId'   => 2,
                'Price'     => 15,
                'items'     => array(
                    array(
                        'OrderId'   => 2,
                        'Name'      => 'Super Cool Item 1',
                    ),
                )
            ),
        );

        $i = 0;
        foreach($oneToManyReader as $row) {
            $this->assertEquals($row, $expected[$i++]);
        }
    }

    public function testReaderThrowsExceptionIfNestKeyExistsInLeftReaderRow()
    {
        $leftData = array(
            array(
                'OrderId'   => 1,
                'Price'     => 30,
                'items'     => null,
            ),
        );

        $rightData = array(
            array(
                'OrderId'   => 1,
                'Name'      => 'Super Cool Item 1',
            ),
        );

        $leftReader  = new ArrayReader($leftData);
        $rightReader  = new ArrayReader($rightData);
        $oneToManyReader  = new OneToManyReader($leftReader, $rightReader, 'items', 'OrderId');

        $this->setExpectedException('Ddeboer\DataImport\Exception\ReaderException', 'Left Row: "0" Reader already contains a field named "items". Please choose a different nest key field');

        $oneToManyReader->rewind();
        $oneToManyReader->current();
    }

    public function testReaderThrowsExceptionIfIdFieldDoesNotExistInLeftRow()
    {
        $leftData = array(
            array(
                'Price'     => 30,
            ),
        );

        $rightData = array(
            array(
                'Name'      => 'Super Cool Item 1',
            ),
        );

        $leftReader = new ArrayReader($leftData);
        $rightReader = new ArrayReader($rightData);
        $oneToManyReader = new OneToManyReader($leftReader, $rightReader, 'items', 'OrderId');

        $this->setExpectedException('Ddeboer\DataImport\Exception\ReaderException', 'Row: "0" has no field named "OrderId"');

        $oneToManyReader->rewind();
        $oneToManyReader->current();
    }

    public function testReaderThrowsExceptionIfIdFieldDoesNotExistInRightRow()
    {
        $leftData = array(
            array(
                'OrderId'   => 1,
                'Price'     => 30,
            ),
        );

        $rightData = array(
            array(
                'Name'      => 'Super Cool Item 1',
            ),
        );

        $leftReader = new ArrayReader($leftData);
        $rightReader = new ArrayReader($rightData);
        $oneToManyReader = new OneToManyReader($leftReader, $rightReader, 'items', 'OrderId');

        $this->setExpectedException('Ddeboer\DataImport\Exception\ReaderException', 'Row: "0" has no field named "OrderId"');

        $oneToManyReader->rewind();
        $oneToManyReader->current();
    }

    public function testGetKeysReturnsLeftReaderColumnsMergedWithNestKey()
    {
        $leftReader = new ArrayReader(array(array('col1' => 'data1',  'col2' => 'data2'), array('data3', 'data4')));
        $rightReader = new ArrayReader(array());
        $oneToManyReader = new OneToManyReader($leftReader, $rightReader, 'items', 'OrderId');

        $this->assertSame(array('col1', 'col2', 'items'), $oneToManyReader->getFields());
    }

    public function testCountReturnsTheCountOfTheLeftReader()
    {
        $leftReader = new ArrayReader(array());
        $rightReader = new ArrayReader(array());

        $oneToManyReader = new OneToManyReader($leftReader, $rightReader, 'items', 'OrderId');
        $this->assertEquals(0, $oneToManyReader->count());

        $leftReader = new ArrayReader(array(array(), array(), array()));
        $oneToManyReader = new OneToManyReader($leftReader, $rightReader, 'items', 'OrderId');
        $this->assertEquals(3, $oneToManyReader->count());
    }

    /**
     * This is probably a limitation - but it's not need for current implementation
     * @dataProvider outOfOrderRowProvider
     */
    public function testOutOfOrderRowsInRightReaderAreNotNested($leftData, $rightData, $expected)
    {
        //var_dump($leftData);
        //var_dump($rightData);
        $leftReader = new ArrayReader($leftData);
        $rightReader = new ArrayReader($rightData);
        $oneToManyReader = new OneToManyReader($leftReader, $rightReader, 'items', 'OrderId');

        $i = 0;
        foreach($oneToManyReader as $row) {
            $this->assertEquals($row, $expected[$i++]);
        }
    }

    public function outOfOrderRowProvider()
    {
        return array(
            'skip-first-right-row' => array(
                'left' => array(
                    array(
                        'OrderId'   => 3,
                        'Price'     => 30,
                    ),
                    array(
                        'OrderId'   => 2,
                        'Price'     => 15,
                    ),
                ),
                'right' => array(
                    array(
                        'OrderId'   => 2,
                        'Name'      => 'Super Cool Item 1',
                    ),
                    array(
                        'OrderId'   => 1,
                        'Name'      => 'Super Cool Item 1',
                    ),
                    array(
                        'OrderId'   => 1,
                        'Name'      => 'Super Cool Item 2',
                    ),
                ),
                'expected' => array(
                    array(
                        'OrderId'   => 3,
                        'Price'     => 30,
                        'items'     => array()
                    ),
                    array(
                        'OrderId'   => 2,
                        'Price'     => 15,
                        'items'     => array(
                            array(
                                'OrderId'   => 2,
                                'Name'      => 'Super Cool Item 1',
                            ),
                        )
                    ),
                ),
            ),
            'skip-out-of-order' => array(
                'left' => array(
                    array(
                        'OrderId'   => 1,
                        'Price'     => 30,
                    ),
                    array(
                        'OrderId'   => 2,
                        'Price'     => 15,
                    ),
                ),
                'right' => array(
                    array(
                        'OrderId'   => 0,
                        'Name'      => 'Super Cool Item 1',
                    ),
                    array(
                        'OrderId'   => 2,
                        'Name'      => 'Super Cool Item 2',
                    ),
                    array(
                        'OrderId'   => 1,
                        'Name'      => 'Super Cool Item 3',
                    ),
                ),
                'expected' => array(
                    array(
                        'OrderId'   => 1,
                        'Price'     => 30,
                        'items'     => array()
                    ),
                    array(
                        'OrderId'   => 2,
                        'Price'     => 15,
                        'items'     => array(),
                    ),
                ),
            ),
        );
    }
}
