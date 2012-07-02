<?php

/**
 * Process pages that support the open graph protocol
 * 
 * @see http://ogp.me/
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetOgRenderer extends AssetRenderer
{

    /**
     * Renderer function. Take a http asset as input and return an array containing
     * various properties: metadata, html snippet, etc.
     * 
     * @param HttpResource $asset
     * @return array
     */
    public function render($asset)
    {
        $type = $asset->get_meta('og:type');
        if (empty($type))
        {
            if ($video = $asset->get_meta('og:video'))
            {
                $type = 'video';
            }
            else if ($video = $asset->get_meta('og:image'))
            {
                $type = 'default';
            }
        }
        if (empty($type))
        {
            return array();
        }

        $type = explode('.', $type);
        $type = reset($type);
        $f = array($this, "render_$type");
        if (is_callable($f))
        {
            $result = call_user_func($f, $asset);
        }
        else
        {
            $result = $this->render_default($asset);
        }

        $result[self::TITLE] = $asset->get_meta('og:title');
        $result[self::THUMBNAIL] = $asset->get_meta('og:image');
        $result[self::LANGUAGE] = $asset->get_meta('og:language');

        return $result;
    }

    /**
     * @param HttpResource $asset
     * @return array
     */
    protected function render_video($asset)
    {
        $url = $asset->get_meta('og:video');
        $url = str_replace('?autoPlay=1', '?', $url);
        $url = str_replace('&autoPlay=1', '', $url);

        if (empty($url))
        {
            return array();
        }

        $type = $asset->get_meta('og:video:type');
        if ($type)
        {
            $type = ' type="' . $type . '" ';
        }
        
        $size = (int) $asset->config('size');
        $size = (24 <= $size && $size <= 800) ? $size : 300;
        
        $width = $asset->get_meta('og:video:width');
        $width = $width ? $width : $asset->get_meta('video_width');
        $height = $asset->get_meta('og:video:height');
        $height = $height ? $height : $asset->get_meta('video_height');

        if ($width)
        {
            $ratio = $height / $width;
            $base = min($size, $width);
            $width = $base;
            $height = $ratio * $base;
            $size = 'width="' . $width . '" height="' . $height . '"';
        }
        else
        {
            $size = 'width="' . $size . '"';
        }

        $embed = <<<EOT
        <embed $type $size src="$url" />        
EOT;

        $result[self::EMBED_TYPE] = $type;
        $result[self::EMBED_URL] = $url;
        $result[self::EMBED_SNIPPET] = $embed;
        $result[self::TAGS] = $asset->get_meta('og:video:tag');
        $result[self::CREATED_TIME] = $asset->get_meta('og:video:release_date');
        $result[self::DURATION] = $asset->get_meta('og:duration');
        return $result;
    }

    protected function render_article($asset)
    {
        $result = $this->render_default($asset);
        return $result;
    }

    protected function render_audio($asset)
    {
        $result = $this->render_default($asset);
        return $result;
    }

    protected function render_book($asset)
    {
        $result = $this->render_default($asset);
        return $result;
    }

    /**
     * 
     * @param HttpResource $asset
     * @return array
     */
    protected function render_default($asset)
    {
        $url = $asset->get_meta('og:url');
        $url = htmlentities($url);
        $title = $asset->get_meta('og:title');
        $image = $asset->get_meta('og:image');
        $image = htmlentities($image);
        $width = $asset->get_meta('og:image:width');
        $height = $asset->get_meta('og:image:height');
        $description = $asset->get_meta('og:description');
        $description = $description ? $description : $asset->get_meta('description');

        $size = (int) $asset->config('size');
        $size = (24 <= $size && $size <= 800) ? $size : 300;

        if ($width)
        {
            $ratio = $height / $width;
            $base = min($size, $width);
            $width = $base;
            $height = $ratio * $base;
            $size = 'width="' . $width . '" height="' . $height . '"';
        }
        else
        {
            $size = 'width="' . $size . '"';
        }
        $embed = <<<EOT
        <div>
            <a href="$url" style="float:left; margin-right:5px; margin-bottom:5px; display:block;"><img src="{$image}" {$size} alt="{$title}" title="{$title}"></a>
            <div style="clear:both;"></div>
        </div>
EOT;

        $result[self::EMBED_SNIPPET] = $embed;
        $result[self::DESCRIPTION] = $asset->get_meta('description');
        return $result;
    }

    /**
     * @param HttpResource $asset
     * @return array
     */
    protected function render_image($asset)
    {
        $size = (int) $asset->config('size');
        $size = (24 <= $size && $size <= 800) ? $size : 300;

        $title = $data['title'];
        $width = $data['width'];
        $height = $data['height'];
        $ratio = $height / $width;
        $base = min($size, $width);
        $width = $base;
        $height = $ratio * $base;

        $url = $data['url'];

        $embed = <<<EOT
        <a href="$url"><img src="{$url}" width="{$width}" height="{$height} "alt="{$title}" title="{$title}"></a>
EOT;
    }

}