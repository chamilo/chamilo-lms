<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Source;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Statement;
use Exporter\Exception\InvalidMethodCallException;

class DoctrineDBALConnectionSourceIterator implements SourceIteratorInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var mixed
     */
    protected $current;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var Statement
     */
    protected $statement;

    /**
     * @param Connection $connection
     * @param            $query
     * @param array      $parameters
     */
    public function __construct(Connection $connection, $query, array $parameters = array())
    {
        $this->connection = $connection;
        $this->query = $query;
        $this->parameters = $parameters;

        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->current = $this->statement->fetch(\PDO::FETCH_ASSOC);
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return is_array($this->current);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        if ($this->statement) {
            throw new InvalidMethodCallException('Cannot rewind a PDOStatement');
        }

        $this->statement = $this->connection->prepare($this->query);
        $this->statement->execute($this->parameters);

        $this->next();
    }
}
