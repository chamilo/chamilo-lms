<?php

require_once dirname(__FILE__) . '/http_resource.class.php';
require_once dirname(__FILE__) . '/asset_aggregated_renderer.class.php';

/**
 * Renderer for an http resource. 
 * Extract meta data and snippet html view from an given url/resource.
 * 
 * Base class. Other renderers must inherit from it.
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetRenderer
{

    const THUMBNAIL = 'thumbnail';
    const EMBED_SNIPPET = 'embed_snippet';
    const EMBED_TYPE = 'embed_type';
    const EMBED_URL = 'embed_url';
    const WIDTH = 'width';
    const HEIGHT = 'height';
    const LANGUAGE = 'language';
    const URL = 'url';
    const TAGS = 'tags';
    const TITLE = 'title';
    const CREATED_TIME = 'created_time';
    const DURATION = 'duration';
    const DESCRIPTION = 'description';
    const ICON = 'icon';

    static function get($url, $config = array())
    {
        if (strpos('url', 'javascript:') !== false)
        {
            return array();
        }
        $result = array();
        $url = trim($url);
        if (empty($url))
        {
            return array();
        }
        $asset = new HttpResource($url, $config);
        $renderer = new AssetAggregatedRenderer(self::plugins());
        $result = $renderer->render($asset);
        $result['url'] = $url;
        return $result;
    }

    static function plugins()
    {
        static $result = array();
        if (!empty($result))
        {
            return $result;
        }

        /*
         * We make sure we load them from most specialized to less specialized.
         * The first that provides a value for a field wins.
         */
        $protocols = array(
            'oembed',
            'og',
            'image',
            'media',
            'rss',
            'google_map',
            'google_document',
            'google_document_viewer',
            'google_widget',
            'mediaserver',
            'scratch',
            'page');

        foreach ($protocols as $protocol)
        {
            $file = "asset_{$protocol}_renderer.class.php";
            require_once dirname(__FILE__) . '/protocol/' . $file;
            
            $class = "asset_{$protocol}_renderer";
            $class = explode('_', $class);
            $class = array_map('ucfirst', $class);
            $class = implode($class);
            
            $result[] = new $class();
        }
        return $result;
    }

    /**
     * Renderer function. Take a http asset as input and return an array containing
     * various properties: metadata, html snippet, etc.
     * 
     * @param HttpResource $asset
     * @return array
     */
    public function render($asset)
    {
        $result = array();
        return $result;
    }

}