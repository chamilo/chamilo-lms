<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.plugin.ticket
 */
/**
 * Init section
 */
$language_file = array('registration');
require_once '../config.php';
$plugin = TicketPlugin::create();

$user_id = intval($_GET['user_id']);
$user_info = api_get_user_info($user_id);
$courses_list = CourseManager::get_courses_list_by_user_id($user_id, false, true);
?>
<div class="row">
    <div class="label2"><?php echo get_lang('User') ?>:</div>
    <div class="formw2" id="user_request"><?php echo $user_info['firstname'] . " " . $user_info['lastname']; ?></div>
</div>
<div class="row" id="divCourse">
    <div class="label2"><?php echo get_lang('Course') ?>:</div>
    <div class="formw2" id="courseuser">
        <select  class="chzn-select" name = "course_id" id="course_id"  style="width:95%;">
            <option value="0">---<?php echo get_lang('Select') ?>---</option>
            <?php
            foreach ($courses_list as $key => $course) {
                $courseinfo = CourseManager::get_course_information($course['code']);
                echo '<option value="' . $courseinfo['code'] . '"> ' . $courseinfo['title'] . '</option>';
            }
            ?>
        </select>
    </div>
</div>
