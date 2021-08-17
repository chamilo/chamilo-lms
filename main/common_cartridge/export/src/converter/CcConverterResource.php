<?php
/* For licensing terms, see /license.txt */

class CcConverterResource extends CcConverters
{

    public function __construct(CcIItem &$item, CcIManifest &$manifest, $rootpath, $path)
    {
        $this->cc_type     = CcVersion13::webcontent;
        $this->defaultfile = 'resource.xml';
        parent::__construct($item, $manifest, $rootpath, $path);
    }

    public function convert($outdir, $objResource)
    {

        $title = $objResource['title'];
        $contextid = $objResource['source_id'];
        $docfilepath = null;
        if (isset($objResource['path'])) {
            $docfilepath = api_get_path(SYS_COURSE_PATH).api_get_course_path($objResource['course_code']).DIRECTORY_SEPARATOR.$objResource['path'];
        }

        $files = CcHelpers::handle_resource_content($this->manifest,
                                                   $this->rootpath,
                                                   $contextid,
                                                   $outdir,
                                                   true,
                                                   $docfilepath);

        $deps = null;
        $resvalue = null;
        foreach ($files as $values) {
            if ($values[2]) {
                $resvalue = $values[0];
                break;
            }
        }

        $resitem = new cc_item();
        $resitem->identifierref = $resvalue;
        $resitem->title = $title;
        $this->item->add_child_item($resitem);

        // Checking the visibility.
        $this->manifest->update_instructoronly($resvalue, !$this->is_visible());

        return true;
    }

}

