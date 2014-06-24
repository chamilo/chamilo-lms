<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Writer;

interface WriterInterface
{
    /**
     * @return void
     */
    public function open();

    /**
     * @param array $data
     *
     * @return void
     */
    public function write(array $data);

    /**
     * @return void
     */
    public function close();
}
