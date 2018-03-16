<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\DependencyInjection;

//use Sylius\Bundle\ResourceBundle\DependencyInjection\AbstractResourceExtension; old
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ChamiloCourseExtension extends Extension
{
    // You can choose your application name, it will use to prefix the configuration keys in the container (the default value is sylius).
    protected $applicationName = 'chamilo_course';

    // You can define where yours service definitions are
    protected $configDirectory = '/../Resources/config';

    // You can define what service definitions you want to load
    protected $configFiles = [
        'services.yml',
        'forms.yml',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        //$loader->load('services.yml');
        $loader->load('admin.yml');

        //self::CONFIGURE_VALIDATORS

        /*$this->configure(
            $config,
            new Configuration(),
            $container,
            self::CONFIGURE_LOADER | self::CONFIGURE_DATABASE | self::CONFIGURE_PARAMETERS | self::CONFIGURE_FORMS
        );*/
    }
}
