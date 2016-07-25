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

use Exporter\Exception\InvalidDataFormatException;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class XmlWriter implements TypedWriterInterface
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var resource
     */
    protected $file;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var string
     */
    protected $mainElement;

    /**
     * @var string
     */
    protected $childElement;

    /**
     * @param string $filename
     * @param string $mainElement
     * @param string $childElement
     */
    public function __construct($filename, $mainElement = 'datas', $childElement = 'data')
    {
        $this->filename = $filename;
        $this->position = 0;
        $this->mainElement = $mainElement;
        $this->childElement = $childElement;

        if (is_file($filename)) {
            throw new \RuntimeException(sprintf('The file %s already exist', $filename));
        }
    }

    /**
     * {@inheritdoc}
     */
    final public function getDefaultMimeType()
    {
        return 'text/xml';
    }

    /**
     * {@inheritdoc}
     */
    final public function getFormat()
    {
        return 'xml';
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        $this->file = fopen($this->filename, 'w', false);

        fwrite($this->file, sprintf("<?xml version=\"1.0\" ?>\n<%s>\n", $this->mainElement));
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        fwrite($this->file, sprintf('</%s>', $this->mainElement));

        fclose($this->file);
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data)
    {
        fwrite($this->file, sprintf("<%s>\n", $this->childElement));

        foreach ($data as $k => $v) {
            $this->generateNode($k, $v);
        }

        fwrite($this->file, sprintf("</%s>\n", $this->childElement));
    }

    /**
     * @param string $name
     * @param string $value
     */
    protected function generateNode($name, $value)
    {
        if (is_array($value)) {
            throw new \RuntimeException('Not implemented');
        } elseif (is_scalar($value) || is_null($value)) {
            fwrite($this->file, sprintf("<%s><![CDATA[%s]]></%s>\n", $name, $value, $name));
        } else {
            throw new InvalidDataFormatException('Invalid data');
        }
    }
}
