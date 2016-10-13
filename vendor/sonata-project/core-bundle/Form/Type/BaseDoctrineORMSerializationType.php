<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Metadata\MetadataFactoryInterface;
use Sonata\CoreBundle\Form\EventListener\FixCheckboxDataListener;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class DoctrineORMSerializationType.
 *
 * This is a doctrine serialization form type that generates a form type from class serialization metadata
 * and doctrine metadata
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class BaseDoctrineORMSerializationType extends AbstractType
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
    protected $name;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $group;

    /**
     * @var bool
     */
    protected $identifierOverwrite;

    /**
     * Constructor.
     *
     * @param MetadataFactoryInterface $metadataFactory     Serializer metadata factory
     * @param ManagerRegistry          $registry            Doctrine registry
     * @param string                   $name                Form type name
     * @param string                   $class               Data class name
     * @param string                   $group               Serialization group name
     * @param bool|false               $identifierOverwrite
     */
    public function __construct(MetadataFactoryInterface $metadataFactory, ManagerRegistry $registry, $name, $class, $group, $identifierOverwrite = false)
    {
        $this->metadataFactory = $metadataFactory;
        $this->registry = $registry;
        $this->name = $name;
        $this->class = $class;
        $this->group = $group;
        $this->identifierOverwrite = $identifierOverwrite;
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

            if (in_array($name, $doctrineMetadata->getIdentifierFieldNames(), true) && !$this->identifierOverwrite) {
                continue;
            }

            if (!$propertyMetadata->groups || !in_array($this->group, $propertyMetadata->groups, true)) {
                continue;
            }

            $type = null;
            $nullable = true;

            if (isset($doctrineMetadata->fieldMappings[$name])) {
                $fieldMetadata = $doctrineMetadata->fieldMappings[$name];
                $type = isset($fieldMetadata['type']) ? $fieldMetadata['type'] : null;
                $nullable = isset($fieldMetadata['nullable']) ? $fieldMetadata['nullable'] : false;
            } elseif (isset($doctrineMetadata->associationMappings[$name])) {
                $associationMetadata = $doctrineMetadata->associationMappings[$name];

                if (isset($associationMetadata['joinColumns']['nullable'])) {
                    $nullable = $associationMetadata['joinColumns']['nullable'];
                } elseif (isset($associationMetadata['inverseJoinColumns']['nullable'])) {
                    $nullable = $associationMetadata['inverseJoinColumns']['nullable'];
                }
            }
            switch ($type) {
                case 'datetime':
                    $builder->add(
                        $name,
                        // NEXT_MAJOR: Remove ternary and keep 'Symfony\Component\Form\Extension\Core\Type\DateTimeType'
                        // (when requirement of Symfony is >= 2.8)
                        method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
                            ? 'Symfony\Component\Form\Extension\Core\Type\DateTimeType'
                            : 'datetime',
                        array('required' => !$nullable, 'widget' => 'single_text')
                    );
                    break;

                case 'boolean':
                    $childBuilder = $builder->create($name, null, array('required' => !$nullable));
                    $childBuilder->addEventSubscriber(new FixCheckboxDataListener());
                    $builder->add($childBuilder);
                    break;

                default:
                    $builder->add($name, null, array('required' => !$nullable));
                    break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     *
     * @todo Remove it when bumping requirements to SF 2.7+
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class,
        ));
    }
}
