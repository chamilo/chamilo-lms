<?php
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */

$language_file[] = 'gradebook';

require_once '../inc/global.inc.php';

require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/displaygradebook.php';
require_once 'lib/fe/evalform.class.php';
require_once 'lib/fe/dataform.class.php';
require_once api_get_path(LIBRARY_PATH) . 'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'import.lib.php';
require_once 'lib/results_data_generator.class.php';
require_once 'lib/fe/resulttable.class.php';
require_once 'lib/fe/exportgradebook.php';
require_once 'lib/scoredisplay.class.php';
require_once api_get_path(LIBRARY_PATH).'ezpdf/class.ezpdf.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';

api_block_anonymous_users();

block_students();

$interbreadcrumb[]= array (
	'url' => $_SESSION['gradebook_dest'],
	'name' => get_lang('Gradebook'
));

//load the evaluation & category
$select_eval=Security::remove_XSS($_GET['selecteval']);
if (empty($select_eval)) {
	api_not_allowed();
}

$displayscore = Scoredisplay :: instance();
$eval= Evaluation :: load($select_eval);
$overwritescore= 0;
if ($eval[0]->get_category_id() < 0) {
	// if category id is negative, then the evaluation's origin is a link
	$link= LinkFactory :: get_evaluation_link($eval[0]->get_id());
	$currentcat= Category :: load($link->get_category_id());
} else
	$currentcat= Category :: load($eval[0]->get_category_id());
	//load the result with the evaluation id

if (isset ($_GET['delete_mark'])) {
    $result = Result :: load($_GET['delete_mark']);  
    if (!empty( $result[0])) {  
        $result[0]->delete();
    }
}

if (isset ($_GET['selecteval'])) {
	$allresults= Result :: load(null,null,$select_eval);
	$iscourse= $currentcat[0]->get_course_code() == null ? 1 : 0;
}

if (isset ($_GET['editres'])) {
	$edit_res_xml=Security::remove_XSS($_GET['editres']);
	$select_eval_edit=Security::remove_XSS($_GET['selecteval']);
	$resultedit= Result :: load($edit_res_xml);
	$edit_res_form= new EvalForm(EvalForm :: TYPE_RESULT_EDIT, $eval[0], $resultedit[0], 'edit_result_form', null, api_get_self() . '?editres=' . $resultedit[0]->get_id() . '&selecteval=' .$select_eval_edit);
	if ($edit_res_form->validate()) {

		$values= $edit_res_form->exportValues();
		$result= new Result();
		$resultlog=new Result();
		$resultlog->add_result__log($values['hid_user_id'],$select_eval_edit);
		$result->set_id($edit_res_xml);
		$result->set_user_id($values['hid_user_id']);
		$result->set_evaluation_id($select_eval_edit);
		$row_value = isset($values['score']) ? (float)$values['score'] : 0 ;
		if ((!empty ($row_value)) || ($row_value == 0)) {
			$result->set_score(floatval(number_format($row_value, api_get_setting('gradebook_number_decimals'))));
		}
		$result->save();
		unset ($result);
		header('Location: gradebook_view_result.php?selecteval=' . $select_eval_edit . '&editresmessage=');
		exit;
	}
}

if (isset ($_GET['import'])) {    
    
	$interbreadcrumb[]= array ('url' => 'gradebook_view_result.php?selecteval=' . Security::remove_XSS($_GET['selecteval']), 'name' => get_lang('ViewResult'));
	$import_result_form = new DataForm(DataForm :: TYPE_IMPORT, 'import_result_form', null, api_get_self() . '?import=&selecteval=' . Security::remove_XSS($_GET['selecteval']), '_blank', '');
	if (!$import_result_form->validate()) {
		Display :: display_header(get_lang('Import'));
	}
    
    $eval[0]->check_lock_permissions();

	if ($_POST['formSent'] ) {
		if (!empty ($_FILES['import_file']['name'])) {
			$values= $import_result_form->exportValues();
			$file_type= $_POST['file_type'];
			$file_name= $_FILES['import_file']['tmp_name'];
			if ($file_type == 'csv') {
				$results= Import :: csv_to_array($file_name);
			} else {
				$results= parse_xml_data($file_name);
			}

			$nr_results_added= 0;
			foreach ($results as $index => $importedresult) {
				//check username & score
				$importedresult['user_id'] = UserManager::get_user_id_from_username($importedresult['username']);
				$added= '0';
				foreach ($allresults as $allresult) {
					if (($importedresult['user_id'] == $allresult->get_user_id())) {
						if ($importedresult['score'] != $allresult->get_score()) {
							if (!isset ($values['overwrite'])) {
								header('Location: gradebook_view_result.php?selecteval=' . Security::remove_XSS($_GET['selecteval']) . '&import_score_error=' . $importedresult['user_id']);
								exit;
								break;
							} else {
								overwritescore($allresult->get_id(), $importedresult['score'], $eval[0]->get_max());
								$overwritescore++;
								$added= '1';
							}
						} else {
							$added= '1';
						}

					}

				}
				if ($importedresult['user_id'] == null) {
					header('Location: gradebook_view_result.php?selecteval=' . Security::remove_XSS($_GET['selecteval']) . '&incorrectdata=');
					exit;
				}
				$userinfo= get_user_info_from_id($importedresult['user_id']);
				if ($userinfo['lastname'] != $importedresult['lastname'] || $userinfo['firstname'] != $importedresult['firstname'] || $userinfo['official_code'] != $importedresult['official_code']) {
					if (!isset ($values['ignoreerrors'])) {
						header('Location: gradebook_view_result.php?selecteval=' .Security::remove_XSS($_GET['selecteval']) . '&import_user_error=' . $importedresult['user_id']);
						exit;
					}
				}
				if ($added != '1') {
					if ($importedresult['score'] > $eval[0]->get_max()) {
						header('Location: gradebook_view_result.php?selecteval=' .Security::remove_XSS($_GET['selecteval']) . '&overwritemax=');
						exit;
					}
					$result= new Result();
					$result->set_user_id($importedresult['user_id']);
					if (!empty ($importedresult['score'])) {
						$result->set_score(floatval(number_format($importedresult['score'], api_get_setting('gradebook_number_decimals'))));
					}
					if (!empty ($importedresult['date'])) {
						$result->set_date(api_get_utc_datetime($importedresult['date']));
					} else {
						$result->set_date(api_get_utc_datetime());
					}
					$result->set_evaluation_id($_GET['selecteval']);
					$result->add();
					$nr_results_added++;
				}
			}
		} else {
			header('Location: ' . api_get_self() . '?import=&selecteval=' . Security::remove_XSS($_GET['selecteval']) . '&importnofile=');
			exit;
		}
		if ($overwritescore != 0) {
			header('Location: ' . api_get_self() . '?selecteval=' . Security::remove_XSS($_GET['selecteval']) . '&importoverwritescore=' . $overwritescore);
			exit;
		}
		if ($nr_results_added == 0) {
			header('Location: ' . api_get_self() . '?selecteval=' . Security::remove_XSS($_GET['selecteval']) . '&nothingadded=');
			exit;
		}
		header('Location: ' . api_get_self() . '?selecteval=' . Security::remove_XSS($_GET['selecteval']) . '&importok=');
		exit;
	}
}

if (isset($_GET['export'])) {
    $interbreadcrumb[]= array ('url' => 'gradebook_view_result.php?selecteval=' . Security::remove_XSS($_GET['selecteval']),'name' => get_lang('ViewResult'));
    $locked_status = $eval[0]->get_locked();
    $export_result_form= new DataForm(DataForm :: TYPE_EXPORT, 'export_result_form', null, api_get_self() . '?export=&selecteval=' . $_GET['selecteval'], '_blank', $locked_status);
    if (!$export_result_form->validate()) {
    	Display :: display_header(get_lang('Export'));
    }
    
    if ($export_result_form->validate()) {
    	$export= $export_result_form->exportValues();
    	$file_type= $export['file_type'];
    	$filename= 'export_results_' . gmdate('Y-m-d_H-i-s');
    	$results= Result :: load(null, null, Security::remove_XSS($_GET['selecteval']));
    	$data= array (); //when file type is csv, add a header to the output file
    	if ($file_type == 'csv') {
    		$alldata[]= array (
    			'username',
    			'official_code',
    			'lastname',
    			'firstname',
    			'score',
    			'date'
    		);
    	}
    
    	// export results to pdf file
    	if ($file_type == 'pdf') {
    		$number_decimals = api_get_setting('gradebook_number_decimals');
    		$datagen = new ResultsDataGenerator ($eval[0],$allresults);
    
    		// set headers pdf
    		!empty($_user['official_code'])? $officialcode=$_user['official_code'].' - ':'';
    
    		$h1 = array(get_lang('Teacher'),$officialcode.$_user['firstName'].', '.$_user['lastName']);
    		$h2 = array(get_lang('Score'),$eval[0]->get_max());
    		$h3 = array(get_lang('Course'),$_course['name']);
    		$h4 = array(get_lang('Weight'),$eval[0]->get_weight());
    		$h5 = array(get_lang('Session'),api_get_session_name(api_get_session_id()));
    		$date = date('d-m-Y H:i:s', time());
    		$h6 = array(get_lang('DateTime'),api_convert_and_format_date($date, "%d/%m/%Y %H:%M"));
    		$header_pdf = array($h1, $h2, $h3, $h4, $h5, $h6);
    
    		// set footer pdf
    		$f1 = '<hr />'.get_lang('Drh');
    		$f2 = '<hr />'.get_lang('Teacher');
    		$f3 = '<hr />'.get_lang('Date');
    		$footer_pdf = array($f1, $f2, $f3);
    
    		// set title pdf
    		$title_pdf = $eval[0]->get_name();
    
    		// set headers data table
    		$head_ape_name = '';
    		if (api_is_western_name_order()) {
                $head_ape_name = get_lang('FirstName').', '.get_lang('LastName');			
    		} else {
    			$head_ape_name = get_lang('LastName').', '.get_lang('FirstName');
    		}
    		
    		$head_table = array(
    							array('#',	3),
    							array(get_lang('Code'),12),
    							array($head_ape_name, 40),
    							array(get_lang('Score'),12)    											
    						);
    		if ($number_decimals == null) {
    			$head_table[]  = array(get_lang('Letters'), 15);
    		}    
            $head_display_score = '';
            $scoredisplay = ScoreDisplay :: instance();            
            $customdisplays = $scoredisplay->get_custom_score_display_settings();            
            
            if (!empty($customdisplays) && $scoredisplay->is_custom()) {
                $head_display_score = get_lang('Display');
                $head_table[] = array($head_display_score,15);
            }
                
    		// get data table
    		if (api_sort_by_first_name()) {
     			$data_array = $datagen->get_data(ResultsDataGenerator :: RDG_SORT_FIRSTNAME, 0, null, false, true);
    		} else {
    			$data_array = $datagen->get_data(ResultsDataGenerator :: RDG_SORT_LASTNAME, 0,  null, false, true);
    		}
    		$data_table = array();
    		
    		foreach ($data_array as $data) {
    			$result 	= array();
    			$user_info	= api_get_user_info($data['id']);    			
    			$result[] 	= $user_info['username'];
    			
    			if (api_is_western_name_order()) {
                    $result[] = $user_info['firstname'].', '.$user_info['lastname'];                				
    			} else {
    				$result[] = $user_info['lastname'].', '.$user_info['firstname'];
    			}
                if ($number_decimals == null) {
	                if (empty($data['scoreletter']) && !is_numeric($data['score'])) {
	                    $result[] = get_lang('DidNotTakeTheExam');
	                } else {
						$result[] = api_strtoupper(get_lang('Literal'.$data['scoreletter']));	                    
	                }           
                } else {
                	if (empty($data['score']) && !is_numeric($data['score'])) {
                		$result[] = get_lang('DidNotTakeTheExamAcronym');
                	} else {
                		$result[] = $data['score'];                        
                	}	
                }
                if ($scoredisplay->is_custom()) {
                    $result[] = $data['display'];                    
                }
    			$data_table[] = $result;
    		}
    		export_pdf_with_html($head_table, $data_table, $header_pdf, $footer_pdf, $title_pdf);
    	}
    
    	// export results to xml or csv file
    	foreach ($results as $result) {
    		$userinfo= get_user_info_from_id($result->get_user_id());
    		$data['username']= $userinfo['username']; //$result->get_user_id();
    		$data['official_code']= $userinfo['official_code'];
    		$data['lastname']= $userinfo['lastname'];
    		$data['firstname']= $userinfo['firstname'];
    		$data['score']= $result->get_score();
    		$data['date'] = api_format_date($result->get_date(), "%d/%m/%Y %R");
    		$alldata[]= $data;
    	}
    
    	switch ($file_type) {
    		case 'xml' :
    			Export :: export_table_xml($alldata, $filename, 'Result', 'XMLResults');
    			exit;
    			break;
    		case 'csv' :
    			Export :: export_table_csv($alldata, $filename);
    			exit;
    			break;
    	}
	}
}
if (isset ($_GET['resultdelete'])) {
	$result= Result :: load($_GET['resultdelete']);
	$result[0]->delete();
	header('Location: gradebook_view_result.php?deleteresult=&selecteval=' .Security::remove_XSS($_GET['selecteval']));
	exit;
}
if (isset ($_POST['action'])) {
	$number_of_selected_items= count($_POST['id']);
	if ($number_of_selected_items == '0') {
		Display :: display_warning_message(get_lang('NoItemsSelected'),false);
	} else {
        switch ($_POST['action']) {
    		case 'delete' :
    			$number_of_deleted_results= 0;
    			foreach ($_POST['id'] as $indexstr) {
    				$result= Result :: load($indexstr);
    				$result[0]->delete();
    				$number_of_deleted_results++;
    			}
    			header('Location: gradebook_view_result.php?massdelete=&selecteval=' .Security::remove_XSS($_GET['selecteval']));
    			exit;
    			break;
    	}
	}
} // TODO - what if selecteval not set ?

$addparams = array ('selecteval' => $eval[0]->get_id());
if (isset ($_GET['print'])) {
	$datagen = new ResultsDataGenerator ($eval[0],$allresults);
	if (api_sort_by_first_name()) {
		$data_array = $datagen->get_data(ResultsDataGenerator :: RDG_SORT_FIRSTNAME, 0, null, true);
	} else {
		$data_array = $datagen->get_data(ResultsDataGenerator :: RDG_SORT_LASTNAME,0,null,true);
	}
	if ($displayscore->is_custom()) {
		if (api_is_western_name_order()) {
			$header_names = array(get_lang('FirstName'),get_lang('LastName'),get_lang('Score'),get_lang('Display'));
		} else {
			$header_names = array(get_lang('LastName'),get_lang('FirstName'),get_lang('Score'),get_lang('Display'));
		}
	} else {
		if (api_is_western_name_order()) {
			$header_names = array(get_lang('FirstName'),get_lang('LastName'),get_lang('Score'));
		}else {
			$header_names = array(get_lang('LastName'),get_lang('FirstName'),get_lang('Score'));
		}
	}
	$newarray = array();
	foreach ($data_array as $data) {
		$newarray[] = array_slice($data, 3);
	}

	echo print_table($newarray, $header_names,get_lang('ViewResult'), $eval[0]->get_name());
	exit;
} else {
	$resulttable= new ResultTable($eval[0], $allresults, $iscourse, $addparams);
}

$htmlHeadXtra[]= '<script type="text/javascript">
	
function confirmationuser() {
    if (confirm("' . get_lang('DeleteUser') . '?"))
    	{return true;}
    else
    	{return false;}
}

function confirmationall () {
    if (confirm("' . get_lang('DeleteAll') . '?"))
    	{return true;}
    else
    	{return false;}
    }
</script>';
if (isset ($_GET['deleteall'])) {
	$eval[0]->delete_results();
	header('Location: gradebook_view_result.php?allresdeleted=&selecteval=' . Security::remove_XSS($_GET['selecteval']));
	exit;
}
if ((!isset ($_GET['export'])) && (!isset ($_GET['import']))) {
	if (!isset($_GET['selectcat'])) {
		$interbreadcrumb[]= array (
		'url' => $_SESSION['gradebook_dest'].'?selectcat=' .$currentcat[0]->get_id(),
		'name' => get_lang('Details')
		  );
	}
	$interbreadcrumb[]= array ('url' => 'gradebook_view_result.php'.'?selecteval='.Security::remove_XSS($_GET['selecteval']),'name' => get_lang('ViewResult'));
	Display :: display_header('');
}
if (isset ($_GET['addresultnostudents'])) {
	Display :: display_warning_message(get_lang('AddResultNoStudents'),false);
}

if (isset ($_GET['editresmessage'])) {
	Display :: display_confirmation_message(get_lang('ResultEdited'),false);
}

if (isset ($_GET['addresult'])) {
	Display :: display_confirmation_message(get_lang('ResultAdded'),false);
}

if (isset ($_GET['adduser'])) {
	Display :: display_confirmation_message(get_lang('UserAdded'),false);
}

if (isset ($_GET['deleteresult'])) {
	Display :: display_confirmation_message(get_lang('ResultDeleted'),false);
}

if (isset ($_GET['editallresults'])) {
	Display :: display_confirmation_message(get_lang('AllResultsEdited'),false);
}
if (isset ($_GET['importok'])) {
	Display :: display_confirmation_message(get_lang('FileUploadComplete'),false);
}
if (isset ($_GET['importnofile'])) {
	Display :: display_warning_message(get_lang('ImportNoFile'),false);
}
if (isset ($_GET['incorrectdata'])) {
	Display :: display_warning_message(get_lang('IncorrectData'),false);
}
if (isset ($_GET['nothingadded'])) {
	Display :: display_warning_message(get_lang('ProblemUploadingFile'),false);
}
if (isset ($_GET['massdelete'])) {
	Display :: display_confirmation_message(get_lang('ResultsDeleted'),false);
}
if (isset ($_GET['nouser'])) {
	Display :: display_warning_message(get_lang('NoUser'),false);
}
if (isset ($_GET['overwritemax'])) {
	Display :: display_warning_message(get_lang('OverWriteMax'),false);
}
if (isset ($_GET['importoverwritescore'])) {
	Display :: display_confirmation_message(get_lang('ImportOverWriteScore') . ' ' . $_GET['importoverwritescore']);
}
if (isset ($_GET['import_user_error'])) {
	$userinfo= get_user_info_from_id($_GET['import_user_error']);
	Display :: display_warning_message(get_lang('UserInfoDoesNotMatch') . ' ' . api_get_person_name($userinfo['firstname'], $userinfo['lastname']));
}
if (isset ($_GET['allresdeleted'])) {
	Display :: display_confirmation_message(get_lang('AllResultDeleted'));
}
if (isset ($_GET['import_score_error'])) {
	$userinfo= get_user_info_from_id($_GET['import_score_error']);
	Display :: display_warning_message(get_lang('ScoreDoesNotMatch') . ' ' . api_get_person_name($userinfo['firstname'], $userinfo['lastname']));
}
if ($file_type == null) { //show the result header
    if (isset ($export_result_form) && !(isset ($edit_res_form))) {
    	echo $export_result_form->display();
    	DisplayGradebook :: display_header_result($eval[0], $currentcat[0]->get_id(), 1);
    } else {
    	if (isset ($import_result_form)) {    		
    		echo $import_result_form->display();    		
    	}
    	if (isset ($edit_res_form)) {
    		echo $edit_res_form->toHtml();
    	}
    	DisplayGradebook :: display_header_result($eval[0], $currentcat[0]->get_id(), 1);
    }
    $resulttable->display();
    Display :: display_footer();
}
