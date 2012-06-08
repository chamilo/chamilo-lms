<?php
/* For licensing terms, see /license.txt */

require_once 'Course.class.php';

/**
 * Class to show a form to select resources
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @author Julio Montoya <gugli100@gmail.com>
 * @package chamilo.backup
 */
class CourseSelectForm
{
	/**
	 * Display the form
	 * @param array $hidden_fiels Hidden fields to add to the form.
	 * @param boolean the document array will be serialize. This is used in the course_copy.php file
	 */
	function display_form($course, $hidden_fields = null, $avoid_serialize=false) {
        global $charset;
		$resource_titles[RESOURCE_EVENT] 			= get_lang('Events');
		$resource_titles[RESOURCE_ANNOUNCEMENT] 		= get_lang('Announcements');
		$resource_titles[RESOURCE_DOCUMENT] 			= get_lang('Documents');
		$resource_titles[RESOURCE_LINK] 				= get_lang('Links');
		$resource_titles[RESOURCE_COURSEDESCRIPTION]	= get_lang('CourseDescription');
		$resource_titles[RESOURCE_FORUM] 			= get_lang('Forums');
		$resource_titles[RESOURCE_QUIZ] 				= get_lang('Tests');
		$resource_titles[RESOURCE_LEARNPATH] 		= get_lang('Learnpaths');
		$resource_titles[RESOURCE_SCORM] 			= 'SCORM';
		$resource_titles[RESOURCE_TOOL_INTRO] 		= get_lang('ToolIntro');
		$resource_titles[RESOURCE_SURVEY] 			= get_lang('Survey');
		$resource_titles[RESOURCE_GLOSSARY] 			= get_lang('Glossary');
		$resource_titles[RESOURCE_WIKI]				= get_lang('Wiki');
		$resource_titles[RESOURCE_THEMATIC]			= get_lang('Thematic');
		$resource_titles[RESOURCE_ATTENDANCE]		= get_lang('Attendance');
?>
		<script>
			function exp(item) {
				el = document.getElementById('div_'+item);
				if (el.style.display=='none'){
					el.style.display='';
					document.getElementById('img_'+item).src='../img/1.gif';
				}
				else{
					el.style.display='none';
					document.getElementById('img_'+item).src='../img/0.gif';
				}
			}
			function setCheckbox(type,value) {
 				d = document.course_select_form;
 				for (i = 0; i < d.elements.length; i++) {
   					if (d.elements[i].type == "checkbox") {
						var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
 						if( name.indexOf(type) > 0 || type == 'all' ){
						     d.elements[i].checked = value;
						}
   					}
 				}
			}
			function checkLearnPath(message){
				d = document.course_select_form;
 				for (i = 0; i < d.elements.length; i++) {
 					if (d.elements[i].type == "checkbox") {
						var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
 						if( name.indexOf('learnpath') > 0){
 							if(d.elements[i].checked){
	 							setCheckbox('document',true);
	 							alert(message);
	 							break;
 							}
 						}
 					}
 				}
			}
		</script>
		<?php

		//get destination course title
		if (!empty($hidden_fields['destination_course'])) {
			require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
			$course_infos = CourseManager::get_course_information($hidden_fields['destination_course']);
			echo '<h3>';
			echo get_lang('DestinationCourse').' : '.$course_infos['title'];
			echo '</h3>';
		}

		echo '<p>';
		echo get_lang('SelectResources');
		echo '</p>';

        Display::display_normal_message(get_lang('DontForgetToSelectTheMediaFilesIfYourResourceNeedIt'));

		echo '<script src="'.api_get_path(WEB_CODE_PATH).'inc/lib/javascript/upload.js" type="text/javascript"></script>';
		echo '<script type="text/javascript">var myUpload = new upload(1000);</script>';
		echo '<form method="post" id="upload_form" name="course_select_form" onsubmit="javascript: myUpload.start(\'dynamic_div\',\''.api_get_path(WEB_CODE_PATH).'img/progress_bar.gif\',\''.get_lang('PleaseStandBy', '').'\',\'upload_form\')">';
		echo '<input type="hidden" name="action" value="course_select_form"/>';

		if (!empty($hidden_fields['destination_course']) && !empty($hidden_fields['origin_course']) && !empty($hidden_fields['destination_session']) && !empty($hidden_fields['origin_session']) ) {
			echo '<input type="hidden" name="destination_course" 	value="'.$hidden_fields['destination_course'].'"/>';
			echo '<input type="hidden" name="origin_course" 		value="'.$hidden_fields['origin_course'].'"/>';
			echo '<input type="hidden" name="destination_session" 	value="'.$hidden_fields['destination_session'].'"/>';
			echo '<input type="hidden" name="origin_session" 		value="'.$hidden_fields['origin_session'].'"/>';
		}

		$element_count = 0;
        foreach ($course->resources as $type => $resources) {
            if (count($resources) > 0) {
				switch ($type) {
					//Resources to avoid
					case RESOURCE_LINKCATEGORY :
					case RESOURCE_FORUMCATEGORY :
					case RESOURCE_FORUMPOST :
					case RESOURCE_FORUMTOPIC :
					case RESOURCE_QUIZQUESTION:
					case RESOURCE_SURVEYQUESTION:
					case RESOURCE_SURVEYINVITATION:
					case RESOURCE_SCORM:
						break;
                    default :
						echo '<img id="img_'.$type.'" src="../img/1.gif" onclick="javascript:exp('."'$type'".');" />&nbsp;';
						echo '<b onclick="javascript:exp('."'$type'".');" >'.$resource_titles[$type].'</b><br />';
						echo '<div id="div_'.$type.'">';
						if ($type == RESOURCE_LEARNPATH) {
    						Display::display_warning_message(get_lang('ToExportLearnpathWithQuizYouHaveToSelectQuiz'));
    						Display::display_warning_message(get_lang('IfYourLPsHaveAudioFilesIncludedYouShouldSelectThemFromTheDocuments'));
						}
						if ($type == RESOURCE_DOCUMENT) {
                            if (api_get_setting('show_glossary_in_documents') != 'none') {
                                Display::display_warning_message(get_lang('ToExportDocumentsWithGlossaryYouHaveToSelectGlossary'));
                            }
						}

						echo '<blockquote>';

                        echo '<div class="btn-group">';
						echo "<a class=\"btn\" href=\"javascript: void(0);\" onclick=\"javascript: setCheckbox('$type',true);\" >".get_lang('All')."</a>";
                        echo "<a class=\"btn\" href=\"javascript: void(0);\" onclick=\"javascript:setCheckbox('$type',false);\" >".get_lang('None')."</a>";
						echo '</div><br />';

						foreach ($resources as $id => $resource) {
                            echo '<label class="checkbox">';
							echo '<input type="checkbox" name="resource['.$type.']['.$id.']"  id="resource['.$type.']['.$id.']" />';
							$resource->show();
							echo '</label>';
						}
						echo '</blockquote>';
						echo '</div>';
						echo '<script language="javascript">exp('."'$type'".')</script>';
						$element_count++;
                }
			}
		}

		if ($avoid_serialize) {
			/*Documents are avoided due the huge amount of memory that the serialize php function "eats"
			(when there are directories with hundred/thousand of files) */
			// this is a known issue of serialize
			$course->resources['document']= null;
		}

		echo '<input type="hidden" name="course" value="'.base64_encode(Course::serialize($course)).'"/>';

		if (is_array($hidden_fields)) {
			foreach ($hidden_fields as $key => $value) {
				echo '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
			}
		}

		if (empty($element_count)) {
		    Display::display_warning_message(get_lang('NoDataAvailable'));
		} else {
    		if (!empty($hidden_fields['destination_session'])) {
    			echo '<br /><button class="save" type="submit" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES, $charset))."'".')) return false;" >'.get_lang('Ok').'</button>';
    		} else {
    			echo '<br /><button class="save" type="submit" onclick="checkLearnPath(\''.addslashes(get_lang('DocumentsWillBeAddedToo')).'\')">'.get_lang('Ok').'</button>';
    		}
		}

		CourseSelectForm :: display_hidden_quiz_questions($course);
		CourseSelectForm :: display_hidden_scorm_directories($course);
		echo '</form>';
		echo '<div id="dynamic_div" style="display:block;margin-left:40%;margin-top:10px;height:50px;"></div>';
	}


	function display_hidden_quiz_questions($course) {
		if(is_array($course->resources)){
			foreach ($course->resources as $type => $resources) {
				if (count($resources) > 0) {
					switch ($type) {
						case RESOURCE_QUIZQUESTION:
							foreach ($resources as $id => $resource) {
								echo '<input type="hidden" name="resource['.RESOURCE_QUIZQUESTION.']['.$id.']" id="resource['.RESOURCE_QUIZQUESTION.']['.$id.']" value="On" />';
							}
							break;

					}
				}
			}
		}
	}

	function display_hidden_scorm_directories($course) {
			if(is_array($course->resources)){
			foreach ($course->resources as $type => $resources) {
				if(count($resources) > 0) {
					switch($type) {
						case RESOURCE_SCORM:
							foreach ($resources as $id=>$resource) {
								echo '<input type="hidden" name="resource['.RESOURCE_SCORM.']['.$id.']" id="resource['.RESOURCE_SCORM.']['.$id.']" value="On" />';
							}
							break;
					}
				}
			}
		}
	}

	/**
	 * Get the posted course
	 * @param string who calls the function? It can be copy_course, create_backup, import_backup or recycle_course
	 * @return course The course-object with all resources selected by the user
	 * in the form given by display_form(...)
	 */
	function get_posted_course($from='', $session_id = 0, $course_code = '') {
		$course = Course::unserialize(base64_decode($_POST['course']));

		//Create the resource DOCUMENT objects
		//Loading the results from the checkboxes of the javascript
		$resource = $_POST['resource'][RESOURCE_DOCUMENT];

		$course_info 	= api_get_course_info($course_code);
		$table_doc 		= Database::get_course_table(TABLE_DOCUMENT);
		$table_prop 	= Database::get_course_table(TABLE_ITEM_PROPERTY);

		$course_id 		= $course_info['real_id'];

		// Searching the documents resource that have been set to null because $avoid_serialize is true in the display_form() function
		if ($from == 'copy_course') {
			if (is_array($resource)) {
				$resource = array_keys($resource);
				foreach	($resource as $resource_item) {

					$condition_session = '';
					if (!empty($session_id)) {
						$session_id = intval($session_id);
						$condition_session = ' AND d.session_id ='.$session_id;
					}

					$sql = 'SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size
							FROM '.$table_doc.' d, '.$table_prop.' p
							WHERE 	d.c_id = '.$course_id.' AND
									p.c_id = '.$course_id.' AND
									tool 	= \''.TOOL_DOCUMENT.'\' AND
									p.ref 	= d.id AND p.visibility != 2 AND
									d.id 	= '.$resource_item.$condition_session.'
							ORDER BY path';
					$db_result = Database::query($sql);
					while ($obj = Database::fetch_object($db_result)) {
						$doc = new Document($obj->id, $obj->path, $obj->comment, $obj->title, $obj->filetype, $obj->size);
						$course->add_resource($doc);
						// adding item property
						$sql = "SELECT * FROM $table_prop WHERE c_id = '.$course_id.'  AND TOOL = '".RESOURCE_DOCUMENT."' AND ref='".$resource_item."'";
						$res = Database::query($sql);
						$all_properties = array ();
						while ($item_property = Database::fetch_array($res,'ASSOC')) {
							$all_properties[] = $item_property;
						}
						$course->resources[RESOURCE_DOCUMENT][$resource_item]->item_properties = $all_properties;
					}
				}
			}
		}

		/*else {
			$documents = $_POST['resource'][RESOURCE_DOCUMENT];
			//print_r($course->resources );
			foreach	($resource as $resource_item) {
				echo $resource_item;
				foreach($documents as $obj) {
					print_r($obj);

					if ($obj->id==$resource_item) {
						$doc = new Document($obj->id, $obj->path, $obj->comment, $obj->title, $obj->filetype, $obj->size);
						print_r($doc);
						$course->add_resource($doc);
					}
				}
			}
		}*/

		if (is_array($course->resources)) {
			foreach ($course->resources as $type => $resources) {
				switch ($type) {
					case RESOURCE_SURVEYQUESTION:
						foreach($resources as $id => $obj) {
						    if(is_array($_POST['resource'][RESOURCE_SURVEY]) && !in_array($obj->survey_id,array_keys($_POST['resource'][RESOURCE_SURVEY]))) {
								unset ($course->resources[$type][$id]);
							}
						}
						break;
					case RESOURCE_LINKCATEGORY :
					case RESOURCE_FORUMCATEGORY :
					case RESOURCE_FORUMPOST :
					case RESOURCE_FORUMTOPIC :
					case RESOURCE_QUIZQUESTION :
					case RESOURCE_DOCUMENT:
						// Mark folders to import which are not selected by the user to import,
						// but in which a document was selected.
						$documents = $_POST['resource'][RESOURCE_DOCUMENT];
						if (is_array($resources))
							foreach($resources as $id => $obj) {
								if( $obj->file_type == 'folder' && ! isset($_POST['resource'][RESOURCE_DOCUMENT][$id]) && is_array($documents)) {
									foreach($documents as $id_to_check => $post_value) {
										$obj_to_check = $resources[$id_to_check];
										$shared_path_part = substr($obj_to_check->path,0,strlen($obj->path));
										if($id_to_check != $id && $obj->path == $shared_path_part) {
											$_POST['resource'][RESOURCE_DOCUMENT][$id] = 1;
											break;
										}
									}
								}
							}
					default :
						if (is_array($resources)) {
							foreach ($resources as $id => $obj) {
								$resource_is_used_elsewhere = $course->is_linked_resource($obj);
								// check if document is in a quiz (audio/video)
								if( $type == RESOURCE_DOCUMENT && $course->has_resources(RESOURCE_QUIZ))
								{
									foreach($course->resources[RESOURCE_QUIZ] as $qid => $quiz)
									{
										if($quiz->media == $id)
										{
											$resource_is_used_elsewhere = true;
										}
									}
								}
								if (!isset ($_POST['resource'][$type][$id]) && !$resource_is_used_elsewhere)
								{
									unset ($course->resources[$type][$id]);
								}
							}
						}
				}
			}
		}
		return $course;
	}

	/**
	 * Display the form session export
	 * @param array $hidden_fiels Hidden fields to add to the form.
	 * @param boolean the document array will be serialize. This is used in the course_copy.php file
	 */
	 function display_form_session_export($list_course, $hidden_fields = null, $avoid_serialize=false) {
?>
		<script type="text/javascript">
			function exp(item) {
				el = document.getElementById('div_'+item);
				if (el.style.display=='none'){
					el.style.display='';
					document.getElementById('img_'+item).src='../img/1.gif';
				}
				else{
					el.style.display='none';
					document.getElementById('img_'+item).src='../img/0.gif';
				}
			}
			function setCheckbox(type,value) {
 				d = document.course_select_form;
 				for (i = 0; i < d.elements.length; i++) {
   					if (d.elements[i].type == "checkbox") {
						var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
 						if( name.indexOf(type) > 0 || type == 'all' ){
						     d.elements[i].checked = value;
						}
   					}
 				}
			}
			function checkLearnPath(message){
				d = document.course_select_form;
 				for (i = 0; i < d.elements.length; i++) {
 					if (d.elements[i].type == "checkbox") {
						var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
 						if( name.indexOf('learnpath') > 0){
 							if(d.elements[i].checked){
	 							setCheckbox('document',true);
	 							alert(message);
	 							break;
 							}
 						}
 					}
 				}
			}
		</script>
		<?php

		//get destination course title
		if(!empty($hidden_fields['destination_course'])) {
			require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
			$course_infos = CourseManager::get_course_information($hidden_fields['destination_course']);
			echo '<h3>';
				echo get_lang('DestinationCourse').' : '.$course_infos['title'];
			echo '</h3>';
		}

		echo '<script src="'.api_get_path(WEB_CODE_PATH).'inc/lib/javascript/upload.js" type="text/javascript"></script>';
		echo '<script type="text/javascript">var myUpload = new upload(1000);</script>';
		echo '<form method="post" id="upload_form" name="course_select_form" onsubmit="myUpload.start(\'dynamic_div\',\''.api_get_path(WEB_CODE_PATH).'img/progress_bar.gif\',\''.get_lang('PleaseStandBy').'\',\'upload_form\')">';
		echo '<input type="hidden" name="action" value="course_select_form"/>';
		foreach($list_course as $course){
			foreach ($course->resources as $type => $resources) {
				if (count($resources) > 0) {
					echo '<img id="img_'.$course->code.'" src="../img/1.gif" onclick="javascript:exp('."'$course->code'".');" />';
					echo '<b  onclick="javascript:exp('."'$course->code'".');" > '.$course->code.'</b><br />';
					echo '<div id="div_'.$course->code.'">';
					echo '<blockquote>';

                    echo '<div class="btn-group">';
					echo "<a class=\"btn\" href=\"#\" onclick=\"javascript:setCheckbox('".$course->code."',true);\" >".get_lang('All')."</a>";
                    echo "<a class=\"btn\" href=\"#\" onclick=\"javascript:setCheckbox('".$course->code."',false);\" >".get_lang('None')."</a>";
					echo '</div><br />';

					foreach ($resources as $id => $resource) {
						echo ' <label class="checkbox" for="resource['.$course->code.']['.$id.']">';
                        echo '<input type="checkbox" name="resource['.$course->code.']['.$id.']" id="resource['.$course->code.']['.$id.']"/>';
						$resource->show();
						echo '</label>';
						echo "\n";
					}
					echo '</blockquote>';
					echo '</div>';
					echo '<script type="text/javascript">exp('."'$course->code'".')</script>';
				}
			}
		}
		if ($avoid_serialize) {
			//Documents are avoided due the huge amount of memory that the serialize php function "eats" (when there are directories with hundred/thousand of files)
			// this is a known issue of serialize
			$course->resources['document']= null;
		}
		echo '<input type="hidden" name="course" value="'.base64_encode(Course::serialize($course)).'"/>';
		if (is_array($hidden_fields)) {
			foreach ($hidden_fields as $key => $value) {
				echo "\n";
				echo '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
			}
		}
		echo '<br /><button class="save" type="submit" onclick="checkLearnPath(\''.addslashes(get_lang('DocumentsWillBeAddedToo')).'\')">'.get_lang('Ok').'</button>';
		CourseSelectForm :: display_hidden_quiz_questions($course);
		CourseSelectForm :: display_hidden_scorm_directories($course);
		echo '</form>';
		echo '<div id="dynamic_div" style="display:block;margin-left:40%;margin-top:10px;height:50px;"></div>';
	}
}
?>
