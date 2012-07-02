<?php

/**
 * Process html pages that support the oembed protocol.
 * 
 * Note that here we rely on the discovery service. That is each page that contains in
 * its metadata the oembed request.
 * 
 * @see http://oembed.com/
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 * 
 */
class AssetOembedRenderer extends AssetRenderer
{

    /**
     *
     * @param HttpResource $asset 
     */
    public function render($asset)
    {
        $link = $asset->get_link('type', 'application/json+oembed');
        if (empty($link))
        {
            return false;
        }

        $width = (int) $asset->config('size');
        $width = (24 <= $width && $width <= 800) ? $width : 300;

        $href = $link['href'];
        $data = HttpResource::fetch_json("$href&maxwidth=$width"); //&maxheight=$height
        if (empty($data))
        {
            return false;
        }

        $data['title'] = isset($data['title']) ? $data['title'] : '';
        $data['width'] = isset($data['width']) ? intval($data['width']) : '';
        $data['height'] = isset($data['height']) ? intval($data['height']) : '';

        $type = $data['type'];
        $f = array($this, "render_$type");
        if (is_callable($f))
        {
            $result = call_user_func($f, $asset, $data);
        }
        else
        {
            $result = array();
        }
        $result[self::THUMBNAIL] = isset($data['thumbnail_url']) ? $data['thumbnail_url'] : '';
        $result[self::TITLE] = isset($data['title']) ? $data['title'] : '';

        return $result;
    }

    protected function render_photo($asset, $data)
    {
        if ($data['type'] != 'photo')
        {
            return array();
        }

        $result = array();
        $html = isset($data['html']) ? $data['html'] : '';
        if ($html)
        {
            $result[self::EMBED_SNIPPET] = '<div style="display:inline-block">' . $html . '</div>';
            return $result;
        }

        $title = $data['title'];
        $width = (int)$data['width'];
        $height = (int)$data['height'];
//        $ratio = $height / $width;
//        $height = $ratio * $width;

        $url = $data['url'];

        $embed = <<<EOT
        <div><a href="$url"><img src="{$url}" width="{$width}" height="{$height}" "alt="{$title}" title="{$title}"></a></div>
EOT;

        $result[self::EMBED_SNIPPET] = $embed;
        return $result;
    }

    protected function render_video($asset, $data)
    {
        if ($data['type'] != 'video')
        {
            return array();
        }
        $result = array();
        $result[self::EMBED_SNIPPET] = '<div style="display:inline-block">' . $data['html'] . '</div>';
        return $result;
    }

    protected function render_rich($asset, $data)
    {
        if ($data['type'] != 'rich')
        {
            return array();
        }

        $result = array();
        $result[self::EMBED_SNIPPET] = '<div style="display:inline-block">' . $data['html'] . '</div>';
        return $result;
    }

    protected function render_link($asset, $data)
    {
        if ($data['type'] != 'link')
        {
            return array();
        }
        return array();
    }

}