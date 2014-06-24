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

namespace JMS\DiExtraBundle\HttpKernel;

use Metadata\ClassHierarchyMetadata;
use JMS\DiExtraBundle\Metadata\ClassMetadata;
use CG\Core\DefaultNamingStrategy;
use CG\Proxy\Enhancer;
use JMS\AopBundle\DependencyInjection\Compiler\PointcutMatchingPass;
use JMS\DiExtraBundle\Generator\DefinitionInjectorGenerator;
use JMS\DiExtraBundle\Generator\LookupMethodClassGenerator;
use JMS\DiExtraBundle\DependencyInjection\Dumper\PhpDumper;
use Metadata\MetadataFactory;
use Symfony\Component\DependencyInjection\Compiler\InlineServiceDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\ConfigCache;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver as BaseControllerResolver;

class ControllerResolver extends BaseControllerResolver
{
    protected function createController($controller)
    {
        if (false === $pos = strpos($controller, '::')) {
            $count = substr_count($controller, ':');
            if (2 == $count) {
                // controller in the a:b:c notation then
                $controller = $this->parser->parse($controller);
                $pos = strpos($controller, '::');
            } elseif (1 == $count) {
                // controller in the service:method notation
                list($service, $method) = explode(':', $controller);

                return array($this->container->get($service), $method);
            } else {
                throw new \LogicException(sprintf('Unable to parse the controller name "%s".', $controller));
            }
        }

        $class = substr($controller, 0, $pos);
        $method = substr($controller, $pos+2);

        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }

        $injector = $this->createInjector($class);
        $controller = call_user_func($injector, $this->container);

        if ($controller instanceof ContainerAwareInterface) {
            $controller->setContainer($this->container);
        }

        return array($controller, $method);
    }

    public function createInjector($class)
    {
        $filename = $this->container->getParameter('jms_di_extra.cache_dir').'/controller_injectors/'.str_replace('\\', '', $class).'.php';
        $cache = new ConfigCache($filename, $this->container->getParameter('kernel.debug'));

        if (!$cache->isFresh()) {
            $metadata = $this->container->get('jms_di_extra.metadata.metadata_factory')->getMetadataForClass($class);
            if (null === $metadata) {
                $metadata = new ClassHierarchyMetadata();
                $metadata->addClassMetadata(new ClassMetadata($class));
            }

            // If the cache warmer tries to warm up a service controller that uses
            // annotations, we need to bail out as this is handled by the service
            // container directly.
            if (null !== $metadata->getOutsideClassMetadata()->id
                    && 0 !== strpos($metadata->getOutsideClassMetadata()->id, '_jms_di_extra.unnamed.service')) {
                return;
            }

            $this->prepareContainer($cache, $filename, $metadata, $class);
        }

        if ( ! class_exists($class.'__JMSInjector', false)) {
            require $filename;
        }

        return array($class.'__JMSInjector', 'inject');
    }

    private function prepareContainer($cache, $containerFilename, $metadata, $className)
    {
        $container = new ContainerBuilder();
        $container->setParameter('jms_aop.cache_dir', $this->container->getParameter('jms_di_extra.cache_dir'));
        $def = $container
            ->register('jms_aop.interceptor_loader', 'JMS\AopBundle\Aop\InterceptorLoader')
            ->addArgument(new Reference('service_container'))
            ->setPublic(false)
        ;

        // add resources
        $ref = $metadata->getOutsideClassMetadata()->reflection;
        while ($ref && false !== $filename = $ref->getFilename()) {
            $container->addResource(new FileResource($filename));
            $ref = $ref->getParentClass();
        }

        // add definitions
        $definitions = $this->container->get('jms_di_extra.metadata.converter')->convert($metadata);
        $serviceIds = $parameters = array();

        $controllerDef = array_pop($definitions);
        $container->setDefinition('controller', $controllerDef);

        foreach ($definitions as $id => $def) {
            $container->setDefinition($id, $def);
        }

        $this->generateLookupMethods($controllerDef, $metadata);

        $config = $container->getCompilerPassConfig();
        $config->setOptimizationPasses(array());
        $config->setRemovingPasses(array());
        $config->addPass(new ResolveDefinitionTemplatesPass());
        $config->addPass(new PointcutMatchingPass($this->container->get('jms_aop.pointcut_container')->getPointcuts()));
        $config->addPass(new InlineServiceDefinitionsPass());
        $container->compile();

        if (!file_exists($dir = dirname($containerFilename))) {
            if (false === @mkdir($dir, 0777, true)) {
                throw new \RuntimeException(sprintf('Could not create directory "%s".', $dir));
            }
        }

        static $generator;
        if (null === $generator) {
            $generator = new DefinitionInjectorGenerator();
        }

        $cache->write($generator->generate($container->getDefinition('controller'), $className), $container->getResources());
    }

    private function generateLookupMethods($def, $metadata)
    {
        $found = false;
        foreach ($metadata->classMetadata as $cMetadata) {
            if (!empty($cMetadata->lookupMethods)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return;
        }

        $generator = new LookupMethodClassGenerator($metadata);
        $outerClass = $metadata->getOutsideClassMetadata()->reflection;

        if ($file = $def->getFile()) {
            $generator->setRequiredFile($file);
        }

        $enhancer = new Enhancer(
            $outerClass,
            array(),
            array(
                $generator,
            )
        );

        $filename = $this->container->getParameter('jms_di_extra.cache_dir').'/lookup_method_classes/'.str_replace('\\', '-', $outerClass->name).'.php';
        $enhancer->writeClass($filename);

        $def->setFile($filename);
        $def->setClass($enhancer->getClassName($outerClass));
        $def->addMethodCall('__jmsDiExtra_setContainer', array(new Reference('service_container')));
    }
}
