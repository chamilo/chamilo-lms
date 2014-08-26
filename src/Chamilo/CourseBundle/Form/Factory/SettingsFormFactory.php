<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Form\Factory;

use Sylius\Bundle\SettingsBundle\Form\Factory\SettingsFormFactory as SyliusSettingsFormFactory;

/**
 * Class SettingsFormFactory
 * @package Chamilo\CourseBundle\Form\Factory
 */
class SettingsFormFactory extends SyliusSettingsFormFactory
{
    /**
     * {@inheritdoc}
     */
    public function create($namespace)
    {
        $schema = $this->schemaRegistry->getSchema($namespace);
        $builder = $this->formFactory->createBuilder('form', null, array('data_class' => null));

        $schema->buildForm($builder);

        return $builder->getForm();
    }
}
