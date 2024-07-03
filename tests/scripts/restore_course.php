<?php

/* For licensing terms, see /license.txt */

/**
 * Script to create an SQL dump file with the data to
 * re-create a course in a running database, based on a separate
 * running backup DB which this script must connect to.
 * You will need an installation of Chamilo to be (temporarily) pointing
 * at this old database (e.g. modifying the configuration.php for that).
 * This script is based on the normal basic structure of a Chamilo 1.11.
 * It only works at one level of depth at this point, so if you have a
 * resource that points to a resource that points to a course, this will
 * not be recovered. Only tables with a clear "c_id" field will be.
 *
 * In this first version, this script works by a destructive manner: it
 * will go through the database and remove everything unrelated to the course!
 * Make sure you don't run this on a production database or you will be left
 * with nothing but these course resources!!!
 *
 * Apart from this (partial) script, there is a better, more efficient way to
 * do a course restore currently, which doesn't evolve emptying a database.
 * It is described at https://beeznest.com/fr/2021/06/07/comment-recuperer-un-cours-specifique-dun-backup-de-chamilo/
 * and involves making a list of all tables that require a restore and
 * issuing a mysqldump command with the --where='c_id=10' argument, which will
 * only export whatever has a c_id of 10. There are some variations
 * (explained in the article) but this will result in something similar to what
 * this script currently does.
 */

exit;

require __DIR__.'/../../main/inc/global.inc.php';

if (PHP_SAPI != 'cli') {
    die('This script can only be executed from the command line');
}

$date = date('Y-m-d h:i:s');
echo "($date) Starting process".PHP_EOL;

// The CID is required. Locate it in your database in the "course" table
$cId = 88;
$cCode = 'G1Z001';

$tableDrops = [
    'access_url',
    'access_url_rel_course_category',
    'access_url_rel_session',
    'access_url_rel_user',
    'access_url_rel_usergroup',
    'admin',
    'announcement_rel_group',
    'block',
    'branch_sync',
    'branch_transaction',
    'branch_transaction_status',
    'career',
    'chat',
    'chat_video',
    'class_item',
    'class_user',
    'course_type',
    'event_email_template',
    'event_sent',
    'extra_field',
    'extra_field_option_rel_field_option',
    'extra_field_options',
    'extra_field_rel_tag',
    'extra_field_saved_search',
    'extra_field_values', //should be analyzed
    'fos_group',
    'fos_user_user_group',
    'grade_components',
    'grade_model',
    'gradebook_certificate',
    'gradebook_linkeval_log',
    'gradebook_result', //should be analyzed
    'gradebook_result_log',
    'gradebook_score_display',
    'gradebook_score_log',
    'hook_call',
    'hook_event',
    'hook_observer',
    'language',
    'legal',
    'message',
    'message_attachment',
    'notification',
    'openid_association',
    'personal_agenda',
    'personal_agenda_repeat',
    'personal_agenda_repeat_not',
    'promotion',
    'room',
    'sequence',
    'sequence_condition',
    'sequence_formula',
    'sequence_method',
    'sequence_resource',
    'sequence_rule',
    'sequence_rule_condition',
    'sequence_rule_method',
    'sequence_type_entity',
    'sequence_valid',
    'sequence_value',
    'sequence_variable',
    'session',
    'session_category',
    'session_rel_user',
    'settings_options',
    'shared_survey_question',
    'shared_survey_question_option',
    'skill',
    'skill_level',
    'skill_level_profile',
    'skill_profile',
    'skill_rel_gradebook',
    'skill_rel_profile',
    'skill_rel_skill',
    'skill_rel_user_comment',
    'specific_field',
    'sys_announcement',
    'sys_calendar',
    'system_template',
    'tag',
    'ticket_assigned_log',
    'ticket_category',
    'ticket_category_rel_user',
    'ticket_message',
    'ticket_message_attachments',
    'ticket_priority',
    'ticket_project',
    'ticket_status',
    'track_e_attempt_coeff',
    'track_e_attempt_recording',
    'track_e_login',
    'track_e_open',
    'user',
    'user_api_key',
    'user_course_category',
    'user_friend_relation_type',
    'user_rel_event_type',
    'user_rel_tag',
    'user_rel_user',
    'usergroup',
    'usergroup_rel_session',
    'usergroup_rel_user',
    'usergroup_rel_usergroup',
    'version',
];

// This is a list of all the tables that contain a c_id and that need to be restored
$tablesWithCid = [
    'access_url_rel_course' => [
        'c_id',
        'id',
        'access_url_id'
    ],
    'c_announcement' => [
        'c_id',
        'iid',
        'id',
        'title',
        'content',
        'end_date',
        'display_order',
        'email_sent',
        'session_id',
    ],
    'c_announcement_attachment' => [
        'c_id',
        'iid',
        'id',
        'path',
        'comment',
        'size',
        'accouncement_id',
        'filename',
    ],
    'c_attendance' => [
        'c_id',
        'iid',
        'id',
        'name',
        'description',
        'active',
        'attendance_qualify_title',
        'attendance_qualify_max',
        'attendance_weight',
        'session_id',
        'locked',
    ],
    'c_attendance_calendar' => [
        'c_id',
        'iid',
        'id',
        'attendance_id',
        'date_time',
        'done_attendance',
    ],
    'c_attendance_calendar_rel_group' => [
        'c_id',
        'iid',
        'id',
        'group_id',
        'calendar_id',
    ],
    'c_attendance_result' => [
        'c_id',
        'iid',
        'id',
        'user_id',
        'attendance_id',
        'score',
    ],
    'c_attendance_sheet' => [
        'c_id',
        'iid',
        'user_id',
        'presence',
        'attendance_calendar_id',
    ],
    'c_attendance_sheet_log' => [
        'c_id',
        'iid',
        'id',
        'attendance_id',
        'lastedit_date',
        'lastedit_type',
        'lastedit_user_id',
        'calendar_date_value',
    ],
    // todo: blog tables to be completed later
    'c_blog' => [

    ],
    'c_blog_attachment' => [

    ],
    'c_blog_comment' => [

    ],
    'c_blog_post' => [

    ],
    'c_blog_rating' => [

    ],
    'c_blog_rel_user' => [

    ],
    'c_blog_task' => [

    ],
    'c_blog_task_rel_user' => [

    ],
    'c_calendar_event' => [
        'c_id',
        'iid',
        'id',
        'room_id',
        'title',
        'content',
        'start_date',
        'end_date',
        'parent_event_id',
        'session_id',
        'all_day',
        'comment',
        'color',
    ],
    'c_calendar_event_attachment' => [
        'c_id',
        'iid',
        'id',
        'path',
        'comment',
        'size',
        'agenda_id',
        'filename',
    ],
    'c_calendar_event_repeat' => [
        'c_id',
        'iid',
        'cal_id',
        'cal_type',
        'cal_end',
        'cal_frequency',
        'cal_days',
    ],
    'c_calendar_event_repeat_not' => [
        'c_id',
        'iid',
        'cal_id',
        'cal_date',
    ],
    //todo 'c_chat_connected'
    'c_chat_connected' => [

    ],
    'c_course_description' => [
        'c_id',
        'iid',
        'id',
        'title',
        'content',
        'session_id',
        'description_type',
        'progress',
    ],
    'c_course_setting' => [
        'c_id',
        'iid',
        'id',
        'variable',
        'subkey',
        'type',
        'category',
        'value',
        'title',
        'comment',
        'subkeytext',
    ],
    'c_document' => [
        'c_id',
        'iid',
        'id',
        'path',
        'comment',
        'title',
        'filetype',
        'size',
        'readonly',
        'session_id',
    ],
    //todo 'c_dropbox_file'
    'c_dropbox_category' => [

    ],
    'c_dropbox_feedback' => [

    ],
    'c_dropbox_file' => [

    ],
    'c_dropbox_person' => [

    ],
    'c_dropbox_post' => [

    ],
    //todo 'c_forum'
    'c_forum_attachment' => [

    ],
    'c_forum_category' => [

    ],
    'c_forum_forum' => [

    ],
    'c_forum_mailcue' => [

    ],
    'c_forum_notification' => [

    ],
    'c_forum_post' => [

    ],
    'c_forum_thread' => [

    ],
    'c_forum_thread_qualify' => [

    ],
    'c_forum_thread_qualify_log' => [

    ],
    'c_glossary' => [
        'c_id',
        'iid',
        'glossary_id',
        'name',
        'description',
        'display_order',
        'session_id',
    ],
    'c_group_category' => [
        'c_id',
        'iid',
        'id',
        'title',
        'description',
        'doc_state',
        'calendar_state',
        'work_state',
        'announcements_state',
        'forum_state',
        'wiki_state',
        'chat_state',
        'max_student',
        'self_reg_allowed',
        'self_unreg_allowed',
        'groups_per_user',
        'display_order',
    ],
    'c_group_info' => [
        'c_id',
        'iid',
        'id',
        'name',
        'status',
        'category_id',
        'description',
        'max_student',
        'doc_state',
        'calendar_state',
        'work_state',
        'announcements_state',
        'forum_state',
        'wiki_state',
        'chat_state',
        'secret_directory',
        'self_registration_allowed',
        'self_unregistration_allowed',
        'session_id',
    ],
    'c_group_rel_tutor' => [
        'c_id',
        'iid',
        'id',
        'user_id',
        'group_id',
    ],
    'c_group_rel_user' => [
        'c_id',
        'iid',
        'id',
        'user_id',
        'group_id',
        'status',
        'role',
    ],
    'c_item_property' => [
        'c_id',
        'iid',
        'id',
        'to_group_id',
        'to_user_id',
        'insert_user_id',
        'session_id',
        'tool',
        'insert_date',
        'lastedit_date',
        'ref',
        'lastedit_type',
        'lastedit_user_id',
        'visibility',
        'start_visible',
        'end_visible',
    ],
    //todo continue
    'c_link' => [
        'c_id',
        'iid',
        'id',
        'url',
        'title',
        'description',
        'category_id',
        'display_order',
        'on_homepage',
        'target',
        'session_id',
    ],
    'c_link_category' => [
        'c_id',
        'iid',
        'id',
        'category_title',
        'description',
        'display_order',
        'session_id',
    ],
    'c_lp' => [
        'c_id',
        'iid',
        'id',
        'lp_type',
        'name',
        'ref',
        'description',
        'path',
        'force_commit',
        'default_view_mod',
        'default_encoding',
        'display_order',
        'content_maker',
        'content_local',
        'content_license',
        'prevent_reinit',
        'js_lib',
        'debug',
        'theme',
        'preview_image',
        'author',
        'session_id',
        'prerequisite',
        'hide_toc_frame',
        'seriousgame_mode',
        'use_max_score',
        'autolaunch',
        'category_id',
        'max_attempts',
        'subscribe_users',
        'created_on',
        'modified_on',
        'publicated_on',
        'expired_on',
        'accumulate_scorm_time',
    ],
    'c_lp_category' => [
        'c_id',
        'iid',
        'name',
        'position',
    ],
    /*
    'c_lp_category_user' => [

    ],
    */
    'c_lp_item' => [
        'c_id',
        'iid',
        'id',
        'lp_id',
        'item_type',
        'ref',
        'title',
        'description',
        'path',
        'min_score',
        'max_score',
        'mastery_score',
        'parent_item_id',
        'previous_item_id',
        'next_item_id',
        'display_order',
        'prerequisite',
        'parameters',
        'launch_data',
        'max_time_allowed',
        'terms',
        'search_did',
        'audio',
        'prerequisite_min_score',
        'prerequisite_max_score',
    ],
    'c_lp_item_view' => [
        'c_id',
        'iid',
        'id',
        'lp_item_id',
        'lp_view_id',
        'view_count',
        'start_time',
        'total_time',
        'score',
        'status',
        'suspend_data',
        'lesson_location',
        'core_exit',
        'max_score',
    ],
    'c_lp_iv_interaction' => [
        'c_id',
        'iid',
        'id',
        'order_id',
        'lp_iv_id',
        'interaction_id',
        'interaction_type',
        'weighting',
        'completion_time',
        'correct_responses',
        'student_response',
        'result',
        'latency',
    ],
    'c_lp_iv_objective' => [
        'c_id',
        'iid',
        'id',
        'lp_iv_id',
        'order_id',
        'objective_id',
        'score_raw',
        'score_max',
        'score_min',
        'status',
    ],
    'c_lp_view' => [
        'c_id',
        'iid',
        'id',
        'lp_id',
        'user_id',
        'view_count',
        'last_item',
        'progress',
        'session_id',
    ],
    'c_notebook' => [

    ],
    'c_online_connected' => [

    ],
    'c_online_link' => [

    ],
    'c_permission_group' => [

    ],
    'c_permission_task' => [

    ],
    'c_permission_user' => [

    ],
    'c_quiz' => [

    ],
    'c_quiz_answer' => [

    ],
    'c_quiz_question' => [

    ],
    'c_quiz_question_category' => [

    ],
    'c_quiz_question_option' => [

    ],
    'c_quiz_question_rel_category' => [

    ],
    'c_quiz_rel_category' => [

    ],
    'c_quiz_rel_question' => [

    ],
    'c_resource' => [

    ],
    'c_role' => [

    ],
    'c_role_group' => [

    ],
    'c_role_permissions' => [

    ],
    'c_role_user' => [

    ],
    'c_student_publication' => [

    ],
    'c_student_publication_assignment' => [

    ],
    'c_student_publication_comment' => [

    ],
    'c_student_publication_rel_document' => [

    ],
    'c_student_publication_rel_user' => [

    ],
    'c_survey' => [

    ],
    'c_survey_answer' => [

    ],
    'c_survey_group' => [

    ],
    'c_survey_invitation' => [

    ],
    'c_survey_question' => [

    ],
    'c_survey_question_option' => [

    ],
    'c_thematic' => [

    ],
    'c_thematic_advance' => [

    ],
    'c_thematic_plan' => [

    ],
    'c_tool' => [

    ],
    'c_tool_intro' => [

    ],
    'c_userinfo_content' => [

    ],
    'c_userinfo_def' => [

    ],
    'c_wiki' => [

    ],
    'c_wiki_conf' => [

    ],
    'c_wiki_discuss' => [

    ],
    'c_wiki_mailcue' => [

    ],
];

$tablesSpecialWithId = [
    'course' => [
        'id',
    ],
    'course_rel_user' => [
        'c_id',
    ],
    'course_rel_user_catalogue' => [
        'c_id',
    ],
    /*
    'scheduled_announcements' => [
        'c_id',
    ],
    */
    'sequence_row_entity' => [
        'c_id',
    ],
    'session_rel_course' => [
        'c_id',
    ],
    'session_rel_course_rel_user' => [
        'c_id',
    ],
    'skill_rel_user' => [
        'course_id',
    ],
    'ticket_ticket' => [
        'course_id',
    ],
    'track_course_ranking' => [
        'c_id',
    ],
    'track_e_access' => [
        'c_id',
    ],
    'track_e_attempt' => [
        'c_id',
    ],
    'track_e_course_access' => [
        'c_id',
    ],
    'track_e_default' => [
        'c_id',
    ],
    'track_e_downloads' => [
        'c_id',
    ],
    'track_e_exercises' => [
        'c_id',
    ],
    'track_e_hotpotatoes' => [
        'c_id',
    ],
    'track_e_hotspot' => [
        'c_id',
    ],
    'track_e_item_property' => [
        'course_id',
    ],
    'track_e_lastaccess' => [
        'c_id',
    ],
    'track_e_links' => [
        'c_id',
    ],
    'track_e_online' => [
        'c_id',
    ],
    'track_e_uploads' => [
        'c_id',
    ],
    'track_stored_values' => [
        'course_id',
    ],
    'track_stored_values_stack' => [
        'course_id',
    ],
    'user_rel_course_vote' => [
        'c_id',
    ],
    'usergroup_rel_course' => [
        'course_id',
    ],
    'usergroup_rel_question' => [
        'c_id',
    ],
];
$tablesSpecialWithCode = [
    'course_rel_class' => [
        'course_code',
    ],
    'course_request' => [
        'code',
    ],
    'gradebook_category' => [
        'course_code',
    ],
    'gradebook_evaluation' => [
        'course_code',
    ],
    'gradebook_link' => [
        'course_code',
    ],
    'search_engine_ref' => [
        'course_code',
    ],
    'shared_survey' => [
        'course_code',
    ],
    'specific_field_values' => [
        'course_code',
    ],
    'templates' => [
        'course_code',
    ],

];

//$currentUserId = api_get_user_id();
Database::query('SET foreign_key_checks = 0');

// Drop non-course tables
foreach ($tableDrops as $table) {
    $sql = "DROP TABLE IF EXISTS $table";
    echo $sql.PHP_EOL;
    $res = Database::query($sql);
}

$date = date('Y-m-d h:i:s');
echo "($date) Done dropping non-course tables".PHP_EOL;

// Clean course tables with c_id
foreach ($tablesWithCid as $table => $fields) {
    $sql = "DELETE FROM $table WHERE c_id != $cId";
    echo $sql.PHP_EOL;
    $res = Database::query($sql);
}

$date = date('Y-m-d h:i:s');
echo "($date) Done cleaning course tables".PHP_EOL;

// Clean general tables with specific course ID fields
foreach ($tablesSpecialWithId as $table => $fields) {
    $field = $fields[0];
    $sql = "DELETE FROM $table WHERE $field != $cId";
    echo $sql.PHP_EOL;
    $res = Database::query($sql);
}

// Clean other tables with a field based on the course code
foreach ($tablesSpecialWithCode as $table => $fields) {
    $field = $fields[0];
    $sql = "DELETE FROM $table WHERE $field != '$cCode'";
    echo $sql.PHP_EOL;
    $res = Database::query($sql);
}

$date = date('Y-m-d h:i:s');
echo "($date) Done cleaning extra tables".PHP_EOL;

// Finalize dropping settings_current (we avoid dropping it earlier as it
// makes things complicated if there are errors
Database::query('DROP TABLE IF EXISTS settings_current');

echo "Your database is now emptied of other resources. Dump it to restore in production.".PHP_EOL;
echo "e.g. mysqldump -u user -p database --skip-add-drop-table --no-create-info --insert-ignore > restore.sql".PHP_EOL;
