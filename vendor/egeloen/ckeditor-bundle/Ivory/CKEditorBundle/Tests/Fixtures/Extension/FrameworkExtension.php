<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Tests\Fixtures\Extension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Fixtures framework extension.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class FrameworkExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $container->setParameter('templating.engines', array('php', 'twig'));
        $container->setParameter('templating.helper.form.resources', array());
        $container->setParameter('twig.form.resources', array());
    }
}
