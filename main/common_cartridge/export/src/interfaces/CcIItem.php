<?php
/* For licensing terms, see /license.txt */

/**
 * CC Item Interface
 */
interface CcIItem
{
    public function add_child_item (CcIItem &$item);
    public function attach_resource ($res);     // can be object or value
    public function has_child_items ();
    public function attr_value (&$nod, $name, $ns=null);
    public function process_item (&$node,&$doc);
}
