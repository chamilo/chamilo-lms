<?php

namespace JMS\SecurityExtraBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;

class MethodAccessControlTest extends BaseTestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testControllerAddActionIsSecure()
    {
        $client = $this->createClient(array('config' => 'method_access_control.yml'));

        $client->request('GET', '/add');
        $this->assertRedirectedToLogin($client->getResponse());
    }

    /**
     * @runInSeparateProcess
     */
    public function testControllerEditActionIsNotSecure()
    {
        $client = $this->createClient(array('config' => 'method_access_control.yml'));

        $client->request('GET', '/edit');
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testWildcardSecured()
    {
        $client = $this->createClient(array('config' => 'method_access_control.yml'));

        $client->request('GET', '/foo/foo');
        $this->assertRedirectedToLogin($client->getResponse());

        $client->request('GET', '/foo/bar');
        $this->assertRedirectedToLogin($client->getResponse());

        $client->request('GET', '/foo/exception');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @runInSeparateProcess
     * @expectedException Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException
     */
    public function testUserManagerDeleteIsSecure()
    {
        $this->createClient(array('config' => 'method_access_control.yml'));

        $manager = self::$kernel->getContainer()->get('user_manager');

        $this->assertNotEquals(
            'JMS\SecurityExtraBundle\Tests\Functional\TestBundle\User\UserManager',
            get_class($manager)
        );
        $manager->delete();
    }

    /**
     * @runInSeparateProcess
     */
    public function testFunctionEvaluator()
    {
        $client = $this->createClient(array('config' => 'method_access_control.yml'));

        $evaluator = self::$kernel->getContainer()->get('always_true_evaluator');
        $this->assertEquals(0, $evaluator->getNbCalls());

        $client->request('GET', '/post/foo');
        $response = $client->getResponse();
        $this->assertEquals('foo', $response->getContent());

        $this->assertEquals(1, $evaluator->getNbCalls());
    }

    /**
     * @runInSeparateProcess
     */
    public function testAcl()
    {
        $client = $this->createClient(array('config' => 'acl_enabled.yml'));
        $client->insulate();

        $this->importDatabaseSchema();
        $this->login($client);

        $client->request('POST', '/post/add', array('title' => 'Foo'));

        $response = $client->getResponse();
        $this->assertEquals('/post/edit/1', $response->headers->get('Location'),
            substr($response, 0, 2000));

        $client->request('GET', '/post/edit/1');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), substr($response, 0, 2000));
        $this->assertEquals('Foo', $response->getContent());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRoleHierarchyIsRespected()
    {
        $client = $this->createClient(array('config' => 'all_voters_disabled.yml'));
        $client->insulate();

        $this->login($client);

        $client->request('GET', '/post/list');

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), substr($response, 0, 2000));
        $this->assertEquals('list', $response->getContent(), substr($response, 0, 2000));
    }

    private function assertRedirectedToLogin(Response $response)
    {
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('http://localhost/login', $response->headers->get('Location'));
    }
}