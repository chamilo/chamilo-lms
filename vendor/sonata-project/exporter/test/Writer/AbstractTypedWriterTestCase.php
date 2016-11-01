<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Test\Writer;

use Exporter\Test\AbstractTypedWriterTestCase as BaseTestCase;

@trigger_error(
    'The '.__NAMESPACE__.'\AbstractTypedWriterTestCase class is deprecated since version 1.6 and will be removed in 2.0.'
    .' Use Exporter\Test\AbstractTypedWriterTestCase instead.',
    E_USER_DEPRECATED
);

/**
 * @author Grégoire Paris <postmaster@greg0ire.fr>
 *
 * @deprecated Deprecated since version 1.6. Use Exporter\Test\AbstractTypedWriterTestCase instead
 */
abstract class AbstractTypedWriterTestCase extends BaseTestCase
{
}
