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
        $writer
            ->prepare()
            ->writeItem(array('first', 'last'))
            ->writeItem(
                array(
                    'first' => 'James',
                    'last'  => 'Bond'
                )
            )
            ->writeItem(
                array(
                    'first' => '',
                    'last'  => 'Dr. No'
                )
            )
            ->finish();

        // Open file with append mode ('a') to add a sheet
        $writer = new ExcelWriter(new \SplFileObject($file, 'a'), 'Sheet 2');
        $writer
            ->prepare()
            ->writeItem(array('first', 'last'))
            ->writeItem(
                array(
                    'first' => 'Miss',
                    'last'  => 'Moneypenny'
                )
            )
            ->finish();

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

        $writer
            ->prepare()
            ->writeItem(array('first', 'last'))
            ->finish();
    }

    public function testFluentInterface()
    {
        $outputFile = new \SplFileObject(tempnam(sys_get_temp_dir(), null));
        $writer = new ExcelWriter($outputFile);

        $this->assertSame($writer, $writer->prepare());
        $this->assertSame($writer, $writer->writeItem(array('first', 'last')));
        $this->assertSame($writer, $writer->finish());
    }
}
