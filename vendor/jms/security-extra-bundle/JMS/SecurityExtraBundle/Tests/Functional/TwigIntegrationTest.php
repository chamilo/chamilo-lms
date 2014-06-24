<?php

namespace JMS\SecurityExtraBundle\Tests\Functional;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class TwigIntegrationTest extends BaseTestCase
{
    private $context;
    private $twig;

    /**
     * @runInSeparateProcess
     */
    public function testIsExprGrantedWithSufficientPermissions()
    {
        $this->context->setToken(new UsernamePasswordToken('foo', 'bar', 'baz', array('FOO')));

        $this->assertEquals('granted',
            $this->twig->render('TestBundle::is_expr_granted.html.twig'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testIsExprGranted()
    {
        $this->context->setToken(new AnonymousToken('foo', 'bar'));

        $this->assertEquals('denied',
            $this->twig->render('TestBundle::is_expr_granted.html.twig'));
    }

    protected function setUp()
    {
        parent::setUp();

        $this->createClient(array('config' => 'all_voters_disabled.yml'));
        $this->context = self::$kernel->getContainer()->get('security.context');
        $this->twig = self::$kernel->getContainer()->get('twig');
    }
}