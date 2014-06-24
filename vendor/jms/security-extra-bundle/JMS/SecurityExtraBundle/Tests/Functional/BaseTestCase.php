<?php

namespace JMS\SecurityExtraBundle\Tests\Functional;

use Symfony\Component\Filesystem\Filesystem;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BaseTestCase extends WebTestCase
{
    protected static function createKernel(array $options = array())
    {
        return new AppKernel(
            isset($options['config']) ? $options['config'] : 'default.yml'
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/JMSSecurityExtraBundle');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/JMSSecurityExtraBundle');
    }

    protected function login($client, $username = null, $password = null)
    {
        if (empty($username) || empty($password)) {
            $username = 'johannes';
            $password = 'test';
        }

        $crawler = $client->request('get', '/login')->selectButton('login');
        $form = $crawler->form();

        $form['_username'] = $username;
        $form['_password'] = $password;
        $client->submit($form);

        $security = $client->getProfile()->getCollector('security');

        $this->assertTrue(is_string($security->getUser()) && strlen($security->getUser()) > 0);
        $this->assertTrue($security->isAuthenticated(), 'Logged in user is not authenticated.');
    }

    final protected function importDatabaseSchema()
    {
        $em = self::$kernel->getContainer()->get('em');

        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
            $schemaTool->createSchema($metadata);
        }
    }
}
