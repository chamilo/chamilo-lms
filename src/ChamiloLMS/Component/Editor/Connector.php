<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\Editor;

use Doctrine\ORM\EntityManager;
use \Entity\User;
use \Entity\Course;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Routing\Router;
use ChamiloLMS\Component\Editor\Driver\Driver;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Class elFinder Connector - editor + Chamilo repository
 * @package ChamiloLMS\Component\Editor
 */
class Connector
{
    /** @var Course */
    public $course;

    /** @var User */
    public $user;

    /** @var Translator */
    public $translator;

    /** @var Router */
    public $urlGenerator;
    /** @var SecurityContext */
    public $security;

    public $paths;

    public $entityManager;

    public $drivers = array();
    public $driverList = array();

    public function __construct(
        EntityManager $entityManager,
        array $paths,
        Router $urlGenerator,
        Translator $translator,
        SecurityContext $security,
        $user,
        $course = null
    ) {
        $this->entityManager = $entityManager;
        $this->paths = $paths;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->security = $security;
        $this->user = $user;
        $this->course = $course;
        $this->driverList = $this->getDefaultDriverList();
    }

    /**
     * @param Driver $driver
     */
    public function addDriver($driver)
    {
        if (!empty($driver)) {
            $this->drivers[$driver->getName()] = $driver;
        }
    }

    /**
     * @return array
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * @param string $driverName
     * @return Driver $driver
     */
    public function getDriver($driverName)
    {
        if (isset($this->drivers[$driverName])) {
            return $this->drivers[$driverName];
        }
        return null;
    }

    /**
     * @param bool $processDefaultValues
     * @return array
     */
    public function getRoots($processDefaultValues = true)
    {
        $roots = array();
        /** @var Driver $driver */
        $drivers = $this->getDrivers();
        foreach ($drivers as $driver) {
            if ($processDefaultValues) {
                $root = $driver->updateWithDefaultValues($driver->getConfiguration());
            }
            $roots[] = $root;
        }
        return $roots;
    }

    /**
     * @return array
     */
    public function getOperations()
    {
        //https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options-2.1
        $opts = array(
            //'debug' => true,
            'bind' => array(
                'upload rm' => array($this, 'manageCommands')
            )
        );

        foreach ($this->getDriverList() as $driverName) {
            $driverClass = $this->getDriverClass($driverName);
            /** @var Driver $driver */
            $driver = new $driverClass();
            $driver->setName($driverName);
            $driver->setConnector($this);
            $this->addDriver($driver);
        }

        $opts['roots'] = $this->getRoots();
        return $opts;
    }


    /**
     * Simple function to demonstrate how to control file access using "accessControl" callback.
     * This method will disable accessing files/folders starting from  '.' (dot)
     *
     * @param string $attr  attribute name (read|write|locked|hidden)
     * @param string $path  file path relative to volume root directory started with directory separator
     * @param string $data
     * @param string $volume
     * @return bool|null
     **/
    public function access($attr, $path, $data, $volume)
    {
    	return strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
    		? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
    		:  null;                                    // else elFinder decide it itself
    }

    /**
     * @return array
     */
    public function getDriverList()
    {
        return $this->driverList;
    }

    /**
     * Available driver list.
     * @return array
     */
    public function setDriverList($list)
    {
        $this->driverList = $list;
    }

    /**
     * Available driver list.
     * @return array
     */
    private function getDefaultDriverList()
    {
        return array(
            'CourseDriver',
            'CourseUserDriver',
            'HomeDriver',
            'PersonalDriver'
        );
    }

    /**
     * @param string $cmd
     * @param array $result
     * @param array $args
     * @param Finder $elFinder
     */
    public function manageCommands($cmd, $result, $args, $elFinder)
    {
        $cmd = ucfirst($cmd);
        $cmd = 'after'.$cmd;
/*
        if (isset($args['target'])) {
            $driverName = $elFinder->getVolumeDriverNameByTarget($args['target']);
        }

        if (isset($args['targets'])) {
            foreach ($args['targets'] as $target) {
                $driverName = $elFinder->getVolumeDriverNameByTarget($target);
                break;
            }
        }
*/
        if (empty($driverName)) {
            return false;
        }

        if (!empty($result['error'])) {
        }

        if (!empty($result['warning'])) {
        }

        if (!empty($result['removed'])) {
            foreach ($result['removed'] as $file) {
                /** @var Driver $driver */
//                $driver = $this->getDriver($driverName);
//                $driver->$cmd($file, $args, $elFinder);
                // removed file contain additional field "realpath"
                //$log .= "\tREMOVED: ".$file['realpath']."\n";
            }
        }

        if (!empty($result['added'])) {
            foreach ($result['added'] as $file) {
//                $driver = $this->getDriver($driverName);
//                $driver->$cmd($file, $args, $elFinder);
            }
        }

        if (!empty($result['changed'])) {
            foreach ($result['changed'] as $file) {
                //$log .= "\tCHANGED: ".$elfinder->realpath($file['hash'])."\n";
            }
        }
    }

    /**
     * @param string $driver
     * @return string
     */
    private function getDriverClass($driver)
    {
        return 'ChamiloLMS\Component\Editor\Driver\\'.$driver;
    }
}
