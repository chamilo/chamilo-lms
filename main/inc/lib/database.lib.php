<?php
/* For licensing terms, see /license.txt */

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Debug\ExceptionHandler;

/**
 * Class Database.
 */
class Database
{
    /**
     * @var EntityManager
     */
    private static $em;
    private static $connection;

    /**
     * Only used by the installer.
     *
     * @param array  $params
     * @param string $entityRootPath
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function connect(
        $params = [],
        $entityRootPath = ''
    ) {
        $config = self::getDoctrineConfig($entityRootPath);
        $config->setAutoGenerateProxyClasses(true);

        $config->setEntityNamespaces(
            [
                'ChamiloUserBundle' => 'Chamilo\UserBundle\Entity',
                'ChamiloCoreBundle' => 'Chamilo\CoreBundle\Entity',
                'ChamiloCourseBundle' => 'Chamilo\CourseBundle\Entity',
                'ChamiloSkillBundle' => 'Chamilo\SkillBundle\Entity',
                'ChamiloTicketBundle' => 'Chamilo\TicketBundle\Entity',
                'ChamiloPluginBundle' => 'Chamilo\PluginBundle\Entity',
            ]
        );

        $params['charset'] = 'utf8';
        $entityManager = EntityManager::create($params, $config);
        $connection = $entityManager->getConnection();

        $sysPath = !empty($sysPath) ? $sysPath : api_get_path(SYS_PATH);
        AnnotationRegistry::registerFile(
            $sysPath."vendor/symfony/doctrine-bridge/Validator/Constraints/UniqueEntity.php"
        );

        // Registering gedmo extensions
        AnnotationRegistry::registerAutoloadNamespace(
            'Gedmo\Mapping\Annotation',
            $sysPath."vendor/gedmo/doctrine-extensions/lib"
        );

        $this->setConnection($connection);
        $this->setManager($entityManager);
    }

    /**
     * @param EntityManager $em
     */
    public static function setManager($em)
    {
        self::$em = $em;
    }

    /**
     * @param Connection $connection
     */
    public static function setConnection(Connection $connection)
    {
        self::$connection = $connection;
    }

    /**
     * @return Connection
     */
    public static function getConnection()
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
     * Get main table.
     *
     * @param string $table
     *
     * @return string
     */
    public static function get_main_table($table)
    {
        return $table;
    }

    /**
     * Get course table.
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
     * Counts the number of rows in a table.
     *
     * @param string $table The table of which the rows should be counted
     *
     * @return int the number of rows in the given table
     *
     * @deprecated
     */
    public static function count_rows($table)
    {
        $obj = self::fetch_object(self::query("SELECT COUNT(*) AS n FROM $table"));

        return $obj->n;
    }

    /**
     * Returns the number of affected rows in the last database operation.
     *
     * @param Statement $result
     *
     * @return int
     */
    public static function affected_rows(Statement $result)
    {
        return $result->rowCount();
    }

    /**
     * Escapes a string to insert into the database as text.
     *
     * @param string $string
     *
     * @return string
     */
    public static function escape_string($string)
    {
        $string = self::getManager()->getConnection()->quote($string);
        // The quote method from PDO also adds quotes around the string, which
        // is not how the legacy mysql_real_escape_string() was used in
        // Chamilo, so we need to remove the quotes around. Using trim will
        // remove more than one quote if they are sequenced, generating
        // broken queries and SQL injection risks
        return substr($string, 1, -1);
    }

    /**
     * Gets the array from a SQL result (as returned by Database::query).
     *
     * @param Statement $result
     * @param string    $option Optional: "ASSOC","NUM" or "BOTH"
     *
     * @return array|mixed
     */
    public static function fetch_array(Statement $result, $option = 'BOTH')
    {
        if ($result === false) {
            return [];
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
     * (as returned by Database::query) in an object form.
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
     * help achieving database independence.
     *
     * @param Statement $result
     *
     * @return mixed
     */
    public static function fetch_row(Statement $result)
    {
        if ($result === false) {
            return [];
        }

        return $result->fetch(PDO::FETCH_NUM);
    }

    /**
     * Gets the ID of the last item inserted into the database.
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
        if ($result === false) {
            return 0;
        }

        return $result->rowCount();
    }

    /**
     * Acts as the relative *_result() function of most DB drivers and fetches a
     * specific line and a field.
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

        return false;
    }

    /**
     * @param string $query
     *
     * @return Statement
     */
    public static function query($query)
    {
        $connection = self::getManager()->getConnection();
        $result = null;
        try {
            $result = $connection->executeQuery($query);
        } catch (Exception $e) {
            self::handleError($e);
        }

        return $result;
    }

    /**
     * @param Exception $e
     */
    public static function handleError($e)
    {
        $debug = api_get_setting('server_type') == 'test';
        if ($debug) {
            // We use Symfony exception handler for better error information
            $handler = new ExceptionHandler();
            $handler->handle($e);
            exit;
        } else {
            error_log($e->getMessage());
            api_not_allowed(false, get_lang('An error has occured. Please contact your system administrator.'));
            exit;
        }
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
     *
     * @param Statement $result - the return value of the query
     * @param string    $option BOTH, ASSOC, or NUM
     *
     * @return array - the value returned by the query
     */
    public static function store_result(Statement $result, $option = 'BOTH')
    {
        return $result->fetchAll(self::customOptionToDoctrineOption($option));
    }

    /**
     * Database insert.
     *
     * @param string $table_name
     * @param array  $attributes
     * @param bool   $show_query
     *
     * @return false|int
     */
    public static function insert($table_name, $attributes, $show_query = false)
    {
        if (empty($attributes) || empty($table_name)) {
            return false;
        }

        $params = array_keys($attributes);

        if (!empty($params)) {
            $sql = 'INSERT INTO '.$table_name.' ('.implode(',', $params).')
                    VALUES (:'.implode(', :', $params).')';

            if ($show_query) {
                var_dump($sql);
                error_log($sql);
            }

            $result = false;
            try {
                $statement = self::getConnection()->prepare($sql);
                $result = $statement->execute($attributes);
            } catch (Exception $e) {
                self::handleError($e);
            }

            if ($result) {
                return (int) self::getManager()->getConnection()->lastInsertId();
            }
        }

        return false;
    }

    /**
     * @param string $tableName       use Database::get_main_table
     * @param array  $attributes      Values to updates
     *                                Example: $params['name'] = 'Julio'; $params['lastname'] = 'Montoya';
     * @param array  $whereConditions where conditions i.e array('id = ?' =>'4')
     * @param bool   $showQuery
     *
     * @return bool|int
     */
    public static function update(
        $tableName,
        $attributes,
        $whereConditions = [],
        $showQuery = false
    ) {
        if (!empty($tableName) && !empty($attributes)) {
            $updateSql = '';
            $count = 1;

            foreach ($attributes as $key => $value) {
                if ($showQuery) {
                    echo $key.': '.$value.PHP_EOL;
                }
                $updateSql .= "$key = :$key ";
                if ($count < count($attributes)) {
                    $updateSql .= ', ';
                }
                $count++;
            }

            if (!empty($updateSql)) {
                // Parsing and cleaning the where conditions
                $whereReturn = self::parse_where_conditions($whereConditions);
                $sql = "UPDATE $tableName SET $updateSql $whereReturn ";

                try {
                    $statement = self::getManager()->getConnection()->prepare($sql);
                    $result = $statement->execute($attributes);
                } catch (Exception $e) {
                    self::handleError($e);
                }

                if ($showQuery) {
                    var_dump($sql);
                    var_dump($attributes);
                    var_dump($whereConditions);
                }

                if ($result && $statement) {
                    return $statement->rowCount();
                }
            }
        }

        return false;
    }

    /**
     * Experimental useful database finder.
     *
     * @todo lot of stuff to do here
     * @todo known issues, it doesn't work when using LIKE conditions
     *
     * @example array('where'=> array('course_code LIKE "?%"'))
     * @example array('where'=> array('type = ? AND category = ?' => array('setting', 'Plugins'))
     * @example array('where'=> array('name = "Julio" AND lastname = "montoya"'))
     *
     * @param array  $columns
     * @param string $table_name
     * @param array  $conditions
     * @param string $type_result
     * @param string $option
     * @param bool   $debug
     *
     * @return array
     */
    public static function select(
        $columns,
        $table_name,
        $conditions = [],
        $type_result = 'all',
        $option = 'ASSOC',
        $debug = false
    ) {
        $conditions = self::parse_conditions($conditions);

        //@todo we could do a describe here to check the columns ...
        if (is_array($columns)) {
            $clean_columns = implode(',', $columns);
        } else {
            if ($columns == '*') {
                $clean_columns = '*';
            } else {
                $clean_columns = (string) $columns;
            }
        }

        $sql = "SELECT $clean_columns FROM $table_name $conditions";
        if ($debug) {
            var_dump($sql);
        }
        $result = self::query($sql);
        $array = [];

        if ($type_result === 'all') {
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
     * Parses WHERE/ORDER conditions i.e array('where'=>array('id = ?' =>'4'), 'order'=>'id DESC').
     *
     * @todo known issues, it doesn't work when using
     * LIKE conditions example: array('where'=>array('course_code LIKE "?%"'))
     *
     * @param array $conditions
     *
     * @return string Partial SQL string to add to longer query
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
                            $clean_values = [];
                            foreach ($value_array as $item) {
                                $item = self::escape_string($item);
                                $clean_values[] = $item;
                            }
                        } else {
                            $value_array = self::escape_string($value_array);
                            $clean_values = $value_array;
                        }

                        if (!empty($condition) && $clean_values != '') {
                            $condition = str_replace('%', "'@percentage@'", $condition); //replace "%"
                            $condition = str_replace("'?'", "%s", $condition);
                            $condition = str_replace("?", "%s", $condition);

                            $condition = str_replace("@%s@", "@-@", $condition);
                            $condition = str_replace("%s", "'%s'", $condition);
                            $condition = str_replace("@-@", "@%s@", $condition);

                            // Treat conditions as string
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
                        $order_array = self::escape_string($order_array, null, false);
                        $new_order_array = explode(',', $order_array);
                        $temp_value = [];

                        foreach ($new_order_array as $element) {
                            $element = explode(' ', $element);
                            $element = array_filter($element);
                            $element = array_values($element);

                            if (!empty($element[1])) {
                                $element[1] = strtolower($element[1]);
                                $order = 'DESC';
                                if (in_array($element[1], ['desc', 'asc'])) {
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
     *
     * @return string
     */
    public static function parse_where_conditions($conditions)
    {
        return self::parse_conditions(['where' => $conditions]);
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
     * Get Doctrine configuration.
     *
     * @param string $path
     *
     * @return \Doctrine\ORM\Configuration
     */
    public static function getDoctrineConfig($path)
    {
        $isDevMode = true; // Forces doctrine to use ArrayCache instead of apc/xcache/memcache/redis
        $isSimpleMode = false; // related to annotations @Entity
        $cache = null;
        $path = !empty($path) ? $path : api_get_path(SYS_PATH);

        $paths = [
            //$path.'src/Chamilo/ClassificationBundle/Entity',
            //$path.'src/Chamilo/MediaBundle/Entity',
            //$path.'src/Chamilo/PageBundle/Entity',
            $path.'src/Chamilo/CoreBundle/Entity',
            $path.'src/Chamilo/UserBundle/Entity',
            $path.'src/Chamilo/CourseBundle/Entity',
            $path.'src/Chamilo/TicketBundle/Entity',
            $path.'src/Chamilo/SkillBundle/Entity',
            $path.'src/Chamilo/PluginBundle/Entity',
            //$path.'vendor/sonata-project/user-bundle/Entity',
            //$path.'vendor/sonata-project/user-bundle/Model',
            //$path.'vendor/friendsofsymfony/user-bundle/FOS/UserBundle/Entity',
        ];

        $proxyDir = $path.'var/cache/';

        $config = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
            $paths,
            $isDevMode,
            $proxyDir,
            $cache,
            $isSimpleMode
        );

        return $config;
    }

    /**
     * @param string $table
     *
     * @return bool
     */
    public static function tableExists($table)
    {
        return self::getManager()->getConnection()->getSchemaManager()->tablesExist($table);
    }

    /**
     * @param string $table
     *
     * @return \Doctrine\DBAL\Schema\Column[]
     */
    public static function listTableColumns($table)
    {
        return self::getManager()->getConnection()->getSchemaManager()->listTableColumns($table);
    }
}
