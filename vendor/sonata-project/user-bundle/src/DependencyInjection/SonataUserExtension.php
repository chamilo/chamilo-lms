<?php

declare(strict_types=1);

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
use Sonata\UserBundle\Document\BaseGroup as DocumentGroup;
use Sonata\UserBundle\Document\BaseUser as DocumentUser;
use Sonata\UserBundle\Entity\BaseGroup as EntityGroup;
use Sonata\UserBundle\Entity\BaseUser as EntityUser;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author     Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class SonataUserExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('twig')) {
            // add custom form widgets
            $container->prependExtensionConfig('twig', ['form_themes' => ['SonataUserBundle:Form:form_admin_fields.html.twig']]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
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

        $loader->load('form.xml');

        if (class_exists('Google\Authenticator\GoogleAuthenticator')) {
            $loader->load('google_authenticator.xml');
        }

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

        if ($config['security_acl']) {
            $loader->load('security_acl.xml');
        }

        $this->checkManagerTypeToModelTypeMapping($config);

        $this->registerDoctrineMapping($config);
        $this->configureAdminClass($config, $container);
        $this->configureClass($config, $container);

        $this->configureTranslationDomain($config, $container);
        $this->configureController($config, $container);

        $container->setParameter('sonata.user.default_avatar', $config['profile']['default_avatar']);

        $container->setParameter('sonata.user.impersonating', $config['impersonating']);

        $this->configureGoogleAuthenticator($config, $container);
    }

    /**
     * @param array $config
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    public function fixImpersonating(array $config)
    {
        if (isset($config['impersonating'], $config['impersonating_route'])) {
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
     * @throws \RuntimeException
     *
     * @return mixed
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

        $container->setParameter('sonata.user.google.authenticator.forced_for_role', $config['google_authenticator']['forced_for_role']);
        $container->setParameter('sonata.user.google.authenticator.ip_white_list', $config['google_authenticator']['ip_white_list']);

        $container->getDefinition('sonata.user.google.authenticator.provider')
            ->replaceArgument(0, $config['google_authenticator']['server']);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    public function configureClass($config, ContainerBuilder $container): void
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
    public function configureAdminClass($config, ContainerBuilder $container): void
    {
        $container->setParameter('sonata.user.admin.user.class', $config['admin']['user']['class']);
        $container->setParameter('sonata.user.admin.group.class', $config['admin']['group']['class']);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    public function configureTranslationDomain($config, ContainerBuilder $container): void
    {
        $container->setParameter('sonata.user.admin.user.translation_domain', $config['admin']['user']['translation']);
        $container->setParameter('sonata.user.admin.group.translation_domain', $config['admin']['group']['translation']);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    public function configureController($config, ContainerBuilder $container): void
    {
        $container->setParameter('sonata.user.admin.user.controller', $config['admin']['user']['controller']);
        $container->setParameter('sonata.user.admin.group.controller', $config['admin']['group']['controller']);
    }

    /**
     * @param array $config
     */
    public function registerDoctrineMapping(array $config): void
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
     * Adds aliases for user & group managers depending on $managerType.
     *
     * @param ContainerBuilder $container
     * @param                  $managerType
     */
    protected function aliasManagers(ContainerBuilder $container, $managerType): void
    {
        $container->setAlias('sonata.user.user_manager', sprintf('sonata.user.%s.user_manager', $managerType));
        $container->setAlias('sonata.user.group_manager', sprintf('sonata.user.%s.group_manager', $managerType));
    }

    /**
     * @param array $config
     */
    private function checkManagerTypeToModelTypeMapping(array $config): void
    {
        $managerType = $config['manager_type'];

        $actualModelClasses = [
            $config['class']['user'],
            $config['class']['group'],
        ];

        if ('orm' === $managerType) {
            $expectedModelClasses = [
                EntityUser::class,
                EntityGroup::class,
            ];
        } elseif ('mongodb' === $managerType) {
            $expectedModelClasses = [
                DocumentUser::class,
                DocumentGroup::class,
            ];
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid manager type "%s".', $managerType));
        }

        foreach ($actualModelClasses as $index => $actualModelClass) {
            if ('\\' === substr($actualModelClass, 0, 1)) {
                $actualModelClass = substr($actualModelClass, 1);
            }

            $expectedModelClass = $expectedModelClasses[$index];

            if ($actualModelClass !== $expectedModelClass && !is_subclass_of($actualModelClass, $expectedModelClass)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Model class "%s" does not correspond to manager type "%s".',
                        $actualModelClass,
                        $managerType
                    )
                );
            }
        }
    }
}
