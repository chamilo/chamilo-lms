<?php

namespace JMS\SecurityExtraBundle\Tests\Metadata\Driver;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use JMS\SecurityExtraBundle\Metadata\MethodMetadata;
use JMS\SecurityExtraBundle\Metadata\Driver\ConfigDriver;

class ConfigDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadMetadata()
    {
        $driver = new ConfigDriver(array(), array(
            'CrudController::.*Action$' => 'hasRole("FOO")',
        ));

        $metadata = $driver->loadMetadataForClass($this->getClass('Controller\\CrudController'));

        $this->assertEquals(5, count($metadata->methodMetadata));

        $metadata = $metadata->methodMetadata;
        foreach (array('addAction', 'deleteAction', 'editAction', 'showAction', 'newAction') as $action) {
            $this->assertArrayHasKey($action, $metadata);
            $this->assertEquals(array(new Expression('hasRole("FOO")')), $metadata[$action]->roles);
        }
    }

    public function testLoadMetadataControllerNotation()
    {
        $driver = new ConfigDriver(array(
            'AcmeFooBundle' => 'JMS\SecurityExtraBundle\Tests\Metadata\Driver\Fixtures\AcmeFooBundle',
        ), array(
            '^AcmeFooBundle:.*:delete.*$' => 'hasRole("ROLE_ADMIN")',
        ));

        $metadata = $driver->loadMetadataForClass($this->getClass('Controller\\CrudController'));

        $this->assertEquals(1, count($metadata->methodMetadata));
        $this->assertArrayHasKey('deleteAction', $metadata->methodMetadata);
        $this->assertEquals(array(new Expression('hasRole("ROLE_ADMIN")')), $metadata->methodMetadata['deleteAction']->roles);
    }

    public function testLoadMetadataWithoutConfig()
    {
        $driver = new ConfigDriver(array(), array());
        $this->assertNull($driver->loadMetadataForClass($this->getClass('Controller\\CrudController')));
    }

    private function getClass($name)
    {
        return new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Metadata\Driver\Fixtures\\'.$name);
    }
}
