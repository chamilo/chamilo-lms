<?php

/**
 * Google widget/gadget renderer.
 * Accept the following urls:
 * 
 *      module:             http://www.google.com/ig/directory?type=gadgets&url=www.google.com/ig/modules/eyes/eyes.xml
 *      directory entry:    www.google.com/ig/modules/eyes/eyes.xml
 *      configured url:     www.gmodules.com/ig/ifr?url=http://www.google.com/ig/modules/eyes/eyes.xml&amp;synd=open&amp;w=320&amp;h=121&amp;title=__MSG_title__&amp;lang=fr&amp;country=ALL&amp;border=%23ffffff%7C3px%2C1px+solid+%23999999&amp;output=js
 * 
 * @see http://www.google.com/ig/directory?type=gadgets&url=www.google.com/ig/modules/eyes/eyes.xml
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetGoogleWidgetRenderer extends AssetRenderer
{

    /**
     *
     * @param HttpResource $asset 
     */
    public function render($asset)
    {
        
        if ($asset->url_match('gmodules.com/ig/') && $asset->url_param('url') != false)
        {
            $url = $asset->url();
            $title = $asset->url_param('title');
            $title = ($title == '__MSG_title__') ? '' : $title;
            
            $embed = <<<EOT
                <script src="$url"></script>
EOT;
            
            $result = array();
            $result[self::EMBED_SNIPPET] = $embed;
            $result[self::TITLE] = $title;
            return $result;
        }

        if (!$asset->is_gadget())
        {
            $url = $asset->url();

            if (!$asset->url_match('google.com/ig/directory'))
            {
                return false;
            }
            if (!$asset->url_match('type=gadgets'))
            {
                return false;
            }

            $url = $asset->url_param('url');
            if (empty($url))
            {
                return false;
            }
            $asset = new HttpResource($url);
            if (!$asset->is_gadget())
            {
                return false;
            }
        }

        $url = $asset->url();
        if (strpos($url, 'http') !== 0)
        {
            $url = "http://$url";
        }
        $url = urlencode($url);
        $title = $asset->title();
        $title = $title ? $title : $asset->name();
        
        $size = (int) $asset->config('size');
        $size = (24 <= $size && $size <= 800) ? $size : 300;

        $embed = <<<EOT
        <script src="//www.gmodules.com/ig/ifr?url=$url&amp;w=$size&amp;output=js"></script>
EOT;

        $result = array();
        $result[self::EMBED_SNIPPET] = $embed;
        $result[self::TITLE] = $title;
        return $result;
    }

}