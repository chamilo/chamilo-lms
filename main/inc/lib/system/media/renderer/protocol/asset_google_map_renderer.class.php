<?php

/**
 * Google map page renderer. 
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetGoogleMapRenderer extends AssetRenderer
{

    /**
     *
     * @param HttpResource $asset 
     */
    public function accept($asset)
    {
        $url = $asset->url();
        $url = str_replace('http://', '', $url);
        $url = str_replace('https://', '', $url);

        $domain = reset(explode('/', $url));
        return strpos($domain, 'maps.google') !== false;
    }

    /**
     *
     * @param string $url 
     */
    public function explode_url_parameters($url = null)
    {
        if (strpos($url, '?') === false)
        {
            return array();
        }

        $result = array();
        $params = explode('?', $url);
        $params = end($params);
        $params = explode('&', $params);
        foreach ($params as $param)
        {
            list($key, $val) = explode('=', $param);
            $result[$key] = $val;
        }

        return $result;
    }

    public function implode_url_parameters($params)
    {
        $result = array();
        foreach ($params as $key => $value)
        {
            if ($value)
            {
                $result[] = "$key=$value";
            }
        }
        return join('&', $result);
    }

    protected function url($base = 'http:://map.google.com/', $params = array())
    {
        $head = reset(explode('?', $base));
        $items = $this->explode_url_parameters($base);
        foreach ($params as $key => $value)
        {
                $items[$key] = $value;
        }
        $tail = $this->implode_url_parameters($items);
        $tail = empty($tail) ? '' : "?$tail";
        return $head . $tail;
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

        $params = array('output' => 'embed');
        
        $base = $asset->url();
        $url = $this->url($base, $params);
        
        $title = $asset->title();
        $description = $asset->get_meta('description');

        $keywords = $asset->get_meta('keywords');
        
        $size = (int) $asset->config('size');
        $size = (24 <= $size && $size <= 800) ? $size : 300;

        $embed = <<<EOT
        <div style="height:{$size}px;" class="resize vertical" >
            <iframe style=background-color:#ffffff;" width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="$url"></iframe>
        </div>
        <style type="text/css">
        div.resize.vertical {
            background-color: #EEEEEE;
            border-color: #EEEEEE;
            border-style: solid;
            border-width: 1px;
            resize:vertical;
            overflow: hidden; 
            padding-bottom:15px; 
            min-height:24px; 
            max-height:800px;
}
        </style>
EOT;


        $result = array();
        $result[self::EMBED_SNIPPET] = $embed;
        $result[self::TITLE] = $title;
        $result[self::DESCRIPTION] = $description;
        $result[self::TAGS] = $keywords;
        return $result;
    }

}