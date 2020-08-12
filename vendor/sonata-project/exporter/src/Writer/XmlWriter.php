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

use Sonata\Exporter\Exception\InvalidDataFormatException;
use Sonata\Exporter\Exception\RuntimeException;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
final class XmlWriter implements TypedWriterInterface
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var resource
     */
    private $file;

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var string
     */
    private $mainElement;

    /**
     * @var string
     */
    private $childElement;

    /**
     * @throws \RuntimeException
     */
    public function __construct(string $filename, string $mainElement = 'datas', string $childElement = 'data')
    {
        $this->filename = $filename;
        $this->mainElement = $mainElement;
        $this->childElement = $childElement;

        if (is_file($filename)) {
            throw new \RuntimeException(sprintf('The file %s already exist', $filename));
        }
    }

    public function getDefaultMimeType(): string
    {
        return 'text/xml';
    }

    public function getFormat(): string
    {
        return 'xml';
    }

    public function open(): void
    {
        $this->file = fopen($this->filename, 'w', false);

        fwrite($this->file, sprintf("<?xml version=\"1.0\" ?>\n<%s>\n", $this->mainElement));
    }

    public function close(): void
    {
        fwrite($this->file, sprintf('</%s>', $this->mainElement));

        fclose($this->file);
    }

    public function write(array $data): void
    {
        fwrite($this->file, sprintf("<%s>\n", $this->childElement));

        foreach ($data as $k => $v) {
            $this->generateNode($k, $v);
        }

        fwrite($this->file, sprintf("</%s>\n", $this->childElement));
    }

    /**
     * @param mixed $value
     *
     * @throws \RuntimeException
     */
    private function generateNode(string $name, $value): void
    {
        if (\is_array($value)) {
            throw new RuntimeException('Not implemented');
        } elseif (is_scalar($value) || null === $value) {
            fwrite($this->file, sprintf("<%s><![CDATA[%s]]></%s>\n", $name, $value, $name));
        } else {
            throw new InvalidDataFormatException('Invalid data');
        }
    }
}
