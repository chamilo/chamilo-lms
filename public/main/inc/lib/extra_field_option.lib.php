<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ExtraFieldOptionsRepository;

/**
 * Handles the extra fields for various objects (users, sessions, courses).
 */
class ExtraFieldOption extends Model
{
    public $columns = [
        'id',
        'field_id',
        'option_value',
        'display_text',
        'option_order',
        'priority',
        'priority_message',
        'tms',
    ];

    public $extraField;
    public $fieldId;

    /** @var ExtraFieldOptionsRepository */
    public $repo;

    /**
     * Gets the table for the type of object for which we are using an extra field.
     *
     * @param string $type Type of object (course, user or session)
     */
    public function __construct($type)
    {
        parent::__construct();
        $this->type = $type;
        $extraField = new ExtraField($this->type);
        $this->extraField = $extraField;
        $this->table = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);
        $this->tableExtraField = Database::get_main_table(TABLE_EXTRA_FIELD);
        $this->repo = Database::getManager()->getRepository(ExtraFieldOptions::class);
    }

    /**
     * @return ExtraField
     */
    public function getExtraField()
    {
        return $this->extraField;
    }

    /**
     * Gets the number of options available for this field.
     *
     * @param int $fieldId
     *
     * @return int Number of options
     * @assert ('') === false
     * @assert (-1) == 0
     * @assert (0) == 0
     */
    public function get_count_by_field_id($fieldId)
    {
        if (empty($fieldId)) {
            return false;
        }
        $extraFieldType = $this->getExtraField()->getItemType();
        $fieldId = (int) $fieldId;

        $sql = "SELECT count(*) as count
                FROM $this->table o
                INNER JOIN $this->tableExtraField e
                ON o.field_id = e.id
                WHERE
                    o.field_id = $fieldId AND
                    e.item_type = $extraFieldType ";
        $result = Database::query($sql);
        $result = Database::fetch_array($result);

        return $result['count'];
    }

    /**
     * Returns a list of options for a specific field, separated by ";".
     *
     * @param int    $field_id
     * @param bool   $add_id_in_array Indicates whether we want the results to be given with their id
     * @param string $ordered_by      Order by clause (without the "order by") to be added to the SQL query
     *
     * @return string List of options separated by ;
     * @assert (-1, false, null) == ''
     */
    public function getFieldOptionsToString($field_id, $add_id_in_array = false, $ordered_by = null)
    {
        $options = self::get_field_options_by_field($field_id, $add_id_in_array, $ordered_by);
        $new_options = [];
        if (!empty($options)) {
            foreach ($options as $option) {
                $new_options[] = $option['option_value'].':'.$option['display_text'];
            }
            $string = implode(';', $new_options);

            return $string;
        }

        return '';
    }

    /**
     * Delete all the options of a specific field.
     *
     * @param int $field_id
     *
     * @assert (-1) === false
     */
    public function delete_all_options_by_field_id($field_id)
    {
        $field_id = (int) $field_id;
        $sql = "DELETE FROM {$this->table} WHERE field_id = $field_id";

        return Database::query($sql);
    }

    /**
     * @param array $params
     * @param bool  $showQuery
     *
     * @return int|bool
     */
    public function saveOptions($params, $showQuery = false)
    {
        $optionInfo = $this->repo->getFieldOptionByFieldAndOption(
            (int) $params['field_id'],
            (string) $params['option_value'],
            $this->extraField->getItemType()
        );

        if (!$optionInfo) {
            $optionValue = api_replace_dangerous_char($params['option_value']);
            $order = $this->get_max_order($params['field_id']);
            $newParams = [
                'field_id' => $params['field_id'],
                'value' => trim($optionValue),
                'display_text' => trim($params['display_text']),
                'option_order' => $order,
            ];

            return parent::save($newParams, $showQuery);
        }

        return false;
    }

    /**
     * Saves an option into the corresponding *_field_options table.
     *
     * @param array $params    Parameters to be considered for the insertion
     * @param bool  $showQuery Whether to show the query (sent to the parent save() method)
     *
     * @return bool True on success, false on error
     * @assert (array('field_id'=>0), false) === false
     * @assert (array('field_id'=>1), false) === true
     */
    public function save($params, $showQuery = false)
    {
        $field_id = (int) $params['field_id'];

        if (empty($field_id)) {
            return false;
        }

        $parseOptions = in_array(
            $params['value_type'],
            [
                ExtraField::FIELD_TYPE_RADIO,
                ExtraField::FIELD_TYPE_SELECT,
                ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                ExtraField::FIELD_TYPE_DOUBLE_SELECT,
                ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD,
                ExtraField::FIELD_TYPE_TRIPLE_SELECT,
            ]
        );

        if (empty($params['field_options']) || !$parseOptions) {
            return true;
        }

        switch ($params['value_type']) {
            case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                //$params['field_options'] = France:Paris;Bretagne;Marseilles;Lyon|Belgique:Bruxelles;Namur;Liège;Bruges|Peru:Lima;Piura;
            case ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD:
                //$params['field_options'] = Option 1|Option 2|Option 3
                $options_parsed = ExtraField::extra_field_double_select_convert_string_to_array(
                    $params['field_options']
                );

                if (empty($options_parsed)) {
                    break;
                }

                foreach ($options_parsed as $key => $option) {
                    $new_params = [
                        'field_id' => $field_id,
                        'option_value' => 0,
                        'display_text' => $option['label'],
                        'option_order' => 0,
                    ];
                    // Looking if option already exists:
                    $option_info = $this->get_field_option_by_field_id_and_option_display_text(
                        $field_id,
                        $option['label']
                    );

                    if (empty($option_info)) {
                        $sub_id = parent::save($new_params, $showQuery);
                    } else {
                        $sub_id = $option_info['id'];
                        $new_params['id'] = $sub_id;
                        parent::update($new_params, $showQuery);
                    }

                    if (ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD == $params['value_type']) {
                        continue;
                    }

                    foreach ($option['options'] as $sub_option) {
                        if (empty($sub_option)) {
                            continue;
                        }

                        $new_params = [
                            'field_id' => $field_id,
                            'option_value' => $sub_id,
                            'display_text' => $sub_option,
                            'option_order' => 0,
                        ];
                        $option_info = $this->getFieldOptionByFieldIdAndOptionDisplayTextAndOptionValue(
                            $field_id,
                            $sub_option,
                            $sub_id
                        );

                        if (empty($option_info)) {
                            parent::save($new_params, $showQuery);

                            continue;
                        }

                        $new_params['id'] = $option_info['id'];
                        parent::update($new_params, $showQuery);
                    }
                }
                break;
            case ExtraField::FIELD_TYPE_TRIPLE_SELECT:
                //Format: Option1\Option11:Option111;Option112\Option12:Option121|Option2\Option21:Option211
                $options = ExtraField::tripleSelectConvertStringToArray($params['field_options']);

                if (!$options) {
                    break;
                }

                foreach ($options as $level1) {
                    $level1Params = [
                        'field_id' => $field_id,
                        'option_value' => 0,
                        'display_text' => $level1['label'],
                        'option_order' => 0,
                    ];
                    $optionInfo = $this->get_field_option_by_field_id_and_option_display_text(
                        $field_id,
                        $level1['label']
                    );

                    if (empty($optionInfo)) {
                        $level1Id = parent::save($level1Params);
                    } else {
                        $level1Id = $optionInfo['id'];
                        $level1Params['id'] = $level1Id;
                        parent::update($level1Params);
                    }

                    foreach ($level1['options'] as $level2) {
                        $level2Params = [
                            'field_id' => $field_id,
                            'option_value' => $level1Id,
                            'display_text' => $level2['label'],
                            'display_order' => 0,
                        ];
                        $optionInfo = $this->getFieldOptionByFieldIdAndOptionDisplayTextAndOptionValue(
                            $field_id,
                            $level2['label'],
                            $level1Id
                        );

                        if (empty($optionInfo)) {
                            $level2Id = parent::save($level2Params);
                        } else {
                            $level2Id = $optionInfo['id'];
                            $level2Params['id'] = $level2Id;
                            parent::update($level2Params);
                        }

                        foreach ($level2['options'] as $level3) {
                            foreach ($level3 as $item) {
                                $level3Params = [
                                    'field_id' => $field_id,
                                    'option_value' => $level2Id,
                                    'display_text' => $item,
                                    'display_order' => 0,
                                ];
                                $optionInfo = $this->getFieldOptionByFieldIdAndOptionDisplayTextAndOptionValue(
                                    $field_id,
                                    $item,
                                    $level2Id
                                );

                                if (empty($optionInfo)) {
                                    parent::save($level3Params);
                                } else {
                                    $level3Params['id'] = $optionInfo['id'];
                                    parent::update($level3Params);
                                }
                            }
                        }
                    }
                }
                break;
            default:
                $list = explode(';', $params['field_options']);

                foreach ($list as $option) {
                    $option_info = $this->repo->getFieldOptionByFieldAndOption(
                        $field_id,
                        $option,
                        $this->extraField->getItemType()
                    );

                    // Use URLify only for new items
                    $optionValue = api_replace_dangerous_char($option);
                    $option = trim($option);

                    if ($option_info) {
                        continue;
                    }

                    $order = $this->get_max_order($field_id);

                    $new_params = [
                        'field_id' => $field_id,
                        'option_value' => trim($optionValue),
                        'display_text' => trim($option),
                        'option_order' => $order,
                    ];
                    parent::save($new_params, $showQuery);
                }
                break;
        }

        return true;
    }

    /**
     * Save one option item at a time.
     *
     * @param array $params          Parameters specific to the option
     * @param bool  $show_query      Whether to show the query (sent to parent save() method)
     * @param bool  $insert_repeated Whether to insert even if the option already exists
     *
     * @return bool True on success, false on failure
     * @assert (array('field_id'=>0),false) === false
     * @assert (array('field_id'=>0),false) === true
     */
    public function save_one_item($params, $show_query = false, $insert_repeated = true)
    {
        $field_id = (int) $params['field_id'];
        if (empty($field_id)) {
            return false;
        }

        if (isset($params['option_value'])) {
            $params['option_value'] = trim($params['option_value']);
        }

        if (isset($params['display_text'])) {
            $params['display_text'] = trim($params['display_text']);
        }

        if (empty($params['option_order'])) {
            $order = $this->get_max_order($field_id);
            $params['option_order'] = $order;
        }

        if (isset($params['id']) && empty($params['id'])) {
            unset($params['id']);
        }

        if ($insert_repeated) {
            parent::save($params, $show_query);
        } else {
            $check = $this->repo->getFieldOptionByFieldAndOption(
                $field_id,
                $params['option_value'],
                $this->extraField->getItemType()
            );
            if (!$check) {
                parent::save($params, $show_query);
            }
        }

        return true;
    }

    /**
     * Get the complete row of a specific option's display text of a specific field.
     *
     * @param int    $field_id
     * @param string $option_display_text Display value of the option
     *
     * @return mixed The row on success or false on failure
     * @assert (0, '') === false
     */
    public function get_field_option_by_field_id_and_option_display_text($field_id, $option_display_text)
    {
        $field_id = (int) $field_id;
        $option_display_text = Database::escape_string($option_display_text);
        $extraFieldType = $this->getExtraField()->getItemType();

        $sql = "SELECT s.* FROM {$this->table} s
                INNER JOIN {$this->tableExtraField} sf
                ON (s.field_id = sf.id)
                WHERE
                    field_id = $field_id AND
                    s.display_text = '".$option_display_text."' AND
                    sf.item_type = $extraFieldType
                ";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return Database::fetch_assoc($result);
        }

        return false;
    }

    /**
     * Get the complete row of a specific option's display text of a specific field.
     *
     * @param int    $field_id
     * @param string $option_display_text Display value of the option
     * @param string $option_value        Value of the option
     *
     * @return mixed The row on success or false on failure
     * @assert (0, '', '') === false
     */
    public function getFieldOptionByFieldIdAndOptionDisplayTextAndOptionValue(
        $field_id,
        $option_display_text,
        $option_value
    ) {
        $field_id = (int) $field_id;
        $option_display_text = Database::escape_string($option_display_text);
        $option_value = Database::escape_string($option_value);
        $extraFieldType = $this->getExtraField()->getItemType();

        $sql = "SELECT s.* FROM {$this->table} s
                INNER JOIN {$this->tableExtraField} sf
                ON (s.field_id = sf.id)
                WHERE
                    field_id = $field_id AND
                    sf.display_text = '".$option_display_text."' AND
                    option_value = '$option_value' AND
                    sf.item_type = ".$extraFieldType."
                ";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return Database::fetch_assoc($result);
        }

        return false;
    }

    /**
     * Gets an array of options for a specific field.
     *
     * @param int  $field_id        The field ID
     * @param bool $add_id_in_array Whether to add the row ID in the result
     * @param null $ordered_by      Extra ordering query bit
     *
     * @return array The options if they exists. Otherwise return false
     */
    public function get_field_options_by_field($field_id, $add_id_in_array = false, $ordered_by = null)
    {
        $field_id = (int) $field_id;

        $orderBy = null;
        switch ($ordered_by) {
            case 'id':
                $orderBy = ['id' => 'ASC'];
                break;
            case 'field_id':
                $orderBy = ['field' => 'ASC'];
                break;
            case 'option_value':
                $orderBy = ['optionValue' => 'ASC'];
                break;
            case 'display_text':
                $orderBy = ['displayText' => 'ASC'];
                break;
            case 'priority':
                $orderBy = ['priority' => 'ASC'];
                break;
            case 'priority_message':
                $orderBy = ['priorityMessage' => 'ASC'];
                break;
            case 'option_order':
                $orderBy = ['optionOrder' => 'ASC'];
                break;
        }

        $result = Container::getExtraFieldOptionsRepository()->findBy(['field' => $field_id], $orderBy);

        if (!$result) {
            return false;
        }

        $options = [];
        /** @var ExtraFieldOptions $row */
        foreach ($result as $row) {
            $option = [
                'id' => $row->getId(),
                'field_id' => $row->getField()->getId(),
                'option_value' => $row->getValue(),
                'display_text' => $row->getDisplayText(),
                'priority' => $row->getPriority(),
                'priority_message' => $row->getPriorityMessage(),
                'option_order' => $row->getOptionOrder(),
            ];

            if ($add_id_in_array) {
                $options[$row->getId()] = $option;
                continue;
            }
            $options[] = $option;
        }

        return $options;
    }

    /**
     * Get options for a specific field as array or in JSON format suited for the double-select format.
     *
     * @param int  $option_value_id Option value ID
     * @param bool $to_json         Return format (whether it should be formatted to JSON or not)
     *
     * @return mixed Row/JSON on success
     */
    public function get_second_select_field_options_by_field($option_value_id, $to_json = false)
    {
        $extraFieldOptionsRepo = Container::getExtraFieldOptionsRepository();
        $option = $extraFieldOptionsRepo->find($option_value_id);

        if (!$option) {
            return !$to_json ? [] : '{}';
        }

        $subOptions = $extraFieldOptionsRepo->findSecondaryOptions($option);
        $optionsInfo = [];

        /** @var ExtraFieldOptions $subOption */
        foreach ($subOptions as $subOption) {
            $optionsInfo[] = [
                'id' => $subOption->getId(),
                'field_id' => $subOption->getField()->getId(),
                'option_value' => $subOption->getValue(),
                'display_text' => $subOption->getDisplayText(),
                'priority' => $subOption->getPriority(),
                'priority_message' => $subOption->getPriorityMessage(),
                'option_order' => $subOption->getOptionOrder(),
            ];
        }

        if (!$to_json) {
            return $optionsInfo;
        }

        $json = [];

        foreach ($optionsInfo as $optionInfo) {
            $json[$optionInfo['id']] = $optionInfo['display_text'];
        }

        return json_encode($json);
    }

    /**
     * Get options for a specific field as string split by ;
     */
    public function get_field_options_by_field_to_string(int $field_id, string $ordered_by = null): string
    {
        $field = new ExtraField($this->type);
        $field_info = $field->get($field_id);
        $options = self::get_field_options_by_field($field_id, false, $ordered_by);
        $elements = [];
        if (!empty($options)) {
            switch ($field_info['value_type']) {
                case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                    $html = ExtraField::extra_field_double_select_convert_array_to_string($options);
                    break;
                case ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD:
                    $html = ExtraField::extraFieldSelectWithTextConvertArrayToString($options);
                    break;
                case ExtraField::FIELD_TYPE_TRIPLE_SELECT:
                    $html = ExtraField::tripleSelectConvertArrayToString($options);
                    break;
                default:
                    foreach ($options as $option) {
                        // If option_value is empty, use display_text
                        $value = !empty($option['option_value']) ? trim($option['option_value']) : trim($option['display_text']);
                        if (!empty($value)) {
                            $elements[] = $value;
                        }
                    }
                    $html = !empty($elements) ? implode(';', $elements) : get_lang("No options available");
                    break;
            }

            return $html;
        }

        return get_lang("No options available");
    }

    /**
     * Get the maximum order value for a specific field.
     *
     * @param int $field_id
     *
     * @return int Current max ID + 1 (we start from 0)
     * @assert (0, '') === 1
     */
    public function get_max_order($field_id)
    {
        $field_id = (int) $field_id;
        $sql = "SELECT MAX(option_order)
                FROM {$this->table}
                WHERE field_id = $field_id";
        $res = Database::query($sql);
        $max = 1;
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $max = $row[0] + 1;
        }

        return $max;
    }

    /**
     * Display a form with the options for the field_id given in REQUEST.
     */
    public function display()
    {
        // action links
        echo '<div class="actions">';
        $field_id = isset($_REQUEST['field_id']) ? intval($_REQUEST['field_id']) : null;
        echo '<a href="'.api_get_self().'?action=add&type='.$this->type.'&field_id='.$field_id.'">'.
                Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add')).'</a>';
        echo '</div>';
        echo Display::grid_html('extra_field_options');
    }

    /**
     * @return array
     */
    public function getPriorityOptions()
    {
        return [
            '' => get_lang('Please select an option'),
            1 => get_lang('Success'),
            2 => get_lang('Information'),
            3 => get_lang('Warning !'),
            4 => get_lang('Error'),
        ];
    }

    /**
     * @param $priority
     *
     * @return string|null
     */
    public function getPriorityMessageType($priority)
    {
        switch ($priority) {
            case 1:
                return 'success';
            case 2:
                return 'info';
            case 3:
                return 'warning';
            case 4:
                return 'error';
        }

        return null;
    }

    /**
     * Returns an HTML form for the current field.
     *
     * @param string URL to send the form to (action=...)
     * @param string Type of action to offer through the form (edit, usually)
     *
     * @return FormValidator
     */
    public function return_form($url, $action)
    {
        $form_name = $this->type.'_field';
        $form = new FormValidator($form_name, 'post', $url);
        // Setting the form elements
        $header = get_lang('Add');
        if ('edit' == $action) {
            $header = get_lang('Edit');
        }

        $form->addElement('header', $header);
        $id = isset($_GET['id']) ? (int) $_GET['id'] : '';

        $form->addElement('hidden', 'id', $id);
        $form->addElement('hidden', 'type', $this->type);
        $form->addElement('hidden', 'field_id', $this->fieldId);

        if ('edit' === $action) {
            $translateUrl = api_get_path(WEB_CODE_PATH).'extrafield/translate_option.php?'.http_build_query(
                ['id' => $id]
            );
            $translateButton = Display::toolbarButton(
                get_lang('Translate this term'),
                $translateUrl,
                'language',
                'link'
            );

            $form->addText(
                'display_text',
                [get_lang('Name'), $translateButton]
            );
        } else {
            $form->addElement('text', 'display_text', get_lang('Name'));
        }

        $form->addElement('text', 'option_value', get_lang('Value'));
        $form->addElement('text', 'option_order', get_lang('Order'));
        $form->addSelect('priority', get_lang('Priority'), $this->getPriorityOptions());
        $form->addElement('textarea', 'priority_message', get_lang('Message type'));

        $defaults = [];

        if ('edit' === $action) {
            // Setting the defaults
            $defaults = $this->get($id, false);
            $form->freeze('option_value');
            $form->addButtonUpdate(get_lang('Edit'));
        } else {
            $form->addButtonCreate(get_lang('Add'));
        }

        $form->setDefaults($defaults);

        $form->addRule('display_text', get_lang('Required field'), 'required');
        $form->addRule('option_value', get_lang('Required field'), 'required');

        return $form;
    }

    /**
     * @param string $tag
     * @param int    $field_id
     * @param int    $limit
     *
     * @return array
     */
    public function searchByField($tag, $field_id, $limit = 10)
    {
        $field_id = (int) $field_id;
        $limit = (int) $limit;
        $tag = Database::escape_string($tag);

        $sql = "SELECT DISTINCT id, option_display_text
                FROM {$this->table}
                WHERE
                    field_id = '".$field_id."' AND
                    option_value LIKE '%$tag%'
                ORDER BY option_value
                LIMIT 0, $limit
                ";
        $result = Database::query($sql);
        $values = [];
        if (Database::num_rows($result)) {
            $values = Database::store_result($result, 'ASSOC');
        }

        return $values;
    }

    /**
     * @param string $tag
     * @param int    $field_id
     * @param int    $limit
     *
     * @return string
     */
    public function getSearchOptionsByField($tag, $field_id, $limit = 10)
    {
        $result = $this->searchByField($tag, $field_id, $limit = 10);
        $values = [];
        $json = null;
        if (!empty($result)) {
            foreach ($result as $item) {
                $values[] = [
                    'value' => $item['id'],
                    'caption' => $item['option_display_text'],
                ];
            }
            $json = json_encode($values);
        }

        return $json;
    }

    /**
     * Gets an element.
     *
     * @param int  $id
     * @param bool $translateDisplayText Optional
     *
     * @return array
     */
    public function get($id, $translateDisplayText = true)
    {
        $info = parent::get($id);

        if ($info) {
            $option = Container::getExtraFieldOptionsRepository()->find($id);
            if (!$translateDisplayText) {
                $option->setLocale(Container::getParameter('locale'));
                Database::getManager()->refresh($option);
            }
            $info['display_text'] = $option->getDisplayText();
        }

        return $info;
    }

    public function get_all(array $options = []): array
    {
        $result = parent::get_all($options);

        foreach ($result as &$row) {
            $option = Container::getExtraFieldOptionsRepository()->find($row['id']);
            $row['display_text'] = $option->getDisplayText();
        }

        return $result;
    }

    /**
     * @param string $variable
     *
     * @return array|ExtraFieldOptions[]
     */
    public function getOptionsByFieldVariable($variable)
    {
        $extraFieldType = $this->getExtraField()->getItemType();

        $dql = "SELECT o FROM ChamiloCoreBundle:ExtraFieldOptions o
            INNER JOIN ChamiloCoreBundle:ExtraField f WITH o.field = f.id
            WHERE f.variable = :variable AND f.itemType = :item_type
            ORDER BY o.value ASC";

        $result = Database::getManager()
            ->createQuery($dql)
            ->setParameters(['variable' => $variable, 'item_type' => $extraFieldType])
            ->getResult();

        return $result;
    }
}
