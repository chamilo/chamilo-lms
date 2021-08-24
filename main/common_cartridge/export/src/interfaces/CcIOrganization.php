<?php
/* For licensing terms, see /license.txt */

/**
 * CC Organization Interface.
 */
interface CcIOrganization
{
    public function addItem(CcIItem &$item);

    public function hasItems();

    public function attrValue(&$nod, $name, $ns = null);

    public function processOrganization(&$node, &$doc);
}
