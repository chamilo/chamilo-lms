<?php

/*
 * This file is part of the MopaBootstrapBundle.
 *
 * (c) Philipp A. Mohrenweiser <phiamo@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mopa\Bundle\BootstrapBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add a new twig.form.resources
 *
 * @author Philipp A. Mohrenweiser <phiamo@googlemail.com>
 */
class FormPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (($template = $container->getParameter('mopa_bootstrap.form.templating')) !== false) {
            $resources = $container->getParameter('twig.form.resources');
            // Ensure it wasnt already aded via config
            if (!in_array($template, $resources)) {
                // If form_div_layout.html.twig is found, insert Mopa right after
                if (($key = array_search('form_div_layout.html.twig', $resources)) !== false) {
                    array_splice($resources, ++$key, 0, $template);
                }
                // Else insert Mopa in first position
                else {
                    array_unshift($resources, $template);
                }

                $container->setParameter('twig.form.resources', $resources);
            }
        }
    }
}
