<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Twig\Extension;

/**
 * NEXT_MAJOR : remove this class and the twig/extensions dependency.
 *
 * @deprecated since version 3.2, to be removed in 4.0.
 */
final class DeprecatedTextExtension extends \Twig_Extensions_Extension_Text
{
    public function twig_truncate_filter(\Twig_Environment $env, $value, $length = 30, $preserve = false, $separator = '...')
    {
        $this->notifyDeprecation();

        return parent::twig_truncate_filter($env, $value, $length, $preserve, $separator);
    }

    public function twig_wordwrap_filter(\Twig_Environment $env, $value, $length = 80, $separator = "\n", $preserve = false)
    {
        $this->notifyDeprecation();

        return parent::twig_wordwrap_filter($env, $value, $length, $separator, $preserve);
    }

    private function notifyDeprecation()
    {
        @trigger_error(
            'Using the sonata.core.twig.extension.text service is deprecated since 3.2 and will be removed in 4.0'
        );
    }
}
