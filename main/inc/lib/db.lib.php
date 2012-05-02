<?php

/**
 * This is a draft 
 *  
 */
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;

function save($data)
{
    db::instance()->save($data);
}

class db
{

    /**
     * @return db 
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    private static function register_autoload()
    {
        static $has_run = false;
        if ($has_run) {
            return true;
        }
        require_once api_get_path(LIBRARY_PATH) . 'symfony/Doctrine/ORM/Tools/Setup.php';
        $directory = api_get_path(LIBRARY_PATH) . 'symfony';
        //Doctrine\ORM\Tools\Setup::registerAutoloadDirectory($lib);

        if (!class_exists('Doctrine\Common\ClassLoader', false)) {
            require_once $directory . '/doctrine/Common/ClassLoader.php';
        }

        $loader = new Doctrine\Common\ClassLoader('Doctrine', $directory);
        $loader->register();

        $loader = new Doctrine\Common\ClassLoader('Symfony\Component', $directory);
        $loader->register();

        $has_run = true;
    }

    protected $config;
    protected $em;

    protected function __construct()
    {
        self::register_autoload();
        $connection_parameters = $this->get_connection_parameters();
        $this->config = Setup::createYAMLMetadataConfiguration(array(api_get_path(LIBRARY_PATH) . 'symfony/app_obj/metadata'), true);
        $this->em = EntityManager::create($connection_parameters, $this->config);
    }

    public function save($data)
    {
        $em = $this->em;
        $em->persist($data);
    }

    function test()
    {
        /*      $classLoader = new ClassLoader('Doctrine', api_get_path(LIBRARY_PATH).'symfony');
          $classLoader->register(); */
        /* $config = new Doctrine\DBAL\Configuration();                

          $conn = DriverManager::getConnection($connectionParams, $config);

          $sql = "SELECT * FROM user";
          $stmt = $conn->query($sql); // Simple, but has several drawbacks
         */

//        $lib = api_get_path(LIBRARY_PATH) . 'symfony';
//        Doctrine\ORM\Tools\Setup::registerAutoloadDirectory($lib);
//
//        $config = new Configuration;
//        $applicationMode = 'development';
//
//        if ($applicationMode == "development") {
//            $cache = new \Doctrine\Common\Cache\ArrayCache;
//        } else {
//            $cache = new \Doctrine\Common\Cache\ApcCache;
//        }
//        $config->setMetadataCacheImpl($cache);
//        $driverImpl = $config->newDefaultAnnotationDriver(api_get_path(LIBRARY_PATH) . 'symfony/app_obj/entities');
//        $config->setMetadataDriverImpl($driverImpl);
//        $config->setQueryCacheImpl($cache);
//        $config->setProxyDir(api_get_path(LIBRARY_PATH) . 'proxies');
//        $config->setProxyNamespace('App\Proxies');
//
//        if ($applicationMode == "development") {
//            $config->setAutoGenerateProxyClasses(true);
//        } else {
//            $config->setAutoGenerateProxyClasses(false);
//        }

        require_once api_get_path(LIBRARY_PATH) . 'symfony/app_obj/entities/user.php';
        $user = new User();
        $user->setUsername('salut');
        $em->persist($user);
        $em->flush();
        echo "Created User with ID " . $user->getId() . "\n";
    }

    /**
     *
     * @return EntityManager The created EntityManager.
     */
    public function em()
    {
        return $this->em;
    }

    /**
     * Pathes?? do we put everything in one place or in several places?
     * @return type 
     */
    public function get_model_path()
    {
        $result = __DIR__ . '/../model';
        $result = realpath($result);
        return $result;
    }

    public function is_production()
    {
        return Chamilo::is_production_server();
    }

    public function is_dev()
    {
        return !Chamilo::is_production_server();
    }

    /**
     * Reverse engineering of the model from the database structure.
     * Write result to the entity folder
     * 
     * WARNING THIS WILL OVERWRITE EXISTING MODEL.
     * 
     * @return boolean 
     */
    public function generate_model()
    {
        if (!$this->is_dev()) {
            return false;
        }

        $model_path = $this->get_model_path();

        $connection_parameters = $this->get_connection_parameters();
        $connection = Doctrine\DBAL\DriverManager::getConnection($connection_parameters);
        $platform = $connection->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');
        $platform->registerDoctrineTypeMapping('set', 'string');

        $config = Setup::createConfiguration($this->is_dev());
        $config->setMetadataDriverImpl(new Doctrine\ORM\Mapping\Driver\DatabaseDriver(new Doctrine\DBAL\Schema\MySqlSchemaManager($connection)));

        $em = EntityManager::create($connection, $config);

        $cmf = new Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($em);
        $metadatas = $cmf->getAllMetadata();

        foreach ($metadatas as $metadata) {
            echo sprintf('Processing entity "<info>%s</info>"', $metadata->name) . '<br/>';
        }

        $generator = new EntityGenerator();
        $generator->setGenerateAnnotations(false);
        $generator->setGenerateStubMethods(true);
        $generator->setRegenerateEntityIfExists(true);
        $generator->setUpdateEntityIfExists(false);
        $generator->setExtension('.class.php');
        $generator->setNumSpaces(4);

        // Generating Entities
        $generator->generate($metadatas, $model_path);

        $exporter = new \Doctrine\ORM\Tools\Export\Driver\YamlExporter();
        $exporter->setOutputDir($model_path . '/mapping');
        $exporter->setMetadata($metadatas);
        $exporter->export();
    }

    public function update_schema($paths, $save_mode)
    {
        $is_dev = $this->is_dev();
        
        $ext = explode('.', $path);
        $ext = end($ext);
        if ($ext == 'yml') {
            $config = Setup::createYAMLMetadataConfiguration($paths, $is_dev);
        } else if ($ext == 'xml') {
            $config = Setup::createXMLMetadataConfiguration($paths, $is_dev);
        } else if ($ext == 'php') {
            $config = Setup::createAnnotationMetadataConfiguration($paths, $is_dev);
        } else {
            return false;
        }
        
        $em = $this->em;

        $cmf = new Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($em);
        $classes = $cmf->getAllMetadata();

        $tool = new Doctrine\ORM\Tools\SchemaTool($em);
        return $tool->updateSchema($classes, $save_mode);
    }

    protected function get_config()
    {
        return $this->config;
    }

    protected function get_connection_parameters()
    {
        global $_configuration;
        $result = array(
            'dbname' => $_configuration['main_database'],
            'user' => $_configuration['db_user'],
            'password' => $_configuration['db_password'],
            'host' => $_configuration['db_host'],
            'driver' => 'pdo_mysql',
        );
        return $result;
    }

}

