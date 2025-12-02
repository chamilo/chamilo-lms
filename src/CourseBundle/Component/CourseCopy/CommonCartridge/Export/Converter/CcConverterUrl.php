<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_converter_url.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Converter;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\CcConverters;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\CcVersion13;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\CcWebLink;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIItem;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIManifest;

class CcConverterUrl extends CcConverters
{
    public function __construct(CcIItem &$item, CcIManifest &$manifest, $rootpath, $path)
    {
        $this->ccType = CcVersion13::WEBLINK;
        $this->defaultfile = 'url.xml';
        $this->defaultname = 'weblink.xml';
        parent::__construct($item, $manifest, $rootpath, $path);
    }

    public function convert($outdir, $objLink)
    {
        $rt = new CcWebLink();
        $title = $objLink['title'];
        $rt->setTitle($title);
        $url = $objLink['url'];
        if (!empty($url)) {
            $rt->setUrl($url, $objLink['target']);
        }
        $this->store($rt, $outdir, $title);

        return true;
    }
}
