<?php

/**
 * Google document viewer renderer. Provide an embeded viewer for the following
 * documetn types:
 * 
 *     Microsoft Word (.DOC and .DOCX)
 *     Microsoft Excel (.XLS and .XLSX)
 *     Microsoft PowerPoint (.PPT and .PPTX)
 *     Adobe Portable Document Format (.PDF)
 *     Apple Pages (.PAGES)
 *     Adobe Illustrator (.AI)
 *     Adobe Photoshop (.PSD)
 *     Tagged Image File Format (.TIFF)
 *     Autodesk AutoCad (.DXF)
 *     Scalable Vector Graphics (.SVG)
 *     PostScript (.EPS, .PS)
 *     TrueType (.TTF)
 *     XML Paper Specification (.XPS)
 *     Archive file types (.ZIP and .RAR)
 * 
 * @see https://docs.google.com/viewer
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetGoogleDocumentViewerRenderer extends AssetRenderer
{

    /**
     *
     * @param HttpResource $asset 
     */
    public function accept($asset)
    {
        $supported_extentions = array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'pages', 'ai', 'psd', 'tiff', 'dxf', 'svg', 'eps', 'ps', 'eps', 'ttf', 'zip', 'rar');
        return $asset->has_ext($supported_extentions);
    }

    protected function url($document_url)
    {
        return 'https://docs.google.com/viewer?embedded=true&url=' . urlencode($document_url);
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

        $url = $this->url($asset->url());

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
        //$result[self::THUMBNAIL] = $image_src;
        $result[self::DESCRIPTION] = $description;
        $result[self::TAGS] = $keywords;
        return $result;
    }

}