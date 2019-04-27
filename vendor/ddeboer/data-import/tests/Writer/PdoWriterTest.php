<?php

namespace Ddeboer\DataImport\Tests\Writer;

use Ddeboer\DataImport\Writer\PdoWriter;

class PdoWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PDO
     */
    private $pdo;

    public function setUp()
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); //important
        $this->pdo->exec('DROP TABLE IF EXISTS `example`');
        $this->pdo->exec('CREATE TABLE `example` (a TEXT, b TEXT)');
    }

    public function testValidWriteItem()
    {
        $writer = new PdoWriter($this->pdo, 'example');
        $writer->prepare();
        $writer->writeItem(array('a' => 'foo', 'b' => 'bar'));
        $writer->finish();

        $stmnt = $this->pdo->query('SELECT * FROM `example`');
        $this->assertEquals(
            array(array('a'=>'foo', 'b'=>'bar')),
            $stmnt->fetchAll(\PDO::FETCH_ASSOC),
            'database does not contain expected row'
        );
    }

    public function testValidWriteMultiple()
    {
        $writer = new PdoWriter($this->pdo, 'example');
        $writer->prepare();
        $writer->writeItem(array('a' => 'foo', 'b' => 'bar'));
        $writer->writeItem(array('a' => 'cat', 'b' => 'dog'));
        $writer->writeItem(array('a' => 'ac', 'b' => 'dc'));
        $writer->finish();

        $stmnt = $this->pdo->query('SELECT * FROM `example`');
        $this->assertEquals(
            array(array('a'=>'foo', 'b'=>'bar'), array('a'=>'cat', 'b'=>'dog'), array('a'=>'ac', 'b'=>'dc')),
            $stmnt->fetchAll(\PDO::FETCH_ASSOC),
            'database does not contain all expected rows'
        );
    }

    /**
     * @expectedException \Ddeboer\DataImport\Exception\WriterException
     */
    public function testWriteTooManyValues()
    {
        $writer = new PdoWriter($this->pdo, 'example');
        $writer->prepare();
        $writer->writeItem(array('foo', 'bar', 'baz')); //expects two
        $writer->finish();
    }

    /**
     * @expectedException \Ddeboer\DataImport\Exception\WriterException
     */
    public function testWriteToNonexistentTable()
    {
        $writer = new PdoWriter($this->pdo, 'foobar');
        $writer->prepare();
        $writer->writeItem(array('foo', 'bar'));
        $writer->finish();
    }

    /**
     * Tests PDO instance with silent errors.
     *
     * @expectedException \Ddeboer\DataImport\Exception\WriterException
     */
    public function testStatementCreateFailureWithNoException()
    {
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);

        $writer = new PdoWriter($this->pdo, 'foob`ar');
        $writer->prepare();
        $writer->writeItem(array('foo', 'bar'));
        $writer->finish();
    }

    /**
     * Tests PDO instance with silent errors. First inert prepares the statement, second creates an exception.
     *
     * @expectedException \Ddeboer\DataImport\Exception\WriterException
     */
    public function testWriteFailureWithNoException()
    {
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);

        $writer = new PdoWriter($this->pdo, 'example');
        $writer->prepare();
        $writer->writeItem(array('foo', 'bar'));
        $writer->writeItem(array('foo', 'bar', 'baz'));
        $writer->finish();
    }
}
