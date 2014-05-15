<?php

/**
 * An HTTP resource. In most cases an HTML document.
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class HttpResource
{

    /**
     * Fetch the content and metadata of an url. 
     * 
     * If the content type is not parsable, i.e. it is not made of text, 
     * only fetch the metadata and not the content. This is mostly done to 
     * avoid downloading big files - videos, images, etc - which is unnecessary.
     * 
     * @param string $url   the url to fetch
     * @return array        array containing the content and various info
     */
    static function fetch($url, $fetch_content = null)
    {
        static $cache = array();
        if (isset($cache[$url]))
        {
            return $cache;
        }

        if (is_null($fetch_content) || $fetch_content === false)
        {
            // create a new cURL resource
            $ch = curl_init();

            // set URL and other appropriate options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_NOBODY, true);

            $content = curl_exec($ch);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);

            // close cURL resource, and free up system resources
            curl_close($ch);
            $info['content'] = $content;
            $info['error'] = $error;

            if ($fetch_content === false)
            {
                return $cache[$url] = $info;
            }

            if (isset($info['content_type']) && strpos($info['content_type'], 'text') === false)
            {
                return $cache[$url] = $info;
            }
        }

        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        //curl_setopt($ch, CURLOPT_VERBOSE, true);

        $content = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);

        // close cURL resource, and free up system resources
        curl_close($ch);
        $info['content'] = $content;
        $info['error'] = $error;

        return $cache[$url] = $info;
    }

    static function fetch_json($url)
    {
        $content = self::fetch($url, true);
        $content = $content['content'];
        if ($content)
        {
            $result = (array) json_decode($content);
        }
        else
        {
            $result = array();
        }
        return $result;
    }

    protected $url;
    protected $url_params = null;
    protected $info = null;
    protected $source = null;
    protected $metadata = null;
    protected $links = null;
    protected $title = null;
    protected $mime = null;
    protected $doc = null;
    protected $config = array();

    public function __construct($url, $config = array())
    {
        $this->url = $url;
        $this->config = $config;
    }

    public function config($key = '', $default = null)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    /**
     * Url of the resource
     * 
     * @return string
     */
    public function url()
    {
        return $this->url;
    }

    public function url_domain()
    {
        $url = $this->url();
        $url = trim($url, '/');
        if (strpos($url, '//') !== false)
        {
            $parts = explode('//', $url);
            $url = end($parts);
        }
        $parts = explode('/', $url);
        $result = reset($parts);
        return $result;
    }

    /**
     *
     * @param array|string $part
     * @return boolean 
     */
    public function url_match($part)
    {
        $params = func_get_args();
        $params = is_array($params) ? $params : array($params);

        $url = strtolower($this->url());
        foreach ($params as $param)
        {
            if (strpos($url, strtolower($param)) !== false)
            {
                return true;
            }
        }
        return false;
    }

    public function url_params()
    {
        if (!is_null($this->url_params))
        {
            return $this->url_params;
        }

        $url = $this->url();
        if (strpos($url, '?') === false)
        {
            return $this->url_params = array();
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

        return $this->url_params = $result;
    }

    public function url_param($name, $default = false)
    {
        $params = $this->url_params();
        return isset($params[$name]) ? $params[$name] : $default;
    }

    /**
     * The name of the resource. I.e. the last part of the url without the ext
     * 
     * @return string
     */
    public function name()
    {
        $url = $this->url();
        $url = explode('/', $url);
        $title = end($url);
        $title = explode('.', $title);
        $title = reset($title);
        return $title;
    }

    /**
     * Extention of the url
     * 
     * @return string
     */
    public function ext()
    {
        $url = $this->url();
        $url = explode('.', $url);
        $ext = end($url);
        $ext = strtolower($ext);
        return $ext;
    }

    /**
     * Return true if the object has one of the extentions. Overloaded:
     * 
     *      $res->has_ext('pdf');
     *      $res->has_ext('pdf', 'doc');
     *      $res->has_ext(array('pdf', 'doc'));
     * 
     * @param array|string $_
     * @return boolean true if the resource has one of the extentions passed
     */
    public function has_ext($_)
    {
        if (is_array($_))
        {
            $params = $_;
        }
        else
        {
            $params = func_get_args();
            $params = is_array($params) ? $params : array($params);
        }
        $ext = $this->ext();
        foreach ($params as $param)
        {
            if (strtolower($param) == $ext)
            {
                return true;
            }
        }
        return false;
    }

    public function charset()
    {
        $info = $this->info();

        $content_type = isset($info['content_type']) ? $info['content_type'] : '';
        if (empty($content_type))
        {
            return null;
        }
        $items = explode(';', $content_type);
        foreach ($items as $item)
        {
            $parts = explode('=', $item);
            if (count($parts) == 2 && reset($parts) == 'charset')
            {
                return strtolower(end($parts));
            }
        }
        return null;
    }

    /**
     * The mime type of the resource or the empty string if none has been specified
     * 
     * @return string
     */
    public function mime()
    {
        if (!is_null($this->mime))
        {
            return $this->mime;
        }
        $info = $this->info();

        $content_type = isset($info['content_type']) ? $info['content_type'] : '';
        if ($content_type)
        {
            $result = reset(explode(';', $content_type));
            $result = strtolower($result);
            return $this->mime = $result;
        }

        return $this->mime = '';
    }

    public function is_xml()
    {
        $mime = $this->mime();
        if (!empty($mime))
        {
            return strpos($mime, 'xml') !== false;
        }
        return $this->ext() == 'xml';
    }

    public function is_image()
    {
        $mime = $this->mime();
        if ($mime)
        {
            return strpos($mime, 'image') !== false;
        }

        $ext = $this->ext();
        $formats = array('gif', 'jpeg', 'jpg', 'jpe', 'pjpeg', 'png', 'svg', 'tiff', 'ico');
        foreach ($formats as $format)
        {
            if ($format == $ext)
            {
                return true;
            }
        }
        return false;
    }

    public function is_video()
    {
        $mime = $this->mime();
        if ($mime)
        {
            return strpos($mime, 'video') !== false;
        }

        $ext = $this->ext();
        $formats = array('mpeg', 'mp4', 'ogg', 'wmv', 'mkv');
        foreach ($formats as $format)
        {
            if ($format == $ext)
            {
                return true;
            }
        }
        return false;
    }

    public function is_audio()
    {
        $mime = $this->mime();
        if ($mime)
        {
            return strpos($mime, 'audio') !== false;
        }

        $ext = $this->ext();
        $formats = array('mp3');
        foreach ($formats as $format)
        {
            if ($format == $ext)
            {
                return true;
            }
        }
        return false;
    }

    public function is_rss()
    {
        if (!$this->is_xml())
        {
            return false;
        }

        $doc = $this->doc();
        $nodes = $doc->getElementsByTagName('rss');
        return $nodes->length != 0;
    }

    public function is_gadget()
    {
        if (!$this->is_xml())
        {
            return false;
        }

        $doc = $this->doc();
        $nodes = $doc->getElementsByTagName('ModulePrefs');
        return $nodes->length != 0;
    }

    public function canonic_url($src)
    {
        if (strpos($src, '//') === 0)
        {
            $src = "http:$src";
        }
        else if (strpos($src, '/') === 0) //relative url to the root 
        {
            $url = $this->url();
            $protocol = reset(explode('://', $url));
            $domain = end(explode('://', $url));
            $domain = reset(explode('/', $domain));
            $src = "$protocol://$domain/$src";
        }
        else if (strpos($src, 'http') !== 0) //relative url to the document
        {
            $url = $this->url();
            $tail = end(explode('/', $url));
            $base = str_replace($tail, '', $url);

            $src = $base . $src;
        }
        return $src;
    }

    /**
     * Content of the resource.
     * 
     * @return string
     */
    public function source()
    {
        if (!is_null($this->source))
        {
            return $this->source;
        }
        $info = $this->info();

        return $this->source = $info['content'];
    }

    /**
     * Array of arrays containing the page's metadata. 
     * 
     * @return array
     */
    public function metadata()
    {
        if (!is_null($this->metadata))
        {
            return $this->metadata;
        }
        return $this->metadata = $this->get_metadata();
    }

    public function title()
    {
        if (!is_null($this->title))
        {
            return $this->title;
        }
        return $this->title = $this->get_title();
    }

    /**
     *
     * @return DOMDocument|boolean
     */
    public function doc()
    {
        if (!is_null($this->doc))
        {
            return $this->doc;
        }
        return $this->doc = $this->get_doc($this->source());
    }

    function get_meta($name)
    {
        $metadata = $this->metadata();
        $name = strtolower($name);
        foreach ($metadata as $attributes)
        {
            $key = isset($attributes['name']) ? $attributes['name'] : false;
            $key = $key ? strtolower($key) : $key;
            if ($name == $key)
            {
                return $attributes['content'];
            }
            $key = isset($attributes['property']) ? $attributes['property'] : false;
            $key = $key ? strtolower($key) : $key;
            if ($name == $key)
            {
                return isset($attributes['content']) ? $attributes['content'] : false;
            }
        }
        return false;
    }

    function get_link($key, $value)
    {
        $links = $this->links();
        $key = strtolower($key);
        $value = strtolower($value);
        foreach ($links as $attributes)
        {
            $a = isset($attributes[$key]) ? $attributes[$key] : false;
            $a = $a ? strtolower($a) : $a;
            if ($a == $value)
            {
                return $attributes;
            }
        }
        return false;
    }

    public function links()
    {
        if (!is_null($this->links))
        {
            return $this->links;
        }
        return $this->links = $this->get_links();
    }

    /**
     *
     * @param string $xpath dom xpath
     * @return string
     */
    public function findx($query)
    {
        $doc = $this->doc();
        if (empty($doc))
        {
            return array();
        }
        $xpath = new DOMXpath($doc);
        $nodes = $xpath->query($query);
        if ($nodes->length > 0)
        {
            return $doc->saveXML($nodes->item(0));
        }
        else
        {
            return '';
        }
    }

    protected function info()
    {
        if (!is_null($this->info))
        {
            return $this->info;
        }
        return $this->info = self::fetch($this->url());
    }

    /**
     *
     * @param string $source
     * @return boolean|DOMDocument 
     */
    protected function get_doc($source)
    {
        if ($source == false)
        {
            return false;
        }
        $source = $this->source();
        $result = new DOMDocument();
        libxml_clear_errors();
        libxml_use_internal_errors(true);
        if ($this->is_xml())
        {
            $success = $result->loadXML($source);
        }
        else
        {
            $success = $result->loadHTML($source);
        }
        //$e = libxml_get_errors();
        return $result ? $result : false;
    }

    protected function get_metadata()
    {
        $result = array();

        $doc = $this->doc();
        if ($doc == false)
        {
            return array();
        }
        $metas = $doc->getElementsByTagName('meta');
        if ($metas->length == 0)
        {
            return $result;
        }
        foreach ($metas as $meta)
        {
            $values = array();
            $attributes = $meta->attributes;
            $length = $attributes->length;
            for ($i = 0; $i < $length; ++$i)
            {
                $name = $attributes->item($i)->name;
                $value = $attributes->item($i)->value;
                $value = $attributes->item($i)->value;
                $values[$name] = $value;
            }
            $result[] = $values;
        }
        return $result;
    }

    protected function get_title()
    {
        $doc = $this->doc();
        if ($doc == false)
        {
            return '';
        }
        $titles = $doc->getElementsByTagName('title');
        if ($titles->length == 0)
        {
            return false;
        }
        $result = $titles->item(0)->nodeValue;
        return $result;
    }

    protected function get_links()
    {
        $doc = $this->doc();
        if ($doc == false)
        {
            return array();
        }
        $result = array();

        $metas = $doc->getElementsByTagName('link');
        if ($metas->length == 0)
        {
            return $result;
        }
        foreach ($metas as $meta)
        {
            $values = array();
            $attributes = $meta->attributes;
            $length = $attributes->length;
            for ($i = 0; $i < $length; ++$i)
            {
                $name = $attributes->item($i)->name;
                $value = $attributes->item($i)->value;
                $values[$name] = $value;
            }
            $result[] = $values;
        }
        return $result;
    }

}
