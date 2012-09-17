<?php
/* For licensing terms, see /license.txt */
/**
 * Exercise library
 * @todo convert this lib into a static class
 *  
 * shows a question and its answers
 * @package chamilo.exercise
 * @author Olivier Brouckaert <oli.brouckaert@skynet.be>
 * @version $Id: exercise.lib.php 22247 2009-07-20 15:57:25Z ivantcholakov $
 * Modified by Hubert Borderiou 2011-10-21 Question Category
 */
/**
 * Code
 */
// The initialization class for the online editor is needed here.
require_once dirname(__FILE__).'/../inc/lib/fckeditor/fckeditor.php';

/**
 * Shows a question
 * 
 * @param int   question id
 * @param bool  if true only show the questions, no exercise title
 * @param bool  origin i.e = learnpath
 * @param int   current item from the list of questions
 * @param int   number of total questions
 * */
function showQuestion($questionId, $only_questions = false, $origin = false, $current_item = '', $show_title = true, $freeze = false, $user_choice = array(), $show_comment = false, $exercise_feedback = null, $show_answers = false) {
	
	// Text direction for the current language
	$is_ltr_text_direction = api_get_text_direction() != 'rtl';
	
	// Change false to true in the following line to enable answer hinting
	$debug_mark_answer = $show_answers; //api_is_allowed_to_edit() && false;
    
	// Reads question information
	if (!$objQuestionTmp = Question::read($questionId)) {
		// Question not found        
		return false;
	}
    
    if ($exercise_feedback != EXERCISE_FEEDBACK_TYPE_END) {
        $show_comment = false;
    }
    
	$answerType    = $objQuestionTmp->selectType();
	$pictureName   = $objQuestionTmp->selectPicture();
	
	$html = '';
	if ($answerType != HOT_SPOT && $answerType != HOT_SPOT_DELINEATION) {
		// Question is not a hotspot
        
		if (!$only_questions) {
			$questionDescription = $objQuestionTmp->selectDescription();
			if ($show_title) {
				Testcategory::displayCategoryAndTitle($objQuestionTmp->id);	// 				
				echo Display::div($current_item.'. '.$objQuestionTmp->selectTitle(), array('class'=>'question_title'));
			}
			if (!empty($questionDescription)) {
                echo Display::div($questionDescription, array('class'=>'question_description'));
            }
        }
		
        if (in_array($answerType, array(FREE_ANSWER, ORAL_EXPRESSION)) && $freeze) {
            return '';
        }
        
        echo '<div class="question_options">';
        
		$s = '';
        if ($show_comment) {
            $s .= '<table class="table table-bordered">';
        } else {
            //$s .= '<table class="table">';
            $s .= '<table class="exercise_options">';
        }
		// construction of the Answer object (also gets all answers details)
		$objAnswerTmp = new Answer($questionId);
        
		$nbrAnswers     = $objAnswerTmp->selectNbrAnswers();
        $course_id      = api_get_course_int_id();
        $quiz_question_options = Question::readQuestionOption($questionId, $course_id);
        
		// For "matching" type here, we need something a little bit special
		// because the match between the suggestions and the answers cannot be
		// done easily (suggestions and answers are in the same table), so we
		// have to go through answers first (elems with "correct" value to 0).
		$select_items = array();
		//This will contain the number of answers on the left side. We call them
		// suggestions here, for the sake of comprehensions, while the ones
		// on the right side are called answers
		$num_suggestions = 0;

		if ($answerType == MATCHING) {
			$x = 1; //iterate through answers
			$letter = 'A'; //mark letters for each answer
			$answer_matching = $cpt1 = array();			

			for ($answerId=1; $answerId <= $nbrAnswers; $answerId++) {
				$answerCorrect = $objAnswerTmp->isCorrect($answerId);
				$numAnswer = $objAnswerTmp->selectAutoId($answerId);
				$answer=$objAnswerTmp->selectAnswer($answerId);
				if ($answerCorrect==0) {
					// options (A, B, C, ...) that will be put into the list-box
					// have the "correct" field set to 0 because they are answer
					$cpt1[$x] = $letter;
					$answer_matching[$x]=$objAnswerTmp->selectAnswerByAutoId($numAnswer);
					$x++; $letter++;
				}
			}
			$i = 1;
			
			$select_items[0]['id'] = 0;
			$select_items[0]['letter'] = '--';
			$select_items[0]['answer'] = '';
			
			foreach ($answer_matching as $id => $value) {
				$select_items[$i]['id'] 	= $value['id'];
				$select_items[$i]['letter'] = $cpt1[$id];
				$select_items[$i]['answer'] = $value['answer'];
				$i ++;
			}
			$num_suggestions = ($nbrAnswers - $x) + 1;
			
		} elseif ($answerType == FREE_ANSWER) {
			$fck_content = isset($user_choice[0]) && !empty($user_choice[0]['answer']) ? $user_choice[0]['answer']:null;
			
			$oFCKeditor = new FCKeditor("choice[".$questionId."]") ;
			
			$oFCKeditor->ToolbarSet = 'TestFreeAnswer';
			$oFCKeditor->Width      = '100%';
			$oFCKeditor->Height     = '200';
			$oFCKeditor->Value      = $fck_content;
            $s .= '<tr><td>';
            $s .= $oFCKeditor->CreateHtml();
            $s .= '</td></tr>';
		} elseif ($answerType == ORAL_EXPRESSION) {
			//Add nanog
			if (api_get_setting('enable_nanogong') == 'true') {				
				
				require_once api_get_path(LIBRARY_PATH).'nanogong.lib.php';
				
				//@todo pass this as a parameter
				global $exercise_stat_info, $exerciseId, $exe_id;
				
				if (!empty($exercise_stat_info)) {
					$params = array(					
						'exercise_id' 	=> $exercise_stat_info['exe_exo_id'],
						'exe_id' 		=> $exercise_stat_info['exe_id'],
						'question_id'   => $questionId
					);				
				} else {
					$params = array(
						'exercise_id' 	=> $exerciseId,
						'exe_id' 		=> 'temp_exe',
						'question_id'   => $questionId
					);
				}
				
				$nano = new Nanogong($params);
				echo $nano->show_button();				
			}
			
			$oFCKeditor = new FCKeditor("choice[".$questionId."]") ;			
			$oFCKeditor->ToolbarSet = 'TestFreeAnswer';
			$oFCKeditor->Width  = '100%';
			$oFCKeditor->Height = '150';
			$oFCKeditor->ToolbarStartExpanded = false;			
			$oFCKeditor->Value	= '' ;
			$s .= '<tr><td>';
			$s .= $oFCKeditor->CreateHtml();
			$s .= '</td></tr>';
		} 
    
		// Now navigate through the possible answers, using the max number of
		// answers for the question as a limiter
		$lines_count = 1; // a counter for matching-type answers
                    
        if ($answerType == MULTIPLE_ANSWER_TRUE_FALSE || $answerType ==  MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {            
            $header = '';
            $header .= Display::tag('th', get_lang('Options'));   
            foreach ($objQuestionTmp->options as $key=>$item) {                
                $header .= Display::tag('th', $item);                           
            }                
            if ($show_comment) {                
                $header .= Display::tag('th', get_lang('Feedback'));                
            }
            $s.= Display::tag('tr', $header, array('style'=>'text-align:left;'));  
        }
        
        if ($show_comment) {
            if (in_array($answerType, array(MULTIPLE_ANSWER, MULTIPLE_ANSWER_COMBINATION, UNIQUE_ANSWER, UNIQUE_ANSWER_NO_OPTION, GLOBAL_MULTIPLE_ANSWER))) {
                $header = '';
                $header .= Display::tag('th', get_lang('Options'));
                if ($exercise_feedback == EXERCISE_FEEDBACK_TYPE_END) {
                    $header .= Display::tag('th', get_lang('Feedback'));
                }
                $s.= Display::tag('tr',$header, array('style'=>'text-align:left;'));
            }
        }

        $matching_correct_answer = 0;
        $user_choice_array = array();
        if (!empty($user_choice)) {        	
        	foreach($user_choice as $item) {
        		$user_choice_array[] = $item['answer'];
        	}
        }
                
		for ($answerId=1; $answerId <= $nbrAnswers; $answerId++) {
			$answer          = $objAnswerTmp->selectAnswer($answerId);
			$answerCorrect   = $objAnswerTmp->isCorrect($answerId);         
			$numAnswer       = $objAnswerTmp->selectAutoId($answerId);            
            $comment         = $objAnswerTmp->selectComment($answerId);

			// Unique answer
			if ($answerType == UNIQUE_ANSWER || $answerType == UNIQUE_ANSWER_NO_OPTION) {								
				$input_id = 'choice-'.$questionId.'-'.$answerId;
				if (isset($user_choice[0]['answer']) && $user_choice[0]['answer'] == $numAnswer ) {
					$attributes = array('id' =>$input_id, 'class'=>'checkbox','checked'=>1, 'selected'=>1);
				} else {
					$attributes = array('id' =>$input_id, 'class'=>'checkbox');
				}
                
                if ($debug_mark_answer) {
					if ($answerCorrect) {
						$attributes['checked'] = 1;
                        $attributes['selected'] = 1;
					}
				}
				
				$answer = Security::remove_XSS($answer, STUDENT);
                
				$s .= Display::input('hidden','choice2['.$questionId.']','0');
				//@todo fix $is_ltr_text_direction
				//<p style="float: '.($is_ltr_text_direction ? 'left' : 'right').'; padding-'.($is_ltr_text_direction ? 'right' : 'left').': 4px;">
				//$s .= '<div style="margin-'.($is_ltr_text_direction ? 'left' : 'right').': 24px;">'.
						
				$s .= '<tr><td>';
				$s .= '<span class="question_answer">';
				$s .= Display::input('radio', 'choice['.$questionId.']', $numAnswer, $attributes);
				$s .= Display::tag('label', $answer, array('for'=>$input_id)).'</span>';
				$s .= '</td>';
               
                if ($show_comment) {                
                    $s .= '<td>';
                    $s .= $comment;
                    $s .= '</td>';
                }
                
                $s .= '</tr>';
			} elseif ($answerType == MULTIPLE_ANSWER || $answerType == MULTIPLE_ANSWER_TRUE_FALSE || $answerType == GLOBAL_MULTIPLE_ANSWER) {
				$input_id = 'choice-'.$questionId.'-'.$answerId;				 
				$answer = Security::remove_XSS($answer, STUDENT);
				
				if (in_array($numAnswer, $user_choice_array)) {
					$attributes = array('id' =>$input_id, 'class'=>'checkbox','checked'=>1, 'selected'=>1);
				} else {
					$attributes = array('id' =>$input_id, 'class'=>'checkbox');
				}
                
                if ($debug_mark_answer) {
					if ($answerCorrect) {
						$attributes['checked'] = 1;
                        $attributes['selected'] = 1;
					}
				}
                
                if ($answerType == MULTIPLE_ANSWER || $answerType == GLOBAL_MULTIPLE_ANSWER) {
                    $s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" />';                
                    $s .= '<tr><td>';
                    
                    $s .= '<span class="question_answer">';                				
                    $s .= Display::tag('span', Display::input('checkbox', 'choice['.$questionId.']['.$numAnswer.']', $numAnswer, $attributes));                                        
                    $s .= Display::tag('label', $answer, array('for'=>$input_id)).'</span></td>';                	

                    if ($show_comment) {                
                        $s .= '<td>';
                        $s .= $comment;
                        $s .= '</td>';
                    }
                    $s .='</tr>';
                } elseif ($answerType == MULTIPLE_ANSWER_TRUE_FALSE) {
                    
                	$my_choice = array();
                    if (!empty($user_choice_array)) {
                        foreach ($user_choice_array as $item) {
                            $item = explode(':', $item);
                            $my_choice[$item[0]] = $item[1];
                        }
                    }
                    
                    $s .='<tr>';
                    $s .= Display::tag('td', $answer);
                    
                    if (!empty($quiz_question_options)) {
                    	foreach ($quiz_question_options as $id => $item) {
                            
                    		if (isset($my_choice[$numAnswer]) && $id == $my_choice[$numAnswer]) {
                    			$attributes = array('class'=>'checkbox','checked'=>1, 'selected'=>1);
                    		} else {
                    			$attributes = array('class'=>'checkbox');
                    		}                            
                            
                            if ($debug_mark_answer) {
                                if ($id == $answerCorrect) {
                                    $attributes['checked'] = 1;
                                    $attributes['selected'] = 1;
                                }
                            }                    
                    		$s .= Display::tag('td', Display::input('radio', 'choice['.$questionId.']['.$numAnswer.']', $id, $attributes), array('style'=>'text-align:center'));
                    	}
                    }
                    
                    if ($show_comment) {                
                        $s .= '<td>';
                        $s .= $comment;
                        $s .= '</td>';
                    }
                    $s.='</tr>';
                }
			} elseif ($answerType == MULTIPLE_ANSWER_COMBINATION) {
				// multiple answers	
				$input_id = 'choice-'.$questionId.'-'.$answerId;
				
				if (in_array($numAnswer, $user_choice_array)) {				    
				    $attributes = array('id'=>$input_id, 'class'=>'checkbox','checked'=>1, 'selected'=>1);
				} else {
				    $attributes = array('id'=>$input_id, 'class'=>'checkbox');
				}
                
                if ($debug_mark_answer) {
					if ($answerCorrect) {
						$attributes['checked'] = 1;
                        $attributes['selected'] = 1;
					}
				}
								
				$answer = Security::remove_XSS($answer, STUDENT);
				$s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" />'.
                        '<tr><td>';
				$s .= '<span class="question_answer">';				
				$s .= Display::tag('span', Display::input('checkbox', 'choice['.$questionId.']['.$numAnswer.']', 1, $attributes));				
			    $s .= Display::tag('label', $answer, array('for'=>$input_id)).'</span></td>';
					
                if ($show_comment) {                
                    $s .= '<td>';
                    $s .= $comment;
                    $s .= '</td>';
                }

                $s.= '</tr>';

            } elseif ($answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {
            	$s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" />';
            	
            	$my_choice = array();
            	if (!empty($user_choice_array)) {
            		foreach ($user_choice_array as $item) {
            			$item = explode(':', $item);
            			$my_choice[$item[0]] = $item[1];
            		}
            	}
            	$answer = Security::remove_XSS($answer, STUDENT);
            	$s .='<tr>';
            	$s .= Display::tag('td', $answer);
            	
            	foreach ($objQuestionTmp->options as $key => $item) {            		
            		if (isset($my_choice[$numAnswer]) && $key == $my_choice[$numAnswer]) {
            			$attributes = array('class'=>'checkbox','checked' => 1, 'selected' => 1);
            		} else {
            			$attributes = array('class'=>'checkbox');
            		}
                    
                    if ($debug_mark_answer) {
                        if ($key == $answerCorrect) {
                            $attributes['checked']  = 1;
                            $attributes['selected'] = 1;
                        }
                    }                    
            		$s .= Display::tag('td', Display::input('radio', 'choice['.$questionId.']['.$numAnswer.']', $key, $attributes));
            	}
				
                if ($show_comment) {                
                    $s .= '<td>';
                    $s .= $comment;
                    $s .= '</td>';
                }			
            	$s.='</tr>';
				
			} elseif ($answerType == FILL_IN_BLANKS) {                
				list($answer) = explode('::', $answer);
				
				api_preg_match_all('/\[[^]]+\]/', $answer, $teacher_answer_list);				
				
				if (isset($user_choice[0]['answer'])) {
					api_preg_match_all('/\[[^]]+\]/', $user_choice[0]['answer'], $student_answer_list);
					$student_answer_list = $student_answer_list[0];
				}
                
                if ($debug_mark_answer) {                    
					$student_answer_list = $teacher_answer_list[0];                    
                }
                
				if (!empty($teacher_answer_list) && !empty($student_answer_list)) {
				    $teacher_answer_list = $teacher_answer_list[0];				    
				    $i = 0;				    
				    foreach ($teacher_answer_list as $teacher_item) {				    	
				        $value = null;
				        if (isset($student_answer_list[$i]) && !empty($student_answer_list[$i])) {
				        	//Cleaning student answer list
				            $value = strip_tags($student_answer_list[$i]);				            				            
				            $value = api_substr($value,1, api_strlen($value)-2);
				            $value = explode('/', $value);
				            if (!empty($value[0])) {
				            	$value = trim($value[0]);
				            	$value = str_replace('&nbsp;', '',  $value);
				            }
				            $answer = api_preg_replace('/\['.$teacher_item.'+\]/', Display::input('text', "choice[$questionId][]", $value), $answer);				            
				        }				        				        
				        $i++;				        
				    }
				} else {
					$answer = api_preg_replace('/\[[^]]+\]/', Display::input('text', "choice[$questionId][]", '', $attributes), $answer);
				}				
				$s .= '<tr><td>'.$answer.'</td></tr>';
            } elseif ($answerType == MATCHING) {
				// matching type, showing suggestions and answers
				// TODO: replace $answerId by $numAnswer
				
				if ($answerCorrect != 0) {
					// only show elements to be answered (not the contents of
					// the select boxes, who are corrrect = 0)
					$s .= '<tr><td width="45%" valign="top">';
					$parsed_answer = $answer;                    
					//left part questions
					$s .= ' <span style="float:left; width:8%;"><b>'.$lines_count.'</b>.&nbsp;</span>
						 	<span style="float:left; width:92%;">'.$parsed_answer.'</span></td>';
					//middle part (matches selects)					
					
					$s .= '<td width="10%" valign="top" align="center">&nbsp;&nbsp;
				            <select name="choice['.$questionId.']['.$numAnswer.']">';
					
					// fills the list-box
					foreach ($select_items as $key=>$val) {
						// set $debug_mark_answer to true at function start to
						// show the correct answer with a suffix '-x'
						$selected = '';
						if ($debug_mark_answer) {
							if ($val['id'] == $answerCorrect) {								
								$selected = 'selected="selected"';
							}
						}						
						if (isset($user_choice[$matching_correct_answer]) && $val['id'] == $user_choice[$matching_correct_answer]['answer']) {
						    $selected = 'selected="selected"';
						}
						$s .= '<option value="'.$val['id'].'" '.$selected.'>'.$val['letter'].$help.'</option>';						
				
					}  // end foreach()

					$s .= '</select></td>';
					//print_r($select_items);
					//right part (answers)
					$s.='<td width="45%" valign="top" >';
					if (isset($select_items[$lines_count])) {
						$s.='<span style="float:left; width:5%;"><b>'.$select_items[$lines_count]['letter'].'.</b></span>'.
							 '<span style="float:left; width:95%;">'.$select_items[$lines_count]['answer'].'</span>';
					} else {
						$s.='&nbsp;';
					}
					$s .= '</td>';
					$s .= '</tr>';
					$lines_count++;
					//if the left side of the "matching" has been completely
					// shown but the right side still has values to show...
					if (($lines_count -1) == $num_suggestions) {
						// if it remains answers to shown at the right side
						while (isset($select_items[$lines_count])) {
							$s .= '<tr>
								  <td colspan="2"></td>
								  <td valign="top">';
							$s.='<b>'.$select_items[$lines_count]['letter'].'.</b> '.$select_items[$lines_count]['answer'];
							$s.="</td>
							</tr>";
							$lines_count++;
						}	// end while()
					}  // end if()
					$matching_correct_answer++;
				}
			}
		}	// end for()
		$s .= '</table>';		
		$s .= '</div>';
		

		// destruction of the Answer object
		unset($objAnswerTmp);

		// destruction of the Question object
		unset($objQuestionTmp);

		if ($origin != 'export') {
			echo $s;
		} else {
			return $s;
		}
	} elseif ($answerType == HOT_SPOT || $answerType == HOT_SPOT_DELINEATION) {
		// Question is a HOT_SPOT        
        //checking document/images visibility
        if (api_is_platform_admin() || api_is_course_admin()) {
            require_once api_get_path(LIBRARY_PATH).'document.lib.php';
            $course = api_get_course_info();        
            $doc_id = DocumentManager::get_document_id($course, '/images/'.$pictureName);  
            if (is_numeric($doc_id)) {              
                $images_folder_visibility = api_get_item_visibility($course,'document', $doc_id, api_get_session_id());                 
                if (!$images_folder_visibility) {
                    //This message is shown only to the course/platform admin if the image is set to visibility = false
                    Display::display_warning_message(get_lang('ChangeTheVisibilityOfTheCurrentImage'));
                }
            }
        }
		$questionName         = $objQuestionTmp->selectTitle();
		$questionDescription  = $objQuestionTmp->selectDescription();
        
        if ($freeze) {
            echo Display::img($objQuestionTmp->selectPicturePath());
            return;
        }        

		// Get the answers, make a list
		$objAnswerTmp         = new Answer($questionId);
		$nbrAnswers           = $objAnswerTmp->selectNbrAnswers();

		// get answers of hotpost
		$answers_hotspot = array();
		for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
			$answers = $objAnswerTmp->selectAnswerByAutoId($objAnswerTmp->selectAutoId($answerId));
			$answers_hotspot[$answers['id']] = $objAnswerTmp->selectAnswer($answerId);
		}

		// display answers of hotpost order by id
		$answer_list = '<div style="padding: 10px; margin-left: 0px; border: 1px solid #A4A4A4; height: 408px; width: 200px;"><b>'.get_lang('HotspotZones').'</b><dl>';
		if (!empty($answers_hotspot)) {
			ksort($answers_hotspot);
			foreach ($answers_hotspot as $key => $value) {
				$answer_list .= '<dt>'.$key.'.- '.$value.'</dt><br />';
			}
		}
		$answer_list .= '</dl></div>';
		
		if ($answerType == HOT_SPOT_DELINEATION) {
			$answer_list='';
			$swf_file = 'hotspot_delineation_user';
			$swf_height = 405;			
		} else {
			$swf_file = 'hotspot_user';
			$swf_height = 436;
		}

		if (!$only_questions) {
            if ($show_title) {
				Testcategory::displayCategoryAndTitle($objQuestionTmp->id);
                echo '<div class="question_title">'.$current_item.'. '.$questionName.'</div>';
            }
			//@todo I need to the get the feedback type
			echo '<input type="hidden" name="hidden_hotspot_id" value="'.$questionId.'" />';
			echo '<table class="exercise_questions" >
				  <tr>
			  		<td valign="top" colspan="2">';
			echo $questionDescription;
			echo '</td></tr>';
		}        
		$canClick = isset($_GET['editQuestion']) ? '0' : (isset($_GET['modifyAnswers']) ? '0' : '1');
        
		$s .= '<script type="text/javascript" src="../plugin/hotspot/JavaScriptFlashGateway.js"></script>
						<script src="../plugin/hotspot/hotspot.js" type="text/javascript" ></script>
						<script type="text/javascript">
						<!--
						// Globals
						// Major version of Flash required
						var requiredMajorVersion = 7;
						// Minor version of Flash required
						var requiredMinorVersion = 0;
						// Minor version of Flash required
						var requiredRevision = 0;
						// the version of javascript supported
						var jsVersion = 1.0;
						// -->
						</script>
						<script language="VBScript" type="text/vbscript">
						<!-- // Visual basic helper required to detect Flash Player ActiveX control version information
						Function VBGetSwfVer(i)
						  on error resume next
						  Dim swControl, swVersion
						  swVersion = 0

						  set swControl = CreateObject("ShockwaveFlash.ShockwaveFlash." + CStr(i))
						  if (IsObject(swControl)) then
						    swVersion = swControl.GetVariable("$version")
						  end if
						  VBGetSwfVer = swVersion
						End Function
						// -->
						</script>

						<script language="JavaScript1.1" type="text/javascript">
						<!-- // Detect Client Browser type
						var isIE  = (navigator.appVersion.indexOf("MSIE") != -1) ? true : false;
						var isWin = (navigator.appVersion.toLowerCase().indexOf("win") != -1) ? true : false;
						var isOpera = (navigator.userAgent.indexOf("Opera") != -1) ? true : false;
						jsVersion = 1.1;
						// JavaScript helper required to detect Flash Player PlugIn version information
						function JSGetSwfVer(i) {
							// NS/Opera version >= 3 check for Flash plugin in plugin array
							if (navigator.plugins != null && navigator.plugins.length > 0) {
								if (navigator.plugins["Shockwave Flash 2.0"] || navigator.plugins["Shockwave Flash"]) {
									var swVer2 = navigator.plugins["Shockwave Flash 2.0"] ? " 2.0" : "";
						      		var flashDescription = navigator.plugins["Shockwave Flash" + swVer2].description;
									descArray = flashDescription.split(" ");
									tempArrayMajor = descArray[2].split(".");
									versionMajor = tempArrayMajor[0];
									versionMinor = tempArrayMajor[1];
									if ( descArray[3] != "" ) {
										tempArrayMinor = descArray[3].split("r");
									} else {
										tempArrayMinor = descArray[4].split("r");
									}
						      		versionRevision = tempArrayMinor[1] > 0 ? tempArrayMinor[1] : 0;
						            flashVer = versionMajor + "." + versionMinor + "." + versionRevision;
						      	} else {
									flashVer = -1;
								}
							}
							// MSN/WebTV 2.6 supports Flash 4
							else if (navigator.userAgent.toLowerCase().indexOf("webtv/2.6") != -1) flashVer = 4;
							// WebTV 2.5 supports Flash 3
							else if (navigator.userAgent.toLowerCase().indexOf("webtv/2.5") != -1) flashVer = 3;
							// older WebTV supports Flash 2
							else if (navigator.userAgent.toLowerCase().indexOf("webtv") != -1) flashVer = 2;
							// Can\'t detect in all other cases
							else
							{
								flashVer = -1;
							}
							return flashVer;
						}
						// When called with reqMajorVer, reqMinorVer, reqRevision returns true if that version or greater is available

						function DetectFlashVer(reqMajorVer, reqMinorVer, reqRevision) {
						 	reqVer = parseFloat(reqMajorVer + "." + reqRevision);
						   	// loop backwards through the versions until we find the newest version
							for (i=25;i>0;i--) {
								if (isIE && isWin && !isOpera) {
									versionStr = VBGetSwfVer(i);
								} else {
									versionStr = JSGetSwfVer(i);
								}
								if (versionStr == -1 ) {
									return false;
								} else if (versionStr != 0) {
									if(isIE && isWin && !isOpera) {
										tempArray         = versionStr.split(" ");
										tempString        = tempArray[1];
										versionArray      = tempString .split(",");
									} else {
										versionArray      = versionStr.split(".");
									}
									versionMajor      = versionArray[0];
									versionMinor      = versionArray[1];
									versionRevision   = versionArray[2];

									versionString     = versionMajor + "." + versionRevision;   // 7.0r24 == 7.24
									versionNum        = parseFloat(versionString);
						        	// is the major.revision >= requested major.revision AND the minor version >= requested minor
									if ( (versionMajor > reqMajorVer) && (versionNum >= reqVer) ) {
										return true;
									} else {
										return ((versionNum >= reqVer && versionMinor >= reqMinorVer) ? true : false );
									}
								}
							}
						}
						// -->
						</script>';
		$s .= '<tr><td valign="top" colspan="2" width="520"><table><tr><td width="520">
					<script>
						<!--
						// Version check based upon the values entered above in "Globals"
						var hasReqestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);

						// Check to see if the version meets the requirements for playback
						if (hasReqestedVersion) {  // if we\'ve detected an acceptable version
						    var oeTags = \'<object type="application/x-shockwave-flash" data="../plugin/hotspot/'.$swf_file.'.swf?modifyAnswers='.$questionId.'&amp;canClick:'.$canClick.'" width="600" height="'.$swf_height.'">\'
						    			+ \'<param name="wmode" value="transparent">\'
										+ \'<param name="movie" value="../plugin/hotspot/'.$swf_file.'.swf?modifyAnswers='.$questionId.'&amp;canClick:'.$canClick.'" />\'
										+ \'<\/object>\';
						    document.write(oeTags);   // embed the Flash Content SWF when all tests are passed
						} else {  // flash is too old or we can\'t detect the plugin
							var alternateContent = "Error<br \/>"
								+ "Hotspots requires Macromedia Flash 7.<br \/>"
								+ "<a href=\"http://www.macromedia.com/go/getflash/\">Get Flash<\/a>";
							document.write(alternateContent);  // insert non-flash content
						}
						// -->
					</script>
					</td>
					<td valign="top" align="left">'.$answer_list.'</td></tr>
					</table>
		</td></tr>';        
		echo $s;
	}
	echo '</table>';
	return $nbrAnswers;
}

function get_exercise_track_exercise_info($exe_id) {
	$TBL_EXERCICES         	= Database::get_course_table(TABLE_QUIZ_TEST);
	$TBL_TRACK_EXERCICES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$exe_id = intval($exe_id);
    $result = array();
    if (!empty($exe_id)) {
	   $sql_fb_type = 'SELECT * FROM '.$TBL_EXERCICES.' as e INNER JOIN '.$TBL_TRACK_EXERCICES.' as te  ON (e.id=te.exe_exo_id) WHERE te.exe_id='.$exe_id;
	   $res_fb_type = Database::query($sql_fb_type);
	   $result      = Database::fetch_array($res_fb_type, 'ASSOC');
    }
	return $result;	
}


/**
 * Validates the time control key
 */
function exercise_time_control_is_valid($exercise_id, $lp_id = 0 , $lp_item_id = 0) {
    $course_id = api_get_course_int_id();	
	$exercise_id = intval($exercise_id);
	$TBL_EXERCICES =  Database::get_course_table(TABLE_QUIZ_TEST);
	$sql 	= "SELECT expired_time FROM $TBL_EXERCICES WHERE c_id = $course_id AND id = $exercise_id";
	$result = Database::query($sql);
	$row	= Database::fetch_array($result, 'ASSOC');
	if (!empty($row['expired_time']) ) {
		$current_expired_time_key = get_time_control_key($exercise_id, $lp_id, $lp_item_id);        
		if (isset($_SESSION['expired_time'][$current_expired_time_key])) {                	
	        $current_time = time();
			$expired_time = api_strtotime($_SESSION['expired_time'][$current_expired_time_key], 'UTC');
			$total_time_allowed = $expired_time + 30;
			//error_log('expired time converted + 30: '.$total_time_allowed);
			//error_log('$current_time: '.$current_time);
	        if ($total_time_allowed < $current_time) {
	        	return false;
	        }
	        return true;
		} else {
			return false;
		}
	} else {
		return true;
	}
}

/**
	Deletes the time control token 
*/
function exercise_time_control_delete($exercise_id,  $lp_id = 0 , $lp_item_id = 0) {	
	$current_expired_time_key = get_time_control_key($exercise_id, $lp_id, $lp_item_id);
	unset($_SESSION['expired_time'][$current_expired_time_key]);	
}

/**
	Generates the time control key
*/
function get_time_control_key($exercise_id, $lp_id = 0, $lp_item_id = 0) {
	$exercise_id = intval($exercise_id);
    $lp_id = intval($lp_id);
    $lp_item_id = intval($lp_item_id);
	return api_get_course_int_id().'_'.api_get_session_id().'_'.$exercise_id.'_'.api_get_user_id().'_'.$lp_id.'_'.$lp_item_id;
}

/**
 * Get session time control
 */
function get_session_time_control_key($exercise_id, $lp_id = 0, $lp_item_id = 0) {
    $time_control_key = get_time_control_key($exercise_id, $lp_id, $lp_item_id);
    $return_value = $_SESSION['expired_time'][$time_control_key];
    return $return_value;
}

/**
 * Gets count of exam results
 * @todo this function should be moved in a library  + no global calls 
 */
function get_count_exam_results($exercise_id, $extra_where_conditions) {
    $count = get_exam_results_data(null, null, null, null, $exercise_id, $extra_where_conditions, true);
    return $count;
}

function get_count_exam_hotpotatoes_results($in_hotpot_path) {
    return get_exam_results_hotpotatoes_data(0, 0, '', '', $in_hotpot_path, true);
}

//function get_exam_results_hotpotatoes_data($from, $number_of_items, $column, $direction, $exercise_id, $extra_where_conditions = null, $get_count = false) {
function get_exam_results_hotpotatoes_data($in_from, $in_number_of_items, $in_column, $in_direction, $in_hotpot_path, $in_get_count = false) {
    
    $tab_res = array();
    $course_code = api_get_course_id();
    // by default in_column = 1 If parameters given, it is the name of the column witch is the bdd field name
    if ($in_column == 1) {
        $in_column = 'firstname';
    }
    
    $TBL_TRACK_HOTPOTATOES      = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);    
    $TBL_GROUP_REL_USER         = Database :: get_course_table(TABLE_GROUP_USER);
    $TBL_GROUP                  = Database :: get_course_table(TABLE_GROUP);
    $TBL_USER                   = Database :: get_main_table(TABLE_MAIN_USER);    
    
    $sql .= "SELECT * FROM $TBL_TRACK_HOTPOTATOES thp JOIN $TBL_USER u ON thp.exe_user_id = u.user_id WHERE thp.exe_cours_id = '$course_code' AND exe_name LIKE '$in_hotpot_path%'";

    // just count how many answers
    if ($in_get_count) {
        $res = Database::query($sql);
        return Database::num_rows($res);
    }
    
    // get a number of sorted results
    $sql .= " ORDER BY $in_column $in_direction  LIMIT $in_from, $in_number_of_items";
    
    $res = Database::query($sql);
    while ($data = Database::fetch_array($res)) {
        $tab_one_res = array();
        $tab_one_res['firstname'] = $data['firstname'];
        $tab_one_res['lastname'] = $data['lastname'];
        $tab_one_res['username'] = $data['username'];
        $tab_one_res['group_name'] = implode("<br/>",GroupManager::get_user_group_name($data['user_id']));
        $tab_one_res['exe_date'] = $data['exe_date'];
        $tab_one_res['score'] = $data['exe_result'].'/'.$data['exe_weighting'];
        $tab_one_res['actions'] = "";
        $tab_res[] = $tab_one_res;
    }
    return $tab_res;
}

/**
 * Gets the exam'data results
 * @todo this function should be moved in a library  + no global calls 
 */
function get_exam_results_data($from, $number_of_items, $column, $direction, $exercise_id, $extra_where_conditions = null, $get_count = false) {
    
                        
    //@todo replace all this globals
    global $documentPath, $filter;
	
	if (empty($extra_where_conditions)) {
		$extra_where_conditions = "1 = 1 ";
	}
    
    $course_id = api_get_course_int_id();
    $course_code = api_get_course_id();
                
   	$is_allowedToEdit           = api_is_allowed_to_edit(null,true) || api_is_allowed_to_edit(true) || api_is_drh();
	    
    $TBL_USER                   = Database :: get_main_table(TABLE_MAIN_USER);    
    $TBL_EXERCICES              = Database :: get_course_table(TABLE_QUIZ_TEST);    
	$TBL_GROUP_REL_USER         = Database :: get_course_table(TABLE_GROUP_USER);
    $TBL_GROUP                  = Database :: get_course_table(TABLE_GROUP);
	
    $TBL_TRACK_EXERCICES        = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $TBL_TRACK_HOTPOTATOES      = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);    
    $TBL_TRACK_ATTEMPT_RECORDING= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);    
    
    $session_id_and = ' AND te.session_id = '.api_get_session_id().' ';
    
    $exercise_id = intval($exercise_id);
    
    $exercise_where = '';
    if (!empty($exercise_id)) {
        $exercise_where .= ' AND te.exe_exo_id = '.$exercise_id.'  ';
    } 
    
    $hotpotatoe_where = '';
    if (!empty($_GET['path'])) {
        $hotpotatoe_path = Database::escape_string($_GET['path']);
        $hotpotatoe_where .= ' AND exe_name = "'.$hotpotatoe_path.'"  ';
    }  
         
    // sql for chamilo-type tests for teacher / tutor view		
    $sql_inner_join_tbl_track_exercices = " (
                                            SELECT DISTINCT ttte.*, if(tr.exe_id,1, 0) as revised 
                                            FROM $TBL_TRACK_EXERCICES ttte LEFT JOIN $TBL_TRACK_ATTEMPT_RECORDING tr 
                                            ON (ttte.exe_id = tr.exe_id) 
                                            WHERE exe_cours_id = '$course_code' AND
                                                  exe_exo_id = $exercise_id AND
                                                  ttte.session_id = ".api_get_session_id()."
                                            )";    	
    if ($is_allowedToEdit) {
        //Teacher view		
        if (isset($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
            //$exercise_where_query = ' te.exe_exo_id = ce.id AND ';
        }

		$sqlFromOption                      = "";
		$sqlWhereOption                     = "";           // for hpsql
	    		
        //@todo fix to work with COURSE_RELATION_TYPE_RRHH in both queries
        
        //Hack in order to filter groups
        $sql_inner_join_tbl_user = '';
                
        if (strpos($extra_where_conditions, 'group_id')) {
            $sql_inner_join_tbl_user = " 
            (
                SELECT u.user_id, firstname, lastname, email, username, g.name as group_name, g.id as group_id
                FROM $TBL_USER u 
                INNER JOIN $TBL_GROUP_REL_USER gru ON ( gru.user_id = u.user_id AND gru.c_id=".$course_id.")
                INNER JOIN $TBL_GROUP g ON (gru.group_id = g.id)
            )";

        }
           
        if (strpos($extra_where_conditions, 'group_all')) {   
            
            $extra_where_conditions = str_replace("AND (  group_id = 'group_all'  )", '', $extra_where_conditions);
            $extra_where_conditions = str_replace("AND group_id = 'group_all'", '', $extra_where_conditions);
            $extra_where_conditions = str_replace("group_id = 'group_all' AND", '', $extra_where_conditions);
                        
            $sql_inner_join_tbl_user = " 
            (
                SELECT u.user_id, firstname, lastname, email, username, '' as group_name, '' as group_id
                FROM $TBL_USER u
            )";
            $sql_inner_join_tbl_user = null;
        }
        
        if (strpos($extra_where_conditions, 'group_none')) {
            $extra_where_conditions = str_replace("AND (  group_id = 'group_none'  )", "AND (  group_id is null  )", $extra_where_conditions);
            $extra_where_conditions = str_replace("AND group_id = 'group_none'", "AND (  group_id is null  )", $extra_where_conditions);
            $sql_inner_join_tbl_user = " 
            (
                SELECT u.user_id, firstname, lastname, email, username, g.name as group_name, g.id as group_id
                FROM $TBL_USER u
                LEFT OUTER JOIN $TBL_GROUP_REL_USER gru ON ( gru.user_id = u.user_id AND gru.c_id=".$course_id." ) 
                LEFT OUTER JOIN $TBL_GROUP g ON (gru.group_id = g.id AND g.c_id = ".$course_id.")
            )";
        }
        
        //All
        $is_empty_sql_inner_join_tbl_user = false;

        if (empty($sql_inner_join_tbl_user)) {
            $is_empty_sql_inner_join_tbl_user = true;
             $sql_inner_join_tbl_user = " 
            (
                SELECT u.user_id, firstname, lastname, email, username, ' ' as group_name, '' as group_id
                FROM $TBL_USER u                 
            )";
        }

        
        $sqlFromOption = " , $TBL_GROUP_REL_USER AS gru ";
        $sqlWhereOption = "  AND gru.c_id = ".api_get_course_int_id()." AND gru.user_id = user.user_id ";
		
        $first_and_last_name = api_is_western_name_order() ? "firstname, lastname" : "lastname, firstname";
        
        if ($get_count) {
            $sql_select = "SELECT count(te.exe_id) ";
        } else {
            $sql_select = "SELECT DISTINCT
                    user_id, 
                    $first_and_last_name, 
                    ce.title, 
                    username,
                    te.exe_result, 
                    te.exe_weighting,
                    te.exe_date, 
                    te.exe_id, 
                    email as exemail, 
                    te.start_date, 
                    steps_counter, 
                    exe_user_id,
                    te.exe_duration, 
                    propagate_neg,
                    revised,
                    group_name,
                    group_id";
        }
        
        $sql = " $sql_select            
                FROM $TBL_EXERCICES AS ce 
                INNER JOIN $sql_inner_join_tbl_track_exercices AS te ON (te.exe_exo_id = ce.id) 
                INNER JOIN $sql_inner_join_tbl_user  AS user ON (user.user_id = exe_user_id)
                WHERE $extra_where_conditions AND
                    te.status != 'incomplete' 
                    AND te.exe_cours_id='" . api_get_course_id() . "' $session_id_and 
                    AND ce.active <>-1 
                    AND orig_lp_id = 0 
                    AND orig_lp_item_id = 0
                    AND ce.c_id=".api_get_course_int_id()."					
                    $exercise_where ";
         
        // sql for hotpotatoes tests for teacher / tutor view
        
        if ($get_count) {
            $hpsql_select = "SELECT count(username)";
        } else {
            $hpsql_select = "SELECT 
                    $first_and_last_name , 
                    username,
                    tth.exe_name, 
                    tth.exe_result , 
                    tth.exe_weighting, 
                    tth.exe_date";
        }
        
        $hpsql = " $hpsql_select
                FROM 
                    $TBL_TRACK_HOTPOTATOES tth, 
                    $TBL_USER user
                    $sqlFromOption
                WHERE  
                    user.user_id=tth.exe_user_id 
                    AND tth.exe_cours_id = '" . api_get_course_id()."'  
                    $hotpotatoe_where 
                    $sqlWhereOption
					AND $where_condition
                ORDER BY 
                    tth.exe_cours_id ASC, 
                    tth.exe_date DESC";
    }
    
    if ($get_count) {
        $resx = Database::query($sql);
        $rowx = Database::fetch_row($resx,'ASSOC');
        return $rowx[0];
    }
    
    $teacher_list = CourseManager::get_teacher_list_from_course_code(api_get_course_id());
    $teacher_id_list = array();
    foreach ($teacher_list as $teacher) {
    	$teacher_id_list[] = $teacher['user_id'];
    }    
    
    //Simple exercises
    if (empty($hotpotatoe_where)) {
        $column             = !empty($column) ? Database::escape_string($column) : null;
        $from               = intval($from);
        $number_of_items    = intval($number_of_items);
        
        if (!empty($column)) {
            $sql .= " ORDER BY $column $direction ";
        }
        $sql .= " LIMIT $from, $number_of_items";			
        
        $results = array();        
        $resx = Database::query($sql);
        while ($rowx = Database::fetch_array($resx,'ASSOC')) {
            $results[] = $rowx;
        }
    
        $list_info = array();
        
        $group_list = GroupManager::get_group_list();
        $clean_group_list = array();
        
        if (!empty($group_list)) {
            foreach ($group_list as $group) {
                $clean_group_list[$group['id']] = $group['name'];
            }
        }
            
        if (is_array($results)) {
			
            $users_array_id = array();
            if ($_GET['gradebook'] == 'view') {                
                $from_gradebook = true;
            }
            $sizeof = count($results);
    
            $user_list_id = array ();                        
                        
            $locked = api_resource_is_locked_by_gradebook($exercise_id, LINK_EXERCISE);
            
            //Looping results
            for ($i = 0; $i < $sizeof; $i++) {               
                $revised = $results[$i]['revised'];	
                
                if ($from_gradebook && ($is_allowedToEdit)) {
                    if (in_array($results[$i]['username'] . $results[$i]['firstname'] . $results[$i]['lastname'], $users_array_id)) {
                        continue;
                    }
                    $users_array_id[] = $results[$i]['username'] . $results[$i]['firstname'] . $results[$i]['lastname'];
                }
                                 
                //Add all groups by user
                $group_name_list = null;

                if ($is_empty_sql_inner_join_tbl_user) {
                    $group_list = GroupManager::get_group_ids(api_get_course_int_id(), $results[$i]['user_id']);

                    foreach ($group_list as $id) {               
                        $group_name_list .= $clean_group_list[$id].'<br/>';
                    }
                    $results[$i]['group_name'] = $group_name_list;
                }                
                
                $results[$i]['exe_duration'] =  !empty($results[$i]['exe_duration']) ? round($results[$i]['exe_duration'] / 60) : 0;

                $user_list_id[] = $results[$i]['exe_user_id'];
                $id = $results[$i]['exe_id'];   
               
                $dt = api_convert_and_format_date($results[$i]['exe_weighting']);                
                
                // we filter the results if we have the permission to
                if (isset($results[$i]['results_disabled'])) {
                    $result_disabled = intval($results[$i]['results_disabled']);
                }  else {
                    $result_disabled = 0;
                }
    
                if ($result_disabled == 0) {      
                    
                    $my_res     = $results[$i]['exe_result'];
                    $my_total   = $results[$i]['exe_weighting'];
                    
                    $results[$i]['start_date']  =   api_get_local_time($results[$i]['start_date']);
                    $results[$i]['exe_date']    =   api_get_local_time($results[$i]['exe_date']);                    
                    
                    if (!$results[$i]['propagate_neg'] && $my_res < 0) {
                        $my_res = 0;
                    }
                    $score = show_score($my_res, $my_total);
                    
                    $actions = '';
                    if ($is_allowedToEdit) {
                        if (isset($teacher_id_list)) {
                            if (in_array($results[$i]['exe_user_id'], $teacher_id_list)) {
                                $actions .= Display::return_icon('teachers.gif', get_lang('Teacher'));
                            }
                        }
                        if ($revised) {
                            $actions .= "<a href='exercise_show.php?".api_get_cidreq()."&action=edit&id=$id'>".Display :: return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL);
                            $actions .= '&nbsp;';
                        } else {
                            $actions .="<a href='exercise_show.php?".api_get_cidreq()."&action=qualify&id=$id'>".Display :: return_icon('quiz.gif', get_lang('Qualify'));
                            $actions .='&nbsp;';
                        }
                        $actions .="</a>";
                                                
                        if ($filter == 2) {
                            $actions .=' <a href="exercise_history.php?'.api_get_cidreq().'&exe_id=' . $id . '">' .Display :: return_icon('history.gif', get_lang('ViewHistoryChange')).'</a>';
                        }                        
                        
                        //Admin can always delete the attempt
                        if ($locked == false || api_is_platform_admin()) {
                            $actions .=' <a href="exercise_report.php?'.api_get_cidreq().'&filter_by_user='.intval($_GET['filter_by_user']).'&filter=' . $filter . '&exerciseId='.$exercise_id.'&delete=delete&did=' . $id . '" onclick="javascript:if(!confirm(\'' . sprintf(get_lang('DeleteAttempt'), $user, $dt) . '\')) return false;">'.Display :: return_icon('delete.png', get_lang('Delete')).'</a>';                            
                            $actions .='&nbsp;';
                        }
                        
                    } else {
                    	$attempt_url 	= api_get_path(WEB_CODE_PATH).'exercice/result.php?'.api_get_cidreq().'&id='.$results[$i]['exe_id'].'&id_session='.api_get_session_id().'&height=500&width=750';
                    	$attempt_link 	= Display::url(get_lang('Show'), $attempt_url, array('class'=>'ajax btn'));                    	
                    	$actions .= $attempt_link;
                    }                    
                    
                    if ($revised) {
                        $revised = Display::label(get_lang('Validated'), 'success');                            
                    } else {
                        $revised = Display::label(get_lang('NotValidated'), 'info');
                    }
                    
                    if ($is_allowedToEdit) {                        					
						$results[$i]['status']  =  $revised;
						$results[$i]['score']   =  $score;
						$results[$i]['actions'] =  $actions;
						$list_info[] = $results[$i];                        
                    } else {
                        $results[$i]['status']  =  $revised;
						$results[$i]['score']   =  $score;							
                        $results[$i]['actions'] =  $actions;                        
						$list_info[] = $results[$i];
                    }
                }
            }
        }
    } else {
        //echo $hpsql; var_dump($hpsql);
        $hpresults = getManyResultsXCol($hpsql, 6);        
   
        // Print HotPotatoes test results.
        if (is_array($hpresults)) {
            
            for ($i = 0; $i < sizeof($hpresults); $i++) {               
                $hp_title = GetQuizName($hpresults[$i][3], $documentPath);                
                if ($hp_title == '') {
                    $hp_title = basename($hpresults[$i][3]);
                }
                //var_dump($hpresults[$i]);
                
                $hp_date = api_get_local_time($hpresults[$i][6], null, date_default_timezone_get());
                $hp_result = round(($hpresults[$i][4] / ($hpresults[$i][5] != 0 ? $hpresults[$i][5] : 1)) * 100, 2).'% ('.$hpresults[$i][4].' / '.$hpresults[$i][5].')';
                if ($is_allowedToEdit) {                   
                    $list_info[] = array($hpresults[$i][0], $hpresults[$i][1], $hpresults[$i][2], '',  $hp_title, '-',  $hp_date , $hp_result , '-');
                } else {
                    $list_info[] = array($hp_title, '-', $hp_date , $hp_result , '-');
                }
            }
        }
    }	
    
    return $list_info;
}


/**
 * Converts the score with the exercise_max_note and exercise_min_score the platform settings + formats the results using the float_format function
 * 
 * @param   float   score
 * @param   float   weight
 * @param   bool    show porcentage or not
 * @param	 bool	use or not the platform settings
 * @return  string  an html with the score modified
 */
function show_score($score, $weight, $show_percentage = true, $use_platform_settings = true) {
    if (is_null($score) && is_null($weight)) {
        return '-';
    }
    
    $max_note =  api_get_setting('exercise_max_score');
    $min_note =  api_get_setting('exercise_min_score');
    
    if ($use_platform_settings) {
        if ($max_note != '' && $min_note != '') {        
            if (!empty($weight) && intval($weight) != 0) {
    	       $score        = $min_note + ($max_note - $min_note) * $score /$weight;
            } else {
               $score        = $min_note;
            }            
            $weight         = $max_note;
        }
    }
    $score_rounded = float_format($score, 1);    
    $weight = float_format($weight, 1);    
    
    $percentage = float_format(($score / ($weight != 0 ? $weight : 1)) * 100, 1);
    
    $html  = '';
    if ($show_percentage) {        
        $parent = '(' . $score_rounded . ' / ' . $weight . ')';
        $html = $percentage." %  $parent";	
    } else {    
    	$html = $score_rounded . ' / ' . $weight;
    }  
    $html  = Display::span($html, array('class' => 'score_exercise'));
    return $html;	
}

function is_success_exercise_result($score, $weight, $pass_percentage) {
    $percentage = float_format(($score / ($weight != 0 ? $weight : 1)) * 100, 1);    
    if (isset($pass_percentage) && !empty($pass_percentage)) {
        if ($percentage >= $pass_percentage) {
            return true;
        }
    }
    return false;        
}

function show_success_message($score, $weight, $pass_percentage) {
    $res = "";
    if (is_pass_pourcentage_enabled($pass_percentage)) {
        $is_success = is_success_exercise_result($score, $weight, $pass_percentage);
        
        $icon = '';
        if ($is_success) {        
            //$html .= Display::return_message(get_lang('CongratulationsYouPassedTheTest'), 'success');
            $html = get_lang('CongratulationsYouPassedTheTest');
            $icon = Display::return_icon('completed.png', get_lang('Correct'), array(), ICON_SIZE_MEDIUM);
        } else {
            //$html .= Display::return_message(get_lang('YouDidNotReachTheMinimumScore'), 'warning');
            $html = get_lang('YouDidNotReachTheMinimumScore');
            $icon = Display::return_icon('warning.png', get_lang('Wrong'), array(), ICON_SIZE_MEDIUM);
        }    
        $html = Display::tag('h4', $html);
        $html .= Display::tag('h5', $icon, array('style' => 'width:40px; padding:5px 10px 0px 0px'));
        $res = $html;
    }
    return $res;
}

/**
 * Return true if pass_pourcentage activated (we use the pass pourcentage feature
 * return false if pass_percentage = 0 (we don't use the pass pourcentage feature
 * @param $in_pass_pourcentage
 * @return boolean
 * In this version, pass_percentage and show_success_message are disabled if
 * pass_percentage is set to 0
 */
function is_pass_pourcentage_enabled($in_pass_pourcentage) {
    return $in_pass_pourcentage > 0;
}

/**
 * Converts a numeric value in a percentage example 0.66666 to 66.67 %
 * @param $value
 * @return float Converted number
 */
function convert_to_percentage($value) {
    $return = '-';
    if ($value != '') {
        $return = float_format($value * 100, 1).' %';
    }
    return $return;    
}

/**
 * Converts a score/weight values to the platform scale 
 * @param   float   score
 * @param   float   weight
 * @return  float   the score rounded converted to the new range
 */
function convert_score($score, $weight) {
    $max_note =  api_get_setting('exercise_max_score');
    $min_note =  api_get_setting('exercise_min_score');  
          
    if ($score != '' && $weight != '') {        
        if ($max_note != '' && $min_note != '') {           
           if (!empty($weight)) {          
               $score   = $min_note + ($max_note - $min_note) * $score / $weight;
           } else {
               $score   = $min_note;
           }                   
        }           
    }    
    $score_rounded  = float_format($score, 1);  
    return $score_rounded;
}

/**
 * Getting all active exercises from a course from a session (if a session_id is provided we will show all the exercises in the course + all exercises in the session)
 * @param   array   course data
 * @param   int     session id
 * @return  array   array with exercise data
 */
function get_all_exercises($course_info = null, $session_id = 0, $check_publication_dates = false) {
	$TBL_EXERCICES = Database :: get_course_table(TABLE_QUIZ_TEST);
	$course_id = api_get_course_int_id();
	
    if (!empty($course_info) && !empty($course_info['real_id'])) {
    	$course_id = $course_info['real_id'];
    }    
    
    if ($session_id == -1) {
    	$session_id  = 0;
    }
    
    $now = api_get_utc_datetime();
    $time_conditions = '';
    
    if ($check_publication_dates) {        
        $time_conditions = " AND ((start_time <> '0000-00-00 00:00:00' AND start_time < '$now'  AND end_time <> '0000-00-00 00:00:00' AND end_time > '$now' )  OR "; //start and end are set
        $time_conditions .= " (start_time <> '0000-00-00 00:00:00' AND start_time < '$now'  AND end_time = '0000-00-00 00:00:00') OR "; // only start is set
        $time_conditions .= " (start_time = '0000-00-00 00:00:00'   AND end_time <> '0000-00-00 00:00:00'  AND end_time > '$now') OR   "; // only end is set
        $time_conditions .= " (start_time = '0000-00-00 00:00:00'   AND end_time =  '0000-00-00 00:00:00'))  "; // nothing is set                           
    }
    
    if ($session_id == 0) {       
    	$conditions = array('where'=>array('active = ? AND session_id = ? AND c_id = ? '.$time_conditions => array('1', $session_id, $course_id)), 'order'=>'title');        
    } else {
        //All exercises
    	$conditions = array('where'=>array('active = ? AND  (session_id = 0 OR session_id = ? ) AND c_id = ? '.$time_conditions => array('1', $session_id, $course_id)), 'order'=>'title');        
    }    
    return Database::select('*',$TBL_EXERCICES, $conditions);
}


/**
 * Getting all active exercises from a course from a session (if a session_id is provided we will show all the exercises in the course + all exercises in the session)
 * @param   array   course data
 * @param   int     session id
 * @param		int			course c_id
 * @return  array   array with exercise data
 * modified by Hubert Borderiou 
 */
function get_all_exercises_for_course_id($course_info = null, $session_id = 0, $course_id=0) {
   	$TBL_EXERCICES = Database :: get_course_table(TABLE_QUIZ_TEST);
    if ($session_id == -1) {
    	$session_id  = 0;
    }
    if ($session_id == 0) {
    	$conditions = array('where'=>array('active = ? AND session_id = ? AND c_id = ?'=>array('1', $session_id, $course_id)), 'order'=>'title');
    } else {
        //All exercises
    	$conditions = array('where'=>array('active = ? AND (session_id = 0 OR session_id = ? ) AND c_id=?' =>array('1', $session_id, $course_id)), 'order'=>'title');
    }
    return Database::select('*',$TBL_EXERCICES, $conditions);
}

/**
 * Gets the position of the score based in a given score (result/weight) and the exe_id based in the user list
 * (NO Exercises in LPs )
 * @param   float   user score to be compared *attention* $my_score = score/weight and not just the score
 * @param   int     exe id of the exercise (this is necesary because if 2 students have the same score the one with the minor exe_id will have a best position, just to be fair and FIFO)
 * @param   int     exercise id
 * @param   string  course code
 * @param   int     session id
 * @return  int     the position of the user between his friends in a course (or course within a session)
 */
function get_exercise_result_ranking($my_score, $my_exe_id, $exercise_id, $course_code, $session_id = 0, $user_list = array(), $return_string = true) { 
    //No score given we return 
    if (is_null($my_score)) {
        return '-';
    }   
    if (empty($user_list)) {
        return '-';
    }
    
    $best_attempts = array();   
    foreach ($user_list as $user_data) {
        $user_id = $user_data['user_id'];        
        $best_attempts[$user_id]= get_best_attempt_by_user($user_id, $exercise_id, $course_code, $session_id);   
    }

    if (empty($best_attempts)) {
    	return 1;
    } else {
        $position = 1; 
        $my_ranking = array();
        foreach($best_attempts as $user_id => $result) {            
            if (!empty($result['exe_weighting']) && intval($result['exe_weighting']) != 0) {
                $my_ranking[$user_id] = $result['exe_result']/$result['exe_weighting'];
            } else {
                $my_ranking[$user_id] = 0;
            }         
        }         
        //if (!empty($my_ranking)) { 
            asort($my_ranking);
            $position = count($my_ranking);
            if (!empty($my_ranking)) {        
                foreach ($my_ranking as $user_id => $ranking) {
                	if ($my_score >= $ranking) {
                        if ($my_score == $ranking) {
                            $exe_id = $best_attempts[$user_id]['exe_id'];
                            if ($my_exe_id < $exe_id) {
                                $position--;
                            }
                        } else {           
                		  $position--;                    
                        }
                	}
                }        
            }
        //}
        $return_value = array('position'=>$position, 'count'=>count($my_ranking)); 
        //var_dump($my_score, $my_ranking);
        if ($return_string) {
            if (!empty($position) && !empty($my_ranking)) {
               $return_value = $position.'/'.count($my_ranking); 
            } else {
                $return_value = '-';
            }
        }
        return $return_value;    
    }
}

/**
 * Gets the position of the score based in a given score (result/weight) and the exe_id based in all attempts
 * (NO Exercises in LPs ) old funcionality by attempt
 * @param   float   user score to be compared attention => score/weight
 * @param   int     exe id of the exercise (this is necesary because if 2 students have the same score the one with the minor exe_id will have a best position, just to be fair and FIFO)
 * @param   int     exercise id
 * @param   string  course code
 * @param   int     session id
 * @return  int     the position of the user between his friends in a course (or course within a session)
 */
function get_exercise_result_ranking_by_attempt($my_score, $my_exe_id, $exercise_id, $course_code, $session_id = 0, $return_string = true) {
    if (empty($session_id)) {
    	$session_id = 0;
    }
    if (is_null($my_score)) {
        return '-';
    }    
    $user_results = get_all_exercise_results($exercise_id, $course_code, $session_id, false);
    $position_data = array();
    if (empty($user_results)) {
    	return 1;
    } else {
        $position = 1; 
        $my_ranking = array();
        foreach($user_results as $result) {
            //print_r($result);
            if (!empty($result['exe_weighting']) && intval($result['exe_weighting']) != 0) {
                $my_ranking[$result['exe_id']] = $result['exe_result']/$result['exe_weighting'];
            } else {
                $my_ranking[$result['exe_id']] = 0;
            }         
        }        
        asort($my_ranking);
        $position = count($my_ranking);
        if (!empty($my_ranking)) {        
            foreach($my_ranking as $exe_id=>$ranking) {
            	if ($my_score >= $ranking) {
                    if ($my_score == $ranking) {
                        if ($my_exe_id < $exe_id) {
                            $position--;
                        }
                    } else {           
            		  $position--;                    
                    }
            	}
            }        
        }
        $return_value = array('position'=>$position, 'count'=>count($my_ranking)); 
        //var_dump($my_score, $my_ranking);
        if ($return_string) {
            if (!empty($position) && !empty($my_ranking)) {
               return $position.'/'.count($my_ranking); 
            }
        }
        return $return_value;    
    }
}


/*
 *  Get the best attempt in a exercise (NO Exercises in LPs )
 */

function get_best_attempt_in_course($exercise_id, $course_code, $session_id) { 
    $user_results = get_all_exercise_results($exercise_id, $course_code, $session_id, false);
    $best_score_data = array();
    $best_score = 0;
    if (!empty($user_results)) {
        foreach($user_results as $result) {
            if (!empty($result['exe_weighting']) && intval($result['exe_weighting']) != 0) {
                $score = $result['exe_result']/$result['exe_weighting'];
                if ($score >= $best_score) {
                    $best_score = $score;
                    $best_score_data = $result;
                }
            }
        }
    }
    return $best_score_data;
}

/*
 *  Get the best score in a exercise (NO Exercises in LPs )
 */
function get_best_attempt_by_user($user_id, $exercise_id, $course_code, $session_id) { 
    $user_results = get_all_exercise_results($exercise_id, $course_code, $session_id, false);
    $best_score_data = array();
    $best_score = 0;
    if (!empty($user_results)) {       
        foreach($user_results as $result) {
            if ($result['exe_user_id'] != $user_id) continue;
            if (!empty($result['exe_weighting']) && intval($result['exe_weighting']) != 0) {
                $score = $result['exe_result']/$result['exe_weighting'];                
                if ($score >= $best_score) {
                    $best_score = $score;
                    $best_score_data = $result;
                }
            }
        }
    }    
    return $best_score_data;
}




/**
 * Get average score (NO Exercises in LPs )
 * @param 	int	exercise id
 * @param 	string	course code
 * @param 	int	session id
 * @return 	float	Average score
 */
function get_average_score($exercise_id, $course_code, $session_id) { 
    $user_results = get_all_exercise_results($exercise_id, $course_code, $session_id);
    $avg_score_data = array();    
    $avg_score = 0;
    if (!empty($user_results)) {        
        foreach($user_results as $result) {
            if (!empty($result['exe_weighting']) && intval($result['exe_weighting']) != 0) {
                $score = $result['exe_result']/$result['exe_weighting'];
                $avg_score +=$score;                
            }
        }
        $avg_score = float_format($avg_score / count($user_results), 1);
    }
    return $avg_score;
}

/**
 * Get average score by score (NO Exercises in LPs )
 * @param 	int	exercise id
 * @param 	string	course code
 * @param 	int	session id
 * @return 	float	Average score
 */
function get_average_score_by_course($course_code, $session_id) { 
    $user_results = get_all_exercise_results_by_course($course_code, $session_id, false);
    //echo $course_code.' - '.$session_id.'<br />';
    $avg_score = 0;    
    if (!empty($user_results)) {        
        foreach($user_results as $result) {
            if (!empty($result['exe_weighting']) && intval($result['exe_weighting']) != 0) { 
                $score = $result['exe_result']/$result['exe_weighting'];
                //var_dump($score);
                $avg_score +=$score;                               
            }
        }
        //We asume that all exe_weighting
        //$avg_score = show_score( $avg_score / count($user_results) , $result['exe_weighting']);
        $avg_score = ($avg_score / count($user_results));
    }   
    //var_dump($avg_score); 
    return $avg_score;
}

function get_average_score_by_course_by_user($user_id, $course_code, $session_id) {
	$user_results = get_all_exercise_results_by_user($user_id, $course_code, $session_id);
	$avg_score = 0;
	if (!empty($user_results)) {
		foreach($user_results as $result) {
			if (!empty($result['exe_weighting']) && intval($result['exe_weighting']) != 0) {
				$score = $result['exe_result']/$result['exe_weighting'];
				$avg_score +=$score;
			}
		}
		//We asume that all exe_weighting
		//$avg_score = show_score( $avg_score / count($user_results) , $result['exe_weighting']);
		$avg_score = ($avg_score / count($user_results));
	}
	return $avg_score;
}


/**
 * Get average score by score (NO Exercises in LPs )
 * @param 	int		exercise id
 * @param 	string	course code
 * @param 	int		session id
 * @return	float	Best average score
 */
function get_best_average_score_by_exercise($exercise_id, $course_code, $session_id, $user_count) { 
    $user_results = get_best_exercise_results_by_user($exercise_id, $course_code, $session_id);
    $avg_score = 0;
    if (!empty($user_results)) {        
        foreach($user_results as $result) {
            if (!empty($result['exe_weighting']) && intval($result['exe_weighting']) != 0) { 
                $score = $result['exe_result']/$result['exe_weighting'];
                $avg_score +=$score;                
            }
        }
        //We asume that all exe_weighting
        //$avg_score = show_score( $avg_score / count($user_results) , $result['exe_weighting']);
        //$avg_score = ($avg_score / count($user_results));
        if(!empty($user_count)) {
            $avg_score = float_format($avg_score / $user_count, 1) * 100;
        } else {
            $avg_score = 0;
        }
    }
    return $avg_score;
}

function get_exercises_to_be_taken($course_code, $session_id) {
	$course_info = api_get_course_info($course_code);
	$exercises = get_all_exercises($course_info, $session_id);
	$result = array();
	$now = time() + 15*24*60*60;
	foreach($exercises as $exercise_item) {
		if (isset($exercise_item['end_time'])  && !empty($exercise_item['end_time']) && $exercise_item['end_time'] != '0000-00-00 00:00:00' && api_strtotime($exercise_item['end_time'], 'UTC') < $now) {
			$result[] = $exercise_item;
		}
	}
	return $result;
}

/**
 * Get student results (only in completed exercises) stats by question
 * @param 	int		question id
 * @param 	int		exercise id
 * @param 	string	course code
 * @param 	int		session id
 *  
 * */
function get_student_stats_by_question($question_id,  $exercise_id, $course_code, $session_id) {
	$track_exercises	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$track_attempt		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
	
	$question_id 		= intval($question_id);
	$exercise_id 		= intval($exercise_id);
	$course_code 		= Database::escape_string($course_code);
	$session_id 		= intval($session_id);
	 
	$sql = "SELECT MAX(marks) as max , MIN(marks) as min, AVG(marks) as average
			FROM $track_exercises e INNER JOIN $track_attempt a ON (a.exe_id = e.exe_id)
			WHERE 	exe_exo_id 		= $exercise_id AND 
					course_code 	= '$course_code' AND 
					e.session_id 	= $session_id AND
					question_id 	= $question_id AND status = '' LIMIT 1";	
	$result = Database::query($sql);	
	$return = array();
	if ($result) {
		$return = Database::fetch_array($result, 'ASSOC');	
			
	}
	return $return; 	
}

function get_number_students_question_with_answer_count($question_id, $exercise_id, $course_code, $session_id) {
	$track_exercises	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$track_attempt		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $course_user        = Database::get_main_table(TABLE_MAIN_COURSE_USER);

	$question_id 		= intval($question_id);
	$exercise_id 		= intval($exercise_id);
	$course_code 		= Database::escape_string($course_code);
	$session_id 		= intval($session_id);
  

	$sql = "SELECT DISTINCT exe_user_id
			FROM $track_exercises e INNER JOIN $track_attempt a ON (a.exe_id = e.exe_id) INNER JOIN $course_user cu
                ON cu.course_code = a.course_code AND cu.user_id  = exe_user_id
			WHERE 	exe_exo_id 		= $exercise_id AND
					a.course_code 	= '$course_code' AND
					e.session_id 	= $session_id AND            
					question_id 	= $question_id AND
                    answer          <> '0' AND 
                    cu.status       = ".STUDENT." AND 
                    relation_type  <> 2 AND
                    e.status        = ''";    
	$result = Database::query($sql);
	$return = 0;
	if ($result) {
		$return = Database::num_rows($result);
	}
	return $return;
}

function get_number_students_answer_hotspot_count($answer_id, $question_id,  $exercise_id, $course_code, $session_id) {
	$track_exercises	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$track_hotspot		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
    $course_user        = Database::get_main_table(TABLE_MAIN_COURSE_USER);

	$question_id 		= intval($question_id);
    $answer_id          = intval($answer_id);
	$exercise_id 		= intval($exercise_id);
	$course_code 		= Database::escape_string($course_code);
	$session_id 		= intval($session_id);    

	$sql = "SELECT DISTINCT exe_user_id
			FROM $track_exercises e INNER JOIN $track_hotspot a ON (a.hotspot_exe_id = e.exe_id) INNER JOIN $course_user cu
                ON cu.course_code = a.hotspot_course_code AND cu.user_id  = exe_user_id
			WHERE 	exe_exo_id              = $exercise_id AND
					a.hotspot_course_code 	= '$course_code' AND
					e.session_id            = $session_id AND
                    hotspot_answer_id       = $answer_id AND                    
					hotspot_question_id     = $question_id AND 
                    cu.status               = ".STUDENT." AND
                    hotspot_correct         =  1 AND
                    relation_type           <> 2 AND
                    e.status                = ''";
    
	$result = Database::query($sql);
	$return = 0;
	if ($result) {
		$return = Database::num_rows($result);
	}
	return $return;
}


function get_number_students_answer_count($answer_id, $question_id, $exercise_id, $course_code, $session_id, $question_type = null, $correct_answer = null, $current_answer = null) {
	$track_exercises	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$track_attempt		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $course_user        = Database::get_main_table(TABLE_MAIN_COURSE_USER);

	$question_id 		= intval($question_id);
    $answer_id          = intval($answer_id);
	$exercise_id 		= intval($exercise_id);
	$course_code 		= Database::escape_string($course_code);
	$session_id 		= intval($session_id);    
    
    switch ($question_type) {
        case FILL_IN_BLANKS:        
            $answer_condition = "";
            $select_condition = " e.exe_id, answer ";
            break;
        case MATCHING:    
        default:
            $answer_condition = " answer = $answer_id AND ";
            $select_condition = " DISTINCT exe_user_id ";
    }
    
	$sql = "SELECT $select_condition
			FROM $track_exercises e INNER JOIN $track_attempt a ON (a.exe_id = e.exe_id) INNER JOIN $course_user cu
                ON cu.course_code = a.course_code AND cu.user_id  = exe_user_id
			WHERE 	exe_exo_id 		= $exercise_id AND
					a.course_code 	= '$course_code' AND
					e.session_id 	= $session_id AND
                    $answer_condition                              
					question_id 	= $question_id AND 
                    cu.status        = ".STUDENT." AND                     
                    relation_type <> 2 AND
                    e.status = ''";
    //var_dump($sql);
	$result = Database::query($sql);
	$return = 0;
	if ($result) {
        $good_answers = 0;
        switch ($question_type) {
            case FILL_IN_BLANKS:                
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    $fill_blank = check_fill_in_blanks($correct_answer, $row['answer']);
                    if (isset($fill_blank[$current_answer]) && $fill_blank[$current_answer] == 1 ) {                    
                        $good_answers++;
                    }
                }
                return $good_answers;
                break;
            case MATCHING:  
            default:
                $return = Database::num_rows($result);                
        }
	}
	return $return;
}


function check_fill_in_blanks($answer, $user_answer) {    
    // the question is encoded like this
    // [A] B [C] D [E] F::10,10,10@1
    // number 1 before the "@" means that is a switchable fill in blank question
    // [A] B [C] D [E] F::10,10,10@ or  [A] B [C] D [E] F::10,10,10
    // means that is a normal fill blank question
    // first we explode the "::"
    $pre_array = explode('::', $answer);
    // is switchable fill blank or not
    $last = count($pre_array) - 1;
    $is_set_switchable = explode('@', $pre_array[$last]);
    $switchable_answer_set = false;
    if (isset ($is_set_switchable[1]) && $is_set_switchable[1] == 1) {
        $switchable_answer_set = true;
    }
    $answer = '';
    for ($k = 0; $k < $last; $k++) {
        $answer .= $pre_array[$k];
    }
    // splits weightings that are joined with a comma
    $answerWeighting = explode(',', $is_set_switchable[0]);

    // we save the answer because it will be modified
    //$temp = $answer;
    $temp = $answer;

    $answer = '';
    $j = 0;
    //initialise answer tags
    $user_tags = $correct_tags = $real_text = array ();
    // the loop will stop at the end of the text
    while (1) {
        // quits the loop if there are no more blanks (detect '[')
        if (($pos = api_strpos($temp, '[')) === false) {
            // adds the end of the text
            $answer = $temp;
            /* // Deprecated code
             // TeX parsing - replacement of texcode tags
            $texstring = api_parse_tex($texstring);
            $answer = str_replace("{texcode}", $texstring, $answer);
            */
            $real_text[] = $answer;
            break; //no more "blanks", quit the loop
        }
        // adds the piece of text that is before the blank
        //and ends with '[' into a general storage array
        $real_text[] = api_substr($temp, 0, $pos +1);
        $answer .= api_substr($temp, 0, $pos +1);
        //take the string remaining (after the last "[" we found)
        $temp = api_substr($temp, $pos +1);
        // quit the loop if there are no more blanks, and update $pos to the position of next ']'
        if (($pos = api_strpos($temp, ']')) === false) {
            // adds the end of the text
            $answer .= $temp;
            break;
        }
        
        $str = $user_answer;

        preg_match_all('#\[([^[]*)\]#', $str, $arr);
        $str = str_replace('\r\n', '', $str);
        $choice = $arr[1];

        $tmp = api_strrpos($choice[$j],' / ');
        $choice[$j] = api_substr($choice[$j],0,$tmp);
        $choice[$j] = trim($choice[$j]);

        //Needed to let characters ' and " to work as part of an answer
        $choice[$j] = stripslashes($choice[$j]);

        $user_tags[] = api_strtolower($choice[$j]);
        //put the contents of the [] answer tag into correct_tags[]
        $correct_tags[] = api_strtolower(api_substr($temp, 0, $pos));
        $j++;
        $temp = api_substr($temp, $pos +1);        
    }
    
    $answer = '';
    $real_correct_tags = $correct_tags;
    $chosen_list = array();
    
    $good_answer = array();
    
    for ($i = 0; $i < count($real_correct_tags); $i++) {        
        if (!$switchable_answer_set) {
            //needed to parse ' and " characters
            $user_tags[$i] = stripslashes($user_tags[$i]);
            if ($correct_tags[$i] == $user_tags[$i]) {                    
                $good_answer[$correct_tags[$i]] = 1;
            } elseif (!empty ($user_tags[$i])) {                
                $good_answer[$correct_tags[$i]] = 0;
            } else {
                $good_answer[$correct_tags[$i]] = 0;
            }
        } else {
            // switchable fill in the blanks
            if (in_array($user_tags[$i], $correct_tags)) {                
                $correct_tags = array_diff($correct_tags, $chosen_list);
                $good_answer[$correct_tags[$i]] = 1;
            } elseif (!empty ($user_tags[$i])) {                
                $good_answer[$correct_tags[$i]] = 0;
            } else {                
                $good_answer[$correct_tags[$i]] = 0;
            }
        }
        // adds the correct word, followed by ] to close the blank
        $answer .= ' / <font color="green"><b>' . $real_correct_tags[$i] . '</b></font>]';
        if (isset ($real_text[$i +1])) {
            $answer .= $real_text[$i +1];
        }
    }    
    return $good_answer;    
}


function get_number_students_finish_exercise($exercise_id, $course_code, $session_id) {
	$track_exercises	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$track_attempt		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

    $exercise_id 		= intval($exercise_id);
	$course_code 		= Database::escape_string($course_code);
	$session_id 		= intval($session_id);

	$sql = "SELECT DISTINCT exe_user_id
			FROM $track_exercises e INNER JOIN $track_attempt a ON (a.exe_id = e.exe_id)
			WHERE 	exe_exo_id 		= $exercise_id AND
					course_code 	= '$course_code' AND
					e.session_id 	= $session_id AND                    
					status = ''";
	$result = Database::query($sql);
	$return = 0;
	if ($result) {
		$return = Database::num_rows($result);

	}
	return $return;
}



/**
// return the HTML code for a menu with students group
// @input : $in_name : is the name and the id of the <select>
//          $in_default : default value for option
// @return : the html code of the <select>
*/
function displayGroupMenu($in_name, $in_default, $in_onchange="") {
    // check the default value of option
    $tabSelected = array($in_default => " selected='selected' ");
    $res = "";
    $res .= "<select name='$in_name' id='$in_name' onchange='".$in_onchange."' >";
    $res .= "<option value='-1'".$tabSelected["-1"].">-- ".get_lang('AllGroups')." --</option>";
    $res .= "<option value='0'".$tabSelected["0"].">- ".get_lang('NotInAGroup')." -</option>";
    $tabGroups = GroupManager::get_group_list();
    $currentCatId = 0;
    for ($i=0; $i < count($tabGroups); $i++) {
        $tabCategory = GroupManager::get_category_from_group($tabGroups[$i]["id"]);
        if ($tabCategory["id"] != $currentCatId) {
            $res .= "<option value='-1' disabled='disabled'>".$tabCategory["title"]."</option>";
            $currentCatId = $tabCategory["id"];
        }
        $res .= "<option ".$tabSelected[$tabGroups[$i]["id"]]."style='margin-left:40px' value='".$tabGroups[$i]["id"]."'>".$tabGroups[$i]["name"]."</option>";
    }
    $res .= "</select>";
    return $res;
}


/** 
 * Return a list of group for user with user_id=in_userid separated with in_separator 
 * @deprecated ?
 */
function displayGroupsForUser($in_separator, $in_userid) {
    $res = implode($in_separator, GroupManager::get_user_group_name($in_userid));
    if ($res == "") {
        $res = "<div style='text-align:center'>-</div>";
    }
    return $res;
}

function create_chat_exercise_session($exe_id) {
    if (!isset($_SESSION['current_exercises'])) {
        $_SESSION['current_exercises'] = array();
    }
    $_SESSION['current_exercises'][$exe_id] = true;
}

function delete_chat_exercise_session($exe_id) {    
    if (isset($_SESSION['current_exercises'])) {
        $_SESSION['current_exercises'][$exe_id] = false;
    }    
}

/**
 * Display the exercise results
 * @param obj   exercise obj
 * @param int   attempt id (exe_id)
 * @param bool  save users results (true) or just show the results (false)
 */
function display_question_list_by_attempt($objExercise, $exe_id, $save_user_result = false) {
    global $origin, $debug;    
    
    //Getting attempt info
    $exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exe_id);
    $question_list = array();
    if (!empty($exercise_stat_info['data_tracking'])) {
        $question_list		= explode(',', $exercise_stat_info['data_tracking']);
    }
    
    $counter = 1;
    $total_score = $total_weight = 0;
    
    $exercise_content = null;    
    
    //Hide results
    $show_results     = false;
    $show_only_score  = false;

    if ($objExercise->results_disabled == EXERCISE_FEEDBACK_TYPE_END) {
        $show_results = true;
    }

    if ($objExercise->results_disabled == EXERCISE_FEEDBACK_TYPE_EXAM) {
        $show_only_score = true;
    }

    if ($show_results || $show_only_score) {
        $user_info   = api_get_user_info($exercise_stat_info['exe_user_id']);
        //Shows exercise header
        $objExercise->description = '';
        echo $objExercise->show_exercise_result_header(api_get_person_name($user_info['firstName'], $user_info['lastName']), api_convert_and_format_date($exercise_date, DATE_TIME_FORMAT_LONG));
    }
    
    if ($save_user_result) {    
        // Display text when test is finished #4074
        // Don't display the text when finished message if we are from a LP #4227
        // but display it from page exercice_show.php
        $end_of_message = $objExercise->selectTextWhenFinished();
        if (!empty($end_of_message) && ($origin != 'learnpath')) {
            Display::display_normal_message($end_of_message, false);
            echo "<div class='clear'>&nbsp;</div>";
        }
    }
    
    $question_list_answers = array();

    // Loop over all question to show results for each of them, one by one
    if (!empty($question_list)) {
        if ($debug) { error_log('Looping question_list '.print_r($question_list,1));}
        foreach ($question_list as $questionId) {

            // creates a temporary Question object
            $objQuestionTmp = Question :: read($questionId);

            //this variable commes from exercise_submit_modal.php

            //$hotspot_delineation_result = $_SESSION['hotspot_delineation_result'][$objExercise->selectId()][$quesId];
            ob_start();
            
            // We're inside *one* question. Go through each possible answer for this question        
            $result = $objExercise->manage_answer($exercise_stat_info['exe_id'], $questionId, null ,'exercise_result', array(), false, true, $show_results, $objExercise->selectPropagateNeg(), $hotspot_delineation_result);
   
            $total_score     += $result['score'];
            $total_weight    += $result['weight'];
            
            $question_list_answers[]   = array('question' => $result['open_question'], 'answer' => $result['open_answer']);            
            
            $my_total_score  = $result['score'];
            $my_total_weight = $result['weight'];   

            if ($objExercise->selectPropagateNeg() == 0 && $my_total_score < 0) {
                $my_total_score = 0;
            }

            $score = array();    
            if ($show_results) {	    
                $score['result'] = get_lang('Score')." : ".show_score($my_total_score, $my_total_weight, false, false);
                $score['pass'] = $my_total_score >= $my_total_weight ? true : false;
                $score['score'] = $my_total_score;
                $score['weight'] = $my_total_weight;
            }
                        
            if ($show_results) {
                $comnt = get_comments($exe_id, $questionId);		
                if (!empty($comnt)) {
                    echo '<b>'.get_lang('Feedback').'</b>';
                    echo '<div id="question_feedback">'.$comnt.'</div>';
                }		
            }
            
            $contents = ob_get_clean();
    
            $question_content = '<div class="question_row">';

            if ($show_results) {           
                //Shows question title an description
                $question_content .= $objQuestionTmp->return_header(null, $counter, $score);
                
                // display question category, if any
                $question_content .= Testcategory::returnCategoryAndTitle($questionId);
            }
            $counter++;
            
            $question_content .= $contents;
            $question_content .= '</div>';
            
            $exercise_content .= $question_content;
            
        } // end foreach() block that loops over all questions
    }
    
    $total_score_text = null;
    
    if ($origin != 'learnpath') {
        if ($show_results || $show_only_score) {
            
            $is_success = is_success_exercise_result($total_score, $total_weight, $objExercise->selectPassPercentage());
            $total_score_text .= '<div class="question_row">';
            $total_score_text .= '<div class="ribbon ribbon-total ">';
            
            // Color the final test score if pass_percentage activated
            $ribbon_total_success_or_error = "";
            if (is_pass_pourcentage_enabled($objExercise->selectPassPercentage())) {
                if ($is_success) {
                    $ribbon_total_success_or_error = ' ribbon-total-success';
                } else {
                    $ribbon_total_success_or_error = ' ribbon-total-error';
                }
            }
            $total_score_text .= '<div class="rib rib-total $ribbon_total_success_or_error">';
            
            $total_score_text .= '<h3>';
            $total_score_text .= get_lang('YourTotalScore')."&nbsp;";
            if ($objExercise->selectPropagateNeg() == 0 && $total_score < 0) {
                $total_score = 0;
            }
            $total_score_text .= show_score($total_score, $total_weight, false, true);
            $total_score_text .= '</h3>';            
            $total_score_text .= '</div>';
            $total_score_text .= show_success_message($total_score, $total_weight, $objExercise->selectPassPercentage());
            
            $total_score_text .= '</div>';
            $total_score_text .= '</div>';
        }
    }
    
    echo $total_score_text;
    
    echo $exercise_content;
    
    if (!$show_only_score) {
        echo $total_score_text;
    }
    
    if ($save_user_result) {
        
        // Tracking of results
        $learnpath_id           = $exercise_stat_info['orig_lp_id'];
        $learnpath_item_id      = $exercise_stat_info['orig_lp_item_id'];
        $learnpath_item_view_id = $exercise_stat_info['orig_lp_item_view_id'];
        
        if (api_is_allowed_to_session_edit()) {    
            update_event_exercice($exercise_stat_info['exe_id'], $objExercise->selectId(), $total_score, $total_weight, api_get_session_id(), $learnpath_id, $learnpath_item_id, $learnpath_item_view_id, $exercise_stat_info['exe_duration'], $question_list, '', array(), $end_date);
        }
        
        // Send notification ..
        if (!api_is_allowed_to_edit(null,true)) {
            $objExercise->send_notification_for_open_questions($question_list_answers, $origin, $exe_id);
        }
    }
}