<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const ENT_XML1;

final readonly class SitemapXmlBuilder
{
    /**
     * @param list<string> $urls
     */
    public function index(array $urls): string
    {
        return $this->build('sitemapindex', 'sitemap', $urls);
    }

    /**
     * @param list<string> $urls
     */
    public function urlSet(array $urls): string
    {
        return $this->build('urlset', 'url', $urls);
    }

    /**
     * @param list<string> $urls
     */
    private function build(string $root, string $element, array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<'.$root.' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <'.$element.'><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</loc></'.$element.'>'."\n";
        }

        return $xml.'</'.$root.'>'."\n";
    }
}
