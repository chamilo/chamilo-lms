<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Acl\Expression;

use JMS\SecurityExtraBundle\Security\Acl\Expression\HasPermissionFunctionCompiler;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ConstantExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\FunctionExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionCompiler;

class HasPermissionFunctionCompilerTest extends \PHPUnit_Framework_TestCase
{
    private $compiler;

    public function testCompile()
    {
        $source = $this->compiler->compile(new FunctionExpression('hasPermission',
            array(new VariableExpression('foo'), new ConstantExpression('VIEW'))));

        $this->assertContains(
            "\$context['permission_evaluator']->hasPermission(\$context['token'], \$context['foo'], 'VIEW');",
            $source);
    }

    public function testCompileUpperCasesPermissions()
    {
        $source = $this->compiler->compile(new FunctionExpression('hasPermission',
            array(new VariableExpression('foo'), new ConstantExpression('view'))));

        $this->assertContains(
            "\$context['permission_evaluator']->hasPermission(\$context['token'], \$context['foo'], 'VIEW');",
            $source);
    }

    protected function setUp()
    {
        $this->compiler = new ExpressionCompiler();
        $this->compiler->addFunctionCompiler(new HasPermissionFunctionCompiler());
    }
}