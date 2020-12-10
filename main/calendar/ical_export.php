<?php
/* For licensing terms, see /license.txt */

use kigkonsult\iCalcreator\vcalendar;
use kigkonsult\iCalcreator\vevent;

/**
 * This file exclusively export calendar items to iCal or similar formats.
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
// we are not inside a course, so we reset the course id
$cidReset = true;
// setting the global file that gets the general configuration, the databases, the languages, ...
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_MYAGENDA;
api_block_anonymous_users();

// setting the name of the tool
$nameTools = get_lang('MyAgenda');

// the variables for the days and the months
// Defining the shorts for the days
$DaysShort = api_get_week_days_short();
// Defining the days of the week to allow translation of the days
$DaysLong = api_get_week_days_long();
// Defining the months of the year to allow translation of the months
$MonthsLong = api_get_months_long();

if (empty($_GET['id'])) {
    api_not_allowed();
}

$id = explode('_', $_GET['id']);
$type = $id[0];
$id = $id[1];

$agenda = new Agenda($type);
if (isset($_GET['course_id'])) {
    $course_info = api_get_course_info_by_id($_GET['course_id']);
    if (!empty($course_info)) {
        $agenda->set_course($course_info);
    }
}

$event = $agenda->get_event($id);

if (!empty($event)) {
    define('ICAL_LANG', api_get_language_isocode());

    $ical = new vcalendar();
    $ical->setConfig('unique_id', api_get_path(WEB_PATH));
    $ical->setProperty('method', 'PUBLISH');
    $ical->setConfig('url', api_get_path(WEB_PATH));
    $vevent = new vevent();

    switch ($_GET['class']) {
        case 'public':
            $vevent->setClass('PUBLIC');
            break;
        case 'private':
            $vevent->setClass('PRIVATE');
            break;
        case 'confidential':
            $vevent->setClass('CONFIDENTIAL');
            break;
        default:
            $vevent->setClass('PRIVATE');
            break;
    }

    $event['start_date'] = api_get_local_time($event['start_date']);
    $event['end_date'] = api_get_local_time($event['end_date']);

    switch ($type) {
        case 'personal':
        case 'platform':
            $vevent->setProperty('summary', api_convert_encoding($event['title'], 'UTF-8', $charset));
            if (empty($event['start_date'])) {
                header('location:'.Security::remove_XSS($_SERVER['HTTP_REFERER']));
            }
            list($y, $m, $d, $h, $M, $s) = preg_split('/[\s:-]/', $event['start_date']);
            $vevent->setProperty(
                'dtstart',
                ['year' => $y, 'month' => $m, 'day' => $d, 'hour' => $h, 'min' => $M, 'sec' => $s]
            );
            if (empty($event['end_date'])) {
                $y2 = $y;
                $m2 = $m;
                $d2 = $d;
                $h2 = $h;
                $M2 = $M + 15;
                $s2 = $s;
                if ($M2 > 60) {
                    $M2 = $M2 - 60;
                    $h2++;
                }
            } else {
                list($y2, $m2, $d2, $h2, $M2, $s2) = preg_split('/[\s:-]/', $event['end_date']);
            }
            $vevent->setProperty(
                'dtend',
                ['year' => $y2, 'month' => $m2, 'day' => $d2, 'hour' => $h2, 'min' => $M2, 'sec' => $s2]
            );
            //$vevent->setProperty( 'LOCATION', get_lang('Unknown') ); // property name - case independent
            $vevent->setProperty('description', api_convert_encoding($event['description'], 'UTF-8', $charset));
            //$vevent->setProperty( 'comment', 'This is a comment' );
            //$user = api_get_user_info($event['user']);
            //$vevent->setProperty('organizer',$user['mail']);
            //$vevent->setProperty('attendee',$user['mail']);
            //$vevent->setProperty( 'rrule', array( 'FREQ' => 'WEEKLY', 'count' => 4));// occurs also four next weeks
            $ical->setConfig('filename', $y.$m.$d.$h.$M.$s.'-'.rand(1, 1000).'.ics');
            $ical->setComponent($vevent); // add event to calendar
            $ical->returnCalendar();
            break;
        case 'course':
            $vevent->setProperty('summary', api_convert_encoding($event['title'], 'UTF-8', $charset));
            if (empty($event['start_date'])) {
                header('location:'.Security::remove_XSS($_SERVER['HTTP_REFERER']));
            }
            list($y, $m, $d, $h, $M, $s) = preg_split('/[\s:-]/', $event['start_date']);
            $vevent->setProperty(
                'dtstart',
                ['year' => $y, 'month' => $m, 'day' => $d, 'hour' => $h, 'min' => $M, 'sec' => $s]
            );
            if (empty($event['end_date'])) {
                $y2 = $y;
                $m2 = $m;
                $d2 = $d;
                $h2 = $h;
                $M2 = $M + 15;
                $s2 = $s;
                if ($M2 > 60) {
                    $M2 = $M2 - 60;
                    $h2++;
                }
            } else {
                list($y2, $m2, $d2, $h2, $M2, $s2) = preg_split('/[\s:-]/', $event['end_date']);
            }
            $vevent->setProperty(
                'dtend',
                ['year' => $y2, 'month' => $m2, 'day' => $d2, 'hour' => $h2, 'min' => $M2, 'sec' => $s2]
            );
            $vevent->setProperty('description', api_convert_encoding($event['description'], 'UTF-8', $charset));
            //$vevent->setProperty( 'comment', 'This is a comment' );
            //$user = api_get_user_info($event['user']);
            //$vevent->setProperty('organizer',$user['mail']);
            //$vevent->setProperty('attendee',$user['mail']);
            //$course = api_get_course_info();
            $vevent->setProperty('location', $course_info['name']); // property name - case independent
            /*if($ai['repeat']) {
                $trans = array('daily'=>'DAILY','weekly'=>'WEEKLY','monthlyByDate'=>'MONTHLY','yearly'=>'YEARLY');
                $freq = $trans[$ai['repeat_type']];
                list($e_y,$e_m,$e_d) = split('/',date('Y/m/d',$ai['repeat_end']));
                $vevent->setProperty('rrule',array('FREQ'=>$freq,'UNTIL'=>array('year'=>$e_y,'month'=>$e_m,'day'=>$e_d),'INTERVAL'=>'1'));
            }*/
            //$vevent->setProperty( 'rrule', array( 'FREQ' => 'WEEKLY', 'count' => 4));// occurs also four next weeks
            $ical->setConfig('filename', $y.$m.$d.$h.$M.$s.'-'.rand(1, 1000).'.ics');
            $ical->setComponent($vevent); // add event to calendar
            $ical->returnCalendar();
            break;
        default:
            header('location:'.Security::remove_XSS($_SERVER['HTTP_REFERER']));
            exit();
    }
} else {
    header('location:'.Security::remove_XSS($_SERVER['HTTP_REFERER']));
    exit;
}
