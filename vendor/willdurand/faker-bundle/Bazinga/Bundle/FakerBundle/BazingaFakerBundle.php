<?php

/**
 * This file is part of the FakerBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Bazinga\Bundle\FakerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Bazinga\Bundle\FakerBundle\DependencyInjection\Compiler\AddProvidersPass;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class BazingaFakerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddProvidersPass());
    }
}
