<?php

namespace Ddeboer\DataImport\Tests\Reader;

use Ddeboer\DataImport\Reader\CsvReader;

class CsvReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testReadCsvFileWithColumnHeaders()
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/data_column_headers.csv');
        $csvReader = new CsvReader($file);
        $csvReader->setHeaderRowNumber(0);

        $this->assertEquals(
            array(
                'id', 'number', 'description'
            ),
            $csvReader->getFields()
        );

        foreach ($csvReader as $row) {
            $this->assertNotNull($row['id']);
            $this->assertNotNull($row['number']);
            $this->assertNotNull($row['description']);
        }

        $this->assertEquals(
            array(
                'id'        => 6,
                'number'    => '456',
                'description' => 'Another description'
            ),
            $csvReader->getRow(2)
        );
    }

    public function testReadCsvFileWithoutColumnHeaders()
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/data_no_column_headers.csv');
        $csvReader = new CsvReader($file);

        $this->assertEmpty($csvReader->getColumnHeaders());
    }

    public function testReadCsvFileWithManualColumnHeaders()
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/data_no_column_headers.csv');
        $csvReader = new CsvReader($file);
        $csvReader->setColumnHeaders(array('id', 'number', 'description'));

        foreach ($csvReader as $row) {
            $this->assertNotNull($row['id']);
            $this->assertNotNull($row['number']);
            $this->assertNotNull($row['description']);
        }
    }

    public function testReadCsvFileWithTrailingBlankLines()
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/data_blank_lines.csv');
        $csvReader = new CsvReader($file);
        $csvReader->setColumnHeaders(array('id', 'number', 'description'));

        foreach ($csvReader as $row) {
            $this->assertNotNull($row['id']);
            $this->assertNotNull($row['number']);
            $this->assertNotNull($row['description']);
        }
    }

    public function testCountWithoutHeaders()
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/data_no_column_headers.csv');
        $csvReader = new CsvReader($file);
        $this->assertEquals(3, $csvReader->count());
    }

    public function testCountWithHeaders()
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/data_column_headers.csv');
        $csvReader = new CsvReader($file);
        $csvReader->setHeaderRowNumber(0);
        $this->assertEquals(3, $csvReader->count(), 'Row count should not include header');
    }

    public function testCountWithFewerElementsThanColumnHeadersNotStrict()
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/data_fewer_elements_than_column_headers.csv');
        $csvReader = new CsvReader($file);
        $csvReader->setStrict(false);
        $csvReader->setHeaderRowNumber(0);

        $this->assertEquals(3, $csvReader->count());
    }

    public function testCountWithMoreElementsThanColumnHeadersNotStrict()
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/data_more_elements_than_column_headers.csv');
        $csvReader = new CsvReader($file);
        $csvReader->setStrict(false);
        $csvReader->setHeaderRowNumber(0);

        $this->assertEquals(3, $csvReader->count());
        $this->assertFalse($csvReader->hasErrors());
        $this->assertEquals(array(6, 456, 'Another description'), array_values($csvReader->getRow(2)));
    }

    public function testCountDoesNotMoveFilePointer()
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/data_column_headers.csv');
        $csvReader = new CsvReader($file);
        $csvReader->setHeaderRowNumber(0);

        $key_before_count = $csvReader->key();
        $csvReader->count();
        $key_after_count = $csvReader->key();

        $this->assertEquals($key_after_count, $key_before_count);
    }

    public function testVaryingElementCountWithColumnHeadersNotStrict()
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/data_column_headers_varying_element_count.csv');
        $csvReader = new CsvReader($file);
        $csvReader->setStrict(false);
        $csvReader->setHeaderRowNumber(0);

        $this->assertEquals(4, $csvReader->count());
        $this->assertFalse($csvReader->hasErrors());
    }

    public function testVaryingElementCountWithoutColumnHeadersNotStrict()
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/data_no_column_headers_varying_element_count.csv');
        $csvReader = new CsvReader($file);
        $csvReader->setStrict(false);
        $csvReader->setColumnHeaders(array('id', 'number', 'description'));

        $this->assertEquals(5, $csvReader->count());
        $this->assertFalse($csvReader->hasErrors());
    }

    public function testInvalidCsv()
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/data_column_headers_varying_element_count.csv');
        $reader = new CsvReader($file);
        $reader->setHeaderRowNumber(0);

        $this->assertTrue($reader->hasErrors());

        $this->assertCount(2, $reader->getErrors());

        $errors = $reader->getErrors();
        $this->assertEquals(2, key($errors));
        $this->assertEquals(array('123', 'test'), current($errors));

        next($errors);
        $this->assertEquals(3, key($errors));
        $this->assertEquals(array('7', '7890', 'Some more info', 'too many columns'), current($errors));
    }

    public function testLastRowInvalidCsv()
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/data_no_column_headers_varying_element_count.csv');
        $reader = new CsvReader($file);
        $reader->setColumnHeaders(array('id', 'number', 'description'));

        $this->assertTrue($reader->hasErrors());
        $this->assertCount(3, $reader->getErrors());

        $errors = $reader->getErrors();
        $this->assertEquals(1, key($errors));
        $this->assertEquals(array('6', 'strictly invalid'), current($errors));

        next($errors);
        $this->assertEquals(3, key($errors));
        $this->assertEquals(array('3','230','Yet more info','Even more info'), current($errors));

        next($errors);
        $this->assertEquals(4, key($errors));
        $this->assertEquals(array('strictly invalid'), current($errors));
    }

    public function testLineBreaks()
    {
        $reader = $this->getReader('data_cr_breaks.csv');
        $this->assertCount(3, $reader);
    }

    /**
     * @expectedException \Ddeboer\DataImport\Exception\DuplicateHeadersException description
     */
    public function testDuplicateHeadersThrowsException()
    {
        $reader = $this->getReader('data_column_headers_duplicates.csv');
        $reader->setHeaderRowNumber(0);
    }

    public function testDuplicateHeadersIncrement()
    {
        $reader = $this->getReader('data_column_headers_duplicates.csv');
        $reader->setHeaderRowNumber(0, CsvReader::DUPLICATE_HEADERS_INCREMENT);
        $reader->rewind();
        $current = $reader->current();

        $this->assertEquals(
            array('id', 'description', 'description1', 'description2', 'details', 'details1', 'last'),
            $reader->getColumnHeaders()
        );

        $this->assertEquals(
            array(
                'id'           => '50',
                'description'  => 'First',
                'description1' => 'Second',
                'description2' => 'Third',
                'details'      => 'Details1',
                'details1'     => 'Details2',
                'last'         => 'Last one'
            ),
            $current
        );
    }

    public function testDuplicateHeadersMerge()
    {
        $reader = $this->getReader('data_column_headers_duplicates.csv');
        $reader->setHeaderRowNumber(0, CsvReader::DUPLICATE_HEADERS_MERGE);
        $reader->rewind();
        $current = $reader->current();

        $this->assertCount(4, $reader->getColumnHeaders());

        $expected = array(
            'id'          => '50',
            'description' => array('First', 'Second', 'Third'),
            'details'     => array('Details1', 'Details2'),
            'last'        => 'Last one'
        );
        $this->assertEquals($expected, $current);
    }

    public function testMaximumNesting()
    {
        if (!function_exists('xdebug_is_enabled')) {
            $this->markTestSkipped('xDebug is not installed');
        }

        $xdebug_start = !xdebug_is_enabled();
        if ($xdebug_start) {
            xdebug_enable();
        }

        ini_set('xdebug.max_nesting_level', 200);

        $file = new \SplTempFileObject();
        for($i = 0; $i < 500; $i++) {
            $file->fwrite("1,2,3\n");
        }

        $reader = new CsvReader($file);
        $reader->rewind();
        $reader->setStrict(true);
        $reader->setColumnHeaders(array('one','two'));

        $current = $reader->current();
        $this->assertEquals(null, $current);

        if ($xdebug_start) {
            xdebug_disable();
        }
    }

    protected function getReader($filename)
    {
        $file = new \SplFileObject(__DIR__.'/../Fixtures/'.$filename);

        return new CsvReader($file);
    }
}
