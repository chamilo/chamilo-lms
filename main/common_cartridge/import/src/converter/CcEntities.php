<?php
/* For licensing terms, see /license.txt */

class CcEntities
{
    /**
     * Prepares convert for inclusion into XML.
     *
     * @param string $value
     *
     * @return string
     */
    public static function safexml($value)
    {
        $result = htmlspecialchars(html_entity_decode($value, ENT_QUOTES, 'UTF-8'),
                                   ENT_NOQUOTES,
                                   'UTF-8',
                                   false);

        return $result;
    }

    public function loadXmlResource($path_to_file)
    {
        $resource = new DOMDocument();

        Cc1p3Convert::logAction('Load the XML resource file: '.$path_to_file);

        if (!$resource->load($path_to_file)) {
            Cc1p3Convert::logAction('Cannot load the XML resource file: '.$path_to_file, false);
        }

        return $resource;
    }

    public function updateSources($html, $rootPath = '')
    {
        $document = $this->loadHtml($html);

        $tags = ['img' => 'src', 'a' => 'href'];

        foreach ($tags as $tag => $attribute) {
            $elements = $document->getElementsByTagName($tag);

            foreach ($elements as $element) {
                $attribute_value = $element->getAttribute($attribute);
                $protocol = parse_url($attribute_value, PHP_URL_SCHEME);

                if (empty($protocol)) {
                    $attribute_value = str_replace("\$IMS-CC-FILEBASE\$", "", $attribute_value);
                    $attribute_value = $this->fullPath($rootPath."/".$attribute_value, "/");
                    $attribute_value = "\$@FILEPHP@\$"."/".$attribute_value;
                }

                $element->setAttribute($attribute, $attribute_value);
            }
        }

        $html = $this->htmlInsidebody($document);

        return $html;
    }

    public function fullPath($path, $dir_sep = DIRECTORY_SEPARATOR)
    {
        $token = '$IMS-CC-FILEBASE$';
        $path = str_replace($token, '', $path);

        if (is_string($path) && ($path != '')) {
            $dot_dir = '.';
            $up_dir = '..';
            $length = strlen($path);
            $rtemp = trim($path);
            $start = strrpos($path, $dir_sep);
            $can_continue = ($start !== false);
            $result = $can_continue ? '' : $path;
            $rcount = 0;

            while ($can_continue) {
                $dir_part = ($start !== false) ? substr($rtemp, $start + 1, $length - $start) : $rtemp;
                $can_continue = ($dir_part !== false);

                if ($can_continue) {
                    if ($dir_part != $dot_dir) {
                        if ($dir_part == $up_dir) {
                            $rcount++;
                        } else {
                            if ($rcount > 0) {
                                $rcount--;
                            } else {
                                $result = ($result == '') ? $dir_part : $dir_part.$dir_sep.$result;
                            }
                        }
                    }
                    $rtemp = substr($path, 0, $start);
                    $start = strrpos($rtemp, $dir_sep);
                    $can_continue = (($start !== false) || (strlen($rtemp) > 0));
                }
            }
        }

        return $result;
    }

    public function includeTitles($html)
    {
        $document = $this->loadHtml($html);

        $images = $document->getElementsByTagName('img');

        foreach ($images as $image) {
            $src = $image->getAttribute('src');
            $alt = $image->getAttribute('alt');
            $title = $image->getAttribute('title');

            $filename = pathinfo($src);
            $filename = $filename['filename'];

            $alt = empty($alt) ? $filename : $alt;
            $title = empty($title) ? $filename : $title;

            $image->setAttribute('alt', $alt);
            $image->setAttribute('title', $title);
        }

        $html = $this->htmlInsidebody($document);

        return $html;
    }

    public function getExternalXml($identifier)
    {
        $xpath = Cc1p3Convert::newxPath(Cc1p3Convert::$manifest, Cc1p3Convert::$namespaces);

        $files = $xpath->query('/imscc:manifest/imscc:resources/imscc:resource[@identifier="'.
            $identifier.'"]/imscc:file/@href');

        if (empty($files)) {
            $response = '';
        } else {
            $response = $files->item(0)->nodeValue;
        }

        return $response;
    }

    public function generateRandomString($length = 6)
    {
        $response = '';
        $source = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if ($length > 0) {
            $response = '';
            $source = str_split($source, 1);

            for ($i = 1; $i <= $length; $i++) {
                $num = mt_rand(1, count($source));
                $response .= $source[$num - 1];
            }
        }

        return $response;
    }

    public function truncateText($text, $max, $remove_html)
    {
        if ($max > 10) {
            $text = substr($text, 0, ($max - 6)).' [...]';
        } else {
            $text = substr($text, 0, $max);
        }

        $text = $remove_html ? strip_tags($text) : $text;

        return $text;
    }

    protected function prepareContent($content)
    {
        $result = $content;
        if (empty($result)) {
            return '';
        }
        $encoding = null;
        $xml_error = new LibxmlErrorsMgr();
        $dom = new DOMDocument();
        $dom->validateOnParse = false;
        $dom->strictErrorChecking = false;
        if ($dom->loadHTML($content)) {
            $encoding = $dom->xmlEncoding;
        }
        if (empty($encoding)) {
            $encoding = mb_detect_encoding($content, 'auto', true);
        }
        if (!empty($encoding) && !mb_check_encoding($content, 'UTF-8')) {
            $result = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        // See if we can strip off body tag and anything outside of it.
        foreach (['body', 'html'] as $tagname) {
            $regex = str_replace('##', $tagname, "/<##[^>]*>(.+)<\/##>/is");
            if (preg_match($regex, $result, $matches)) {
                $result = $matches[1];
                break;
            }
        }

        return $result;
    }

    /**
     * @param string $html
     *
     * @return DOMDocument
     */
    private function loadHtml($html)
    {
        // Need to make sure that the html passed has charset meta tag.
        $metatag = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        if (strpos($html, $metatag) === false) {
            $html = '<html><head>'.$metatag.'</head><body>'.$html.'</body></html>';
        }

        $document = new DOMDocument();
        @$document->loadHTML($html);

        return $document;
    }

    /**
     * @param DOMDocument $domdocument
     *
     * @return string
     */
    private function htmlInsidebody($domdocument)
    {
        $html = '';
        $bodyitems = $domdocument->getElementsByTagName('body');
        if ($bodyitems->length > 0) {
            $body = $bodyitems->item(0);
            $html = str_ireplace(['<body>', '</body>'], '', $body->C14N());
        }

        return $html;
    }
}
