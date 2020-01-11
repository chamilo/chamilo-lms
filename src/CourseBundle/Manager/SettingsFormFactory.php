<?php

namespace Chamilo\CourseBundle\Manager;

use Sylius\Bundle\SettingsBundle\Form\Factory\SettingsFormFactoryInterface;
use Sylius\Bundle\SettingsBundle\Schema\SchemaFormOptionsInterface;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;

final class SettingsFormFactory implements SettingsFormFactoryInterface
{
    /**
     * @var ServiceRegistryInterface
     */
    private $schemaRegistry;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    public function __construct(ServiceRegistryInterface $schemaRegistry, FormFactoryInterface $formFactory)
    {
        $this->schemaRegistry = $schemaRegistry;
        $this->formFactory = $formFactory;
    }

    public function create($schemaAlias, $data = null, array $options = [])
    {
        /** @var SchemaInterface $schema */
        $schema = $this->schemaRegistry->get($schemaAlias);

        if ($schema instanceof SchemaFormOptionsInterface) {
            $options = array_merge($schema->getOptions(), $options);
        }

        $builder = $this->formFactory->createBuilder(
            FormType::class,
            $data,
            array_merge_recursive(
                ['data_class' => null],
                $options
            )
        );

        $schema->buildForm($builder);

        return $builder->getForm();
    }
}
