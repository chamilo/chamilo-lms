<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Converter;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Base\Validator\LibxmlErrorsMgr;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Cc1p3Convert;
use DOMDocument;

use const DIRECTORY_SEPARATOR;
use const ENT_NOQUOTES;
use const ENT_QUOTES;
use const PHP_URL_SCHEME;

class CcEntities
{
    /**
     * Escapes content for safe XML inclusion (HTML entities normalized).
     *
     * @param mixed $value
     */
    public static function safexml($value)
    {
        return htmlspecialchars(
            html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8'),
            ENT_NOQUOTES,
            'UTF-8',
            false
        );
    }

    public function loadXmlResource($path_to_file)
    {
        $resource = new DOMDocument();

        Cc1p3Convert::logAction('Load the XML resource file: '.$path_to_file);

        if (!$resource->load($path_to_file)) {
            // Keep non-fatal to continue best-effort import.
            Cc1p3Convert::logAction('Cannot load the XML resource file: '.$path_to_file, false);
        }

        return $resource;
    }

    public function updateSources($html, $rootPath = '')
    {
        $document = $this->loadHtml((string) $html);

        $tags = ['img' => 'src', 'a' => 'href'];

        foreach ($tags as $tag => $attribute) {
            $elements = $document->getElementsByTagName($tag);

            foreach ($elements as $element) {
                $attribute_value = $element->getAttribute($attribute);
                $protocol = parse_url($attribute_value, PHP_URL_SCHEME);

                if (empty($protocol)) {
                    // Remove $IMS-CC-FILEBASE$ or $1EdTech-CC-FILEBASE$ placeholder (regex).
                    $attribute_value = preg_replace('/\$(?:IMS|1EdTech)[-_]CC[-_]FILEBASE\$/', '', (string) $attribute_value);
                    // Normalize relative path based on provided $rootPath.
                    $attribute_value = $this->fullPath($rootPath.'/'.$attribute_value, '/');
                }

                $element->setAttribute($attribute, $attribute_value);
            }
        }

        return $this->htmlInsidebody($document);
    }

    public function fullPath($path, $dir_sep = DIRECTORY_SEPARATOR)
    {
        // Normalize CC placeholders to real relative paths.
        $path = preg_replace('/\$(?:IMS|1EdTech)[-_]CC[-_]FILEBASE\$/', '', (string) $path);

        if ('' === $path || null === $path) {
            return '';
        }

        // Unify separators to the one requested
        $sep = $dir_sep ?: '/';
        $path = str_replace(['\\', '/'], $sep, $path);

        // Split and resolve "." and ".." without using fragile substr/strrpos
        $parts = array_values(array_filter(explode($sep, $path), static function ($p) {
            return '' !== $p;
        }));

        $stack = [];
        foreach ($parts as $p) {
            if ('.' === $p) {
                continue;
            }
            if ('..' === $p) {
                // Pop only if there is something to go back to
                if (!empty($stack)) {
                    array_pop($stack);
                }

                continue;
            }
            $stack[] = $p;
        }

        // Join back using the normalized separator; keep it relative
        return implode($sep, $stack);
    }

    public function includeTitles($html)
    {
        $document = $this->loadHtml((string) $html);

        $images = $document->getElementsByTagName('img');

        foreach ($images as $image) {
            $src = $image->getAttribute('src');
            $alt = $image->getAttribute('alt');
            $title = $image->getAttribute('title');

            $filename = pathinfo($src);
            $filename = $filename['filename'] ?? '';

            $alt = '' !== $alt ? $alt : $filename;
            $title = '' !== $title ? $title : $filename;

            $image->setAttribute('alt', $alt);
            $image->setAttribute('title', $title);
        }

        return $this->htmlInsidebody($document);
    }

    public function getExternalXml($identifier)
    {
        $xpath = Cc1p3Convert::newxPath(Cc1p3Convert::$manifest, Cc1p3Convert::$namespaces);

        $files = $xpath->query(
            '/imscc:manifest/imscc:resources/imscc:resource[@identifier="'.$identifier.'"]/imscc:file/@href'
        );

        return ($files && $files->length > 0) ? $files->item(0)->nodeValue : '';
    }

    public function generateRandomString($length = 6)
    {
        $response = '';
        $source = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if ($length > 0) {
            $response = '';
            $source = str_split($source, 1);

            for ($i = 1; $i <= $length; $i++) {
                $num = mt_rand(1, \count($source));
                $response .= $source[$num - 1];
            }
        }

        return $response;
    }

    public function truncateText($text, $max, $remove_html)
    {
        if ($max > 10) {
            $text = substr((string) $text, 0, $max - 6).' [...]';
        } else {
            $text = substr((string) $text, 0, $max);
        }

        return $remove_html ? strip_tags($text) : $text;
    }

    protected function prepareContent($content)
    {
        $result = (string) $content;
        if ('' === $result) {
            return '';
        }
        $encoding = null;
        $xml_error = new LibxmlErrorsMgr();
        $dom = new DOMDocument();
        $dom->validateOnParse = false;
        $dom->strictErrorChecking = false;
        if (@$dom->loadHTML($result)) {
            $encoding = $dom->xmlEncoding;
        }
        if (empty($encoding)) {
            $encoding = mb_detect_encoding($result, 'auto', true);
        }
        if (!empty($encoding) && !mb_check_encoding($result, 'UTF-8')) {
            $result = mb_convert_encoding($result, 'UTF-8', (string) $encoding);
        }

        // Strip body/html wrapper if present.
        foreach (['body', 'html'] as $tagname) {
            $regex = str_replace('##', $tagname, '/<##[^>]*>(.+)<\\/##>/is');
            if (preg_match($regex, $result, $matches)) {
                $result = $matches[1];

                break;
            }
        }

        return $result;
    }

    /**
     * Ensure HTML has charset meta and return a DOMDocument.
     *
     * @param mixed $html
     */
    private function loadHtml($html)
    {
        $metatag = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        if (!str_contains($html, $metatag)) {
            $html = '<html><head>'.$metatag.'</head><body>'.$html.'</body></html>';
        }

        $document = new DOMDocument();
        @$document->loadHTML($html);

        return $document;
    }

    private function htmlInsidebody($domdocument)
    {
        $html = '';
        $bodyitems = $domdocument->getElementsByTagName('body');
        if ($bodyitems->length > 0) {
            $body = $bodyitems->item(0);
            // Using C14N to get inner content of body.
            $html = str_ireplace(['<body>', '</body>'], '', $body->C14N());
        }

        return $html;
    }
}
