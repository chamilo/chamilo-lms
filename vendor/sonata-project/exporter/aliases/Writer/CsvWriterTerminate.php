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

if (!class_exists('\Sonata\\'.__NAMESPACE__.'\CsvWriterTerminate', false)) {
    @trigger_error(
        'The '.__NAMESPACE__.'\CsvWriterTerminate class is deprecated since version 1.x and will be removed in 2.0.'
        .' Use \Sonata\\'.__NAMESPACE__.'\CsvWriterTerminate instead',
        E_USER_DEPRECATED
    );
}

class_alias(
    '\Sonata\\'.__NAMESPACE__.'\CsvWriterTerminate',
    __NAMESPACE__.'\CsvWriterTerminate'
);

if (false) {
    final class CsvWriterTerminate extends \Sonata\Exporter\Writer\CsvWriterTerminate
    {
    }
}
