<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Framework;

use Exporter\Source\SourceIteratorInterface;
use Exporter\Writer\CsvWriter;
use Exporter\Writer\JsonWriter;
use Exporter\Writer\XlsWriter;
use Exporter\Writer\XmlWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class Exporter.
 *
 * @package Chamilo\CoreBundle\Framework\Exporter
 */
class Exporter
{
    /**
     * @param string                  $format
     * @param string                  $filename
     * @param SourceIteratorInterface $source
     *
     * @throws \RuntimeException
     *
     * @return StreamedResponse
     */
    public function getResponse($format, $filename, SourceIteratorInterface $source)
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
                $writer = new CsvWriter('php://output', ',', '"', "", true, true);
                $contentType = 'text/csv';
                break;
            default:
                throw new \RuntimeException('Invalid format');
        }

        $callback = function () use ($source, $writer) {
            $handler = \Exporter\Handler::create($source, $writer);
            $handler->export();
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => sprintf('attachment; filename=%s', $filename),
        ]);
    }
}
