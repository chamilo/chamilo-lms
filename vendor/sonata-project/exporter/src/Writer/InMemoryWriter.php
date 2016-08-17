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

class InMemoryWriter implements WriterInterface
{
    /**
     * @var array
     */
    protected $elements;

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        $this->elements = array();
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return $this->elements;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data)
    {
        $this->elements[] = $data;
    }

    /**
     * @return array
     */
    public function getElements()
    {
        return $this->elements;
    }
}
