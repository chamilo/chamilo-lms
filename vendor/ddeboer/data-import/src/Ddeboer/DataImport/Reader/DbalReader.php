<?php
namespace Ddeboer\DataImport\Reader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;

/**
 * Reads data through the Doctrine DBAL
 */
class DbalReader implements CountableReaderInterface
{
    /** @var Connection */
    private $connection;

    private $data;

    /** @var Statement */
    private $stmt;

    /** @var string */
    private $sql;
    /** @var array */
    private $params;

    /** @var integer */
    private $rowCount;
    /** @var bool */
    private $rowCountCalculated = true;

    private $key;

    /**
     * Constructor
     *
     * @param Connection $connection Database connection
     * @param string     $sql        SQL statement
     * @param array      $params     SQL statement parameters
     */
    public function __construct(Connection $connection, $sql, array $params = array())
    {
        $this->connection = $connection;

        $this->setSql($sql, $params);
    }

    /**
     * Do calculate row count?
     *
     * @param bool $calculate
     *
     * @return $this
     */
    public function setRowCountCalculated($calculate = true)
    {
        $this->rowCountCalculated = (bool) $calculate;

        return $this;
    }

    /**
     * Is row count calculated?
     *
     * @return bool
     */
    public function isRowCountCalculated()
    {
        return $this->rowCountCalculated;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        if (null === $this->data) {
            $this->rewind();
        }
        if (false === $this->data) {
            return array();
        }

        return array_keys((array) $this->data);
    }

    /**
     * Set Query string with Parameters
     *
     * @param string $sql
     * @param array  $params
     *
     * @return $this
     */
    public function setSql($sql, array $params = array())
    {
        $this->sql = (string) $sql;

        $this->setSqlParameters($params);

        return $this;
    }

    /**
     * Set SQL parameters
     *
     * @param array  $params
     *
     * @return $this
     */
    public function setSqlParameters(array $params)
    {
        $this->params = $params;

        $this->stmt = null;
        $this->rowCount = null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        if (null === $this->data) {
            $this->rewind();
        }

        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->key++;
        $this->data = $this->stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        if (null === $this->data) {
            $this->rewind();
        }

        return (false !== $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        if (null === $this->stmt) {
            $this->stmt = $this->prepare($this->sql, $this->params);
        }
        if (0 !== $this->key) {
            $this->stmt->execute();
            $this->data = $this->stmt->fetch(\PDO::FETCH_ASSOC);
            $this->key = 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if (null === $this->rowCount) {
            if ($this->rowCountCalculated) {
                $this->doCalcRowCount();
            } else {
                if (null === $this->stmt) {
                    $this->rewind();
                }
                $this->rowCount = $this->stmt->rowCount();
            }
        }

        return $this->rowCount;
    }

    private function doCalcRowCount()
    {
        $statement = $this->prepare('SELECT COUNT(*) FROM ('.$this->sql.')', $this->params);
        $statement->execute();

        $this->rowCount = (int) $statement->fetchColumn(0);
    }

    /**
     * Prepare given statement
     *
     * @param string $sql
     * @param array  $params
     *
     * @return Statement
     */
    private function prepare($sql, array $params)
    {
        $statement = $this->connection->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }

        return $statement;
    }
}
