<?php
/* For licensing terms, see /license.txt */

/**
 * Class lp_item
 *  made to manipulate data of lp_item table.
 *
 * This class is still incomplete
 * You can add lp_item database manipulation function here
 */
class LpItem
{
    public $c_id = 0;
    public $id = 0;
    public $lp_id = 0;
    public $item_type = '';
    public $ref = '';
    public $title = '';
    public $description = '';
    public $path = '';
    public $min_score = 0;
    public $max_score = 0;
    public $mastery_score = 0;
    public $parent_item_id = 0;
    public $previous_item_id = 0;
    public $next_item_id = 0;
    public $display_order = 0;
    public $prerequisite = '';
    public $parameters = '';
    public $launch_data = '';
    public $max_time_allowed = '';
    public $terms = '';
    public $search_did = 0;
    public $audio = '';

    /**
     * LpItem constructor.
     *
     * @param int $in_c_id
     * @param int $in_id
     */
    public function __construct($in_c_id = 0, $in_id = 0)
    {
        if ($in_c_id > 0 && $in_id > 0) {
            $item_view_table = Database::get_course_table(TABLE_LP_ITEM);
            $sql = "SELECT * FROM $item_view_table
                    WHERE
                        c_id=".intval($in_c_id)." AND
                        iid=".intval($in_id);

            $res = Database::query($sql);
            $data = Database::fetch_array($res);
            if (Database::num_rows($res) > 0) {
                $this->c_id = $data['c_id'];
                $this->id = $data['id'];
                $this->lp_id = $data['lp_id'];
                $this->item_type = $data['item_type'];
                $this->ref = $data['ref'];
                $this->title = $data['title'];
                $this->description = $data['description'];
                $this->path = $data['path'];
                $this->min_score = $data['min_score'];
                $this->max_score = $data['max_score'];
                $this->mastery_score = $data['mastery_score'];
                $this->parent_item_id = $data['parent_item_id'];
                $this->previous_item_id = $data['previous_item_id'];
                $this->next_item_id = $data['next_item_id'];
                $this->display_order = $data['display_order'];
                $this->prerequisite = $data['prerequisite'];
                $this->parameters = $data['parameters'];
                $this->launch_data = $data['launch_data'];
                $this->max_time_allowed = $data['max_time_allowed'];
                $this->terms = $data['terms'];
                $this->search_did = $data['search_did'];
                $this->audio = $data['audio'];
            }
        }
    }

    /**
     * Update in database.
     */
    public function update()
    {
        $table = Database::get_course_table(TABLE_LP_ITEM);
        if ($this->c_id > 0 && $this->id > 0) {
            $sql = "UPDATE $table SET
                        lp_id = '".intval($this->lp_id)."' ,
                        item_type = '".Database::escape_string($this->item_type)."' ,
                        ref = '".Database::escape_string($this->ref)."' ,
                        title = '".Database::escape_string($this->title)."' ,
                        description = '".Database::escape_string($this->description)."' ,
                        path = '".Database::escape_string($this->path)."' ,
                        min_score = '".Database::escape_string($this->min_score)."' ,
                        max_score = '".Database::escape_string($this->max_score)."' ,
                        mastery_score = '".Database::escape_string($this->mastery_score)."' ,
                        parent_item_id = '".Database::escape_string($this->parent_item_id)."' ,
                        previous_item_id = '".Database::escape_string($this->previous_item_id)."' ,
                        next_item_id = '".Database::escape_string($this->next_item_id)."' ,
                        display_order = '".Database::escape_string($this->display_order)."' ,
                        prerequisite = '".Database::escape_string($this->prerequisite)."' ,
                        parameters = '".Database::escape_string($this->parameters)."' ,
                        launch_data = '".Database::escape_string($this->launch_data)."' ,
                        max_time_allowed = '".Database::escape_string($this->max_time_allowed)."' ,
                        terms = '".Database::escape_string($this->terms)."' ,
                        search_did = '".Database::escape_string($this->search_did)."' ,
                        audio = '".Database::escape_string($this->audio)."'
                    WHERE c_id=".$this->c_id." AND id=".$this->id;
            Database::query($sql);
        }
    }

    /**
     * Create extra field for learning path item.
     *
     * @param string      $variable
     * @param int         $fieldType
     * @param string      $displayText
     * @param string|null $default         Optional.
     * @param bool        $changeable      Optional.
     * @param bool        $visibleToSelf   Optional.
     * @param bool        $visibleToOthers Optional.
     *
     * @return bool|int
     */
    public static function createExtraField(
        $variable,
        $fieldType,
        $displayText,
        $default = null,
        $changeable = false,
        $visibleToSelf = false,
        $visibleToOthers = false
    ) {
        $extraField = new ExtraField('lp_item');
        $params = [
            'variable' => $variable,
            'field_type' => $fieldType,
            'display_text' => $displayText,
            'default_value' => $default,
            'changeable' => $changeable,
            'visible_to_self' => $visibleToSelf,
            'visible_to_others' => $visibleToOthers,
        ];

        return $extraField->save($params);
    }
}
