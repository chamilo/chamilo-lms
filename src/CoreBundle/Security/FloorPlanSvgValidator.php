<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security;

use DOMDocument;
use DOMElement;
use DOMNode;
use Throwable;

use const LIBXML_NONET;
use const XML_CDATA_SECTION_NODE;
use const XML_COMMENT_NODE;
use const XML_DOCUMENT_NODE;
use const XML_TEXT_NODE;

/**
 * Accept a deliberately limited, static subset of SVG for floor plans.
 * No scripting, embedded content, stylesheets, links, external references,
 * filters, animation or foreign namespaces are allowed.
 */
final class FloorPlanSvgValidator
{
    private const array ELEMENTS = [
        'svg', 'g', 'defs', 'path', 'rect', 'circle', 'ellipse', 'line',
        'polyline', 'polygon', 'text', 'tspan', 'title', 'desc',
    ];

    private const array ATTRIBUTES = [
        'id', 'width', 'height', 'viewBox', 'preserveAspectRatio',
        'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
        'd', 'points', 'transform', 'fill', 'fill-opacity', 'fill-rule',
        'stroke', 'stroke-width', 'stroke-opacity', 'stroke-linecap',
        'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset', 'opacity',
        'font-family', 'font-size', 'font-weight', 'text-anchor',
        'dominant-baseline',
    ];

    public function isSafe(string $content): bool
    {
        if ('' === $content || \strlen($content) > 10485760
            || preg_match('/<!\s*(?:DOCTYPE|ENTITY)\b/i', $content)
        ) {
            return false;
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument();
            $document->resolveExternals = false;
            $document->substituteEntities = false;
            if (!$document->loadXML($content, LIBXML_NONET) || null !== $document->doctype) {
                return false;
            }

            $root = $document->documentElement;
            if (!$root instanceof DOMElement
                || 'svg' !== $root->localName
                || 'http://www.w3.org/2000/svg' !== $root->namespaceURI
            ) {
                return false;
            }

            // Intrinsic dimensions are mandatory: Leaflet uses the image aspect ratio
            // when mapping room_map's normalized points onto the plan.
            $dimensions = [];
            foreach (['width', 'height'] as $dimension) {
                $value = $root->getAttribute($dimension);
                if (!preg_match('/^(?:\d+(?:\.\d+)?)(?:px|pt|mm|cm|in)?$/', $value)
                    || (float) $value <= 0 || (float) $value > 15000
                ) {
                    return false;
                }
                $dimensions[] = (float) $value;
            }
            if ($dimensions[0] * $dimensions[1] > 50000000) {
                return false;
            }

            $count = 0;

            return $this->checkNode($document, $count);
        } catch (Throwable) {
            return false;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function checkNode(DOMNode $node, int &$count): bool
    {
        if (++$count > 10000) {
            return false;
        }
        if ($node instanceof DOMElement) {
            if ('http://www.w3.org/2000/svg' !== $node->namespaceURI
                || !\in_array($node->localName, self::ELEMENTS, true)
            ) {
                return false;
            }
            foreach ($node->attributes as $attribute) {
                if ('http://www.w3.org/2000/xmlns/' === $attribute->namespaceURI) {
                    continue;
                }
                if (null !== $attribute->namespaceURI
                    || !\in_array($attribute->name, self::ATTRIBUTES, true)
                    || \strlen($attribute->value) > 100000
                    || preg_match('/(?:url\s*\(|https?:|ftp:|data:|javascript:|@import|expression\s*\()/i', $attribute->value)
                ) {
                    return false;
                }
            }
        } elseif (!\in_array($node->nodeType, [XML_DOCUMENT_NODE, XML_TEXT_NODE, XML_COMMENT_NODE, XML_CDATA_SECTION_NODE], true)) {
            return false;
        }

        foreach ($node->childNodes as $child) {
            if (!$this->checkNode($child, $count)) {
                return false;
            }
        }

        return true;
    }
}
