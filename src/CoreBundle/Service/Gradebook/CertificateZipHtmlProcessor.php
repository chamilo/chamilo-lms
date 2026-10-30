<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Gradebook;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use const LIBXML_NOERROR;
use const LIBXML_NONET;
use const LIBXML_NOWARNING;
use const PHP_URL_PATH;

/**
 * Makes certificate HTML self-contained before it is stored as a course resource.
 * Only images shipped in the same archive can be embedded.
 */
final class CertificateZipHtmlProcessor
{
    /**
     * @param array<string, array{mime: string, content: string}> $images Normalized archive paths
     */
    public function prepareHtml(string $html, string $htmlPath, array $images): string
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();

        try {
            $loaded = $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (!$loaded) {
            throw new BadRequestHttpException('Invalid certificate HTML in ZIP archive.');
        }

        $xpath = new DOMXPath($document);
        $unsafeNodes = [];
        foreach ($xpath->query('//script|//iframe|//object|//embed|//form|//input|//button|//base|//link|//meta|//svg|//math|//video|//audio|//source|//track|//picture') as $element) {
            $unsafeNodes[] = $element;
        }
        foreach ($unsafeNodes as $element) {
            $element->parentNode?->removeChild($element);
        }

        foreach ($document->getElementsByTagName('*') as $element) {
            if (!$element instanceof DOMElement) {
                continue;
            }

            $attributes = [];
            foreach ($element->attributes as $attribute) {
                $name = strtolower($attribute->name);
                $value = trim($attribute->value);
                if (str_starts_with($name, 'on') || \in_array($name, ['srcset', 'background', 'formaction', 'xlink:href'], true)) {
                    $attributes[] = $attribute->name;
                } elseif (\in_array($name, ['href', 'action'], true) && !preg_match('#^(https?://|mailto:|/|\#)#i', $value)) {
                    $attributes[] = $attribute->name;
                } elseif ('style' === $name) {
                    $element->setAttribute('style', $this->sanitizeCss($value));
                }
            }

            foreach ($attributes as $name) {
                $element->removeAttribute($name);
            }

            if ('style' === strtolower($element->tagName)) {
                $element->textContent = $this->sanitizeCss($element->textContent);
            }

            if ('img' !== strtolower($element->tagName)) {
                continue;
            }

            $source = trim($element->getAttribute('src'));
            $image = $this->resolveImage($source, $htmlPath, $images);
            if (null === $image) {
                // Avoid broken/remote image requests, including server-side PDF fetching.
                $element->removeAttribute('src');

                continue;
            }

            $element->setAttribute('src', 'data:'.$image['mime'].';base64,'.base64_encode($image['content']));
        }

        $result = $document->saveHTML();

        return \is_string($result) ? $result : '';
    }

    private function sanitizeCss(string $css): string
    {
        $css = preg_replace('/@import\b[^;]*;?/i', '', $css) ?? '';
        $css = preg_replace('/url\s*\(\s*[^)]*\)/i', 'none', $css) ?? '';

        return preg_replace('/expression\s*\(\s*[^)]*\)/i', '', $css) ?? '';
    }

    /**
     * @param array<string, array{mime: string, content: string}> $images
     *
     * @return array{mime: string, content: string}|null
     */
    private function resolveImage(string $source, string $htmlPath, array $images): ?array
    {
        if ('' === $source || str_starts_with(strtolower($source), 'data:')) {
            return null;
        }

        $path = rawurldecode((string) (parse_url($source, PHP_URL_PATH) ?? ''));
        $path = str_replace('\\', '/', $path);
        $relative = ltrim($path, '/');
        $htmlDirectory = \dirname($htmlPath);
        $candidate = $this->normalizePath(('.' === $htmlDirectory ? '' : $htmlDirectory.'/').$relative);

        if (null !== $candidate && isset($images[$candidate])) {
            return $images[$candidate];
        }

        // Older certificate HTML often contains absolute URLs of the *previous*
        // installation. Match by basename only when the ZIP has one unique match.
        $basename = basename($relative);
        $matches = [];
        foreach ($images as $imagePath => $image) {
            if ($basename === basename($imagePath)) {
                $matches[] = $image;
            }
        }

        return 1 === \count($matches) ? $matches[0] : null;
    }

    private function normalizePath(string $path): ?string
    {
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }
            if ('..' === $segment) {
                if ([] === $segments) {
                    return null;
                }
                array_pop($segments);
            } else {
                $segments[] = $segment;
            }
        }

        return implode('/', $segments);
    }
}
