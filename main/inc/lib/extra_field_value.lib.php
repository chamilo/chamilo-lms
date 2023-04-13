<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Tag;
use ChamiloSession as Session;

/**
 * Class ExtraFieldValue
 * Declaration for the ExtraFieldValue class, managing the values in extra
 * fields for any data type.
 */
class ExtraFieldValue extends Model
{
    public $type = '';
    public $columns = [
        'id',
        'field_id',
        'value',
        'comment',
        'item_id',
        'created_at',
        'updated_at',
    ];
    /** @var ExtraField */
    public $extraField;

    /**
     * Formats the necessary elements for the given datatype.
     *
     * @param string $type The type of data to which this extra field
     *                     applies (user, course, session, ...)
     *
     * @assert (-1) === false
     */
    public function __construct($type)
    {
        parent::__construct();
        $this->type = $type;
        $extraField = new ExtraField($this->type);
        $this->extraField = $extraField;
        $this->table = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $this->table_handler_field = Database::get_main_table(TABLE_EXTRA_FIELD);
    }

    /**
     * @return ExtraField
     */
    public function getExtraField()
    {
        return $this->extraField;
    }

    /**
     * Gets the number of values stored in the table (all fields together)
     * for this type of resource.
     *
     * @return int Number of rows in the table
     * @assert () !== false
     */
    public function get_count()
    {
        $em = Database::getManager();
        $query = $em->getRepository('ChamiloCoreBundle:ExtraFieldValues')->createQueryBuilder('e');
        $query->select('count(e.id)');
        $query->where('e.extraFieldType = :type');
        $query->setParameter('type', $this->getExtraField()->getExtraFieldType());

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Save the extra fields values
     * In order to save this function needs a item_id (user id, course id, etc)
     * This function is used with $extraField->addElements().
     *
     * @param array $params              array for the insertion into the *_field_values table (each label must be prefixed by 'extra_')
     * @param bool  $onlySubmittedFields Only save parameters in the $param array
     * @param bool  $showQuery
     * @param array $saveOnlyThisFields
     * @param array $avoidFields         do not insert/modify this field
     * @param bool  $forceSave
     *
     * @return mixed false on empty params, void otherwise
     * @assert (array()) === false
     */
    public function saveFieldValues(
        $params,
        $onlySubmittedFields = false,
        $showQuery = false,
        $saveOnlyThisFields = [],
        $avoidFields = [],
        $forceSave = false,
        $deleteOldValues = true
    ) {
        foreach ($params as $key => $value) {
            $found = strpos($key, '__persist__');

            if (false === $found) {
                continue;
            }

            $tempKey = str_replace('__persist__', '', $key);
            if (!isset($params[$tempKey])) {
                $params[$tempKey] = [];
            }
        }

        if (empty($params['item_id'])) {
            return false;
        }

        $type = $this->getExtraField()->getExtraFieldType();

        $extraField = new ExtraField($this->type);
        $extraFields = $extraField->get_all(null, 'option_order');

        // Parse params.
        foreach ($extraFields as $fieldDetails) {
            if (false === $forceSave) {
                // if the field is not visible to the user in the end, we need to apply special rules.
                if (1 != $fieldDetails['visible_to_self']) {
                    if (isset($params['origin']) && 'profile' == $params['origin']) {
                        continue;
                    } else {
                        //only admins should be able to add those values
                        if (!api_is_platform_admin(true, true)) {
                            // although if not admin but sent through a CLI script, we should accept it as well
                            if (PHP_SAPI != 'cli') {
                                continue; //not a CLI script, so don't write the value to DB
                            }
                        }
                    }
                }
            }

            $field_variable = $fieldDetails['variable'];

            if ($onlySubmittedFields && !isset($params['extra_'.$field_variable])) {
                continue;
            }

            if (!empty($avoidFields)) {
                if (in_array($field_variable, $avoidFields)) {
                    continue;
                }
            }

            if (!empty($saveOnlyThisFields)) {
                if (!in_array($field_variable, $saveOnlyThisFields)) {
                    continue;
                }
            }

            $value = '';
            if (isset($params['extra_'.$field_variable])) {
                $value = $params['extra_'.$field_variable];
            }
            $extraFieldInfo = $this->getExtraField()->get_handler_field_info_by_field_variable($field_variable);

            if (!$extraFieldInfo) {
                continue;
            }

            $commentVariable = 'extra_'.$field_variable.'_comment';
            $comment = isset($params[$commentVariable]) ? $params[$commentVariable] : null;
            $dirPermissions = api_get_permissions_for_new_directories();

            switch ($extraFieldInfo['field_type']) {
                case ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES:
                case ExtraField::FIELD_TYPE_GEOLOCALIZATION:
                    if (!empty($value)) {
                        if (isset($params['extra_'.$extraFieldInfo['variable'].'_coordinates'])) {
                            $value = $value.'::'.$params['extra_'.$extraFieldInfo['variable'].'_coordinates'];
                        }
                        $newParams = [
                            'item_id' => $params['item_id'],
                            'field_id' => $extraFieldInfo['id'],
                            'value' => $value,
                            'comment' => $comment,
                        ];
                        self::save($newParams, $showQuery);
                    }
                    break;
                case ExtraField::FIELD_TYPE_TAG:
                    if (EntityExtraField::USER_FIELD_TYPE == $type) {
                        UserManager::delete_user_tags(
                            $params['item_id'],
                            $extraFieldInfo['id']
                        );

                        UserManager::process_tags(
                            $value,
                            $params['item_id'],
                            $extraFieldInfo['id']
                        );
                        break;
                    }

                    $em = Database::getManager();

                    $currentTags = $em
                        ->getRepository('ChamiloCoreBundle:ExtraFieldRelTag')
                        ->findBy([
                            'fieldId' => $extraFieldInfo['id'],
                            'itemId' => $params['item_id'],
                        ]);

                    if ($deleteOldValues) {
                        foreach ($currentTags as $extraFieldtag) {
                            $em->remove($extraFieldtag);
                        }
                    }
                    $em->flush();
                    $tagValues = is_array($value) ? $value : [$value];
                    $tags = [];

                    foreach ($tagValues as $tagValue) {
                        if (empty($tagValue)) {
                            continue;
                        }

                        $tagsResult = $em->getRepository('ChamiloCoreBundle:Tag')
                            ->findBy([
                                'tag' => $tagValue,
                                'fieldId' => $extraFieldInfo['id'],
                            ]);

                        if (empty($tagsResult)) {
                            $tag = new Tag();
                            $tag->setFieldId($extraFieldInfo['id']);
                            $tag->setTag($tagValue);

                            $tags[] = $tag;
                        } else {
                            $tags = array_merge($tags, $tagsResult);
                        }
                    }

                    foreach ($tags as $tag) {
                        $tagUses = $em
                            ->getRepository('ChamiloCoreBundle:ExtraFieldRelTag')
                            ->findBy([
                                'tagId' => $tag->getId(),
                            ]);

                        $tag->setCount(count($tagUses) + 1);
                        $em->persist($tag);
                    }

                    $em->flush();

                    foreach ($tags as $tag) {
                        $fieldRelTag = new ExtraFieldRelTag();
                        $fieldRelTag->setFieldId($extraFieldInfo['id']);
                        $fieldRelTag->setItemId($params['item_id']);
                        $fieldRelTag->setTagId($tag->getId());
                        $em->persist($fieldRelTag);
                    }

                    $em->flush();
                    break;
                case ExtraField::FIELD_TYPE_FILE_IMAGE:
                    $fileDir = $fileDirStored = '';
                    switch ($this->type) {
                        case 'course':
                            $fileDir = api_get_path(SYS_UPLOAD_PATH)."courses/";
                            $fileDirStored = "courses/";
                            break;
                        case 'session':
                            $fileDir = api_get_path(SYS_UPLOAD_PATH)."sessions/";
                            $fileDirStored = "sessions/";
                            break;
                        case 'user':
                            $fileDir = UserManager::getUserPathById($params['item_id'], 'system');
                            $fileDirStored = UserManager::getUserPathById($params['item_id'], 'last');
                            break;
                        case 'work':
                            $fileDir = api_get_path(SYS_UPLOAD_PATH).'work/';
                            $fileDirStored = 'work/';
                            break;
                    }

                    $fileName = ExtraField::FIELD_TYPE_FILE_IMAGE."_{$params['item_id']}.png";

                    if (!file_exists($fileDir)) {
                        mkdir($fileDir, $dirPermissions, true);
                    }

                    if (!empty($value['tmp_name']) && isset($value['error']) && $value['error'] == 0) {
                        // Crop the image to adjust 16:9 ratio
                        if (isset($params['extra_'.$field_variable.'_crop_result'])) {
                            $crop = new Image($value['tmp_name']);
                            $crop->crop($params['extra_'.$field_variable.'_crop_result']);
                        }

                        $imageExtraField = new Image($value['tmp_name']);
                        $imageExtraField->resize(400);
                        $imageExtraField->send_image($fileDir.$fileName, -1, 'png');
                        $newParams = [
                            'item_id' => $params['item_id'],
                            'field_id' => $extraFieldInfo['id'],
                            'value' => $fileDirStored.$fileName,
                            'comment' => $comment,
                        ];
                        $this->save($newParams);
                    }
                    break;
                case ExtraField::FIELD_TYPE_FILE:
                    $fileDir = $fileDirStored = '';
                    switch ($this->type) {
                        case 'course':
                            $fileDir = api_get_path(SYS_UPLOAD_PATH).'courses/';
                            $fileDirStored = "courses/";
                            break;
                        case 'session':
                            $fileDir = api_get_path(SYS_UPLOAD_PATH).'sessions/';
                            $fileDirStored = "sessions/";
                            break;
                        case 'user':
                            $fileDir = UserManager::getUserPathById($params['item_id'], 'system');
                            $fileDirStored = UserManager::getUserPathById($params['item_id'], 'last');
                            break;
                        case 'work':
                            $fileDir = api_get_path(SYS_UPLOAD_PATH).'work/';
                            $fileDirStored = "work/";
                            break;
                        case 'scheduled_announcement':
                            $fileDir = api_get_path(SYS_UPLOAD_PATH).'scheduled_announcement/';
                            $fileDirStored = 'scheduled_announcement/';
                            break;
                    }

                    $cleanedName = api_replace_dangerous_char($value['name']);
                    $fileName = ExtraField::FIELD_TYPE_FILE."_{$params['item_id']}_$cleanedName";
                    if (!file_exists($fileDir)) {
                        mkdir($fileDir, $dirPermissions, true);
                    }

                    if (!empty($value['tmp_name']) && isset($value['error']) && $value['error'] == 0) {
                        $cleanedName = api_replace_dangerous_char($value['name']);
                        $fileName = ExtraField::FIELD_TYPE_FILE."_{$params['item_id']}_$cleanedName";
                        moveUploadedFile($value, $fileDir.$fileName);

                        $new_params = [
                            'item_id' => $params['item_id'],
                            'field_id' => $extraFieldInfo['id'],
                            'value' => $fileDirStored.$fileName,
                        ];

                        if ('session' !== $this->type && 'course' !== $this->type) {
                            $new_params['comment'] = $comment;
                        }

                        $this->save($new_params);
                    }
                    break;
                case ExtraField::FIELD_TYPE_CHECKBOX:
                    $fieldToSave = 0;
                    if (is_array($value)) {
                        if (isset($value['extra_'.$field_variable])) {
                            $fieldToSave = 1;
                        }
                    }

                    $newParams = [
                        'item_id' => $params['item_id'],
                        'field_id' => $extraFieldInfo['id'],
                        'value' => $fieldToSave,
                        'comment' => $comment,
                    ];
                    $this->save($newParams);

                    break;
                case ExtraField::FIELD_TYPE_DATE:
                    $d = DateTime::createFromFormat('Y-m-d', $value);
                    $valid = $d && $d->format('Y-m-d') === $value;
                    if ($valid) {
                        $newParams = [
                            'item_id' => $params['item_id'],
                            'field_id' => $extraFieldInfo['id'],
                            'value' => $value,
                            'comment' => $comment,
                        ];
                        $this->save($newParams, $showQuery);
                    }
                    break;
                case ExtraField::FIELD_TYPE_DATETIME:
                    $d = DateTime::createFromFormat('Y-m-d H:i', $value);
                    $valid = $d && $d->format('Y-m-d H:i') === $value;
                    if (!$valid && EntityExtraField::LP_ITEM_FIELD_TYPE === (int) $extraFieldInfo['extra_field_type']) {
                        $valid = empty($value);
                    }
                    if ($valid) {
                        $newParams = [
                            'item_id' => $params['item_id'],
                            'field_id' => $extraFieldInfo['id'],
                            'value' => $value,
                            'comment' => $comment,
                        ];
                        $this->save($newParams, $showQuery);
                    }
                    break;
                default:
                    $newParams = [
                        'item_id' => $params['item_id'],
                        'field_id' => $extraFieldInfo['id'],
                        'value' => $value,
                        'comment' => $comment,
                    ];
                    $this->save($newParams, $showQuery);
            }
        }
    }

    /**
     * Save values in the *_field_values table.
     *
     * @param array $params    Structured array with the values to save
     * @param bool  $showQuery Whether to show the insert query (passed to the parent save() method)
     *
     * @return mixed The result sent from the parent method
     * @assert (array()) === false
     */
    public function save($params, $showQuery = false)
    {
        $extra_field = $this->getExtraField();

        // Setting value to insert.
        $value = $params['value'];
        $value_to_insert = null;

        if (is_array($value)) {
            $value_to_insert = implode(';', $value);
        } else {
            $value_to_insert = $value;
        }

        $params['value'] = $value_to_insert;

        // If field id exists
        if (isset($params['field_id'])) {
            $extraFieldInfo = $extra_field->get($params['field_id']);
        } else {
            // Try the variable
            $extraFieldInfo = $extra_field->get_handler_field_info_by_field_variable(
                $params['variable']
            );
            if ($extraFieldInfo) {
                $params['field_id'] = $extraFieldInfo['id'];
            }
        }

        if ($extraFieldInfo) {
            switch ($extraFieldInfo['field_type']) {
                case ExtraField::FIELD_TYPE_RADIO:
                case ExtraField::FIELD_TYPE_SELECT:
                    break;
                case ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
                    //$field_options = $session_field_option->get_field_options_by_field($params['field_id']);
                    //$params['field_value'] = split(';', $value_to_insert);
                    /*
                        if ($field_options) {
                            $check = false;
                            foreach ($field_options as $option) {
                                if (in_array($option['option_value'], $values)) {
                                    $check = true;
                                    break;
                                }
                           }
                           if (!$check) {
                               return false; //option value not found
                           }
                       } else {
                           return false; //enumerated type but no option found
                       }*/
                    break;
                case ExtraField::FIELD_TYPE_TEXT:
                case ExtraField::FIELD_TYPE_TEXTAREA:
                    break;
                case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                case ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD:
                    if (is_array($value)) {
                        $value_to_insert = null;
                        if (isset($value['extra_'.$extraFieldInfo['variable']]) &&
                            isset($value['extra_'.$extraFieldInfo['variable'].'_second'])
                        ) {
                            $value_to_insert = $value['extra_'.$extraFieldInfo['variable']].'::'.$value['extra_'.$extraFieldInfo['variable'].'_second'];
                        }
                    }
                    break;
                default:
                    break;
            }

            if (ExtraField::FIELD_TYPE_TAG == $extraFieldInfo['field_type']) {
                $field_values = self::getAllValuesByItemAndFieldAndValue(
                    $params['item_id'],
                    $params['field_id'],
                    $value
                );
            } else {
                $field_values = self::get_values_by_handler_and_field_id(
                    $params['item_id'],
                    $params['field_id']
                );
            }

            $params['value'] = $value_to_insert;
            //$params['author_id'] = api_get_user_id();

            // Insert
            if (empty($field_values)) {
                /* Enable this when field_loggeable is introduced as a table field (2.0)
                if ($extraFieldInfo['field_loggeable'] == 1) {
                */
                if (false) {
                    global $app;
                    switch ($this->type) {
                        case 'question':
                            $extraFieldValue = new ChamiloLMS\Entity\QuestionFieldValues();
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setQuestionId($params[$this->handler_id]);
                            break;
                        case 'course':
                            $extraFieldValue = new ChamiloLMS\Entity\CourseFieldValues();
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setQuestionId($params[$this->handler_id]);
                            break;
                        case 'user':
                            $extraFieldValue = new ChamiloLMS\Entity\UserFieldValues();
                            $extraFieldValue->setUserId($params[$this->handler_id]);
                            $extraFieldValue->setAuthorId(api_get_user_id());
                            break;
                        case 'session':
                            $extraFieldValue = new ChamiloLMS\Entity\SessionFieldValues();
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setSessionId($params[$this->handler_id]);
                            break;
                    }
                    if (isset($extraFieldValue)) {
                        if (!empty($params['value'])) {
                            $extraFieldValue->setComment($params['comment']);
                            $extraFieldValue->setFieldValue($params['value']);
                            $extraFieldValue->setFieldId($params['field_id']);
                            $extraFieldValue->setTms(api_get_utc_datetime(null, false, true));
                            $app['orm.ems']['db_write']->persist($extraFieldValue);
                            $app['orm.ems']['db_write']->flush();
                        }
                    }
                } else {
                    if ($extraFieldInfo['field_type'] == ExtraField::FIELD_TYPE_TAG) {
                        $option = new ExtraFieldOption($this->type);
                        $optionExists = $option->get($params['value']);
                        if (empty($optionExists)) {
                            $optionParams = [
                                'field_id' => $params['field_id'],
                                'option_value' => $params['value'],
                            ];
                            $optionId = $option->saveOptions($optionParams);
                        } else {
                            $optionId = $optionExists['id'];
                        }

                        $params['value'] = $optionId;
                        if ($optionId) {
                            return parent::save($params, $showQuery);
                        }
                    } else {
                        return parent::save($params, $showQuery);
                    }
                }
            } else {
                // Update
                /* Enable this when field_loggeable is introduced as a table field (2.0)
                if ($extraFieldInfo['field_loggeable'] == 1) {
                */
                if (false) {
                    global $app;
                    switch ($this->type) {
                        case 'question':
                            $extraFieldValue = $app['orm.ems']['db_write']->getRepository('ChamiloLMS\Entity\QuestionFieldValues')->find($field_values['id']);
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setQuestionId($params[$this->handler_id]);
                            break;
                        case 'course':
                            $extraFieldValue = $app['orm.ems']['db_write']->getRepository('ChamiloLMS\Entity\CourseFieldValues')->find($field_values['id']);
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setCourseCode($params[$this->handler_id]);
                            break;
                        case 'user':
                            $extraFieldValue = $app['orm.ems']['db_write']->getRepository('ChamiloLMS\Entity\UserFieldValues')->find($field_values['id']);
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setAuthorId(api_get_user_id());
                            break;
                        case 'session':
                            $extraFieldValue = $app['orm.ems']['db_write']->getRepository('ChamiloLMS\Entity\SessionFieldValues')->find($field_values['id']);
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setSessionId($params[$this->handler_id]);
                            break;
                    }

                    if (isset($extraFieldValue)) {
                        if (!empty($params['value'])) {
                            /*
                             *  If the field value is similar to the previous value then the comment will be the same
                                in order to no save in the log an empty record
                            */
                            if ($extraFieldValue->getFieldValue() == $params['value']) {
                                if (empty($params['comment'])) {
                                    $params['comment'] = $extraFieldValue->getComment();
                                }
                            }

                            $extraFieldValue->setComment($params['comment']);
                            $extraFieldValue->setFieldValue($params['value']);
                            $extraFieldValue->setFieldId($params['field_id']);
                            $extraFieldValue->setTms(api_get_utc_datetime(null, false, true));
                            $app['orm.ems']['db_write']->persist($extraFieldValue);
                            $app['orm.ems']['db_write']->flush();
                        }
                    }
                } else {
                    $params['id'] = $field_values['id'];

                    return parent::update($params, $showQuery);
                }
            }
        }
    }

    /**
     * Returns the value of the given extra field on the given resource.
     *
     * @param int  $item_id   Item ID (It could be a session_id, course_id or user_id)
     * @param int  $field_id  Field ID (the ID from the *_field table)
     * @param bool $transform Whether to transform the result to a human readable strings
     *
     * @return mixed A structured array with the field_id and field_value, or false on error
     * @assert (-1,-1) === false
     */
    public function get_values_by_handler_and_field_id($item_id, $field_id, $transform = false)
    {
        $field_id = (int) $field_id;
        $item_id = Database::escape_string($item_id);

        $sql = "SELECT s.*, field_type FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf ON (s.field_id = sf.id)
                WHERE
                    item_id = $item_id AND
                    field_id = $field_id AND
                    sf.extra_field_type = ".$this->getExtraField()->getExtraFieldType()."
                ORDER BY id";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $result = Database::fetch_array($result, 'ASSOC');
            if ($transform) {
                if (!empty($result['value'])) {
                    switch ($result['field_type']) {
                        case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                            $field_option = new ExtraFieldOption($this->type);
                            $options = explode('::', $result['value']);
                            // only available for PHP 5.4  :( $result['field_value'] = $field_option->get($options[0])['id'].' -> ';
                            $result = $field_option->get($options[0]);
                            $result_second = $field_option->get($options[1]);
                            if (!empty($result)) {
                                $result['value'] = $result['display_text'].' -> ';
                                $result['value'] .= $result_second['display_text'];
                            }
                            break;
                        case ExtraField::FIELD_TYPE_SELECT:
                            $field_option = new ExtraFieldOption($this->type);
                            $extra_field_option_result = $field_option->get_field_option_by_field_and_option(
                                $result['field_id'],
                                $result['value']
                            );

                            if (isset($extra_field_option_result[0])) {
                                $result['value'] = $extra_field_option_result[0]['display_text'];
                            }
                            break;
                        case ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD:
                            $options = explode('::', $result['value']);

                            $field_option = new ExtraFieldOption($this->type);
                            $result = $field_option->get($options[0]);

                            if (!empty($result)) {
                                $result['value'] = $result['display_text']
                                    .'&rarr;'
                                    .$options[1];
                            }
                            break;
                        case ExtraField::FIELD_TYPE_TRIPLE_SELECT:
                            $optionIds = explode(';', $result['value']);
                            $optionValues = [];

                            foreach ($optionIds as $optionId) {
                                $objEfOption = new ExtraFieldOption('user');
                                $optionInfo = $objEfOption->get($optionId);

                                $optionValues[] = $optionInfo['display_text'];
                            }

                            $result['value'] = implode(' / ', $optionValues);
                            break;
                    }
                }
            }

            return $result;
        }

        return false;
    }

    /**
     * @param string $tag
     * @param int    $field_id
     * @param int    $limit
     *
     * @return array
     */
    public function searchValuesByField($tag, $field_id, $limit = 10)
    {
        $field_id = (int) $field_id;
        $limit = (int) $limit;
        $extraFieldType = $this->getExtraField()->getExtraFieldType();

        $tag = Database::escape_string($tag);
        $sql = "SELECT DISTINCT s.value, s.field_id
                FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf
                ON (s.field_id = sf.id)
                WHERE
                    field_id = $field_id AND
                    value LIKE '%$tag%' AND
                    sf.extra_field_type = $extraFieldType
                ORDER BY value
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
     * Gets a structured array of the original item and its extra values, using
     * a specific original item and a field name (like "branch", or "birthdate").
     *
     * @param int    $item_id            Item ID from the original table
     * @param string $field_variable     The name of the field we are looking for
     * @param bool   $transform
     * @param bool   $filterByVisibility
     * @param int    $visibility
     *
     * @return mixed Array of results, or false on error or not found
     * @assert (-1,'') === false
     */
    public function get_values_by_handler_and_field_variable(
        $item_id,
        $field_variable,
        $transform = false,
        $filterByVisibility = false,
        $visibility = 0
    ) {
        $item_id = (int) $item_id;
        $field_variable = Database::escape_string($field_variable);
        $extraFieldType = $this->getExtraField()->getExtraFieldType();

        $sql = "SELECT s.*, field_type
                FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf
                ON (s.field_id = sf.id)
                WHERE
                    item_id = $item_id  AND
                    variable = '$field_variable' AND
                    sf.extra_field_type = $extraFieldType
                ";
        if ($filterByVisibility) {
            $visibility = (int) $visibility;
            $sql .= " AND visible_to_self = $visibility ";
        }
        $sql .= ' ORDER BY id';

        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $result = Database::fetch_array($result, 'ASSOC');
            if ($transform) {
                if ($result['field_type'] == ExtraField::FIELD_TYPE_DOUBLE_SELECT) {
                    if (!empty($result['value'])) {
                        $field_option = new ExtraFieldOption($this->type);
                        $options = explode('::', $result['value']);
                        $result = $field_option->get($options[0]);
                        $result_second = $field_option->get($options[1]);
                        if (!empty($result)) {
                            $result['value'] = $result['display_text'].' -> ';
                            $result['value'] .= $result_second['display_text'];
                        }
                    }
                }
                if ($result['field_type'] == ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD) {
                    if (!empty($result['value'])) {
                        $options = explode('::', $result['value']);
                        $field_option = new ExtraFieldOption($this->type);
                        $result = $field_option->get($options[0]);
                        if (!empty($result)) {
                            $result['value'] = $result['display_text'].'&rarr;'.$options[1];
                        }
                    }
                }
                if ($result['field_type'] == ExtraField::FIELD_TYPE_TRIPLE_SELECT) {
                    if (!empty($result['value'])) {
                        $optionIds = explode(';', $result['value']);
                        $optionValues = [];

                        foreach ($optionIds as $optionId) {
                            $objEfOption = new ExtraFieldOption('user');
                            $optionInfo = $objEfOption->get($optionId);
                            $optionValues[] = $optionInfo['display_text'];
                        }

                        $result['value'] = implode(' / ', $optionValues);
                    }
                }

                if ($result['field_type'] == Extrafield::FIELD_TYPE_SELECT && !empty($result['value'])) {
                    $fopt = (new ExtraFieldOption('user'))
                        ->get_field_option_by_field_and_option($result['field_id'], $result['value']);
                    $fopt = current(is_array($fopt) ? $fopt : []);

                    $result['value'] = $fopt['display_text'] ?? $result['value'];
                }
            }

            return $result;
        }

        return false;
    }

    /**
     * Gets the ID from the item (course, session, etc) for which
     * the given field is defined with the given value.
     *
     * @param string $variable  Field (type of data) we want to check
     * @param string $value     Data we are looking for in the given field
     * @param bool   $transform Whether to transform the result to a human readable strings
     * @param bool   $last      Whether to return the last element or simply the first one we get
     * @param bool   $useLike
     *
     * @return mixed Give the ID if found, or false on failure or not found
     * @assert (-1,-1) === false
     */
    public function get_item_id_from_field_variable_and_field_value(
        $variable,
        $value,
        $transform = false,
        $last = false,
        $all = false,
        $useLike = false
    ) {
        $value = Database::escape_string($value);
        $variable = Database::escape_string($variable);

        $valueCondition = " value  = '$value' AND ";
        if ($useLike) {
            $valueCondition = " value LIKE '%".$value."%' AND ";
        }
        $extraFieldType = $this->getExtraField()->getExtraFieldType();

        $sql = "SELECT item_id FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf
                ON (s.field_id = sf.id)
                WHERE
                    $valueCondition
                    variable = '".$variable."' AND
                    sf.extra_field_type = $extraFieldType
                ORDER BY item_id
                ";

        if ($last) {
            // If we want the last element instead of the first
            // This is useful in special cases where there might
            // (erroneously) be more than one row for an item
            $sql .= ' DESC';
        }
        $result = Database::query($sql);
        if ($result !== false && Database::num_rows($result)) {
            if ($all) {
                $result = Database::store_result($result, 'ASSOC');
            } else {
                $result = Database::fetch_array($result, 'ASSOC');
            }

            return $result;
        }

        return false;
    }

    /**
     * Get all the values stored for one specific field.
     *
     * @param int $fieldId
     *
     * @return array|bool
     */
    public function getValuesByFieldId($fieldId)
    {
        $fieldId = (int) $fieldId;
        $extraFieldType = $this->getExtraField()->getExtraFieldType();

        $sql = "SELECT s.* FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf
                ON (s.field_id = sf.id)
                WHERE
                    field_id = $fieldId AND
                    sf.extra_field_type = $extraFieldType
                ORDER BY s.value";
        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            return Database::store_result($result, 'ASSOC');
        }

        return false;
    }

    /**
     * @param int $itemId
     * @param int $fieldId
     *
     * @return array
     */
    public function getAllValuesByItemAndField($itemId, $fieldId)
    {
        $fieldId = (int) $fieldId;
        $itemId = (int) $itemId;
        $extraFieldType = $this->getExtraField()->getExtraFieldType();

        $sql = "SELECT s.* FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf
                ON (s.field_id = sf.id)
                WHERE
                    field_id = $fieldId AND
                    item_id = $itemId AND
                    sf.extra_field_type = $extraFieldType
                ORDER BY s.value";
        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            return Database::store_result($result, 'ASSOC');
        }

        return false;
    }

    /**
     * @param int $itemId
     *
     * @return array
     */
    public function getAllValuesByItem($itemId)
    {
        $itemId = (int) $itemId;
        $extraFieldType = $this->getExtraField()->getExtraFieldType();

        $sql = "SELECT s.value, sf.variable, sf.field_type, sf.id, sf.display_text
                FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf
                ON (s.field_id = sf.id)
                WHERE
                    item_id = $itemId AND
                    sf.extra_field_type = $extraFieldType
                ORDER BY s.value";

        $result = Database::query($sql);
        $idList = [];
        if (Database::num_rows($result)) {
            $result = Database::store_result($result, 'ASSOC');
            $finalResult = [];
            foreach ($result as $item) {
                $finalResult[$item['id']] = $item;
            }
            $idList = array_column($result, 'id');
        }

        $em = Database::getManager();

        $extraField = new ExtraField($this->type);
        $allData = $extraField->get_all(['filter = ?' => 1]);
        $allResults = [];
        foreach ($allData as $field) {
            if (in_array($field['id'], $idList)) {
                $allResults[] = $finalResult[$field['id']];
            } else {
                if ($field['field_type'] == ExtraField::FIELD_TYPE_TAG) {
                    $tagResult = [];
                    $tags = $em->getRepository('ChamiloCoreBundle:ExtraFieldRelTag')
                        ->findBy(
                            [
                                'fieldId' => $field['id'],
                                'itemId' => $itemId,
                            ]
                        );
                    if ($tags) {
                        /** @var ExtraFieldRelTag $extraFieldTag */
                        foreach ($tags as $extraFieldTag) {
                            /** @var \Chamilo\CoreBundle\Entity\Tag $tag */
                            $tag = $em->find('ChamiloCoreBundle:Tag', $extraFieldTag->getTagId());
                            $tagResult[] = [
                                'id' => $extraFieldTag->getTagId(),
                                'value' => $tag->getTag(),
                            ];
                        }
                    }
                    $allResults[] = [
                        'value' => $tagResult,
                        'variable' => $field['variable'],
                        'field_type' => $field['field_type'],
                        'id' => $field['id'],
                    ];
                }
            }
        }

        return $allResults;
    }

    /**
     * @param int    $itemId
     * @param int    $fieldId
     * @param string $fieldValue
     *
     * @return array|bool
     */
    public function getAllValuesByItemAndFieldAndValue($itemId, $fieldId, $fieldValue)
    {
        $fieldId = (int) $fieldId;
        $itemId = (int) $itemId;
        $extraFieldType = $this->getExtraField()->getExtraFieldType();

        $fieldValue = Database::escape_string($fieldValue);
        $sql = "SELECT s.* FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf
                ON (s.field_id = sf.id)
                WHERE
                    field_id = $fieldId AND
                    item_id = $itemId AND
                    value = '$fieldValue' AND
                    sf.extra_field_type = $extraFieldType
                ORDER BY value";

        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return Database::store_result($result, 'ASSOC');
        }

        return false;
    }

    /**
     * Deletes all the values related to a specific field ID.
     *
     * @param int $field_id
     *
     * @assert ('a') == null
     */
    public function delete_all_values_by_field_id($field_id)
    {
        $field_id = (int) $field_id;
        $sql = "DELETE FROM {$this->table}
                WHERE
                    field_id = $field_id ";
        Database::query($sql);
    }

    /**
     * Deletes all values from an item.
     *
     * @param int $itemId (session id, course id, etc)
     * @assert (-1,-1) == null
     */
    public function deleteValuesByItem($itemId)
    {
        $itemId = (int) $itemId;
        $extraFieldType = $this->getExtraField()->getExtraFieldType();

        $sql = "DELETE FROM {$this->table}
                WHERE
                    item_id = $itemId AND
                    field_id IN (
                        SELECT id FROM {$this->table_handler_field}
                        WHERE extra_field_type = $extraFieldType
                    )
                ";
        Database::query($sql);
    }

    /**
     * @param int $itemId
     * @param int $fieldId
     * @param int $fieldValue
     *
     * @return bool
     */
    public function deleteValuesByHandlerAndFieldAndValue($itemId, $fieldId, $fieldValue)
    {
        $itemId = (int) $itemId;
        $fieldId = (int) $fieldId;

        $fieldData = $this->getExtraField()->get($fieldId);
        if ($fieldData) {
            $fieldValue = Database::escape_string($fieldValue);

            $sql = "DELETE FROM {$this->table}
                WHERE
                    item_id = $itemId AND
                    field_id = $fieldId AND
                    value = '$fieldValue'
                ";
            Database::query($sql);

            // Delete file from uploads
            if ($fieldData['field_type'] == ExtraField::FIELD_TYPE_FILE) {
                api_remove_uploaded_file($this->type, basename($fieldValue));
            }

            return true;
        }

        return false;
    }

    /**
     * Get all values for an item.
     *
     * @param int  $itemId          The item ID
     * @param bool $visibleToSelf   Get the visible extra field only
     * @param bool $visibleToOthers
     *
     * @return array
     */
    public function getAllValuesForAnItem($itemId, $visibleToSelf = null, $visibleToOthers = null)
    {
        $em = Database::getManager();
        $qb = $em->createQueryBuilder();
        $qb = $qb->select('fv')
            ->from('ChamiloCoreBundle:ExtraFieldValues', 'fv')
            ->join('fv.field', 'f')
            ->where(
                $qb->expr()->eq('fv.itemId', ':item')
            )
            ->andWhere(
                $qb->expr()->eq('f.extraFieldType', ':extra_field_type')
            );

        if (is_bool($visibleToSelf)) {
            $qb
                ->andWhere($qb->expr()->eq('f.visibleToSelf', ':visibleToSelf'))
                ->setParameter('visibleToSelf', $visibleToSelf);
        }

        if (is_bool($visibleToOthers)) {
            $qb
                ->andWhere($qb->expr()->eq('f.visibleToOthers', ':visibleToOthers'))
                ->setParameter('visibleToOthers', $visibleToOthers);
        }

        $fieldValues = $qb
            ->setParameter('item', $itemId)
            ->setParameter('extra_field_type', $this->getExtraField()->getExtraFieldType())
            ->getQuery()
            ->getResult();

        $fieldOptionsRepo = $em->getRepository('ChamiloCoreBundle:ExtraFieldOptions');

        $valueList = [];
        /** @var ExtraFieldValues $fieldValue */
        foreach ($fieldValues as $fieldValue) {
            $item = ['value' => $fieldValue];
            switch ($fieldValue->getField()->getFieldType()) {
                case ExtraField::FIELD_TYPE_SELECT:
                    $item['option'] = $fieldOptionsRepo->findOneBy([
                        'field' => $fieldValue->getField(),
                        'value' => $fieldValue->getValue(),
                    ]);
                    break;
            }
            $valueList[] = $item;
        }

        return $valueList;
    }

    public function copy($sourceId, $destinationId)
    {
        if (empty($sourceId) || empty($destinationId)) {
            return false;
        }

        $extraField = new ExtraField($this->type);
        $allFields = $extraField->get_all();
        $extraFieldValue = new ExtraFieldValue($this->type);
        foreach ($allFields as $field) {
            $variable = $field['variable'];
            $sourceValues = $extraFieldValue->get_values_by_handler_and_field_variable($sourceId, $variable);
            if (!empty($sourceValues) && isset($sourceValues['value']) && $sourceValues['value'] != '') {
                $params = [
                    'extra_'.$variable => $sourceValues['value'],
                    'item_id' => $destinationId,
                ];
                $extraFieldValue->saveFieldValues($params, true);
            }
        }

        return true;
    }
}
