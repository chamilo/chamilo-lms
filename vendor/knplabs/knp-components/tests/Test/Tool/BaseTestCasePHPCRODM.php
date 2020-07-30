<?php

namespace Test\Tool;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\DriverManager;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Connection;
use Jackalope\RepositoryFactoryDoctrineDBAL;
use Jackalope\Session;
use Jackalope\Transport\DoctrineDBAL\RepositorySchema;

/**
 * Base test case contains common mock objects
 */
abstract class BaseTestCasePHPCRODM extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    protected function setUp()
    {
        if (!class_exists('Doctrine\ODM\PHPCR\Query\Query')) {
            $this->markTestSkipped('Doctrine PHPCR-ODM is not available');
        }
    }

    protected function tearDown()
    {
        if ($this->dm) {
            $this->dm = null;
        }
    }

    protected function getMockDocumentManager(EventManager $evm = null)
    {
        $config = new \Doctrine\ODM\PHPCR\Configuration();
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());

        $this->dm = DocumentManager::create($this->getSession(), $config, $evm ?: $this->getEventManager());

        return $this->dm;
    }

    protected function getMetadataDriverImplementation()
    {
        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    private function getSession()
    {
        $connection = DriverManager::getConnection(array(
            'driver' => 'pdo_sqlite',
            'path'   => ':memory:',
        ));
        $factory = new RepositoryFactoryDoctrineDBAL();
        $repository = $factory->getRepository(array(
            'jackalope.doctrine_dbal_connection' => $connection,
        ));

        $schema = new RepositorySchema(array('disable_fks' => true), $connection);
        $schema->reset();

        $session = $repository->login(new \PHPCR\SimpleCredentials('', ''));

        $cnd = <<<CND
<phpcr='http://www.doctrine-project.org/projects/phpcr_odm'>
[phpcr:managed]
mixin
- phpcr:class (STRING)
- phpcr:classparents (STRING) multiple
CND;

        $session->getWorkspace()->getNodeTypeManager()->registerNodeTypesCnd($cnd, true);

        return $session;
    }

    private function getEventManager()
    {
        $evm = new EventManager();
        return $evm;
    }
}
