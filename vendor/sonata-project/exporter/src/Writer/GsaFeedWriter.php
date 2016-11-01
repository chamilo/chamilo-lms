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
 * Generates a GSA feed.
 *
 * @author Rémi Marseille <marseille@ekino.com>
 */
class GsaFeedWriter implements WriterInterface
{
    const LIMIT_SIZE = 31457280; // 30MB

    /**
     * @var \SplFileInfo
     */
    private $folder;

    /**
     * @var string
     */
    private $dtd;

    /**
     * @var string
     */
    private $datasource;

    /**
     * @var string
     */
    private $feedtype;

    /**
     * @var int
     */
    private $bufferPart;

    /**
     * @var resource
     */
    private $buffer;

    /**
     * @var int
     */
    private $bufferSize;

    /**
     * Constructor.
     *
     * @param \SplFileInfo $folder     The folder to store the generated feed(s)
     * @param string       $dtd        A DTD URL (something like http://gsa.example.com/gsafeed.dtd)
     * @param string       $datasource A datasouce
     * @param string       $feedtype   A feedtype (full|incremental|metadata-and-url)
     */
    public function __construct(\SplFileInfo $folder, $dtd, $datasource, $feedtype)
    {
        $this->folder = $folder;
        $this->dtd = $dtd;
        $this->datasource = $datasource;
        $this->feedtype = $feedtype;
        $this->bufferPart = 0;
        $this->bufferSize = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        $this->generateNewPart();
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data)
    {
        $line = sprintf("        <record url=\"%s\" mimetype=\"%s\" action=\"%s\"/>\n",
            $data['url'],
            $data['mime_type'],
            $data['action']
        );

        // + 18 corresponding to the length of the closing tags
        if (($this->bufferSize + strlen($line) + 18) > self::LIMIT_SIZE) {
            $this->generateNewPart();
        }

        $this->bufferSize += fwrite($this->buffer, $line);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->buffer) {
            $this->closeFeed();
        }
    }

    /**
     * Generates a new file.
     *
     * @throws \RuntimeException
     */
    private function generateNewPart()
    {
        if ($this->buffer) {
            $this->closeFeed();
        }

        $this->bufferSize = 0;
        ++$this->bufferPart;

        if (!is_writable($this->folder)) {
            throw new \RuntimeException(sprintf('Unable to write to folder: %s', $this->folder));
        }

        $this->buffer = fopen(sprintf('%s/feed_%05d.xml', $this->folder, $this->bufferPart), 'w');

        $this->bufferSize += fwrite($this->buffer, <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE gsafeed PUBLIC "-//Google//DTD GSA Feeds//EN" "$this->dtd">
<gsafeed>
    <header>
        <datasource>$this->datasource</datasource>
        <feedtype>$this->feedtype</feedtype>
    </header>

    <group>

XML
        );
    }

    /**
     * Closes the current feed.
     */
    private function closeFeed()
    {
        fwrite($this->buffer, <<<'EOF'
    </group>
</gsafeed>
EOF
        );

        fclose($this->buffer);
    }
}
