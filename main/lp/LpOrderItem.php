<?php
/* For licensing terms, see /license.txt */

/**
 * Class LpOrderItem.
 */
class LpOrderItem
{
    public $id = 0;
    public $parent_item_id = 0;
    public $previous_item_id = 0;
    public $next_item_id = 0;
    public $display_order = 0;

    /**
     * LpOrderItem constructor.
     *
     * @param int $id
     * @param int $parentId
     */
    public function __construct($id = 0, $parentId = 0)
    {
        $this->id = $id;
        $this->parent_item_id = $parentId;
    }
}
