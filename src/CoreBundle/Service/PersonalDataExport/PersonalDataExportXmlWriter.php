<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\PersonalDataExport;

use RuntimeException;
use XMLWriter;

use const JSON_THROW_ON_ERROR;

/**
 * Serializes a personal-data export document to XML 1.0.
 *
 * The tree follows the JSON document: the envelope elements, a schema.org
 * Person (and other schema.org types) in the https://schema.org/ namespace,
 * and xAPI statements with actor, verb, object, result, context and timestamp.
 * User-supplied text is written with XMLWriter and stripped of characters
 * that are illegal in XML 1.0; no string concatenation is used to build
 * markup.
 */
final class PersonalDataExportXmlWriter
{
    public const string ROOT_ELEMENT = 'chamiloPersonalData';
    public const string SCHEMA_NS = 'https://schema.org/';
    public const string SCHEMA_PREFIX = 'schema';

    /**
     * @param array<string, mixed> $document
     */
    public function write(array $document): string
    {
        $writer = new XMLWriter();
        if (!$writer->openMemory()) {
            throw new RuntimeException('The personal data export could not be encoded as XML.');
        }

        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);
        $writer->setIndentString('  ');
        $writer->startElement(self::ROOT_ELEMENT);
        $writer->writeAttribute('xmlns:'.self::SCHEMA_PREFIX, self::SCHEMA_NS);

        foreach ($document as $name => $value) {
            $this->writeNode($writer, (string) $name, $value, null);
        }

        $writer->endElement();
        $writer->endDocument();

        $xml = $writer->outputMemory();
        if ('' === $xml) {
            throw new RuntimeException('The personal data export could not be encoded as XML.');
        }

        return $xml;
    }

    private function writeNode(XMLWriter $writer, string $name, mixed $value, ?string $prefix): void
    {
        if (null === $value) {
            return;
        }

        if (\is_array($value)) {
            if (isset($value['@type']) && \is_string($value['@type']) && !$this->isList($value)) {
                $this->startName($writer, $name, $prefix);
                $this->writeSchemaElement($writer, $value);
                $writer->endElement();

                return;
            }

            if ($this->isList($value)) {
                $this->writeList($writer, $name, $value, $prefix);

                return;
            }

            if ($this->isLanguageMap($value)) {
                $this->startName($writer, $name, $prefix);
                foreach ($value as $lang => $text) {
                    $writer->startElement('value');
                    $writer->writeAttribute('xml:lang', $this->sanitizeXmlText((string) $lang));
                    $this->writeText($writer, $text);
                    $writer->endElement();
                }
                $writer->endElement();

                return;
            }

            if ($this->isIriKeyedMap($value)) {
                $this->startName($writer, $name, $prefix);
                foreach ($value as $iri => $item) {
                    $writer->startElement('extension');
                    $writer->writeAttribute('id', $this->sanitizeXmlText((string) $iri));
                    if (\is_array($item)) {
                        $this->writeChildren($writer, $item, null);
                    } else {
                        $this->writeText($writer, $item);
                    }
                    $writer->endElement();
                }
                $writer->endElement();

                return;
            }

            $this->startName($writer, $name, $prefix);
            $this->writeChildren($writer, $value, $prefix);
            $writer->endElement();

            return;
        }

        $this->startName($writer, $name, $prefix);
        $this->writeText($writer, $value);
        $writer->endElement();
    }

    /**
     * @param array<string, mixed> $value
     */
    private function writeSchemaElement(XMLWriter $writer, array $value): void
    {
        $type = $this->safeLocalName((string) $value['@type']);
        $writer->startElementNs(self::SCHEMA_PREFIX, $type, null);
        if (isset($value['@id']) && (\is_string($value['@id']) || is_numeric($value['@id']))) {
            $id = $this->sanitizeXmlText((string) $value['@id']);
            if ('' !== $id) {
                $writer->writeAttribute('id', $id);
            }
        }

        foreach ($value as $key => $child) {
            if (\in_array((string) $key, ['@context', '@type', '@id'], true)) {
                continue;
            }
            $this->writeNode($writer, (string) $key, $child, self::SCHEMA_PREFIX);
        }

        $writer->endElement();
    }

    /**
     * @param list<mixed> $items
     */
    private function writeList(XMLWriter $writer, string $name, array $items, ?string $prefix): void
    {
        if (null !== $prefix) {
            foreach ($items as $item) {
                $this->startName($writer, $name, $prefix);
                if (\is_array($item) && isset($item['@type']) && \is_string($item['@type'])) {
                    $this->writeSchemaElement($writer, $item);
                } elseif (\is_array($item)) {
                    $this->writeChildren($writer, $item, $prefix);
                } else {
                    $this->writeText($writer, $item);
                }
                $writer->endElement();
            }

            return;
        }

        $this->startName($writer, $name, null);
        foreach ($items as $item) {
            if (\is_array($item) && isset($item['@type']) && \is_string($item['@type'])) {
                $this->writeSchemaElement($writer, $item);
            } elseif (\is_array($item)) {
                $childName = $this->itemElementName($name, $item);
                $this->startName($writer, $childName, null);
                $this->writeChildren($writer, $item, null);
                $writer->endElement();
            } else {
                $this->startName($writer, $this->itemElementName($name, null), null);
                $this->writeText($writer, $item);
                $writer->endElement();
            }
        }
        $writer->endElement();
    }

    /**
     * @param array<int|string, mixed> $value
     */
    private function writeChildren(XMLWriter $writer, array $value, ?string $prefix): void
    {
        foreach ($value as $key => $child) {
            if (\is_int($key)) {
                if (\is_array($child) && isset($child['@type']) && \is_string($child['@type'])) {
                    $this->writeSchemaElement($writer, $child);
                } elseif (\is_array($child)) {
                    $this->startName($writer, $this->itemElementName('item', $child), null);
                    $this->writeChildren($writer, $child, $prefix);
                    $writer->endElement();
                } else {
                    $this->startName($writer, 'item', null);
                    $this->writeText($writer, $child);
                    $writer->endElement();
                }

                continue;
            }

            $this->writeNode($writer, (string) $key, $child, $prefix);
        }
    }

    /**
     * @param array<int|string, mixed>|null $item
     */
    private function itemElementName(string $parent, ?array $item): string
    {
        if (\is_array($item) && isset($item['objectType']) && \is_string($item['objectType'])) {
            $fromType = $this->safeLocalName($item['objectType']);
            if ('item' !== $fromType) {
                return lcfirst($fromType);
            }
        }

        return match ($parent) {
            'learning_activities' => 'statement',
            default => 'item',
        };
    }

    private function startName(XMLWriter $writer, string $name, ?string $prefix): void
    {
        $local = $this->safeLocalName($name);
        if (null !== $prefix) {
            $writer->startElementNs($prefix, $local, null);

            return;
        }

        $writer->startElement($local);
    }

    private function writeText(XMLWriter $writer, mixed $value): void
    {
        if (\is_bool($value)) {
            $writer->text($value ? 'true' : 'false');

            return;
        }

        if (\is_int($value) || \is_float($value)) {
            $writer->text(json_encode($value, JSON_THROW_ON_ERROR));

            return;
        }

        $writer->text($this->sanitizeXmlText((string) $value));
    }

    /**
     * @param array<int|string, mixed> $value
     */
    private function isList(array $value): bool
    {
        if ([] === $value) {
            return true;
        }

        return array_is_list($value);
    }

    /**
     * @param array<int|string, mixed> $value
     */
    private function isLanguageMap(array $value): bool
    {
        if ([] === $value || $this->isList($value)) {
            return false;
        }

        foreach ($value as $key => $item) {
            // Require a subtag (e.g. en-US) so keys such as "name", "homePage",
            // "raw" or "max" are not mistaken for language codes.
            if (!\is_string($key) || 1 !== preg_match('/^[A-Za-z]{1,8}(?:-[A-Za-z0-9]{1,8})+$/', $key)) {
                return false;
            }
            if (null !== $item && !\is_scalar($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int|string, mixed> $value
     */
    private function isIriKeyedMap(array $value): bool
    {
        if ([] === $value || $this->isList($value)) {
            return false;
        }

        foreach (array_keys($value) as $key) {
            if (!\is_string($key) || !str_contains($key, '://')) {
                return false;
            }
        }

        return true;
    }

    private function safeLocalName(string $name): string
    {
        $name = trim($name);
        if (str_starts_with($name, '@')) {
            $name = substr($name, 1);
        }

        if (1 === preg_match('/^[A-Za-z_][A-Za-z0-9._-]*$/', $name)) {
            return $name;
        }

        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?? '';
        $safe = trim($safe, '.-_');
        if ('' === $safe || 1 !== preg_match('/^[A-Za-z_]/', $safe)) {
            return 'item';
        }

        return $safe;
    }

    public function sanitizeXmlText(string $value): string
    {
        if ('' === $value) {
            return '';
        }

        if (!preg_match('//u', $value)) {
            // Keep only ASCII that is legal in XML 1.0 when the string is not valid UTF-8.
            $value = preg_replace('/[^\x09\x0A\x0D\x20-\x7F]/', '', $value) ?? '';
        }

        $sanitized = preg_replace(
            '/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u',
            '',
            $value
        );

        return \is_string($sanitized) ? $sanitized : '';
    }
}
