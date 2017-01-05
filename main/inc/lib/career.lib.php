<?php
/* For licensing terms, see /license.txt */

/**
 * Class Career
 */
class Career extends Model
{
    public $table;
    public $columns = array(
        'id',
        'name',
        'description',
        'status',
        'created_at',
        'updated_at'
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_CAREER);
    }

    /**
     * Get the count of elements
     * @return int
     */
    public function get_count()
    {
        $row = Database::select(
            'count(*) as count',
            $this->table,
            array(),
            'first'
        );
        return $row['count'];
    }

    /**
     * @param array $where_conditions
     * @return array
     */
    public function get_all($where_conditions = array())
    {
        return Database::select(
            '*',
            $this->table,
            array('where' => $where_conditions, 'order' => 'name ASC')
        );
    }

    /**
     * Update all promotion status by career
     * @param   int     $career_id
     * @param   int     $status (1 or 0)
     */
    public function update_all_promotion_status_by_career_id($career_id, $status)
    {
        $promotion = new Promotion();
        $promotion_list = $promotion->get_all_promotions_by_career_id($career_id);
        if (!empty($promotion_list)) {
            foreach ($promotion_list  as $item) {
                $params['id'] = $item['id'];
                $params['status'] = $status;
                $promotion->update($params);
                $promotion->update_all_sessions_status_by_promotion_id($params['id'], $status);
            }
        }
    }

    /**
     * Displays the title + grid
     */
    public function display()
    {
        echo '<div class="actions" style="margin-bottom:20px">';
        echo '<a href="career_dashboard.php">'.
            Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
        echo '<a href="'.api_get_self().'?action=add">'.
                Display::return_icon('new_career.png', get_lang('Add'), '', ICON_SIZE_MEDIUM).'</a>';
        echo '</div>';
        echo Display::grid_html('careers');
    }

    /**
     * @return array
     */
    public function get_status_list()
    {
        return array(
            CAREER_STATUS_ACTIVE => get_lang('Unarchived'),
            CAREER_STATUS_INACTIVE => get_lang('Archived')
        );
    }

    /**
     * Returns a Form validator Obj
     * @todo the form should be auto generated
     * @param   string  $url
     * @param   string  $action add, edit
     * @return  FormValidator
     */
    public function return_form($url, $action)
    {
        $form = new FormValidator('career', 'post', $url);
        // Setting the form elements
        $header = get_lang('Add');
        if ($action == 'edit') {
            $header = get_lang('Modify');
        }

        $form->addElement('header', $header);
        $id = isset($_GET['id']) ? intval($_GET['id']) : '';
        $form->addElement('hidden', 'id', $id);
        $form->addElement('text', 'name', get_lang('Name'), array('size' => '70'));
        $form->addHtmlEditor(
            'description',
            get_lang('Description'),
            false,
            false,
            array(
                'ToolbarSet' => 'Careers',
                'Width' => '100%',
                'Height' => '250'
            )
        );
        $status_list = $this->get_status_list();
        $form->addElement('select', 'status', get_lang('Status'), $status_list);
        if ($action == 'edit') {
            $form->addElement('text', 'created_at', get_lang('CreatedAt'));
            $form->freeze('created_at');
        }
        if ($action == 'edit') {
            $form->addButtonSave(get_lang('Modify'), 'submit');
        } else {
            $form->addButtonCreate(get_lang('Add'), 'submit');
        }

        // Setting the defaults
        $defaults = $this->get($id);

        if (!empty($defaults['created_at'])) {
            $defaults['created_at'] = api_convert_and_format_date($defaults['created_at']);
        }
        if (!empty($defaults['updated_at'])) {
            $defaults['updated_at'] = api_convert_and_format_date($defaults['updated_at']);
        }
        $form->setDefaults($defaults);

        // Setting the rules
        $form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

        return $form;
    }

    /**
     * Copies the career to a new one
     * @param   integer     Career ID
     * @param   boolean     Whether or not to copy the promotions inside
     * @return  integer     New career ID on success, false on failure
     */
    public function copy($id, $copy_promotions = false)
    {
        $career = $this->get($id);
        $new = array();
        foreach ($career as $key => $val) {
            switch ($key) {
                case 'id':
                case 'updated_at':
                    break;
                case 'name':
                    $val .= ' '.get_lang('CopyLabelSuffix');
                    $new[$key] = $val;
                    break;
                case 'created_at':
                    $val = api_get_utc_datetime();
                    $new[$key] = $val;
                    break;
                default:
                    $new[$key] = $val;
                    break;
            }
        }
        $cid = $this->save($new);
        if ($copy_promotions) {
            //Now also copy each session of the promotion as a new session and register it inside the promotion
            $promotion = new Promotion();
            $promo_list = $promotion->get_all_promotions_by_career_id($id);
            if (!empty($promo_list)) {
                foreach ($promo_list as $item) {
                    $promotion->copy($item['id'], $cid, true);
                }
            }
        }

        return $cid;
    }

    /**
     * @param int $career_id
     * @return bool
     */
    public function get_status($career_id)
    {
        $TBL_CAREER = Database::get_main_table(TABLE_CAREER);
        $career_id = intval($career_id);
        $sql = "SELECT status FROM $TBL_CAREER WHERE id = '$career_id'";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $data = Database::fetch_array($result);
            return $data['status'];
        } else {
            return false;
        }
    }

    /**
     * @param array $params
     * @param bool $show_query
     * @return bool
     */
    public function save($params, $show_query = false)
    {
        if (isset($params['description'])) {
            $params['description'] = Security::remove_XSS($params['description']);
        }

        $id = parent::save($params);
        if (!empty($id)) {
            Event::addEvent(
                LOG_CAREER_CREATE,
                LOG_CAREER_ID,
                $id,
                api_get_utc_datetime(),
                api_get_user_id()
            );
        }

        return $id;
    }

    /**
     * Delete a record from the career table and report in the default events log table
     * @param int $id The ID of the career to delete
     * @return bool True if the career could be deleted, false otherwise
     */
    public function delete($id)
    {
        $res = parent::delete($id);
        if ($res) {
            Event::addEvent(
                LOG_CAREER_DELETE,
                LOG_CAREER_ID,
                $id,
                api_get_utc_datetime(),
                api_get_user_id()
            );
        }
        return $res;
    }

    /**
     * Update the career table with the given params
     * @param array $params The field values to be set
     * @return bool Returns true if the record could be updated, false otherwise
     */
    public function update($params)
    {
        if (isset($params['description'])) {
            $params['description'] = Security::remove_XSS($params['description']);
        }

        return parent::update($params);
    }
}
