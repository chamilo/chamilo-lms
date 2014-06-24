<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\Expression;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\OrExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleInterface;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ReverseInterpreter;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;

class ReverseInterpreterTest extends \PHPUnit_Framework_TestCase
{
    private $compiler;
    private $reverseInterpreter;
    private $handler;
    private $token;
    private $vars;

    public function testSimpleExpression()
    {
        $this->assumeVar('a', false);
        $this->verifyExpr('a', new VariableExpression('a'));
    }

    public function testShortCircuitingExpression()
    {
        $this->assumeVar('a', false);
        $this->assumeVar('b', false);
        $this->verifyExpr('a && b', new VariableExpression('a'));
    }

    public function testShortCircuitingExpression2()
    {
        $this->assumeVar('a', false);
        $this->assumeVar('b', false);
        $this->assumeVar('c', false);
        $this->verifyExpr('a && b && c', new VariableExpression('a'));
    }

    public function testShortCircuitingExpression3()
    {
        $this->assumeVar('a', true);
        $this->assumeVar('b', false);
        $this->verifyExpr('a && b', new VariableExpression('b'));
    }

    public function testShortCircuitingExpression4()
    {
        $this->assumeVar('a', true);
        $this->assumeVar('b', false);
        $this->assumeVar('c', false);
        $this->verifyExpr('a && b && c', new VariableExpression('b'));
    }

    public function testMaybeShortCircuitingExpression()
    {
        $this->assumeVar('a', false);
        $this->assumeVar('b', false);
        $this->verifyExpr('a || b', new OrExpression(new VariableExpression('a'), new VariableExpression('b')));
    }

    public function testMaybeShortCircuitingExpression2()
    {
        $this->assumeVar('a', false);
        $this->assumeVar('b', true);
        $this->verifyExpr('a || b', null);
    }

    public function testMaybeShortCircuitingExpression3()
    {
        $this->assumeVar('a', true);
        $this->assumeVar('b', false);
        $this->verifyExpr('a || b', null);
    }

    public function testMaybeShortCircuitingExpression4()
    {
        $this->assumeVar('a', false);
        $this->assumeVar('b', true);
        $this->assumeVar('c', false);
        $this->verifyExpr('a || (b && c)', new OrExpression(new VariableExpression('a'), new VariableExpression('c')));
    }

    private function verifyExpr($expr, $denyingExpr)
    {
        $context = $this->vars;
        $context['token'] = $this->token;

        $this->handler->expects($this->once())
            ->method('createContext')
            ->will($this->returnValue($context));

        $actualExpr = $this->reverseInterpreter->getDenyingExpr($this->token, array(new Expression($expr)));
        $this->assertEquals($denyingExpr, $actualExpr);
    }

    private function assumeVar($name, $value)
    {
        $this->vars[$name] = $value;
    }

    protected function setUp()
    {
        $this->compiler = new ExpressionCompiler();
        $this->handler = $this->getMock('JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionHandlerInterface');
        $this->reverseInterpreter = new ReverseInterpreter($this->compiler, $this->handler);
        $this->token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
    }
}