<?php
error_log(__LINE__);
/**
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package chamilo.exercise
 *
 * @author claro team <cvs@claroline.net>
 * @author Guillaume Lederer <guillaume@claroline.net>
 */
/**
 * Security check
 */
if (count(get_included_files()) == 1)
	die('---');

/**
 * function to create a temporary directory (SAME AS IN MODULE ADMIN)
 */

function tempdir($dir, $prefix = 'tmp', $mode = 0777) {
	if (substr($dir, -1) != '/')
		$dir .= '/';

	do {
		$path = $dir . $prefix . mt_rand(0, 9999999);
	} while (!mkdir($path, $mode));

	return $path;
}

/**
 * @return the path of the temporary directory where the exercise was uploaded and unzipped
 */

function get_and_unzip_uploaded_exercise($baseWorkDir, $uploadPath) {
	global $_course, $_user;
	//Check if the file is valid (not to big and exists)
	if (!isset ($_FILES['userFile']) || !is_uploaded_file($_FILES['userFile']['tmp_name'])) {
		// upload failed
		return false;
	}	
	if (preg_match('/.zip$/i', $_FILES['userFile']['name']) && handle_uploaded_document($_course, $_FILES['userFile'], $baseWorkDir, $uploadPath, $_user['user_id'], 0, null, 1)) {
		if (!function_exists('gzopen')) {			
			//claro_delete_file($uploadPath);
			return false;
		}
		// upload successfull
		return true;
	} else {
		//claro_delete_file($uploadPath);
		return false;
	}
}
/**
 * main function to import an exercise,
 *
 * @return an array as a backlog of what was really imported, and error or debug messages to display
 */

function import_exercise($file) {
	error_log(__LINE__);
	global $exercise_info;
	global $element_pile;
	global $non_HTML_tag_to_avoid;
	global $record_item_body;
	// used to specify the question directory where files could be found in relation in any question
	global $questionTempDir;

	$archive_path = api_get_path(SYS_ARCHIVE_PATH) . 'aiken';
	$baseWorkDir = $archive_path;

	if (!is_dir($baseWorkDir)) {
		mkdir($baseWorkDir, api_get_permissions_for_new_directories(), true);
	}

	$uploadPath = '/';

	// set some default values for the new exercise
	$exercise_info = array ();
	$exercise_info['name'] = preg_replace('/.zip$/i', '', $file);
	$exercise_info['question'] = array();
	$element_pile = array ();

	// create parser and array to retrieve info from manifest
	$element_pile = array (); //pile to known the depth in which we are
	//$module_info = array (); //array to store the info we need

	// if file is not a .zip, then we cancel all
	if (!preg_match('/.zip$/i', $file)) {
		Display :: display_error_message(get_lang('You must upload a zip file'));
		return false;
	}

	// unzip the uploaded file in a tmp directory
	if (!get_and_unzip_uploaded_exercise($baseWorkDir, $uploadPath)) {
		Display :: display_error_message(get_lang('You must upload a zip file'));
		return false;
	}
error_log(__LINE__);

	// find the different manifests for each question and parse them.
	$exerciseHandle = opendir($baseWorkDir);
	//$question_number = 0;
	$file_found = false;
	$operation = false;
    $result = false;
	// parse every subdirectory to search txt question files
	while (false !== ($file = readdir($exerciseHandle))) {
		if (is_dir($baseWorkDir . '/' . $file) && $file != "." && $file != "..") {
			//find each manifest for each question repository found
			$questionHandle = opendir($baseWorkDir . '/' . $file);
			while (false !== ($questionFile = readdir($questionHandle))) {
				if (preg_match('/.txt$/i', $questionFile)) {
					$result = parse_file($baseWorkDir, $file, $questionFile);
					$file_found = true;
				}
			}
		} elseif (preg_match('/.txt$/i', $file)) {
			$result = parse_file($baseWorkDir, '', $file);
			$file_found = true;
		} // else ignore file
	}
	if (!$file_found) {
		Display :: display_error_message(get_lang('No XML file found in the zip'));        
		return false;
	}
    if ($result == false ) {        
        return false;
    }

	//add exercise in tool

	//1.create exercise
	$exercise = new Exercise();
	$exercise->exercise = $exercise_info['name'];
    
	$exercise->save();
	$last_exercise_id = $exercise->selectId();
	if (!empty($last_exercise_id)) {
		//For each question found...
		foreach ($exercise_info['question'] as $key => $question_array) {
			//2.create question
			$question = new AikenQuestion();
			$question->type = $question_array['type'];
			$question->setAnswer();
			$question->updateTitle($question_array['title']); // question ...
			$type = $question->selectType();
			$question->type = constant($type); // type ...
			$question->save($last_exercise_id); // save computed grade
			$last_question_id = $question->selectId();
			//3.create answer
			$answer = new Answer($last_question_id);
			$answer->new_nbrAnswers = count($question_array['answer']);
			foreach ($question_array['answer'] as $key => $answers) {
				$split = explode('_', $key);
				$i = $split[1];
				$answer->new_answer[$i] = $answers['value']; // answer ...
				$answer->new_comment[$i] = $answers['feedback']; // comment ...
				$answer->new_position[$i] = $i; // position ...
				// correct answers ...
				if (in_array($key, $question_array['correct_answers'])) {
					$answer->new_correct[$i] = 1;
				} else {
					$answer->new_correct[$i] = 0;
				}
				$answer->new_weighting[$i] = $question_array['weighting'][$key];
			}
			$answer->save();
		}
		// delete the temp dir where the exercise was unzipped
		my_delete($baseWorkDir . $uploadPath);
		$operation = true;
	}    
	return $operation;
}

function parse_file($exercisePath, $file, $questionFile) {
	global $exercise_info;
	global $element_pile;
	global $non_HTML_tag_to_avoid;
	global $record_item_body;
	global $questionTempDir;

	$questionTempDir = $exercisePath . '/' . $file . '/';
	$questionFilePath = $questionTempDir . $questionFile;

	if (!($fp = @ fopen($questionFilePath, 'r'))) {
		Display :: display_error_message(get_lang('Error opening question\'s TXT file'));
		return false;
	} else {
		$data = fread($fp, filesize($questionFilePath));
	}
/*while(!feof($fp)) {
	$data = fread($fp, filesize($questionFilePath));
	error_log(print_r($data,1));

}*/
error_log('test');
$data = file($questionFilePath);

$question_index = 0;
foreach($data as $linea => $info) {
	//$preg = preg_match('/^[A-Z](\)|\.)\s(.*)/g', $linea, $matches);
	#$preg = preg_match('/(^ANSWER:\sq([A-Z])\s?)|(.*))/i', $info, $matches);
//error_log($linea . '----');
	if (preg_match('/^([A-Z])(\)|\.)\s(.*)/', $info)) {
		$question_info['option'][] = $info;
	} elseif (preg_match('/^ANSWER:\s([A-Z])\s?/', $info)) {
		$question_info['answer'] = $info;
	} elseif (preg_match('/^TEXTO_CORRECTA:\s([A-Z])\s?/', $info)) {
		$question_info['answer_explanation'] = $info;
	} elseif (preg_match('/^ETIQUETAS:\s([A-Z])\s?/', $info)) {
		$question_info['answer_tags'] = explode(',', $info);
	} elseif (preg_match('/^\n/',$info)) {
		$question_index =  $question_index++;
		//$questions[] = $question_info;
	} else {
		$question_info['title'] = $info;
	}

	
}

error_log(print_r($questions,1));
/*
	//used global variable start values declaration :

	$record_item_body = false;
	$non_HTML_tag_to_avoid = array (
		"SIMPLECHOICE",
		"CHOICEINTERACTION",
		"INLINECHOICEINTERACTION",
		"INLINECHOICE",
		"SIMPLEMATCHSET",
		"SIMPLEASSOCIABLECHOICE",
		"TEXTENTRYINTERACTION",
		"FEEDBACKINLINE",
		"MATCHINTERACTION",
		"ITEMBODY",
		"BR",
		"IMG"
	);

	//this array to detect tag not supported by claroline import in the xml file to warn the user.

	$non_supported_content_in_question = array (
		"GAPMATCHINTERACTION",
		"EXTENDEDTEXTINTERACTION",
		"HOTTEXTINTERACTION",
		"HOTSPOTINTERACTION",
		"SELECTPOINTINTERACTION",
		"GRAPHICORDERINTERACTION",
		"GRAPHICASSOCIATIONINTERACTION",
		"GRAPHICGAPMATCHINTERACTION",
		"POSITIONOBJECTINTERACTION",
		"SLIDERINTERACTION",
		"DRAWINGINTERACTION",
		"UPLOADINTERACTION",
		"RESPONSECONDITION",
		"RESPONSEIF"
	);
	$question_format_supported = true;

	$xml_parser = xml_parser_create();
	xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, false);
	xml_set_element_handler($xml_parser, 'startElement', 'endElement');
	xml_set_character_data_handler($xml_parser, 'elementData');

	if (!xml_parse($xml_parser, $data, feof($fp))) {
		// if reading of the xml file in not successfull :
		// set errorFound, set error msg, break while statement
		Display :: display_error_message(get_lang('Error reading XML file'));
		return false;
	}

	//close file
	fclose($fp);
	if (!$question_format_supported) {
		Display :: display_error_message(get_lang('Unknown question format in file %file', array (
			'%file' => $questionFile
		)));
		return false;
	}
	*/

	return true;
}
