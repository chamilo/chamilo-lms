<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle;

use Sonata\BlockBundle\DependencyInjection\Compiler\GlobalVariablesCompilerPass;
use Sonata\BlockBundle\DependencyInjection\Compiler\TweakCompilerPass;
use Sonata\CoreBundle\Form\FormHelper;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SonataBlockBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TweakCompilerPass());
        $container->addCompilerPass(new GlobalVariablesCompilerPass());

        $this->registerFormMapping();
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->registerFormMapping();
    }

    /**
     * Register form mapping information.
     */
    public function registerFormMapping()
    {
        FormHelper::registerFormTypeMapping([
            'sonata_block_service_choice' => 'Sonata\BlockBundle\Form\Type\ServiceListType',
            'sonata_type_container_template_choice' => 'Sonata\BlockBundle\Form\Type\ContainerTemplateType',
        ]);
    }
}
