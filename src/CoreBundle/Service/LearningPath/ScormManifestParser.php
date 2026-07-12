<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;

final class ScormManifestParser
{
    /**
     * @return array{
     *     version: string,
     *     encoding: string,
     *     organizations: array<int, array{
     *         identifier: string,
     *         title: string,
     *         items: array<int, array<string, mixed>>
     *     }>,
     *     resources: array<string, array{href: string, scormType: string}>
     * }
     */
    public function parse(string $xml): array
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (!$loaded || !$document->documentElement instanceof DOMElement) {
            throw new RuntimeException('The SCORM manifest is not valid XML.');
        }

        $xpath = new DOMXPath($document);
        $resources = $this->parseResources($xpath);
        $organizationsNode = $xpath->query('/*[local-name()="manifest"]/*[local-name()="organizations"]')->item(0);
        if (!$organizationsNode instanceof DOMElement) {
            throw new RuntimeException('The SCORM manifest does not contain an organizations element.');
        }

        $defaultIdentifier = trim($organizationsNode->getAttribute('default'));
        $organizations = [];
        foreach ($organizationsNode->childNodes as $organizationNode) {
            if (!$organizationNode instanceof DOMElement || 'organization' !== $organizationNode->localName) {
                continue;
            }

            $identifier = trim($organizationNode->getAttribute('identifier'));
            if ('' === $identifier) {
                $identifier = 'organization_'.(\count($organizations) + 1);
            }

            $title = $this->getDirectChildText($organizationNode, 'title');
            if ('' === $title) {
                $title = 'Untitled';
            }

            $items = [];
            foreach ($organizationNode->childNodes as $itemNode) {
                if ($itemNode instanceof DOMElement && 'item' === $itemNode->localName) {
                    $items[] = $this->parseItem($itemNode);
                }
            }

            $organizations[] = [
                'identifier' => $identifier,
                'title' => $title,
                'items' => $items,
            ];
        }

        if ([] === $organizations) {
            throw new RuntimeException('The SCORM manifest does not contain an organization.');
        }

        if ('' !== $defaultIdentifier) {
            usort(
                $organizations,
                static fn (array $left, array $right): int => ($left['identifier'] === $defaultIdentifier ? 0 : 1)
                    <=> ($right['identifier'] === $defaultIdentifier ? 0 : 1),
            );
        }

        $schemaVersion = $this->firstText($xpath, '//*[local-name()="schemaversion"]');
        $version = str_contains(strtolower($schemaVersion), '2004') ? '2004' : '1.2';

        return [
            'version' => $version,
            'encoding' => $document->encoding ?: 'UTF-8',
            'organizations' => $organizations,
            'resources' => $resources,
        ];
    }

    /** @return array<string, array{href: string, scormType: string}> */
    private function parseResources(DOMXPath $xpath): array
    {
        $resources = [];
        $resourceNodes = $xpath->query('//*[local-name()="resources"]/*[local-name()="resource"]');
        if (false === $resourceNodes) {
            return $resources;
        }

        foreach ($resourceNodes as $resourceNode) {
            if (!$resourceNode instanceof DOMElement) {
                continue;
            }

            $identifier = trim($resourceNode->getAttribute('identifier'));
            if ('' === $identifier) {
                continue;
            }

            $href = $this->normalizeResourcePath($resourceNode->getAttribute('href'));
            $scormType = '';
            foreach ($resourceNode->attributes ?? [] as $attribute) {
                if ('scormtype' === strtolower((string) $attribute->localName)) {
                    $scormType = strtolower(trim((string) $attribute->nodeValue));
                    break;
                }
            }

            if (!\in_array($scormType, ['sco', 'asset'], true)) {
                $scormType = '' !== $href ? 'sco' : 'asset';
            }

            $resources[$identifier] = [
                'href' => $href,
                'scormType' => $scormType,
            ];
        }

        return $resources;
    }

    /** @return array<string, mixed> */
    private function parseItem(DOMElement $itemNode): array
    {
        $children = [];
        foreach ($itemNode->childNodes as $childNode) {
            if ($childNode instanceof DOMElement && 'item' === $childNode->localName) {
                $children[] = $this->parseItem($childNode);
            }
        }

        $title = $this->getDirectChildText($itemNode, 'title');
        if ('' === $title) {
            $title = 'Untitled';
        }

        return [
            'identifier' => trim($itemNode->getAttribute('identifier')),
            'identifierRef' => trim($itemNode->getAttribute('identifierref')),
            'parameters' => trim($itemNode->getAttribute('parameters')),
            'title' => $title,
            'prerequisites' => $this->getDirectChildText($itemNode, 'prerequisites'),
            'launchData' => $this->getDirectChildText($itemNode, 'datafromlms')
                ?: $this->getDirectChildText($itemNode, 'launchdata'),
            'maxScore' => $this->nullableFloat($this->getDirectChildText($itemNode, 'max_score')),
            'masteryScore' => $this->nullableFloat($this->getDirectChildText($itemNode, 'masteryscore')),
            'maxTimeAllowed' => $this->nullableString($this->getDirectChildText($itemNode, 'maxtimeallowed')),
            'children' => $children,
        ];
    }

    private function getDirectChildText(DOMElement $parent, string $localName): string
    {
        foreach ($parent->childNodes as $childNode) {
            if ($childNode instanceof DOMElement && $localName === strtolower((string) $childNode->localName)) {
                return trim($childNode->textContent);
            }
        }

        return '';
    }

    private function firstText(DOMXPath $xpath, string $query): string
    {
        $node = $xpath->query($query)->item(0);

        return $node instanceof DOMNode ? trim($node->textContent) : '';
    }

    private function normalizeResourcePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if ('' === $path) {
            return '';
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $path)) {
            throw new RuntimeException('The SCORM manifest contains an unsafe resource path.');
        }

        if (preg_match('#^([A-Za-z][A-Za-z0-9+.-]*)://#', $path, $matches)) {
            if (!\in_array(strtolower($matches[1]), ['http', 'https'], true)) {
                throw new RuntimeException('The SCORM manifest contains an unsupported remote resource URL.');
            }

            return $path;
        }

        $path = rawurldecode((string) preg_replace('/[?#].*$/', '', $path));
        $path = ltrim($path, '/');
        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ('..' === $segment) {
                throw new RuntimeException('The SCORM manifest contains an unsafe resource path.');
            }
        }

        return implode('/', array_values(array_filter(
            $segments,
            static fn (string $segment): bool => '' !== $segment && '.' !== $segment,
        )));
    }

    private function nullableFloat(string $value): ?float
    {
        return '' !== $value && is_numeric($value) ? (float) $value : null;
    }

    private function nullableString(string $value): ?string
    {
        return '' === $value ? null : $value;
    }
}
