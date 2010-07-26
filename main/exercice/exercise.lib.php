<?php
/* For licensing terms, see /license.txt */

/**
 *	Exercise library
 * 	shows a question and its answers
 *	@package dokeos.exercise
 * 	@author Olivier Brouckaert <oli.brouckaert@skynet.be>
 * 	@version $Id: exercise.lib.php 22247 2009-07-20 15:57:25Z ivantcholakov $
 */

// The initialization class for the online editor is needed here.
require_once '../inc/lib/fckeditor/fckeditor.php';

/**
 * @param int question id
 * @param boolean only answers
 * @param boolean origin i.e = learnpath
 * @param int current item from the list of questions
 * @param int number of total questions
 * */
function showQuestion($questionId, $onlyAnswers = false, $origin = false, $current_item, $total_item) {

	// Text direction for the current language
	$is_ltr_text_direction = api_get_text_direction() != 'rtl';

	// Change false to true in the following line to enable answer hinting.
	$debug_mark_answer = api_is_allowed_to_edit() && false;

	if (!ereg("MSIE", $_SERVER["HTTP_USER_AGENT"])) {
		//echo '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript"></script>';
		//echo '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.corners.min.js" type="text/javascript"></script>';
	}

	// Reads question informations.
	if (!$objQuestionTmp = Question::read($questionId)) {
		// question not found
		return false;
	}

	$answerType=$objQuestionTmp->selectType();
	$pictureName=$objQuestionTmp->selectPicture();

	if ($answerType != HOT_SPOT) {
		// Question is not of type hotspot
		if (!$onlyAnswers) {
			$questionName=$objQuestionTmp->selectTitle();
			$questionDescription=$objQuestionTmp->selectDescription();

			$questionName=api_parse_tex($questionName);

			$s="<div id=\"question_title\" class=\"sectiontitle\">
				".get_lang('Question').' ';

			$s.=$current_item;
			//@todo I need the get the feedback type
			//if($answerType != 1)
			//$s.=' / '.$total_item;
			echo $s;
			echo ' : ';

			echo $questionName.'</div>';
			$s='';
			$s.="<table class='exercise_questions' style='margin:4px;padding:2px;'>
				<tr><td valign='top' colspan='2'>";
			$questionDescription=api_parse_tex($questionDescription);
			$s.=$questionDescription;
			$s.="</td></tr></table>";

			if (!empty($pictureName)) {
				$s.="
				<tr>
				  <td align='center' colspan='2'><img src='../document/download.php?doc_url=%2Fimages%2F'".$pictureName."' border='0'></td>
				</tr>";
			}
		}
		$s.= '</table>';
		if (!ereg("MSIE",$_SERVER["HTTP_USER_AGENT"])) {
			$s .= '<div class="rounded exercise_questions" style="width: 720px; padding: 3px;">';
		} else {
			$option_ie="margin-left:10px";
		}
		$s .= '<table width="720" class="exercise_options" style="width: 720px;'.$option_ie.' background-color:#fff;">';
		// construction of the Answer object (also gets all answers details)
		$objAnswerTmp=new Answer($questionId);
		$nbrAnswers=$objAnswerTmp->selectNbrAnswers();

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
			$answer_suggestions = $nbrAnswers;

			for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
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
			foreach ($answer_matching as $id => $value) {
				$select_items[$i]['id'] =  $value['id'];
				$select_items[$i]['letter'] =  $cpt1[$id];
				$select_items[$i]['answer'] = $value['answer'];
				$i ++;
			}
			$num_suggestions = ($nbrAnswers - $x) + 1;
		} elseif ($answerType == FREE_ANSWER) {
			#$comment = $objAnswerTmp->selectComment(1);
			//

			$oFCKeditor = new FCKeditor("choice[".$questionId."]") ;

			$oFCKeditor->ToolbarSet = 'TestFreeAnswer';
			$oFCKeditor->Width  = '100%';
			$oFCKeditor->Height = '200';
			$oFCKeditor->Value	= '' ;

			$s .= '<tr><td colspan="3">'.$oFCKeditor->CreateHtml()."</td></tr>";
			//$s.="<tr><td colspan='2'><textarea cols='80' rows='10' name='choice[".$questionId."]'>$answer</textarea></td></tr>";

		}

		// Now navigate through the possible answers, using the max number of
		// answers for the question as a limiter
		$lines_count=1; // a counter for matching-type answers
		for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
			$answer          = $objAnswerTmp->selectAnswer($answerId);
			$answerCorrect   = $objAnswerTmp->isCorrect($answerId);
			$numAnswer       = $objAnswerTmp->selectAutoId($answerId);

			if ($answerType == FILL_IN_BLANKS) {
				// splits text and weightings that are joined with the character '::'
				list($answer) = explode('::',$answer);

				// because [] is parsed here we follow this procedure:
				// 1. find everything between the [tex] and [/tex] tags
				$startlocations = api_strpos($answer,'[tex]');
				$endlocations = api_strpos($answer,'[/tex]');

				if ($startlocations !== false && $endlocations !== false) {
					$texstring = api_substr($answer,$startlocations,$endlocations-$startlocations+6);
					// 2. replace this by {texcode}
					$answer = str_replace($texstring,'{texcode}',$answer);
				}

				// 3. do the normal matching parsing
				// replaces [blank] by an input field

				//getting the matches
				$answer = api_ereg_replace('\[[^]]+\]','<input type="text" name="choice['.$questionId.'][]" size="10" />',($answer));

				// Change input size
				/*
				preg_match_all('/\[[^]]+]/',$answer,$matches);
				$answer=ereg_replace('\[[^]]+\]','<input type="text" name="choice['.$questionId.'][]" size="@@" />',($answer));

				// 4. resize the input


				foreach($matches[0] as $match) {
				$answer_len = strlen($match)-2;
				//we will only replace 1 item
				// echo implode("replace term", explode("search term", "input", $limit));
				if ($answer_len <= 5) {
				$answer = (implode("5", explode("@@", $answer, 2)));
				} elseif($answer_len <= 10) {
				$answer = (implode("10", explode("@@", $answer, 2)));
				} elseif($answer_len <= 20) {
				$answer = (implode("20", explode("@@", $answer, 2)));
				} elseif($answer_len <= 30) {
				$answer = (implode("30", explode("@@", $answer, 2)));
				} elseif($answer_len <= 40) {
				$answer = (implode("45", explode("@@", $answer, 2)));
				} elseif($answer_len <= 50) {
				$answer = (implode("60", explode("@@", $answer, 2)));
				} elseif($answer_len <= 60) {
				$answer = (implode("70", explode("@@", $answer, 2)));
				} elseif($answer_len <= 70) {
				$answer = (implode("80", explode("@@", $answer, 2)));
				} elseif($answer_len <= 80) {
				$answer = (implode("90", explode("@@", $answer, 2)));
				} elseif($answer_len <= 90) {
				$answer = (implode("100", explode("@@", $answer, 2)));
				} elseif($answer_len <= 100) {
				$answer = (implode("110", explode("@@", $answer, 2)));
				} elseif($answer_len > 100 ) {
				$answer = (implode("120", explode("@@", $answer, 2)));
				}
				}

				*/

				// 5. replace the {texcode by the api_pare_tex parsed code}
				$texstring = api_parse_tex($texstring);
				$answer=str_replace("{texcode}",$texstring,$answer);

			}

			// Unique answer
			if ($answerType == UNIQUE_ANSWER) {
				// set $debug_mark_answer to true at function start to
				// show the correct answer with a suffix '-x'
				$help = $selected = '';
				if ($debug_mark_answer) {
					if ($answerCorrect) {
						$help = 'x-';
						$selected = 'checked="checked"';
					}
				}
				$answer = api_parse_tex($answer);
				$answer = Security::remove_XSS($answer, STUDENT);
				/*
				$s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" /><tr><td colspan="3">';
				$s .= '<div class="u-m-answer">
					<p style="float:left; padding-right:4px;">
					<span><input class="checkbox" type="radio" name="choice['.$questionId.']" value="'.$numAnswer.'" '.$selected.' /></span></p>';
				$s .= '<div style="margin-left: 20px;">';
				$s .= $answer;
				$s .= '</div></div>';
				$s .= '</td></tr>';
				*/
				$s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" />';
				$s .= '<tr><td><p style="float:left; padding-'.($is_ltr_text_direction ? 'right' : 'left').': 4px;">';
				$s .= '<span><input class="checkbox" type="radio" name="choice['.$questionId.']" value="'.$numAnswer.'" '.$selected.' /></span></p>';
				$s .= '<td colspan="2"><div class="u-m-answer">'.$answer.'</div></td></tr>';

			} elseif ($answerType == MULTIPLE_ANSWER) {
				// multiple answers
				// set $debug_mark_answer to true at function start to
				// show the correct answer with a suffix '-x'
				$help = $selected = '';
				if ($debug_mark_answer) {
					if ($answerCorrect) {
						$help = 'x-';
						$selected = 'checked="checked"';
					}
				}
				$answer = api_parse_tex($answer);
				$answer = Security::remove_XSS($answer, STUDENT);
				/*
				$s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" /><tr><td colspan="3">';
				$s .= '<div class="u-m-answer">
					<p style="float:left; padding-right:4px;">
					<span><input class="checkbox" type="checkbox" name="choice['.$questionId.']['.$numAnswer.']" value="1" '.$selected.' /></span></p>';
				$s .= '<div style="margin-left: 20px;">';
				$s .= $answer;
				$s .= '</div></div>';
				$s .= '</td></tr>';
				*/
				$s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" />';
				$s .= '<tr><td><p style="padding-'.($is_ltr_text_direction ? 'right' : 'left').': 4px;">';
				$s .= '<span><input class="checkbox" type="checkbox" name="choice['.$questionId.']['.$numAnswer.']" value="1" '.$selected.' /></span><p></td>';
				$s .= '<td colspan="2"><div class="u-m-answer">'.$answer.'</div></td></tr>';

			} elseif ($answerType == MULTIPLE_ANSWER_COMBINATION) {
				// multiple answers
				// set $debug_mark_answer to true at function start to
				// show the correct answer with a suffix '-x'
				$help = $selected = '';
				if ($debug_mark_answer) {
					if ($answerCorrect) {
						$help = 'x-';
						$selected = 'checked="checked"';
					}
				}
				$answer = api_parse_tex($answer);
				$answer = Security::remove_XSS($answer, STUDENT);
				/*
				$s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" /><tr><td colspan="3">';
				$s .= '<div class="u-m-answer">
					<p style="float:left; padding-right:4px;">
					<span><input class="checkbox" type="checkbox" name="choice['.$questionId.']['.$numAnswer.']" value="1" '.$selected.' /></span></p>';
				$s .= '<div style="margin-left: 20px;">';
				$s .= $answer;
				$s .= '</div></div>';
				$s .= '</td></tr>';
				*/
				$s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" />';
				$s .= '<tr><td><p style="padding-'.($is_ltr_text_direction ? 'right' : 'left').': 4px;">';
				$s .= '<span><input class="checkbox" type="checkbox" name="choice['.$questionId.']['.$numAnswer.']" value="1" '.$selected.' /></span></p>';
				$s .= '<td colspan="2"><div class="u-m-answer">'.$answer.'</div></td></tr>';

			} elseif ($answerType == FILL_IN_BLANKS) {
				// fill in blanks
				$s .= '<tr><td colspan="3">'.$answer.'</td></tr>';

			} else {
				//  matching type, showing suggestions and answers
				// TODO: replace $answerId by $numAnswer
				if ($answerCorrect != 0) {
					// only show elements to be answered (not the contents of
					// the select boxes, who are corrrect = 0)
					$s .= '<tr><td width="45%" valign="top" >';
					$parsed_answer = api_parse_tex($answer);
					//left part questions
					$s .= ' <span style="float:left; width:8%;"><b>'.$lines_count.'</b>.&nbsp;</span>
						 	<span style="float:left; width:92%;">'.$parsed_answer.'</span></td>';
					//middle part (matches selects)
					$s .= '<td width="10%" valign="top" align="center">&nbsp;&nbsp;
				            <select name="choice['.$questionId.']['.$numAnswer.']">
							  <option value="0">--</option>';
					// fills the list-box
					foreach ($select_items as $key=>$val) {
						// set $debug_mark_answer to true at function start to
						// show the correct answer with a suffix '-x'
						$help = $selected = '';
						if ($debug_mark_answer) {
							if ($val['id'] == $answerCorrect) {
								$help = '-x';
								$selected = 'selected="selected"';
							}
						}
						$s.='<option value="'.$val['id'].'" '.$selected.'>'.$val['letter'].$help.'</option>';
					}  // end foreach()

					$s .= '</select>&nbsp;&nbsp;</td>';
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
								  <td colspan="2">&nbsp;</td>
								  <td valign="top">';
							$s.='<b>'.$select_items[$lines_count]['letter'].'.</b> '.$select_items[$lines_count]['answer'];
							$s.="</td>
							</tr>";
							$lines_count++;
						}	// end while()
					}  // end if()
				}
			}
		}	// end for()
		if (!ereg("MSIE", $_SERVER["HTTP_USER_AGENT"])) {
			$s .= '</table>';
		}
		$s .= '</div><br />';

		// destruction of the Answer object
		unset($objAnswerTmp);

		// destruction of the Question object
		unset($objQuestionTmp);

		if ($origin != 'export') {
			echo $s;
		} else {
			return($s);
		}
	} elseif ($answerType == HOT_SPOT) {

		// Question is of type HOT_SPOT
		$questionName=$objQuestionTmp->selectTitle();
		$questionDescription=$objQuestionTmp->selectDescription();

		// Get the answers, make a list
		$objAnswerTmp=new Answer($questionId);
		$nbrAnswers=$objAnswerTmp->selectNbrAnswers();

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

		if (!$onlyAnswers) {
			echo '<div id="question_title" class="sectiontitle">'.get_lang('Question').' '.$current_item.' : '.$questionName.'</div>';
			//@todo I need to the get the feedback type
			//if($answerType == 2)
			//	$s.=' / '.$total_item;
			echo '<input type="hidden" name="hidden_hotspot_id" value="'.$questionId.'" />';
			echo '<table class="exercise_questions" >
				  <tr>
			  		<td valign="top" colspan="2">';
			echo $questionDescription=api_parse_tex($questionDescription);
			echo '</td></tr>';
		}

		$canClick = isset($_GET['editQuestion']) ? '0' : (isset($_GET['modifyAnswers']) ? '0' : '1');
		//$tes = isset($_GET['modifyAnswers']) ? '0' : '1';
		//echo $tes;
		$s .= '<script language="JavaScript" type="text/javascript" src="../plugin/hotspot/JavaScriptFlashGateway.js"></script>
						<script src="../plugin/hotspot/hotspot.js" type="text/javascript" language="JavaScript"></script>
						<script language="JavaScript" type="text/javascript">
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
					<script language="JavaScript" type="text/javascript">
						<!--
						// Version check based upon the values entered above in "Globals"
						var hasReqestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);


						// Check to see if the version meets the requirements for playback
						if (hasReqestedVersion) {  // if we\'ve detected an acceptable version
						    var oeTags = \'<object type="application/x-shockwave-flash" data="../plugin/hotspot/hotspot_user.swf?modifyAnswers='.$questionId.'&amp;canClick:'.$canClick.'" width="560" height="436">\'
										+ \'<param name="movie" value="../plugin/hotspot/hotspot_user.swf?modifyAnswers='.$questionId.'&amp;canClick:'.$canClick.'" />\'
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
	echo '</table><br />';
	return $nbrAnswers;
}