<?php
/* For licensing terms, see /license.txt */

/**
 * CC Item Interface.
 */
interface CcIItem
{
    public function addChildItem(CcIItem &$item);

    public function attachResource($res);     // can be object or value

    public function hasChildItems();

    public function attrValue(&$nod, $name, $ns = null);

    public function processItem(&$node, &$doc);
}
