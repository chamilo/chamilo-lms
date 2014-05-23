<?php
/* For licensing terms, see /license.txt */

/**
 * Class ExtraField
 */
class ExtraField extends Model
{
    public $columns = array(
        'id',
        'field_type',
        'field_variable',
        'field_display_text',
        'field_default_value',
        'field_order',
        'field_visible',
        'field_changeable',
        'field_filter',
        'field_loggeable',
        'tms'
    );


    public $ops = array(
        'eq' => '=',        //equal
        'ne' => '<>',       //not equal
        'lt' => '<',        //less than
        'le' => '<=',       //less than or equal
        'gt' => '>',        //greater than
        'ge' => '>=',       //greater than or equal
        'bw' => 'LIKE',     //begins with
        'bn' => 'NOT LIKE', //doesn't begin with
        'in' => 'LIKE',     //is in
        'ni' => 'NOT LIKE', //is not in
        'ew' => 'LIKE',     //ends with
        'en' => 'NOT LIKE', //doesn't end with
        'cn' => 'LIKE',     //contains
        'nc' => 'NOT LIKE'  //doesn't contain
    );

    const FIELD_TYPE_TEXT            = 1;
    const FIELD_TYPE_TEXTAREA        = 2;
    const FIELD_TYPE_RADIO           = 3;
    const FIELD_TYPE_SELECT          = 4;
    const FIELD_TYPE_SELECT_MULTIPLE = 5;
    const FIELD_TYPE_DATE            = 6;
    const FIELD_TYPE_DATETIME        = 7;
    const FIELD_TYPE_DOUBLE_SELECT   = 8;
    const FIELD_TYPE_DIVIDER         = 9;
    const FIELD_TYPE_TAG             = 10;
    const FIELD_TYPE_TIMEZONE        = 11;
    const FIELD_TYPE_SOCIAL_PROFILE  = 12;
    const FIELD_TYPE_CHECKBOX        = 13;

    public $type = 'user'; //or session or course
    public $handler_id = 'user_id';
    public $pageName;
    public $pageUrl;

    /**
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
        switch ($this->type) {
            case 'course':
                $this->table_field_options = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_OPTIONS);
                $this->table_field_values  = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);

                //Used for the model
                $this->table      = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
                $this->handler_id = 'course_code';
                $this->handlerEntityId = 'courseCode';
                $this->primaryKey = 'id';
                break;
            case 'user':
                $this->table_field_options = Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
                $this->table_field_values  = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

                //Used for the model
                $this->table      = Database::get_main_table(TABLE_MAIN_USER_FIELD);
                $this->handler_id = 'user_id';
                $this->handlerEntityId = 'userId';
                $this->primaryKey = 'user_id';
                break;
            case 'session':
                $this->table_field_options = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_OPTIONS);
                $this->table_field_values  = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);

                //Used for the model
                $this->table      = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
                $this->handler_id = 'session_id';
                $this->handlerEntityId = 'sessionId';
                $this->primaryKey = 'id';
                break;
            case 'question':
                $this->table_field_options = Database::get_main_table(TABLE_MAIN_QUESTION_FIELD_OPTIONS);
                $this->table_field_values  = Database::get_main_table(TABLE_MAIN_QUESTION_FIELD_VALUES);

                //Used for the model
                $this->table      = Database::get_main_table(TABLE_MAIN_QUESTION_FIELD);
                $this->handler_id = 'question_id';
                $this->handlerEntityId = 'questionId';
                $this->primaryKey = 'iid';
                break;
            case 'lp':
                $this->table_field_options = Database::get_main_table(TABLE_MAIN_LP_FIELD_OPTIONS);
                $this->table_field_values  = Database::get_main_table(TABLE_MAIN_LP_FIELD_VALUES);

                // Used for the model
                $this->table      = Database::get_main_table(TABLE_MAIN_LP_FIELD);
                $this->handler_id = 'lp_id';
                $this->handlerEntityId = 'lpId';
                $this->primaryKey = 'id';
                break;
        }
        $this->pageUrl  = 'extra_fields.php?type='.$this->type;
        // Example QuestionFields
        $this->pageName = get_lang(ucwords($this->type).'Fields');
    }

    static function getValidExtraFieldTypes()
    {
        return array(
            'user',
            'course',
            'session',
            'question',
            'lp'
        );
    }

    public function get_count()
    {
        $row = Database::select('count(*) as count', $this->table, array(), 'first');

        return $row['count'];
    }

    public function get_all($where_conditions = array(), $order_field_options_by = null)
    {
        $options = Database::select(
            '*',
            $this->table,
            array('where' => $where_conditions, 'order' => 'field_order ASC')
        );

        $field_option = new ExtraFieldOption($this->type);
        if (!empty($options)) {
            foreach ($options as &$option) {
                $option['options'] = $field_option->get_field_options_by_field(
                    $option['id'],
                    false,
                    $order_field_options_by
                );
            }
        }

        return $options;
    }


    public function get_handler_field_info_by_field_variable($field_variable)
    {
        $field_variable = Database::escape_string($field_variable);
        $sql_field      = "SELECT * FROM {$this->table} WHERE field_variable = '$field_variable'";
        $result         = Database::query($sql_field);
        if (Database::num_rows($result)) {
            $r_field = Database::fetch_array($result, 'ASSOC');

            return $r_field;
        } else {
            return false;
        }
    }

    public function get_max_field_order()
    {
        $sql = "SELECT MAX(field_order) FROM {$this->table}";
        $res = Database::query($sql);

        $order = 0;
        if (Database::num_rows($res) > 0) {
            $row   = Database::fetch_row($res);
            $order = $row[0] + 1;
        }

        return $order;
    }

    public static function get_extra_fields_by_handler($handler)
    {
        $types                                   = array();
        $types[self::FIELD_TYPE_TEXT]            = get_lang('FieldTypeText');
        $types[self::FIELD_TYPE_TEXTAREA]        = get_lang('FieldTypeTextarea');
        $types[self::FIELD_TYPE_RADIO]           = get_lang('FieldTypeRadio');
        $types[self::FIELD_TYPE_SELECT]          = get_lang('FieldTypeSelect');
        $types[self::FIELD_TYPE_SELECT_MULTIPLE] = get_lang('FieldTypeSelectMultiple');
        $types[self::FIELD_TYPE_DATE]            = get_lang('FieldTypeDate');
        $types[self::FIELD_TYPE_DATETIME]        = get_lang('FieldTypeDatetime');
        $types[self::FIELD_TYPE_DOUBLE_SELECT]   = get_lang('FieldTypeDoubleSelect');
        $types[self::FIELD_TYPE_DIVIDER]         = get_lang('FieldTypeDivider');
        $types[self::FIELD_TYPE_TAG]             = get_lang('FieldTypeTag');
        $types[self::FIELD_TYPE_TIMEZONE]        = get_lang('FieldTypeTimezone');
        $types[self::FIELD_TYPE_SOCIAL_PROFILE]  = get_lang('FieldTypeSocialProfile');

        switch ($handler) {
            case 'course':
            case 'session':
            case 'user':
                break;
        }

        return $types;
    }

    /**
     * Add elements to a form
     *
     * @param FormValidator $form
     * @param int $item_id
     * @return array|bool
     */
    public function addElements($form, $item_id = null)
    {
        if (empty($form)) {
            return false;
        }

        $extra_data = false;
        if (!empty($item_id)) {
            $extra_data = self::get_handler_extra_data($item_id);
            if ($form) {
                $form->setDefaults($extra_data);
            }
        }

        $extra_fields = $this->get_all(null, 'option_order');
        $extra = $this->set_extra_fields_in_form(
            $form,
            $extra_data,
            $this->type.'_field',
            false,
            false,
            $extra_fields,
            $item_id
        );

        return $extra;
    }

    /**
     *
     * @param int $item_id (session_id, question_id, course id)
     * @return array
     */
    public function get_handler_extra_data($item_id)
    {
        if (empty($item_id)) {
            return array();
        }

        $extra_data   = array();
        $fields       = self::get_all();
        $field_values = new ExtraFieldValue($this->type);

        if (!empty($fields) > 0) {
            foreach ($fields as $field) {
                $field_value = $field_values->get_values_by_handler_and_field_id($item_id, $field['id']);
                if ($field_value) {
                    $field_value = $field_value['field_value'];

                    switch ($field['field_type']) {
                        case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                            $selected_options                                                                           = explode(
                                '::',
                                $field_value
                            );
                            $extra_data['extra_'.$field['field_variable']]['extra_'.$field['field_variable']]           = $selected_options[0];
                            $extra_data['extra_'.$field['field_variable']]['extra_'.$field['field_variable'].'_second'] = $selected_options[1];
                            break;
                        case ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
                            $field_value = explode(';', $field_value);
                        case ExtraField::FIELD_TYPE_RADIO:
                            $extra_data['extra_'.$field['field_variable']]['extra_'.$field['field_variable']] = $field_value;
                            break;
                        default:
                            $extra_data['extra_'.$field['field_variable']] = $field_value;
                            break;
                    }
                } else {
                    // Set default values
                    if (isset($field['field_default_value']) && !empty($field['field_default_value'])) {
                        $extra_data['extra_'.$field['field_variable']] = $field['field_default_value'];
                    }
                }
            }
        }

        return $extra_data;
    }

    public function get_all_extra_field_by_type($field_type)
    {
        // all the information of the field
        $sql    = "SELECT * FROM  {$this->table} WHERE field_type='".Database::escape_string($field_type)."'";
        $result = Database::query($sql);
        $return = array();
        while ($row = Database::fetch_array($result)) {
            $return[] = $row['id'];
        }

        return $return;
    }


    public function get_field_types()
    {
        return self::get_extra_fields_by_handler($this->type);
    }

    public function get_field_type_by_id($id)
    {
        $types = self::get_field_types();
        if (isset($types[$id])) {
            return $types[$id];
        }

        return null;
    }

    /**
     * Converts a string like this:
     * France:Paris;Bretagne;Marseilles;Lyon|Belgique:Bruxelles;Namur;Liège;Bruges|Peru:Lima;Piura;
     * into
     * array('France' => array('Paris', 'Bregtane', 'Marseilles'), 'Belgique' => array('Namur', 'Liège', etc
     * @param string $string
     * @return array
     */
    static function extra_field_double_select_convert_string_to_array($string)
    {
        $options        = explode('|', $string);
        $options_parsed = array();
        $id             = 0;
        if (!empty($options)) {
            foreach ($options as $sub_options) {
                $options             = explode(':', $sub_options);
                $sub_sub_options     = explode(';', $options[1]);
                $options_parsed[$id] = array('label' => $options[0], 'options' => $sub_sub_options);
                $id++;
            }
        }

        return $options_parsed;
    }

    static function extra_field_double_select_convert_array_to_ordered_array($options)
    {
        $options_parsed = array();
        if (!empty($options)) {
            foreach ($options as $option) {
                if ($option['option_value'] == 0) {
                    $options_parsed[$option['id']][] = $option;
                } else {
                    $options_parsed[$option['option_value']][] = $option;
                }
            }
        }

        return $options_parsed;
    }

    /**
     * @param array options the result of the get_field_options_by_field() array
     */
    static function extra_field_double_select_convert_array_to_string($options)
    {
        $string         = null;
        $options_parsed = self::extra_field_double_select_convert_array_to_ordered_array($options);

        if (!empty($options_parsed)) {
            foreach ($options_parsed as $option) {
                foreach ($option as $key => $item) {
                    $string .= $item['option_display_text'];
                    if ($key == 0) {
                        $string .= ':';
                    } else {
                        if (isset($option[$key + 1])) {
                            $string .= ';';
                        }
                    }
                }
                $string .= '|';
            }
        }

        if (!empty($string)) {
            $string = substr($string, 0, strlen($string) - 1);
        }

        return $string;
    }

    /**
     * @param array $params
     * @return array
     */
    public function clean_parameters($params)
    {
        if (!isset($params['field_variable']) || empty($params['field_variable'])) {
            $params['field_variable'] = trim(strtolower(str_replace(" ", "_", $params['field_display_text'])));
        }

        if (!isset($params['field_order'])) {
            $max_order             = self::get_max_field_order();
            $params['field_order'] = $max_order;
        }

        return $params;
    }

    /**
     * @param array $params
     * @param bool $show_query
     * @return bool
     */
    public function save($params, $show_query = false)
    {
        $session_field_info = self::get_handler_field_info_by_field_variable($params['field_variable']);
        $params = self::clean_parameters($params);
        if ($session_field_info) {
            return $session_field_info['id'];
        } else {
            if (!isset($params['tms'])) {
                $params['tms'] = api_get_utc_datetime();
            }
            $id = parent::save($params, $show_query);
            if ($id) {
                $session_field_option = new ExtraFieldOption($this->type);
                $params['field_id']   = $id;
                $session_field_option->save($params);
            }

            return $id;
        }
    }


    public function update($params)
    {
        $params = self::clean_parameters($params);
        if (isset($params['id'])) {
            $field_option       = new ExtraFieldOption($this->type);
            $params['field_id'] = $params['id'];
            $field_option->save($params);
        }
        parent::update($params);
    }

    public function delete($id)
    {
        parent::delete($id);
        $field_option = new ExtraFieldOption($this->type);
        $field_option->delete_all_options_by_field_id($id);

        $session_field_values = new ExtraFieldValue($this->type);
        $session_field_values->delete_all_values_by_field_id($id);
    }

    /**
     * @param FormValidator $form
     * @param array $extraData
     * @param string $form_name
     * @param bool $admin_permissions
     * @param int $user_id
     * @param array $extra
     * @param int $itemId
     *
     * @return array
     */
    public function set_extra_fields_in_form(
        $form,
        $extraData,
        $form_name,
        $admin_permissions = false,
        $user_id = null,
        $extra = array(),
        $itemId = null
    ) {
        $user_id = intval($user_id);
        $type = $this->type;

        // User extra fields
        if ($type == 'user') {
            $extra = UserManager::get_extra_fields(0, 50, 5, 'ASC', true, null, true);
        }

        $jquery_ready_content = null;

        if (!empty($extra)) {
            foreach ($extra as $field_details) {

                // Getting default value id if is set
                $defaultValueId = null;
                if (isset($field_details['options']) && !empty($field_details['options'])) {
                    $valueToFind = null;
                    if (isset($field_details['field_default_value'])) {
                        $valueToFind = $field_details['field_default_value'];
                    }
                    // If a value is found we override the default value
                    if (isset($extraData['extra_'.$field_details['field_variable']])) {
                        $valueToFind = $extraData['extra_'.$field_details['field_variable']];
                    }

                    foreach ($field_details['options'] as $option) {
                        if ($option['option_value'] == $valueToFind) {
                            $defaultValueId = $option['id'];
                        }
                    }
                }

                if (!$admin_permissions) {
                    if ($field_details['field_visible'] == 0) {
                        continue;
                    }
                }

                switch ($field_details['field_type']) {
                    case ExtraField::FIELD_TYPE_TEXT:
                        $form->addElement(
                            'text',
                            'extra_'.$field_details['field_variable'],
                            $field_details['field_display_text'],
                            array('class' => 'span4')
                        );
                        $form->applyFilter('extra_'.$field_details['field_variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['field_variable'], 'trim');
                        if (!$admin_permissions) {
                            if ($field_details['field_visible'] == 0) {
                                $form->freeze(
                                    'extra_'.$field_details['field_variable']
                                );
                            }
                        }
                        break;
                    case ExtraField::FIELD_TYPE_TEXTAREA:
                        $form->add_html_editor(
                            'extra_'.$field_details['field_variable'],
                            $field_details['field_display_text'],
                            false,
                            false,
                            array('ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130')
                        );
                        $form->applyFilter('extra_'.$field_details['field_variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['field_variable'], 'trim');
                        if (!$admin_permissions) {
                            if ($field_details['field_visible'] == 0) {
                                $form->freeze(
                                    'extra_'.$field_details['field_variable']
                                );
                            }
                        }
                        break;
                    case ExtraField::FIELD_TYPE_RADIO:
                        $group = array();
                        if (isset($field_details['options']) && !empty($field_details['options'])) {
                            foreach ($field_details['options'] as $option_details) {
                                $options[$option_details['option_value']] = $option_details['option_display_text'];
                                $group[]                                  = $form->createElement(
                                    'radio',
                                    'extra_'.$field_details['field_variable'],
                                    $option_details['option_value'],
                                    $option_details['option_display_text'].'<br />',
                                    $option_details['option_value']
                                );
                            }
                        }
                        $form->addGroup(
                            $group,
                            'extra_'.$field_details['field_variable'],
                            $field_details['field_display_text'],
                            ''
                        );
                        if (!$admin_permissions) {
                            if ($field_details['field_visible'] == 0) {
                                $form->freeze(
                                    'extra_'.$field_details['field_variable']
                                );
                            }
                        }
                        break;
                    case ExtraField::FIELD_TYPE_CHECKBOX:
                        $group = array();
                        if (isset($field_details['options']) && !empty($field_details['options'])) {
                            foreach ($field_details['options'] as $option_details) {
                                $options[$option_details['option_value']] = $option_details['option_display_text'];
                                $group[]                                  = $form->createElement(
                                    'checkbox',
                                    'extra_'.$field_details['field_variable'],
                                    $option_details['option_value'],
                                    $option_details['option_display_text'].'<br />',
                                    $option_details['option_value']
                                );
                            }
                        } else {
                            // We assume that is a switch on/off with 1 and 0 as values
                            $group[] = $form->createElement(
                                'checkbox',
                                'extra_'.$field_details['field_variable'],
                                null,
                                //$field_details['field_display_text'].'<br />',
                                'Yes <br />',
                                null
                            );
                        }
                        $form->addGroup(
                            $group,
                            'extra_'.$field_details['field_variable'],
                            $field_details['field_display_text'],
                            ''
                        );
                        if (!$admin_permissions) {
                            if ($field_details['field_visible'] == 0) {
                                $form->freeze(
                                    'extra_'.$field_details['field_variable']
                                );
                            }
                        }
                        break;
                    case ExtraField::FIELD_TYPE_SELECT:
                        $get_lang_variables = false;
                        if (in_array(
                            $field_details['field_variable'],
                            array('mail_notify_message', 'mail_notify_invitation', 'mail_notify_group_message')
                        )
                        ) {
                            $get_lang_variables = true;
                        }

                        // Get extra field workflow
                        $userInfo = api_get_user_info();

                        $addOptions = array();

                        global $app;
                        $optionsExists = $app['orm.em']->getRepository('ChamiloLMS\Entity\ExtraFieldOptionRelFieldOption')->
                            findOneBy(array('fieldId' => $field_details['id']));

                        if ($optionsExists) {
                            if (isset($userInfo['status']) && !empty($userInfo['status'])) {

                                $fieldWorkFlow = $app['orm.em']->getRepository('ChamiloLMS\Entity\ExtraFieldOptionRelFieldOption')
                                    ->findBy(
                                        array(
                                            'fieldId' => $field_details['id'],
                                            'relatedFieldOptionId' => $defaultValueId,
                                            'roleId' => $userInfo['status']
                                        )
                                    );
                                foreach ($fieldWorkFlow as $item) {
                                    $addOptions[] = $item->getFieldOptionId();
                                }
                            }
                        }

                        $options = array();
                        if (empty($defaultValueId)) {
                            $options[''] = get_lang('SelectAnOption');
                        }

                        $optionList = array();
                        if (!empty($field_details['options'])) {
                            foreach ($field_details['options'] as $option_details) {
                                $optionList[$option_details['id']] = $option_details;
                                if ($get_lang_variables) {
                                    $options[$option_details['option_value']] = get_lang($option_details['option_display_text']);
                                } else {
                                    if ($optionsExists) {
                                        // Adding always the default value
                                        if ($option_details['id'] == $defaultValueId) {
                                            $options[$option_details['option_value']] = $option_details['option_display_text'];
                                        } else {
                                            if (isset($addOptions) && !empty($addOptions)) {
                                                // Parsing filters
                                                if (in_array($option_details['id'], $addOptions)) {
                                                    $options[$option_details['option_value']] = $option_details['option_display_text'];
                                                }
                                            }
                                        }
                                    } else {
                                        // Normal behaviour
                                        $options[$option_details['option_value']] = $option_details['option_display_text'];
                                    }
                                }
                            }

                            if (isset($optionList[$defaultValueId])) {

                                if (isset($optionList[$defaultValueId]['option_value']) && $optionList[$defaultValueId]['option_value'] == 'aprobada') {
                                    if (api_is_question_manager() == false) {
                                        $form->freeze();
                                    }
                                }
                            }

                            // Setting priority message
                            if (isset($optionList[$defaultValueId]) && isset($optionList[$defaultValueId]['priority'])) {

                                if (!empty($optionList[$defaultValueId]['priority'])) {
                                    $priorityId = $optionList[$defaultValueId]['priority'];
                                    $option = new ExtraFieldOption($this->type);
                                    $messageType = $option->getPriorityMessageType($priorityId);
                                    $form->addElement('label', null, Display::return_message($optionList[$defaultValueId]['priority_message'], $messageType));
                                }
                            }
                        }

                        if ($get_lang_variables) {
                            $field_details['field_display_text'] = get_lang($field_details['field_display_text']);
                        }

                        // chzn-select doesn't work for sessions??
                        $form->addElement(
                            'select',
                            'extra_'.$field_details['field_variable'],
                            $field_details['field_display_text'],
                            $options,
                            array('id' => 'extra_'.$field_details['field_variable'])
                        );

                        if ($optionsExists && $field_details['field_loggeable'] && !empty($defaultValueId)) {

                            $form->addElement(
                                'textarea',
                                'extra_'.$field_details['field_variable'].'_comment',
                                $field_details['field_display_text'].' '.get_lang('Comment')
                            );

                            $extraFieldValue = new ExtraFieldValue($this->type);
                            $repo = $app['orm.em']->getRepository($extraFieldValue->entityName);
                            $repoLog = $app['orm.em']->getRepository('Gedmo\Loggable\Entity\LogEntry');
                            $newEntity = $repo->findOneBy(
                                array(
                                    $this->handlerEntityId => $itemId,
                                    'fieldId' => $field_details['id']
                                )
                            );
                            // @todo move this in a function inside the class
                            if ($newEntity) {
                                $logs = $repoLog->getLogEntries($newEntity);
                                if (!empty($logs)) {
                                    $html = '<b>'.get_lang('LatestChanges').'</b><br /><br />';

                                    $table = new HTML_Table(array('class' => 'data_table'));
                                    $table->setHeaderContents(0, 0, get_lang('Value'));
                                    $table->setHeaderContents(0, 1, get_lang('Comment'));
                                    $table->setHeaderContents(0, 2, get_lang('ModifyDate'));
                                    $table->setHeaderContents(0, 3, get_lang('Username'));
                                    $row = 1;
                                    foreach ($logs as $log) {
                                        $column = 0;
                                        $data = $log->getData();
                                        $fieldValue = isset($data['fieldValue']) ? $data['fieldValue'] : null;
                                        $comment = isset($data['comment']) ? $data['comment'] : null;

                                        $table->setCellContents($row, $column, $fieldValue);
                                        $column++;
                                        $table->setCellContents($row, $column, $comment);
                                        $column++;
                                        $table->setCellContents($row, $column, api_get_local_time($log->getLoggedAt()->format('Y-m-d H:i:s')));
                                        $column++;
                                        $table->setCellContents($row, $column, $log->getUsername());
                                        $row++;
                                    }
                                    $form->addElement('label', null, $html.$table->toHtml());
                                }
                            }
                        }

                        if (!$admin_permissions) {
                            if ($field_details['field_visible'] == 0) {
                                $form->freeze('extra_'.$field_details['field_variable']);
                            }
                        }
                        break;
                    case ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
                        $options = array();
                        foreach ($field_details['options'] as $option_id => $option_details) {
                            $options[$option_details['option_value']] = $option_details['option_display_text'];
                        }
                        $form->addElement(
                            'select',
                            'extra_'.$field_details['field_variable'],
                            $field_details['field_display_text'],
                            $options,
                            array('multiple' => 'multiple')
                        );
                        if (!$admin_permissions) {
                            if ($field_details['field_visible'] == 0) {
                                $form->freeze('extra_'.$field_details['field_variable']);
                            }
                        }
                        break;
                    case ExtraField::FIELD_TYPE_DATE:
                        $form->addElement(
                            'datepickerdate',
                            'extra_'.$field_details['field_variable'],
                            $field_details['field_display_text'],
                            array('form_name' => $form_name)
                        );
                        $form->_elements[$form->_elementIndex['extra_'.$field_details['field_variable']]]->setLocalOption(
                            'minYear',
                            1900
                        );
                        $defaults['extra_'.$field_details['field_variable']] = date('Y-m-d 12:00:00');
                        if (!isset($form->_defaultValues['extra_'.$field_details['field_variable']])) {
                            $form->setDefaults($defaults);
                        }
                        if (!$admin_permissions) {
                            if ($field_details['field_visible'] == 0) {
                                $form->freeze('extra_'.$field_details['field_variable']);
                            }
                        }
                        $form->applyFilter('theme', 'trim');
                        break;
                    case ExtraField::FIELD_TYPE_DATETIME:
                        $form->addElement(
                            'datepicker',
                            'extra_'.$field_details['field_variable'],
                            $field_details['field_display_text'],
                            array('form_name' => $form_name)
                        );
                        $form->_elements[$form->_elementIndex['extra_'.$field_details['field_variable']]]->setLocalOption(
                            'minYear',
                            1900
                        );
                        $defaults['extra_'.$field_details['field_variable']] = date('Y-m-d 12:00:00');
                        if (!isset($form->_defaultValues['extra_'.$field_details['field_variable']])) {
                            $form->setDefaults($defaults);
                        }
                        if (!$admin_permissions) {
                            if ($field_details['field_visible'] == 0) {
                                $form->freeze('extra_'.$field_details['field_variable']);
                            }
                        }
                        $form->applyFilter('theme', 'trim');
                        break;
                    case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                        $first_select_id = 'first_extra_'.$field_details['field_variable'];
                        $url = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?1=1';

                        $jquery_ready_content .= '
                        $("#'.$first_select_id.'").on("change", function() {
                            var id = $(this).val();
                            if (id) {
                                $.ajax({
                                    url: "'.$url.'&a=get_second_select_options",
                                    dataType: "json",
                                    data: "type='.$type.'&field_id='.$field_details['id'].'&option_value_id="+id,
                                    success: function(data) {
                                        $("#second_extra_'.$field_details['field_variable'].'").empty();
                                        $.each(data, function(index, value) {
                                            $("#second_extra_'.$field_details['field_variable'].'").append($("<option/>", {
                                                value: index,
                                                text: value
                                            }));
                                        });
                                    },
                                });
                            } else {
                                $("#second_extra_'.$field_details['field_variable'].'").empty();
                            }
                        });';

                        $first_id  = null;
                        $second_id = null;

                        if (!empty($extraData)) {
                            $first_id  = $extraData['extra_'.$field_details['field_variable']]['extra_'.$field_details['field_variable']];
                            $second_id = $extraData['extra_'.$field_details['field_variable']]['extra_'.$field_details['field_variable'].'_second'];
                        }

                        $options = ExtraField::extra_field_double_select_convert_array_to_ordered_array(
                            $field_details['options']
                        );
                        $values  = array('' => get_lang('Select'));

                        $second_values = array();
                        if (!empty($options)) {
                            foreach ($options as $option) {
                                foreach ($option as $sub_option) {
                                    if ($sub_option['option_value'] == '0') {
                                        $values[$sub_option['id']] = $sub_option['option_display_text'];
                                    } else {
                                        if ($first_id === $sub_option['option_value']) {
                                            $second_values[$sub_option['id']] = $sub_option['option_display_text'];
                                        }
                                    }
                                }
                            }
                        }
                        $group   = array();
                        $group[] = $form->createElement(
                            'select',
                            'extra_'.$field_details['field_variable'],
                            null,
                            $values,
                            array('id' => $first_select_id)
                        );
                        $group[] = $form->createElement(
                            'select',
                            'extra_'.$field_details['field_variable'].'_second',
                            null,
                            $second_values,
                            array('id' => 'second_extra_'.$field_details['field_variable'])
                        );
                        $form->addGroup(
                            $group,
                            'extra_'.$field_details['field_variable'],
                            $field_details['field_display_text'],
                            '&nbsp;'
                        );

                        if (!$admin_permissions) {
                            if ($field_details['field_visible'] == 0) {
                                $form->freeze('extra_'.$field_details['field_variable']);
                            }
                        }
                        break;
                    case ExtraField::FIELD_TYPE_DIVIDER:
                        $form->addElement(
                            'static',
                            $field_details['field_variable'],
                            '<br /><strong>'.$field_details['field_display_text'].'</strong>'
                        );
                        break;
                    case ExtraField::FIELD_TYPE_TAG:
                        $field_variable = $field_details['field_variable'];
                        $field_id       = $field_details['id'];

                        if ($this->type == 'user') {

                            // The magic should be here
                            $user_tags = UserManager::get_user_tags($user_id, $field_details['id']);

                            $tag_list = '';
                            if (is_array($user_tags) && count($user_tags) > 0) {
                                foreach ($user_tags as $tag) {
                                    $tag_list .= '<option value="'.$tag['tag'].'" class="selected">'.$tag['tag'].'</option>';
                                }
                            }
                            $url = api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?';
                        } else {
                            $extraFieldValue = new ExtraFieldValue($this->type);
                            $tags = array();
                            if (!empty($itemId)) {
                                $tags = $extraFieldValue->getAllValuesByItemAndField($itemId, $field_id);
                            }
                            $tag_list = '';
                            if (is_array($tags) && count($tags) > 0) {
                                $extraFieldOption = new ExtraFieldOption($this->type);
                                foreach ($tags as $tag) {
                                    $option = $extraFieldOption->get($tag['field_value']);
                                    $tag_list .= '<option value="'.$option['id'].'" class="selected">'.$option['option_display_text'].'</option>';
                                }
                            }
                            $url = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php';
                        }

                        $form->addElement('hidden', 'extra_'.$field_details['field_variable'].'__persist__', 1);

                        $multiSelect = '<select id="extra_'.$field_details['field_variable'].'" name="extra_'.$field_details['field_variable'].'">
                                        '.$tag_list.'
                                        </select>';

                        $form->addElement('label', $field_details['field_display_text'], $multiSelect);
                        $complete_text = get_lang('StartToType');

                        //if cache is set to true the jquery will be called 1 time

                        $jquery_ready_content .= <<<EOF
                    $("#extra_$field_variable").fcbkcomplete({
                        json_url: "$url?a=search_tags&field_id=$field_id&type={$this->type}",
                        cache: false,
                        filter_case: true,
                        filter_hide: true,
                        complete_text:"$complete_text",
                        firstselected: false,
                        filter_selected: true,
                        newel: true
                    });
EOF;
                        break;
                    case ExtraField::FIELD_TYPE_TIMEZONE:
                        $form->addElement(
                            'select',
                            'extra_'.$field_details['field_variable'],
                            $field_details['field_display_text'],
                            api_get_timezones(),
                            ''
                        );
                        if ($field_details['field_visible'] == 0) {
                            $form->freeze(
                                'extra_'.$field_details['field_variable']
                            );
                        }
                        break;
                    case ExtraField::FIELD_TYPE_SOCIAL_PROFILE:
                        // get the social network's favicon
                        $icon_path = UserManager::get_favicon_from_url(
                            $extraData['extra_'.$field_details['field_variable']],
                            $field_details['field_default_value']
                        );
                        // special hack for hi5
                        $leftpad = '1.7';
                        $top     = '0.4';
                        $domain  = parse_url($icon_path, PHP_URL_HOST);
                        if ($domain == 'www.hi5.com' or $domain == 'hi5.com') {
                            $leftpad = '3';
                            $top     = '0';
                        }
                        // print the input field
                        $form->addElement(
                            'text',
                            'extra_'.$field_details['field_variable'],
                            $field_details['field_display_text'],
                            array(
                                'size'  => 60,
                                'style' => 'background-image: url(\''.$icon_path.'\'); background-repeat: no-repeat; background-position: 0.4em '.$top.'em; padding-left: '.$leftpad.'em; '
                            )
                        );
                        $form->applyFilter('extra_'.$field_details['field_variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['field_variable'], 'trim');
                        if ($field_details['field_visible'] == 0) {
                            $form->freeze('extra_'.$field_details['field_variable']);
                        }
                        break;
                }
            }
        }
        $return = array();
        $return['jquery_ready_content'] = $jquery_ready_content;

        return $return;
    }

    function setupBreadcrumb(&$breadcrumb, $action)
    {
        if ($action == 'add') {
            $breadcrumb[] = array('url' => $this->pageUrl, 'name' => $this->pageName);
            $breadcrumb[] = array('url' => '#', 'name' => get_lang('Add'));
        } elseif ($action == 'edit') {
            $breadcrumb[] = array('url' => $this->pageUrl, 'name' => $this->pageName);
            $breadcrumb[] = array('url' => '#', 'name' => get_lang('Edit'));
        } else {
            $breadcrumb[] = array('url' => '#', 'name' => $this->pageName);
        }
    }


    /**
     * Displays the title + grid
     */
    public function display()
    {
        // action links
        echo '<div class="actions">';
        echo '<a href="../admin/index.php">'.Display::return_icon(
                'back.png',
                get_lang('BackTo').' '.get_lang('PlatformAdmin'),
                '',
                ICON_SIZE_MEDIUM
            ).'</a>';
        echo '<a href="'.api_get_self().'?action=add&type='.$this->type.'">'.Display::return_icon(
                'add_user_fields.png',
                get_lang('Add'),
                '',
                ICON_SIZE_MEDIUM
            ).'</a>';
        echo '</div>';
        echo Display::grid_html($this->type.'_fields');
    }

    public function getJqgridColumnNames()
    {
        return array(
            get_lang('Name'),
            get_lang('FieldLabel'),
            get_lang('Type'),
            get_lang('FieldChangeability'),
            get_lang('Visibility'),
            get_lang('Filter'),
            get_lang('FieldOrder'),
            get_lang('Actions')
        );
    }

    public function getJqgridColumnModel()
    {
        return array(
            array('name' => 'field_display_text', 'index' => 'field_display_text', 'width' => '180', 'align' => 'left'),
            array(
                'name'     => 'field_variable',
                'index'    => 'field_variable',
                'width'    => '',
                'align'    => 'left',
                'sortable' => 'true'
            ),
            array(
                'name'     => 'field_type',
                'index'    => 'field_type',
                'width'    => '',
                'align'    => 'left',
                'sortable' => 'true'
            ),
            array(
                'name'     => 'field_changeable',
                'index'    => 'field_changeable',
                'width'    => '50',
                'align'    => 'left',
                'sortable' => 'true'
            ),
            array(
                'name'     => 'field_visible',
                'index'    => 'field_visible',
                'width'    => '40',
                'align'    => 'left',
                'sortable' => 'true'
            ),
            array(
                'name'     => 'field_filter',
                'index'    => 'field_filter',
                'width'    => '30',
                'align'    => 'left',
                'sortable' => 'true'
            ),
            array(
                'name'     => 'field_order',
                'index'    => 'field_order',
                'width'    => '40',
                'align'    => 'left',
                'sortable' => 'true'
            ),
            array(
                'name'      => 'actions',
                'index'     => 'actions',
                'width'     => '100',
                'align'     => 'left',
                'formatter' => 'action_formatter',
                'sortable'  => 'false'
            )
        );

    }

    /**
     * @param string $url
     * @param string $action
     * @return FormValidator
     */
    public function return_form($url, $action)
    {
        $form = new FormValidator($this->type.'_field', 'post', $url);

        $form->addElement('hidden', 'type', $this->type);
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        $form->addElement('hidden', 'id', $id);

        // Setting the form elements
        $header   = get_lang('Add');
        $defaults = array();

        if ($action == 'edit') {
            $header = get_lang('Modify');
            // Setting the defaults
            $defaults = $this->get($id);
        }

        $form->addElement('header', $header);
        $form->addElement('text', 'field_display_text', get_lang('Name'), array('class' => 'span5'));

        // Field type
        $types = self::get_field_types();

        $form->addElement(
            'select',
            'field_type',
            get_lang('FieldType'),
            $types,
            array('id' => 'field_type', 'class' => 'chzn-select', 'data-placeholder' => get_lang('Select'))
        );
        $form->addElement('label', get_lang('Example'), '<div id="example">-</div>');
        $form->addElement('text', 'field_variable', get_lang('FieldLabel'), array('class' => 'span5'));
        $form->addElement(
            'text',
            'field_options',
            get_lang('FieldPossibleValues'),
            array('id' => 'field_options', 'class' => 'span6')
        );

        $fieldWithOptions = array(
            ExtraField::FIELD_TYPE_SELECT,
            ExtraField::FIELD_TYPE_TAG,
            ExtraField::FIELD_TYPE_DOUBLE_SELECT,
        );

        if ($action == 'edit') {
            if (in_array($defaults['field_type'], $fieldWithOptions)) {
                $url = Display::url(
                    get_lang('EditExtraFieldOptions'),
                    'extra_field_options.php?type='.$this->type.'&field_id='.$id
                );
                $form->addElement('label', null, $url);

                if ($defaults['field_type'] == ExtraField::FIELD_TYPE_SELECT) {
                    $urlWorkFlow = Display::url(
                        get_lang('EditExtraFieldWorkFlow'),
                        'extra_field_workflow.php?type='.$this->type.'&field_id='.$id
                    );
                    $form->addElement('label', null, $urlWorkFlow);
                }

                $form->freeze('field_options');
            }
        }
        $form->addElement(
            'text',
            'field_default_value',
            get_lang('FieldDefaultValue'),
            array('id' => 'field_default_value', 'class' => 'span5')
        );

        $group   = array();
        $group[] = $form->createElement('radio', 'field_visible', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'field_visible', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('Visible'), '', false);

        $group   = array();
        $group[] = $form->createElement('radio', 'field_changeable', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'field_changeable', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('FieldChangeability'), '', false);

        $group   = array();
        $group[] = $form->createElement('radio', 'field_filter', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'field_filter', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('FieldFilter'), '', false);

        $group   = array();
        $group[] = $form->createElement('radio', 'field_loggeable', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'field_loggeable', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('FieldLoggeable'), '', false);


        $form->addElement('text', 'field_order', get_lang('FieldOrder'), array('class' => 'span1'));

        if ($action == 'edit') {
            $option = new ExtraFieldOption($this->type);
            if ($defaults['field_type'] == ExtraField::FIELD_TYPE_DOUBLE_SELECT) {
                $form->freeze('field_options');
            }
            $defaults['field_options'] = $option->get_field_options_by_field_to_string($id);
            $form->addElement('button', 'submit', get_lang('Modify'), 'class="save"');
        } else {
            $defaults['field_visible']    = 0;
            $defaults['field_changeable'] = 0;
            $defaults['field_filter']     = 0;
            $form->addElement('button', 'submit', get_lang('Add'), 'class="save"');
        }

        /*if (!empty($defaults['created_at'])) {
            $defaults['created_at'] = api_convert_and_format_date($defaults['created_at']);
        }
        if (!empty($defaults['updated_at'])) {
            $defaults['updated_at'] = api_convert_and_format_date($defaults['updated_at']);
        }*/
        $form->setDefaults($defaults);

        // Setting the rules
        $form->addRule('field_display_text', get_lang('ThisFieldIsRequired'), 'required');
        //$form->addRule('field_variable', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('field_type', get_lang('ThisFieldIsRequired'), 'required');

        return $form;
    }

    public function getJqgridActionLinks($token)
    {
        //With this function we can add actions to the jgrid (edit, delete, etc)
        return 'function action_formatter(cellvalue, options, rowObject) {
     return \'<a href="?action=edit&type='.$this->type.'&id=\'+options.rowId+\'">'.Display::return_icon(
            'edit.png',
            get_lang('Edit'),
            '',
            ICON_SIZE_SMALL
        ).'</a>'.
        '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(
            api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES)
        )."\'".')) return false;"  href="?sec_token='.$token.'&type='.$this->type.'&action=delete&id=\'+options.rowId+\'">'.Display::return_icon(
            'delete.png',
            get_lang('Delete'),
            '',
            ICON_SIZE_SMALL
        ).'</a>'.
        '\';
    }';
    }

    /**
     * @param array $columns
     * @param array  $column_model
     * @param array  $extraFields
     * @return array
     */
    public function getRules(&$columns, &$column_model, $extraFields = array(), $checkExtraFieldExistence = false)
    {
        $fields = $this->get_all(
            array('field_visible = ? AND field_filter = ?'
            => array(1, 1)
            ),
            'option_display_text'
        );
        $extraFieldOption = new ExtraFieldOption($this->type);

        $rules = array();
        if (!empty($fields)) {
            foreach ($fields as $field) {

                $search_options = array();
                $type           = 'text';
                if (in_array($field['field_type'], array(self::FIELD_TYPE_SELECT, self::FIELD_TYPE_DOUBLE_SELECT))) {
                    $type                   = 'select';
                    $search_options['sopt'] = array('eq', 'ne'); //equal not equal
                } else {
                    $search_options['sopt'] = array('cn', 'nc'); //contains not contains
                }

                $search_options['searchhidden'] = 'true';
                $search_options['defaultValue'] = isset($search_options['field_default_value']) ? $search_options['field_default_value'] : null;

                if ($field['field_type'] == self::FIELD_TYPE_DOUBLE_SELECT) {
                    //Add 2 selects
                    $options       = $extraFieldOption->get_field_options_by_field($field['id']);
                    $options       = self::extra_field_double_select_convert_array_to_ordered_array($options);
                    $first_options = array();

                    if (!empty($options)) {
                        foreach ($options as $option) {
                            foreach ($option as $sub_option) {
                                if ($sub_option['option_value'] == 0) {
                                    $first_options[] = $sub_option['field_id'].'#'.$sub_option['id'].':'.$sub_option['option_display_text'];
                                }
                            }
                        }
                    }

                    $search_options['value']    = implode(';', $first_options);
                    $search_options['dataInit'] = 'fill_second_select';

                    //First
                    $column_model[] = array(
                        'name'          => 'extra_'.$field['field_variable'],
                        'index'         => 'extra_'.$field['field_variable'],
                        'width'         => '100',
                        'hidden'        => 'true',
                        'search'        => 'true',
                        'stype'         => 'select',
                        'searchoptions' => $search_options
                    );
                    $columns[]      = $field['field_display_text'].' (1)';
                    $rules[]        = array('field' => 'extra_'.$field['field_variable'], 'op' => 'cn');

                    //Second
                    $search_options['value']    = $field['id'].':';
                    $search_options['dataInit'] = 'register_second_select';

                    $column_model[] = array(
                        'name'          => 'extra_'.$field['field_variable'].'_second',
                        'index'         => 'extra_'.$field['field_variable'].'_second',
                        'width'         => '100',
                        'hidden'        => 'true',
                        'search'        => 'true',
                        'stype'         => 'select',
                        'searchoptions' => $search_options
                    );
                    $columns[]      = $field['field_display_text'].' (2)';
                    $rules[]        = array('field' => 'extra_'.$field['field_variable'].'_second', 'op' => 'cn');
                    continue;
                } else {
                    $search_options['value'] = $extraFieldOption->get_field_options_to_string(
                        $field['id'],
                        false,
                        'option_display_text'
                    );
                }
                $column_model[] = array(
                    'name'          => 'extra_'.$field['field_variable'],
                    'index'         => 'extra_'.$field['field_variable'],
                    'width'         => '100',
                    'hidden'        => 'true',
                    'search'        => 'true',
                    'stype'         => $type,
                    'searchoptions' => $search_options
                );
                $columns[]      = $field['field_display_text'];
                $rules[]        = array('field' => 'extra_'.$field['field_variable'], 'op' => 'cn');
            }
        }

        return $rules;
    }

    /**
     * @param array $options
     * @return array
     */
    public function parseConditions($options)
    {
        $inject_extra_fields = null;
        $extraFieldOption    = new ExtraFieldOption($this->type);
        $double_fields       = array();

        if (isset($options['extra'])) {
            $extra_fields = $options['extra'];
            if (!empty($extra_fields)) {
                $counter = 1;
                foreach ($extra_fields as &$extra) {
                    $extra_field_obj           = new ExtraField($this->type);
                    $extra_field_info          = $extra_field_obj->get($extra['id']);
                    $extra['extra_field_info'] = $extra_field_info;

                    if (isset($extra_field_info['field_type']) && in_array(
                            $extra_field_info['field_type'],
                            array(
                                ExtraField::FIELD_TYPE_SELECT,
                                ExtraField::FIELD_TYPE_SELECT,
                                ExtraField::FIELD_TYPE_DOUBLE_SELECT
                            )
                        )
                    ) {
                        $inject_extra_fields .= " fvo$counter.option_display_text as {$extra['field']}, ";
                    } else {
                        $inject_extra_fields .= " fv$counter.field_value as {$extra['field']}, ";
                    }

                    if (isset($extra_fields_info[$extra['id']])) {
                        $info = $extra_fields_info[$extra['id']];
                    } else {
                        $info = $this->get($extra['id']);
                        $extra_fields_info[$extra['id']] = $info;
                    }
                    if (isset($info['field_type']) && $info['field_type'] == ExtraField::FIELD_TYPE_DOUBLE_SELECT) {
                        $double_fields[$info['id']] = $info;
                    }
                    $counter++;
                }
            }
        }
        $options_by_double = array();
        foreach ($double_fields as $double) {
            $my_options = $extraFieldOption->get_field_options_by_field(
                $double['id'],
                true
            );
            $options_by_double['extra_'.$double['field_variable']] = $my_options;
        }

        $field_value_to_join = array();

        //filter can be all/any = and/or
        $inject_joins = null;
        $inject_where = null;
        $where        = null;

        if (!empty($options['where'])) {
            if (!empty($options['extra'])) {
                // Removing double 1=1
                $options['where'] = str_replace(' 1 = 1  AND', '', $options['where']);
                // Always OR
                $counter = 1;
                foreach ($extra_fields as $extra_info) {
                    $extra_field_info = $extra_info['extra_field_info'];
                    $inject_joins .= " INNER JOIN $this->table_field_values fv$counter ON (s.".$this->primaryKey." = fv$counter.".$this->handler_id.") ";

                    //Add options
                    if (isset($extra_field_info['field_type']) && in_array(
                            $extra_field_info['field_type'],
                            array(
                                ExtraField::FIELD_TYPE_SELECT,
                                ExtraField::FIELD_TYPE_SELECT,
                                ExtraField::FIELD_TYPE_DOUBLE_SELECT
                            )
                        )
                    ) {
                        $options['where'] = str_replace(
                            $extra_info['field'],
                            'fv'.$counter.'.field_id = '.$extra_info['id'].' AND fvo'.$counter.'.option_value',
                            $options['where']
                        );
                        $inject_joins .= " INNER JOIN $this->table_field_options fvo$counter ".
                            " ON (fv$counter.field_id = fvo$counter.field_id AND fv$counter.field_value = fvo$counter.option_value) ";
                    } else {
                        //text, textarea, etc
                        $options['where'] = str_replace(
                            $extra_info['field'],
                            'fv'.$counter.'.field_id = '.$extra_info['id'].' AND fv'.$counter.'.field_value',
                            $options['where']
                        );
                    }

                    $field_value_to_join[] = " fv$counter.$this->handler_id ";
                    $counter++;
                }
                if (!empty($field_value_to_join)) {
                    //$inject_where .= " AND s.id = ".implode(' = ', $field_value_to_join);
                }
            }
            $where .= ' AND '.$options['where'];
        }

        $order = null;
        if (!empty($options['order'])) {
            $order = " ORDER BY ".$options['order'];
        }
        $limit = null;
        if (!empty($options['limit'])) {
            $limit = " LIMIT ".$options['limit'];
        }

        return array(
            'order'               => $order,
            'limit'               => $limit,
            'where'               => $where,
            'inject_where'        => $inject_where,
            'inject_joins'        => $inject_joins,
            'field_value_to_join' => $field_value_to_join,
            'inject_extra_fields' => $inject_extra_fields,
        );
    }


    //@todo move this in the display_class or somewhere else
    /**
     * @param $col
     * @param $oper
     * @param $val
     * @return string
     */
    public function get_where_clause($col, $oper, $val)
    {

        if (empty($col)) {
            return '';
        }
        if ($oper == 'bw' || $oper == 'bn') {
            $val .= '%';
        }
        if ($oper == 'ew' || $oper == 'en') {
            $val = '%'.$val;
        }
        if ($oper == 'cn' || $oper == 'nc' || $oper == 'in' || $oper == 'ni') {
            $val = '%'.$val.'%';
        }
        $val = \Database::escape_string($val);

        return " $col {$this->ops[$oper]} '$val' ";
    }

    public function getExtraFieldRules($filters, $stringToSearch = 'extra_')
    {
        $extra_fields = array();

        // Getting double select if exists
        $double_select = array();
        foreach ($filters->rules as $rule) {
            if (strpos($rule->field, '_second') === false) {

            } else {
                $my_field = str_replace('_second', '', $rule->field);
                $double_select[$my_field] = $rule->data;
            }
        }

        $condition_array = array();

        foreach ($filters->rules as $rule) {

            if (strpos($rule->field, $stringToSearch) === false) {
                //normal fields
                $field = $rule->field;

                if (isset($rule->data) && $rule->data != -1) {
                    $condition_array[] = $this->get_where_clause($field, $rule->op, $rule->data);
                }
            } else {
                // Extra fields

                if (strpos($rule->field, '_second') === false) {
                    //No _second
                    $original_field = str_replace($stringToSearch, '', $rule->field);
                    $field_option = $this->get_handler_field_info_by_field_variable($original_field);

                    if ($field_option['field_type'] == ExtraField::FIELD_TYPE_DOUBLE_SELECT) {

                        if (isset($double_select[$rule->field])) {
                            $data = explode('#', $rule->data);
                            $rule->data = $data[1].'::'.$double_select[$rule->field];
                        } else {
                            // only was sent 1 select
                            $data = explode('#', $rule->data);
                            $rule->data = $data[1];
                        }

                        if (!isset($rule->data)) {
                            $condition_array[] = ' ('.$this->get_where_clause($rule->field, $rule->op, $rule->data).') ';
                            $extra_fields[] = array('field' => $rule->field, 'id' => $field_option['id']);
                        }
                    } else {
                        if (isset($rule->data)) {
                            if ($rule->data == -1) {
                                continue;
                            }
                            $condition_array[] = ' ('.$this->get_where_clause($rule->field, $rule->op, $rule->data).') ';
                            $extra_fields[] = array(
                                'field' => $rule->field,
                                'id' => $field_option['id'],
                                'data' => $rule->data
                            );
                        }
                    }
                } else {
                    $my_field = str_replace('_second', '', $rule->field);
                    $original_field = str_replace($stringToSearch, '', $my_field);
                    $field_option = $this->get_handler_field_info_by_field_variable($original_field);
                    $extra_fields[] = array(
                        'field' => $rule->field,
                        'id' => $field_option['id']
                    );
                }
            }
        }

        return array(
            'extra_fields' => $extra_fields,
            'condition_array' => $condition_array
        );
    }
}
