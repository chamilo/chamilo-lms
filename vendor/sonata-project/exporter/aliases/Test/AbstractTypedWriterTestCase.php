<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Test;

if (!class_exists('\Sonata\\'.__NAMESPACE__.'\AbstractTypedWriterTestCase', false)) {
    @trigger_error(
        'The '.__NAMESPACE__.'\AbstractTypedWriterTestCase class is deprecated since version 1.x and will be removed in 2.0.'
        .' Use \Sonata\\'.__NAMESPACE__.'\AbstractTypedWriterTestCase instead',
        E_USER_DEPRECATED
    );
}

class_alias(
    '\Sonata\\'.__NAMESPACE__.'\AbstractTypedWriterTestCase',
    __NAMESPACE__.'\AbstractTypedWriterTestCase'
);

if (false) {
    /**
     * @deprecated since version 1.x, to be removed in 2.0.
     */
    abstract class AbstractTypedWriterTestCase extends \Sonata\Exporter\Test\AbstractTypedWriterTestCase
    {
    }
}
