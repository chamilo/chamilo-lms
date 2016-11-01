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

/**
 * Format boolean before use another writer.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class FormattedBoolWriter implements WriterInterface
{
    /**
     * @var WriterInterface
     */
    protected $writer;

    /**
     * @var string
     */
    protected $trueLabel;

    /**
     * @var string
     */
    protected $falseLabel;

    /**
     * @param WriterInterface $writer
     * @param string          $falseLabel
     * @param string          $trueLabel
     */
    public function __construct(WriterInterface $writer, $trueLabel = 'yes', $falseLabel = 'no')
    {
        $this->writer = $writer;
        $this->trueLabel = $trueLabel;
        $this->falseLabel = $falseLabel;
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        $this->writer->open();
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->writer->close();
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data)
    {
        foreach ($data as $key => $value) {
            if (is_bool($data[$key])) {
                $data[$key] = $data[$key] === true ? $this->trueLabel : $this->falseLabel;
            }
        }
        $this->writer->write($data);
    }
}
