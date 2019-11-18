<?php
/* For licensing terms, see /license.txt */

$plugin = MigrationMoodlePlugin::create();

try {
    UserManager::create_extra_field(
        'moodle_password',
        ExtraField::FIELD_TYPE_TEXT,
        $this->get_lang('MoodlePassword'),
        ''
    );
} catch (Exception $exception) {
    $message = sprintf(
        $plugin->get_lang('InstallError'),
        $exception->getMessage()
    );

    echo Display::return_message($message, 'error');
}
