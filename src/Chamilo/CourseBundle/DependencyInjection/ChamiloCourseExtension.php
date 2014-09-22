<?php

namespace Chamilo\CourseBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Sylius\Bundle\ResourceBundle\DependencyInjection\AbstractResourceExtension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ChamiloCourseExtension extends AbstractResourceExtension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        list($config) = $this->configure($config, new Configuration(), $container, self::CONFIGURE_LOADER | self::CONFIGURE_DATABASE);

        $classes = $config['classes'];
        $parameterClasses = $classes['parameter'];

        if (isset($parameterClasses['model'])) {
            $container->setParameter('chamilo_course.model.parameter.class', $parameterClasses['model']);
        }

        if (isset($parameterClasses['repository'])) {
            $container->setParameter('chamilo_course.repository.parameter.class', $parameterClasses['repository']);
        }

        if ($container->hasParameter('chamilo_course.config.classes')) {
            $classes = array_merge($classes, $container->getParameter('chamilo_course.config.classes'));
        }

        $container->setParameter('chamilo_course.config.classes', $classes);

    }
}
