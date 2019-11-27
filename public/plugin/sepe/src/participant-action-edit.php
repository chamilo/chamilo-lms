<?php
/* For licensing terms, see /license.txt */

/**
 *    This script displays a participant edit form.
 */
require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if (!empty($_POST)) {
    $check = Security::check_token('post');
    if ($check) {
        $companyTutorId = (!empty($_POST['company_tutor_id']) ? intval($_POST['company_tutor_id']) : null);
        $trainingTutorId = (!empty($_POST['training_tutor_id']) ? intval($_POST['training_tutor_id']) : null);
        $tutorCompanyDocumentType = Database::escape_string(trim($_POST['tutor_company_document_type']));
        $tutorCompanyDocumentNumber = Database::escape_string(trim($_POST['tutor_company_document_number']));
        $tutorCompanyDocumentLetter = Database::escape_string(trim($_POST['tutor_company_document_letter']));
        $tutorCompanyAlias = Database::escape_string(trim($_POST['tutor_company_alias']));
        $tutorTrainingDocumentType = Database::escape_string(trim($_POST['tutor_training_document_type']));
        $tutorTrainingDocumentNumber = Database::escape_string(trim($_POST['tutor_training_document_number']));
        $tutorTrainingDocumentLetter = Database::escape_string(trim($_POST['tutor_training_document_letter']));
        $tutorTrainingAlias = Database::escape_string(trim($_POST['tutor_training_alias']));
        $newParticipant = intval($_POST['new_participant']);
        $platformUserId = intval($_POST['platform_user_id']);
        $documentType = Database::escape_string(trim($_POST['document_type']));
        $documentNumber = Database::escape_string(trim($_POST['document_number']));
        $documentLetter = Database::escape_string(trim($_POST['document_letter']));
        $keyCompetence = Database::escape_string(trim($_POST['key_competence']));
        $contractId = Database::escape_string(trim($_POST['contract_id']));
        $companyFiscalNumber = Database::escape_string(trim($_POST['company_fiscal_number']));
        $participantId = intval($_POST['participant_id']);
        $actionId = intval($_POST['action_id']);

        if (isset($companyTutorId) && $companyTutorId == 0) {
            $sql = "SELECT * FROM $tableTutorCompany 
                    WHERE document_type = '".$tutorCompanyDocumentType."' 
                    AND document_number = '".$tutorCompanyDocumentNumber."' 
                    AND document_letter = '".$tutorCompanyDocumentLetter."';";
            $rs = Database::query($sql);
            if (Database::num_rows($rs) > 0) {
                $row = Database::fetch_assoc($rs);
                $companyTutorId = $row['id'];
                $sql = "UPDATE $tableTutorCompany SET company = 1 WHERE id = $companyTutorId";
                Database::query($sql);
            } else {
                $sql = "INSERT INTO $tableTutorCompany (alias,document_type,document_number,document_letter,company) 
                        VALUES ('".$tutorCompanyAlias."','".$tutorCompanyDocumentType."','".$tutorCompanyDocumentNumber."','".$tutorCompanyDocumentLetter."','1');";
                $rs = Database::query($sql);
                if (!$rs) {
                } else {
                    $companyTutorId = Database::insert_id();
                }
            }
        }

        if (isset($trainingTutorId) && $trainingTutorId == 0) {
            $sql = "SELECT * FROM $tableTutorCompany 
                    WHERE 
                        document_type = '".$tutorTrainingDocumentType."' AND 
                        document_number = '".$tutorTrainingDocumentNumber."' AND 
                        document_letter = '".$tutorTrainingDocumentLetter."';";
            $rs = Database::query($sql);

            if (Database::num_rows($rs) > 0) {
                $row = Database::fetch_assoc($rs);
                $trainingTutorId = $row['id'];
                $sql = "UPDATE $tableTutorCompany SET training = 1 WHERE id = $trainingTutorId";
                Database::query($sql);
            } else {
                $sql = "INSERT INTO $tableTutorCompany (alias,document_type,document_number,document_letter,training) 
                        VALUES ('".$tutorTrainingAlias."','".$tutorTrainingDocumentType."','".$tutorTrainingDocumentNumber."','".$tutorTrainingDocumentLetter."','1');";
                $rs = Database::query($sql);
                if (!$rs) {
                } else {
                    $trainingTutorId = Database::insert_id();
                }
            }
        }

        if (isset($newParticipant) && $newParticipant != 1) {
            $sql = "UPDATE $tableSepeParticipants SET 
                        platform_user_id = '".$platformUserId."', 
                        document_type = '".$documentType."', 
                        document_number = '".$documentNumber."', 
                        document_letter = '".$documentLetter."', 
                        key_competence = '".$keyCompetence."', 
                        contract_id = '".$contractId."', 
                        company_fiscal_number = '".$companyFiscalNumber."'
                    WHERE id = $participantId";
        } else {
            $sql = "INSERT INTO $tableSepeParticipants(
                        action_id,
                        platform_user_id,
                        document_type,
                        document_number,
                        document_letter,
                        key_competence,
                        contract_id,
                        company_fiscal_number
                    ) VALUES (
                        '".$actionId."',
                        '".$platformUserId."',
                        '".$documentType."',
                        '".$documentNumber."',
                        '".$documentLetter."',
                        '".$keyCompetence."',
                        '".$contractId."',
                        '".$companyFiscalNumber."'
                    );";
        }
        $res = Database::query($sql);
        if (!$res) {
            $_SESSION['sepe_message_error'] = $plugin->get_lang('NoSaveChange');
        } else {
            if ($newParticipant == 1) {
                $participantId = Database::insert_id();
            }
            // Update tutors
            if (is_null($companyTutorId)) {
                $sql = "UPDATE $tableSepeParticipants SET company_tutor_id = NULL WHERE id = $participantId";
            } else {
                $sql = "UPDATE $tableSepeParticipants SET company_tutor_id = $companyTutorId WHERE id = $participantId";
            }
            Database::query($sql);
            if (is_null($trainingTutorId)) {
                $sql = "UPDATE $tableSepeParticipants SET training_tutor_id = NULL WHERE id = $participantId";
            } else {
                $sql = "UPDATE $tableSepeParticipants SET training_tutor_id = $trainingTutorId WHERE id = $participantId";
            }
            Database::query($sql);

            $insertLog = checkInsertNewLog($platformUserId, $actionId);
            if ($insertLog) {
                $sql = "INSERT INTO $tableSepeLogParticipant (
                            platform_user_id, 
                            action_id, 
                            registration_date
                        ) VALUES (
                            '".$platformUserId."',
                            '".$actionId."',
                            '".date("Y-m-d H:i:s")."'
                        );";
            } else {
                $sql = "INSERT INTO $tableSepeLogChangeParticipant (
                            platform_user_id, 
                            action_id, 
                            change_date
                        ) VALUES (
                            '".$platformUserId."',
                            '".$actionId."',
                            '".date("Y-m-d H:i:s")."'
                        );";
            }
            $res = Database::query($sql);
            $_SESSION['sepe_message_info'] = $plugin->get_lang('SaveChange');
        }
        session_write_close();
        header("Location: participant-action-edit.php?new_participant=0&participant_id=".$participantId."&action_id=".$actionId);
        exit;
    } else {
        $participantId = intval($_POST['participant_id']);
        $actionId = intval($_POST['action_id']);
        $newParticipant = intval($_POST['new_participant']);
        Security::clear_token();
        $token = Security::get_token();
        $_SESSION['sepe_message_error'] = $plugin->get_lang('ProblemToken');
        session_write_close();
        header("Location: participant-action-edit.php?new_participant=".$newParticipant."&participant_id=".$participantId."&action_id=".$actionId);
        exit;
    }
} else {
    $token = Security::get_token();
}

if (api_is_platform_admin()) {
    $actionId = intval($_GET['action_id']);
    $courseId = getCourse($actionId);
    $interbreadcrumb[] = [
        "url" => "/plugin/sepe/src/sepe-administration-menu.php",
        "name" => $plugin->get_lang('MenuSepe'),
    ];
    $interbreadcrumb[] = [
        "url" => "formative-actions-list.php",
        "name" => $plugin->get_lang('FormativesActionsList'),
    ];
    $interbreadcrumb[] = [
        "url" => "formative-action.php?cid=".$courseId,
        "name" => $plugin->get_lang('FormativeAction'),
    ];
    if (isset($_GET['new_participant']) && intval($_GET['new_participant']) == 1) {
        $templateName = $plugin->get_lang('NewParticipantAction');
        $tpl = new Template($templateName);
        $tpl->assign('action_id', $actionId);
        $info = [];
        $tpl->assign('info', $info);
        $tpl->assign('new_participant', '1');
    } else {
        $templateName = $plugin->get_lang('EditParticipantAction');
        $tpl = new Template($templateName);
        $tpl->assign('action_id', $actionId);
        $info = getInfoParticipantAction($_GET['participant_id']);
        $tpl->assign('info', $info);
        $tpl->assign('new_participant', '0');
        $tpl->assign('participant_id', (int) $_GET['participant_id']);

        if ($info['platform_user_id'] != 0) {
            $infoUserPlatform = api_get_user_info($info['platform_user_id']);
            $tpl->assign('info_user_platform', $infoUserPlatform);
        }
        $listParticipantSpecialty = listParticipantSpecialty(intval($_GET['participant_id']));
        $tpl->assign('listParticipantSpecialty', $listParticipantSpecialty);
    }
    $courseCode = getCourseCode($actionId);
    $listStudentInfo = [];
    $listStudent = CourseManager::get_student_list_from_course_code($courseCode);

    foreach ($listStudent as $value) {
        $sql = "SELECT 1 FROM $tableSepeParticipants WHERE platform_user_id = '".$value['user_id']."';";
        $res = Database::query($sql);
        if (Database::num_rows($res) == 0) {
            $listStudentInfo[] = api_get_user_info($value['user_id']);
        }
    }
    $tpl->assign('listStudent', $listStudentInfo);
    $listTutorCompany = listTutorType("company = '1'");
    $tpl->assign('list_tutor_company', $listTutorCompany);
    $listTutorTraining = listTutorType("training = '1'");
    $tpl->assign('list_tutor_training', $listTutorTraining);
    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);
        unset($_SESSION['sepe_message_error']);
    }
    $tpl->assign('sec_token', $token);
    $listing_tpl = 'sepe/view/participant-action-edit.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location:'.api_get_path(WEB_PATH));
    exit;
}
