<?php

namespace Tests\Brumann\Polyfill;

use Brumann\Polyfill\Unserialize;

class UnserializeTest extends \PHPUnit_Framework_TestCase
{
    public function test_unserialize_without_options_returns_instance()
    {
        $foo = new Foo();
        $serialized = serialize($foo);

        $unserialized = Unserialize::unserialize($serialized);

        $this->assertInstanceOf('Tests\\Brumann\\Polyfill\\Foo', $unserialized);
    }

    public function test_unserialize_with_cqn_returns_instance()
    {
        $foo = new Foo();
        $serialized = serialize($foo);
        $options = array(
            'allowed_classes' => array('Tests\\Brumann\\Polyfill\\Foo'),
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf('Tests\\Brumann\\Polyfill\\Foo', $unserialized);
    }

    public function test_unserialize_with_fqcn_allowed_returns_instance()
    {
        $foo = new Foo();
        $serialized = serialize($foo);
        $options = array(
            'allowed_classes' => array('\\Tests\\Brumann\\Polyfill\\Foo'),
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized);
    }

    public function test_unserialize_with_allowed_classes_false_returns_incomplete_object()
    {
        $foo = new Foo();
        $serialized = serialize($foo);
        $options = array(
            'allowed_classes' => false,
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized);
    }

    /**
     * @requires PHP < 7.0
     *
     * @expectedException \PHPUnit_Framework_Error_Warning
     * @expectedMessage allowed_classes option should be array or boolean
     */
    public function test_unserialize_with_allowed_classes_null_behaves_like_php71()
    {
        $foo = new Foo();
        $serialized = serialize($foo);
        $options = array(
            'allowed_classes' => null,
        );

        Unserialize::unserialize($serialized, $options);
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Notice
     * @expectedExceptionMessage tried to execute a method or access a property of an incomplete object.
     */
    public function test_accessing_property_of_incomplete_object_returns_warning()
    {
        $bar = new \stdClass();
        $bar->foo = new Foo();
        $serialized = serialize($bar);
        $options = array(
            'allowed_classes' => array('Tests\\Brumann\\Polyfill\\Foo'),
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized);
        $unserialized->foo;
    }

    public function test_unserialize_only_parent_object()
    {
        $foo = new Foo();
        $foo->bar = new \stdClass();
        $serialized = serialize($foo);
        $options = array(
            'allowed_classes' => array('Tests\\Brumann\\Polyfill\\Foo'),
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf('\\Tests\\Brumann\\Polyfill\\Foo', $unserialized);
        $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized->bar);
    }

    public function test_unserialize_parent_and_embedded_object()
    {
        $foo = new Foo();
        $foo->foo = new Foo();
        $serialized = serialize($foo);
        $options = array(
            'allowed_classes' => array('Tests\\Brumann\\Polyfill\\Foo'),
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf('\\Tests\\Brumann\\Polyfill\\Foo', $unserialized);
        $this->assertInstanceOf('\\Tests\\Brumann\\Polyfill\\Foo', $unserialized->foo);
    }

    public function test_unserialize_with_allowed_classes_false_serializes_string()
    {
        $string = 'This is an ordinary string';
        $serialized = serialize($string);
        $options = array(
            'allowed_classes' => false,
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertEquals($string, $unserialized);
    }

    public function test_unserialize_with_allowed_classes_false_serializes_bool()
    {
        $bool = true;
        $serialized = serialize($bool);
        $options = array(
            'allowed_classes' => false,
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertEquals($bool, $unserialized);
    }

    public function test_unserialize_with_allowed_classes_false_serializes_array()
    {
        $array = array(
            'key' => 42,
            1 => 'foo',
            'bar' => 'baz',
            2 => 23,
            4 => true,
        );
        $serialized = serialize($array);
        $options = array(
            'allowed_classes' => false,
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertSame($array, $unserialized);
    }

    public function test_double_serialized_unserializes_as_first_serialized()
    {
        $foo = new Foo();
        $first = serialize($foo);
        $second = serialize($first);
        $options = array(
            'allowed_classes' => false,
        );

        $unserialized = Unserialize::unserialize($second, $options);

        $this->assertSame($first, $unserialized);
    }

    public function test_double_unserialize_double_serialized()
    {
        $foo = new Foo();
        $serialized = serialize(serialize($foo));
        $options = array(
            'allowed_classes' => false,
        );

        $first = Unserialize::unserialize($serialized, $options);
        $unserialized = Unserialize::unserialize($first, $options);

        $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized);
    }
}
