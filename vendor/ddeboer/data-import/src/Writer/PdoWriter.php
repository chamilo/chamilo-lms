<?php

namespace Ddeboer\DataImport\Writer;

use Ddeboer\DataImport\Exception\WriterException;
use Ddeboer\DataImport\Writer;

/**
 * Write data into a specific database table using a PDO instance.
 *
 * IMPORTANT: If your PDO instance does not have ERRMODE_EXCEPTION any write failure will be silent or logged to
 * stderr only. It is strongly recomended you enable Exceptions with:
 *
 *     $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
 *
 * @author Stefan Warman
 */
class PdoWriter implements Writer, FlushableWriter
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
     * @var array
     */
    private $stack;

    /**
     * Note if your table name is a reserved word for your target DB you should quote it in the appropriate way e.g.
     * for MySQL enclose the name in `backticks`.
     *
     * @param \PDO   $pdo
     * @param string $tableName
     */
    public function __construct(\PDO $pdo, $tableName)
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;

        if (\PDO::ERRMODE_EXCEPTION !== $this->pdo->getAttribute(\PDO::ATTR_ERRMODE)) {
            throw new WriterException('Please set the pdo error mode to PDO::ERRMODE_EXCEPTION');
        }
    }

    public function prepare()
    {
        $this->stack = [];
        $this->statement = null;
    }

    /**
     * {@inheritdoc}
     */
    public function writeItem(array $item)
    {
        if (null === $this->statement) {
            try {
                $this->statement = $this->pdo->prepare(sprintf(
                    'INSERT INTO %s (%s) VALUES (%s)',
                    $this->tableName,
                    implode(',', array_keys($item)),
                    substr(str_repeat('?,', count($item)), 0, -1)
                ));
            } catch (\PDOException $e) {
                throw new WriterException('Failed to send query', null, $e);
            }
        }

        $this->stack[] = array_values($item);
    }

    public function finish()
    {
        $this->flush();

        return $this;
    }

    public function flush()
    {
        $this->pdo->beginTransaction();

        try {
            foreach ($this->stack as $data) {
                $this->statement->execute($data);
            }
            $this->stack = [];

            $this->pdo->commit();
        } catch (\PDOException $e) {
            throw new WriterException('Failed to write to database', null, $e);
        }
    }
}
