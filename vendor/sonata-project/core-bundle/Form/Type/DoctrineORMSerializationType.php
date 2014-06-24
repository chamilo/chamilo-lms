<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Sonata\CoreBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Metadata\MetadataFactoryInterface;

/**
 * Class DoctrineORMSerializationType
 *
 * This is a doctrine serialization form type that generates a form type from class serialization metadata
 * and doctrine metadata
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class DoctrineORMSerializationType extends AbstractType
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var MetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $group;

    /**
     * Constructor
     *
     * @param MetadataFactoryInterface $metadataFactory Serializer metadata factory
     * @param ManagerRegistry          $registry        Doctrine registry
     * @param string                   $name            Form type name
     * @param string                   $class           Data class name
     * @param string                   $group           Serialization group name
     */
    public function __construct(MetadataFactoryInterface $metadataFactory, ManagerRegistry $registry, $name, $class, $group)
    {
        $this->metadataFactory = $metadataFactory;
        $this->registry = $registry;
        $this->name  = $name;
        $this->class = $class;
        $this->group = $group;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $serializerMetadata = $this->metadataFactory->getMetadataForClass($this->class);

        $manager = $this->registry->getManagerForClass($this->class);
        $doctrineMetadata = $manager->getClassMetadata($this->class);

        foreach ($serializerMetadata->propertyMetadata as $propertyMetadata) {
            $name = $propertyMetadata->name;

            if (in_array($name, $doctrineMetadata->getIdentifierFieldNames())) {
                continue;
            }

            if (!in_array($this->group, $propertyMetadata->groups)) {
                continue;
            }

            $type = null;
            $nullable = true;

            if (isset($doctrineMetadata->fieldMappings[$name])) {
                $fieldMetadata = $doctrineMetadata->fieldMappings[$name];
                $type = isset($fieldMetadata['type']) ? $fieldMetadata['type'] : null;
                $nullable = isset($fieldMetadata['nullable']) ? $fieldMetadata['nullable'] : false;
            } else if (isset($doctrineMetadata->associationMappings[$name])) {
                $associationMetadata = $doctrineMetadata->associationMappings[$name];

                if (isset($associationMetadata['joinColumns']['nullable'])) {
                    $nullable = $associationMetadata['joinColumns']['nullable'];
                } else if (isset($associationMetadata['inverseJoinColumns']['nullable'])) {
                    $nullable = $associationMetadata['inverseJoinColumns']['nullable'];
                }
            }

            switch ($type) {
                case 'datetime':
                    $builder->add($name, $type, array('required' => !$nullable, 'widget' => 'single_text'));
                    break;

                default:
                    $builder->add($name, null, array('required' => !$nullable));
                    break;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class
        ));
    }
}
