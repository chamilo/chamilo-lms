<?php

/**
 * This is a draft 
 *  
 */
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Tools\EntityGenerator;
use Tools\EntityRepositoryGenerator;
use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

function save($data)
{
    db::instance()->save($data);
}

/**
 *
 * @license see /license.txt 
 */
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
        $this->config = Setup::createYAMLMetadataConfiguration(array($this->get_entity_path() . '/mapping'), true);
        $this->em = EntityManager::create($connection_parameters, $this->config);
    }

    public function save($data)
    {
        $em = $this->em;
        $em->persist($data);
        $em->flush();
    }

    public function remove($data)
    {
        $em = $this->em;
        $em->remove($data);
        $em->flush();
    }

    public function flush($data = null)
    {
        $em = $this->em;
        $em->flush($data);
    }

    function test()
    {
        $_SESSION['_real_cid'] = 2;
        
 //       $repo = Entity\User::repository();
//        $user = Entity\User::create();
//        $user->set_username('salut1');
//        $user->set_password('salut');
//        $user->set_status(1);
//        $user->set_chatcall_user_id(1);
//        $user->set_chatcall_date(new DateTime());
//        $user->set_chatcall_text('');
//        $user->set_registration_date(new DateTime());
//        $user->set_expiration_date(new DateTime());
//        $user->set_active(true);
//        $user->set_hr_dept_id(0);
//        $em = $this->em();
//        $em->persist($user);
//        $em->flush();
//        echo "Created User with ID " . $user->get_user_id() . "\n";
//        $user = $repo->find(3);
//        $this->remove($user);
//        $this->flush();


        $doc = \Entity\Document::create();
        $doc->set_path('path');
        $doc->set_title('title');
        //$doc->set_c_id(1);
        $doc->set_comment('comment');
        $doc->set_size(0);
        $doc->set_session_id(0);
        $doc->set_filetype('dd');
        $doc->set_readonly(false);

        //$repo = \Entity\Document::repository();
        //$id = $repo->next_id($doc);
        //echo "next id: $id <br/>";


        //$doc->set_id($id);
        $this->save($doc);
        $this->flush();
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
     *
     * @param string $entity_name
     * @return EntityRepository 
     */
    public function get_repository($entity_name)
    {
        return $this->em()->getRepository($entity_name);
    }

    /**
     * 
     * @return type 
     */
    public function get_entity_path()
    {
        $result = __DIR__ . '/../entity';
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

        $root = $this->get_entity_path();

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


        $repo_factory = new EntityRepositoryGenerator();

        $course = null;
        foreach ($metadatas as $metadata) {
            $n = strtolower($metadata->name);
            if ($n == 'course') {
                $course = $metadata;
                break;
            }
        }

        foreach ($metadatas as $metadata) {
            echo sprintf('Processing entity "<info>%s</info>"', $metadata->name) . '<br/>';

            foreach ($metadata->identifier as $key => $value) {
                //$mapping = $metadata->fieldMappings[$value];                
                $metadata->identifier[$key] = Inflector::tableize($value);
            }

            $fields = array();
            foreach ($metadata->fieldMappings as $fieldMapping) {
                $name = Inflector::tableize($fieldMapping['fieldName']);
                $fieldMapping['fieldName'] = $name;
                $fields[$name] = $fieldMapping;
            }
            $metadata->fieldMappings = $fields;

            $n = $metadata->name;
            if ($n == 'CDocument') {
                $i = 1;
            }
            $name = $metadata->name;
            $name = Inflector::tableize($name);
            $is_course_table = (strpos($name, 'c_') === 0);
            if ($is_course_table) {
                $name = substr($name, 2, strlen($name) - 2);
            }
            //$metadata->namespace = 'Entity';
            $metadata->customRepositoryClassName = 'Entity\\Repository\\' . Inflector::classify($name) . 'Repository';
            //if(is_course_table){
            $metadata->name = 'Entity\\' . Inflector::classify($name);

            $metadata->lifecycleCallbacks['prePersist'] = array('before_save');

            //}
            //$metadata->rootEntityName = Inflector::classify($name);
            if ($is_course_table) {
                foreach ($metadata->fieldMappings as $mapping) {
                    $name = $mapping['columnName'];
                    $is_id = isset($mapping['id']) ? $mapping['id'] : false;
                    if ($name != 'c_id' && $is_id) {
                        
                    }
                }
            }
            if ($is_course_table) {
                $metadata->is_course_table = true;
//                $mapping = array();
//                $mapping['cascade'] = array();
//                $mapping['joinColumns'][0] = array('name' => 'c_id', 'referencedColumnName' => 'id');
//                $mapping['sourceToTargetKeyColumns']['c_id'] = 'id';
//                $mapping['joinColumnFieldNames']['c_id'] = 'c_id';
//                $mapping['targetToSourceKeyColumns']['id'] = 'c_id';
//                $mapping['id'] = 1;
//                $mapping['isOwningSide'] = 0;
//                $mapping['isCascadeRemove'] = 0;
//                $mapping['isCascadePersist'] = 0;
//                $mapping['isCascadeRefresh'] = 0;
//                $mapping['isCascadeMerge'] = 0;
//                $mapping['isCascadeDetach'] = 0;
//                $mapping['orphanRemoval'] = 0;
//                $mapping['type'] = ClassMetadataInfo::MANY_TO_ONE;
//                $mapping['fetch'] = ClassMetadataInfo::FETCH_LAZY;
//                $mapping['fieldName'] = 'course';
//                $mapping['targetEntity'] = 'Entity\\Course';
//                $mapping['sourceEntity'] = $metadata->name;
//
//                $metadata->associationMappings['course'] = $mapping;
//                $metadata->identifier['course'];
                
//                unset($metadata->identifier['c_id']);
//                unset($metadata->fieldMappings['c_id']);


//                $mapping = array();
//                $mapping['cascade'] = array();
//                $mapping['joinColumns'][0] = array('name' => 'id', 'referencedColumnName' => 'c_id');
//                $mapping['sourceToTargetKeyColumns']['id'] = 'c_id';
//                $mapping['joinColumnFieldNames']['id'] = 'id';
//                $mapping['targetToSourceKeyColumns']['c_id'] = 'id';
//                $mapping['id'] = 1;
//                $mapping['isOwningSide'] = 1;
//                $mapping['isCascadeRemove'] = 0;
//                $mapping['isCascadePersist'] = 0;
//                $mapping['isCascadeRefresh'] = 0;
//                $mapping['isCascadeMerge'] = 0;
//                $mapping['isCascadeDetach'] = 0;
//                $mapping['orphanRemoval'] = 0;
//                $mapping['type'] = ClassMetadataInfo::ONE_TO_MANY;
//                $mapping['fetch'] = ClassMetadataInfo::FETCH_LAZY;
//                $name = explode('\\' ,$metadata->name);
//                $name = end($name);
//                $name = Inflector::tableize($name);
//                $mapping['fieldName'] = $name;
//                $mapping['targetEntity'] = $metadata->name;
//                $mapping['sourceEntity'] = 'Entity\\Course';
//                $course->associationMappings[$name] = $mapping;
            }
            $metadata->class_to_extend = $is_course_table ? 'CourseEntity' : 'Entity';
            

            $repo_factory->writeEntityRepositoryClass($metadata->name, $root . '\\repository\\');
        }

        $generator = new EntityGenerator();
        
        $generator->setClassToExtend('Entity');
        $generator->setGenerateAnnotations(false);
        $generator->setGenerateStubMethods(true);
        $generator->setRegenerateEntityIfExists(false);
        $generator->setUpdateEntityIfExists(false);
        $generator->setBackupExisting(false);
        $generator->setExtension('.class.php');
        $generator->setNumSpaces(4);

        // Generating Entities
        $generator->generate($metadatas, $root);

        $exporter = new \Doctrine\ORM\Tools\Export\Driver\YamlExporter();
        $exporter->setOutputDir($root . '/mapping');
        foreach ($metadatas as $metadata) {
            echo $metadata->name . '<br/>';
            try {
                $exporter->setMetadata(array($metadata));
                $exporter->export();
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    public function update_schema($paths, $save_mode)
    {
//        $is_dev = $this->is_dev();
//
//        $ext = explode('.', $path);
//        $ext = end($ext);
//        if ($ext == 'yml') {
//            $config = Setup::createYAMLMetadataConfiguration($paths, $is_dev);
//        } else if ($ext == 'xml') {
//            $config = Setup::createXMLMetadataConfiguration($paths, $is_dev);
//        } else if ($ext == 'php') {
//            $config = Setup::createAnnotationMetadataConfiguration($paths, $is_dev);
//        } else {
//            return false;
//        }
//
//        $em = $this->em;
//
//        $cmf = new Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
//        $cmf->setEntityManager($em);
//        $classes = $cmf->getAllMetadata();
//
//        $tool = new Doctrine\ORM\Tools\SchemaTool($em);
//        return $tool->updateSchema($classes, $save_mode);
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

