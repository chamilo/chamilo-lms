<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\DiExtraBundle\DependencyInjection;

use CG\Core\DefaultNamingStrategy;
use CG\Proxy\Enhancer;
use JMS\DiExtraBundle\Exception\RuntimeException;
use JMS\DiExtraBundle\Generator\RepositoryInjectionGenerator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class JMSDiExtraExtension extends Extension
{
    /**
     * Controller blacklist, ie. php files names for controllers that should not be analyzed
     *
     * @var array
     */
    private $blackListedControllerFiles = array();

    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->mergeConfigs($configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('jms_di_extra.all_bundles', $config['locations']['all_bundles']);
        $container->setParameter('jms_di_extra.bundles', $config['locations']['bundles']);
        $container->setParameter('jms_di_extra.directories', $config['locations']['directories']);
        $container->setParameter('jms_di_extra.cache_dir', $config['cache_dir']);
        $container->setParameter('jms_di_extra.disable_grep', $config['disable_grep']);
        $container->setParameter('jms_di_extra.doctrine_integration', $config['doctrine_integration']);
        if ($config['cache_warmer']['enabled']) {
            foreach ($config['cache_warmer']['controller_file_blacklist'] as $filename) {
                $this->blackListControllerFile($filename);
            }
            $container->setParameter('jms_di_extra.cache_warmer.controller_file_blacklist', $this->blackListedControllerFiles);
        } else {
            $container->removeDefinition('jms_di_extra.controller_injectors_warmer');
        }

        $this->configureMetadata($config['metadata'], $container, $config['cache_dir'].'/metadata');
        $this->configureAutomaticControllerInjections($config, $container);

        if ($config['doctrine_integration']) {
            $this->generateEntityManagerProxyClass($config, $container);
        }

        $this->addClassesToCompile(array(
            'JMS\\DiExtraBundle\\HttpKernel\ControllerResolver',
        ));
    }

    public function blackListControllerFile($filename)
    {
        $this->blackListedControllerFiles[] = realpath($filename);
    }

    private function generateEntityManagerProxyClass(array $config, ContainerBuilder $container)
    {
        $cacheDir = $container->getParameterBag()->resolveValue($config['cache_dir']);

        if (!is_dir($cacheDir.'/doctrine')) {
            if (false === @mkdir($cacheDir.'/doctrine', 0777, true)) {
                throw new \RuntimeException(sprintf('Could not create cache directory "%s".', $cacheDir.'/doctrine'));
            }
        }

        $enhancer = new Enhancer($ref = new \ReflectionClass('Doctrine\ORM\EntityManager'), array(), array(new RepositoryInjectionGenerator()));
        $uniqid = uniqid(); // We do have to use a non-static id to avoid problems with cache:clear.
        if (strtoupper(PHP_OS)=='CYGWIN') {
            $uniqid=preg_replace('/\./','_',$uniqid); // replace dot; cygwin always generates uniqid's with more_entropy
        }
        $enhancer->setNamingStrategy(new DefaultNamingStrategy('EntityManager'.$uniqid));
        $enhancer->writeClass($file = $cacheDir.'/doctrine/EntityManager_'.$uniqid.'.php');
        $container->setParameter('jms_di_extra.doctrine_integration.entity_manager.file', $file);
        $container->setParameter('jms_di_extra.doctrine_integration.entity_manager.class', $enhancer->getClassName($ref));
    }

    private function configureAutomaticControllerInjections(array $config, ContainerBuilder $container)
    {
        if (!isset($config['automatic_controller_injections'])) {
            $container->setAlias('jms_di_extra.metadata_driver', 'jms_di_extra.metadata.driver.annotation_driver');

            return;
        }

        $propertyInjections = array();
        foreach ($config['automatic_controller_injections']['properties'] as $name => $value) {
            $propertyInjections[$name] = $this->convertValue($value);
        }

        $methodInjections = array();
        foreach ($config['automatic_controller_injections']['method_calls'] as $name => $args) {
            foreach ($args as $i => $arg) {
                $args[$i] = $this->convertValue($arg);
            }

            $methodInjections[$name] = $args;
        }

        $container->getDefinition('jms_di_extra.metadata.driver.configured_controller_injections')
            ->addArgument($propertyInjections)
            ->addArgument($methodInjections);
    }

    private function convertValue($value)
    {
        if (is_string($value) && '@' === $value[0]) {
            $def = new Definition('Symfony\Component\DependencyInjection\Reference');
            $def->addArgument(substr($value, 1));

            return $def;
        }

        return $value;
    }

    private function configureMetadata(array $config, $container, $cacheDir)
    {
        if ('none' === $config['cache']) {
            $container->removeAlias('jms_di_extra.metadata.cache');
            return;
        }

        if ('file' === $config['cache']) {
            $cacheDir = $container->getParameterBag()->resolveValue($cacheDir);

            // clear the cache if container is re-build, needed for correct controller injections
            $fs = new Filesystem();
            $fs->remove($cacheDir);

            if (!file_exists($cacheDir)) {
                if (false === @mkdir($cacheDir, 0777, true)) {
                    throw new RuntimeException(sprintf('The cache dir "%s" could not be created.', $cacheDir));
                }
            }
            if (!is_writable($cacheDir)) {
                throw new RuntimeException(sprintf('The cache dir "%s" is not writable.', $cacheDir));
            }

            $container
                ->getDefinition('jms_di_extra.metadata.cache.file_cache')
                ->replaceArgument(0, $cacheDir)
            ;
        } else {
            $container->setAlias('jms_di_extra.metadata.cache', new Alias($config['cache'], false));
        }
    }

    private function mergeConfigs(array $configs)
    {
        $processor = new Processor();
        $configuration = new Configuration();

        return $processor->process($configuration->getConfigTreeBuilder()->buildTree(), $configs);
    }
}
