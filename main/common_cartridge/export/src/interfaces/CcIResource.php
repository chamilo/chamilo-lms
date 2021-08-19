<?php
/* For licensing terms, see /license.txt */

/**
 * CC Resource Interface.
 */
interface CcIResource
{
    public function getAttrValue(&$nod, $name, $ns = null);

    public function addResource($fname, $location = '');

    public function importResource(DOMElement &$node, CcIManifest &$doc);

    public function processResource($manifestroot, &$fname, $folder);
}
