<?php
/* For licensing terms, see /license.txt */

class CcConverterForum extends CcConverters 
{

    public function __construct(CcIItem &$item, CcIManifest &$manifest, $rootpath, $path){
        $this->cc_type     = CcVersion13::discussiontopic;
        $this->defaultfile = 'forum.xml';
        $this->defaultname = 'discussion.xml';
        parent::__construct($item, $manifest, $rootpath, $path);
    }

    public function convert($outdir, $item) {
        
        $rt = new CcForum();
        
        $title = $item['title'];
        $rt->set_title($title);        
        $text = $item['comment'];
        
        $deps = null;
        if (!empty($text)) {            
            $contextid = $item['source_id'];            
            $result = CcHelpers::process_linked_files($text,
                                                       $this->manifest,
                                                       $this->rootpath,
                                                       $contextid,
                                                       $outdir);
            $textformat = 'text/html';
            $rt->set_text($result[0], $textformat);
            $deps = $result[1];
        }
        $this->store($rt, $outdir, $title, $deps);
        return true;
    }

}

