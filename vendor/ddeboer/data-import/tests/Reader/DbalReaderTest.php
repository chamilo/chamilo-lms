<?php

namespace Ddeboer\DataImport\Tests\Reader;

use Ddeboer\DataImport\Reader\DbalReader;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;

class DbalReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testCalculateRowCount()
    {
        $reader = $this->getReader();

        $reader->setRowCountCalculated();
        $this->assertTrue($reader->isRowCountCalculated());

        $reader->setRowCountCalculated(false);
        $this->assertFalse($reader->isRowCountCalculated());

        $reader->setRowCountCalculated(true);
        $this->assertTrue($reader->isRowCountCalculated());
    }

    public function testGetFields()
    {
        $fields = $this->getReader()->getFields();
        $this->assertInternalType('array', $fields);
        $this->assertEquals(array('id', 'username', 'name'), $fields);
    }

    public function testCount()
    {
        $this->assertEquals(10, $this->getReader()->count());
    }

    public function testCountInhibited()
    {
        $reader = $this->getReader();
        $reader->setRowCountCalculated(false);

        $this->assertEquals(null, $reader->count());
    }

    public function testSqlAndParamsAreMutable()
    {
        $reader = $this->getReader();

        $reader->setSql('SELECT * FROM groups WHERE id = :id', array('id' => 2));
        $this->assertAttributeEquals('SELECT * FROM groups WHERE id = :id', 'sql', $reader);
        $this->assertAttributeEquals(array('id' => 2), 'params', $reader);
    }

    public function testChangeSqlOrParamsClearsNumRowsAndStatement()
    {
        $reader = $this->getReader();
        $reader->count();
        $reader->getFields();

        $this->assertAttributeNotEmpty('rowCount', $reader);
        $this->assertAttributeNotEmpty('stmt', $reader);

        $reader->setSql('SELECT * FROM `user` WHERE id IN (:id)', array('id' => array()));

        $this->assertAttributeEmpty('rowCount', $reader);
        $this->assertAttributeEmpty('stmt', $reader);
    }

    public function testIterate()
    {
        $i=31;
        foreach ($this->getReader() as $key => $row) {
            $this->assertInternalType('array', $row);
            $this->assertEquals('user-'.$i, $row['username']);
            $this->assertEquals($i - 31, $key);
            $i++;
        }

        $this->assertEquals(41, $i);
    }

    public function testReaderRewindWorksCorrectly()
    {
        $reader = $this->getReader();
        foreach ($reader as $row) {
            if (!isset($row['username'])) {
                $this->fail('There should be a username');
            }
            if ($row['username'] == 'user-35') {
                break;
            }
        }

        $reader->rewind();

        $this->assertEquals(array(
            'id' => 31,
            'username' => 'user-31',
            'name' => 'name 4',
        ), $reader->current());
    }

    public function testCallingCurrentTwiceShouldNotAdvance()
    {
        $reader = $this->getReader();

        $expected = array(
            'id' => 31,
            'username' => 'user-31',
            'name' => 'name 4',
        );
        $this->assertEquals($expected, $reader->current());
        $this->assertEquals($expected, $reader->current());
    }

    public function testEmptyResultDoesNotThrowException()
    {
        $reader = $this->getReader();

        $reader->setSql(null, array('name' => 'unknown group'));
        $this->assertInternalType('array', $reader->getFields());
    }

    public function testCallValidRewindsIfNeeded()
    {
        $reader = $this->getReader();

        $this->assertTrue($reader->valid());
        $this->assertAttributeInternalType('array', 'data', $reader);
    }

    public function getConnection()
    {
        $params = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $connection = DriverManager::getConnection($params, new Configuration());

        $schema = new Schema();

        $table = $schema->createTable('groups');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', array('length' => 45));
        $table->setPrimaryKey(array('id'));

        $myTable = $schema->createTable('user');
        $myTable->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
        $myTable->addColumn('username', 'string', array('length' => 32));
        $myTable->addColumn('group_id', 'integer');
        $myTable->setPrimaryKey(array('id'));
        $myTable->addUniqueIndex(array('username'));
        $myTable->addForeignKeyConstraint($table, array('group_id'), array('id'));

        foreach ($schema->toSql(new SqlitePlatform()) as $query) {
            $connection->query($query);
        };

        return $connection;
    }

    protected function getReader()
    {
        $connection = $this->getConnection();
        $this->loadFixtures($connection);

        return new DbalReader($connection, implode(' ', array(
            'SELECT u.id, u.username, g.name',
            'FROM `user` u INNER JOIN groups g ON u.group_id = g.id',
            'WHERE g.name LIKE :name',
        )), array(
            'name' => 'name 4',
        ));
    }

    /**
     * @param Connection $connection
     */
    protected function loadFixtures($connection)
    {
        $counter = 1;
        for ($i = 1; $i <= 10; $i++) {
            $connection->insert('groups', array('name' => "name {$i}"));
            $id = $connection->lastInsertId();

            for ($j = 1; $j <= 10; $j++) {
                $connection->insert('user', array(
                    'username' => "user-{$counter}",
                    'group_id' => $id,
                ));

                $counter++;
            }
        }
    }
}
