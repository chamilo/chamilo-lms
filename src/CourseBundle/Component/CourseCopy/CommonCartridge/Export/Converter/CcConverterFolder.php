<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_converter_folder.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Converter;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\CcConverters;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\CcItem;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIItem;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIManifest;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils\CcHelpers;

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
        $files = CcHelpers::handleStaticContent(
            $this->manifest,
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
        $resitem = new CcItem();
        $resitem->identifierref = $resvalue;
        $resitem->title = $objDocument['title'];
        $this->item->addChildItem($resitem);

        return true;
    }
}
