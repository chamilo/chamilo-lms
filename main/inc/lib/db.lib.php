<?php
/**
 * This is a draft 
 *  
 */
require_once api_get_path(LIBRARY_PATH).'symfony/symfony/Component/ClassLoader/UniversalClassLoader.php';
use Doctrine\Common\ClassLoader;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Tools\Setup;

require_once api_get_path(LIBRARY_PATH).'symfony/Doctrine/Common/ClassLoader.php';
require_once api_get_path(LIBRARY_PATH).'symfony/Doctrine/ORM/Tools/Setup.php';

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\Configuration;


class db {
    
    function __construct() {
        global $_configuration;
        $connectionParams = array(
            'dbname'    => $_configuration['main_database'],
            'user'      => $_configuration['db_user'],
            'password'  => $_configuration['db_password'],
            'host'      => $_configuration['db_host'],
            'driver'    => 'pdo_mysql',
        );
        
  /*      $classLoader = new ClassLoader('Doctrine', api_get_path(LIBRARY_PATH).'symfony');
        $classLoader->register();*/
       /* $config = new Doctrine\DBAL\Configuration();                
        
        $conn = DriverManager::getConnection($connectionParams, $config);
        
        $sql = "SELECT * FROM user";
        $stmt = $conn->query($sql); // Simple, but has several drawbacks        
        */       
             
        $lib = api_get_path(LIBRARY_PATH).'symfony';
        Doctrine\ORM\Tools\Setup::registerAutoloadDirectory($lib);
        
        $config = new Configuration;
        $applicationMode = 'development';
        
        if ($applicationMode == "development") {
            $cache = new \Doctrine\Common\Cache\ArrayCache;
        } else {
            $cache = new \Doctrine\Common\Cache\ApcCache;
        }
        $config->setMetadataCacheImpl($cache);
        $driverImpl = $config->newDefaultAnnotationDriver(api_get_path(LIBRARY_PATH).'symfony/app_obj/entities');
        $config->setMetadataDriverImpl($driverImpl);
        $config->setQueryCacheImpl($cache);
        $config->setProxyDir(api_get_path(LIBRARY_PATH).'proxies');
        $config->setProxyNamespace('App\Proxies');        
        
        if ($applicationMode == "development") {
            $config->setAutoGenerateProxyClasses(true);
        } else {
            $config->setAutoGenerateProxyClasses(false);
        }
        
        $config = Setup::createYAMLMetadataConfiguration(array(api_get_path(LIBRARY_PATH).'symfony/app_obj/metadata'), true);
                
        $em = EntityManager::create($connectionParams, $config);
        
        require_once api_get_path(LIBRARY_PATH).'symfony/app_obj/entities/user.php';
        
        $user = new User();
        $user->setUsername('salut');
        $em->persist($user);
        $em->flush();
        echo "Created User with ID " . $user->getId() . "\n";
    }
}


