<?php
/* For licensing terms, see /license.txt */

/**
 * Class Timeline
 * Timeline model class definition.
 */
class Timeline extends Model
{
    public $table;
    public $columns = [
        'headline',
        'type',
        'start_date',
        'end_date',
        'text',
        'media',
        'media_credit',
        'media_caption',
        'title_slide',
        'parent_id',
        'status',
        'c_id',
    ];
    public $is_course_model = true;

    /**
     * Timeline constructor.
     */
    public function __construct()
    {
        $this->table = Database::get_course_table(TABLE_TIMELINE);
    }

    /**
     * Get the count of elements.
     *
     * @return int
     */
    public function get_count()
    {
        $course_id = api_get_course_int_id();
        $row = Database::select(
            'count(*) as count',
            $this->table,
            [
                'where' => [
                    'parent_id = ? AND c_id = ?' => [
                        '0',
                        $course_id,
                    ],
                ],
            ],
            'first'
        );

        return $row['count'];
    }

    /**
     * @param array $where_conditions
     *
     * @return array
     */
    public function get_all($where_conditions = [])
    {
        return Database::select(
            '*',
            $this->table,
            ['where' => $where_conditions, 'order' => 'headline ASC']
        );
    }

    /**
     * Displays the title + grid.
     */
    public function listing()
    {
        // action links
        $html = '<div class="actions">';
        $html .= '<a href="'.api_get_self().'?action=add">'.
            Display::return_icon('add.png', get_lang('Add'), '', '32').'</a>';
        $html .= '</div>';
        $html .= Display::grid_html('timelines');

        return $html;
    }

    /**
     * @return array
     */
    public function get_status_list()
    {
        return [
            TIMELINE_STATUS_ACTIVE => get_lang('Active'),
            TIMELINE_STATUS_INACTIVE => get_lang('Inactive'),
        ];
    }

    /**
     * Returns a Form validator Obj.
     *
     * @todo the form should be auto generated
     *
     * @param   string  url
     * @param   string  action add, edit
     *
     * @return obj form validator obj
     */
    public function return_form($url, $action)
    {
        $form = new FormValidator('timeline', 'post', $url);
        // Setting the form elements
        $header = get_lang('Add');
        if ($action == 'edit') {
            $header = get_lang('Modify');
        }
        $form->addElement('header', $header);
        $id = isset($_GET['id']) ? intval($_GET['id']) : '';
        $form->addElement('hidden', 'id', $id);

        $form->addElement('text', 'headline', get_lang('Name'), ['size' => '70']);
        $status_list = $this->get_status_list();
        $form->addElement('select', 'status', get_lang('Status'), $status_list);
        if ($action == 'edit') {
            //$form->addElement('text', 'created_at', get_lang('CreatedAt'));
            //$form->freeze('created_at');
        }
        if ($action == 'edit') {
            $form->addButtonSave(get_lang('Modify'), 'submit');
        } else {
            $form->addButtonCreate(get_lang('Add'), 'submit');
        }

        $form->addRule('headline', get_lang('ThisFieldIsRequired'), 'required');

        // Setting the defaults
        $defaults = $this->get($id);

        /*if (!empty($defaults['created_at'])) {
            $defaults['created_at'] = api_convert_and_format_date($defaults['created_at']);
        }
        if (!empty($defaults['updated_at'])) {
            $defaults['updated_at'] = api_convert_and_format_date($defaults['updated_at']);
        }*/
        $form->setDefaults($defaults);

        // Setting the rules
        $form->addRule('headline', get_lang('ThisFieldIsRequired'), 'required');

        return $form;
    }

    /**
     * @param $url
     * @param $action
     *
     * @return FormValidator
     */
    public function return_item_form($url, $action)
    {
        $form = new FormValidator('item_form', 'post', $url);
        // Setting the form elements
        $header = get_lang('Add');
        if ($action == 'edit') {
            $header = get_lang('Modify');
        }
        $form->addElement('header', $header);
        $id = isset($_GET['id']) ? intval($_GET['id']) : '';
        $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : '';
        $form->addElement('hidden', 'parent_id', $parent_id);
        $form->addElement('hidden', 'id', $id);
        $form->addElement('text', 'headline', get_lang('Name'));

        //@todo fix this
        $form->addElement('text', 'start_date', get_lang('StartDate'), ['size' => '70']);
        $form->addElement('text', 'end_date', get_lang('EndDate'), ['size' => '70']);
        $form->addElement('textarea', 'text', get_lang('TimelineItemText'));
        $form->addElement('text', 'media', get_lang('TimelineItemMedia'), ['size' => '70']);
        $form->addElement('text', 'media_caption', get_lang('TimelineItemMediaCaption'), ['size' => '70']);
        $form->addElement('text', 'media_credit', get_lang('TimelineItemMediaCredit'), ['size' => '70']);
        $form->addElement('text', 'title_slide', get_lang('TimelineItemTitleSlide'), ['size' => '70']);

        $form->addRule('headline', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('start_date', get_lang('ThisFieldIsRequired'), 'required');

        if ($action == 'edit') {
            // Setting the defaults
            $defaults = $this->get($id);
            $form->addButtonSave(get_lang('Modify'), 'submit');
        } else {
            $form->addButtonCreate(get_lang('Add'), 'submit');
        }
        $form->setDefaults($defaults);

        // Setting the rules
        $form->addRule('headline', get_lang('ThisFieldIsRequired'), 'required');

        return $form;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function save_item($params)
    {
        $params['c_id'] = api_get_course_int_id();
        $id = parent::save($params);
        if (!empty($id)) {
            //event_system(LOG_CAREER_CREATE, LOG_CAREER_ID, $id, api_get_utc_datetime(), api_get_user_id());
        }

        return $id;
    }

    /**
     * @param array $params
     * @param bool  $showQuery
     *
     * @return bool
     */
    public function save($params, $showQuery = false)
    {
        $params['c_id'] = api_get_course_int_id();
        $params['parent_id'] = '0';
        $params['type'] = 'default';
        $id = parent::save($params, $showQuery);
        if (!empty($id)) {
            //event_system(LOG_CAREER_CREATE, LOG_CAREER_ID, $id, api_get_utc_datetime(), api_get_user_id());
        }

        return $id;
    }

    /**
     * @param int $id
     */
    public function delete($id)
    {
        parent::delete($id);
        //event_system(LOG_CAREER_DELETE, LOG_CAREER_ID, $id, api_get_utc_datetime(), api_get_user_id());
    }

    /**
     * @param int $id
     *
     * @return string
     */
    public function get_url($id)
    {
        return api_get_path(WEB_AJAX_PATH).'timeline.ajax.php?a=get_timeline_content&id='.intval($id);
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function get_timeline_content($id)
    {
        $timeline = [];
        $course_id = api_get_course_int_id();
        $timeline['timeline'] = $this->process_item($this->get($id));
        $items = $this->process_items($this->get_all(['parent_id = ? AND c_id = ? ' => [$id, $course_id]]));
        $timeline['timeline']['date'] = $items;

        return $timeline;
    }

    public function process_items($items)
    {
        foreach ($items as &$item) {
            $item = $this->process_item($item);
        }
        $new_array = [];
        foreach ($items as $item) {
            $new_array[] = $item;
        }

        return $new_array;
    }

    public function process_item($item)
    {
        $item['startDate'] = $item['start_date'];
        unset($item['start_date']);
        if (!empty($item['end_date'])) {
            $item['endDate'] = $item['end_date'];
        } else {
            unset($item['endDate']);
        }
        unset($item['end_date']);
        // Assets
        $item['asset'] = [
            'media' => $item['media'],
            'credit' => $item['media_credit'],
            'caption' => $item['media_caption'],
        ];

        //Cleaning items
        unset($item['id']);
        if (empty($item['type'])) {
            unset($item['type']);
        }
        unset($item['media']);
        unset($item['media_credit']);
        unset($item['media_caption']);
        unset($item['status']);
        unset($item['title_slide']);
        unset($item['parent_id']);
        unset($item['c_id']);

        return $item;
    }
}
