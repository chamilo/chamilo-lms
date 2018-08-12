<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\OptionsResolver;

if (!interface_exists('Symfony\Component\OptionsResolver\OptionsResolverInterface')) {
    /**
     * @deprecated since 3.9, to be removed in 4.0. Use \Symfony\Component\OptionsResolver\OptionsResolver instead.
     */
    interface OptionsResolverInterface
    {
    }
}
