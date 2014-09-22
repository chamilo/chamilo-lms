<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chamilo\InstallerBundle\Process;

use Sylius\Bundle\FlowBundle\Process\Builder\ProcessBuilderInterface;
use Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class InstallerScenario extends ContainerAware implements ProcessScenarioInterface
{
    /**
     * {@inheritdoc}
     */
    public function build(ProcessBuilderInterface $builder)
    {
        $builder
            ->add('welcome', new Step\WelcomeStep())
            ->add('configure', new Step\ConfigureStep())
            ->add('schema', new Step\SchemaStep())
            ->add('setup', new Step\SetupStep())
            ->add('installation', new Step\InstallationStep())
            ->add('final', new Step\FinalStep())
            ->setRedirect('homepage')
        ;
    }
}
