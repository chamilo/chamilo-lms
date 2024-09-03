<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

if (!api_get_configuration_value('show_missing_signatures_page') || !api_get_configuration_value('enable_sign_attendance_sheet')) {
    api_not_allowed(true);
}

api_block_anonymous_users();

$htmlHeadXtra[] = api_get_asset('signature_pad/signature_pad.umd.js');
$htmlHeadXtra[] = '<style>
    #search-user {
      background-image: url("/main/img/icons/22/sn-search.png");
      background-position: 10px 12px;
      background-repeat: no-repeat;
      width: 100%;
      font-size: 16px;
      padding: 12px 20px 12px 40px;
      border: 1px solid #ddd;
      margin: 12px 0px;
    }
    #main_content ul {
        list-style-type: none;
        height : 180px;
    }
    #main_content li {
        float : left;
        position : relative;
        width : 190px;
        height : 150px;
        border-radius : 10px;
        border : 1px solid gray;
        background-color : #F9E79F;
        margin : 10px;
        padding : 10px;
    }
    #main_content li a{
        position : absolute;
        bottom : 10px;
        left : 10%;
        width : 80%;
    }
</style>';

$userId = api_get_user_id();
//$courses = CourseManager::get_courses_list_by_user_id($userId, true);

$tbl_attendance_sheet = Database::get_course_table(TABLE_ATTENDANCE_SHEET);
$tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

//$sql = "select * from c_attendance_sheet where user_id = ". $userId . " and presence = 1 and signature IS NULL order by c_id";
$sql = "SELECT cal.c_id as courseId, course.title as courseTitle, cal.date_time as date_time, cal.iid as calendarId
        FROM $tbl_attendance_sheet att
        INNER JOIN $tbl_attendance_calendar cal
        ON cal.id = att.attendance_calendar_id
        INNER JOIN $tbl_course course
        ON cal.c_id = course.id
        WHERE
            att.presence = 1 AND
            att.signature IS NULL AND
            att.user_id = '$userId'
            ORDER BY cal.c_id";

$result = Database::query($sql);
$calendars = Database::store_result($result);
$presences = [];
foreach ($calendars as $calendar) {
    $presences[$calendar['courseId']]['title'] = $calendar['courseTitle'];
    $presences[$calendar['courseId']]['calendars'][$calendar['calendarId']]['buttonToSign'] = "<span class=\"list-data\"><a id=\"sign-".$userId."-".$calendar['calendarId']."-".$calendar['courseId']."\" class=\"btn btn-primary attendance-sign\" href=\"javascript:void(0)\"><em class=\"fa fa-pencil\"></em>".get_lang('Sign')."</a></span>";
    $presences[$calendar['courseId']]['calendars'][$calendar['calendarId']]['date_time'] = api_convert_and_format_date($calendar['date_time'], null, date_default_timezone_get());
}

$template = new Template(get_lang('MyMissingSignatures'));
$template->assign('presences', $presences);
$content = $template->fetch($template->get_template('/attendance/my_missing_signatures.tpl'));
$template->assign('content', $content);
$template->display_one_col_template();

include_once 'attendance_signature.inc.php';
