<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_converter_page.php under GNU/GPL license */

class CcConverterPage extends CcConverters
{
    public function __construct(CcIItem &$item, CcIManifest &$manifest, $rootpath, $path)
    {
        $this->ccType = CcVersion13::WEBCONTENT;
        $this->defaultfile = 'page.xml';
        $this->defaultname = uniqid().'.html';
        parent::__construct($item, $manifest, $rootpath, $path);
    }

    public function convert($outdir, $objPage)
    {
        $rt = new CcPage();
        $title = $objPage['title'];
        $intro = '';
        $contextid = $objPage['source_id'];
        $pagecontent = $objPage['comment'];
        $rt->setTitle($title);
        $rawname = str_replace(' ', '_', strtolower(trim(Security::filter_filename($title))));

        if (!empty($rawname)) {
            $this->defaultname = $rawname.".html";
        }

        $result = CcHelpers::processLinkedFiles($pagecontent,
                                                    $this->manifest,
                                                    $this->rootpath,
                                                    $contextid,
                                                    $outdir,
                                                    true);
        $rt->setContent($result[0]);
        $rt->setIntro($intro);
        //store everything
        $this->store($rt, $outdir, $title, $result[1]);

        return true;
    }
}
