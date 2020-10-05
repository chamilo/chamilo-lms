<?php
/* For licensing terms, see /license.txt */

/**
 * Class LpItemOrderList.
 *
 * Classes to create a special data structure to manipulate LP Items used only in this file.
 */
class LpItemOrderList
{
    public $list = [];

    /**
     * LpItemOrderList constructor.
     */
    public function __construct()
    {
        $this->list = [];
    }

    /**
     * @param int $parentId
     *
     * @return LpItemOrderList
     */
    public function getItemWithSameParent($parentId)
    {
        $list = new LpItemOrderList();
        for ($i = 0; $i < count($this->list); $i++) {
            if ($this->list[$i]->parent_item_id == $parentId) {
                $list->add($this->list[$i]);
            }
        }

        return $list;
    }

    /**
     * @param array $list
     */
    public function add($list)
    {
        $this->list[] = $list;
    }

    /**
     * @return array
     */
    public function getListOfParents()
    {
        $result = [];
        foreach ($this->list as $item) {
            if (!in_array($item->parent_item_id, $result)) {
                $result[] = $item->parent_item_id;
            }
        }

        return $result;
    }

    /**
     * @param int    $id
     * @param int    $value
     * @param string $parameter
     */
    public function setParametersForId($id, $value, $parameter)
    {
        for ($i = 0; $i < count($this->list); $i++) {
            if ($this->list[$i]->id == $id) {
                $this->list[$i]->$parameter = $value;
                break;
            }
        }
    }
}
