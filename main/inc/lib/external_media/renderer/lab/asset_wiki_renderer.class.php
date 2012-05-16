<?php

/**
 * Wiki renderer. 
 * 
 * @see http://en.wikipedia.org/w/api.php
 * 
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetWikiRenderer extends AssetRenderer
{

    /**
     *
     * @param HttpResource $asset 
     */
    public function accept($asset)
    {       
        return $asset->url_match('wikipedia.org/wiki', 'mediawiki.org/wiki');
    }

    /**
     *
     * @param HttpResource $asset 
     * 
     */
    public function render($asset)
    {
        if (!$this->accept($asset))
        {
            return;
        }

        $domain = $asset->url_domain();
        $description = $asset->findx('//div[@id="bodyContent"]/p');
        $result = array();
        $result[self::EMBED_SNIPPET] = $description;
        $result[self::TITLE] = $title;
        $result[self::DESCRIPTION] = $description;
        $result[self::TAGS] = $keywords;
        return $result;
    }

}