<?php

/*
 * This file is part of the Pagerfanta package.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pagerfanta\View;

use Pagerfanta\PagerfantaInterface;

/**
 * ViewInterface.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 */
interface ViewInterface
{
    /**
     * Renders a pagerfanta.
     *
     * The route generator can be any callable to generate
     * the routes receiving the page number as first and
     * unique argument.
     *
     * @param PagerfantaInterface $pagerfanta     A pagerfanta.
     * @param mixed               $routeGenerator A callable to generate the routes.
     * @param array               $options        An array of options (optional).
     */
    function render(PagerfantaInterface $pagerfanta, $routeGenerator, array $options = array());

    /**
     * Returns the canonical name.
     *
     * @return string The canonical name.
     */
    function getName();

}
