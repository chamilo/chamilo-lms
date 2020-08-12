<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Exporter\Writer;

use Sonata\Exporter\Exception\SonataExporterException;

interface WriterInterface
{
    /**
     * @throws SonataExporterException
     */
    public function open();

    /**
     * @throws SonataExporterException
     */
    public function write(array $data);

    public function close();
}
