<?php

/**
 * Media renderer. I.e. video streams that can be embeded through an embed tag.
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetMediaRenderer extends AssetRenderer
{

    /**
     *
     * @param HttpResource $asset 
     */
    public function accept($asset)
    {
        if ($asset->is_video())
        {
            return true;
        }
        
        //swf mime type is application/x-shockwave-flash
        return $asset->has_ext('swf');
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

        $url = $asset->url();

        $title = $asset->title();
        $description = $asset->get_meta('description');
        $keywords = $asset->get_meta('keywords');
        
        
        $size = (int) $asset->config('size');
        $size = (24 <= $size && $size <= 800) ? $size : 300;
        
        $width = $size;
        $height = $size *9/16;

        $embed = <<<EOT
        <div style="text-align:center;"><embed style="display:inline-block;" width="{$width}px" height="{$height}px" name="plugin" src="$url" ></div>
EOT;


        $result = array();
        $result[self::EMBED_SNIPPET] = $embed;
        $result[self::TITLE] = $title;
        $result[self::DESCRIPTION] = $description;
        $result[self::TAGS] = $keywords;
        return $result;
    }

}