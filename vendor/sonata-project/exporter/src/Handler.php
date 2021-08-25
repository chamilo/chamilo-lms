<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Exporter;

use Sonata\Exporter\Source\SourceIteratorInterface;
use Sonata\Exporter\Writer\WriterInterface;

class Handler
{
    /**
     * @var SourceIteratorInterface
     */
    protected $source;

    /**
     * @var WriterInterface
     */
    protected $writer;

    /**
     * @param SourceIteratorInterface $source
     * @param WriterInterface         $writer
     */
    public function __construct(SourceIteratorInterface $source, WriterInterface $writer)
    {
        $this->source = $source;
        $this->writer = $writer;
    }

    public function export()
    {
        $this->writer->open();

        foreach ($this->source as $data) {
            $this->writer->write($data);
        }

        $this->writer->close();
    }

    /**
     * @param SourceIteratorInterface $source
     * @param WriterInterface         $writer
     *
     * @return Handler
     */
    public static function create(SourceIteratorInterface $source, WriterInterface $writer)
    {
        return new self($source, $writer);
    }
}

class_exists(\Exporter\Handler::class);
