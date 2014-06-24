<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\Expression;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\IsEqualExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ParameterExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ConstantExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\GetItemExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\ArrayExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\MethodCallExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\GetPropertyExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\VariableExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\OrExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\AndExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Ast\FunctionExpression;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\ExpressionParser;

class ExpressionParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser;

    public function testSingleFunction()
    {
        $this->assertEquals(new FunctionExpression('isAnonymous', array()),
            $this->parser->parse('isAnonymous()'));
    }

    public function testSingleFunctionWithOneArgument()
    {
        $this->assertEquals(new FunctionExpression('hasRole', array(
            new ConstantExpression('ROLE_ADMIN'))),
            $this->parser->parse('hasRole("ROLE_ADMIN")'));
    }

    public function testSingleFunctionWithMultipleArguments()
    {
        $this->assertEquals(new FunctionExpression('hasAnyRole', array(
            new ConstantExpression('FOO'), new ConstantExpression('BAR'))),
            $this->parser->parse('hasAnyRole("FOO", "BAR",)'));
    }

    public function testComplexFunctionExpression()
    {
        $expected = new OrExpression(new FunctionExpression('hasRole', array(
            new ConstantExpression('ADMIN'))),
            new FunctionExpression('hasAnyRole', array(new ConstantExpression('FOO'),
            new ConstantExpression('BAR'))));

        $this->assertEquals($expected, $this->parser->parse('hasRole("ADMIN") or hasAnyRole("FOO", "BAR")'));
    }

    public function testAnd()
    {
        $expected = new AndExpression(
            new FunctionExpression('isAnonymous', array()),
            new FunctionExpression('hasRole', array(new ConstantExpression('FOO'))));

        $this->assertEquals($expected, $this->parser->parse('isAnonymous() && hasRole("FOO")'));
        $this->assertEquals($expected, $this->parser->parse('isAnonymous() and hasRole("FOO")'));
    }

    /**
     * @expectedException \JMS\Parser\SyntaxErrorException
     * @expectedExceptionMessage Expected end of input, but got "," of type T_COMMA at position 6 (0-based).
     */
    public function testInvalidExpression()
    {
        $this->parser->parse('object, "FOO")');
    }

    /**
     * @dataProvider getPrecedenceTests
     */
    public function testPrecendence($expected, $expr)
    {
        $this->assertEquals($expected, $this->parser->parse($expr));
    }

    public function getPrecedenceTests()
    {
        $tests = array();

        $expected = new OrExpression(
            new AndExpression(new VariableExpression('A'), new VariableExpression('B')),
            new VariableExpression('C')
        );
        $tests[] = array($expected, 'A && B || C');
        $tests[] = array($expected, '(A && B) || C');

        $expected = new OrExpression(
        new VariableExpression('C'),
            new AndExpression(new VariableExpression('A'), new VariableExpression('B'))
        );
        $tests[] = array($expected, 'C || A && B');
        $tests[] = array($expected, 'C || (A && B)');

        $expected = new AndExpression(
        new AndExpression(new VariableExpression('A'), new VariableExpression('B')),
            new VariableExpression('C')
        );
        $tests[] = array($expected, 'A && B && C');

        $expected = new AndExpression(
            new VariableExpression('A'),
            new OrExpression(new VariableExpression('B'), new VariableExpression('C'))
        );
        $tests[] = array($expected, 'A && (B || C)');

        return $tests;
    }

    public function testGetProperty()
    {
        $expected = new GetPropertyExpression(new VariableExpression('A'), 'foo');
        $this->assertEquals($expected, $this->parser->parse('A.foo'));
    }

    public function testMethodCall()
    {
        $expected = new MethodCallExpression(new VariableExpression('A'), 'foo', array());
        $this->assertEquals($expected, $this->parser->parse('A.foo()'));
    }

    public function testArray()
    {
        $expected = new ArrayExpression(array(
            'foo' => new ConstantExpression('bar'),
        ));
        $this->assertEquals($expected, $this->parser->parse('{"foo":"bar",}'));
        $this->assertEquals($expected, $this->parser->parse('{"foo":"bar"}'));

        $expected = new ArrayExpression(array(
            new ConstantExpression('foo'),
            new ConstantExpression('bar'),
        ));
        $this->assertEquals($expected, $this->parser->parse('["foo","bar",]'));
        $this->assertEquals($expected, $this->parser->parse('["foo","bar"]'));
    }

    public function testGetItem()
    {
        $expected = new GetItemExpression(
            new GetPropertyExpression(new VariableExpression('A'), 'foo'),
            new ConstantExpression('foo')
        );
        $this->assertEquals($expected, $this->parser->parse('A.foo["foo"]'));
    }

    public function testParameter()
    {
        $expected = new ParameterExpression('contact');
        $this->assertEquals($expected, $this->parser->parse('#contact'));
    }

    public function testIsEqual()
    {
        $expected = new IsEqualExpression(new MethodCallExpression(
            new VariableExpression('user'), 'getUsername', array()),
            new ConstantExpression('Johannes'));
        $this->assertEquals($expected, $this->parser->parse('user.getUsername() == "Johannes"'));
    }

    protected function setUp()
    {
        $this->parser = new ExpressionParser;
    }
}
