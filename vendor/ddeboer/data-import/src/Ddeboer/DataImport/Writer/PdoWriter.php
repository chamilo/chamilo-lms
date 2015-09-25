<?php

namespace Ddeboer\DataImport\Writer;

use \Ddeboer\DataImport\Exception\WriterException;

/**
 * Class PdoWriter
 *
 * Write data into a specific database table using a PDO instance.
 *
 * IMPORTANT: If your PDO instance does not have ERRMODE_EXCEPTION any write failure will be silent or logged to
 * stderr only. It is strongly recomended you enable Exceptions with:
 *
 *     $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
 *
 */
class PdoWriter implements WriterInterface
{
    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var \PDOStatement
     */
    protected $statement;

    /**
     * Note if your table name is a reserved word for your target DB you should quote it in the appropriate way e.g.
     * for MySQL enclose the name in `backticks`.
     *
     * @param \PDO $pdo
     * @param string $tableName
     */
    public function __construct(\PDO $pdo, $tableName)
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    /**
     * {@inheritDoc}
     */
    public function prepare()
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function writeItem(array $item)
    {
        try {
            //prepare the statment as soon as we know how many values there are
            if (!$this->statement) {

                $this->statement = $this->pdo->prepare(
                    'INSERT INTO '.$this->tableName.'('.implode(',', array_keys($item)).') VALUES ('.substr(str_repeat('?,', count($item)), 0, -1).')'
                );

                //for PDO objects that do not have exceptions enabled
                if (!$this->statement) {
                    throw new WriterException('Failed to prepare write statement for item: '.implode(',', $item));
                }
            }

            //do the insert
            if (!$this->statement->execute(array_values($item))) {
                throw new WriterException('Failed to write item: '.implode(',', $item));
            }

        } catch (\Exception $e) {
            //convert exception so the abstracton doesn't leak
            throw new WriterException('Write failed ('.$e->getMessage().').', null, $e);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function finish()
    {
        return $this;
    }
}
