<?php

/*
 * This file is part of the Pagerfanta package.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pagerfanta\View\Template;

/**
 * @author Pablo Díez <pablodip@gmail.com>
 */
interface TemplateInterface
{
    function container();

    function page($page);

    function pageWithText($page, $text);

    function previousDisabled();

    function previousEnabled($page);

    function nextDisabled();

    function nextEnabled($page);

    function first();

    function last($page);

    function current($page);

    function separator();
}