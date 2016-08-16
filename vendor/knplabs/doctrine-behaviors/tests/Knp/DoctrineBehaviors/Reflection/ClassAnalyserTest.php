<?php

namespace Tests\Knp\DoctrineBehaviors\Reflection;

use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;
use BehaviorFixtures\ORM\DeletableEntity;
use BehaviorFixtures\ORM\DeletableEntityInherit;
use BehaviorFixtures\ORM\GeocodableEntity;
use BehaviorFixtures\ORM\TranslatableEntity;

class ClassAnalyserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function it_should_test_if_object_use_trait () {

        $analyser = new ClassAnalyzer;

        $object = new DeletableEntity;

        $use = $analyser->hasTrait(
            new \ReflectionClass($object), 
            'Knp\DoctrineBehaviors\Model\SoftDeletable\SoftDeletable', 
            false
        );

        $this->assertTrue($use);
    }

    /**
     * @test
     */
    public function it_should_test_if_object_dont_use_trait () {

        $analyser = new ClassAnalyzer;

        $object = new DeletableEntity;

        $use = $analyser->hasTrait(
            new \ReflectionClass($object), 
            'Knp\DoctrineBehaviors\Model\Blameable\Blameable', 
            false
        );

        $this->assertFalse($use);
    }

    /**
     * @test
     */
    public function it_should_test_if_object_or_his_parent_classes_use_trait () {

        $analyser = new ClassAnalyzer;

        $object = new DeletableEntityInherit;

        $use = $analyser->hasTrait(
            new \ReflectionClass($object), 
            'Knp\DoctrineBehaviors\Model\SoftDeletable\SoftDeletable', 
            false
        );

        $this->assertFalse($use);

        $useInherit = $analyser->hasTrait(
            new \ReflectionClass($object), 
            'Knp\DoctrineBehaviors\Model\SoftDeletable\SoftDeletable', 
            true
        );

        $this->assertTrue($useInherit);
    }

    /**
     * @test
     */
    public function it_should_test_if_object_has_a_method () {

        $analyser = new ClassAnalyzer;

        $object = new GeocodableEntity;

        $use = $analyser->hasMethod(
            new \ReflectionClass($object), 
            'getLocation'
        );

        $this->assertTrue($use);
    }

    /**
     * @test
     */
    public function it_should_test_if_object_dont_has_a_method () {

        $analyser = new ClassAnalyzer;

        $object = new DeletableEntity;

        $use = $analyser->hasMethod(
            new \ReflectionClass($object), 
            'getLocation'
        );

        $this->assertFalse($use);
    }

    /**
     * @test
     */
    public function it_should_test_if_object_has_a_property () {

        $analyser = new ClassAnalyzer;

        $object = new TranslatableEntity;

        $use = $analyser->hasProperty(
            new \ReflectionClass($object), 
            'translations'
        );

        $this->assertTrue($use);
    }

    /**
     * @test
     */
    public function it_should_test_if_object_dont_has_a_property () {

        $analyser = new ClassAnalyzer;

        $object = new DeletableEntity;

        $use = $analyser->hasProperty(
            new \ReflectionClass($object), 
            'translations'
        );

        $this->assertFalse($use);
    }

    /**
     * @test
     */
    public function it_should_test_if_object_or_his_parent_classes_has_a_property () {

        $analyser = new ClassAnalyzer;

        $object = new DeletableEntityInherit;

        $use = $analyser->hasProperty(
            new \ReflectionClass($object), 
            'deletedAt'
        );

        $this->assertTrue($use);
    }

}
