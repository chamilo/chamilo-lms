<?php
/* For licensing terms, see /license.txt */

/**
 *    This script displays a participant edit form.
 *    @package chamilo.plugin.sepe
 */

use \ChamiloSession as Session;
require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if ( !empty($_POST)) {
    $check = Security::check_token('post');
    if ($check) {
        $companyTutorId = trim(Security::remove_XSS(stripslashes($_POST['company_tutor_id'])));
        $tutorCompanyDocumentType = trim(Security::remove_XSS(stripslashes($_POST['tutor_company_document_type'])));
        $tutorCompanyDocumentNumber = trim(Security::remove_XSS(stripslashes($_POST['tutor_company_document_number'])));
        $tutorCompanyDocumentLetter = trim(Security::remove_XSS(stripslashes($_POST['tutor_company_document_letter'])));
        $tutorCompanyAlias = trim(Security::remove_XSS(stripslashes($_POST['tutor_company_alias'])));
        $trainingTutorId = trim(Security::remove_XSS(stripslashes($_POST['training_tutor_id'])));
        $tutorTrainingDocumentType = trim(Security::remove_XSS(stripslashes($_POST['tutor_training_document_type'])));
        $tutorTrainingDocumentNumber = trim(Security::remove_XSS(stripslashes($_POST['tutor_training_document_number'])));
        $tutorTrainingDocumentLetter = trim(Security::remove_XSS(stripslashes($_POST['tutor_training_document_letter'])));
        $tutorTrainingAlias = trim(Security::remove_XSS(stripslashes($_POST['tutor_training_alias'])));
        $newParticipant = trim(Security::remove_XSS(stripslashes($_POST['new_participant'])));
        $platformUserId = trim(Security::remove_XSS(stripslashes($_POST['platform_user_id'])));
        $documentType = trim(Security::remove_XSS(stripslashes($_POST['document_type'])));
        $documentNumber = trim(Security::remove_XSS(stripslashes($_POST['document_number'])));
        $documentLetter = trim(Security::remove_XSS(stripslashes($_POST['document_letter'])));
        $keyCompetence = trim(Security::remove_XSS(stripslashes($_POST['key_competence'])));
        $contractId = trim(Security::remove_XSS(stripslashes($_POST['contract_id'])));
        $companyFiscalNumber = trim(Security::remove_XSS(stripslashes($_POST['company_fiscal_number'])));
        $participantId = trim(Security::remove_XSS(stripslashes($_POST['participant_id'])));
        $actionId = trim(Security::remove_XSS(stripslashes($_POST['action_id'])));
    	
    	if (isset($companyTutorId) && $companyTutorId == "new_company_tutor") {
    		$sql = "SELECT * FROM $tableTutorCompany 
    				WHERE document_type='".$tutorCompanyDocumentType."' AND document_number='".$tutorCompanyDocumentNumber."' AND document_letter='".$tutorCompanyDocumentLetter."';";
    		$rs = Database::query($sql);
    		if (Database::num_rows($rs) > 0) {
    			$row = Database::fetch_assoc($rs);
    			$companyTutorId = $row['id'];
    			$sql = "UPDATE $tableTutorCompany SET company='1' WHERE id='".$companyTutorId."'";
    			Database::query($sql);
    		} else {
    			$sql = "INSERT INTO $tableTutorCompany (alias,document_type,document_number,document_letter,company) 
    					VALUES ('".$tutorCompanyAlias."','".$tutorCompanyDocumentType."','".$tutorCompanyDocumentNumber."','".$tutorCompanyDocumentLetter."','1');";
    			$rs = Database::query($sql);
    			if (!$rs) {
    				echo Database::error();	
    			} else {
    				$companyTutorId = Database::insert_id();
    			}
    		}
    	}
    	
    	if (isset($trainingTutorId) && $trainingTutorId == "new_training_tutor") {
    		$sql = "SELECT * FROM $tableTutorCompany 
    				WHERE document_type='".$tutorTrainingDocumentType."' AND document_number='".$tutorTrainingDocumentNumber."' AND document_letter='".$tutorTrainingDocumentLetter."';";
    		$rs = Database::query($sql);
    
    		if (Database::num_rows($rs) > 0) {
    			$row = Database::fetch_assoc($rs);
    			$trainingTutorId = $row['id'];
    			$sql = "UPDATE $tableTutorCompany SET training='1' WHERE id='".$trainingTutorId."'";
    			Database::query($sql);
    		} else {
    			$sql = "INSERT INTO $tableTutorCompany (alias,document_type,document_number,document_letter,training) 
    					VALUES ('".$tutorTrainingAlias."','".$tutorTrainingDocumentType."','".$tutorTrainingDocumentNumber."','".$tutorTrainingDocumentLetter."','1');";
    			$rs = Database::query($sql);
    			if (!$rs) {
    				echo Database::error();	
    			} else {
    				$trainingTutorId = Database::insert_id();
    			}
    		}
    	}
    	
    	if (isset($newParticipant) && $newParticipant != "1") {
    		$sql = "UPDATE plugin_sepe_participants SET 
    		            platform_user_id='".$platformUserId."', 
    		            document_type='".$documentType."', 
    		            document_number='".$documentNumber."', 
    		            document_letter='".$documentLetter."', 
    		            key_competence='".$keyCompetence."', 
    		            contract_id='".$contractId."', 
    		            company_fiscal_number='".$companyFiscalNumber."', 
    		            company_tutor_id='".$companyTutorId."', 
    		            training_tutor_id='".$trainingTutorId."' 
                    WHERE id='".$participantId."';";	
    	} else {
    		$sql = "INSERT INTO plugin_sepe_participants(
        		        action_id,
        		        platform_user_id,
        		        document_type,
        		        document_number,
        		        document_letter,
        		        key_competence,
        		        contract_id,
        		        company_fiscal_number,
        		        company_tutor_id,
        		        training_tutor_id
    		        ) VALUES (
    		            '".$actionId."',
    		            '".$platformUserId."',
    		            '".$documentType."',
    		            '".$documentNumber."',
    		            '".$documentLetter."',
    		            '".$keyCompetence."',
    		            '".$contractId."',
    		            '".$companyFiscalNumber."',
    		            '".$companyTutorId."',
    		            '".$trainingTutorId."'
    		        );";
    	}
    	$res = Database::query($sql);
    	if (!$res) {
    		error_log(Database::error());
    		$_SESSION['sepe_message_error'] = $plugin->get_lang('NoSaveChange');
    	} else {
    		if ($newParticipant == '1') {
    			$participantId = Database::insert_id();
    		}
    		$insertLog = checkInsertNewLog($platformUserId,$actionId);
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
    } else {
        $participantId = trim(Security::remove_XSS(stripslashes($_POST['participant_id'])));
        $actionId = trim(Security::remove_XSS(stripslashes($_POST['action_id'])));
        $newParticipant = trim(Security::remove_XSS(stripslashes($_POST['new_participant'])));
        Security::clear_token();
        $token = Security::get_token();
        $_SESSION['sepe_message_error'] = $plugin->get_lang('ProblemToken');
        session_write_close();
    	header("Location: participant-action-edit.php?new_participant=".$newParticipant."&participant_id=".$participantId."&action_id=".$actionId);
    }
} else {
    $token = Security::get_token();
}

if (api_is_platform_admin()) {
	$courseId = getCourse($_GET['action_id']);
	$interbreadcrumb[] = array("url" => "/plugin/sepe/src/sepe-administration-menu.php", "name" => $plugin->get_lang('MenuSepe'));
	$interbreadcrumb[] = array("url" => "formative-actions-list.php", "name" => $plugin->get_lang('FormativesActionsList'));
	$interbreadcrumb[] = array("url" => "formative-action.php?cid=".$courseId, "name" => $plugin->get_lang('FormativeAction'));
	if (isset($_GET['new_participant']) && $_GET['new_participant'] == '1') {
		$templateName = $plugin->get_lang('NewParticipantAction');
		$tpl = new Template($templateName);
		$tpl->assign('action_id', $_GET['action_id']);
		$info = array();
		$tpl->assign('info', $info);
		$tpl->assign('new_participant', '1');
	} else {
		$templateName = $plugin->get_lang('EditParticipantAction');
		$tpl = new Template($templateName);
		$tpl->assign('action_id', $_GET['action_id']);
		$info = getInfoParticipantAction($_GET['participant_id']);
		$tpl->assign('info', $info);
		$tpl->assign('new_participant', '0');
		$tpl->assign('participant_id', $_GET['participant_id']);
		
		if ($info['platform_user_id'] != 0) {
			$infoUserPlatform = api_get_user_info($info['platform_user_id']);//UserManager::get_user_info_by_id($info['platform_user_id']);
			$tpl->assign('info_user_platform', $infoUserPlatform);
		}
		$listParticipantSpecialty = listParticipantSpecialty($_GET['participant_id']);
		$tpl->assign('listParticipantSpecialty', $listParticipantSpecialty);
	}
	$courseCode = getCourseCode($_GET['action_id']);
	$listStudentInfo = array();
	$listStudent = CourseManager::get_student_list_from_course_code($courseCode);
	
	foreach ($listStudent as $value) {
		$sql = "SELECT 1 FROM $tableSepeParticipants WHERE platform_user_id='".$value['user_id']."';";
		$res = Database::query($sql);
		if (Database::num_rows($res)==0) {
			$listStudentInfo[] = api_get_user_info($value['user_id']); 
		}
	}
    $tpl->assign('listStudent', $listStudentInfo);
	$listTutorCompany = array();
	$listTutorCompany = listTutorType("company='1'");
	$tpl->assign('list_tutor_company', $listTutorCompany);
	$listTutorTraining = array();
	$listTutorTraining= listTutorType("training='1'");
	$tpl->assign('list_tutor_training', $listTutorTraining);
	if (isset($_SESSION['sepe_message_info'])) {
		$tpl->assign('message_info', $_SESSION['sepe_message_info']);	
		unset($_SESSION['sepe_message_info']);
	}
	if (isset($_SESSION['sepe_message_error'])) {
		$tpl->assign('message_error', $_SESSION['sepe_message_error']);	
		unset($_SESSION['sepe_message_error']);
	}
	$tpl->assign('sec_token',$token);
	$listing_tpl = 'sepe/view/participant-action-edit.tpl';
	$content = $tpl->fetch($listing_tpl);
	$tpl->assign('content', $content);
	$tpl->display_one_col_template();
} else {
    header('Location:' . api_get_path(WEB_PATH));
}
