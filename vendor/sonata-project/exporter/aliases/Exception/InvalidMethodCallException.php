<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Exception;

if (!class_exists('\Sonata\\'.__NAMESPACE__.'\InvalidMethodCallException', false)) {
    @trigger_error(
        'The '.__NAMESPACE__.'\InvalidMethodCallException class is deprecated since version 1.x and will be removed in 2.0.'
        .' Use \Sonata\\'.__NAMESPACE__.'\InvalidMethodCallException instead',
        E_USER_DEPRECATED
    );
}

class_alias(
    '\Sonata\\'.__NAMESPACE__.'\InvalidMethodCallException',
    __NAMESPACE__.'\InvalidMethodCallException'
);

if (false) {
    class InvalidMethodCallException extends \Sonata\Exporter\Exception\InvalidMethodCallException
    {
    }
}
