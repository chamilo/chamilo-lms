<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

use Sonata\EasyExtendsBundle\Mapper\DoctrineCollector;

/**
 *
 * @author     Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class SonataUserExtension extends Extension
{

    /**
     *
     * @param array            $configs   An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);
        $config = $this->fixImpersonating($config);

        $bundles = $container->getParameter('kernel.bundles');

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (isset($bundles['FOSRestBundle']) && isset($bundles['NelmioApiDocBundle'])) {
            $loader->load('api_controllers.xml');
        }

        if (isset($bundles['SonataAdminBundle'])) {
            $loader->load('admin.xml');
            $loader->load(sprintf('admin_%s.xml', $config['manager_type']));
        }

        $loader->load('block.xml');
        $loader->load('menu.xml');
        $loader->load('orm.xml');
        $loader->load('form.xml');
        $loader->load('google_authenticator.xml');
        $loader->load('twig.xml');

        if (isset($bundles['SonataSeoBundle'])) {
            $loader->load('seo_block.xml');
        }

        if ($config['security_acl']) {
            $loader->load('security_acl.xml');
        }

        $config = $this->addDefaults($config);

        $this->registerDoctrineMapping($config);
        $this->configureAdminClass($config, $container);
        $this->configureClass($config, $container);

        $this->configureTranslationDomain($config, $container);
        $this->configureController($config, $container);

        // add custom form widgets
        $container->setParameter('twig.form.resources', array_merge(
            $container->getParameter('twig.form.resources'),
            array('SonataUserBundle:Form:form_admin_fields.html.twig')
        ));

        $container->setParameter('sonata.user.impersonating', $config['impersonating']);

        $this->configureGoogleAuthenticator($config, $container);
        $this->configureShortcut($container);
        $this->configureProfile($config, $container);
        $this->configureMenu($config, $container);
    }

    /**
     * @param array $config
     *
     * @return array
     * @throws \RuntimeException
     */
    public function fixImpersonating(array $config)
    {
        if (isset($config['impersonating']) && isset($config['impersonating_route'])) {
            throw new \RuntimeException('you can\'t have `impersonating` and `impersonating_route` keys defined at the same time');
        }

        if (isset($config['impersonating_route'])) {
            $config['impersonating'] = array(
                'route' =>  $config['impersonating_route'],
                'parameters' => array()
            );
        }

        if (!isset($config['impersonating']['parameters'])) {
            $config['impersonating']['parameters'] = array();
        }

        if (!isset($config['impersonating']['route'])) {
            $config['impersonating'] = false;
        }

        return $config;
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     *
     * @return mixed
     * @throws \RuntimeException
     */
    public function configureGoogleAuthenticator($config, ContainerBuilder $container)
    {
        $container->setParameter('sonata.user.google.authenticator.enabled', $config['google_authenticator']['enabled']);

        if (!$config['google_authenticator']['enabled']) {
            $container->removeDefinition('sonata.user.google.authenticator');
            $container->removeDefinition('sonata.user.google.authenticator.provider');
            $container->removeDefinition('sonata.user.google.authenticator.interactive_login_listener');
            $container->removeDefinition('sonata.user.google.authenticator.request_listener');

            return;
        }

        if (!class_exists('Google\Authenticator\GoogleAuthenticator')) {
            throw new \RuntimeException('Please add ``sonata-project/google-authenticator`` package');
        }

        $container->getDefinition('sonata.user.google.authenticator.provider')
            ->replaceArgument(0, $config['google_authenticator']['server']);

    }

    /**
     * @param array $config
     *
     * @return array
     */
    public function addDefaults(array $config)
    {
        if ('orm' === $config['manager_type']) {
            $modelType = 'Entity';
        } elseif ('mongodb' === $config['manager_type']) {
            $modelType = 'Document';
        }

        $defaultConfig['class']['user']  = sprintf('Application\\Sonata\\UserBundle\\%s\\User', $modelType);
        $defaultConfig['class']['group'] = sprintf('Application\\Sonata\\UserBundle\\%s\\Group', $modelType);

        $defaultConfig['admin']['user']['class']  = sprintf('Sonata\\UserBundle\\Admin\\%s\\UserAdmin', $modelType);
        $defaultConfig['admin']['group']['class'] = sprintf('Sonata\\UserBundle\\Admin\\%s\\GroupAdmin', $modelType);

        return array_replace_recursive($defaultConfig, $config);
    }

    /**
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    public function configureClass($config, ContainerBuilder $container)
    {
        if ('orm' === $config['manager_type']) {
            $modelType = 'entity';
        } elseif ('mongodb' === $config['manager_type']) {
            $modelType = 'document';
        }

        $container->setParameter(sprintf('sonata.user.admin.user.%s', $modelType), $config['class']['user']);
        $container->setParameter(sprintf('sonata.user.admin.group.%s', $modelType), $config['class']['group']);
    }

    /**
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    public function configureAdminClass($config, ContainerBuilder $container)
    {
        $container->setParameter('sonata.user.admin.user.class', $config['admin']['user']['class']);
        $container->setParameter('sonata.user.admin.group.class', $config['admin']['group']['class']);
    }

    /**
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    public function configureTranslationDomain($config, ContainerBuilder $container)
    {
        $container->setParameter('sonata.user.admin.user.translation_domain', $config['admin']['user']['translation']);
        $container->setParameter('sonata.user.admin.group.translation_domain', $config['admin']['group']['translation']);
    }

    /**
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    public function configureController($config, ContainerBuilder $container)
    {
        $container->setParameter('sonata.user.admin.user.controller', $config['admin']['user']['controller']);
        $container->setParameter('sonata.user.admin.group.controller', $config['admin']['group']['controller']);
    }

    /**
     * @param array $config
     */
    public function registerDoctrineMapping(array $config)
    {
        foreach ($config['class'] as $type => $class) {
            if (!class_exists($class)) {
                return;
            }
        }

        $collector = DoctrineCollector::getInstance();

        $collector->addAssociation($config['class']['user'], 'mapManyToMany', array(
            'fieldName'       => 'groups',
            'targetEntity'    => $config['class']['group'],
            'cascade'         => array( ),
            'joinTable'       => array(
                'name' => $config['table']['user_group'],
                'joinColumns' => array(
                    array(
                        'name' => 'user_id',
                        'referencedColumnName' => 'id',
                        'onDelete' => 'CASCADE'
                    ),
                ),
                'inverseJoinColumns' => array( array(
                    'name' => 'group_id',
                    'referencedColumnName' => 'id',
                    'onDelete' => 'CASCADE'
                )),
            )
        ));
    }

    /**
     * @param ContainerBuilder $container
     */
    public function configureShortcut(ContainerBuilder $container)
    {
        $container->setAlias('sonata.user.authentication.form', 'fos_user.profile.form');
        $container->setAlias('sonata.user.authentication.form_handler', 'fos_user.profile.form.handler');
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    public function configureProfile(array $config, ContainerBuilder $container)
    {
        $container->setParameter('sonata.user.profile.form.type', $config['profile']['form']['type']);
        $container->setParameter('sonata.user.profile.form.name', $config['profile']['form']['name']);
        $container->setParameter('sonata.user.profile.form.validation_groups', $config['profile']['form']['validation_groups']);

        $container->setParameter('sonata.user.register.confirm.redirect_route', $config['profile']['register']['confirm']['redirect']['route']);
        $container->setParameter('sonata.user.register.confirm.redirect_route_params', $config['profile']['register']['confirm']['redirect']['route_parameters']);

        $container->setParameter('sonata.user.configuration.profile_blocks', $config['profile']['dashboard']['blocks']);

        $container->setAlias('sonata.user.profile.form.handler', $config['profile']['form']['handler']);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    public function configureMenu(array $config, ContainerBuilder $container)
    {
        $container->getDefinition('sonata.user.profile.menu_builder')->replaceArgument(2, $config['profile']['menu']);
    }
}
