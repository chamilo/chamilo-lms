<?php
/* For licensing terms, see /license.txt */

class CcConverterFolder extends CcConverters
{

    public function __construct(CcIItem &$item, CcIManifest &$manifest, $rootpath, $path)
    {
        $this->defaultfile = 'folder.xml';
        parent::__construct($item, $manifest, $rootpath, $path);
    }

    public function convert($outdir, $objDocument)
    {
        $contextid = $objDocument['source_id'];
        $folder = api_get_path(SYS_COURSE_PATH).api_get_course_path($objDocument['course_code']).'/'.$objDocument['path'];
        $files = CcHelpers::handle_static_content($this->manifest,
                                          $this->rootpath,
                                          $contextid,
                                          $outdir,
                                          true,
                                          $folder
        );
        $resvalue = null;
        foreach ($files as $values) {
            if ($values[2]) {
                $resvalue = $values[0];
                break;
            }
        }
        $resitem = new cc_item();
        $resitem->identifierref = $resvalue;
        $resitem->title = $objDocument['title'];
        $this->item->add_child_item($resitem);
        return true;
    }

}

