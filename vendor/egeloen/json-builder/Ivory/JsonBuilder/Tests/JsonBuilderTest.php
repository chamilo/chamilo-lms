<?php

/*
 * This file is part of the Ivory Jsn Builder package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\JsonBuilder\Tests;

use Ivory\JsonBuilder\JsonBuilder;

/**
 * Json builder test.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class JsonBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Ivory\JsonBuilder\JsonBuilder */
    protected $jsonBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->jsonBuilder = new JsonBuilder();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->jsonBuilder);
    }

    /**
     * Gets the json builder values.
     *
     * @return array The json builder values.
     */
    public function valuesProvider()
    {
        return array(
            // Arrays
            array('["foo"]', array('foo')),
            array('["foo","bar","baz"]', array('foo', 'bar', 'baz')),
            array('[["foo","bar"],[["baz"]],"bat"]', array(array('foo', 'bar'), array(array('baz')), 'bat')),

            // Objects
            array('{"foo":"bar"}', array('foo' => 'bar')),
            array('{"foo":"bar","baz":"bat","ban":"boo"}', array('foo' => 'bar', 'baz' => 'bat', 'ban' => 'boo')),
            array('{"foo":"bar","baz":{"bat":"ban"}}', array('foo' => 'bar', 'baz' => array('bat' => 'ban'))),

            // Mixed
            array('["foo",{"bar":"baz"},"bat","ban"]', array('foo', array('bar' => 'baz'), 'bat', 'ban')),
            array('{"foo":"bar","baz":["bat","ban"]}', array('foo' => 'bar', 'baz' => array('bat', 'ban'))),
        );
    }

    /**
     * Gets the json builder value.
     *
     * @return array The json builder value.
     */
    public function valueProvider()
    {
        return array(
            // Arrays
            array('[foo]', array('[0]' => array('value' => 'foo', 'escape' => false))),
            array('[foo,"bar",baz]', array(
                '[0]' => array('value' => 'foo', 'escape' => false),
                '[1]' => array('value' => 'bar', 'escape' => true),
                '[2]' => array('value' => 'baz', 'escape' => false),
            )),
            array('[[foo,"bar"],[baz],bat]', array(
                '[0][0]' => array('value' => 'foo', 'escape' => false),
                '[0][1]' => array('value' => 'bar', 'escape' => true),
                '[1][0]' => array('value' => 'baz', 'escape' => false),
                '[2]'    => array('value' => 'bat', 'escape' => false),
            )),

            // Objects
            array('{"foo":bar}', array('[foo]' => array('value' => 'bar', 'escape' => false))),
            array('{"foo":bar,"baz":"bat","ban":boo}', array(
                '[foo]' => array('value' => 'bar', 'escape' => false),
                '[baz]' => array('value' => 'bat', 'escape' => true),
                '[ban]' => array('value' => 'boo', 'escape' => false),
            )),
            array('{"foo":"bar","baz":{"bat":ban}}', array(
                '[foo]'      => array('value' => 'bar', 'escape' => true),
                '[baz][bat]' => array('value' => 'ban', 'escape' => false),
            )),

            // Mixed
            array('["foo",{"bar":baz},bat,"ban"]', array(
                '[0]'      => array('value' => 'foo', 'escape' => true),
                '[1][bar]' => array('value' => 'baz', 'escape' => false),
                '[2]'      => array('value' => 'bat', 'escape' => false),
                '[3]'      => array('value' => 'ban', 'escape' => true),
            )),
            array('{"foo":bar,"baz":[bat,"ban"]}', array(
                '[foo]'    => array('value' => 'bar', 'escape' => false),
                '[baz][0]' => array('value' => 'bat', 'escape' => false),
                '[baz][1]' => array('value' => 'ban', 'escape' => true),
            )),
        );
    }

    public function testDefaultState()
    {
        $this->assertDefaultState();
    }

    public function testValues()
    {
        $this->jsonBuilder->setValues(array('foo' => 'bar'));

        $this->assertTrue($this->jsonBuilder->hasValues());
        $this->assertSame(array('[foo]' => 'bar'), $this->jsonBuilder->getValues());
    }

    public function testValueWithEscape()
    {
        $this->jsonBuilder->setValue('[foo]', 'bar');

        $this->assertTrue($this->jsonBuilder->hasValues());
        $this->assertSame(array('[foo]' => 'bar'), $this->jsonBuilder->getValues());
    }

    public function testValueWithoutEscape()
    {
        $this->jsonBuilder->setValue('[foo]', 'bar', false);

        $values = $this->jsonBuilder->getValues();

        $this->assertTrue($this->jsonBuilder->hasValues());
        $this->assertArrayHasKey('[foo]', $values);
        $this->assertNotSame('bar', $values['[foo]']);
    }

    public function testRemoveValue()
    {
        $this->jsonBuilder
            ->setValue('[foo]', 'bar')
            ->removeValue('[foo]');

        $this->assertFalse($this->jsonBuilder->hasValues());
    }

    public function testReset()
    {
        $this->jsonBuilder
            ->setValues(array('foo' => 'bar'))
            ->reset();

        $this->assertDefaultState();
    }

    public function testBuildWithoutValues()
    {
        $this->assertSame('[]', $this->jsonBuilder->build());
    }

    public function testBuildWithJsonEncodeOptions()
    {
        $this->jsonBuilder->setJsonEncodeOptions(JSON_FORCE_OBJECT);

        $this->assertSame(JSON_FORCE_OBJECT, $this->jsonBuilder->getJsonEncodeOptions());
        $this->assertSame('{}', $this->jsonBuilder->build());
    }

    /**
     * @dataProvider valuesProvider
     */
    public function testBuildWithValues($expected, array $values)
    {
        $this->assertSame($expected, $this->jsonBuilder->setValues($values)->build());
    }

    /**
     * @dataProvider valueProvider
     */
    public function testBuildWithValue($expected, array $values)
    {
        foreach ($values as $path => $value) {
            $this->jsonBuilder->setValue($path, $value['value'], $value['escape']);
        }

        $this->assertSame($expected, $this->jsonBuilder->build());
    }

    /**
     * Asserts the json builder default state.
     */
    protected function assertDefaultState()
    {
        $this->assertSame(0, $this->jsonBuilder->getJsonEncodeOptions());
        $this->assertFalse($this->jsonBuilder->hasValues());
        $this->assertEmpty($this->jsonBuilder->getValues());
    }
}
