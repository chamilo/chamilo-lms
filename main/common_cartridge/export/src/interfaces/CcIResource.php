<?php
/* For licensing terms, see /license.txt */

/**
 * CC Resource Interface
 */
interface CcIResource
{

    public function get_attr_value (&$nod, $name, $ns=null);
    public function add_resource ($fname, $location='');
    public function import_resource (DOMElement &$node, CcIManifest &$doc);
    public function process_resource ($manifestroot, &$fname,$folder);

}

