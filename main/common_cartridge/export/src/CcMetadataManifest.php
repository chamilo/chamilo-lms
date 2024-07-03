<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_manifest.php under GNU/GPL license */

class CcMetadataManifest implements CcIMetadataManifest
{
    public $arraygeneral = [];
    public $arraytech = [];
    public $arrayrights = [];
    public $arraylifecycle = [];

    public function addMetadataGeneral($obj)
    {
        if (empty($obj)) {
            throw new Exception('Medatada Object given is invalid or null!');
        }
        !is_null($obj->title) ? $this->arraygeneral['title'] = $obj->title : null;
        !is_null($obj->language) ? $this->arraygeneral['language'] = $obj->language : null;
        !is_null($obj->description) ? $this->arraygeneral['description'] = $obj->description : null;
        !is_null($obj->keyword) ? $this->arraygeneral['keyword'] = $obj->keyword : null;
        !is_null($obj->coverage) ? $this->arraygeneral['coverage'] = $obj->coverage : null;
        !is_null($obj->catalog) ? $this->arraygeneral['catalog'] = $obj->catalog : null;
        !is_null($obj->entry) ? $this->arraygeneral['entry'] = $obj->entry : null;
    }

    public function addMetadataTechnical($obj)
    {
        if (empty($obj)) {
            throw new Exception('Medatada Object given is invalid or null!');
        }
        !is_null($obj->format) ? $this->arraytech['format'] = $obj->format : null;
    }

    public function addMetadataRights($obj)
    {
        if (empty($obj)) {
            throw new Exception('Medatada Object given is invalid or null!');
        }
        !is_null($obj->copyright) ? $this->arrayrights['copyrightAndOtherRestrictions'] = $obj->copyright : null;
        !is_null($obj->description) ? $this->arrayrights['description'] = $obj->description : null;
        !is_null($obj->cost) ? $this->arrayrights['cost'] = $obj->cost : null;
    }

    public function addMetadataLifecycle($obj)
    {
        if (empty($obj)) {
            throw new Exception('Medatada Object given is invalid or null!');
        }
        !is_null($obj->role) ? $this->arraylifecycle['role'] = $obj->role : null;
        !is_null($obj->entity) ? $this->arraylifecycle['entity'] = $obj->entity : null;
        !is_null($obj->date) ? $this->arraylifecycle['date'] = $obj->date : null;
    }
}
