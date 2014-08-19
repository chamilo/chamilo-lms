<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;

use Chamilo\CourseBundle\DependencyInjection\Compiler\ToolCompilerClass;
use Chamilo\CourseBundle\DependencyInjection\Compiler\RegisterSchemasPass;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;

/**
 * Class ChamiloCourseBundle
 * @package Chamilo\CourseBundle
 */
class ChamiloCourseBundle extends AbstractResourceBundle
{
    /**
     * {@inheritdoc}
     */
    public static function getSupportedDrivers()
    {
        return array(
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getBundlePrefix()
    {
        return 'chamilo_course';
    }

    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ToolCompilerClass());
        $container->addCompilerPass(new RegisterSchemasPass());
    }
}
