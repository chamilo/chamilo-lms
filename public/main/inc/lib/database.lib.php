<?php

/* For licensing terms, see /license.txt */

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class Database
{
    private static EntityManager $em;
    private static Connection $connection;

    /**
     * Setup doctrine only for the installation.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\DBAL\Exception
     */
    public function connect(array $params = [], string $entityRootPath = '')
    {
        $config = self::getDoctrineConfig($entityRootPath);
        $config->setAutoGenerateProxyClasses(true);
        $config->setEntityNamespaces(
            [
                'ChamiloCoreBundle' => 'Chamilo\CoreBundle\Entity',
                'ChamiloCourseBundle' => 'Chamilo\CourseBundle\Entity',
            ]
        );

        $params['charset'] = 'utf8';
        $sysPath = api_get_path(SYMFONY_SYS_PATH);

        // standard annotation reader
        $annotationReader = new Doctrine\Common\Annotations\AnnotationReader();
        $cachedAnnotationReader = new PsrCachedReader(
            $annotationReader,
            new ArrayAdapter()
        );

        $evm = new EventManager();
        $timestampableListener = new Gedmo\Timestampable\TimestampableListener();
        $timestampableListener->setAnnotationReader($cachedAnnotationReader);
        $evm->addEventSubscriber($timestampableListener);

        $driverChain = new \Doctrine\Persistence\Mapping\Driver\MappingDriverChain();
        // load superclass metadata mapping only, into driver chain
        // also registers Gedmo annotations.NOTE: you can personalize it
        Gedmo\DoctrineExtensions::registerAbstractMappingIntoDriverChainORM(
            $driverChain, // our metadata driver chain, to hook into
            $cachedAnnotationReader // our cached annotation reader
        );

        AnnotationRegistry::registerLoader(
            function ($class) use ($sysPath) {
                $file = str_replace("\\", DIRECTORY_SEPARATOR, $class).".php";
                $file = str_replace('Symfony/Component/Validator', '', $file);
                $file = str_replace('Symfony\Component\Validator', '', $file);
                $file = str_replace('Symfony/Component/Serializer', '', $file);

                $fileToInclude = $sysPath.'vendor/symfony/validator/'.$file;

                if (file_exists($fileToInclude)) {
                    // file exists makes sure that the loader fails silently
                    require_once $fileToInclude;

                    return true;
                }

                $fileToInclude = $sysPath.'vendor/symfony/validator/Constraints/'.$file;
                if (file_exists($fileToInclude)) {
                    // file exists makes sure that the loader fails silently
                    require_once $fileToInclude;

                    return true;
                }

                $fileToInclude = $sysPath.'vendor/symfony/serializer/'.$file;

                if (file_exists($fileToInclude)) {
                    // file exists makes sure that the loader fails silently
                    require_once $fileToInclude;

                    return true;
                }
            }
        );

        AnnotationRegistry::registerFile(
            $sysPath.'vendor/api-platform/core/src/Annotation/ApiResource.php'
        );
        AnnotationRegistry::registerFile(
            $sysPath.'vendor/api-platform/core/src/Annotation/ApiFilter.php'
        );
        AnnotationRegistry::registerFile(
            $sysPath.'vendor/api-platform/core/src/Annotation/ApiProperty.php'
        );
        AnnotationRegistry::registerFile(
            $sysPath.'vendor/api-platform/core/src/Annotation/ApiSubresource.php'
        );

        $entityManager = EntityManager::create($params, $config, $evm);

        if (false === Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }

        $connection = $entityManager->getConnection();
        AnnotationRegistry::registerFile(
            $sysPath.'vendor/symfony/doctrine-bridge/Validator/Constraints/UniqueEntity.php'
        );

        $this->setConnection($connection);
        $this->setManager($entityManager);
    }

    public static function setManager(EntityManager $em)
    {
        self::$em = $em;
    }

    public static function setConnection(Connection $connection)
    {
        self::$connection = $connection;
    }

    public static function getConnection(): Connection
    {
        return self::$connection;
    }

    public static function getManager(): EntityManager
    {
        return self::$em;
    }

    /**
     * Returns the name of the main database.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function get_main_database(): bool|string|null
    {
        return self::getManager()->getConnection()->getDatabase();
    }

    /**
     * Get main table.
     */
    public static function get_main_table(string $table): string
    {
        return $table;
    }

    /**
     * Get course table.
     */
    public static function get_course_table(string $table): string
    {
        return DB_COURSE_PREFIX.$table;
    }

    /**
     * Counts the number of rows in a table.
     *
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     *
     * @deprecated
     */
    public static function count_rows(string $table): int
    {
        $result = self::query("SELECT COUNT(*) AS n FROM $table");

        return (int) $result->fetchOne();
    }

    /**
     * Returns the number of affected rows in the last database operation.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function affected_rows(\Doctrine\DBAL\Result $result): int
    {
        return $result->rowCount();
    }

    /**
     * Escapes a string to insert into the database as text.
     */
    public static function escape_string(mixed $string): string
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
     * @throws \Doctrine\DBAL\Exception
     */
    public static function fetch_array(\Doctrine\DBAL\Result $result): mixed
    {
        $data = $result->fetchAssociative();

        if (empty($data)) {
            return [];
        }

        $i = 0;

        foreach ($data as $value) {
            $data[$i] = $value;
            $i++;
        }

        return $data;
    }

    /**
     * Gets an associative array from a SQL result (as returned by Database::query).
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function fetch_assoc(\Doctrine\DBAL\Result $result): array|bool
    {
        return $result->fetchAssociative();
    }

    /**
     * Gets the next row of the result of the SQL query
     * (as returned by Database::query) in an object form.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function fetch_object(\Doctrine\DBAL\Result $result): stdClass
    {
        $data = $result->fetchAssociative();

        $object = new stdClass();

        foreach ($data as $key => $value) {
            $object->$key = $value;
        }

        return $object;
    }

    /**
     * Gets the array from a SQL result (as returned by Database::query)
     * help to achieve database independence.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function fetch_row(\Doctrine\DBAL\Result $result): array|bool
    {
        return $result->fetchNumeric();
    }

    /**
     * Gets the ID of the last item inserted into the database.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function insert_id(): int
    {
        return (int) self::getManager()->getConnection()->lastInsertId();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public static function num_rows(\Doctrine\DBAL\Result $result): int
    {
        return $result->rowCount();
    }

    /**
     * Acts as the relative *_result() function of most DB drivers and fetches a
     * specific line and a field.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function result(\Doctrine\DBAL\Result $resource, int $row, string $field = ''): mixed
    {
        if ($resource->rowCount() > 0) {
            $result = $resource->fetchAllAssociative();

            return $result[$row][$field];
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public static function query(string $query): ?\Doctrine\DBAL\Result
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
     * @throws Exception
     */
    public static function handleError(Exception $e)
    {
        $debug = 'test' === api_get_setting('server_type');
        if ($debug) {
            throw $e;
        } else {
            error_log($e->getMessage());
            api_not_allowed(false, get_lang('An error has occurred. Please contact your system administrator.'));
        }
    }

    /**
     * Stores a query result into an array.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function store_result(\Doctrine\DBAL\Result $result, $option = 'BOTH'): array
    {
        if ('NUM' === $option) {
            return $result->fetchAllNumeric();
        }

        return $result->fetchAllAssociative();
    }

    /**
     * Database insert.
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function insert(string $table_name, array $attributes, bool $show_query = false): bool|int
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

            try {
                self::getConnection()
                    ->prepare($sql)
                    ->executeQuery($attributes)
                ;
            } catch (Exception $e) {
                self::handleError($e);

                return false;
            }

            return (int) self::getManager()->getConnection()->lastInsertId();
        }

        return false;
    }

    /**
     * @param string $tableName       use Database::get_main_table
     * @param array  $attributes      Values to updates
     *                                Example: $params['name'] = 'Julio'; $params['lastname'] = 'Montoya';
     * @param array  $whereConditions where conditions i.e. array('id = ?' =>'4')
     * @param bool   $showQuery
     *
     * @throws Exception
     *
     * @return bool|int
     */
    public static function update(
        string $tableName,
        array $attributes,
        array $whereConditions = [],
        bool $showQuery = false
    ): bool|int {
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
                    $result = $statement->executeQuery($attributes);
                } catch (Exception $e) {
                    self::handleError($e);

                    return false;
                }

                if ($showQuery) {
                    var_dump($sql);
                    var_dump($attributes);
                    var_dump($whereConditions);
                }

                return $result->rowCount();
            }
        }

        return false;
    }

    /**
     * Experimental useful database finder.
     *
     * @throws Exception
     *
     * @todo    lot of stuff to do here
     * @todo    known issues, it doesn't work when using LIKE conditions
     *
     * @example ['where'=> ['course_code LIKE "?%"']]
     * @example ['where'=> ['type = ? AND category = ?' => ['setting', 'Plugins']]]
     * @example ['where'=> ['name = "Julio" AND lastname = "montoya"']]
     */
    public static function select(
        string|array $columns,
        string $table_name,
        array $conditions = [],
        string $type_result = 'all',
        string $option = 'ASSOC',
        bool $debug = false
    ): int|array {
        if ('count' === $type_result) {
            $conditions['LIMIT'] = null;
            $conditions['limit'] = null;
        }
        $conditions = self::parse_conditions($conditions);

        //@todo we could do a describe here to check the columns ...
        if (is_array($columns)) {
            $clean_columns = implode(',', $columns);
        } else {
            if ('*' === $columns) {
                $clean_columns = '*';
            } else {
                $clean_columns = (string) $columns;
            }
        }

        if ('count' === $type_result) {
            $clean_columns = ' count(*) count ';
        }
        $sql = "SELECT $clean_columns FROM $table_name $conditions";
        if ($debug) {
            var_dump($sql);
        }
        $result = self::query($sql);
        if ('count' === $type_result) {
            $row = self::fetch_array($result);
            if ($row) {
                return (int) $row['count'];
            }

            return 0;
        }
        $array = [];

        if ('all' === $type_result) {
            while ($row = self::fetch_array($result)) {
                if (isset($row['id'])) {
                    $array[$row['id']] = $row;
                } else {
                    $array[] = $row;
                }
            }
        } else {
            $array = self::fetch_array($result);
        }

        return $array;
    }

    /**
     * Parses WHERE/ORDER conditions i.e. array('where'=>array('id = ?' =>'4'), 'order'=>'id DESC').
     *
     * @todo known issues, it doesn't work when using
     * LIKE conditions example: array('where'=>array('course_code LIKE "?%"'))
     */
    public static function parse_conditions(array $conditions): string
    {
        if (empty($conditions)) {
            return '';
        }
        $return_value = $where_return = '';
        foreach ($conditions as $type_condition => $condition_data) {
            if (false == $condition_data) {
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
                            $clean_values = [$value_array];
                        }

                        if (!empty($condition) && '' != $clean_values) {
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
                        $order_array = self::escape_string($order_array);
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
                                $temp_value[] = ' `'.$element[0].'` '.$order.' ';
                            } else {
                                //by default DESC
                                $temp_value[] = ' `'.$element[0].'` DESC ';
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

    public static function parse_where_conditions(array $conditions): string
    {
        return self::parse_conditions(['where' => $conditions]);
    }

    /**
     * @throws Exception
     */
    public static function delete(string $tableName, array $where_conditions, bool $show_query = false): int
    {
        $where_return = self::parse_where_conditions($where_conditions);
        $sql = "DELETE FROM $tableName $where_return ";
        if ($show_query) {
            echo $sql;
            echo '<br />';
        }
        $result = self::query($sql);

        return self::affected_rows($result);
    }

    /**
     * Get Doctrine configuration.
     */
    public static function getDoctrineConfig(string $path): Configuration
    {
        $cache = null;
        $path = !empty($path) ? $path : api_get_path(SYMFONY_SYS_PATH);

        $paths = [
            $path.'src/Chamilo/CoreBundle/Entity',
            $path.'src/Chamilo/CourseBundle/Entity',
        ];

        $proxyDir = $path.'var/cache/';

        return \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
            $paths,
            true, // Forces doctrine to use ArrayCache instead of apc/xcache/memcache/redis
            $proxyDir,
            $cache,
            false // related to annotations @Entity
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public static function tableExists(string $table): bool
    {
        return self::getManager()->getConnection()->createSchemaManager()->tablesExist($table);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public static function listTableColumns(string $table): array
    {
        return self::getManager()->getConnection()->createSchemaManager()->listTableColumns($table);
    }

    public static function escapeField($field): string
    {
        return self::escape_string(preg_replace("/[^a-zA-Z0-9_.]/", '', $field));
    }
}
