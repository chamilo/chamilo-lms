<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Framework;

use RuntimeException;
use Sonata\Exporter\Handler;
use Sonata\Exporter\Source\SourceIteratorInterface;
use Sonata\Exporter\Writer\CsvWriter;
use Sonata\Exporter\Writer\JsonWriter;
use Sonata\Exporter\Writer\XlsWriter;
use Sonata\Exporter\Writer\XmlWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Exporter
{
    /**
     * @throws RuntimeException
     *
     * @return StreamedResponse
     */
    public function getResponse(string $format, string $filename, SourceIteratorInterface $source)
    {
        switch ($format) {
            case 'xls':
                $writer = new XlsWriter('php://output');
                $contentType = 'application/vnd.ms-excel';

                break;
            case 'xml':
                $writer = new XmlWriter('php://output');
                $contentType = 'text/xml';

                break;
            case 'json':
                $writer = new JsonWriter('php://output');
                $contentType = 'application/json';

                break;
            case 'csv':
                $writer = new CsvWriter('php://output', ',', '"', '', true, true);
                $contentType = 'text/csv';

                break;
            default:
                throw new RuntimeException('Invalid format');
        }

        $callback = function () use ($source, $writer): void {
            $handler = Handler::create($source, $writer);
            $handler->export();
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => sprintf('attachment; filename=%s', $filename),
        ]);
    }
}
