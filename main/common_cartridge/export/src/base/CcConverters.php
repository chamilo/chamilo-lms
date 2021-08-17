<?php
/* For licensing terms, see /license.txt */

abstract class CcConverters
{

    protected $item     = null;
    protected $manifest = null;
    protected $rootpath = null;
    protected $path     = null;
    protected $defaultfile = null;
    protected $defaultname = null;
    protected $cc_type = null;
    protected $doc = null;

    /**
     *
     * ctor
     * @param  CcIItem $item
     * @param  CcIManifest $manifest
     * @param  string $rootpath
     * @param  string $path
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

        $this->doc      = $doc;
        $this->item     = $item;
        $this->manifest = $manifest;
        $this->rootpath = $rpath;
        $this->path     = $rpath2;
    }

    /**
     *
     * performs conversion
     * @param string $outdir - root directory of common cartridge
     * @return boolean
     */
    abstract public function convert($outdir, $objCourse);

    /**
     *
     * Is the element visible in the course?
     * @throws RuntimeException
     * @return bool
     */
    protected function is_visible()
    {
        $tdoc = new XMLGenericDocument();
        return true;
    }

    /**
     *
     * Stores any files that need to be stored
     */
    protected function store(CcGeneralFile $doc, $outdir, $title, $deps = null)
    {
        $rdir = new CcResourceLocation($outdir);
        $rtp = $rdir->fullpath(true).$this->defaultname;
        if ( $doc->saveTo($rtp) ) {
            $resource = new CcResources($rdir->rootdir(), $this->defaultname, $rdir->dirname(true));
            $resource->dependency = empty($deps) ? array() : $deps;
            $resource->instructoronly = !$this->is_visible();
            $res = $this->manifest->add_resource($resource, null, $this->cc_type);
            $resitem = new cc_item();
            $resitem->attach_resource($res[0]);
            $resitem->title = $title;
            $this->item->add_child_item($resitem);
        } else {
            throw new RuntimeException("Unable to save file {$rtp}!");
        }
    }
}
