<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\Expression;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler\ParameterExpressionCompiler;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\FunctionExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler\Func\FunctionCompilerInterface;

use JMS\SecurityExtraBundle\Security\Acl\Expression\HasPermissionFunctionCompiler;

use JMS\SecurityExtraBundle\Tests\Security\Authorization\Expression\Fixture\Issue22\Project;

use JMS\SecurityExtraBundle\Tests\Security\Authorization\Expression\Fixture\Issue22\SecuredObject;
use CG\Proxy\MethodInvocation;
use Symfony\Component\Security\Core\Role\Role;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;

class ExpressionCompilerTest extends \PHPUnit_Framework_TestCase
{
    private $compiler;

    public function testCompileExpression()
    {
        $evaluator = eval($this->compiler->compileExpression(new Expression('isAnonymous()')));

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $trustResolver = $this->getMock('Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface');
        $trustResolver->expects($this->once())
            ->method('isAnonymous')
            ->with($token)
            ->will($this->returnValue(true));

        $context = array(
            'token' => $token,
            'trust_resolver' => $trustResolver,
        );

        $this->assertTrue($evaluator($context));
    }

    public function testCompileComplexExpression()
    {
        $evaluator = eval($this->compiler->compileExpression(
            new Expression('hasRole("ADMIN") or hasAnyRole("FOO", "BAR")')));

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue(array(new Role('FOO'))));
        $this->assertTrue($evaluator(array('token' => $token)));

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue(array(new Role('BAZ'))));
        $this->assertFalse($evaluator(array('token' => $token)));
    }

    /**
     * @dataProvider getPrecedenceTests
     */
    public function testCompilePrecedence($expected, $a, $b, $c)
    {
        $evaluator = eval($this->compiler->compileExpression(
            new Expression('A and (B or C)')));

        $this->assertSame($expected, $evaluator(array('A' => $a, 'B' => $b, 'C' => $c)));
    }

    public function getPrecedenceTests()
    {
        return array(
            array(true, true, true, false),
            array(true, true, true, true),
            array(true, true, false, true),
            array(false, true, false, false),
            array(false, false, true, true),
            array(false, false, true, false),
            array(false, false, false, true),
            array(false, false, false, false),
        );
    }

    public function testCompileWhenParameterIsWrappedInMethodCall()
    {
        $this->compiler->addTypeCompiler(new ParameterExpressionCompiler());
        $this->compiler->addFunctionCompiler(new HasPermissionFunctionCompiler());

        // the first call ensure that state is reset correctly
        $this->compiler->compileExpression(new Expression(
            'hasPermission(#project.getCompany(), "OPERATOR")'));
        $evaluator = eval($this->compiler->compileExpression(
            new Expression('hasPermission(#project.getCompany(), "OPERATOR")')));

        $secureObject = new SecuredObject();
        $project = new Project();
        $permissionEvaluator = $this->getMockBuilder('JMS\SecurityExtraBundle\Security\Acl\Expression\PermissionEvaluator')
            ->disableOriginalConstructor()
            ->getMock();
        $permissionEvaluator->expects($this->once())
            ->method('hasPermission')
            ->will($this->returnValue(false));

        $context = array(
            'token'  => $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface'),
            'object' => new MethodInvocation(new \ReflectionMethod($secureObject, 'delete'), $secureObject, array($project), array()),
            'permission_evaluator' => $permissionEvaluator,
        );

        $this->assertFalse($evaluator($context));
    }

    public function testCompileInternalInPreconditions()
    {
        $this->compiler->addTypeCompiler(new ParameterExpressionCompiler());
        $this->compiler->addFunctionCompiler(new TestIssue108FunctionCompiler());

        // the first call ensure that state is reset correctly
        $this->compiler->compileExpression(new Expression(
            'testIssue(#project)'));
        $evaluator = eval($this->compiler->compileExpression(
            new Expression('testIssue(#project)')));

        $secureObject = new SecuredObject();
        $project = new Project();

        $context = array(
            'object' => new MethodInvocation(new \ReflectionMethod($secureObject, 'delete'), $secureObject, array($project), array()),
        );

        $this->assertTrue($evaluator($context));
    }

    /**
     * @dataProvider getUnaryNotTests
     */
    public function testCompileWithUnaryOperator($roles, $expected)
    {
        $evaluator = eval($this->compiler->compileExpression(new Expression(
            'not hasRole("FOO") and !hasRole("BAR") and hasRole("BAZ")')));

        $roles = array_map(function($v) {
            return new Role($v);
        }, $roles);

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($roles));

        $this->assertSame($expected, $evaluator(array('token' => $token)));
    }

    public function getUnaryNotTests()
    {
        return array(
            array(array('FOO'), false),
            array(array(), false),
            array(array('BAR'), false),
            array(array('BAZ'), true),
            array(array('FOO', 'BAR'), false),
            array(array('FOO', 'BAZ'), false),
            array(array('BAR', 'BAZ'), false),
        );
    }

    protected function setUp()
    {
        $this->compiler = new ExpressionCompiler();
    }
}

class TestIssue108FunctionCompiler implements FunctionCompilerInterface
{
    public function getName()
    {
        return 'testIssue';
    }

    public function compilePreconditions(ExpressionCompiler $compiler, FunctionExpression $function)
    {
        if (1 !== count($function->args)) {
            throw new \RuntimeException(sprintf('The %s() function expects exactly one argument, but got "%s".', $this->getName(), var_export($function->args, true)));
        }

        $argName = $compiler->nextName();

        $compiler
            ->write("\$$argName = ")
            ->compileInternal($function->args[0])
            ->writeln(';');

        $compiler->attributes['arg_name'] = $argName;
    }

    public function compile(ExpressionCompiler $compiler, FunctionExpression $function)
    {
        $argName = $compiler->attributes['arg_name'];

        $compiler->write("\$$argName !== null");
    }
}
