<?php

/**
 * Generic HTML page renderer. Process any html page.
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetPageRenderer extends AssetRenderer
{

    /**
     *
     * @param HttpResource $asset 
     */
    public function render($asset)
    {
        global $THEME;
        $url = $asset->url();
        $title = $asset->title();
        $title = $title ? $title : $asset->name();
        $description = $asset->get_meta('description');
        $description = $description;

        $keywords = $asset->get_meta('keywords');

        $image_src = $asset->get_link('rel', 'image_src');
        $image_src = $image_src ? $image_src['href'] : false;

        if (empty($image_src))
        {
            $image_src = $this->get_icon($asset);
        }

        $icon = $this->get_icon($asset);
        
        $image_src = $asset->canonic_url($image_src);
        $icon = $asset->canonic_url($icon);

        $embed = <<<EOT
        <a href="$url">
            <img src="{$image_src}" alt="{$title}" title="{$title}" style="float:left; margin-right:5px; margin-bottom:5px; " >
        </a>
        $description
        <span style="clear:both;"></span>
EOT;


        $result = array();
        $result[self::EMBED_SNIPPET] = $embed;
        $result[self::TITLE] = $title;
        $result[self::THUMBNAIL] = $image_src;
        $result[self::DESCRIPTION] = $description;
        $result[self::ICON] = $icon;
        $result[self::TAGS] = $keywords;
        return $result;
    }

    function get_icon($asset)
    {

        $icon = $asset->get_link('rel', 'apple-touch-icon');
        $icon = $icon ? $icon['href'] : false;
        if (empty($icon))
        {
            $icon = $asset->get_link('rel', 'fluid-icon');
            $icon = $icon ? $icon['href'] : false;
        }
        if (empty($icon))
        {
            $icon = $asset->get_link('rel', 'shortcut icon');
            $icon = $icon ? $icon['href'] : false;
        }
        if (empty($icon))
        {
            $icon = $asset->get_link('rel', 'icon');
            $icon = $icon ? $icon['href'] : false;
        }
        return $icon;
    }

}