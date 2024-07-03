<?php
/* For licensing terms, see /license.txt */

/**
 * Class Promotion
 * This class provides methods for the promotion management.
 * Include/require it in your code to use its features.
 */
class Promotion extends Model
{
    public $table;
    public $columns = [
        'id',
        'name',
        'description',
        'career_id',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = Database::get_main_table(TABLE_PROMOTION);
    }

    /**
     * Get the count of elements.
     */
    public function get_count()
    {
        $row = Database::select(
            'count(*) as count',
            $this->table,
            [],
            'first'
        );

        return $row['count'];
    }

    /**
     * Copies the promotion to a new one.
     *
     * @param   int     Promotion ID
     * @param   int     Career ID, in case we want to change it
     * @param   bool     Whether or not to copy the sessions inside
     *
     * @return int New promotion ID on success, false on failure
     */
    public function copy($id, $career_id = null, $copy_sessions = false)
    {
        $pid = false;
        $promotion = $this->get($id);
        if (!empty($promotion)) {
            $new = [];
            foreach ($promotion as $key => $val) {
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
                    case 'career_id':
                        if (!empty($career_id)) {
                            $val = (int) $career_id;
                        }
                        $new[$key] = $val;
                        break;
                    default:
                        $new[$key] = $val;
                        break;
                }
            }

            if ($copy_sessions) {
                /**
                 * When copying a session we do:
                 * 1. Copy a new session from the source
                 * 2. Copy all courses from the session (no user data, no user list)
                 * 3. Create the promotion.
                 */
                $session_list = SessionManager::get_all_sessions_by_promotion($id);

                if (!empty($session_list)) {
                    $pid = $this->save($new);
                    if (!empty($pid)) {
                        $new_session_list = [];

                        foreach ($session_list as $item) {
                            $sid = SessionManager::copy(
                                $item['id'],
                                true,
                                false,
                                false,
                                true
                            );
                            $new_session_list[] = $sid;
                        }

                        if (!empty($new_session_list)) {
                            SessionManager::subscribe_sessions_to_promotion(
                                $pid,
                                $new_session_list
                            );
                        }
                    }
                }
            } else {
                $pid = $this->save($new);
            }
        }

        return $pid;
    }

    /**
     * Gets all promotions by career id.
     *
     * @param   int     career id
     * @param bool $order
     *
     * @return array results
     */
    public function get_all_promotions_by_career_id($career_id, $order = false)
    {
        return Database::select(
            '*',
            $this->table,
            [
                'where' => ['career_id = ?' => $career_id],
                'order' => $order,
            ]
        );
    }

    /**
     * @return array
     */
    public function get_status_list()
    {
        return [
            PROMOTION_STATUS_ACTIVE => get_lang('Active'),
            PROMOTION_STATUS_INACTIVE => get_lang('Inactive'),
        ];
    }

    /**
     * Displays the title + grid.
     *
     * @return string html code
     */
    public function display()
    {
        // Action links
        echo '<div class="actions" style="margin-bottom:20px">';
        echo '<a href="career_dashboard.php">'.
            Display::return_icon(
                'back.png',
                get_lang('Back'),
                '',
                '32'
            )
            .'</a>';
        echo '<a href="'.api_get_self().'?action=add">'.
            Display::return_icon(
                'new_promotion.png',
                get_lang('Add'),
                '',
                '32'
            ).'</a>';
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'session/session_add.php">'.
            Display::return_icon(
                'new_session.png',
                get_lang('AddSession'),
                '',
                '32'
            ).'</a>';
        echo '</div>';
        echo Display::grid_html('promotions');
    }

    /**
     * Update all session status by promotion.
     *
     * @param int $promotion_id
     * @param int $status       (1, 0)
     */
    public function update_all_sessions_status_by_promotion_id(
        $promotion_id,
        $status
    ) {
        $sessionList = SessionManager::get_all_sessions_by_promotion($promotion_id);
        if (!empty($sessionList)) {
            foreach ($sessionList as $item) {
                SessionManager::set_session_status($item['id'], $status);
            }
        }
    }

    /**
     * Returns a Form validator Obj.
     *
     * @param string $url
     * @param string $action
     *
     * @return FormValidator
     */
    public function return_form($url, $action = 'add')
    {
        $form = new FormValidator('promotion', 'post', $url);
        // Setting the form elements
        $header = get_lang('Add');
        if ($action == 'edit') {
            $header = get_lang('Modify');
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : '';

        $form->addHeader($header);
        $form->addHidden('id', $id);
        $form->addText('name', get_lang('Name'), true, ['size' => '70', 'id' => 'name']);
        $form->addHtmlEditor(
            'description',
            get_lang('Description'),
            false,
            false,
            [
                'ToolbarSet' => 'Careers',
                'Width' => '100%',
                'Height' => '250',
            ]
        );
        $career = new Career();
        $careers = $career->get_all();
        $career_list = [];
        foreach ($careers as $item) {
            $career_list[$item['id']] = $item['name'];
        }
        $form->addSelect(
            'career_id',
            get_lang('Career'),
            $career_list,
            ['id' => 'career_id']
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
     * @param array $params
     * @param bool  $show_query
     *
     * @return bool
     */
    public function save($params, $show_query = false)
    {
        $id = parent::save($params, $show_query);
        if (!empty($id)) {
            Event::addEvent(
                LOG_PROMOTION_CREATE,
                LOG_PROMOTION_ID,
                $id,
                api_get_utc_datetime(),
                api_get_user_id()
            );
        }

        return $id;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete($id)
    {
        if (parent::delete($id)) {
            SessionManager::clear_session_ref_promotion($id);
            Event::addEvent(
                LOG_PROMOTION_DELETE,
                LOG_PROMOTION_ID,
                $id,
                api_get_utc_datetime(),
                api_get_user_id()
            );
        } else {
            return false;
        }
    }
}
