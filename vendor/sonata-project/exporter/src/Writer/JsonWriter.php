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
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class JsonWriter implements TypedWriterInterface
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
     * @param string $filename
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->position = 0;

        if (is_file($filename)) {
            throw new \RuntimeException(sprintf('The file %s already exist', $filename));
        }
    }

    /**
     * {@inheritdoc}
     */
    final public function getDefaultMimeType()
    {
        return 'application/json';
    }

    /**
     * {@inheritdoc}
     */
    final public function getFormat()
    {
        return 'json';
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        $this->file = fopen($this->filename, 'w', false);

        fwrite($this->file, '[');
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        fwrite($this->file, ']');

        fclose($this->file);
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data)
    {
        fwrite($this->file, ($this->position > 0 ? ',' : '').json_encode($data));

        ++$this->position;
    }
}
