<?php
/* For licensing terms, see /license.txt */

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;

/**
 * Class Database
 */
class Database
{
    /**
     * @var EntityManager
     */
    private static $em;
    private static $connection;
    public static $utcDateTimeClass;

    /**
     * @param EntityManager $em
     */
    public function setManager($em)
    {
        self::$em = $em;
    }

    /**
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        self::$connection = $connection;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return self::$connection;
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
     *
     * @return string
     */
    public static function get_main_database()
    {
        return self::getManager()->getConnection()->getDatabase();
    }

    /**
     * Get main table
     *
     * @param string $table
     *
     * @return mixed
     */
    public static function get_main_table($table)
    {
        return $table;
    }

    /**
     * Get course table
     *
     * @param string $table
     *
     * @return string
     */
    public static function get_course_table($table)
    {
        return DB_COURSE_PREFIX.$table;
    }

    /**
     * Counts the number of rows in a table
     * @param string $table The table of which the rows should be counted
     *
     * @return int The number of rows in the given table.
     * @deprecated
     */
    public static function count_rows($table)
    {
        $obj = self::fetch_object(self::query("SELECT COUNT(*) AS n FROM $table"));

        return $obj->n;
    }

    /**
     * Returns the number of affected rows in the last database operation.
     * @param Statement $result
     *
     * @return int
     */
    public static function affected_rows(Statement $result)
    {
        return $result->rowCount();
    }

    /**
     * @return string
     */
    public static function getUTCDateTimeTypeClass()
    {
        return isset(self::$utcDateTimeClass) ? self::$utcDateTimeClass :
        'Application\DoctrineExtensions\DBAL\Types\UTCDateTimeType';
    }

    /**
     * Connect to the database sets the entity manager.
     *
     * @param array  $params
     * @param string $sysPath
     * @param string $entityRootPath
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function connect($params = array(), $sysPath = '', $entityRootPath = '')
    {
        $config = self::getDoctrineConfig($entityRootPath);
        $config->setAutoGenerateProxyClasses(true);

        $config->setEntityNamespaces(
            array(
                'ChamiloUserBundle' => 'Chamilo\UserBundle\Entity',
                'ChamiloCoreBundle' => 'Chamilo\CoreBundle\Entity',
                'ChamiloCourseBundle' => 'Chamilo\CourseBundle\Entity'
            )
        );

        $params['charset'] = 'utf8';
        $entityManager = EntityManager::create($params, $config);
        $sysPath = !empty($sysPath) ? $sysPath : api_get_path(SYS_PATH);

        // Registering Constraints
        AnnotationRegistry::registerAutoloadNamespace(
            'Symfony\Component\Validator\Constraint',
            $sysPath."vendor/symfony/validator"
        );

        AnnotationRegistry::registerFile(
            $sysPath."vendor/symfony/doctrine-bridge/Symfony/Bridge/Doctrine/Validator/Constraints/UniqueEntity.php"
        );

        // Registering gedmo extensions
        AnnotationRegistry::registerAutoloadNamespace(
            'Gedmo\Mapping\Annotation',
            $sysPath."vendor/gedmo/doctrine-extensions/lib"
        );

        Type::overrideType(
            Type::DATETIME,
            self::getUTCDateTimeTypeClass()
        );

        $listener = new \Gedmo\Timestampable\TimestampableListener();
        $entityManager->getEventManager()->addEventSubscriber($listener);

        $listener = new \Gedmo\Tree\TreeListener();
        $entityManager->getEventManager()->addEventSubscriber($listener);

        $listener = new \Gedmo\Sortable\SortableListener();
        $entityManager->getEventManager()->addEventSubscriber($listener);
        $connection = $entityManager->getConnection();

        $this->setConnection($connection);
        $this->setManager($entityManager);
    }

    /**
     * Escape MySQL wildchars _ and % in LIKE search
     * @param string $text            The string to escape
     *
     * @return string           The escaped string
     */
    public static function escape_sql_wildcards($text)
    {
        $text = api_preg_replace("/_/", "\_", $text);
        $text = api_preg_replace("/%/", "\%", $text);

        return $text;
    }

    /**
     * Escapes a string to insert into the database as text
     *
     * @param string $string
     *
     * @return string
     */
    public static function escape_string($string)
    {
        $string = self::getManager()->getConnection()->quote($string);

        return trim($string, "'");
    }

    /**
     * Gets the array from a SQL result (as returned by Database::query)
     *
     * @param Statement $result
     * @param string    $option Optional: "ASSOC","NUM" or "BOTH"
     *
     * @return array|mixed
     */
    public static function fetch_array(Statement $result, $option = 'BOTH')
    {
        if ($result === false) {
            return array();
        }

        return $result->fetch(self::customOptionToDoctrineOption($option));
    }

    /**
     * Gets an associative array from a SQL result (as returned by Database::query).
     *
     * @param Statement $result
     *
     * @return array
     */
    public static function fetch_assoc(Statement $result)
    {
        return $result->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Gets the next row of the result of the SQL query
     * (as returned by Database::query) in an object form
     *
     * @param Statement $result
     *
     * @return mixed
     */
    public static function fetch_object(Statement $result)
    {
        return $result->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Gets the array from a SQL result (as returned by Database::query)
     * help achieving database independence
     *
     * @param Statement $result
     *
     * @return mixed
     */
    public static function fetch_row(Statement $result)
    {
        return $result->fetch(PDO::FETCH_NUM);
    }

    /**
     * Frees all the memory associated with the provided result identifier.
     * @return bool     Returns TRUE on success or FALSE on failure.
     * Notes: Use this method if you are concerned about how much memory is being used for queries that return large result sets.
     * Anyway, all associated result memory is automatically freed at the end of the script's execution.
     */
    public static function free_result(Statement $result)
    {
        $result->closeCursor();
    }

    /**
     * Gets the ID of the last item inserted into the database
     *
     * @return string
     */
    public static function insert_id()
    {
        return self::getManager()->getConnection()->lastInsertId();
    }

    /**
     * @param Statement $result
     *
     * @return int
     */
    public static function num_rows(Statement $result)
    {
        return $result->rowCount();
    }

    /**
     * Acts as the relative *_result() function of most DB drivers and fetches a
     * specific line and a field
     *
     * @param Statement $resource
     * @param int       $row
     * @param string    $field
     *
     * @return mixed
     */
    public static function result(Statement $resource, $row, $field = '')
    {
        if ($resource->rowCount() > 0) {
            $result = $resource->fetchAll(PDO::FETCH_BOTH);

            return $result[$row][$field];
        }
    }

    /**
     * @param string $query
     *
     * @return Statement
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function query($query)
    {
        $connection = self::getManager()->getConnection();

        if (api_get_setting('server_type') == 'test') {
            $result = $connection->executeQuery($query);
        } else {
            try {
                $result = $connection->executeQuery($query);
            } catch (Exception $e) {
                error_log($e->getMessage());
                api_not_allowed(false, get_lang('GeneralError'));

                exit;
            }
        }

        return $result;
    }

    /**
     * @param string $option
     *
     * @return int
     */
    public static function customOptionToDoctrineOption($option)
    {
        switch ($option) {
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
     *
     * @author Olivier Brouckaert
     * @param  Statement $result - the return value of the query
     * @param  string $option BOTH, ASSOC, or NUM
     *
     * @return array - the value returned by the query
     */
    public static function store_result(Statement $result, $option = 'BOTH')
    {
        return $result->fetchAll(self::customOptionToDoctrineOption($option));
    }

    /**
     * Database insert
     * @param string    $table_name
     * @param array     $attributes
     * @param bool      $show_query
     *
     * @return bool|int
     */
    public static function insert($table_name, $attributes, $show_query = false)
    {
        if (empty($attributes) || empty($table_name)) {
            return false;
        }

        $params = array_keys($attributes);

        if (!empty($params)) {
            $sql = 'INSERT INTO '.$table_name.' ('.implode(',', $params).')
                    VALUES (:'.implode(', :' ,$params).')';

            $statement = self::getManager()->getConnection()->prepare($sql);
            $result = $statement->execute($attributes);

            if ($show_query) {
                var_dump($sql);
                error_log($sql);
            }

            if ($result) {
                return self::getManager()->getConnection()->lastInsertId();
            }
        }

        return false;
    }

    /**
     * @param string $table_name use Database::get_main_table
     * @param array $attributes Values to updates
     * Example: $params['name'] = 'Julio'; $params['lastname'] = 'Montoya';
     * @param array $where_conditions where conditions i.e array('id = ?' =>'4')
     * @param bool $show_query
     *
     * @return bool|int
     */
    public static function update(
        $table_name,
        $attributes,
        $where_conditions = array(),
        $show_query = false
    ) {
        if (!empty($table_name) && !empty($attributes)) {
            $update_sql = '';
            //Cleaning attributes
            $count = 1;

            foreach ($attributes as $key => $value) {
                $update_sql .= "$key = :$key ";
                if ($count < count($attributes)) {
                    $update_sql.=', ';
                }
                $count++;
            }

            if (!empty($update_sql)) {
                //Parsing and cleaning the where conditions
                $where_return = self::parse_where_conditions($where_conditions);

                $sql = "UPDATE $table_name SET $update_sql $where_return ";

                $statement = self::getManager()->getConnection()->prepare($sql);
                $result = $statement->execute($attributes);

                if ($show_query) {
                    var_dump($sql);
                }

                if ($result) {

                    return $statement->rowCount();
                }
            }
        }

        return false;
    }

    /**
     * Experimental useful database finder
     * @todo lot of stuff to do here
     * @todo known issues, it doesn't work when using LIKE conditions
     * @example array('where'=> array('course_code LIKE "?%"'))
     * @example array('where'=> array('type = ? AND category = ?' => array('setting', 'Plugins'))
     * @example array('where'=> array('name = "Julio" AND lastname = "montoya"'))
     */
    public static function select($columns, $table_name, $conditions = array(), $type_result = 'all', $option = 'ASSOC')
    {
        $conditions = self::parse_conditions($conditions);

        //@todo we could do a describe here to check the columns ...
        if (is_array($columns)) {
            $clean_columns = implode(',', $columns);
        } else {
            if ($columns == '*') {
                $clean_columns = '*';
            } else {
                $clean_columns = (string)$columns;
            }
        }

        $sql    = "SELECT $clean_columns FROM $table_name $conditions";
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
     * @todo known issues, it doesn't work when using
     * LIKE conditions example: array('where'=>array('course_code LIKE "?%"'))
     * @param   array $conditions
     */
    public static function parse_conditions($conditions)
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
                            foreach($value_array as $item) {
                                $item = Database::escape_string($item);
                                $clean_values[]= $item;
                            }
                        } else {
                            $value_array = Database::escape_string($value_array);
                            $clean_values = $value_array;
                        }

                        if (!empty($condition) && $clean_values != '') {
                            $condition = str_replace('%',"'@percentage@'", $condition); //replace "%"
                            $condition = str_replace("'?'","%s", $condition);
                            $condition = str_replace("?","%s", $condition);

                            $condition = str_replace("@%s@","@-@", $condition);
                            $condition = str_replace("%s","'%s'", $condition);
                            $condition = str_replace("@-@","@%s@", $condition);

                            // Treat conditions as string
                            $condition = vsprintf($condition, $clean_values);
                            $condition = str_replace('@percentage@','%', $condition); //replace "%"
                            $where_return .= $condition;
                        }
                    }

                    if (!empty($where_return)) {
                        $return_value = " WHERE $where_return" ;
                    }
                    break;
                case 'order':
                    $order_array = $condition_data;

                    if (!empty($order_array)) {
                        // 'order' => 'id desc, name desc'
                        $order_array = self::escape_string($order_array, null, false);
                        $new_order_array = explode(',', $order_array);
                        $temp_value = array();

                        foreach($new_order_array as $element) {
                            $element = explode(' ', $element);
                            $element = array_filter($element);
                            $element = array_values($element);

                            if (!empty($element[1])) {
                                $element[1] = strtolower($element[1]);
                                $order = 'DESC';
                                if (in_array($element[1], array('desc', 'asc'))) {
                                    $order = $element[1];
                                }
                                $temp_value[]= $element[0].' '.$order.' ';
                            } else {
                                //by default DESC
                                $temp_value[]= $element[0].' DESC ';
                            }
                        }
                        if (!empty($temp_value)) {
                            $return_value .= ' ORDER BY '.implode(', ', $temp_value);
                        } else {
                            //$return_value .= '';
                        }
                    }
                    break;
                case 'limit':
                    $limit_array = explode(',', $condition_data);
                    if (!empty($limit_array)) {
                        if (count($limit_array) > 1) {
                            $return_value .= ' LIMIT '.intval($limit_array[0]).' , '.intval($limit_array[1]);
                        }  else {
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
     *
     * @return string
     */
    public static function parse_where_conditions($conditions)
    {
        return self::parse_conditions(array('where' => $conditions));
    }

    /**
     * @param string $table_name
     * @param array  $where_conditions
     * @param bool   $show_query
     *
     * @return int
     */
    public static function delete($table_name, $where_conditions, $show_query = false)
    {
        $where_return = self::parse_where_conditions($where_conditions);
        $sql    = "DELETE FROM $table_name $where_return ";
        if ($show_query) { echo $sql; echo '<br />'; }
        $result = self::query($sql);
        $affected_rows = self::affected_rows($result);
        //@todo should return affected_rows for
        return $affected_rows;
    }

    /**
     * Get Doctrine configuration
     * @param string $path
     *
     * @return \Doctrine\ORM\Configuration
     */
    public static function getDoctrineConfig($path)
    {
        $isDevMode = false;
        $isSimpleMode = false; // related to annotations @Entity
        $cache = null;
        $path = !empty($path) ? $path : api_get_path(SYS_PATH);

        $paths = array(
            $path.'src/Chamilo/CoreBundle/Entity',
            $path.'src/Chamilo/UserBundle/Entity',
            $path.'src/Chamilo/CourseBundle/Entity'
        );

        $proxyDir = $path.'app/cache/';

        return \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
            $paths,
            $isDevMode,
            $proxyDir,
            $cache,
            $isSimpleMode
        );
    }
}
