<?php

/*
 * This file is part of the MopaBootstrapBundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mopa\Bundle\BootstrapBundle\Twig;

/**
 * Add new twig functions related to forms
 *
 * @author Paweł Madej (nysander) <pawel.madej@profarmaceuta.pl>
 * @author Charles Sanquer <charles.sanquer@gmail.com>
 */
class FormExtension extends \Twig_Extension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'form_help' => new \Twig_Function_Node('Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode', array('is_safe' => array('html'))),
            'form_tabs' => new \Twig_Function_Node('Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode', array('is_safe' => array('html'))),
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'bootstrap_form';
    }
}
