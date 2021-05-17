<?php

/**
 * Functions.
 *
 * @package chamilo.plugin.sepe
 */
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
        $row['center_origin'] = Security::remove_XSS(stripslashes($row['center_origin']));
        $row['center_code'] = Security::remove_XSS(stripslashes($row['center_code']));
        $row['center_name'] = Security::remove_XSS(stripslashes($row['center_name']));
        $row['url'] = Security::remove_XSS(stripslashes($row['url']));
        $row['tracking_url'] = Security::remove_XSS(stripslashes($row['tracking_url']));
        $row['phone'] = Security::remove_XSS(stripslashes($row['phone']));
        $row['mail'] = Security::remove_XSS(stripslashes($row['mail']));
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
    }

    return false;
}

function getActionId($courseId)
{
    global $tableSepeCourseActions;
    $courseId = (int) $courseId;
    $sql = "SELECT action_id FROM $tableSepeCourseActions WHERE course_id = $courseId";
    $rs = Database::query($sql);
    $aux = Database::fetch_assoc($rs);

    return $aux['action_id'];
}

function getCourse($actionId)
{
    global $tableSepeCourseActions;
    $actionId = (int) $actionId;
    $sql = "SELECT course_id FROM $tableSepeCourseActions WHERE action_id = $actionId";
    $rs = Database::query($sql);
    $aux = Database::fetch_assoc($rs);

    return $aux['course_id'];
}
function getCourseCode($actionId)
{
    global $tableCourse;
    $actionId = (int) $actionId;
    $courseId = getCourse($actionId);
    $sql = "SELECT code FROM $tableCourse WHERE id = $courseId";
    $rs = Database::query($sql);
    $aux = Database::fetch_assoc($rs);

    return $aux['code'];
}

function getActionInfo($id)
{
    global $tableSepeActions;

    $id = (int) $id;
    $sql = "SELECT * FROM $tableSepeActions WHERE id = $id";
    $res = Database::query($sql);
    $row = false;
    if (Database::num_rows($res) > 0) {
        $row['action_origin'] = Security::remove_XSS(stripslashes($row['action_origin']));
        $row['action_code'] = Security::remove_XSS(stripslashes($row['action_code']));
        $row['situation'] = Security::remove_XSS(stripslashes($row['situation']));
        $row['specialty_origin'] = Security::remove_XSS(stripslashes($row['specialty_origin']));
        $row['professional_area'] = Security::remove_XSS(stripslashes($row['professional_area']));
        $row['specialty_code'] = Security::remove_XSS(stripslashes($row['specialty_code']));
        $row['full_itinerary_indicator'] = Security::remove_XSS(stripslashes($row['full_itinerary_indicator']));
        $row['financing_type'] = Security::remove_XSS(stripslashes($row['financing_type']));
        $row['action_name'] = Security::remove_XSS(stripslashes($row['action_name']));
        $row['global_info'] = Security::remove_XSS(stripslashes($row['global_info']));
        $row['schedule'] = Security::remove_XSS(stripslashes($row['schedule']));
        $row['requirements'] = Security::remove_XSS(stripslashes($row['requirements']));
        $row['contact_action'] = Security::remove_XSS(stripslashes($row['contact_action']));
        $row = Database::fetch_assoc($res);
    }

    return $row;
}

function getSpecialtActionInfo($specialtyId)
{
    global $tableSepeSpecialty;
    $specialtyId = (int) $specialtyId;
    $sql = "SELECT * FROM $tableSepeSpecialty WHERE id = $specialtyId";
    $res = Database::query($sql);
    $row = false;
    if (Database::num_rows($res) > 0) {
        $row['specialty_origin'] = Security::remove_XSS(stripslashes($row['specialty_origin']));
        $row['professional_area'] = Security::remove_XSS(stripslashes($row['professional_area']));
        $row['specialty_code'] = Security::remove_XSS(stripslashes($row['specialty_code']));
        $row['center_origin'] = Security::remove_XSS(stripslashes($row['center_origin']));
        $row['center_code'] = Security::remove_XSS(stripslashes($row['center_code']));
        $row['modality_impartition'] = Security::remove_XSS(stripslashes($row['modality_impartition']));
        $row = Database::fetch_assoc($res);
    }

    return $row;
}

function getInfoSpecialtyClassroom($classroomId)
{
    global $tableSepeSpecialtyClassroom;
    global $tableCenters;
    $classroomId = (int) $classroomId;
    $sql = "SELECT a.*, center_origin, center_code
            FROM $tableSepeSpecialtyClassroom a
            LEFT JOIN $tableCenters b ON a.center_id = b.id
            WHERE a.id = $classroomId";
    $res = Database::query($sql);
    $row = false;
    if (Database::num_rows($res) > 0) {
        $row['center_origin'] = Security::remove_XSS(stripslashes($row['center_origin']));
        $row['center_code'] = Security::remove_XSS(stripslashes($row['center_code']));
        $row = Database::fetch_assoc($res);
    }

    return $row;
}

function getInfoSpecialtyTutorial($tutorialId)
{
    global $tableSepeParticipantsSpecialtyTutorials;
    $tutorialId = (int) $tutorialId;
    $sql = "SELECT * FROM $tableSepeParticipantsSpecialtyTutorials WHERE id = $tutorialId";
    $res = Database::query($sql);
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
    $specialtyId = (int) $specialtyId;
    $sql = "SELECT * FROM $tableSepeSpecialtyTutors WHERE specialty_id = $specialtyId";
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
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}

function listTutorType($condition)
{
    global $tableTutorCompany;
    $condition = Database::escape_string($condition);
    $sql = "SELECT * FROM $tableTutorCompany WHERE ".$condition." ORDER BY alias ASC, document_number ASC;";
    $res = Database::query($sql);
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        $tmp = [];
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
    $specialtyId = (int) $specialtyId;

    $sql = "SELECT tutor_id FROM $tableSepeSpecialtyTutors WHERE specialty_id = $specialtyId";
    $rs = Database::query($sql);
    $tutorsList = [];
    while ($tmp = Database::fetch_assoc($rs)) {
        $tutorsList[] = $tmp['tutor_id'];
    }
    $sql = "SELECT a.*, b.firstname AS firstname, b.lastname AS lastname
            FROM $tableSepeTutors AS a
            LEFT JOIN $tableUser AS b ON a.platform_user_id=b.user_id;";
    $res = Database::query($sql);
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        if (!in_array($row['id'], $tutorsList)) {
            $tutor = [];
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
    $tutorId = (int) $tutorId;
    $sql = "SELECT a.*,platform_user_id,document_type, document_number,document_letter
            FROM $tableSepeSpecialtyTutors a
            INNER JOIN $tableSepeTutors b ON a.tutor_id=b.id
            WHERE a.id = $tutorId;";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        $row['tutor_accreditation'] = Security::remove_XSS(stripslashes($row['tutor_accreditation']));
        $row['teaching_competence'] = Security::remove_XSS(stripslashes($row['teaching_competence']));
        $row['training_teleforming'] = Security::remove_XSS(stripslashes($row['training_teleforming']));
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }

    return $row;
}

function freeTeacherList($teacherList, $specialtyId, $platform_user_id)
{
    global $tableSepeSpecialtyTutors;
    global $tableSepeTutors;

    $specialtyId = (int) $specialtyId;
    $platform_user_id = (int) $platform_user_id;

    $sql = "SELECT tutor_id FROM $tableSepeSpecialtyTutors WHERE specialty_id = $specialtyId";
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
    $participantId = (int) $participantId;
    $sql = "SELECT * FROM $tableSepeParticipants WHERE id = $participantId";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
        $result = [];
        $result['id'] = $row[''];
        $result['action_id'] = $row['action_id'];
        $result['company_tutor_id'] = $row['company_tutor_id'];
        $result['training_tutor_id'] = $row['training_tutor_id'];
        $result['platform_user_id'] = $row['platform_user_id'];
        $result['document_type'] = Security::remove_XSS(stripslashes($row['document_type']));
        $result['document_number'] = Security::remove_XSS(stripslashes($row['document_number']));
        $result['document_letter'] = Security::remove_XSS(stripslashes($row['document_letter']));
        $result['key_competence'] = Security::remove_XSS(stripslashes($row['key_competence']));
        $result['contract_id'] = Security::remove_XSS(stripslashes($row['contract_id']));
        $result['company_fiscal_number'] = Security::remove_XSS(stripslashes($row['company_fiscal_number']));
    } else {
        $result = false;
    }

    return $result;
}

function getParticipantId($id)
{
    global $tableSepeParticipantsSpecialty;
    $id = (int) $id;
    $sql = "SELECT participant_id FROM  $tableSepeParticipantsSpecialty WHERE id = $id";
    $rs = Database::query($sql);
    $aux = Database::fetch_assoc($rs);

    return $aux['participant_id'];
}

function getInfoSpecialtyParticipant($specialtyId)
{
    global $tableSepeParticipantsSpecialty;
    $specialtyId = (int) $specialtyId;
    $sql = "SELECT * FROM $tableSepeParticipantsSpecialty WHERE id = $specialtyId";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
        $row['specialty_origin'] = Security::remove_XSS(stripslashes($row['specialty_origin']));
        $row['professional_area'] = Security::remove_XSS(stripslashes($row['professional_area']));
        $row['specialty_code'] = Security::remove_XSS(stripslashes($row['specialty_code']));
        $row['center_origin'] = Security::remove_XSS(stripslashes($row['center_origin']));
        $row['center_code'] = Security::remove_XSS(stripslashes($row['center_code']));
        $row['final_result'] = Security::remove_XSS(stripslashes($row['final_result']));
        $row['final_qualification'] = Security::remove_XSS(stripslashes($row['final_qualification']));
        $row['final_score'] = Security::remove_XSS(stripslashes($row['final_score']));
    } else {
        $row = false;
    }

    return $row;
}

function specialtyList($actionId)
{
    global $tableSepeSpecialty;
    $actionId = (int) $actionId;
    $sql = "SELECT id, specialty_origin, professional_area, specialty_code
            FROM $tableSepeSpecialty
            WHERE action_id = $actionId";
    $res = Database::query($sql);
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}

function participantList($actionId)
{
    global $tableSepeParticipants;
    global $tableUser;
    $actionId = (int) $actionId;
    $sql = "SELECT $tableSepeParticipants.id AS id, document_type, document_number, document_letter, firstname, lastname
            FROM $tableSepeParticipants
            LEFT JOIN $tableUser ON $tableSepeParticipants.platform_user_id=$tableUser.user_id
            WHERE action_id = $actionId";
    $res = Database::query($sql);
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}

function listParticipantSpecialty($participantId)
{
    global $tableSepeParticipantsSpecialty;

    $participantId = (int) $participantId;
    $sql = "SELECT * FROM $tableSepeParticipantsSpecialty WHERE participant_id = $participantId";
    $res = Database::query($sql);
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        $row['specialty_origin'] = Security::remove_XSS(stripslashes($row['specialty_origin']));
        $row['professional_area'] = Security::remove_XSS(stripslashes($row['professional_area']));
        $row['specialty_code'] = Security::remove_XSS(stripslashes($row['specialty_code']));
        $row['center_origin'] = Security::remove_XSS(stripslashes($row['center_origin']));
        $row['center_code'] = Security::remove_XSS(stripslashes($row['center_code']));
        $row['final_result'] = Security::remove_XSS(stripslashes($row['final_result']));
        $row['final_qualification'] = Security::remove_XSS(stripslashes($row['final_qualification']));
        $row['final_score'] = Security::remove_XSS(stripslashes($row['final_score']));
        $aux[] = $row;
    }

    return $aux;
}

function classroomList($specialtyId)
{
    global $tableSepeSpecialtyClassroom;
    global $tableCenters;
    $specialtyId = (int) $specialtyId;
    $sql = "SELECT a.*, center_origin, center_code
            FROM $tableSepeSpecialtyClassroom a
            LEFT JOIN $tableCenters b ON a.center_id=b.id
            WHERE specialty_id = $specialtyId";
    $res = Database::query($sql);
    $aux = [];
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
    $specialtyId = (int) $specialtyId;
    $aux = [];
    $sql = "SELECT a.*,document_type,document_number,document_letter, firstname, lastname
            FROM $tableSepeSpecialtyTutors a
            INNER JOIN $tableSepeTutors b ON a.tutor_id=b.id
            LEFT JOIN $tableUser c ON b.platform_user_id=c.user_id
            WHERE a.specialty_id = $specialtyId";
    $res = Database::query($sql);
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}

function getListSpecialtyTutorial($specialtyId)
{
    global $tableSepeParticipantsSpecialtyTutorials;
    $specialtyId = (int) $specialtyId;
    $sql = "SELECT * FROM $tableSepeParticipantsSpecialtyTutorials
            WHERE participant_specialty_id = $specialtyId";
    $res = Database::query($sql);
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        $row['tutor_accreditation'] = Security::remove_XSS(stripslashes($row['tutor_accreditation']));
        $row['teaching_competence'] = Security::remove_XSS(stripslashes($row['teaching_competence']));
        $row['training_teleforming'] = Security::remove_XSS(stripslashes($row['training_teleforming']));
        $aux[] = $row;
    }

    return $aux;
}

function listCourseAction()
{
    global $tableSepeActions;
    global $tableSepeCourseActions;

    $sql = "SELECT
            $tableSepeCourseActions.*, course.title AS title,
            $tableSepeActions.action_origin AS action_origin,
            $tableSepeActions.action_code AS action_code
            FROM $tableSepeCourseActions, course, $tableSepeActions
            WHERE $tableSepeCourseActions.course_id=course.id
            AND $tableSepeActions.id=$tableSepeCourseActions.action_id";
    $res = Database::query($sql);
    $aux = [];
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
                SELECT * FROM $tableSepeCourseActions
                WHERE $tableCourse.id = $tableSepeCourseActions.course_id)
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
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        $row['action_origin'] = Security::remove_XSS(stripslashes($row['action_origin']));
        $row['action_code'] = Security::remove_XSS(stripslashes($row['action_code']));
        $aux[] = $row;
    }

    return $aux;
}

function getSpecialtyTutorId($specialtyId, $tutorId)
{
    global $tableSepeSpecialtyTutors;
    $specialtyId = (int) $specialtyId;
    $tutorId = (int) $tutorId;

    $sql = "SELECT id
            FROM $tableSepeSpecialtyTutors
            WHERE specialty_id = $specialtyId AND tutor_id = $tutorId";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);

    return $row['id'];
}

function checkInsertNewLog($platformUserId, $actionId)
{
    global $tableSepeLogParticipant;
    $platformUserId = (int) $platformUserId;
    $actionId = (int) $actionId;
    $sql = "SELECT * FROM $tableSepeLogParticipant
            WHERE platform_user_id = $platformUserId AND action_id = $actionId";
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
    $participantId = (int) $participantId;

    $sql = "SELECT * FROM $tableSepeParticipants WHERE id = $participantId";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    if ($row['platform_user_id'] == 0 || $row['platform_user_id'] == '') {
        return false;
    } else {
        return $row['platform_user_id'];
    }
}
