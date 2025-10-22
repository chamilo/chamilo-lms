<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_converter_resource.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Converter;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\CcConverters;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\CcItem;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\CcVersion13;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIItem;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIManifest;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils\CcHelpers;

use const DIRECTORY_SEPARATOR;

class CcConverterResource extends CcConverters
{
    public function __construct(CcIItem &$item, CcIManifest &$manifest, $rootpath, $path)
    {
        $this->ccType = CcVersion13::WEBCONTENT;
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

        $files = CcHelpers::handleResourceContent(
            $this->manifest,
            $this->rootpath,
            $contextid,
            $outdir,
            true,
            $docfilepath
        );

        $deps = null;
        $resvalue = null;
        foreach ($files as $values) {
            if ($values[2]) {
                $resvalue = $values[0];

                break;
            }
        }

        $resitem = new CcItem();
        $resitem->identifierref = $resvalue;
        $resitem->title = $title;
        $this->item->addChildItem($resitem);

        // Checking the visibility.
        $this->manifest->updateInstructoronly($resvalue, !$this->isVisible());

        return true;
    }
}
