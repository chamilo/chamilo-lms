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

namespace JMS\DiExtraBundle;

use JMS\DiExtraBundle\DependencyInjection\Compiler\ResourceOptimizationPass;
use JMS\DiExtraBundle\DependencyInjection\Compiler\IntegrationPass;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use JMS\DiExtraBundle\DependencyInjection\Compiler\AnnotationConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class JMSDiExtraBundle extends Bundle
{
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function build(ContainerBuilder $container)
    {
        $config = $container->getCompiler()->getPassConfig();
        $passes = $config->getBeforeOptimizationPasses();
        array_unshift($passes, new AnnotationConfigurationPass($this->kernel));
        $config->setBeforeOptimizationPasses($passes);

        $container->addCompilerPass(new IntegrationPass());
        $container->addCompilerPass(new ResourceOptimizationPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
}
