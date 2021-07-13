<?php
/* For licensing terms, see /license.txt */

class CcMetadataManifest implements CcIMetadataManifest
{
    
    public  $arraygeneral   = array();
    public  $arraytech      = array();
    public  $arrayrights    = array();
    public  $arraylifecycle = array();


    public function add_metadata_general($obj){
        if (empty($obj)){
            throw new Exception('Medatada Object given is invalid or null!');
        }
        !is_null($obj->title)? $this->arraygeneral['title']=$obj->title:null;
        !is_null($obj->language)? $this->arraygeneral['language']=$obj->language:null;
        !is_null($obj->description)? $this->arraygeneral['description']=$obj->description:null;
        !is_null($obj->keyword)? $this->arraygeneral['keyword']=$obj->keyword:null;
        !is_null($obj->coverage)? $this->arraygeneral['coverage']=$obj->coverage:null;
        !is_null($obj->catalog)? $this->arraygeneral['catalog']=$obj->catalog:null;
        !is_null($obj->entry)? $this->arraygeneral['entry']=$obj->entry:null;
    }

    public function add_metadata_technical($obj){
        if (empty($obj)){
            throw new Exception('Medatada Object given is invalid or null!');
        }
        !is_null($obj->format)? $this->arraytech['format']=$obj->format:null;
    }


    public function add_metadata_rights($obj){
        if (empty($obj)){
            throw new Exception('Medatada Object given is invalid or null!');
        }
        !is_null($obj->copyright)? $this->arrayrights['copyrightAndOtherRestrictions']=$obj->copyright:null;
        !is_null($obj->description)? $this->arrayrights['description']=$obj->description:null;
        !is_null($obj->cost)? $this->arrayrights['cost']=$obj->cost:null;

    }


    public function add_metadata_lifecycle($obj){
        if (empty($obj)){
            throw new Exception('Medatada Object given is invalid or null!');
        }
        !is_null($obj->role)? $this->arraylifecycle['role']=$obj->role:null;
        !is_null($obj->entity)? $this->arraylifecycle['entity']=$obj->entity:null;
        !is_null($obj->date)? $this->arraylifecycle['date']=$obj->date:null;

    }
    
}

/**
 * Metadata General Type
 *
 */
class cc_metadata_general {

    public  $title          = array();
    public  $language       = array();
    public  $description    = array();
    public  $keyword        = array();
    public  $coverage       = array();
    public  $catalog        = array();
    public  $entry          = array();



    public function set_coverage ($coverage,$language){
        $this->coverage[] = array($language,$coverage);
    }
    public function set_description ($description,$language){
        $this->description[] = array($language,$description);
    }
    public function set_keyword ($keyword,$language){
        $this->keyword[] = array($language,$keyword);
    }
    public function set_language ($language){
        $this->language[] = array($language);
    }
    public function set_title ($title,$language){
        $this->title[] = array($language,$title);
    }
    public function set_catalog ($cat){
        $this->catalog[] = array($cat);
    }
    public function set_entry ($entry){
        $this->entry[] = array($entry);
    }


}

