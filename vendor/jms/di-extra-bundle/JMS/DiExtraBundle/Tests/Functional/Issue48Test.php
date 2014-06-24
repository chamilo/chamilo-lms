<?php

namespace JMS\DiExtraBundle\Tests\Functional;

class Issue48Test extends BaseTestCase
{
    public function testCreatingMultipleKernelsInATest()
    {
        $kernelA = static::createKernel(array('debug' => false, 'config' => 'doctrine.yml'));
        $kernelA->boot();

        $kernelB = static::createKernel(array('debug' => true, 'config' => 'doctrine.yml'));
        $kernelB->boot();

        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $kernelA->getContainer()->get('doctrine.orm.default_entity_manager'));
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $kernelB->getContainer()->get('doctrine.orm.default_entity_manager'));
    }
}