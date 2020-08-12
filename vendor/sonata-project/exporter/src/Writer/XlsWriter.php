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

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
final class XlsWriter implements TypedWriterInterface
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
     * @var bool
     */
    private $showHeaders;

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @throws \RuntimeException
     */
    public function __construct(string $filename, bool $showHeaders = true)
    {
        $this->filename = $filename;
        $this->showHeaders = $showHeaders;

        if (is_file($filename)) {
            throw new \RuntimeException(sprintf('The file %s already exists', $filename));
        }
    }

    public function getDefaultMimeType(): string
    {
        return 'application/vnd.ms-excel';
    }

    public function getFormat(): string
    {
        return 'xls';
    }

    public function open(): void
    {
        $this->file = fopen($this->filename, 'w', false);
        fwrite($this->file, '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta name=ProgId content=Excel.Sheet><meta name=Generator content="https://github.com/sonata-project/exporter"></head><body><table>');
    }

    public function close(): void
    {
        fwrite($this->file, '</table></body></html>');
        fclose($this->file);
    }

    public function write(array $data): void
    {
        $this->init($data);

        fwrite($this->file, '<tr>');
        foreach ($data as $value) {
            fwrite($this->file, sprintf('<td>%s</td>', $value));
        }
        fwrite($this->file, '</tr>');

        ++$this->position;
    }

    private function init(array $data): void
    {
        if ($this->position > 0) {
            return;
        }

        if ($this->showHeaders) {
            fwrite($this->file, '<tr>');
            foreach ($data as $header => $value) {
                fwrite($this->file, sprintf('<th>%s</th>', $header));
            }
            fwrite($this->file, '</tr>');
            ++$this->position;
        }
    }
}
