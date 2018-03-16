<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle;

use Chamilo\CourseBundle\DependencyInjection\Compiler\RegisterSchemasPass;
use Chamilo\CourseBundle\DependencyInjection\Compiler\ToolCompilerClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ChamiloCourseBundle.
 *
 * @package Chamilo\CourseBundle
 */
class ChamiloCourseBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public static function getSupportedDrivers()
    {
        return [
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ToolCompilerClass());
        $container->addCompilerPass(new RegisterSchemasPass());
    }

    /**
     * {@inheritdoc}
     */
    protected function getBundlePrefix()
    {
        return 'chamilo_course';
    }
}
