<?php

/* For licensing terms, see /license.txt */

class ExerciseSignaturePlugin extends Plugin
{
    public function __construct()
    {
        parent::__construct(
            '0.1',
            'Julio Montoya',
            [
                'tool_enable' => 'boolean',
            ]
        );
        $this->isAdminPlugin = true;
    }

    /**
     * @return $this
     */
    public static function create()
    {
        static $instance = null;

        return $instance ? $instance : $instance = new self();
    }

    public static function exerciseHasSignatureActivated(Exercise $exercise)
    {
        if (empty($exercise->iid)) {
            return false;
        }

        if ('true' === api_get_plugin_setting('exercise_signature', 'tool_enable')) {
            $extraFieldValue = new ExtraFieldValue('exercise');
            $result = $extraFieldValue->get_values_by_handler_and_field_variable($exercise->iid, 'signature_activated');
            if ($result && isset($result['value']) && 1 === (int) $result['value']) {
                return true;
            }
        }

        return false;
    }

    public static function saveSignature($userId, $trackInfo, $file)
    {
        if (false === self::validateSignatureAccess($userId, $trackInfo)) {
            return false;
        }

        $signature = self::getSignature($userId, $trackInfo);
        if (false !== $signature) {
            return false;
        }

        if (empty($file)) {
            return false;
        }

        $params = [
            'item_id' => $trackInfo['exe_id'],
            'extra_signature' => $file,
        ];
        $extraFieldValue = new ExtraFieldValue('track_exercise');
        $extraFieldValue->saveFieldValues(
            $params,
            true
        );

        $signature = self::getSignature($userId, $trackInfo);
        if (false !== $signature) {
            return true;
        }

        return true;
    }

    public static function validateSignatureAccess($userId, $trackInfo)
    {
        $userId = (int) $userId;
        if (isset($trackInfo['exe_id']) && isset($trackInfo['exe_user_id']) &&
            !empty($trackInfo['exe_id']) && !empty($trackInfo['exe_user_id']) &&
            $trackInfo['status'] !== 'incomplete'
        ) {
            if ($userId === (int) $trackInfo['exe_user_id']) {
                return true;
            }
        }

        return false;
    }

    public static function getSignature($userId, $trackInfo)
    {
        if (false === self::validateSignatureAccess($userId, $trackInfo)) {
            return false;
        }

        $extraFieldValue = new ExtraFieldValue('track_exercise');
        $result = $extraFieldValue->get_values_by_handler_and_field_variable($trackInfo['exe_id'], 'signature');

        if ($result && isset($result['value']) && !empty($result['value'])) {
            return $result['value'];
        }

        return false;
    }

    /**
     * Get the plugin Name.
     *
     * @return string
     */
    public function get_name()
    {
        return 'exercise_signature';
    }

    /**
     * Creates this plugin's related tables in the internal database.
     * Installs course fields in all courses.
     */
    public function install()
    {
        $extraField = new ExtraField('exercise');
        $extraFieldHandler = $extraField->get_handler_field_info_by_field_variable('signature_activated');
        $exists = $extraFieldHandler !== false;

        if (!$exists) {
            $extraField->save(
                [
                    'field_type' => 13, // checkbox yes/no
                    'variable' => 'signature_activated',
                    'display_text' => get_plugin_lang('SignatureActivated', 'ExerciseSignaturePlugin'),
                    'default_value' => null,
                    'field_order' => null,
                    'visible_to_self' => 1,
                    'changeable' => 1,
                    'filter' => null,
                ]
            );
        }

        $extraFieldHandler = $extraField->get_handler_field_info_by_field_variable('signature_mandatory');
        $exists = $extraFieldHandler !== false;

        if (!$exists) {
            $extraField->save(
                [
                    'field_type' => 13, // checkbox yes/no
                    'variable' => 'signature_mandatory',
                    'display_text' => get_plugin_lang('SignatureMandatory', 'ExerciseSignaturePlugin'),
                    'default_value' => null,
                    'field_order' => null,
                    'visible_to_self' => 1,
                    'changeable' => 1,
                    'filter' => null,
                ]
            );
        }

        $extraField = new ExtraField('track_exercise');
        $extraFieldHandler = $extraField->get_handler_field_info_by_field_variable('signature');
        $exists = $extraFieldHandler !== false;

        if (!$exists) {
            $extraField->save(
                [
                    'field_type' => 2, // textarea
                    'variable' => 'signature',
                    'display_text' => get_plugin_lang('Signature', 'ExerciseSignaturePlugin'),
                    'default_value' => null,
                    'field_order' => null,
                    'visible_to_self' => 1,
                    'changeable' => 1,
                    'filter' => null,
                ]
            );
        }

        $table = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $sql = "ALTER TABLE $table MODIFY COLUMN value LONGTEXT null;";
        Database::query($sql);
    }

    /**
     * Drops this plugins' related tables from the internal database.
     * Uninstalls course fields in all courses().
     */
    public function uninstall()
    {
        $extraField = new ExtraField('track_exercise');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable('signature_activated');

        if ($fieldInfo) {
            $extraField->delete($fieldInfo['id']);
        }

        $extraField = new ExtraField('exercise');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable('signature');
        if ($fieldInfo) {
            $extraField->delete($fieldInfo['id']);
        }
    }
}
