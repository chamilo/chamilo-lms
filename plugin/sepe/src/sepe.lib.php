<?php
/**
 * Functions
 * @package chamilo.plugin.sepe
 */

require_once 'sepe_plugin.class.php';

$tableSepeCenter = Database::get_main_table(SepePlugin::TABLE_SEPE_CENTER);
$tableSepeActions = Database::get_main_table(SepePlugin::TABLE_SEPE_ACTIONS);
$tableSepeSpecialty = Database::get_main_table(SepePlugin::TABLE_SEPE_SPECIALTY);
$tableSepeSpecialtyClassroom = Database::get_main_table(SepePlugin::TABLE_SEPE_SPECIALTY_CLASSROOM);
$tableSepeSpecialtyTutors = Database::get_main_table(SepePlugin::TABLE_SEPE_SPECIALTY_TUTORS);
$tableSepeTutors = Database::get_main_table(SepePlugin::TABLE_SEPE_TUTORS);
$tableSepeParticipants = Database::get_main_table(SepePlugin::TABLE_SEPE_PARTICIPANTS);
$tableSepeParticipantsSpecialty = Database::get_main_table(SepePlugin::TABLE_SEPE_PARTICIPANTS_SPECIALTY);
$tableSepeParticipantsSpecialtyTutorials = Database::get_main_table(SepePlugin::TABLE_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS);
$tableSepeCourseActions = Database::get_main_table(SepePlugin::TABLE_SEPE_COURSE_ACTIONS);
$tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$tableCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tableUser = Database::get_main_table(TABLE_MAIN_USER);
$tableCenters = Database::get_main_table(SepePlugin::TABLE_SEPE_CENTERS);
$tableTutorCompany = Database::get_main_table(SepePlugin::TABLE_SEPE_TUTORS_COMPANY);
$tableSepeCourseActions = Database::get_main_table(SepePlugin::TABLE_SEPE_COURSE_ACTIONS);
$tableSepeLogParticipant = Database::get_main_table(SepePlugin::TABLE_SEPE_LOG_PARTICIPANT);
$tableSepeLogChangeParticipant = Database::get_main_table(SepePlugin::TABLE_SEPE_LOG_MOD_PARTICIPANT);

function getInfoIdentificationData()
{
    global $tableSepeCenter;
    $sql = "SELECT * FROM $tableSepeCenter;";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function checkIdentificationData()
{
    global $tableSepeCenter;
    $sql = "SELECT 1 FROM $tableSepeCenter;";
    $result = Database::query($sql);
    if (Database::affected_rows($result) > 0) {
        return true;
    }else{
        return false;
    }
}

function getActionId($courseId)
{
    global $tableSepeCourseActions;
    $sql = "SELECT action_id FROM $tableSepeCourseActions WHERE course_id='".$courseId."';";
    $rs = Database::query($sql);
    $aux = Database::fetch_assoc($rs);
    return $aux['action_id'];
}

function getCourse($actionId)
{
    global $tableSepeCourseActions;
    $sql = "SELECT course_id FROM $tableSepeCourseActions WHERE action_id='".$actionId."';";
    $rs = Database::query($sql);
    $aux = Database::fetch_assoc($rs);
    return $aux['course_id'];
}
function getCourseCode($actionId)
{
    global $tableCourse;
    $courseId = getCourse($actionId);
    $sql = "SELECT code FROM $tableCourse WHERE id='".$courseId."'";    
    $rs = Database::query($sql);
    $aux = Database::fetch_assoc($rs);
    return $aux['code'];
}

function getActionInfo($id)
{
    global $tableSepeActions;
    $sql = "SELECT * FROM $tableSepeActions WHERE id='".$id."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function getSpecialtActionInfo($specialtyId)
{
    global $tableSepeSpecialty;
    $sql = "SELECT * FROM $tableSepeSpecialty WHERE id='".$specialtyId."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function getInfoSpecialtyClassroom($classroomId)
{
    global $tableSepeSpecialtyClassroom;
    global $tableCenters;
    $sql = "SELECT a.*, center_origin, center_code 
            FROM $tableSepeSpecialtyClassroom a LEFT JOIN $tableCenters b ON a.center_id=b.id 
            WHERE a.id='".$classroomId."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function getInfoSpecialtyTutorial($tutorialId)
{
    global $tableSepeParticipantsSpecialtyTutorials;
    $sql = "SELECT * FROM $tableSepeParticipantsSpecialtyTutorials WHERE id='".$tutorialId."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function list_tutor($specialtyId)
{
    global $tableSepeSpecialtyTutors;
    $sql = "SELECT * FROM $tableSepeSpecialtyTutors WHERE specialty_id='".$specialtyId."';";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function getCentersList() 
{
    global $tableCenters;
    $sql = "SELECT * FROM $tableCenters;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function listTutorType($condition)
{
    global $tableTutorCompany;
       $sql = "SELECT * FROM $tableTutorCompany WHERE ".$condition." ORDER BY alias ASC, document_number ASC;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $tmp = array();
        $tmp['id'] = $row['id'];
        if (trim($row['alias']) != '') {
            $tmp['alias'] = $row['alias'].' - '.$row['document_type'].' '.$row['document_number'].' '.$row['document_letter'];    
        } else {
            $tmp['alias'] = $row['document_type'].' '.$row['document_number'].' '.$row['document_letter'];    
        }
        $aux[] = $tmp;
    }
    return $aux;
}

function getTutorsSpecialty($specialtyId)
{
    global $tableSepeSpecialtyTutors;
    global $tableSepeTutors;
    global $tableUser;
    $sql = "SELECT tutor_id FROM $tableSepeSpecialtyTutors;";
    $rs = Database::query($sql);
    $tutorsList = array();
    while ($tmp = Database::fetch_assoc($rs)) {
        $tutorsList[] = $tmp['tutor_id'];
    }
    $sql = "SELECT a.*, b.firstname AS firstname, b.lastname AS lastname 
            FROM $tableSepeTutors AS a LEFT JOIN $tableUser AS b ON a.platform_user_id=b.user_id;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        if (!in_array($row['id'],$tutorsList)) {
            $tutor = array();
            $tutor['id'] = $row['id'];
            if (trim($row['firstname']) != '' || trim($row['lastname']) != '') {
                $tutor['data'] = $row['firstname'].' '.$row['lastname'].' ('.$row['document_type'].' '.$row['document_number'].' '.$row['document_letter'].' )';    
            } else {
                $tutor['data'] = $row['document_type'].' '.$row['document_number'].' '.$row['document_letter'];    
            }
            $aux[] = $tutor;
        }
    }
    return $aux;
}

function getInfoSpecialtyTutor($tutorId)
{
    global $tableSepeSpecialtyTutors;
    global $tableSepeTutors;
    $sql = "SELECT a.*,platform_user_id,document_type, document_number,document_letter 
            FROM $tableSepeSpecialtyTutors a
            INNER JOIN $tableSepeTutors b ON a.tutor_id=b.id 
            WHERE a.id='".$tutorId."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function freeTeacherList($teacherList,$specialtyId,$platform_user_id)
{
    global $tableSepeSpecialtyTutors;
    global $tableSepeTutors;
    $sql = "SELECT tutor_id FROM $tableSepeSpecialtyTutors WHERE specialty_id='".$specialtyId."';";
    $rs = Database::query($sql);
    if (Database::num_rows($rs) > 0) {
        while ($aux = Database::fetch_assoc($rs)) {
            $sql = "SELECT platform_user_id FROM $tableSepeTutors WHERE id='".$aux['tutor_id']."';";
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $tmp = Database::fetch_assoc($res);
                if ($tmp['platform_user_id'] != 0 && $tmp['platform_user_id'] != $platform_user_id) {
                    foreach ($teacherList as $key => $value) {
                        if ($value['id'] == $tmp['platform_user_id']) {
                            unset($teacherList[$key]);
                            break;
                        }
                    }
                }
            }
        }
    }
    return $teacherList;
}

function getInfoParticipantAction($participantId)
{
    global $tableSepeParticipants;
    $sql = "SELECT * FROM $tableSepeParticipants WHERE id='".$participantId."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function getParticipantId($id)
{
    global $tableSepeParticipantsSpecialty;
    $sql = "SELECT participant_id FROM  $tableSepeParticipantsSpecialty WHERE id='".$id."';";
    $rs = Database::query($sql);
    $aux = Database::fetch_assoc($rs);
    return $aux['participant_id'];
}

function getInfoSpecialtyParticipant($specialtyId)
{
    global $tableSepeParticipantsSpecialty;
    $sql = "SELECT * FROM $tableSepeParticipantsSpecialty WHERE id='".$specialtyId."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function specialtyList($actionId)
{
    global $tableSepeSpecialty;
    $sql = "SELECT id, specialty_origin, professional_area, specialty_code
            FROM $tableSepeSpecialty
            WHERE action_id='".$actionId."';";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function participantList($actionId)
{
    global $tableSepeParticipants;
    global $tableUser;
    $sql = "SELECT $tableSepeParticipants.id AS id, document_type, document_number, document_letter, firstname, lastname
            FROM $tableSepeParticipants LEFT JOIN $tableUser ON $tableSepeParticipants.platform_user_id=$tableUser.user_id
            WHERE action_id='".$actionId."';";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function listParticipantSpecialty($participantId)
{
    global $tableSepeParticipantsSpecialty;
    $sql = "SELECT * FROM $tableSepeParticipantsSpecialty WHERE participant_id='".$participantId."';";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function classroomList($specialtyId)
{
    global $tableSepeSpecialtyClassroom;
    global $tableCenters;
    $sql = "SELECT a.*, center_origin, center_code
            FROM $tableSepeSpecialtyClassroom a LEFT JOIN $tableCenters b ON a.center_id=b.id 
            WHERE specialty_id='".$specialtyId."';";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function tutorsList($specialtyId)
{
    global $tableSepeSpecialtyTutors;
    global $tableSepeTutors;
    global $tableUser;
    $aux = array();
    $sql = "SELECT a.*,document_type,document_number,document_letter, firstname, lastname FROM $tableSepeSpecialtyTutors a 
            INNER JOIN $tableSepeTutors b ON a.tutor_id=b.id 
            LEFT JOIN $tableUser c ON b.platform_user_id=c.user_id 
            WHERE a.specialty_id='".$specialtyId."';";
    $res = Database::query($sql);
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function getListSpecialtyTutorial($specialtyId)
{
    global $tableSepeParticipantsSpecialtyTutorials;
    $sql = "SELECT * FROM $tableSepeParticipantsSpecialtyTutorials WHERE participant_specialty_id='".$specialtyId."';";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function listCourseAction()
{
    global $tableSepeActions;
    global $tableSepeCourseActions;
    $sql = "SELECT $tableSepeCourseActions.*, course.title AS title, $tableSepeActions.action_origin AS action_origin, $tableSepeActions.action_code AS action_code 
            FROM $tableSepeCourseActions, course, $tableSepeActions 
            WHERE $tableSepeCourseActions.course_id=course.id 
            AND $tableSepeActions.id=$tableSepeCourseActions.action_id";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function listCourseFree()
{
    global $tableCourse;
    global $tableSepeCourseActions;
    $sql = "SELECT id, title FROM $tableCourse
            WHERE NOT EXISTS (
                SELECT * FROM $tableSepeCourseActions WHERE $tableCourse.id = $tableSepeCourseActions.course_id)
            ;";
    $res = Database::query($sql);
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function listActionFree()
{
    global $tableSepeActions;
    global $tableSepeCourseActions;
    $sql = "SELECT id, action_origin, action_code FROM $tableSepeActions
            WHERE NOT EXISTS (
                SELECT * FROM $tableSepeCourseActions WHERE $tableSepeActions.id = $tableSepeCourseActions.action_id)
            ;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function getSpecialtyTutorId($specialtyId, $tutorId)
{
    global $tableSepeSpecialtyTutors;
    $sql = "SELECT id 
            FROM $tableSepeSpecialtyTutors 
            WHERE specialty_id='".$specialtyId."' AND tutor_id='".$tutorId."';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    return $row['id'];
}

function checkInsertNewLog($platformUserId,$actionId)
{
    global $tableSepeLogParticipant;
    $sql = "SELECT * FROM $tableSepeLogParticipant WHERE platform_user_id='".$platformUserId."' AND action_id='".$actionId."';";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        return false;
    } else {
        return true;
    }
}

function getUserPlatformFromParticipant($participantId)
{
    global $tableSepeParticipants;
    $sql = "SELECT * FROM $tableSepeParticipants WHERE id='".$participantId."';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    if ($row['platform_user_id'] == '0' || $row['platform_user_id'] == '') {
        return false;
    } else {
        return $row['platform_user_id'];
    }
}
