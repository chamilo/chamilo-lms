<?php

/* For licensing terms, see /license.txt */

class ExerciseSignaturePlugin extends Plugin
{
    private const FIELD_SIGNATURE_ACTIVATED = 'signature_activated';
    private const FIELD_SIGNATURE_MANDATORY = 'signature_mandatory';
    private const FIELD_SIGNATURE = 'signature';

    private const HANDLER_EXERCISE = 'exercise';
    private const HANDLER_TRACK_EXERCISE = 'track_exercise';

    private const VALUE_TYPE_CHECKBOX = 13;
    private const VALUE_TYPE_TEXTAREA = 2;

    private const MAX_SIGNATURE_SIZE = 2000000;

    public function __construct()
    {
        parent::__construct(
            '0.1',
            'Julio Montoya',
            []
        );

        $this->isAdminPlugin = true;
    }

    /**
     * @return ExerciseSignaturePlugin
     */
    public static function create()
    {
        static $instance = null;

        return $instance ? $instance : $instance = new self();
    }

    public static function exerciseHasSignatureActivated(Exercise $exercise)
    {
        if (!self::isPluginEnabled()) {
            return false;
        }

        $exerciseId = self::getExerciseId($exercise);
        if ($exerciseId <= 0) {
            return false;
        }

        return self::getBooleanExtraFieldValue(
            self::HANDLER_EXERCISE,
            $exerciseId,
            self::FIELD_SIGNATURE_ACTIVATED
        );
    }

    public static function exerciseHasSignatureMandatory(Exercise $exercise)
    {
        if (!self::isPluginEnabled()) {
            return false;
        }

        $exerciseId = self::getExerciseId($exercise);
        if ($exerciseId <= 0) {
            return false;
        }

        return self::getBooleanExtraFieldValue(
            self::HANDLER_EXERCISE,
            $exerciseId,
            self::FIELD_SIGNATURE_MANDATORY
        );
    }

    public static function saveSignature($userId, $trackInfo, $file)
    {
        $result = self::saveSignatureWithReason($userId, $trackInfo, $file);

        return !empty($result['success']);
    }

    public static function saveSignatureWithReason($userId, $trackInfo, $file)
    {
        if (!self::isPluginEnabled()) {
            return self::getSaveResult(false, 'Exercise signature is not available.');
        }

        if (!is_array($trackInfo) || empty($trackInfo['exe_id'])) {
            return self::getSaveResult(false, 'Exercise attempt was not found.');
        }

        $exeId = (int) $trackInfo['exe_id'];
        $exerciseId = isset($trackInfo['exe_exo_id']) ? (int) $trackInfo['exe_exo_id'] : 0;
        $attemptUserId = isset($trackInfo['exe_user_id']) ? (int) $trackInfo['exe_user_id'] : 0;
        $userId = (int) $userId;

        if ($exeId <= 0) {
            return self::getSaveResult(false, 'Missing exercise attempt id.');
        }

        if ($attemptUserId <= 0 || $userId !== $attemptUserId) {
            return self::getSaveResult(false, 'You are not allowed to sign this attempt.');
        }

        if (!empty($trackInfo['status']) && 'incomplete' === $trackInfo['status']) {
            return self::getSaveResult(false, 'Incomplete attempts cannot be signed.');
        }

        if ($exerciseId <= 0 || !self::isSignatureActivatedForExercise($exerciseId)) {
            return self::getSaveResult(false, 'Signature is not activated for this exercise.');
        }

        $currentSignature = self::getSignatureValueDirectly($exeId);
        if (self::isSignatureDataUrl($currentSignature)) {
            return self::getSaveResult(false, 'This attempt is already signed.');
        }

        $file = self::normalizeSignatureFile($file);
        if ('' === $file) {
            return self::getSaveResult(false, 'Missing signature image.');
        }

        if (!self::saveSignatureValueDirectly($exeId, $file)) {
            return self::getSaveResult(false, 'The signature could not be saved.');
        }

        $savedSignature = self::getSignatureValueDirectly($exeId);
        if (!self::isSignatureDataUrl($savedSignature)) {
            return self::getSaveResult(false, 'The signature was written but could not be read back.');
        }

        return self::getSaveResult(true, 'Saved.');
    }

    public static function validateSignatureAccess($userId, $trackInfo)
    {
        $userId = (int) $userId;

        if (
            empty($trackInfo['exe_id'])
            || empty($trackInfo['exe_user_id'])
            || empty($trackInfo['status'])
        ) {
            return false;
        }

        if ('incomplete' === $trackInfo['status']) {
            return false;
        }

        return $userId === (int) $trackInfo['exe_user_id'];
    }

    public static function getSignature($userId, $trackInfo)
    {
        if (false === self::validateSignatureAccess($userId, $trackInfo)) {
            return false;
        }

        if (empty($trackInfo['exe_id'])) {
            return false;
        }

        $exeId = (int) $trackInfo['exe_id'];
        $directValue = self::getSignatureValueDirectly($exeId);

        if (self::isSignatureDataUrl($directValue)) {
            return $directValue;
        }

        $extraFieldValue = new ExtraFieldValue(self::HANDLER_TRACK_EXERCISE);
        $result = $extraFieldValue->get_values_by_handler_and_field_variable($exeId, self::FIELD_SIGNATURE);
        $value = self::extractExtraFieldValue($result);

        return self::isSignatureDataUrl($value) ? $value : false;
    }

    /**
     * Creates this plugin's related extra fields.
     */
    public function install()
    {
        $this->createExtraField(
            self::HANDLER_EXERCISE,
            self::FIELD_SIGNATURE_ACTIVATED,
            get_plugin_lang('SignatureActivated', 'ExerciseSignaturePlugin'),
            self::VALUE_TYPE_CHECKBOX,
            '0',
            true
        );

        $this->createExtraField(
            self::HANDLER_EXERCISE,
            self::FIELD_SIGNATURE_MANDATORY,
            get_plugin_lang('SignatureMandatory', 'ExerciseSignaturePlugin'),
            self::VALUE_TYPE_CHECKBOX,
            '0',
            true
        );

        $this->createExtraField(
            self::HANDLER_TRACK_EXERCISE,
            self::FIELD_SIGNATURE,
            get_plugin_lang('Signature', 'ExerciseSignaturePlugin'),
            self::VALUE_TYPE_TEXTAREA,
            '',
            false
        );

        $this->ensureSignatureStorageCanHoldDataUrl();
    }

    /**
     * Removes this plugin's extra fields.
     */
    public function uninstall()
    {
        $this->deleteExtraField(self::HANDLER_EXERCISE, self::FIELD_SIGNATURE_ACTIVATED);
        $this->deleteExtraField(self::HANDLER_EXERCISE, self::FIELD_SIGNATURE_MANDATORY);
        $this->deleteExtraField(self::HANDLER_TRACK_EXERCISE, self::FIELD_SIGNATURE);
    }

    private static function isPluginEnabled()
    {
        try {
            return self::create()->isEnabled();
        } catch (Throwable $exception) {
            error_log('[ExerciseSignature] Failed to check plugin status: '.$exception->getMessage());

            return false;
        }
    }

    private static function getExerciseId(Exercise $exercise)
    {
        if (method_exists($exercise, 'getId')) {
            $exerciseId = (int) $exercise->getId();
            if ($exerciseId > 0) {
                return $exerciseId;
            }
        }

        if (property_exists($exercise, 'iId')) {
            return (int) $exercise->iId;
        }

        return 0;
    }

    private static function getBooleanExtraFieldValue($handler, $itemId, $variable)
    {
        $value = self::getExtraFieldValueDirectly($handler, (int) $itemId, $variable);

        if (null === $value) {
            $extraFieldValue = new ExtraFieldValue($handler);
            $result = $extraFieldValue->get_values_by_handler_and_field_variable((int) $itemId, $variable);
            $value = self::extractExtraFieldValue($result);
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    private static function extractExtraFieldValue($result)
    {
        if (empty($result) || !is_array($result)) {
            return '';
        }

        if (array_key_exists('field_value', $result)) {
            return (string) $result['field_value'];
        }

        if (array_key_exists('value', $result)) {
            return (string) $result['value'];
        }

        return '';
    }

    private static function attemptBelongsToSignatureExercise($trackInfo)
    {
        if (empty($trackInfo['exe_exo_id'])) {
            return false;
        }

        return self::isSignatureActivatedForExercise((int) $trackInfo['exe_exo_id']);
    }

    private static function isSignatureActivatedForExercise($exerciseId)
    {
        $exerciseId = (int) $exerciseId;
        if ($exerciseId <= 0) {
            return false;
        }

        return self::getBooleanExtraFieldValue(
            self::HANDLER_EXERCISE,
            $exerciseId,
            self::FIELD_SIGNATURE_ACTIVATED
        );
    }

    private static function getSaveResult($success, $message)
    {
        return [
            'success' => (bool) $success,
            'message' => (string) $message,
        ];
    }

    private static function normalizeSignatureFile($file)
    {
        if (!is_string($file)) {
            return '';
        }

        $file = trim(str_replace(' ', '+', $file));
        if ('' === $file || strlen($file) > self::MAX_SIGNATURE_SIZE) {
            return '';
        }

        return self::isSignatureDataUrl($file) ? $file : '';
    }

    private static function isSignatureDataUrl($file)
    {
        if (!is_string($file)) {
            return false;
        }

        $file = trim(str_replace(' ', '+', $file));
        if ('' === $file || strlen($file) > self::MAX_SIGNATURE_SIZE) {
            return false;
        }

        if (!preg_match('#^data:image/(png|jpeg|jpg);base64,#i', $file)) {
            return false;
        }

        $base64 = preg_replace('#^data:image/(png|jpeg|jpg);base64,#i', '', $file);
        if (null === $base64 || '' === $base64) {
            return false;
        }

        $decoded = base64_decode($base64, true);

        return false !== $decoded && '' !== $decoded;
    }

    private static function saveSignatureValueDirectly($exeId, $file)
    {
        $fieldId = self::getExtraFieldId(self::HANDLER_TRACK_EXERCISE, self::FIELD_SIGNATURE);
        if ($fieldId <= 0) {
            return false;
        }

        $columns = self::getExtraFieldValueColumns();
        if (empty($columns['field_value'])) {
            return false;
        }

        $table = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $exeId = (int) $exeId;
        $file = Database::escape_string($file);
        $now = Database::escape_string(api_get_utc_datetime());

        $sql = "SELECT id FROM $table WHERE field_id = $fieldId AND item_id = $exeId ORDER BY id DESC LIMIT 1";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        if (!empty($row['id'])) {
            $assignments = ["field_value = '$file'"];

            if (!empty($columns['updated_at'])) {
                $assignments[] = "updated_at = '$now'";
            }

            $id = (int) $row['id'];
            Database::query("UPDATE $table SET ".implode(', ', $assignments)." WHERE id = $id");

            return self::isSignatureDataUrl(self::getSignatureValueDirectly($exeId));
        }

        $insertValues = [
            'field_id' => $fieldId,
            'item_id' => $exeId,
            'field_value' => $file,
        ];

        if (!empty($columns['created_at'])) {
            $insertValues['created_at'] = $now;
        }

        if (!empty($columns['updated_at'])) {
            $insertValues['updated_at'] = $now;
        }

        foreach ($columns as $columnName => $columnInfo) {
            if (isset($insertValues[$columnName])) {
                continue;
            }

            if (self::isAutoIncrementColumn($columnInfo)) {
                continue;
            }

            if (self::columnAllowsMissingValue($columnInfo)) {
                continue;
            }

            $insertValues[$columnName] = self::getDefaultValueForRequiredColumn($columnInfo);
        }

        $insertColumns = [];
        $insertSqlValues = [];

        foreach ($insertValues as $columnName => $value) {
            $insertColumns[] = $columnName;
            $insertSqlValues[] = self::formatSqlValue($value, $columns[$columnName] ?? []);
        }

        Database::query(
            "INSERT INTO $table (".implode(', ', $insertColumns).")
             VALUES (".implode(', ', $insertSqlValues).")"
        );

        return self::isSignatureDataUrl(self::getSignatureValueDirectly($exeId));
    }

    private static function getSignatureValueDirectly($exeId)
    {
        $value = self::getExtraFieldValueDirectly(self::HANDLER_TRACK_EXERCISE, (int) $exeId, self::FIELD_SIGNATURE);

        return null === $value ? '' : (string) $value;
    }

    private static function getExtraFieldValueDirectly($handler, $itemId, $variable)
    {
        $fieldId = self::getExtraFieldId($handler, $variable);
        if ($fieldId <= 0 || $itemId <= 0) {
            return null;
        }

        $columns = self::getExtraFieldValueColumns();
        $selectParts = [];

        if (!empty($columns['field_value'])) {
            $selectParts[] = 'field_value';
        }
        if (!empty($columns['value'])) {
            $selectParts[] = 'value';
        }

        if (empty($selectParts)) {
            return null;
        }

        $table = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $itemId = (int) $itemId;
        $sql = "SELECT ".implode(', ', $selectParts)." FROM $table WHERE field_id = $fieldId AND item_id = $itemId ORDER BY id DESC LIMIT 1";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        if (empty($row)) {
            return null;
        }

        if (array_key_exists('field_value', $row) && '' !== (string) $row['field_value']) {
            return (string) $row['field_value'];
        }

        if (array_key_exists('value', $row)) {
            return (string) $row['value'];
        }

        return null;
    }

    private static function getExtraFieldValueColumns()
    {
        static $columns = null;

        if (null !== $columns) {
            return $columns;
        }

        $columns = [];
        $table = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $result = Database::query("SHOW COLUMNS FROM $table");

        while ($row = Database::fetch_assoc($result)) {
            if (!empty($row['Field'])) {
                $columns[$row['Field']] = $row;
            }
        }

        return $columns;
    }

    private static function isAutoIncrementColumn($columnInfo)
    {
        return isset($columnInfo['Extra']) && false !== stripos((string) $columnInfo['Extra'], 'auto_increment');
    }

    private static function columnAllowsMissingValue($columnInfo)
    {
        if (!isset($columnInfo['Null']) || 'YES' === strtoupper((string) $columnInfo['Null'])) {
            return true;
        }

        if (array_key_exists('Default', $columnInfo) && null !== $columnInfo['Default']) {
            return true;
        }

        return false;
    }

    private static function getDefaultValueForRequiredColumn($columnInfo)
    {
        $type = isset($columnInfo['Type']) ? strtolower((string) $columnInfo['Type']) : '';

        if (false !== strpos($type, 'int') || false !== strpos($type, 'decimal') || false !== strpos($type, 'float') || false !== strpos($type, 'double')) {
            return 0;
        }

        if (false !== strpos($type, 'date') || false !== strpos($type, 'time')) {
            return api_get_utc_datetime();
        }

        return '';
    }

    private static function formatSqlValue($value, $columnInfo)
    {
        if (null === $value) {
            return 'NULL';
        }

        $type = isset($columnInfo['Type']) ? strtolower((string) $columnInfo['Type']) : '';

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_numeric($value) && (false !== strpos($type, 'int') || false !== strpos($type, 'decimal') || false !== strpos($type, 'float') || false !== strpos($type, 'double'))) {
            return (string) $value;
        }

        return "'".Database::escape_string((string) $value)."'";
    }

    private static function getExtraFieldId($handler, $variable)
    {
        $extraField = new ExtraField($handler);
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable($variable);

        if (empty($fieldInfo['id'])) {
            return 0;
        }

        return (int) $fieldInfo['id'];
    }

    private function ensureSignatureStorageCanHoldDataUrl()
    {
        $table = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $sql = "ALTER TABLE $table MODIFY COLUMN field_value LONGTEXT NULL";
        Database::query($sql);
    }

    private function createExtraField($handler, $variable, $displayText, $valueType, $defaultValue, $visibleToSelf)
    {
        $extraField = new ExtraField($handler);
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable($variable);

        if (false !== $fieldInfo) {
            return;
        }

        $extraField->save(
            [
                'field_type' => (int) $valueType,
                'value_type' => (int) $valueType,
                'variable' => $variable,
                'display_text' => $displayText,
                'default_value' => $defaultValue,
                'field_order' => 0,
                'visible_to_self' => $visibleToSelf ? 1 : 0,
                'changeable' => 1,
                'filter' => 0,
                'visible' => 1,
            ]
        );
    }

    private function deleteExtraField($handler, $variable)
    {
        $extraField = new ExtraField($handler);
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable($variable);

        if (!empty($fieldInfo['id'])) {
            $extraField->delete((int) $fieldInfo['id']);
        }
    }
}
