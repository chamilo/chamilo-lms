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

namespace Sonata\Exporter;

use Sonata\Exporter\Source\SourceIteratorInterface;
use Sonata\Exporter\Writer\TypedWriterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @author Grégoire Paris <postmaster@greg0ire.fr>
 */
final class Exporter
{
    /**
     * @var TypedWriterInterface[]
     */
    private $writers;

    /**
     * @param TypedWriterInterface[] $writers an array of allowed typed writers, indexed by format name
     */
    public function __construct(array $writers = [])
    {
        $this->writers = [];

        foreach ($writers as $writer) {
            $this->addWriter($writer);
        }
    }

    /**
     * @throws \RuntimeException
     */
    public function getResponse(string $format, string $filename, SourceIteratorInterface $source): StreamedResponse
    {
        if (!\array_key_exists($format, $this->writers)) {
            throw new \RuntimeException(sprintf(
                'Invalid "%s" format, supported formats are : "%s"',
                $format,
                implode(', ', array_keys($this->writers))
            ));
        }
        $writer = $this->writers[$format];

        $callback = static function () use ($source, $writer): void {
            $handler = Handler::create($source, $writer);
            $handler->export();
        };

        $headers = [
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ];

        $headers['Content-Type'] = $writer->getDefaultMimeType();

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Returns a simple array of export formats.
     *
     * @return string[] writer formats as returned by the TypedWriterInterface::getFormat() method
     */
    public function getAvailableFormats(): array
    {
        return array_keys($this->writers);
    }

    /**
     * The main benefit of this method is the type hinting.
     *
     * @param TypedWriterInterface $writer a possible writer for exporting data
     */
    public function addWriter(TypedWriterInterface $writer): void
    {
        $this->writers[$writer->getFormat()] = $writer;
    }
}
