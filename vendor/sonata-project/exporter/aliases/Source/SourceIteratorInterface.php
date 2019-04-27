<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Source;

if (!interface_exists('\Sonata\\'.__NAMESPACE__.'\SourceIteratorInterface', false)) {
    @trigger_error(
        'The '.__NAMESPACE__.'\SourceIteratorInterface class is deprecated since version 1.x and will be removed in 2.0.'
        .' Use \Sonata\\'.__NAMESPACE__.'\SourceIteratorInterface instead',
        E_USER_DEPRECATED
    );
}

class_alias(
    '\Sonata\\'.__NAMESPACE__.'\SourceIteratorInterface',
    __NAMESPACE__.'\SourceIteratorInterface'
);

if (false) {
    /**
     * @deprecated since version 1.x, to be removed in 2.0.
     */
    interface SourceIteratorInterface extends \Sonata\Exporter\Source\SourceIteratorInterface
    {
    }
}
