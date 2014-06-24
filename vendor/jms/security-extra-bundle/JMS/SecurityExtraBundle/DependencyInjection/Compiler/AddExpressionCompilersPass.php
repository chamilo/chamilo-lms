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

namespace JMS\SecurityExtraBundle\DependencyInjection\Compiler;

use JMS\SecurityExtraBundle\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AddExpressionCompilersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('security.expressions.compiler')) {
            return;
        }

        $count = 0;
        foreach ($container->findTaggedServiceIds('security.expressions.function_evaluator') as $id => $tags) {
            foreach ($tags as $attributes) {
                if ( ! isset($attributes['function'])) {
                    throw new RuntimeException(sprintf('"function" must be given for tag "security.expressions.function_evaluator" of service "%s".', $id));
                }

                $container
                    ->register('security.expressions.service_callback_compiler.'.($count++),
                                   'JMS\SecurityExtraBundle\Security\Authorization\Expression\Compiler\Func\ServiceCallbackFunctionCompiler')
                    ->addArgument($attributes['function'])
                    ->addArgument($id)
                    ->addArgument(isset($attributes['method']) ? $attributes['method'] : $attributes['function'])
                    ->addTag('security.expressions.function_compiler');
            }
        }

        $compilerDef = $container->getDefinition('security.expressions.compiler');
        foreach ($container->findTaggedServiceIds('security.expressions.function_compiler')
            as $id => $attr) {
            $compilerDef->addMethodCall('addFunctionCompiler', array(new Reference($id)));
        }

        foreach ($container->findTaggedServiceIds('security.expressions.type_compiler')
            as $id => $attr) {
            $compilerDef->addMethodCall('addTypeCompiler', array(new Reference($id)));
        }

        $serviceMap = $parameterMap = array();
        foreach ($container->findTaggedServiceIds('security.expressions.variable') as $id => $attributes) {
            foreach ($attributes as $attr) {
                if (!isset($attr['variable']) || (!isset($attr['service']) && !isset($attr['parameter']))) {
                    throw new RuntimeException(sprintf('"variable", and either "service" or "parameter" must be given for tag "security.expressions.variable" for service id "%s".', $id));
                }

                if (isset($attr['service'])) {
                    $serviceMap[$attr['variable']] = $attr['service'];
                    $container
                        ->findDefinition($attr['service'])
                        ->setPublic(true)
                    ;
                } else {
                    $parameterMap[$attr['variable']] = $attr['parameter'];
                }
            }
        }
        $container->getDefinition('security.expressions.variable_compiler')
            ->addMethodCall('setMaps', array($serviceMap, $parameterMap));
    }
}
