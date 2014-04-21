<?php

/**
 * Google document renderer. 
 * 
 * @see http://support.google.com/docs/bin/answer.py?hl=en&answer=86101&topic=1360911&ctx=topic
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetGoogleDocumentRenderer extends AssetRenderer
{

    /**
     *
     * @param HttpResource $asset 
     */
    public function accept($asset)
    {       
        $url = $asset->url();
        
        return strpos($url, 'docs.google.com/document/pub') !== false;
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