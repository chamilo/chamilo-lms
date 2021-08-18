<?php
/* For licensing terms, see /license.txt */

/**
 * Metadata Resource Educational Type
 *
 */
class CcMetadataResourceEducational
{

    public $value   = array();

    public function setValue ($value){
        $arr = array($value);
        $this->value[] = $arr;
    }
}

/**
 * Metadata Resource
 *
 */
class CcMetadataResource implements CcIMetadataResource
{

    public $arrayeducational  = array();

    public function addMetadataResourceEducational($obj)
    {
        if (empty($obj)){
            throw new Exception('Medatada Object given is invalid or null!');
        }
        $this->arrayeducational['value'] = (!is_null($obj->value)?$obj->value:null);
    }
}
