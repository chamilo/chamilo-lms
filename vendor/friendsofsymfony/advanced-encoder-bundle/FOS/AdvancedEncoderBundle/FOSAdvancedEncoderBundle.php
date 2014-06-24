<?php

/*
 * This file is part of the FOSAdvancedEncoderBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\AdvancedEncoderBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use FOS\AdvancedEncoderBundle\DependencyInjection\Compiler\SecurityEncoderFactoryPass;

class FOSAdvancedEncoderBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SecurityEncoderFactoryPass());
    }
}
