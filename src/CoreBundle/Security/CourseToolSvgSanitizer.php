<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security;

use DOMDocument;
use DOMElement;
use Throwable;

use const LIBXML_NONET;
use const XML_CDATA_SECTION_NODE;
use const XML_COMMENT_NODE;
use const XML_TEXT_NODE;

/**
 * Convert uploaded course-tool icons to a small, static SVG subset.
 * Editor metadata and inline presentation styles are normalized away before storage.
 */
final class CourseToolSvgSanitizer
{
    private const string SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    private const int MAX_BYTES = 262144;

    private const array ELEMENTS = [
        'svg', 'g', 'defs', 'path', 'rect', 'circle', 'ellipse', 'line',
        'polyline', 'polygon', 'text', 'tspan', 'title', 'desc',
    ];

    private const array ATTRIBUTES = [
        'id', 'width', 'height', 'viewBox', 'preserveAspectRatio',
        'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
        'd', 'points', 'transform', 'fill', 'fill-opacity', 'fill-rule',
        'stroke', 'stroke-width', 'stroke-miterlimit', 'stroke-opacity',
        'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset',
        'opacity', 'font-family', 'font-size', 'font-weight', 'text-anchor',
        'dominant-baseline',
    ];

    private const array STYLE_ATTRIBUTES = [
        'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width',
        'stroke-miterlimit', 'stroke-opacity', 'stroke-linecap',
        'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset',
        'opacity', 'font-family', 'font-size', 'font-weight',
        'text-anchor', 'dominant-baseline',
    ];

    private const array EDITOR_NAMESPACES = [
        'http://www.inkscape.org/namespaces/inkscape',
        'http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd',
    ];

    public function sanitize(string $content): ?string
    {
        if ('' === $content || \strlen($content) > self::MAX_BYTES
            || preg_match('/<!\s*(?:DOCTYPE|ENTITY)\b/i', $content)
        ) {
            return null;
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $source = new DOMDocument();
            $source->resolveExternals = false;
            $source->substituteEntities = false;
            if (!$source->loadXML($content, LIBXML_NONET) || null !== $source->doctype) {
                return null;
            }

            foreach ($source->childNodes as $child) {
                if ($child !== $source->documentElement
                    && XML_COMMENT_NODE !== $child->nodeType
                    && !(XML_TEXT_NODE === $child->nodeType && '' === trim((string) $child->textContent))
                ) {
                    return null;
                }
            }

            $root = $source->documentElement;
            if (!$root instanceof DOMElement
                || 'svg' !== $root->localName
                || self::SVG_NAMESPACE !== $root->namespaceURI
            ) {
                return null;
            }

            $document = new DOMDocument('1.0', 'UTF-8');
            $count = 0;
            $cleanRoot = $this->copyElement($root, $document, $count, 0);
            if (null === $cleanRoot || !$this->ensureDimensions($cleanRoot)) {
                return null;
            }

            $document->appendChild($cleanRoot);
            $sanitized = $document->saveXML($cleanRoot);

            return false !== $sanitized && \strlen($sanitized) <= self::MAX_BYTES ? $sanitized : null;
        } catch (Throwable) {
            return null;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function copyElement(DOMElement $source, DOMDocument $document, int &$count, int $depth): ?DOMElement
    {
        if (++$count > 10000 || $depth > 64
            || self::SVG_NAMESPACE !== $source->namespaceURI
            || !\in_array($source->localName, self::ELEMENTS, true)
        ) {
            return null;
        }

        $element = $document->createElementNS(self::SVG_NAMESPACE, $source->localName);
        $style = null;
        foreach ($source->attributes as $attribute) {
            if ('http://www.w3.org/2000/xmlns/' === $attribute->namespaceURI
                || \in_array($attribute->namespaceURI, self::EDITOR_NAMESPACES, true)
            ) {
                continue;
            }
            if (null !== $attribute->namespaceURI) {
                return null;
            }

            $name = $attribute->name;
            if ('style' === $name) {
                $style = $attribute->value;

                continue;
            }
            if (\in_array($name, ['version', 'class', 'role', 'focusable', 'aria-hidden'], true)) {
                continue;
            }
            if (!\in_array($name, self::ATTRIBUTES, true)
                || !$this->validAttribute($name, $attribute->value)
            ) {
                return null;
            }
            $element->setAttribute($name, $attribute->value);
        }

        if (null !== $style && !$this->copyStyles($element, $style)) {
            return null;
        }

        foreach ($source->childNodes as $child) {
            if ($child instanceof DOMElement) {
                if ($this->isEditorMetadata($child)) {
                    continue;
                }
                $cleanChild = $this->copyElement($child, $document, $count, $depth + 1);
                if (null === $cleanChild) {
                    return null;
                }
                $element->appendChild($cleanChild);
            } elseif (\in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE], true)) {
                if (\in_array($source->localName, ['text', 'tspan', 'title', 'desc'], true)) {
                    $element->appendChild($document->createTextNode((string) $child->textContent));
                } elseif ('' !== trim((string) $child->textContent)) {
                    return null;
                }
            } elseif (XML_COMMENT_NODE !== $child->nodeType) {
                return null;
            }
        }

        return $element;
    }

    private function isEditorMetadata(DOMElement $element): bool
    {
        return ('metadata' === $element->localName && self::SVG_NAMESPACE === $element->namespaceURI)
            || \in_array($element->namespaceURI, self::EDITOR_NAMESPACES, true);
    }

    private function copyStyles(DOMElement $element, string $style): bool
    {
        foreach (explode(';', $style) as $declaration) {
            if ('' === trim($declaration)) {
                continue;
            }
            if (1 !== substr_count($declaration, ':')) {
                return false;
            }
            [$name, $value] = array_map('trim', explode(':', $declaration, 2));
            $name = strtolower($name);
            if (!\in_array($name, self::STYLE_ATTRIBUTES, true)
                || !$this->validAttribute($name, $value)
            ) {
                return false;
            }
            $element->setAttribute($name, $value);
        }

        return true;
    }

    private function ensureDimensions(DOMElement $root): bool
    {
        foreach (['width', 'height'] as $dimension) {
            if ('' === $root->getAttribute($dimension)) {
                $viewBox = preg_split('/[\s,]+/', trim($root->getAttribute('viewBox')));
                if (!\is_array($viewBox) || 4 !== \count($viewBox) || !$this->isNumber($viewBox['width' === $dimension ? 2 : 3])) {
                    return false;
                }
                $root->setAttribute($dimension, $viewBox['width' === $dimension ? 2 : 3]);
            }
            $value = $root->getAttribute($dimension);
            if (!preg_match('/^\d+(?:\.\d+)?(?:px|pt|mm|cm|in)?$/D', $value)
                || (float) $value <= 0 || (float) $value > 15000
            ) {
                return false;
            }
        }

        return (float) $root->getAttribute('width') * (float) $root->getAttribute('height') <= 50000000;
    }

    private function validAttribute(string $name, string $value): bool
    {
        if ('' === $value || \strlen($value) > 100000
            || preg_match('/(?:url\s*\(|https?:|ftp:|data:|javascript:|@import|expression\s*\(|[<>{}\\\])/i', $value)
        ) {
            return false;
        }

        return match ($name) {
            'id' => (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_.:-]{0,127}$/D', $value),
            'd' => (bool) preg_match('/^[MmLlHhVvCcSsQqTtAaZzEe0-9+.,\-\s]+$/D', $value),
            'points' => (bool) preg_match('/^[0-9Ee+.,\-\s]+$/D', $value),
            'viewBox' => $this->validViewBox($value),
            'preserveAspectRatio' => (bool) preg_match('/^(?:none|x(?:Min|Mid|Max)Y(?:Min|Mid|Max)(?:\s+(?:meet|slice))?)$/D', $value),
            'transform' => (bool) preg_match('/^(?:(?:matrix|translate|scale|rotate|skewX|skewY)\s*\([0-9Ee+.,\-\s]+\)\s*)+$/D', $value),
            'fill', 'stroke' => (bool) preg_match('/^(?:#[0-9a-fA-F]{3,4}|#[0-9a-fA-F]{6}|#[0-9a-fA-F]{8}|[a-zA-Z]{1,32}|rgb\(\s*\d{1,3}(?:\s*,\s*\d{1,3}){2}\s*\))$/D', $value),
            'fill-opacity', 'stroke-opacity', 'opacity' => is_numeric($value) && (float) $value >= 0 && (float) $value <= 1,
            'fill-rule' => \in_array($value, ['nonzero', 'evenodd'], true),
            'stroke-linecap' => \in_array($value, ['butt', 'round', 'square'], true),
            'stroke-linejoin' => \in_array($value, ['miter', 'round', 'bevel'], true),
            'stroke-dasharray' => 'none' === $value || (bool) preg_match('/^[0-9Ee+.,\-\s]+$/D', $value),
            'font-family' => (bool) preg_match('/^[a-zA-Z0-9 _,-]{1,100}$/D', $value),
            'font-weight' => (bool) preg_match('/^(?:normal|bold|bolder|lighter|[1-9]00)$/D', $value),
            'text-anchor' => \in_array($value, ['start', 'middle', 'end'], true),
            'dominant-baseline' => (bool) preg_match('/^[a-z-]{1,32}$/D', $value),
            default => (bool) preg_match('/^[+\-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[Ee][+\-]?\d+)?(?:px|pt|mm|cm|in|%)?$/D', $value),
        };
    }

    private function validViewBox(string $value): bool
    {
        $parts = preg_split('/[\s,]+/', trim($value));

        return \is_array($parts) && 4 === \count($parts) && array_reduce(
            $parts,
            fn (bool $valid, string $part): bool => $valid && $this->isNumber($part),
            true
        ) && (float) $parts[2] > 0 && (float) $parts[3] > 0;
    }

    private function isNumber(string $value): bool
    {
        return (bool) preg_match('/^[+\-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[Ee][+\-]?\d+)?$/D', $value);
    }
}
