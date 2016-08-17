<?php

namespace Ddeboer\DataImport\Tests\Writer;

use Ddeboer\DataImport\Writer\ExcelWriter;

class ExcelWriterTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped();
        }
    }

    public function testWriteItemAppendWithSheetTitle()
    {
        $file = tempnam(sys_get_temp_dir(), null);

        $writer = new ExcelWriter(new \SplFileObject($file, 'w'), 'Sheet 1');

        $writer->prepare();
        $writer->writeItem(array('first', 'last'));

        $writer->writeItem(array(
            'first' => 'James',
            'last'  => 'Bond'
        ));

        $writer->writeItem(array(
            'first' => '',
            'last'  => 'Dr. No'
        ));

        $writer->finish();

        // Open file with append mode ('a') to add a sheet
        $writer = new ExcelWriter(new \SplFileObject($file, 'a'), 'Sheet 2');

        $writer->prepare();

        $writer->writeItem(array('first', 'last'));

        $writer->writeItem(array(
            'first' => 'Miss',
            'last'  => 'Moneypenny'
        ));

        $writer->finish();

        $excel = \PHPExcel_IOFactory::load($file);

        $this->assertTrue($excel->sheetNameExists('Sheet 1'));
        $this->assertEquals(3, $excel->getSheetByName('Sheet 1')->getHighestRow());

        $this->assertTrue($excel->sheetNameExists('Sheet 2'));
        $this->assertEquals(2, $excel->getSheetByName('Sheet 2')->getHighestRow());
    }

    public function testWriteItemWithoutSheetTitle()
    {
        $outputFile = new \SplFileObject(tempnam(sys_get_temp_dir(), null));
        $writer = new ExcelWriter($outputFile);

        $writer->prepare();

        $writer->writeItem(array('first', 'last'));

        $writer->finish();
    }

    /**
     * Test that column names not prepended to first row if ExcelWriter's 4-th
     * parameter not given
     *
     * @author  Igor Mukhin <igor.mukhin@gmail.com>
     */
    public function testHeaderNotPrependedByDefault()
    {
        $file = tempnam(sys_get_temp_dir(), null);

        $writer = new ExcelWriter(new \SplFileObject($file, 'w'), null, 'Excel2007');
        $writer->prepare();
        $writer->writeItem(array(
            'col 1 name'=>'col 1 value',
            'col 2 name'=>'col 2 value',
            'col 3 name'=>'col 3 value'
        ));
        $writer->finish();

        $excel = \PHPExcel_IOFactory::load($file);
        $sheet = $excel->getActiveSheet()->toArray();

        # Values should be at first line
        $this->assertEquals(array('col 1 value', 'col 2 value', 'col 3 value'), $sheet[0]);
    }


    /**
     * Test that column names prepended at first row
     * and values have been written at second line
     * if ExcelWriter's 4-th parameter set to true
     *
     * @author  Igor Mukhin <igor.mukhin@gmail.com>
     */
    public function testHeaderPrependedWhenOptionSetToTrue()
    {
        $file = tempnam(sys_get_temp_dir(), null);

        $writer = new ExcelWriter(new \SplFileObject($file, 'w'), null, 'Excel2007', true);
        $writer->prepare();
        $writer->writeItem(array(
            'col 1 name'=>'col 1 value',
            'col 2 name'=>'col 2 value',
            'col 3 name'=>'col 3 value'
        ));
        $writer->finish();

        $excel = \PHPExcel_IOFactory::load($file);
        $sheet = $excel->getActiveSheet()->toArray();

        # Check column names at first line
        $this->assertEquals(array('col 1 name', 'col 2 name', 'col 3 name'), $sheet[0]);

        # Check values at second line
        $this->assertEquals(array('col 1 value', 'col 2 value', 'col 3 value'), $sheet[1]);
    }
}
