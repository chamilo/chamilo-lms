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

class JsonWriter implements WriterInterface
{
    protected $filename;

    protected $file;

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
