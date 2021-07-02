<?php
/* For licensing terms, see /license.txt */

/**
 * CC Organization Interface
 */
interface CcIOrganization
{

    public function add_item (CcIItem &$item);
    public function has_items ();
    public function attr_value (&$nod, $name, $ns=null);
    public function process_organization (&$node,&$doc);

}

