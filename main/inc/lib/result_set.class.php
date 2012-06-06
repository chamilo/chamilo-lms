<?php

/**
 * ResultSet
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class ResultSet implements Countable, Iterator
{

    /**
     *
     * @param string $sql
     * @return ResultSet 
     */
    static function create($sql)
    {
        return new self($sql);
    }

    protected $sql = '';
    protected $handle = null;
    protected $current = false;
    protected $index = -1;
    protected $count = false;
    protected $limit_count = null;
    protected $limit_offset = null;
    protected $orderby_column = null;
    protected $orderby_direction = null;
    protected $return_type = null;

    function __construct($sql, $limit_count = null, $limit_offset = null, $orderby_column = null, $orderby_direction = null, $return_type = null)
    {
        $this->sql = $sql;
        $this->limit_count = $limit_count;
        $this->limit_offset = $limit_offset;
        $this->orderby_column = $orderby_column;
        $this->orderby_direction = $direction;
        $this->return_type = $return_type;
    }

    public function sql()
    {
        $sql = $this->sql;

        $column = $this->orderby_column;
        $direction = $this->orderby_direction;

        $offset = $this->limit_offset;
        $count = $this->limit_count;
        if (is_null($column) && is_null($count) && is_null($offset)) {
            return $sql;
        }

        if (strpos($sql, ' ORDER ') || strpos($sql, ' LIMIT ') || strpos($sql, ' OFFSET ')) {
            $sql = "SELECT * FROM ($sql) AS dat ";
        } else {
            $sql .= ' ';
        }

        if ($column) {
            $sql .= "ORDER BY $column $direction ";
        }

        if ($count) {
            $sql .= "LIMIT $count ";
        }
        if ($offset) {
            $sql .= "OFFSET $offset";
        }

        return $sql;
    }

    protected function handle()
    {
        if (is_null($this->handle)) {
            $this->handle = Database::query($this->sql());
        }

        return $this->handle;
    }

    public function count()
    {
        if ($this->count === false) {
            $sql = $this->sql();
            $sql = "SELECT COUNT(*) AS alpha FROM ($sql) AS dat ";
            $rs = Database :: query($sql);
            $data = Database::fetch_array($rs);
            $count = $data ? $data['alpha'] : 0;
            $this->count = (int) $count;
        }
        return $this->count;
    }

    public function first()
    {
        foreach ($this as $item) {
            return $item;
        }
        return null;
    }

    /**
     *
     * @param int $count
     * @param int $from
     * @return ResultSet 
     */
    public function limit($count, $from = 0)
    {
        $result = clone($this);
        $result->limit_offset = $from;
        $result->limit_count = $count;
        return $result;
    }

    /**
     *
     * @param int $column
     * @param int $dir
     * @return ResultSet 
     */
    public function orderby($column, $dir = 'ASC')
    {
        $result = clone($this);
        $result->orderby_column = $column;
        $result->orderby_direction = $dir;
        return $result;
    }

    public function return_type($value)
    {
        $result = clone($this);
        $result->return_type = $value;
        return $result;
    }

    public function current()
    {
        return $this->current;
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        $data = Database::fetch_assoc($this->handle());
        if (!$data) {
            $this->current = $this->return_type ? null : array();
        } else if (empty($this->return_type)) {
            $this->current = $data;
        } else if ($this->return_type == 'object') {
            $this->current = (object) $data;
        } else {
            $this->current = new $this->return_type($data);
        }
        $this->index++;
        return $this->current;
    }

    public function rewind()
    {
        $this->handle = null;
        $this->current = false;
        $this->index = -1;
        $this->next();
    }

    public function valid()
    {
        return !empty($this->current);
    }

    function __clone()
    {
        $this->reset();
    }

    function reset()
    {
        $this->handle = null;
        $this->current = false;
        $this->index = -1;
        $this->count = false;
    }

}