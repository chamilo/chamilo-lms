<?php
/* For licensing terms, see /license.txt */
/**
 *  This is the main database library for Chamilo.
 *  Include/require it in your code to use its functionality.
 *
 *  This library now uses a Doctrine DBAL Silex service provider
 *
 * @package chamilo.library
 */

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Driver\Statement;

/**
 * Database class definition
 * @package chamilo.database
 */
class Database
{
    /**
     * The main connection
     *
     * @var Connection
     */
    private static $db;

    /**
     * Read connection
     *
     * @var Connection
     */
    private static $connectionRead;

    /**
     * Write connection
     *
     * @var Connection
     */
    private static $connectionWrite;

    /**
     * @var EntityManager
     */
    private static $em;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Return current connection
     * @return Connection
     */
    public function getConnection()
    {
        return self::$db;
    }

    /**
     * @param $db
     * @param $dbs
     */
    public function setConnection($db, $dbs = array())
    {
        self::$db = $db;

        // Using read/write connections see the services.php file
        self::$connectionRead = isset($dbs['db_read']) ? $dbs['db_read'] : $db;
        self::$connectionWrite = isset($dbs['db_write']) ? $dbs['db_write'] : $db;
    }

    /**
     * @param EntityManager $em
     */
    public static function setManager($em)
    {
        self::$em = $em;
    }

    /**
     * @return EntityManager
     */
    public static function getManager()
    {
        return self::$em;
    }

    /**
     * Returns the name of the main database.
     * @return string
     */
    public static function get_main_database()
    {
        return self::$db->getDatabase();
    }

    /**
     *    The glue is the string needed between database and table.
     *    The trick is: in multiple databases, this is a period (with backticks).
     *    In single database, this can be e.g. an underscore so we just fake
     *    there are multiple databases and the code can be written independent
     *    of the single / multiple database setting.
     *    @return string
     */
    public static function get_database_glue()
    {
        return `.`;
    }

    /*
        Table name methods
        Use these methods to get table names for queries,
        instead of constructing them yourself.

        Backticks automatically surround the result,
        e.g. COURSE_NAME.link
        so the queries can look cleaner.

        Example:
        $table = Database::get_course_table(TABLE_DOCUMENT);
        $sql_query = "SELECT * FROM $table WHERE $condition";
        $sql_result = Database::query($sql_query);
        $result = Database::fetch_array($sql_result);
    */

    /**
     * This function returns the correct complete name of any table of the main
     * database of which you pass the short name as a parameter.
     * Define table names as constants in this library and use them
     * instead of directly using magic words in your tool code.
     *
     * @param string $short_table_name, the name of the table
     * @return string
     */
    public static function get_main_table($short_table_name)
    {
        return self::format_table_name(self::get_main_database(), $short_table_name);
    }

    /**
     * This method returns the correct complete name of any course table of
     * which you pass the short name as a parameter.
     * Define table names as constants in this library and use them
     * instead of directly using magic words in your tool code.
     *
     * @param string $short_table_name, the name of the table
     * @return string
     *
     */
    public static function get_course_table($short_table_name)
    {
        return self::format_table_name(self::get_main_database(), DB_COURSE_PREFIX.$short_table_name);
    }

    /**
     * Returns the number of affected rows in the last database operation.
     * @param \Doctrine\DBAL\Driver\Statement $result
     * @return int
     */
    public static function affected_rows(Statement $result = null)
    {
        return $result->rowCount();
        //return self::use_default_connection($connection) ? mysql_affected_rows() : mysql_affected_rows($connection);
    }

    /**
     * Gets the array from a SQL result (as returned by Database::query) - help achieving database independence
     * @param resource        The result from a call to sql_query (e.g. Database::query)
     * @param string        Optional: "ASSOC","NUM" or "BOTH", as the constant used in mysql_fetch_array.
     * @return array        Array of results as returned by php
     * @author Yannick Warnier <yannick.warnier@beeznest.com>
     */
    public static function fetch_array(Statement $result, $option = 'BOTH')
    {
        if ($result === false) {
            return array();
        }
        return $result->fetch(self::customOptionToDoctrineOption($option));

        /*return $option == 'ASSOC' ? mysql_fetch_array($result, MYSQL_ASSOC) : ($option == 'NUM' ? mysql_fetch_array(
            $result,
            MYSQL_NUM
        ) : mysql_fetch_array($result));*/
    }

    /**
     * Gets an associative array from a SQL result (as returned by Database::query).
     * This method is equivalent to calling Database::fetch_array() with 'ASSOC' value for the optional second parameter.
     * @param resource $result    The result from a call to sql_query (e.g. Database::query).
     * @return array            Returns an associative array that corresponds to the fetched row and moves the internal data pointer ahead.
     */
    public static function fetch_assoc(Statement $result)
    {
        return $result->fetch(PDO::FETCH_ASSOC);
        //return mysql_fetch_assoc($result);
    }

    /**
     * Gets the next row of the result of the SQL query (as returned by Database::query) in an object form
     * @param    \Doctrine\DBAL\Driver\Statement    The result from a call to Database::query())
     * @param    string        Optional class name to instanciate
     * @param    array        Optional array of parameters
     * @return    object        Object of class StdClass or the required class, containing the query result row
     * @author    Yannick Warnier <yannick.warnier@dokeos.com>
     */
    public static function fetch_object(Statement $result)
    {
        // Waiting for http://www.doctrine-project.org/jira/browse/DBAL-544 in order to know which constant use.
        //return $result->fetch(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        return $result->fetch(PDO::FETCH_OBJ);

        /*return !empty($class) ? (is_array($params) ? mysql_fetch_object($result, $class, $params) : mysql_fetch_object(
            $result,
            $class
        )) : mysql_fetch_object($result);*/
    }

    /**
     * Gets the array from a SQL result (as returned by Database::query) - help achieving database independence
     * @param  \Doctrine\DBAL\Driver\Statement    The result from a call to Database::query())
     * @return array        Array of results as returned by php
     */
    public static function fetch_row(Statement $result)
    {
        return $result->fetch(PDO::FETCH_NUM);
        //return mysql_fetch_row($result);
    }

    /**
     * Gets the ID of the last item inserted into the database
     * @return int The last ID as returned by the DB function
     */
    public static function insert_id()
    {
        return self::$connectionWrite->lastInsertId();
    }

    /**
     * Gets the number of rows from the last query result - help achieving database independence
     * @param Statement $result
     * @return integer The number of rows contained in this result
     **/
    public static function num_rows(Statement $result)
    {
        return $result->rowCount();
    }

    /**
     * Acts as the relative *_result() function of most DB drivers and fetches a
     * specific line and a field
     * @param    Statement     The database resource to get data from
     * @param    integer        The row number
     * @param    string        Optional field name or number
     * @return    mixed        One cell of the result, or FALSE on error
     */
    public static function result(Statement $resource, $row, $field = 0)
    {
        if ($resource->rowCount() > 0) {
            $result = $resource->fetchAll(PDO::FETCH_BOTH);
            return $result[$row][$field];
        }

        return null;
    }

    /**
     * Frees all the memory associated with the provided result identifier.
     * @return bool        Returns TRUE on success or FALSE on failure.
     * Notes: Use this method if you are concerned about how much memory is being used for queries that return large result sets.
     * Anyway, all associated result memory is automatically freed at the end of the script's execution.
     */
    public static function free_result(Statement $result)
    {
        $result->closeCursor();
        //return mysql_free_result($result);
    }

    /**
     * Detects if a query is going to modify something in the database in order to use the write connection
     * @param string $query
     * @return bool
     */
    public static function isWriteQuery($query)
    {
        $isWriteQuery = preg_match("/UPDATE(.*) FROM/i", $query) ||
            preg_match("/INSERT INTO/i", $query) ||
            preg_match("/REPLACE INTO/i", $query) ||
            preg_match("/DELETE FROM/i", $query);
        return $isWriteQuery;
    }

    /**
     * Escapes a string to insert into the database as text
     * @param string The string to escape
     * @return string The escaped string
     */
    public static function escape_string($string)
    {
        /* The pdo::quote function adds a "'" character we need to remove that '
           because in Chamilo, developers builds a query like this:
           $sql = "SELECT * FROM $table WHERE id = 'Database::escape_string($id)'";
           otherwise we will have an error because the query will be:
           SELECT * FROM user WHERE id = ''1'' instead of
           SELECT * FROM user WHERE id = '1'
        */
        // $string = '_@_'.self::$db->quote($string).'_@_';

        $string = self::$db->quote($string);
        return trim($string, "'");
        return $string;
    }

    /**
     * Executes a query in the database
     * @author Julio Montoya
     * @param string $query The SQL query
     * @return Statement
     */
    public static function query($query)
    {
        $isWriteQuery = self::isWriteQuery($query);
        if ($isWriteQuery) {
            $connection = self::$connectionWrite;
        } else {
            $connection = self::$connectionRead;
        }
        /* The solution below does not work because there are some case where we use the "LIKE" option like this:
            $sql  = 'SELECT * FROM user WHERE id LIKE "%'.Database::escape_string($id).' %" ;

            Chamilo queries are formed in many ways:
            $sql  = "SELECT * FROM user WHERE id = '".Database::escape_string($id)."'; or
            $sql  = 'SELECT * FROM user WHERE id = '.Database::escape_string($id).';

            The problem here is that the function escape_string() calls the quote function that adds a "'" string.
            Instead of this we're adding a identifier __@__ so we can identify those cases and replace with a simple '
        */
        //var_dump($query);
        /*$query = str_replace(
            array(
                "\"_@_'",
                "'_@_\"",
                "'_@_'",
                "_@_'",
                "'_@_",
            ),
            "'",
            $query
        );*/
        //var_dump($query);
        return $connection->executeQuery($query);

    }

    public static function customOptionToDoctrineOption($option)
    {
        switch($option) {
            case 'ASSOC':
                return PDO::FETCH_ASSOC;
                break;
            case 'NUM':
                return PDO::FETCH_NUM;
                break;
            case 'BOTH':
            default:
                return PDO::FETCH_BOTH;
                break;
        }
    }

    /**
     * Stores a query result into an array.
     * @param  Statement $result - the return value of the query
     * @param  option BOTH, ASSOC, or NUM
     * @return array - the value returned by the query
     */
    public static function store_result(Statement $result, $option = 'BOTH')
    {
        return $result->fetchAll(self::customOptionToDoctrineOption($option));
        /*
        var_dump($a );
        $array = array();
        if ($result !== false) { // For isolation from database engine's behaviour.
            while ($row = self::fetch_array($result, $option)) {
                $array[] = $row;
            }
        }
        return $array;*/
    }

    /*
        Private methods
        You should not access these from outside the class
        No effort is made to keep the names / results the same.
    */

    /**
     *    Structures a database and table name to ready them
     *    for querying. The database parameter is considered not glued,
     *    just plain e.g. COURSE001
     *   @todo not sure if we need this now
     */
    private static function format_table_name($database, $table)
    {
        /*$glue = '`.`';
        $table_name = '`'.$database.$glue.$table.'`';
        */
        return $table;
        //return $table_name;
    }

    /*
        New useful DB functions
    */

    /**
     * Executes an insert to in the database (dbal already escape strings)
     * @param string table name
     * @param array An array of field and values
     * @param bool show query
     * @return int the id of the latest executed query
     */
    public static function insert($table_name, $attributes, $show_query = false)
    {
        $result = self::$connectionWrite->insert($table_name, $attributes);
        if ($result) {
            return self::insert_id();
        }
        return false;
    }

    /**
     * Experimental useful database finder
     * @todo lot of stuff to do here
     * @todo known issues, it doesn't work when using LIKE conditions
     * @example array('where'=> array('course_code LIKE "?%"'))
     * @example array('where'=> array('type = ? AND category = ?' => array('setting', 'Plugins'))
     * @example array('where'=> array('name = "Julio" AND lastname = "montoya"))
     */
    public static function select($columns, $table_name, $conditions = array(), $type_result = 'all', $option = 'ASSOC')
    {
        //$qb = self::$db->createQueryBuilder();

        $conditions = self::parse_conditions($conditions);

        //@todo we could do a describe here to check the columns ...
        $clean_columns = '';
        if (is_array($columns)) {
            $clean_columns = implode(',', $columns);
        } else {
            if ($columns == '*') {
                $clean_columns = '*';
            } else {
                $clean_columns = (string)$columns;
            }
        }

        /*$qb->select($clean_columns);
        $qb->from($table_name, 'table');
        $qb->orderBy('table.' . $sort_order, 'ASC');*/

        $sql = "SELECT $clean_columns FROM $table_name $conditions";
        //var_dump($sql);

        $result = self::query($sql);
        $array = array();

        if ($type_result == 'all') {
            while ($row = self::fetch_array($result, $option)) {
                if (isset($row['id'])) {
                    $array[$row['id']] = $row;
                } else {
                    $array[] = $row;
                }
            }
        } else {
            $array = self::fetch_array($result, $option);
        }

        return $array;
    }

    /**
     * Parses WHERE/ORDER conditions i.e array('where'=>array('id = ?' =>'4'), 'order'=>'id DESC'))
     * @todo known issues, it doesn't work when using LIKE conditions example: array('where'=>array('course_code LIKE "?%"'))
     * @param array
     * @return string
     * @todo lot of stuff to do here
     */
    static function parse_conditions($conditions)
    {
        if (empty($conditions)) {
            return '';
        }
        $return_value = $where_return = '';
        foreach ($conditions as $type_condition => $condition_data) {
            if ($condition_data == false) {
                continue;
            }
            $type_condition = strtolower($type_condition);
            switch ($type_condition) {
                case 'where':
                    foreach ($condition_data as $condition => $value_array) {
                        if (is_array($value_array)) {
                            $clean_values = array();
                            foreach ($value_array as $item) {
                                $item = Database::escape_string($item);
                                $clean_values[] = $item;
                            }
                        } else {
                            $value_array = Database::escape_string($value_array);
                            $clean_values = $value_array;
                        }

                        if (!empty($condition) && $clean_values != '') {
                            $condition = str_replace('%', "'@percentage@'", $condition); //replace "%"
                            $condition = str_replace("'?'", "%s", $condition);
                            $condition = str_replace("?", "%s", $condition);

                            $condition = str_replace("@%s@", "@-@", $condition);
                            $condition = str_replace("%s", "'%s'", $condition);
                            $condition = str_replace("@-@", "@%s@", $condition);

                            //Treat conditons as string
                            $condition = vsprintf($condition, $clean_values);
                            $condition = str_replace('@percentage@', '%', $condition); //replace "%"
                            $where_return .= $condition;
                        }
                    }


                    if (!empty($where_return)) {
                        $return_value = " WHERE $where_return";
                    }
                    break;
                case 'order':
                    $order_array = $condition_data;

                    if (!empty($order_array)) {
                        // 'order' => 'id desc, name desc'
                        $order_array = $order_array;

                        $new_order_array = explode(',', $order_array);
                        $temp_value = array();

                        foreach ($new_order_array as $element) {
                            $element = explode(' ', $element);
                            $element = array_filter($element);
                            $element = array_values($element);

                            if (!empty($element[1])) {
                                $element[1] = strtolower($element[1]);
                                $order = 'DESC';
                                if (in_array($element[1], array('desc', 'asc'))) {
                                    $order = $element[1];
                                }
                                $temp_value[] = $element[0].' '.$order.' ';
                            } else {
                                //by default DESC
                                $temp_value[] = $element[0].' DESC ';
                            }
                        }
                        if (!empty($temp_value)) {
                            $return_value .= ' ORDER BY '.implode(', ', $temp_value);
                        }
                    }

                    break;
                case 'limit':
                    $limit_array = explode(',', $condition_data);

                    if (!empty($limit_array)) {
                        if (count($limit_array) > 1) {
                            $return_value .= ' LIMIT '.intval($limit_array[0]).' , '.intval($limit_array[1]);
                        } else {
                            $return_value .= ' LIMIT '.intval($limit_array[0]);
                        }
                    }
                    break;
            }
        }

        return $return_value;
    }

    /**
     * @param array $conditions
     * @return string
     */
    public static function parse_where_conditions($conditions)
    {
        return self::parse_conditions(array('where' => $conditions));
    }

    /**
     * Deletes an item depending of conditions
     * @param string $table_name
     * @param array $where_conditions
     * @param bool $show_query
     * @return int
     */
    public static function delete($table_name, $where_conditions, $show_query = false)
    {
        //return self::$connectionWrite->delete($table_name, $where_conditions);

        $where_return = self::parse_where_conditions($where_conditions);
        $sql = "DELETE FROM $table_name $where_return ";
        if ($show_query) {
            echo $sql;
            echo '<br />';
        }
        $result = self::query($sql);
        $affected_rows = self::affected_rows($result);

        //@todo should return affected_rows for
        return $affected_rows;
    }

    /**
     * Experimental useful database update
     * @param    string    table name use Database::get_main_table
     * @param    array    array with values to updates, keys are the fields in the database:
     * @example: $params['name'] = 'Julio'; $params['lastname'] = 'Montoya';
     * @param    array    where conditions i.e array('id = ?' =>'4')
     * @param bool show query
     * @todo lot of stuff to do here
     */
    public static function update($table_name, $attributes, $where_conditions = array(), $show_query = false)
    {
        if (!empty($table_name) && !empty($attributes)) {
            $update_sql = '';
            //Cleaning attributes
            $count = 1;
            foreach ($attributes as $key => $value) {

                if (!is_array($value)) {
                    $value = self::escape_string($value);
                }
                $update_sql .= "$key = '$value' ";
                if ($count < count($attributes)) {
                    $update_sql .= ', ';
                }
                $count++;
            }
            if (!empty($update_sql)) {
                //Parsing and cleaning the where conditions
                $where_return = self::parse_where_conditions($where_conditions);
                $sql = "UPDATE $table_name SET $update_sql $where_return ";
                if ($show_query) {
                    var_dump($sql);
                }
                $result = self::query($sql);
                $affected_rows = self::affected_rows($result);

                return $affected_rows;
            }
        }

        return false;
    }

    /**
     * Counts the number of rows in a table
     * @param string $table The table of which the rows should be counted
     * @return int The number of rows in the given table.
     */
    public static function count_rows($table)
    {
        $obj = self::fetch_object(self::query("SELECT COUNT(*) AS n FROM $table"));
        return $obj->n;
    }

    /**
     * Returns a list of tables within a database. The list may contain all of the
     * available table names or filtered table names by using a pattern.
     * @param string $database (optional)        The name of the examined database.
     * @param string $pattern (optional)        A pattern for filtering table names as if it was needed for the SQL's LIKE clause, for example 'access_%'.
     * @deprecated
     * @return array                            Returns in an array the retrieved list of table names.
     */
    public static function get_tables($database = '', $pattern = '')
    {
        $result = array();
        $query = "SHOW TABLES";
        if (!empty($database)) {
            $query .= " FROM `".self::escape_string($database)."`";
        }
        if (!empty($pattern)) {
            $query .= " LIKE '".self::escape_string($pattern)."'";
        }
        $query_result = Database::query($query);
        while ($row = Database::fetch_row($query_result)) {
            $result[] = $row[0];
        }

        return $result;
    }

    /**
     * Returns a list of databases created on the server. The list may contain all of the
     * available database names or filtered database names by using a pattern.
     * @return array Returns in an array the retrieved list of database names.
     */
    public static function get_databases()
    {
        $sm = self::$db->getSchemaManager();
        return $sm->listDatabases();
    }
}
