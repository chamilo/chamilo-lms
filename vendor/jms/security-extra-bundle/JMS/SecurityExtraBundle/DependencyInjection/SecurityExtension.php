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

namespace JMS\SecurityExtraBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension as BaseSecurityExtension;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Enhances the access_control section of the SecurityBundle.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SecurityExtension extends Extension
{
    private $extension;

    public function __construct(BaseSecurityExtension $extension)
    {
        $this->extension = $extension;
    }

    public function getAlias()
    {
        return $this->extension->getAlias();
    }

    public function getNamespace()
    {
        return $this->extension->getNamespace();
    }

    public function getXsdValidationBasePath()
    {
        return $this->extension->getXsdValidationBasePath();
    }

    public function getClassesToCompile()
    {
        return array_merge(parent::getClassesToCompile(), $this->extension->getClassesToCompile());
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $parentConfigs = array();

        foreach ($configs as $config) {
            if (isset($config['rule'])) {
                unset($config['rule']);
            }
            if (isset($config['access_control'])) {
                unset($config['access_control']);
            }

            $parentConfigs[] = $config;
        }
        $this->extension->load($parentConfigs, $container);

        $config = $this->processConfiguration(new AccessControlConfiguration(), $configs);
        $this->createAuthorization($config, $container);
    }

    public function __call($method, array $args)
    {
        return call_user_func_array(array($this->extension, $method), $args);
    }

    private function createAuthorization($config, ContainerBuilder $container)
    {
        if (!$config['access_control']) {
            return;
        }

        $this->addClassesToCompile(array(
            'Symfony\\Component\\Security\\Http\\AccessMap',
        ));

        foreach ($config['access_control'] as $access) {
            $matcher = $this->invokeParent('createRequestMatcher', array(
                $container,
                $access['path'],
                $access['host'],
                count($access['methods']) === 0 ? null : $access['methods'],
                $access['ip']
            ));

            if (isset($access['roles'])) {
                $attributes = $access['roles'];
            } else {
                $def = new DefinitionDecorator('security.expressions.expression');
                $def->addArgument($access['access']);
                $container->setDefinition($exprId = 'security.expressions.expression.'.sha1($access['access']), $def);

                $attributes = array(new Reference($exprId));
            }

            $container->getDefinition('security.access_map')
                ->addMethodCall('add', array($matcher, $attributes, $access['requires_channel']));
        }
    }

    private function invokeParent($method, array $args = array())
    {
        $ref = new \ReflectionMethod($this->extension, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($this->extension, $args);
    }
}
