<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_converters.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\CcItem;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\CcResources;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIItem;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIManifest;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils\CcResourceLocation;
use InvalidArgumentException;
use RuntimeException;

abstract class CcConverters
{
    protected $item;
    protected $manifest;
    protected $rootpath;
    protected $path;
    protected $defaultfile;
    protected $defaultname;
    protected $ccType;
    protected $doc;

    /**
     * ctor.
     *
     * @param string $rootpath
     * @param string $path
     *
     * @throws InvalidArgumentException
     */
    public function __construct(CcIItem &$item, CcIManifest &$manifest, $rootpath, $path)
    {
        $rpath = realpath($rootpath);
        if (empty($rpath)) {
            throw new InvalidArgumentException('Invalid path!');
        }
        $rpath2 = realpath($path);
        if (empty($rpath)) {
            throw new InvalidArgumentException('Invalid path!');
        }
        $doc = new XMLGenericDocument();

        $this->doc = $doc;
        $this->item = $item;
        $this->manifest = $manifest;
        $this->rootpath = $rpath;
        $this->path = $rpath2;
    }

    /**
     * performs conversion.
     *
     * @param string $outdir    - root directory of common cartridge
     * @param object $objCourse
     *
     * @return bool
     */
    abstract public function convert($outdir, $objCourse);

    /**
     * Is the element visible in the course?
     *
     * @return bool
     *
     * @throws RuntimeException
     */
    protected function isVisible()
    {
        $tdoc = new XMLGenericDocument();

        return true;
    }

    /**
     * Stores any files that need to be stored.
     *
     * @param mixed      $outdir
     * @param mixed      $title
     * @param null|mixed $deps
     */
    protected function store(CcGeneralFile $doc, $outdir, $title, $deps = null): void
    {
        $rdir = new CcResourceLocation($outdir);
        $rtp = $rdir->fullpath(true).$this->defaultname;
        if ($doc->saveTo($rtp)) {
            $resource = new CcResources($rdir->rootdir(), $this->defaultname, $rdir->dirname(true));
            $resource->dependency = empty($deps) ? [] : $deps;
            $resource->instructoronly = !$this->isVisible();
            $res = $this->manifest->addResource($resource, null, $this->ccType);
            $resitem = new CcItem();
            $resitem->attachResource($res[0]);
            $resitem->title = $title;
            $this->item->addChildItem($resitem);
        } else {
            throw new RuntimeException("Unable to save file {$rtp}!");
        }
    }
}
