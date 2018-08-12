<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\DependencyInjection;

use Sonata\EasyExtendsBundle\Mapper\DoctrineCollector;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author     Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class SonataUserExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('twig')) {
            // add custom form widgets
            $container->prependExtensionConfig('twig', ['form_themes' => ['SonataUserBundle:Form:form_admin_fields.html.twig']]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);
        $config = $this->fixImpersonating($config);

        $bundles = $container->getParameter('kernel.bundles');

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (isset($bundles['SonataAdminBundle'])) {
            $loader->load('admin.xml');
            $loader->load(sprintf('admin_%s.xml', $config['manager_type']));
        }

        $loader->load(sprintf('%s.xml', $config['manager_type']));

        $this->aliasManagers($container, $config['manager_type']);

        $loader->load('block.xml');
        $loader->load('menu.xml');
        $loader->load('form.xml');
        $loader->load('google_authenticator.xml');
        $loader->load('twig.xml');

        if ('orm' === $config['manager_type'] && isset(
            $bundles['FOSRestBundle'],
            $bundles['NelmioApiDocBundle'],
            $bundles['JMSSerializerBundle']
        )) {
            $loader->load('serializer.xml');

            $loader->load('api_form.xml');
            $loader->load('api_controllers.xml');
        }

        if (isset($bundles['SonataSeoBundle'])) {
            $loader->load('seo_block.xml');
        }

        if ($config['security_acl']) {
            $loader->load('security_acl.xml');
        }

        $config = $this->addDefaults($config);

        // Set the SecurityContext for Symfony <2.6
        // NEXT_MAJOR: Go back to simple xml configuration when bumping requirements to SF 2.6+
        if (interface_exists('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')) {
            $tokenStorageReference = new Reference('security.token_storage');
            $authorizationCheckerReference = new Reference('security.authorization_checker');
        } else {
            $tokenStorageReference = new Reference('security.context');
            $authorizationCheckerReference = new Reference('security.context');
        }

        // NEXT_MAJOR: Remove following lines.
        $profileFormDefinition = $container->getDefinition('sonata.user.profile.form');
        $registrationFormDefinition = $container->getDefinition('sonata.user.registration.form');
        if (method_exists($profileFormDefinition, 'setFactory')) {
            $profileFormDefinition->setFactory([new Reference('form.factory'), 'createNamed']);
            $registrationFormDefinition->setFactory([new Reference('form.factory'), 'createNamed']);
        } else {
            $profileFormDefinition->setFactoryClass(new Reference('form.factory'));
            $profileFormDefinition->setFactoryMethod('createNamed');
            $registrationFormDefinition->setFactoryClass(new Reference('form.factory'));
            $registrationFormDefinition->setFactoryMethod('createNamed');
        }

        if ($container->hasDefinition('sonata.user.editable_role_builder')) {
            $container
                ->getDefinition('sonata.user.editable_role_builder')
                ->replaceArgument(0, $tokenStorageReference)
                ->replaceArgument(1, $authorizationCheckerReference)
            ;
        }

        $container
            ->getDefinition('sonata.user.block.account')
            ->replaceArgument(2, $tokenStorageReference)
        ;

        $container
            ->getDefinition('sonata.user.google.authenticator.request_listener')
            ->replaceArgument(1, $tokenStorageReference)
        ;

        $this->registerDoctrineMapping($config);
        $this->configureAdminClass($config, $container);
        $this->configureClass($config, $container);

        $this->configureTranslationDomain($config, $container);
        $this->configureController($config, $container);

        $container->setParameter('sonata.user.default_avatar', $config['profile']['default_avatar']);

        $container->setParameter('sonata.user.impersonating', $config['impersonating']);

        $this->configureGoogleAuthenticator($config, $container);
        $this->configureShortcut($container);
        $this->configureProfile($config, $container);
        $this->configureRegistration($config, $container);
        $this->configureMenu($config, $container);
    }

    /**
     * @param array $config
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    public function fixImpersonating(array $config)
    {
        if (isset($config['impersonating']) && isset($config['impersonating_route'])) {
            throw new \RuntimeException('you can\'t have `impersonating` and `impersonating_route` keys defined at the same time');
        }

        if (isset($config['impersonating_route'])) {
            $config['impersonating'] = [
                'route' => $config['impersonating_route'],
                'parameters' => [],
            ];
        }

        if (!isset($config['impersonating']['parameters'])) {
            $config['impersonating']['parameters'] = [];
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
     *
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
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid manager type "%s".', $config['manager_type']));
        }

        $defaultConfig['class']['user'] = sprintf('Application\\Sonata\\UserBundle\\%s\\User', $modelType);
        $defaultConfig['class']['group'] = sprintf('Application\\Sonata\\UserBundle\\%s\\Group', $modelType);

        $defaultConfig['admin']['user']['class'] = sprintf('Sonata\\UserBundle\\Admin\\%s\\UserAdmin', $modelType);
        $defaultConfig['admin']['group']['class'] = sprintf('Sonata\\UserBundle\\Admin\\%s\\GroupAdmin', $modelType);

        return array_replace_recursive($defaultConfig, $config);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    public function configureClass($config, ContainerBuilder $container)
    {
        if ('orm' === $config['manager_type']) {
            $modelType = 'entity';
        } elseif ('mongodb' === $config['manager_type']) {
            $modelType = 'document';
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid manager type "%s".', $config['manager_type']));
        }

        $container->setParameter(sprintf('sonata.user.admin.user.%s', $modelType), $config['class']['user']);
        $container->setParameter(sprintf('sonata.user.admin.group.%s', $modelType), $config['class']['group']);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    public function configureAdminClass($config, ContainerBuilder $container)
    {
        $container->setParameter('sonata.user.admin.user.class', $config['admin']['user']['class']);
        $container->setParameter('sonata.user.admin.group.class', $config['admin']['group']['class']);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    public function configureTranslationDomain($config, ContainerBuilder $container)
    {
        $container->setParameter('sonata.user.admin.user.translation_domain', $config['admin']['user']['translation']);
        $container->setParameter('sonata.user.admin.group.translation_domain', $config['admin']['group']['translation']);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
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

        $collector->addAssociation($config['class']['user'], 'mapManyToMany', [
            'fieldName' => 'groups',
            'targetEntity' => $config['class']['group'],
            'cascade' => [],
            'joinTable' => [
                'name' => $config['table']['user_group'],
                'joinColumns' => [
                    [
                        'name' => 'user_id',
                        'referencedColumnName' => 'id',
                        'onDelete' => 'CASCADE',
                    ],
                ],
                'inverseJoinColumns' => [[
                    'name' => 'group_id',
                    'referencedColumnName' => 'id',
                    'onDelete' => 'CASCADE',
                ]],
            ],
        ]);
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
    public function configureRegistration(array $config, ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['MopaBootstrapBundle'])) {
            $options = [
                'horizontal_input_wrapper_class' => 'col-lg-8',
                'horizontal_label_class' => 'col-lg-4 control-label',
            ];
        } else {
            $options = [];
        }

        $container->setParameter('sonata.user.registration.form.options', $options);

        $container->setParameter('sonata.user.registration.form.type', $config['profile']['register']['form']['type']);
        $container->setParameter('sonata.user.registration.form.name', $config['profile']['register']['form']['name']);
        $container->setParameter('sonata.user.registration.form.validation_groups', $config['profile']['register']['form']['validation_groups']);

        $container->setAlias('sonata.user.registration.form.handler', $config['profile']['register']['form']['handler']);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    public function configureMenu(array $config, ContainerBuilder $container)
    {
        $container->getDefinition('sonata.user.profile.menu_builder')->replaceArgument(2, $config['profile']['menu']);
    }

    /**
     * Adds aliases for user & group managers depending on $managerType.
     *
     * @param ContainerBuilder $container
     * @param                  $managerType
     */
    protected function aliasManagers(ContainerBuilder $container, $managerType)
    {
        $container->setAlias('sonata.user.user_manager', sprintf('sonata.user.%s.user_manager', $managerType));
        $container->setAlias('sonata.user.group_manager', sprintf('sonata.user.%s.group_manager', $managerType));
    }
}
