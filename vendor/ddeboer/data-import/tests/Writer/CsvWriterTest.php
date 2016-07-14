<?php

namespace Ddeboer\DataImport\Tests\Writer;

use Ddeboer\DataImport\Writer\CsvWriter;

class CsvWriterTest extends StreamWriterTest
{
    public function testWriteItem()
    {
        $writer = new CsvWriter(';', '"', $this->getStream());

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

        $this->assertContentsEquals(
            "first;last\nJames;Bond\n;\"Dr. No\"\n",
            $writer
        );

        $writer->finish();
    }

    public function testWriteUtf8Item()
    {
        $writer = new CsvWriter(';', '"', $this->getStream(), true);

        $writer->prepare();
        $writer->writeItem(array('Précédent', 'Suivant'));

        $this->assertContentsEquals(
            chr(0xEF) . chr(0xBB) . chr(0xBF) . "Précédent;Suivant\n",
            $writer
        );

        $writer->finish();
    }

    /**
     * Test that column names not prepended to first row
     * if CsvWriter's 5-th parameter not given
     *
     * @author  Igor Mukhin <igor.mukhin@gmail.com>
     */
    public function testHeaderNotPrependedByDefault()
    {
        $writer = new CsvWriter(';', '"', $this->getStream(), false);
        $writer->prepare();
        $writer->writeItem(array(
            'col 1 name'=>'col 1 value',
            'col 2 name'=>'col 2 value',
            'col 3 name'=>'col 3 value'
        ));

        # Values should be at first line
        $this->assertContentsEquals(
            "\"col 1 value\";\"col 2 value\";\"col 3 value\"\n",
            $writer
        );
        $writer->finish();
    }

    /**
     * Test that column names prepended at first row
     * and values have been written at second line
     * if CsvWriter's 5-th parameter set to true
     *
     * @author  Igor Mukhin <igor.mukhin@gmail.com>
     */
    public function testHeaderPrependedWhenOptionSetToTrue()
    {
        $writer = new CsvWriter(';', '"', $this->getStream(), false, true);
        $writer->prepare();
        $writer->writeItem(array(
            'col 1 name'=>'col 1 value',
            'col 2 name'=>'col 2 value',
            'col 3 name'=>'col 3 value'
        ));

        # Column names should be at second line
        # Values should be at second line
        $this->assertContentsEquals(
            "\"col 1 name\";\"col 2 name\";\"col 3 name\"\n" .
            "\"col 1 value\";\"col 2 value\";\"col 3 value\"\n",
            $writer
        );
        $writer->finish();
    }
}
