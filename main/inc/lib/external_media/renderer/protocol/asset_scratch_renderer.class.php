<?php

/**
 * Scratch renderer. 
 * 
 * @see http://scratch.mit.edu/projects/
 * 
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetScratchRenderer extends AssetRenderer
{

    /**
     *
     * @param HttpResource $asset 
     */
    public function accept($asset)
    {
        return $asset->url_match('http://scratch.mit.edu/projects/');
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

        $matches = array();
        $pattern = "#http:\/\/scratch.mit.edu\/projects\/(\w+)/(\d*)\/?#ims";
        preg_match($pattern, $asset->url(), $matches);

        $url = $matches[0];
        $author = $matches[1];
        $project_id = $matches[2];

        $project_url = "../../static/projects/$author/$project_id.sb";
        $image_url = "http://scratch.mit.edu/static/projects/$author/{$project_id}_med.png";
        $thumb_url = "http://scratch.mit.edu/static/projects/$author/{$project_id}_sm.png";

        $height = 387;
        $width = 482;

        if (function_exists('get_string'))
        {
            $no_java = get_string('no_java', 'artefact.extresource');
        }
        else
        {
            $no_java = 'Java is not installed on your computer. You must install java first.';
        }

        $embed = <<<EOT
        <object type="application/x-java-applet" width="$width" height="$height"  style="display:block" id="ProjectApplet">

            <param name="codebase" value="http://scratch.mit.edu/static/misc">
            <param name="archive" value="ScratchApplet.jar">
            <param name="code" value="ScratchApplet">
            <param name="project" value="$project_url">
            <pre>$no_java</pre>
            <img alt="" src="$image_url">
        </object>
EOT;
        $result = array();
        $result[self::EMBED_SNIPPET] = $embed;
        $result[self::THUMBNAIL] = $thumb_url;
        return $result;
    }

}