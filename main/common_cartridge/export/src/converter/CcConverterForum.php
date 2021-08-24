<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_converter_forum.php under GNU/GPL license */

class CcConverterForum extends CcConverters
{
    public function __construct(CcIItem &$item, CcIManifest &$manifest, $rootpath, $path)
    {
        $this->ccType = CcVersion13::DISCUSSIONTOPIC;
        $this->defaultfile = 'forum.xml';
        $this->defaultname = 'discussion.xml';
        parent::__construct($item, $manifest, $rootpath, $path);
    }

    public function convert($outdir, $item)
    {
        $rt = new CcForum();
        $title = $item['title'];
        $rt->setTitle($title);
        $text = $item['comment'];
        $deps = null;
        if (!empty($text)) {
            $contextid = $item['source_id'];
            $result = CcHelpers::processLinkedFiles($text,
                                                       $this->manifest,
                                                       $this->rootpath,
                                                       $contextid,
                                                       $outdir);
            $textformat = 'text/html';
            $rt->setText($result[0], $textformat);
            $deps = $result[1];
        }
        $this->store($rt, $outdir, $title, $deps);

        return true;
    }
}
