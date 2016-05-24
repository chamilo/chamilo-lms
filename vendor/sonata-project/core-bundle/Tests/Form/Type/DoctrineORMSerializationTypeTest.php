<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Form\Type;

use Doctrine\ORM\Mapping\ClassMetadataInfo as DoctrineMetadata;
use JMS\Serializer\Metadata\ClassMetadata as SerializerMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use Sonata\CoreBundle\Form\Type\DoctrineORMSerializationType;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class FakeMetadataClass.
 */
class FakeMetadataClass
{
    protected $name;

    protected $url;

    protected $comments;
}

/**
 * Class DoctrineORMSerializationTypeTest.
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class DoctrineORMSerializationTypeTest extends TypeTestCase
{
    /**
     * @var string
     */
    protected $class;

    /**
     * Set up test.
     */
    public function setUp()
    {
        if (!interface_exists('\Metadata\MetadataFactoryInterface') or !interface_exists('\Doctrine\ORM\EntityManagerInterface')) {
            $this->markTestSkipped('Serializer and Doctrine has to be loaded to run this test');
        }

        parent::setUp();

        $this->class = 'Sonata\CoreBundle\Tests\Form\Type\FakeMetadataClass';
    }

    /**
     * Test form type buildForm() method that generates data.
     */
    public function testBuildForm()
    {
        $metadataFactory = $this->getMetadataFactoryMock();
        $registry = $this->getRegistryMock();

        if (version_compare(Kernel::VERSION, '2.8', '<')) {
            $type = new DoctrineORMSerializationType($metadataFactory, $registry, 'form_type_test', $this->class, 'serialization_api_write');
        } else {
            $this->factory = $this->createCustomFactory($metadataFactory, $registry, 'form_type_test', $this->class, 'serialization_api_write');
            $type = 'Sonata\CoreBundle\Form\Type\DoctrineORMSerializationType';
        }

        $form = $this->factory->createBuilder($type, null)->setCompound(true)->getForm();

        // Asserts that all 3 elements are in the form
        $this->assertSame(3, $form->count(), 'Should return 3 items in form');

        // Assets that forms are correctly returned for correct fields
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form->get('name'), 'Should return a form instance');
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form->get('url'), 'Should return a form instance');
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form->get('comments'), 'Should return a form instance');

        // Asserts that required fields / associations rules are correctly parsed
        $this->assertFalse($form->get('name')->isRequired(), 'Should return a non-required field');
        $this->assertTrue($form->get('url')->isRequired(), 'Should return a required field');
        $this->assertFalse($form->get('comments')->isRequired(), 'Should return a non-required field');
    }

    /**
     * Test form type buildForm() method that generates data with an identifier field.
     */
    public function testBuildFormWithIdentifier()
    {
        $metadataFactory = $this->getMetadataFactoryMock();
        $registry = $this->getRegistryMock(array('name'));

        if (version_compare(Kernel::VERSION, '2.8', '<')) {
            $type = new DoctrineORMSerializationType($metadataFactory, $registry, 'form_type_test', $this->class, 'serialization_api_write');
        } else {
            $this->factory = $this->createCustomFactory($metadataFactory, $registry, 'form_type_test', $this->class, 'serialization_api_write');
            $type = 'Sonata\CoreBundle\Form\Type\DoctrineORMSerializationType';
        }

        $form = $this->factory->createBuilder($type, null)->setCompound(true)->getForm();

        // Assets that forms have only 2 generated fields as the third is an identifier
        $this->assertSame(2, $form->count(), 'Should return 2 elements in the form');

        // Assets that forms are correctly returned for correct fields
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form->get('url'), 'Should return a form instance');
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form->get('comments'), 'Should return a form instance');
    }

    /**
     * Test form type buildForm() method that generates data with an invalid group name.
     */
    public function testBuildFormWithInvalidGroupName()
    {
        $metadataFactory = $this->getMetadataFactoryMock();
        $registry = $this->getRegistryMock(array('name'));

        if (version_compare(Kernel::VERSION, '2.8', '<')) {
            $type = new DoctrineORMSerializationType($metadataFactory, $registry, 'form_type_test', $this->class, 'serialization_api_invalid');
        } else {
            $this->factory = $this->createCustomFactory($metadataFactory, $registry, 'form_type_test', $this->class, 'serialization_api_invalid');
            $type = 'Sonata\CoreBundle\Form\Type\DoctrineORMSerializationType';
        }

        $form = $this->factory->createBuilder($type, null)->setCompound(true)->getForm();

        // Assets that forms have no generated field as group is invalid
        $this->assertSame(0, $form->count(), 'Should return 0 elements as given form type group is invalid');
    }

    /**
     * Returns a Serializer MetadataFactory mock.
     *
     * @return \Metadata\MetadataFactoryInterface
     */
    protected function getMetadataFactoryMock()
    {
        $name = new PropertyMetadata($this->class, 'name');
        $name->groups = array('serialization_api_write');

        $url = new PropertyMetadata($this->class, 'url');
        $url->groups = array('serialization_api_write');

        $comments = new PropertyMetadata($this->class, 'comments');
        $comments->groups = array('serialization_api_write');

        $classMetadata = new SerializerMetadata($this->class);
        $classMetadata->addPropertyMetadata($name);
        $classMetadata->addPropertyMetadata($url);
        $classMetadata->addPropertyMetadata($comments);

        $metadataFactory = $this->getMock('Metadata\MetadataFactoryInterface');
        $metadataFactory->expects($this->once())->method('getMetadataForClass')->will($this->returnValue($classMetadata));

        return $metadataFactory;
    }

    /**
     * Returns a Doctrine registry mock.
     *
     * @param array $identifiers
     *
     * @return \Symfony\Bridge\Doctrine\RegistryInterface
     */
    protected function getRegistryMock($identifiers = array())
    {
        $classMetadata = new DoctrineMetadata($this->class);
        $classMetadata->identifier = $identifiers;

        $classMetadata->fieldMappings = array('name' => array(
            'nullable' => true,
        ));

        $classMetadata->associationMappings = array(
            'url' => array(
                'joinColumns' => array('nullable' => false),
            ),
            'comments' => array(
                'inverseJoinColumns' => array('nullable' => true),
            ),
        );

        $entityManager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $entityManager->expects($this->once())->method('getClassMetadata')->will($this->returnValue($classMetadata));

        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->once())->method('getManagerForClass')->will($this->returnValue($entityManager));

        return $registry;
    }

    /**
     * Create a form factory registering the DoctrineORMSerializationType.
     */
    protected function createCustomFactory($metadataFactory, $registry, $name, $class, $group)
    {
        return $this->factory = Forms::createFormFactoryBuilder()
            ->addType(new DoctrineORMSerializationType($metadataFactory, $registry, $name, $class, $group))
            ->getFormFactory();
    }
}
