<?php

/**
 * Media Server renderer. 
 * 
 * Note that some videos are protected. It is therefore not possible to use the
 * autodiscovery feature. That is to get the web page, look at the meta data headers
 * and read the oembed api call. This would only work for public content/javascript
 * bookmarklet. 
 * 
 * So here we bypass the discovery service and directly call the API endpoint 
 * with the page url to retrieve oembed metadata - which happens to be public.
 * 
 * @see https://mediaserver.unige.ch
 * 
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetMediaserverRenderer extends AssetRenderer
{
    
    const API_ENDPOINT = 'http://129.194.20.121/oembed/unige-oembed-provider-test.php';

    /**
     *
     * @param HttpResource $asset 
     */
    public function accept($asset)
    {
        return $asset->url_match('https://mediaserver.unige.ch/play/') ||$asset->url_match('http://mediaserver.unige.ch/play/');
    }

    /**
     *
     * @param HttpResource $asset 
     */
    public function render($asset)
    {
        if (!$this->accept($asset))
        {
            return;
        }

        $width = (int) $asset->config('size');
        $width = (24 <= $width && $width <= 800) ? $width : 300;
        
        $url = $asset->url();
        
        $oembed = self::API_ENDPOINT . '?url=' . urlencode($url) . '&maxwidth=' . $width;

        $data = HttpResource::fetch_json($oembed); 
        if (empty($data))
        {
            return false;
        }

        $result[self::THUMBNAIL] = isset($data['thumbnail_url']) ? $data['thumbnail_url'] : '';
        $result[self::TITLE] = isset($data['title']) ? $data['title'] : '';
        $result[self::EMBED_SNIPPET] = isset($data['html']) ? $data['html'] : '';

        return $result;
    }

}