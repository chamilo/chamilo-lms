<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;

/**
 * Class ExtraField
 */
class ExtraField extends Model
{
    public $columns = array(
        'id',
        'field_type',
        'variable',
        'display_text',
        'default_value',
        'field_order',
        'visible_to_self',
        'visible_to_others',
        'changeable',
        'filter',
        'extra_field_type',
         /* Enable this when field_loggeable is introduced as a table field (2.0)
        'field_loggeable',
         */
        'created_at'
    );

    public $ops = array(
        'eq' => '=', //equal
        'ne' => '<>', //not equal
        'lt' => '<', //less than
        'le' => '<=', //less than or equal
        'gt' => '>', //greater than
        'ge' => '>=', //greater than or equal
        'bw' => 'LIKE', //begins with
        'bn' => 'NOT LIKE', //doesn't begin with
        'in' => 'LIKE', //is in
        'ni' => 'NOT LIKE', //is not in
        'ew' => 'LIKE', //ends with
        'en' => 'NOT LIKE', //doesn't end with
        'cn' => 'LIKE', //contains
        'nc' => 'NOT LIKE'  //doesn't contain
    );

    const FIELD_TYPE_TEXT = 1;
    const FIELD_TYPE_TEXTAREA = 2;
    const FIELD_TYPE_RADIO = 3;
    const FIELD_TYPE_SELECT = 4;
    const FIELD_TYPE_SELECT_MULTIPLE = 5;
    const FIELD_TYPE_DATE = 6;
    const FIELD_TYPE_DATETIME = 7;
    const FIELD_TYPE_DOUBLE_SELECT = 8;
    const FIELD_TYPE_DIVIDER = 9;
    const FIELD_TYPE_TAG = 10;
    const FIELD_TYPE_TIMEZONE = 11;
    const FIELD_TYPE_SOCIAL_PROFILE = 12;
    const FIELD_TYPE_CHECKBOX = 13;
    const FIELD_TYPE_MOBILE_PHONE_NUMBER = 14;
    const FIELD_TYPE_INTEGER = 15;
    const FIELD_TYPE_FILE_IMAGE = 16;
    const FIELD_TYPE_FLOAT = 17;
    const FIELD_TYPE_FILE = 18;
    const FIELD_TYPE_VIDEO_URL = 19;
    const FIELD_TYPE_LETTERS_ONLY = 20;
    const FIELD_TYPE_ALPHANUMERIC = 21;
    const FIELD_TYPE_LETTERS_SPACE = 22;
    const FIELD_TYPE_ALPHANUMERIC_SPACE = 23;
    const FIELD_TYPE_GEOLOCALIZATION = 24;
    const FIELD_TYPE_GEOLOCALIZATION_COORDINATES = 25;

    public $type = 'user';
    public $pageName;
    public $pageUrl;
    public $extraFieldType = 0;

    public $table_field_options;
    public $table_field_values;
    public $table_field_tag;
    public $table_field_rel_tag;

    public $handler_id;
    public $primaryKey;

    /**
     * @param string $type
     */
    public function __construct($type)
    {
        parent::__construct();

        $this->type = $type;
        $this->table = Database::get_main_table(TABLE_EXTRA_FIELD);
        $this->table_field_options = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);
        $this->table_field_values = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $this->table_field_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $this->table_field_rel_tag = Database::get_main_table(TABLE_MAIN_EXTRA_FIELD_REL_TAG);

        $this->handler_id = 'item_id';

        switch ($this->type) {
            case 'calendar_event':
                $this->extraFieldType = EntityExtraField::CALENDAR_FIELD_TYPE;
                break;
            case 'course':
                $this->extraFieldType = EntityExtraField::COURSE_FIELD_TYPE;
                $this->primaryKey = 'id';
                break;
            case 'user':
                $this->extraFieldType = EntityExtraField::USER_FIELD_TYPE;
                $this->primaryKey = 'id';
                break;
            case 'session':
                $this->extraFieldType = EntityExtraField::SESSION_FIELD_TYPE;
                $this->primaryKey = 'id';
                break;
            case 'question':
                $this->extraFieldType = EntityExtraField::QUESTION_FIELD_TYPE;
                break;
            case 'lp':
                $this->extraFieldType = EntityExtraField::LP_FIELD_TYPE;
                break;
            case 'lp_item':
                $this->extraFieldType = EntityExtraField::LP_ITEM_FIELD_TYPE;
                break;
            case 'skill':
                $this->extraFieldType = EntityExtraField::SKILL_FIELD_TYPE;
                break;
            case 'work':
                $this->extraFieldType = EntityExtraField::WORK_FIELD_TYPE;
                break;
        }

        $this->pageUrl  = 'extra_fields.php?type='.$this->type;
        // Example QuestionFields
        $this->pageName = get_lang(ucwords($this->type).'Fields');
    }

    /**
     * @return int
     */
    public function getExtraFieldType()
    {
        return (int) $this->extraFieldType;
    }

    /**
     * @return array
     */
    public static function getValidExtraFieldTypes()
    {
        return array(
            'user',
            'course',
            'session',
            'question',
            'lp',
            'calendar_event',
            'lp_item',
            'skill',
            'work'
        );
    }

    /**
     * @return int
     */
    public function get_count()
    {
        $em = Database::getManager();
        $query = $em->getRepository('ChamiloCoreBundle:ExtraField')->createQueryBuilder('e');
        $query->select('count(e.id)');
        $query->where('e.extraFieldType = :type');
        $query->setParameter('type', $this->getExtraFieldType());

        return $query->getQuery()->getScalarResult();
    }

    /**
     * @param string $sidx
     * @param string $sord
     * @param int $start
     * @param int $limit
     *
     * @return array
     */
    public function getAllGrid($sidx, $sord, $start, $limit)
    {
        switch ($sidx) {
            case 'field_order':
                $sidx = 'e.fieldOrder';
                break;
            case 'variable':
                $sidx = 'e.variable';
                break;
            case 'display_text':
                $sidx = 'e.displayText';
                break;
            case 'changeable':
                $sidx = 'e.changeable';
                break;
            case 'visible_to_self':
                $sidx = 'e.visibleToSelf';
                break;
            case 'visible_to_others':
                $sidx = 'e.visibleToOthers';
                break;
            case 'filter':
                $sidx = 'e.filter';
                break;
            case 'display_text':
                $sidx = 'e.fieldType';
                break;
        }
        $em = Database::getManager();
        $query = $em->getRepository('ChamiloCoreBundle:ExtraField')->createQueryBuilder('e');
        $query->select('e')
            ->where('e.extraFieldType = :type')
            ->setParameter('type', $this->getExtraFieldType())
            ->orderBy($sidx, $sord)
            ->setFirstResult($start)
            ->setMaxResults($limit);
        //echo $query->getQuery()->getSQL();
        return $query->getQuery()->getArrayResult();
    }

    /**
     * @param array $conditions
     * @param null  $order_field_options_by
     *
     * @return array
     */
    public function get_all($conditions = array(), $order_field_options_by = null)
    {
        $conditions = Database::parse_conditions(array('where' => $conditions));

        if (empty($conditions)) {
            $conditions .= " WHERE extra_field_type = ".$this->extraFieldType;
        } else {
            $conditions .= " AND extra_field_type = ".$this->extraFieldType;
        }

        $sql = "SELECT * FROM $this->table
                $conditions
                ORDER BY field_order ASC
        ";

        $result = Database::query($sql);
        $extraFields = Database::store_result($result, 'ASSOC');

        $option = new ExtraFieldOption($this->type);
        if (!empty($extraFields)) {
            foreach ($extraFields as &$extraField) {
                $extraField['display_text'] = self::translateDisplayName(
                    $extraField['variable'],
                    $extraField['display_text']
                );
                $extraField['options'] = $option->get_field_options_by_field(
                    $extraField['id'],
                    false,
                    $order_field_options_by
                );
            }
        }

        return $extraFields;
    }

    /**
     * @param string $variable
     *
     * @return array|bool
     */
    public function get_handler_field_info_by_field_variable($variable)
    {
        $variable = Database::escape_string($variable);
        $sql = "SELECT * FROM {$this->table}
                WHERE
                    variable = '$variable' AND
                    extra_field_type = $this->extraFieldType";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');
            $row['display_text'] = self::translateDisplayName($row['variable'], $row['display_text']);

            // All the options of the field
            $sql = "SELECT * FROM $this->table_field_options
                    WHERE field_id='".intval($row['id'])."'
                    ORDER BY option_order ASC";
            $result = Database::query($sql);
            while ($option = Database::fetch_array($result)) {
                $row['options'][$option['id']] = $option;
            }

            return $row;
        } else {
            return false;
        }
    }

    /**
     * Get all the field info for tags
     * @param string $variable
     *
     * @return array|bool
     */
    public function get_handler_field_info_by_tags($variable)
    {
        $variable = Database::escape_string($variable);
        $sql = "SELECT * FROM {$this->table}
                WHERE
                    variable = '$variable' AND
                    extra_field_type = $this->extraFieldType";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');
            $row['display_text'] = self::translateDisplayName(
                $row['variable'],
                $row['display_text']
            );

            // All the tags of the field
            $sql = "SELECT * FROM $this->table_field_tag
                    WHERE field_id='".intval($row['id'])."'
                    ORDER BY id ASC";
            $result = Database::query($sql);
            while ($option = Database::fetch_array($result, 'ASSOC')) {
                $row['options'][$option['id']] = $option;
            }

            return $row;
        } else {
            return false;
        }
    }

    /**
     * @param int $fieldId
     *
     * @return array|bool
     */
    public function getFieldInfoByFieldId($fieldId)
    {
        $fieldId = intval($fieldId);
        $sql = "SELECT * FROM {$this->table}
                WHERE
                    id = '$fieldId' AND
                    extra_field_type = $this->extraFieldType";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');

            // All the options of the field
            $sql = "SELECT * FROM $this->table_field_options
                    WHERE field_id='".$fieldId."'
                    ORDER BY option_order ASC";
            $result = Database::query($sql);
            while ($option = Database::fetch_array($result)) {
                $row['options'][$option['id']] = $option;
            }

            return $row;
        } else {
            return false;
        }
    }

    /**
     * @return int
     */
    public function get_max_field_order()
    {
        $sql = "SELECT MAX(field_order)
                FROM {$this->table}
                WHERE
                    extra_field_type = '.$this->extraFieldType.'";
        $res = Database::query($sql);

        $order = 0;
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_row($res);
            $order = $row[0] + 1;
        }

        return $order;
    }

    /**
     * @param string $handler
     *
     * @return array
     */
    public static function get_extra_fields_by_handler($handler)
    {
        $types = array();
        $types[self::FIELD_TYPE_TEXT] = get_lang('FieldTypeText');
        $types[self::FIELD_TYPE_TEXTAREA] = get_lang('FieldTypeTextarea');
        $types[self::FIELD_TYPE_RADIO] = get_lang('FieldTypeRadio');
        $types[self::FIELD_TYPE_SELECT] = get_lang('FieldTypeSelect');
        $types[self::FIELD_TYPE_SELECT_MULTIPLE] = get_lang('FieldTypeSelectMultiple');
        $types[self::FIELD_TYPE_DATE] = get_lang('FieldTypeDate');
        $types[self::FIELD_TYPE_DATETIME] = get_lang('FieldTypeDatetime');
        $types[self::FIELD_TYPE_DOUBLE_SELECT] = get_lang('FieldTypeDoubleSelect');
        $types[self::FIELD_TYPE_DIVIDER] = get_lang('FieldTypeDivider');
        $types[self::FIELD_TYPE_TAG] = get_lang('FieldTypeTag');
        $types[self::FIELD_TYPE_TIMEZONE] = get_lang('FieldTypeTimezone');
        $types[self::FIELD_TYPE_SOCIAL_PROFILE] = get_lang('FieldTypeSocialProfile');
        $types[self::FIELD_TYPE_MOBILE_PHONE_NUMBER] = get_lang('FieldTypeMobilePhoneNumber');
        $types[self::FIELD_TYPE_CHECKBOX] = get_lang('FieldTypeCheckbox');
        $types[self::FIELD_TYPE_INTEGER] = get_lang('FieldTypeInteger');
        $types[self::FIELD_TYPE_FILE_IMAGE] = get_lang('FieldTypeFileImage');
        $types[self::FIELD_TYPE_FLOAT] = get_lang('FieldTypeFloat');
        $types[self::FIELD_TYPE_FILE] = get_lang('FieldTypeFile');
        $types[self::FIELD_TYPE_VIDEO_URL] = get_lang('FieldTypeVideoUrl');
        $types[self::FIELD_TYPE_LETTERS_ONLY] = get_lang('FieldTypeOnlyLetters');
        $types[self::FIELD_TYPE_ALPHANUMERIC] = get_lang('FieldTypeAlphanumeric');
        $types[self::FIELD_TYPE_LETTERS_SPACE] = get_lang(
            'FieldTypeLettersSpaces'
        );
        $types[self::FIELD_TYPE_ALPHANUMERIC_SPACE] = get_lang(
            'FieldTypeAlphanumericSpaces'
        );
        $types[self::FIELD_TYPE_GEOLOCALIZATION] = get_lang(
            'Geolocalization'
        );
        $types[self::FIELD_TYPE_GEOLOCALIZATION_COORDINATES] = get_lang(
            'GeolocalizationCoordinates'
        );

        switch ($handler) {
            case 'course':
                // no break
            case 'session':
                // no break
            case 'user':
                // no break
            case 'skill':
                break;
        }

        return $types;
    }

    /**
     * Add elements to a form
     *
     * @param FormValidator $form
     * @param int $itemId
     * @param array $exclude variables of extra field to exclude
     * @param bool $filter
     * @param bool $useTagAsSelect
     * @param array $showOnlyTheseFields
     * @param array $orderFields
     * @param bool $adminPermissions
     *
     * @return array|bool
     */
    public function addElements(
        $form,
        $itemId = 0,
        $exclude = [],
        $filter = false,
        $useTagAsSelect = false,
        $showOnlyTheseFields = [],
        $orderFields = [],
        $adminPermissions = false
    ) {
        if (empty($form)) {
            return false;
        }

        $itemId = (int) $itemId;
        $form->addHidden('item_id', $itemId);
        $extraData = false;
        if (!empty($itemId)) {
            $extraData = self::get_handler_extra_data($itemId);

            if ($form) {
                if (!empty($showOnlyTheseFields)) {
                    $setData = [];
                    foreach ($showOnlyTheseFields as $variable) {
                        $extraName = 'extra_'.$variable;
                        if (in_array($extraName, array_keys($extraData))) {
                            $setData[$extraName] = $extraData[$extraName];
                        }
                    }
                    $form->setDefaults($setData);
                } else {
                    $form->setDefaults($extraData);
                }
            }
        }

        $conditions = [];
        if ($filter) {
            $conditions = ['filter = ?' => 1];
        }

        $extraFields = $this->get_all($conditions, 'option_order');
        $extra = $this->set_extra_fields_in_form(
            $form,
            $extraData,
            $adminPermissions,
            $extraFields,
            $itemId,
            $exclude,
            $useTagAsSelect,
            $showOnlyTheseFields,
            $orderFields
        );

        return $extra;
    }

    /**
     * @param int $itemId (session_id, question_id, course id)
     *
     * @return array
     */
    public function get_handler_extra_data($itemId)
    {
        if (empty($itemId)) {
            return array();
        }

        $extra_data = array();
        $fields = self::get_all();
        $field_values = new ExtraFieldValue($this->type);

        if (!empty($fields) > 0) {
            foreach ($fields as $field) {
                $field_value = $field_values->get_values_by_handler_and_field_id(
                    $itemId,
                    $field['id']
                );

                if ($field['field_type'] == self::FIELD_TYPE_TAG) {
                    $tags = UserManager::get_user_tags_to_string($itemId, $field['id'], false);
                    $extra_data['extra_'.$field['variable']] = $tags;

                    continue;
                }

                if ($field_value) {
                    $field_value = $field_value['value'];
                    switch ($field['field_type']) {
                        case self::FIELD_TYPE_TAG:
                            $tags = UserManager::get_user_tags_to_string($itemId, $field['id'], false);

                            $extra_data['extra_'.$field['variable']] = $tags;
                            break;
                        case self::FIELD_TYPE_DOUBLE_SELECT:
                            $selected_options = explode(
                                '::',
                                $field_value
                            );
                            $firstOption = isset($selected_options[0]) ? $selected_options[0] : '';
                            $secondOption = isset($selected_options[1]) ? $selected_options[1] : '';
                            $extra_data['extra_'.$field['variable']]['extra_'.$field['variable']] = $firstOption;
                            $extra_data['extra_'.$field['variable']]['extra_'.$field['variable'].'_second'] = $secondOption;

                            break;
                        case self::FIELD_TYPE_SELECT_MULTIPLE:
                            $field_value = explode(';', $field_value);
                            $extra_data['extra_'.$field['variable']] = $field_value;
                            break;
                        case self::FIELD_TYPE_RADIO:
                            $extra_data['extra_'.$field['variable']]['extra_'.$field['variable']] = $field_value;
                            break;
                        default:
                            $extra_data['extra_'.$field['variable']] = $field_value;
                            break;
                    }
                } else {
                    // Set default values
                    if (isset($field['field_default_value']) && !empty($field['field_default_value'])) {
                        $extra_data['extra_'.$field['variable']] = $field['field_default_value'];
                    }
                }
            }
        }

        return $extra_data;
    }

    /**
     * @param string $field_type
     *
     * @return array
     */
    public function get_all_extra_field_by_type($field_type)
    {
        // all the information of the field
        $sql = "SELECT * FROM {$this->table}
                WHERE
                    field_type = '".Database::escape_string($field_type)."' AND
                    extra_field_type = $this->extraFieldType
                ";
        $result = Database::query($sql);

        $return = array();
        while ($row = Database::fetch_array($result)) {
            $return[] = $row['id'];
        }

        return $return;
    }

    /**
     * @return array
     */
    public function get_field_types()
    {
        return self::get_extra_fields_by_handler($this->type);
    }

    /**
     * @param int $id
     *
     * @return null
     */
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
     * France:Paris;Bretagne;Marseille;Lyon|Belgique:Bruxelles;Namur;Liège;Bruges|Peru:Lima;Piura;
     * into
     * array(
     *   'France' =>
     *      array('Paris', 'Bretagne', 'Marseille'),
     *   'Belgique' =>
     *      array('Namur', 'Liège')
     * ), etc
     * @param string $string
     *
     * @return array
     */
    public static function extra_field_double_select_convert_string_to_array($string)
    {
        $options = explode('|', $string);
        $options_parsed = array();
        $id = 0;

        if (!empty($options)) {
            foreach ($options as $sub_options) {
                $options = explode(':', $sub_options);
                $sub_sub_options = explode(';', $options[1]);
                $options_parsed[$id] = array(
                    'label' => $options[0],
                    'options' => $sub_sub_options,
                );
                $id++;
            }
        }

        return $options_parsed;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public static function extra_field_double_select_convert_array_to_ordered_array($options)
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
     * @param array $options the result of the get_field_options_by_field() array
     *
     * @return string
     */
    public static function extra_field_double_select_convert_array_to_string($options)
    {
        $string = null;
        $options_parsed = self::extra_field_double_select_convert_array_to_ordered_array($options);

        if (!empty($options_parsed)) {
            foreach ($options_parsed as $option) {
                foreach ($option as $key => $item) {
                    $string .= $item['display_text'];
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
     *
     * @return array
     */
    public function clean_parameters($params)
    {
        if (!isset($params['variable']) || empty($params['variable'])) {
            $params['variable'] = $params['display_text'];
        }

        $params['variable'] = trim(strtolower(str_replace(" ", "_", $params['variable'])));

        if (!isset($params['field_order'])) {
            $max_order = self::get_max_field_order();
            $params['field_order'] = $max_order;
        } else {
            $params['field_order'] = (int) $params['field_order'];
        }

        return $params;
    }

    /**
     * @param array $params
     * @param bool  $show_query
     *
     * @return bool
     */
    public function save($params, $show_query = false)
    {
        $fieldInfo = self::get_handler_field_info_by_field_variable($params['variable']);
        $params = self::clean_parameters($params);
        $params['extra_field_type'] = $this->extraFieldType;

        if ($fieldInfo) {
            return $fieldInfo['id'];
        } else {
            $id = parent::save($params, $show_query);
            if ($id) {
                $session_field_option = new ExtraFieldOption($this->type);
                $params['field_id'] = $id;
                $session_field_option->save($params);
            }

            return $id;
        }
    }

    /**
     * @param array $params
     *
     * @return bool|void
     */
    public function update($params)
    {
        $params = self::clean_parameters($params);
        if (isset($params['id'])) {
            $field_option = new ExtraFieldOption($this->type);
            $params['field_id'] = $params['id'];
            $field_option->save($params);
        }

        parent::update($params);
    }

    /**
     * @param $id
     *
     * @return bool|void
     */
    public function delete($id)
    {
        $em = Database::getManager();
        $items = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findBy(['field' => $id]);
        if ($items) {
            foreach ($items as $item) {
                $em->remove($item);
            }
            $em->flush();
        }
        $field_option = new ExtraFieldOption($this->type);
        $field_option->delete_all_options_by_field_id($id);

        $session_field_values = new ExtraFieldValue($this->type);
        $session_field_values->delete_all_values_by_field_id($id);

        parent::delete($id);
    }

    /**
     * Add an element that matches the given extra field to the given $form object
     * @param FormValidator $form
     * @param array $extraData
     * @param bool $adminPermissions
     * @param array $extra
     * @param int $itemId
     * @param array $exclude variables of extra field to exclude
     * @param bool $useTagAsSelect
     * @param array $showOnlyTheseFields
     * @param array $orderFields
     *
     * @return array If relevant, returns a one-element array with JS code to be added to the page HTML headers
     */
    public function set_extra_fields_in_form(
        $form,
        $extraData,
        $adminPermissions = false,
        $extra = array(),
        $itemId = null,
        $exclude = [],
        $useTagAsSelect = false,
        $showOnlyTheseFields = [],
        $orderFields = []
    ) {
        $type = $this->type;
        $jquery_ready_content = null;
        if (!empty($extra)) {
            $newOrder = [];
            if (!empty($orderFields)) {
                foreach ($orderFields as $order) {
                    foreach ($extra as $field_details) {
                        if ($order == $field_details['variable']) {
                            $newOrder[] = $field_details;
                        }
                    }
                }
                $extra = $newOrder;
            }

            foreach ($extra as $field_details) {
                if (!empty($showOnlyTheseFields)) {
                    if (!in_array($field_details['variable'], $showOnlyTheseFields)) {
                        continue;
                    }
                }

                // Getting default value id if is set
                $defaultValueId = null;
                if (isset($field_details['options']) && !empty($field_details['options'])) {
                    $valueToFind = null;
                    if (isset($field_details['field_default_value'])) {
                        $valueToFind = $field_details['field_default_value'];
                    }
                    // If a value is found we override the default value
                    if (isset($extraData['extra_'.$field_details['variable']])) {
                        $valueToFind = $extraData['extra_'.$field_details['variable']];
                    }

                    foreach ($field_details['options'] as $option) {
                        if ($option['option_value'] == $valueToFind) {
                            $defaultValueId = $option['id'];
                        }
                    }
                }

                if (!$adminPermissions) {
                    if ($field_details['visible_to_self'] == 0) {
                        continue;
                    }

                    if (in_array($field_details['variable'], $exclude)) {
                        continue;
                    }
                }

                $freezeElement = false;
                if (!$adminPermissions) {
                    $freezeElement = $field_details['visible_to_self'] == 0 || $field_details['changeable'] == 0;
                }

                switch ($field_details['field_type']) {
                    case self::FIELD_TYPE_TEXT:
                        $form->addElement(
                            'text',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            array(
                                'id' => 'extra_'.$field_details['variable']
                            )
                        );
                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_TEXTAREA:
                        $form->addHtmlEditor(
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            false,
                            false,
                            array(
                                'ToolbarSet' => 'Profile',
                                'Width' => '100%',
                                'Height' => '130',
                                'id' => 'extra_'.$field_details['variable']
                            )
                        );
                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_RADIO:
                        $group = array();
                        if (isset($field_details['options']) && !empty($field_details['options'])) {
                            foreach ($field_details['options'] as $option_details) {
                                $options[$option_details['option_value']] = $option_details['display_text'];
                                $group[] = $form->createElement(
                                    'radio',
                                    'extra_'.$field_details['variable'],
                                    $option_details['option_value'],
                                    $option_details['display_text'].'<br />',
                                    $option_details['option_value']
                                );
                            }
                        }
                        $form->addGroup(
                            $group,
                            'extra_'.$field_details['variable'],
                            $field_details['display_text']
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_CHECKBOX:
                        $group = array();
                        if (isset($field_details['options']) && !empty($field_details['options'])) {
                            foreach ($field_details['options'] as $option_details) {
                                $options[$option_details['option_value']] = $option_details['display_text'];
                                $group[] = $form->createElement(
                                    'checkbox',
                                    'extra_'.$field_details['variable'],
                                    $option_details['option_value'],
                                    $option_details['display_text'].'<br />',
                                    $option_details['option_value']
                                );
                            }
                        } else {
                            $fieldVariable = "extra_{$field_details['variable']}";
                            $checkboxAttributes = array();
                            if (is_array($extraData) && array_key_exists($fieldVariable, $extraData)) {
                                if (!empty($extraData[$fieldVariable])) {
                                    $checkboxAttributes['checked'] = 1;
                                }
                            }

                            // We assume that is a switch on/off with 1 and 0 as values
                            $group[] = $form->createElement(
                                'checkbox',
                                'extra_'.$field_details['variable'],
                                null,
                                //$field_details['display_text'].'<br />',
                                get_lang('Yes'),
                                $checkboxAttributes
                            );
                        }

                        $form->addGroup(
                            $group,
                            'extra_'.$field_details['variable'],
                            $field_details['display_text']
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_SELECT:
                        $get_lang_variables = false;
                        if (in_array(
                            $field_details['variable'],
                            array('mail_notify_message', 'mail_notify_invitation', 'mail_notify_group_message')
                        )
                        ) {
                            $get_lang_variables = true;
                        }

                        // Get extra field workflow
                        $userInfo = api_get_user_info();
                        $addOptions = array();
                        $optionsExists = false;
                        global $app;
                        // Check if exist $app['orm.em'] object
                        if (isset($app['orm.em']) && is_object($app['orm.em'])) {
                            $optionsExists = $app['orm.em']
                                ->getRepository('ChamiloLMS\Entity\ExtraFieldOptionRelFieldOption')
                                ->findOneBy(array('fieldId' => $field_details['id']));
                        }

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
                                    $options[$option_details['option_value']] = $option_details['display_text'];
                                } else {
                                    if ($optionsExists) {
                                        // Adding always the default value
                                        if ($option_details['id'] == $defaultValueId) {
                                            $options[$option_details['option_value']] = $option_details['display_text'];
                                        } else {
                                            if (isset($addOptions) && !empty($addOptions)) {
                                                // Parsing filters
                                                if (in_array($option_details['id'], $addOptions)) {
                                                    $options[$option_details['option_value']] = $option_details['display_text'];
                                                }
                                            }
                                        }
                                    } else {
                                        // Normal behaviour
                                        $options[$option_details['option_value']] = $option_details['display_text'];
                                    }
                                }
                            }

                            if (isset($optionList[$defaultValueId])) {
                                if (isset($optionList[$defaultValueId]['option_value']) &&
                                    $optionList[$defaultValueId]['option_value'] == 'aprobada'
                                ) {
                                    // @todo function don't exists api_is_question_manager
                                    /*if (api_is_question_manager() == false) {
                                        $form->freeze();
                                    }*/
                                }
                            }

                            // Setting priority message
                            if (isset($optionList[$defaultValueId]) &&
                                isset($optionList[$defaultValueId]['priority'])
                            ) {

                                if (!empty($optionList[$defaultValueId]['priority'])) {
                                    $priorityId = $optionList[$defaultValueId]['priority'];
                                    $option = new ExtraFieldOption($this->type);
                                    $messageType = $option->getPriorityMessageType($priorityId);
                                    $form->addElement(
                                        'label',
                                        null,
                                        Display::return_message(
                                            $optionList[$defaultValueId]['priority_message'],
                                            $messageType
                                        )
                                    );
                                }
                            }
                        }

                        // chzn-select doesn't work for sessions??
                        $form->addElement(
                            'select',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            $options,
                            array('id' => 'extra_'.$field_details['variable'])
                        );

                        /* Enable this when field_loggeable is introduced as a table field (2.0)
                        if ($optionsExists && $field_details['field_loggeable'] && !empty($defaultValueId)) {

                            $form->addElement(
                                'textarea',
                                'extra_' . $field_details['variable'] . '_comment',
                                $field_details['display_text'] . ' ' . get_lang('Comment')
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
                                    $html = '<b>' . get_lang('LatestChanges') . '</b><br /><br />';

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
                        */

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_SELECT_MULTIPLE:
                        $options = array();
                        foreach ($field_details['options'] as $option_id => $option_details) {
                            $options[$option_details['option_value']] = $option_details['display_text'];
                        }
                        $form->addElement(
                            'select',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            $options,
                            array('multiple' => 'multiple', 'id' => 'extra_'.$field_details['variable'])
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_DATE:
                        $form->addDatePicker('extra_'.$field_details['variable'], $field_details['display_text']);
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_DATETIME:
                        $form->addDateTimePicker(
                            'extra_'.$field_details['variable'],
                            $field_details['display_text']
                        );

                        $defaults['extra_'.$field_details['variable']] = api_get_local_time();
                        if (!isset($form->_defaultValues['extra_'.$field_details['variable']])) {
                            $form->setDefaults($defaults);
                        }
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_DOUBLE_SELECT:
                        $first_select_id = 'first_extra_'.$field_details['variable'];
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
                                        $("#second_extra_'.$field_details['variable'].'").empty();
                                        $.each(data, function(index, value) {
                                            $("#second_extra_'.$field_details['variable'].'").append($("<option/>", {
                                                value: index,
                                                text: value
                                            }));
                                        });
                                        $("#second_extra_'.$field_details['variable'].'").selectpicker("refresh");
                                    },
                                });
                            } else {
                                $("#second_extra_'.$field_details['variable'].'").empty();
                            }
                        });';

                        $first_id = null;
                        if (!empty($extraData)) {
                            if (isset($extraData['extra_'.$field_details['variable']])) {
                                $first_id = $extraData['extra_'.$field_details['variable']]['extra_'.$field_details['variable']];
                            }
                        }

                        $options = self::extra_field_double_select_convert_array_to_ordered_array(
                            $field_details['options']
                        );
                        $values = array('' => get_lang('Select'));

                        $second_values = array();
                        if (!empty($options)) {
                            foreach ($options as $option) {
                                foreach ($option as $sub_option) {
                                    if ($sub_option['option_value'] == '0') {
                                        $values[$sub_option['id']] = $sub_option['display_text'];
                                    } else {
                                        if ($first_id === $sub_option['option_value']) {
                                            $second_values[$sub_option['id']] = $sub_option['display_text'];
                                        }
                                    }
                                }
                            }
                        }
                        $group = array();
                        $group[] = $form->createElement(
                            'select',
                            'extra_'.$field_details['variable'],
                            null,
                            $values,
                            array('id' => $first_select_id)
                        );
                        $group[] = $form->createElement(
                            'select',
                            'extra_'.$field_details['variable'].'_second',
                            null,
                            $second_values,
                            array('id' => 'second_extra_'.$field_details['variable'])
                        );
                        $form->addGroup(
                            $group,
                            'extra_'.$field_details['variable'],
                            $field_details['display_text']
                        );

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_DIVIDER:
                        $form->addHtml('
                            <div class="form-group ">
                                <div class="col-sm-12">
                                    <div class="panel-separator">
                                       <h4 id="' . $field_details['variable'].'" class="form-separator">'.$field_details['display_text'].'</h4>
                                    </div>
                                </div>
                            </div>    
                        ');
                        break;
                    case self::FIELD_TYPE_TAG:
                        $variable = $field_details['variable'];
                        $field_id = $field_details['id'];

                        $tagsSelect = $form->addSelect(
                            "extra_{$field_details['variable']}",
                            $field_details['display_text'],
                            [],
                            ['style' => 'width: 100%;']
                        );

                        if ($useTagAsSelect == false) {
                            $tagsSelect->setAttribute('class', null);
                        }

                        $tagsSelect->setAttribute('id', "extra_{$field_details['variable']}");
                        $tagsSelect->setMultiple(true);

                        $selectedOptions = [];

                        if ($this->type === 'user') {
                            // The magic should be here
                            $user_tags = UserManager::get_user_tags($itemId, $field_details['id']);

                            if (is_array($user_tags) && count($user_tags) > 0) {
                                foreach ($user_tags as $tag) {
                                    $tagsSelect->addOption(
                                        $tag['tag'],
                                        $tag['tag']
                                    );

                                    $selectedOptions[] = $tag['tag'];
                                }
                            }
                            $url = api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php';
                        } else {
                            $em = Database::getManager();

                            $fieldTags = $em
                                ->getRepository('ChamiloCoreBundle:ExtraFieldRelTag')
                                ->findBy([
                                    'fieldId' => $field_id,
                                    'itemId' => $itemId
                                ]);
                            /** @var \Chamilo\CoreBundle\Entity\ExtraFieldRelTag $fieldTag */
                            foreach ($fieldTags as $fieldTag) {
                                /** @var \Chamilo\CoreBundle\Entity\Tag $tag */
                                $tag = $em->find('ChamiloCoreBundle:Tag', $fieldTag->getTagId());

                                if (empty($tag)) {
                                    continue;
                                }
                                $tagsSelect->addOption(
                                    $tag->getTag(),
                                    $tag->getTag()
                                );
                                $selectedOptions[] = $tag->getTag();
                            }

                            if ($useTagAsSelect) {
                                $fieldTags = $em
                                    ->getRepository('ChamiloCoreBundle:ExtraFieldRelTag')
                                    ->findBy([
                                        'fieldId' => $field_id
                                    ]);
                                $tagsAdded = [];
                                foreach ($fieldTags as $fieldTag) {
                                    $tag = $em->find('ChamiloCoreBundle:Tag', $fieldTag->getTagId());

                                    if (empty($tag)) {
                                        continue;
                                    }

                                    $tagText = $tag->getTag();

                                    if (in_array($tagText, $tagsAdded)) {
                                        continue;
                                    }

                                    $tagsSelect->addOption(
                                        $tag->getTag(),
                                        $tag->getTag(),
                                        []
                                    );

                                    $tagsAdded[] = $tagText;
                                }

                            }

                            $url = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php';
                        }

                        $form->setDefaults([
                            'extra_'.$field_details['variable'] => $selectedOptions
                        ]);

                        if ($useTagAsSelect == false) {
                            $jquery_ready_content .= "
                                $('#extra_$variable').select2({
                                    ajax: {
                                        url: '$url?a=search_tags&field_id=$field_id&type={$this->type}',
                                        processResults: function (data) {
                                            return {
                                                results: data.items
                                            }
                                        }
                                    },
                                    cache: false,
                                    tags: true,
                                    tokenSeparators: [','],
                                    placeholder: '".get_lang('StartToType')."'
                                });
                            ";
                        }
                        break;
                    case self::FIELD_TYPE_TIMEZONE:
                        $form->addElement(
                            'select',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            api_get_timezones(),
                            ''
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_SOCIAL_PROFILE:
                        // get the social network's favicon
                        $extra_data_variable = isset($extraData['extra_'.$field_details['variable']]) ? $extraData['extra_'.$field_details['variable']] : null;
                        $field_default_value = isset($field_details['field_default_value']) ? $field_details['field_default_value'] : null;
                        $icon_path = UserManager::get_favicon_from_url(
                            $extra_data_variable,
                            $field_default_value
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
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            array(
                                'size'  => 60,
                                'style' => 'background-image: url(\''.$icon_path.'\'); background-repeat: no-repeat; background-position: 0.4em '.$top.'em; padding-left: '.$leftpad.'em; '
                            )
                        );
                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_MOBILE_PHONE_NUMBER:
                        $form->addElement(
                            'text',
                            'extra_'.$field_details[1],
                            $field_details[3]." (".get_lang('CountryDialCode').")",
                            array('size' => 40, 'placeholder' => '(xx)xxxxxxxxx')
                        );
                        $form->applyFilter('extra_'.$field_details[1], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details[1], 'trim');
                        $form->applyFilter('extra_'.$field_details[1], 'mobile_phone_number_filter');
                        $form->addRule(
                            'extra_'.$field_details[1],
                            get_lang('MobilePhoneNumberWrong'),
                            'mobile_phone_number'
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_INTEGER:
                        $form->addElement(
                            'number',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            array('class' => 'span1', 'step' => 1)
                        );

                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');
                        $form->applyFilter('extra_'.$field_details['variable'], 'intval');

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_FILE_IMAGE:
                        $fieldVariable = "extra_{$field_details['variable']}";
                        $fieldTexts = [
                            $field_details['display_text']
                        ];

                        if (is_array($extraData) && array_key_exists($fieldVariable, $extraData)) {
                            if (file_exists(api_get_path(SYS_UPLOAD_PATH).$extraData[$fieldVariable])) {
                                $fieldTexts[] = Display::img(
                                    api_get_path(WEB_UPLOAD_PATH).$extraData[$fieldVariable],
                                    $field_details['display_text'],
                                    ['width' => '300']
                                );
                            }
                        }

                        if ($fieldTexts[0] === 'Image') {
                            $fieldTexts[0] = get_lang($fieldTexts[0]);
                        }

                        $form->addFile(
                            $fieldVariable,
                            $fieldTexts,
                            ['accept' => 'image/*', 'id' => 'extra_image', 'crop_image' => 'true']
                        );

                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');

                        $allowed_picture_types = ['jpg', 'jpeg', 'png', 'gif'];
                        $form->addRule(
                            'extra_'.$field_details['variable'],
                            get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')',
                            'filetype',
                            $allowed_picture_types
                        );

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_FLOAT:
                        $form->addElement(
                            'number',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            array('class' => 'span1', 'step' => '0.01')
                        );

                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');
                        $form->applyFilter('extra_'.$field_details['variable'], 'floatval');

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_FILE:
                        $fieldVariable = "extra_{$field_details['variable']}";
                        $fieldTexts = array(
                            $field_details['display_text']
                        );

                        if (is_array($extraData) &&
                            array_key_exists($fieldVariable, $extraData)
                        ) {
                            if (file_exists(api_get_path(SYS_UPLOAD_PATH).$extraData[$fieldVariable])) {
                                $fieldTexts[] = Display::url(
                                    api_get_path(WEB_UPLOAD_PATH).$extraData[$fieldVariable],
                                    api_get_path(WEB_UPLOAD_PATH).$extraData[$fieldVariable],
                                    array(
                                        'title' => $field_details['display_text'],
                                        'target' => '_blank'
                                    )
                                );
                            }
                        }

                        $form->addElement(
                            'file',
                            $fieldVariable,
                            $fieldTexts,
                            array()
                        );

                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_VIDEO_URL:
                        $form->addUrl(
                            "extra_{$field_details['variable']}",
                            $field_details['display_text'],
                            false,
                            ['placeholder' => 'https://']
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_LETTERS_ONLY:
                        $form->addTextLettersOnly(
                            "extra_{$field_details['variable']}",
                            $field_details['display_text']
                        );
                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_ALPHANUMERIC:
                        $form->addTextAlphanumeric(
                            "extra_{$field_details['variable']}",
                            $field_details['display_text']
                        );
                        $form->applyFilter(
                            'extra_'.$field_details['variable'],
                            'stripslashes'
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_LETTERS_SPACE:
                        $form->addTextLettersAndSpaces(
                            "extra_{$field_details['variable']}",
                            $field_details['display_text']
                        );
                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_ALPHANUMERIC_SPACE:
                        $form->addTextAlphanumericAndSpaces(
                            "extra_{$field_details['variable']}",
                            $field_details['display_text']
                        );
                        $form->applyFilter(
                            'extra_'.$field_details['variable'],
                            'stripslashes'
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_GEOLOCALIZATION:
                        $dataValue = isset($extraData['extra_'.$field_details['variable']])
                            ? $extraData['extra_'.$field_details['variable']]
                            : '';
                        $form->addElement(
                            'text',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            ['id' => 'extra_'.$field_details['variable']]
                        );
                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }

                        $form->addHtml(
                            '<script>
                                $(document).ready(function() {                                    
                                    if (typeof google === "object") {                                        
                                        var address = "' . $dataValue.'";
                                        initializeGeo'.$field_details['variable'].'(address, false);
    
                                        $("#geolocalization_extra_'.$field_details['variable'].'").on("click", function() {
                                            var address = $("#extra_'.$field_details['variable'].'").val();
                                            initializeGeo'.$field_details['variable'].'(address, false);
                                            return false;
                                        });
    
                                        $("#myLocation_extra_'.$field_details['variable'].'").on("click", function() {
                                            myLocation'.$field_details['variable'].'();
                                            return false;
                                        });
    
                                        $("#extra_'.$field_details['variable'].'").keypress(function (event) {
                                            if (event.which == 13) {
                                                $("#geolocalization_extra_'.$field_details['variable'].'").click();
                                                return false;
                                            }
                                        });
                                        
                                    } else {
                                        $("#map_extra_'.$field_details['variable'].'").html("<div class=\"alert alert-info\">'.get_lang('YouNeedToActivateTheGoogleMapsPluginInAdminPlatformToSeeTheMap').'</div>");
                                    }                                    
                                });

                                function myLocation'.$field_details['variable'].'() {
                                    if (navigator.geolocation) {
                                        var geoPosition = function(position) {
                                            var lat = position.coords.latitude;
                                            var lng = position.coords.longitude;
                                            var latLng = new google.maps.LatLng(lat, lng);
                                            initializeGeo'.$field_details['variable'].'(false, latLng)
                                        };

                                        var geoError = function(error) {
                                            alert("Geocode ' . get_lang('Error').': " + error);
                                        };

                                        var geoOptions = {
                                            enableHighAccuracy: true
                                        };

                                        navigator.geolocation.getCurrentPosition(geoPosition, geoError, geoOptions);
                                    }
                                }

                                function initializeGeo'.$field_details['variable'].'(address, latLng) {
                                    var geocoder = new google.maps.Geocoder();
                                    var latlng = new google.maps.LatLng(-34.397, 150.644);
                                    var myOptions = {
                                        zoom: 15,
                                        center: latlng,
                                        mapTypeControl: true,
                                        mapTypeControlOptions: {
                                            style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
                                        },
                                        navigationControl: true,
                                        mapTypeId: google.maps.MapTypeId.ROADMAP
                                    };

                                    map_'.$field_details['variable'].' = new google.maps.Map(document.getElementById("map_extra_'.$field_details['variable'].'"), myOptions);

                                    var parameter = address ? { "address": address } : latLng ? { "latLng": latLng } : false;

                                    if (geocoder && parameter) {
                                        geocoder.geocode(parameter, function(results, status) {
                                            if (status == google.maps.GeocoderStatus.OK) {
                                                if (status != google.maps.GeocoderStatus.ZERO_RESULTS) {
                                                    map_'.$field_details['variable'].'.setCenter(results[0].geometry.location);
                                                    if (!address) {
                                                        $("#extra_'.$field_details['variable'].'").val(results[0].formatted_address);
                                                    }
                                                    var infowindow = new google.maps.InfoWindow({
                                                        content: "<b>" + $("#extra_'.$field_details['variable'].'").val() + "</b>",
                                                        size: new google.maps.Size(150, 50)
                                                    });

                                                    var marker = new google.maps.Marker({
                                                        position: results[0].geometry.location,
                                                        map: map_'.$field_details['variable'].',
                                                        title: $("#extra_'.$field_details['variable'].'").val()
                                                    });
                                                    google.maps.event.addListener(marker, "click", function() {
                                                        infowindow.open(map_'.$field_details['variable'].', marker);
                                                    });
                                                } else {
                                                    alert("' . get_lang("NotFound").'");
                                                }

                                            } else {
                                                alert("Geocode ' . get_lang('Error').': '.get_lang("AddressField").' '.get_lang("NotFound").'");
                                            }
                                        });
                                    }
                                }
                            </script>
                        ');
                        $form->addHtml('
                            <div class="form-group">
                                <label for="geolocalization_extra_'.$field_details['variable'].'" class="col-sm-2 control-label"></label>
                                <div class="col-sm-8">
                                    <button class="null btn btn-default " id="geolocalization_extra_'.$field_details['variable'].'" name="geolocalization_extra_'.$field_details['variable'].'" type="submit"><em class="fa fa-map-marker"></em> '.get_lang('Geolocalization').'</button>
                                    <button class="null btn btn-default " id="myLocation_extra_'.$field_details['variable'].'" name="myLocation_extra_'.$field_details['variable'].'" type="submit"><em class="fa fa-crosshairs"></em> '.get_lang('MyLocation').'</button>
                                </div>
                            </div>
                        ');

                        $form->addHtml('
                            <div class="form-group">
                                <label for="map_extra_'.$field_details['variable'].'" class="col-sm-2 control-label">
                                    '.$field_details['display_text'].' - '.get_lang('Map').'
                                </label>
                                <div class="col-sm-8">
                                    <div name="map_extra_'.$field_details['variable'].'" id="map_extra_'.$field_details['variable'].'" style="width:100%; height:300px;">
                                    </div>
                                </div>
                            </div>
                        ');
                        break;
                    case self::FIELD_TYPE_GEOLOCALIZATION_COORDINATES:
                        $dataValue = isset($extraData['extra_'.$field_details['variable']])
                            ? $extraData['extra_'.$field_details['variable']]
                            : '';
                        $form->addElement(
                            'text',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            ['id' => 'extra_'.$field_details['variable']]
                        );
                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        $latLag = explode(",", $dataValue);

                        // if no value, set default coordinates value
                        if (empty($dataValue)) {
                            $lat = '-34.397';
                            $lng = '150.644';
                        } else {
                            $lat = $latLag[0];
                            $lng = $latLag[1];
                        }

                        $form->addHtml(
                            '<script>
                                $(document).ready(function() {
                                    if (typeof google === "object") {
                                        
                                        var lat = "' . $lat.'";
                                        var lng = "' . $lng.'";
                                        var latLng = new google.maps.LatLng(lat, lng);
                                        initializeGeo'.$field_details['variable'].'(false, latLng);
    
                                        $("#geolocalization_extra_'.$field_details['variable'].'").on("click", function() {
                                            var latLng = $("#extra_'.$field_details['variable'].'").val().split(",");
                                            var lat = latLng[0];
                                            var lng = latLng[1];
                                            var latLng = new google.maps.LatLng(lat, lng);
                                            initializeGeo'.$field_details['variable'].'(false, latLng);
                                            return false;
                                        });
    
                                        $("#myLocation_extra_'.$field_details['variable'].'").on("click", function() {
                                            myLocation'.$field_details['variable'].'();
                                            return false;
                                        });
    
                                        $("#extra_'.$field_details['variable'].'").keypress(function (event) {
                                            if (event.which == 13) {
                                                $("#geolocalization_extra_'.$field_details['variable'].'").click();
                                                return false;
                                            }
                                        });
                                    } else {
                                        $("#map_extra_'.$field_details['variable'].'").html("<div class=\"alert alert-info\">'.get_lang('YouNeedToActivateTheGoogleMapsPluginInAdminPlatformToSeeTheMap').'</div>");
                                    }
                                    
                                });

                                function myLocation'.$field_details['variable'].'() {
                                    if (navigator.geolocation) {
                                        var geoPosition = function(position) {
                                            var lat = position.coords.latitude;
                                            var lng = position.coords.longitude;
                                            var latLng = new google.maps.LatLng(lat, lng);
                                            initializeGeo'.$field_details['variable'].'(false, latLng)
                                        };

                                        var geoError = function(error) {
                                            alert("Geocode ' . get_lang('Error').': " + error);
                                        };

                                        var geoOptions = {
                                            enableHighAccuracy: true
                                        };

                                        navigator.geolocation.getCurrentPosition(geoPosition, geoError, geoOptions);
                                    }
                                }

                                function initializeGeo'.$field_details['variable'].'(address, latLng) {
                                    var geocoder = new google.maps.Geocoder();
                                    var latlng = new google.maps.LatLng(-34.397, 150.644);
                                    var myOptions = {
                                        zoom: 15,
                                        center: latlng,
                                        mapTypeControl: true,
                                        mapTypeControlOptions: {
                                            style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
                                        },
                                        navigationControl: true,
                                        mapTypeId: google.maps.MapTypeId.ROADMAP
                                    };

                                    map_'.$field_details['variable'].' = new google.maps.Map(document.getElementById("map_extra_'.$field_details['variable'].'"), myOptions);

                                    var parameter = address ? { "address": address } : latLng ? { "latLng": latLng } : false;

                                    if (geocoder && parameter) {
                                        geocoder.geocode(parameter, function(results, status) {
                                            if (status == google.maps.GeocoderStatus.OK) {
                                                if (status != google.maps.GeocoderStatus.ZERO_RESULTS) {
                                                    map_'.$field_details['variable'].'.setCenter(results[0].geometry.location);

                                                    $("#extra_'.$field_details['variable'].'").val(results[0].geometry.location.lat() + "," + results[0].geometry.location.lng());

                                                    var infowindow = new google.maps.InfoWindow({
                                                        content: "<b>" + $("#extra_'.$field_details['variable'].'").val() + "</b>",
                                                        size: new google.maps.Size(150, 50)
                                                    });

                                                    var marker = new google.maps.Marker({
                                                        position: results[0].geometry.location,
                                                        map: map_'.$field_details['variable'].',
                                                        title: $("#extra_'.$field_details['variable'].'").val()
                                                    });
                                                    google.maps.event.addListener(marker, "click", function() {
                                                        infowindow.open(map_'.$field_details['variable'].', marker);
                                                    });
                                                } else {
                                                    alert("' . get_lang("NotFound").'");
                                                }

                                            } else {
                                                alert("Geocode ' . get_lang('Error').': " + status);
                                            }
                                        });
                                    }
                                }
                            </script>
                        ');
                        $form->addHtml('
                            <div class="form-group">
                                <label for="geolocalization_extra_'.$field_details['variable'].'" class="col-sm-2 control-label"></label>
                                <div class="col-sm-8">
                                    <button class="null btn btn-default " id="geolocalization_extra_'.$field_details['variable'].'" name="geolocalization_extra_'.$field_details['variable'].'" type="submit"><em class="fa fa-map-marker"></em> '.get_lang('Geolocalization').'</button>
                                    <button class="null btn btn-default " id="myLocation_extra_'.$field_details['variable'].'" name="myLocation_extra_'.$field_details['variable'].'" type="submit"><em class="fa fa-crosshairs"></em> '.get_lang('MyLocation').'</button>
                                </div>
                            </div>
                        ');

                        $form->addHtml('
                            <div class="form-group">
                                <label for="map_extra_'.$field_details['variable'].'" class="col-sm-2 control-label">
                                    '.$field_details['display_text'].' - '.get_lang('Map').'
                                </label>
                                <div class="col-sm-8">
                                    <div name="map_extra_'.$field_details['variable'].'" id="map_extra_'.$field_details['variable'].'" style="width:100%; height:300px;">
                                    </div>
                                </div>
                            </div>
                        ');
                        break;
                }
            }
        }

        $return = array();
        $return['jquery_ready_content'] = $jquery_ready_content;

        return $return;
    }

    /**
     * @param $breadcrumb
     * @param $action
     */
    public function setupBreadcrumb(&$breadcrumb, $action)
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

    /**
     * @return array
     */
    public function getJqgridColumnNames()
    {
        return array(
            get_lang('Name'),
            get_lang('FieldLabel'),
            get_lang('Type'),
            get_lang('FieldChangeability'),
            get_lang('VisibleToSelf'),
            get_lang('VisibleToOthers'),
            get_lang('Filter'),
            get_lang('FieldOrder'),
            get_lang('Actions')
        );
    }

    /**
     * @return array
     */
    public function getJqgridColumnModel()
    {
        return array(
            array(
                'name' => 'display_text',
                'index' => 'display_text',
                'width' => '140',
                'align' => 'left',
            ),
            array(
                'name' => 'variable',
                'index' => 'variable',
                'width' => '90',
                'align' => 'left',
                'sortable' => 'true',
            ),
            array(
                'name' => 'field_type',
                'index' => 'field_type',
                'width' => '70',
                'align' => 'left',
                'sortable' => 'true',
            ),
            array(
                'name' => 'changeable',
                'index' => 'changeable',
                'width' => '35',
                'align' => 'left',
                'sortable' => 'true',
            ),
            array(
                'name' => 'visible_to_self',
                'index' => 'visible_to_self',
                'width' => '45',
                'align' => 'left',
                'sortable' => 'true',
            ),
             array(
                'name' => 'visible_to_others',
                'index' => 'visible_to_others',
                'width' => '35',
                'align' => 'left',
                'sortable' => 'true',
            ),
            array(
                'name' => 'filter',
                'index' => 'filter',
                'width' => '30',
                'align' => 'left',
                'sortable' => 'true',
            ),
            array(
                'name' => 'field_order',
                'index' => 'field_order',
                'width' => '25',
                'align' => 'left',
                'sortable' => 'true',
            ),
            array(
                'name' => 'actions',
                'index' => 'actions',
                'width' => '40',
                'align' => 'left',
                'formatter' => 'action_formatter',
                'sortable' => 'false',
            ),
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
            $defaults = $this->get($id, false);
        }

        $form->addElement('header', $header);

        if ($action == 'edit') {
            $translateUrl = api_get_path(WEB_CODE_PATH).'extrafield/translate.php?'.http_build_query([
                'extra_field' => $id
            ]);
            $translateButton = Display::toolbarButton(get_lang('TranslateThisTerm'), $translateUrl, 'language', 'link');

            $form->addText(
                'display_text',
                [get_lang('Name'), $translateButton]
            );
        } else {
            $form->addElement('text', 'display_text', get_lang('Name'));
        }

        // Field type
        $types = self::get_field_types();

        $form->addElement(
            'select',
            'field_type',
            get_lang('FieldType'),
            $types,
            array('id' => 'field_type')
        );
        $form->addElement('label', get_lang('Example'), '<div id="example">-</div>');
        $form->addElement('text', 'variable', get_lang('FieldLabel'), array('class' => 'span5'));
        $form->addElement(
            'text',
            'field_options',
            get_lang('FieldPossibleValues'),
            array('id' => 'field_options', 'class' => 'span6')
        );

        $fieldWithOptions = array(
            self::FIELD_TYPE_RADIO,
            self::FIELD_TYPE_SELECT_MULTIPLE,
            self::FIELD_TYPE_SELECT,
            self::FIELD_TYPE_TAG,
            self::FIELD_TYPE_DOUBLE_SELECT,
        );

        if ($action == 'edit') {
            if (in_array($defaults['field_type'], $fieldWithOptions)) {
                $url = Display::url(
                    get_lang('EditExtraFieldOptions'),
                    'extra_field_options.php?type='.$this->type.'&field_id='.$id
                );
                $form->addElement('label', null, $url);

                if ($defaults['field_type'] == self::FIELD_TYPE_SELECT) {
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
            'default_value',
            get_lang('FieldDefaultValue'),
            array('id' => 'default_value')
        );

        $group = array();
        $group[] = $form->createElement('radio', 'visible_to_self', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'visible_to_self', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('VisibleToSelf'), null, false);

        $group = array();
        $group[] = $form->createElement('radio', 'visible_to_others', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'visible_to_others', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('VisibleToOthers'), null, false);

        $group   = array();
        $group[] = $form->createElement('radio', 'changeable', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'changeable', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('FieldChangeability'), null, false);

        $group   = array();
        $group[] = $form->createElement('radio', 'filter', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'filter', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('FieldFilter'), null, false);

        /* Enable this when field_loggeable is introduced as a table field (2.0)
        $group   = array();
        $group[] = $form->createElement('radio', 'field_loggeable', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'field_loggeable', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('FieldLoggeable'), '', false);
        */

        $form->addElement('text', 'field_order', get_lang('FieldOrder'));

        if ($action == 'edit') {
            $option = new ExtraFieldOption($this->type);
            if ($defaults['field_type'] == self::FIELD_TYPE_DOUBLE_SELECT) {
                $form->freeze('field_options');
            }
            $defaults['field_options'] = $option->get_field_options_by_field_to_string($id);
            $form->addButtonUpdate(get_lang('Modify'));
        } else {
            $defaults['visible_to_self'] = 0;
            $defaults['visible_to_others'] = 0;
            $defaults['changeable'] = 0;
            $defaults['filter'] = 0;
            $form->addButtonCreate(get_lang('Add'));
        }

        /*if (!empty($defaults['created_at'])) {
            $defaults['created_at'] = api_convert_and_format_date($defaults['created_at']);
        }
        if (!empty($defaults['updated_at'])) {
            $defaults['updated_at'] = api_convert_and_format_date($defaults['updated_at']);
        }*/
        $form->setDefaults($defaults);

        // Setting the rules
        $form->addRule('display_text', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('field_type', get_lang('ThisFieldIsRequired'), 'required');

        return $form;
    }

    /**
     * @param $token
     * @return string
     */
    public function getJqgridActionLinks($token)
    {
        //With this function we can add actions to the jgrid (edit, delete, etc)
        $editIcon = Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL);
        $deleteIcon = Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL);
        $confirmMessage = addslashes(
            api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES)
        );

        $editButton = <<<JAVASCRIPT
            <a href="?action=edit&type={$this->type}&id=' + options.rowId + '" class="btn btn-link btn-xs">\
                $editIcon\
            </a>
JAVASCRIPT;
        $deleteButton = <<<JAVASCRIPT
            <a \
                onclick="if (!confirm(\'$confirmMessage\')) {return false;}" \
                href="?sec_token=$token&type={$this->type}&id=' + options.rowId + '&action=delete" \
                class="btn btn-link btn-xs">\
                $deleteIcon\
            </a>
JAVASCRIPT;

        return "function action_formatter(cellvalue, options, rowObject) {        
            return '$editButton $deleteButton';
        }";
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
            array(
                'visible_to_self = ? AND filter = ?' => array(1, 1)
            ),
            'display_text'
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
                    $options = $extraFieldOption->get_field_options_by_field($field['id']);
                    $options = self::extra_field_double_select_convert_array_to_ordered_array($options);
                    $first_options = array();

                    if (!empty($options)) {
                        foreach ($options as $option) {
                            foreach ($option as $sub_option) {
                                if ($sub_option['option_value'] == 0) {
                                    $first_options[] = $sub_option['field_id'].'#'.$sub_option['id'].':'.$sub_option['display_text'];
                                }
                            }
                        }
                    }

                    $search_options['value'] = implode(';', $first_options);
                    $search_options['dataInit'] = 'fill_second_select';

                    // First
                    $column_model[] = array(
                        'name' => 'extra_'.$field['variable'],
                        'index' => 'extra_'.$field['variable'],
                        'width' => '100',
                        'hidden' => 'true',
                        'search' => 'true',
                        'stype' => 'select',
                        'searchoptions' => $search_options,
                    );
                    $columns[] = $field['display_text'].' (1)';
                    $rules[] = array(
                        'field' => 'extra_'.$field['variable'],
                        'op' => 'cn',
                    );

                    //Second
                    $search_options['value']    = $field['id'].':';
                    $search_options['dataInit'] = 'register_second_select';

                    $column_model[] = array(
                        'name' => 'extra_'.$field['variable'].'_second',
                        'index' => 'extra_'.$field['variable'].'_second',
                        'width' => '100',
                        'hidden' => 'true',
                        'search' => 'true',
                        'stype' => 'select',
                        'searchoptions' => $search_options,
                    );
                    $columns[] = $field['display_text'].' (2)';
                    $rules[] = array('field' => 'extra_'.$field['variable'].'_second', 'op' => 'cn');
                    continue;
                } else {
                    $search_options['value'] = $extraFieldOption->getFieldOptionsToString(
                        $field['id'],
                        false,
                        'display_text'
                    );
                }
                $column_model[] = array(
                    'name' => 'extra_'.$field['variable'],
                    'index' => 'extra_'.$field['variable'],
                    'width' => '100',
                    'hidden' => 'true',
                    'search' => 'true',
                    'stype' => $type,
                    'searchoptions' => $search_options,
                );
                $columns[] = $field['display_text'];
                $rules[] = array(
                    'field' => 'extra_'.$field['variable'],
                    'op' => 'cn'
                );
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
        $extraFieldOption = new ExtraFieldOption($this->type);
        $double_fields = array();

        if (isset($options['extra'])) {
            $extra_fields = $options['extra'];
            if (!empty($extra_fields)) {
                $counter = 1;
                foreach ($extra_fields as &$extra) {
                    $extra_field_obj = new ExtraField($this->type);
                    $extra_field_info = $extra_field_obj->get($extra['id']);
                    $extra['extra_field_info'] = $extra_field_info;

                    if (isset($extra_field_info['field_type']) && in_array(
                            $extra_field_info['field_type'],
                            array(
                                self::FIELD_TYPE_SELECT,
                                self::FIELD_TYPE_SELECT,
                                self::FIELD_TYPE_DOUBLE_SELECT
                            )
                        )
                    ) {
                        $inject_extra_fields .= " fvo$counter.display_text as {$extra['field']}, ";
                    } else {
                        $inject_extra_fields .= " fv$counter.value as {$extra['field']}, ";
                    }

                    if (isset($extra_fields_info[$extra['id']])) {
                        $info = $extra_fields_info[$extra['id']];
                    } else {
                        $info = $this->get($extra['id']);
                        $extra_fields_info[$extra['id']] = $info;
                    }
                    if (isset($info['field_type']) && $info['field_type'] == self::FIELD_TYPE_DOUBLE_SELECT) {
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
            $options_by_double['extra_'.$double['variable']] = $my_options;
        }

        $field_value_to_join = array();

        //filter can be all/any = and/or
        $inject_joins = null;
        $inject_where = null;
        $where = null;

        if (!empty($options['where'])) {
            if (!empty($options['extra'])) {
                // Removing double 1=1
                $options['where'] = str_replace(' 1 = 1  AND', '', $options['where']);
                // Always OR
                $counter = 1;
                foreach ($extra_fields as $extra_info) {
                    $extra_field_info = $extra_info['extra_field_info'];
                    $inject_joins .= " INNER JOIN $this->table_field_values fv$counter
                                       ON (s.".$this->primaryKey." = fv$counter.".$this->handler_id.") ";
                    // Add options
                    if (isset($extra_field_info['field_type']) && in_array(
                            $extra_field_info['field_type'],
                            array(
                                self::FIELD_TYPE_SELECT,
                                self::FIELD_TYPE_SELECT,
                                self::FIELD_TYPE_DOUBLE_SELECT
                            )
                        )
                    ) {
                        $options['where'] = str_replace(
                            $extra_info['field'],
                            'fv'.$counter.'.field_id = '.$extra_info['id'].' AND fvo'.$counter.'.option_value',
                            $options['where']
                        );
                        $inject_joins .= "
                             INNER JOIN $this->table_field_options fvo$counter
                             ON (
                                fv$counter.field_id = fvo$counter.field_id AND
                                fv$counter.value = fvo$counter.option_value
                             )
                            ";
                    } else if (isset($extra_field_info['field_type']) &&
                        $extra_field_info['field_type'] == self::FIELD_TYPE_TAG
                    ) {
                        $options['where'] = str_replace(
                            $extra_info['field'],
                            'tag'.$counter.'.tag ',
                            $options['where']
                        );

                        $inject_joins .= "
                            INNER JOIN $this->table_field_rel_tag tag_rel$counter
                            ON (tag_rel$counter.field_id = ".$extra_info['id']." AND tag_rel$counter.item_id = s.".$this->primaryKey.")
                            INNER JOIN $this->table_field_tag tag$counter
                            ON (tag$counter.id =  tag_rel$counter.tag_id)
                        ";
                    } else {
                        //text, textarea, etc
                        $options['where'] = str_replace(
                            $extra_info['field'],
                            'fv'.$counter.'.field_id = '.$extra_info['id'].' AND fv'.$counter.'.value',
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
            'order' => $order,
            'limit' => $limit,
            'where' => $where,
            'inject_where' => $inject_where,
            'inject_joins' => $inject_joins,
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
            if (is_array($val)) {
                $result = '"%'.implode(';', $val).'%"';
                foreach ($val as $item) {
                    $item = trim($item);
                    $result .= ' OR '.$col.' LIKE "%'.$item.'%"';
                }
                $val = $result;

                return " $col {$this->ops[$oper]} $val ";
            } else {
                $val = '%'.$val.'%';
            }
        }
        $val = \Database::escape_string($val);

        return " $col {$this->ops[$oper]} '$val' ";
    }

    /**
     * @param $filters
     * @param string $stringToSearch
     * @return array
     */
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
                // normal fields
                $field = $rule->field;
                if (isset($rule->data) && is_string($rule->data) && $rule->data != -1) {
                    $condition_array[] = $this->get_where_clause($field, $rule->op, $rule->data);
                }
            } else {
                // Extra fields
                if (strpos($rule->field, '_second') === false) {
                    //No _second
                    $original_field = str_replace($stringToSearch, '', $rule->field);
                    $field_option = $this->get_handler_field_info_by_field_variable($original_field);

                    if ($field_option['field_type'] == self::FIELD_TYPE_DOUBLE_SELECT) {
                        if (isset($double_select[$rule->field])) {
                            $data = explode('#', $rule->data);
                            $rule->data = $data[1].'::'.$double_select[$rule->field];
                        } else {
                            // only was sent 1 select
                            if (is_string($rule->data)) {
                                $data = explode('#', $rule->data);
                                $rule->data = $data[1];
                            }
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

    /**
     * Get the extra fields and their formatted values
     * @param int|string $itemId The item ID (It could be a session_id, course_id or user_id)
     * @return array The extra fields data
     */
    public function getDataAndFormattedValues($itemId)
    {
        $valuesData = array();

        $fields = $this->get_all();

        foreach ($fields as $field) {
            if ($field['visible_to_self'] != '1') {
                continue;
            }

            $fieldValue = new ExtraFieldValue($this->type);
            $valueData = $fieldValue->get_values_by_handler_and_field_id($itemId, $field['id'], true);

            if (!$valueData) {
                continue;
            }

            $displayedValue = get_lang('None');

            switch ($field['field_type']) {
                case self::FIELD_TYPE_CHECKBOX:
                    if ($valueData !== false && $valueData['value'] == '1') {
                        $displayedValue = get_lang('Yes');
                    } else {
                        $displayedValue = get_lang('No');
                    }
                    break;
                case self::FIELD_TYPE_DATE:
                    if ($valueData !== false && !empty($valueData['value'])) {
                        $displayedValue = api_format_date($valueData['value'], DATE_FORMAT_LONG_NO_DAY);
                    }
                    break;
                case self::FIELD_TYPE_FILE_IMAGE:
                    if ($valueData === false || empty($valueData['value'])) {
                        break;
                    }

                    if (!file_exists(api_get_path(SYS_UPLOAD_PATH).$valueData['value'])) {
                        break;
                    }

                    $image = Display::img(
                        api_get_path(WEB_UPLOAD_PATH).$valueData['value'],
                        $field['display_text'],
                        array('width' => '300')
                    );

                    $displayedValue = Display::url(
                        $image,
                        api_get_path(WEB_UPLOAD_PATH).$valueData['value'],
                        array('target' => '_blank')
                    );
                    break;
                case self::FIELD_TYPE_FILE:
                    if ($valueData === false || empty($valueData['value'])) {
                        break;
                    }

                    if (!file_exists(api_get_path(SYS_UPLOAD_PATH).$valueData['value'])) {
                        break;
                    }

                    $displayedValue = Display::url(
                        get_lang('Download'),
                        api_get_path(WEB_UPLOAD_PATH).$valueData['value'],
                        array(
                            'title' => $field['display_text'],
                            'target' => '_blank'
                        )
                    );
                    break;
                default:
                    $displayedValue = $valueData['value'];
                    break;
            }

            $valuesData[] = array(
                'text' => $field['display_text'],
                'value' => $displayedValue
            );
        }

        return $valuesData;
    }

    /**
     * Gets an element
     * @param int $id
     * @param bool $translateDisplayText Optional
     * @return array
     */
    public function get($id, $translateDisplayText = true)
    {
        $info = parent::get($id);

        if ($translateDisplayText) {
            $info['display_text'] = self::translateDisplayName($info['variable'], $info['display_text']);
        }

        return $info;
    }

    /**
     * Translate the display text for a extra field
     * @param string $variable
     * @param string $defaultDisplayText
     * @return string
     */
    public static function translateDisplayName($variable, $defaultDisplayText)
    {
        $camelCase = api_underscore_to_camel_case($variable);

        return isset($GLOBALS[$camelCase]) ? $GLOBALS[$camelCase] : $defaultDisplayText;
    }

    /**
     * @param int $fieldId
     * @param string $tag
     *
     * @return array
     */
    public function getAllUserPerTag($fieldId, $tag)
    {
        $tagRelUserTable = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $tag = Database::escape_string($tag);
        $fieldId = (int) $fieldId;

        $sql = "SELECT user_id 
                FROM {$this->table_field_tag} f INNER JOIN $tagRelUserTable ft 
                ON tag_id = f.id 
                WHERE tag = '$tag' AND f.field_id = $fieldId;
        ";

        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param int $fieldId
     * @param int $tagId
     *
     * @return array
     */
    public function getAllSkillPerTag($fieldId, $tagId)
    {
        $skillTable = Database::get_main_table(TABLE_MAIN_SKILL);
        $tagRelExtraTable = Database::get_main_table(TABLE_MAIN_EXTRA_FIELD_REL_TAG);
        $fieldId = intval($fieldId);
        $tagId = intval($tagId);

        $sql = "SELECT s.id
                FROM $skillTable s INNER JOIN $tagRelExtraTable t
                ON t.item_id = s.id
                WHERE tag_id = $tagId AND t.field_id = $fieldId;
        ";

        $result = Database::query($sql);
        $result = Database::store_result($result, 'ASSOC');

        $skillList = [];
        foreach ($result as $index => $value) {
            $skillList[$value['id']] = $value['id'];
        }

        return $skillList;
    }
}
