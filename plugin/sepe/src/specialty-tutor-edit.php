<?php
/* For licensing terms, see /license.txt */

/**
 *    This script displays a specialty tutors edit form.
 */
require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if (!empty($_POST)) {
    $check = Security::check_token('post');
    if ($check) {
        $sltUserExists = intval($_POST['slt_user_exists']);
        $existingTutor = intval($_POST['existingTutor']);
        $specialtyId = intval($_POST['specialty_id']);
        $tutorAccreditation = Database::escape_string(trim($_POST['tutor_accreditation']));
        $professionalExperience = intval($_POST['professional_experience']);
        $teachingCompetence = Database::escape_string(trim($_POST['teaching_competence']));
        $experienceTeleforming = intval($_POST['experience_teleforming']);
        $trainingTeleforming = Database::escape_string(trim($_POST['training_teleforming']));
        $specialtyTutorId = intval($_POST['specialtyTutorId']);
        $documentType = Database::escape_string(trim($_POST['document_type']));
        $documentNumber = Database::escape_string(trim($_POST['document_number']));
        $documentLetter = Database::escape_string(trim($_POST['document_letter']));
        $actionId = intval($_POST['action_id']);
        $newTutor = intval($_POST['new_tutor']);
        $platformUserId = intval($_POST['platform_user_id']);

        if ($sltUserExists == 1) {
            $sql = "SELECT * FROM $tableSepeTutors WHERE id = $existingTutor;";
            $rs = Database::query($sql);
            $tmp = Database::fetch_assoc($rs);

            $sql = "INSERT INTO $tableSepeSpecialtyTutors (
                        specialty_id,
                        tutor_id,
                        tutor_accreditation,
                        professional_experience,
                        teaching_competence,
                        experience_teleforming    ,
                        training_teleforming
                    ) VALUES (
                        $specialtyId,
                        $existingTutor,
                        '".$tmp['tutor_accreditation']."',
                        '".$tmp['professional_experience']."',
                        '".$tmp['teaching_competence']."',
                        '".$tmp['experience_teleforming    ']."',
                        '".$tmp['training_teleforming']."'
                    );";
            $res = Database::query($sql);
        } else {
            $sql = "SELECT id
                    FROM $tableSepeTutors
                    WHERE
                        document_type = '".$documentType."'
                        AND document_number = '".$documentNumber."'
                        AND document_letter = '".$documentLetter."';";
            $rs = Database::query($sql);
            if (Database::num_rows($rs) > 0) {
                $aux = Database::fetch_assoc($rs);
                $sql = "UPDATE $tableSepeTutors SET
                        platform_user_id = $platformUserId,
                        tutor_accreditation = '".$tutorAccreditation."',
                        professional_experience = $professionalExperience,
                        teaching_competence = '".$teachingCompetence."',
                        experience_teleforming = $experienceTeleforming,
                        training_teleforming = '".$trainingTeleforming."'
                        WHERE id = '".$aux['id']."';";
                $res = Database::query($sql);
                if (!$res) {
                    $_SESSION['sepe_message_error'] = $plugin->get_lang('NoSaveChange');
                }
                $newTutor = 0; //Reset variable, no create new tutor, exists tutor
                $tutorId = $aux['id'];
                $specialtyTutorId = getSpecialtyTutorId($specialtyId, $tutorId);
            } else {
                $sql = "UPDATE $tableSepeTutors
                        SET platform_user_id=''
                        WHERE platform_user_id='".$platformUserId."'";
                Database::query($sql);
                $sql = "INSERT INTO $tableSepeTutors (
                            platform_user_id,
                            document_type,
                            document_number,
                            document_letter,
                            tutor_accreditation,
                            professional_experience,
                            teaching_competence,
                            experience_teleforming,
                            training_teleforming
                        ) VALUES (
                            $platformUserId,
                            '".$documentType."',
                            '".$documentNumber."',
                            '".$documentLetter."',
                            '".$tutorAccreditation."',
                            $professionalExperience,
                            '".$teachingCompetence."',
                            $experienceTeleforming,
                            '".$trainingTeleforming."'
                        );";
                $res = Database::query($sql);
                if (!$res) {
                    $_SESSION['sepe_message_error'] = $plugin->get_lang('NoSaveChange');
                } else {
                    $tutorId = Database::insert_id();
                }
            }

            if (isset($newTutor) && $newTutor != 1) {
                $sql = "UPDATE $tableSepeSpecialtyTutors SET
                        tutor_id = $tutorId,
                        tutor_accreditation = '".$tutorAccreditation."',
                        professional_experience = $professionalExperience,
                        teaching_competence = '".$teachingCompetence."',
                        experience_teleforming = $experienceTeleforming,
                        training_teleforming='".$trainingTeleforming."'
                        WHERE id = $specialtyTutorId;";
            } else {
                $sql = "INSERT INTO $tableSepeSpecialtyTutors (
                            specialty_id,
                            tutor_id,
                            tutor_accreditation,
                            professional_experience,
                            teaching_competence,
                            experience_teleforming,
                            training_teleforming
                        ) VALUES (
                            $specialtyId,
                            $tutorId,
                            '".$tutorAccreditation."',
                            $professionalExperience,
                            '".$teachingCompetence."',
                            $experienceTeleforming,
                            '".$trainingTeleforming."'
                        );";
            }
            $res = Database::query($sql);
            if (!$res) {
                $_SESSION['sepe_message_error'] = $plugin->get_lang('NoSaveChange');
            } else {
                if ($newTutor == 1) {
                    $tutorId = Database::insert_id();
                }
                $_SESSION['sepe_message_info'] = $plugin->get_lang('SaveChange');
            }
        }
        session_write_close();
        header("Location: specialty-action-edit.php?new_specialty=0&specialty_id=".$specialtyId."&action_id=".$actionId);
        exit;
    } else {
        $actionId = intval($_POST['action_id']);
        $newTutor = intval($_POST['new_tutor']);
        $specialtyId = intval($_POST['specialty_id']);
        $specialtyTutorId = intval($_POST['specialtyTutorId']);
        Security::clear_token();
        $token = Security::get_token();
        $_SESSION['sepe_message_error'] = $plugin->get_lang('ProblemToken');
        session_write_close();
        header("Location: specialty-tutor-edit.php?new_tutor=".$newTutor."&specialty_id=".$specialtyId."&tutor_id=".$specialtyTutorId."&action_id=".$actionId);
        exit;
    }
} else {
    $token = Security::get_token();
}

if (api_is_platform_admin()) {
    $actionId = (int) $_GET['action_id'];
    $specialtyId = (int) $_GET['specialty_id'];

    $courseId = getCourse($actionId);
    $interbreadcrumb[] = [
        "url" => "/plugin/sepe/src/sepe-administration-menu.php",
        "name" => $plugin->get_lang('MenuSepe'),
    ];
    $interbreadcrumb[] = ["url" => "formative-actions-list.php", "name" => $plugin->get_lang('FormativesActionsList')];
    $interbreadcrumb[] = [
        "url" => "formative-action.php?cid=".$courseId,
        "name" => $plugin->get_lang('FormativeAction'),
    ];
    $interbreadcrumb[] = [
        "url" => "specialty-action-edit.php?new_specialty=0&specialty_id=".$specialtyId."&action_id=".$actionId,
        "name" => $plugin->get_lang('SpecialtyFormativeAction'),
    ];
    if (isset($_GET['new_tutor']) && intval($_GET['new_tutor']) == 1) {
        $templateName = $plugin->get_lang('NewSpecialtyTutor');
        $tpl = new Template($templateName);
        $tpl->assign('action_id', $actionId);
        $tpl->assign('specialty_id', $specialtyId);
        $info = [];
        $tpl->assign('info', $info);
        $tpl->assign('new_tutor', '1');
        $platformUserId = '';
    } else {
        $templateName = $plugin->get_lang('EditSpecialtyTutor');
        $tpl = new Template($templateName);
        $tpl->assign('action_id', $actionId);
        $tpl->assign('specialty_id', $specialtyId);
        $tpl->assign('tutor_id', intval($_GET['tutor_id']));
        $info = getInfoSpecialtyTutor($_GET['tutor_id']);
        $tpl->assign('info', $info);
        $tpl->assign('new_tutor', '0');
        $platformUserId = $info['platform_user_id'];
    }
    $tutorsList = getTutorsSpecialty($_GET['specialty_id']);
    $tpl->assign('ExistingTutorsList', $tutorsList);

    $listTeachers = CourseManager::getTeachersFromCourse($courseId);
    $listTeachers = freeTeacherList($listTeachers, $_GET['specialty_id'], $platformUserId);
    $tpl->assign('listTeachers', $listTeachers);
    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);
        unset($_SESSION['sepe_message_error']);
    }
    $tpl->assign('sec_token', $token);

    $listing_tpl = 'sepe/view/specialty-tutor-edit.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location:'.api_get_path(WEB_PATH));
    exit;
}
