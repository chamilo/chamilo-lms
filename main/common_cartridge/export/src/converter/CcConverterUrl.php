<?php
/* For licensing terms, see /license.txt */

class CcConverterUrl extends CcConverters
{

    public function __construct(CcIItem &$item, CcIManifest &$manifest, $rootpath, $path){
        $this->cc_type     = CcVersion13::weblink;
        $this->defaultfile = 'url.xml';
        $this->defaultname = 'weblink.xml';
        parent::__construct($item, $manifest, $rootpath, $path);
    }

    public function convert($outdir, $objLink) {
        $rt = new CcWebLink();        
        $title = $objLink['title'];
        $rt->set_title($title);
        $url = $objLink['url'];
        if (!empty($url)) {            
            $rt->set_url($url, $objLink['target']);
        }
        $this->store($rt, $outdir, $title);
        return true;
    }

}

