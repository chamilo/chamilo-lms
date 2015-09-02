<?php
/* For license terms, see /license.txt */
/**
 * Install the Sessions Block Slider plugin
 * @package chamilo.plugin.sessions_slider_block
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
//require_once __DIR__ . '/../../main/inc/global.inc.php';

$plugin = SessionsSliderBlockPlugin::create();

$showSlider = $plugin->get(SessionsSliderBlockPlugin::CONFIG_SHOW_SLIDER) === 'true';

if ($showSlider) {
    $sessions = [];

    $sessionList = $plugin->getSessionList();

    foreach ($sessionList as $session) {
        $extraFieldValue = new ExtraFieldValue('session');
        $imageInfo = $extraFieldValue->get_values_by_handler_and_field_variable(
            $session['id'],
            'image'
        );

        $courses = SessionManager::get_course_list_by_session_id($session['id']);
        $course = current($courses);

        $description = '';
        $level = '';

        if (!empty($course)) {
            $courseDescription = new CourseDescription();
            $descriptionData = $courseDescription->get_data_by_description_type(1, $course['id'], $session['id']);
            if (empty($descriptionData)){
                $descriptionData = $courseDescription->get_data_by_description_type(1, $course['id']);
            }

            if (isset($descriptionData['description_content'])) {
                $description = $descriptionData['description_content'];
            }
        }

        $extraFieldValue = new ExtraFieldValue('course');
        $fieldValueData = $extraFieldValue->get_values_by_handler_and_field_variable(
            $course['id'],
            SessionsSliderBlockPlugin::FIELD_VARIABLE_COURSE_LEVEL,
            true
        );

        if (!empty($fieldValueData)) {
            $level = $fieldValueData['value'];
            if (get_plugin_lang($level, 'SessionsSliderBlockPlugin') !== $level) {
                $level = get_plugin_lang($level, 'SessionsSliderBlockPlugin');
            }
        }

        $sessions[] = [
            'name' => $session['name'],
            'url' => api_get_path(WEB_PATH) . "session/{$session['id']}/about/",
            'image_in_slider' => $imageInfo['value'],
            'course_description' => $description,
            'course_level' => $level
        ];
    }

    $_template['sessions'] = [];

    if (count($sessions) > 0) {
        $_template['sessions'] = $sessions;
    }
}
