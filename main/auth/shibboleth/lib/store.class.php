<?php

namespace Shibboleth;

use \Database;

/**
 * A database store. Used interact with the database - save objects, run queries.
 * 
 * One store = one table.
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
class Store
{

    /**
     *
     * @return Store 
     */
    public static function create($table_name, $class_name = '', $id_name = 'id', $db_name = '')
    {
        return new self($table_name, $class_name, $id_name, $db_name);
    }

    protected $db_name = '';
    protected $table_name = '';
    protected $id_name = '';
    protected $class_name = '';

    function __construct($table_name, $class_name = '', $id_name = 'id', $db_name = '')
    {
        $this->db_name = $db_name ? $db_name : Database::get_main_database();
        $this->table_name = $table_name;
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    function get_db_name($object = '')
    {
        if ($this->db_name)
        {
            return $this->db_name;
        }
        if ($object)
        {
            $result = isset($object->{db_name}) ? $object->{db_name} : '';
            $result = $result ? $result : Database :: get_main_database();
            return $result;
        }

        return Database::get_main_database();
    }

    function get($w)
    {
        $args = func_get_args();
        $f = array($this, 'get_where');
        $db_name = $this->get_db_name();
        $where = call_user_func_array($f, $args);
        $sql = "SELECT * 
                FROM `{$db_name}`.`{$this->table_name}`
                WHERE $where";

        $items = $this->query($sql);
        return (count($items) == 1) ? reset($items) : null;
    }

    function select($w)
    {
        $args = func_get_args();
        $f = array($this, 'get_where');
        $db_name = $this->get_db_name();
        $where = call_user_func_array($f, $args);
        $sql = "SELECT * 
                FROM `{$db_name}`.`{$this->table_name}`
                WHERE $where";

        $result = $this->query($sql);
        return $result;
    }

    function exist($w)
    {
        $args = func_get_args();
        $f = array($this, 'get');
        $object = call_user_func_array($f, $args);
        return !empty($object);
    }

    function is_new($object)
    {
        $id_name = $this->id_name;
        $id = isset($object->{$id_name}) ? $object->{$id_name} : false;
        return empty($id);
    }

    function save($object)
    {
        if (empty($object))
        {
            return false;
        }
        $object = is_array($object) ? $this->create_object($object) : $object;
        $this->before_save($object);
        if ($this->is_new($object))
        {
            $result = $this->insert($object);
        }
        else
        {
            $result = $this->update($object);
        }
        return $result;
    }

    function delete($object)
    {
        $args = func_get_args();
        $f = array($this, 'get_where');
        $db_name = $this->get_db_name();
        $where = call_user_func_array($f, $args);
        $sql = "DELETE  
                FROM `{$db_name
                }

`.`{$this->table_name
                }

`
                WHERE $where";

        $result = $this->query($sql);
        return $result;
    }

    /**
     *
     * @param array|object $data
     * @return object
     */
    public function create_object($data = array())
    {
        $data = $data ? $data : array();
        $data = (object) $data;
        $class = $this->class_name;
        if (empty($class))
        {
            return clone $data;
        }
        $result = new $class();

        foreach ($result as $key => $value)
        {
            $result->{$key} = property_exists($data, $key) ? $data->{$key} : null;
        }
        return $result;
    }

    public function fields($object)
    {
        static $result = array();
        if (!empty($result))
        {
            return $result;
        }

        $db_name = $this->get_db_name($object);
        $sql = "SELECT * 
                FROM `{$db_name}`.`{$this->table_name}`
                LIMIT 1";
        $rs = Database::query($sql, null, __FILE__);
        while ($field = mysql_fetch_field($rs))
        {
            $result[] = $field;
        }
        return $result;
    }

    protected function before_save($object)
    {
//hook
    }

    protected function update($object)
    {
        $id = isset($object->{$this->id_name}) ? $object->{$this->id_name} : false;
        if (empty($id))
        {
            return false;
        }
        $items = array();
        $fields = $this->fields($object);
        foreach ($fields as $field)
        {
            $name = $field->name;
            if ($name != $this->id_name)
            {
                if (property_exists($object, $name))
                {
                    $value = $object->{$name};
                    $value = $this->format_value($value);
                    $items[] = "$name=$value";
                }
            }
        }

        $db_name = $this->get_db_name($object);
        $sql = "UPDATE `{$db_name}`.`{$this->table_name}` SET ";
        $sql .= join(', ', $items);
        $sql .= " WHERE {$this->id_name}=$id";

        $result = $this->execute($sql);
        if ($result)
        {
            $object->{db_name} = $db_name;
        }
        return (bool) $result;
    }

    protected function insert($object)
    {
        $id = isset($object->{$this->id_name}) ? $object->{$this->id_name} : false;
        if (empty($object))
        {
            return false;
        }
        $values = array();
        $keys = array();
        $fields = $this->fields($object);
        foreach ($fields as $field)
        {
            $name = $field->name;
            if ($name != $this->id_name)
            {
                if (property_exists($object, $name))
                {
                    $value = $object->{$name};
                    $value = is_null($value) ? 'DEFAULT' : $this->format_value($value);
                    $values[] = $value;
                    $keys[] = $name;
                }
            }
        }

        $db_name = $this->get_db_name($object);
        $sql = "INSERT INTO `{$db_name}`.`{$this->table_name}` ";
        $sql .= ' (' . join(', ', $keys) . ') ';
        $sql .= 'VALUES';
        $sql .= ' (' . join(', ', $values) . ') ';

        $result = $this->execute($sql);
        if ($result)
        {
            $id = mysql_insert_id();
            $object->{$this->id_name} = $id;
            $object->{db_name} = $db_name;
            return $id;
        }
        else
        {
            return false;
        }
    }

    protected function get_where($_)
    {
        $args = func_get_args();
        if (count($args) == 1)
        {
            $arg = reset($args);
            if (is_numeric($arg))
            {
                $id = (int) $arg;
                if (empty($id))
                {
                    return '';
                }
                $args = array($this->pk_name, $arg);
            }
            else if (is_string($arg))
            {
                return $arg;
            }
            else if (is_array($arg))
            {
                $args = $arg;
            }
            else
            {
                return $arg;
            }
        }
        $items = array();
        foreach ($args as $key => $val)
        {
            $items[] = $key . ' = ' . $this->format_value($val);
        }
        return implode(' AND ', $items);
    }

    protected function format_value($value)
    {
        if (is_null($value))
        {
            return 'NULL';
        }
        if (is_bool($var))
        {
            return $value ? '1' : '0';
        }
        else if (is_numeric($value))
        {
            return empty($value) ? '0' : $value;
        }
        else if (is_string($value))
        {
            $value = mysql_escape_string($value);
            return "'$value'";
        }
        else
        {
            return $value;
        }
    }

    /**
     *
     * @param string $sql
     * @return array 
     */
    protected function query($sql)
    {
        $resource = Database::query($sql, null, __FILE__);
        if ($resource == false)
        {
            return array();
        }

        $result = array();
        while ($data = mysql_fetch_assoc($resource))
        {
            $result[] = $this->create_object($data);
        }
        return $result;
    }

    protected function execute($sql)
    {
        return Database::query($sql, null, __FILE__);
    }

}