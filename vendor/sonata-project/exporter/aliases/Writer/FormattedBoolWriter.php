<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Writer;

if (!class_exists('\Sonata\\'.__NAMESPACE__.'\FormattedBoolWriter', false)) {
    @trigger_error(
        'The '.__NAMESPACE__.'\FormattedBoolWriter class is deprecated since version 1.x and will be removed in 2.0.'
        .' Use \Sonata\\'.__NAMESPACE__.'\FormattedBoolWriter instead',
        E_USER_DEPRECATED
    );
}
class_alias(
    '\Sonata\\'.__NAMESPACE__.'\FormattedBoolWriter',
    __NAMESPACE__.'\FormattedBoolWriter'
);

if (false) {
    class FormattedBoolWriter extends \Sonata\Exporter\Writer\FormattedBoolWriter
    {
    }
}
