<?php
/* For licensing terms, see /license.txt */
/**
 * Exams script
 * @package chamilo.tracking
 */
/**
 * Code
 */

$language_file = array ('registration', 'index', 'tracking', 'exercice','survey');

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'pear/Spreadsheet_Excel_Writer/Writer.php';

$this_section = SECTION_TRACKING;


$is_allowedToTrack = $is_courseAdmin || $is_platformAdmin || $is_courseCoach || $is_sessionAdmin;

if(!$is_allowedToTrack) {
	Display :: display_header(null);
	api_not_allowed();
	Display :: display_footer();
}

$export_to_xls = false;
if (isset($_GET['export'])) {
	$export_to_xls = true;
}

$TBL_EXERCICES			= Database::get_course_table(TABLE_QUIZ_TEST);
$tbl_stats_exercices 	= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);

if (api_is_platform_admin() ) {	
	$global = true;
} else {
	$global = false;
}

if ($global) {
	$temp_course_list = CourseManager :: get_courses_list();	
	foreach($temp_course_list  as $temp_course_item) {
		$course_item = CourseManager ::get_course_information($temp_course_item['code']);		
		$course_list[]= array('db_name' =>$course_item['db_name'],'code'=>$course_item['code'], 'title'=>$course_item['title']);	
	}
} else {	
	$current_course['db_name'] 	= $_course['dbName'];
	$current_course['code'] 	= $_course['id'];	
	$course_list = array($current_course);
}

$form = new FormValidator('search_simple','POST','','',null,false);
$form->addElement('text','score',get_lang('Percentage'));
if ($global) {
	$form->addElement('hidden','view','admin');
} else {
	//Get exam lists
	$t_quiz = Database::get_course_table(TABLE_QUIZ_TEST,$_course['db_name']);	
	$sqlExercices = "	SELECT quiz.title,id FROM ".$t_quiz." AS quiz WHERE active='1' ORDER BY quiz.title ASC";
	$resultExercices = Database::query($sqlExercices);	
	$exercise_list[0] = get_lang('All');
	while($a_exercices = Database::fetch_array($resultExercices)) {
		$exercise_list[$a_exercices['id']] = $a_exercices['title'];
	}	
	
	$form->addElement('select', 'exercise_id', get_lang('Exercise'), $exercise_list);
}
		
//$form->addElement('submit','submit',get_lang('Filter'));
$form->addElement('style_submit_button','submit', get_lang('Filter'),'class="search"' );


if (!empty($_REQUEST['score']))	$filter_score = intval($_REQUEST['score']); else $filter_score = 70;
if (!empty($_REQUEST['exercise_id']))	$exercise_id = intval($_REQUEST['exercise_id']); else $exercise_id = 0;

$form->setDefaults(array('score'=>$filter_score));

if (!$export_to_xls) {
	Display :: display_header(get_lang('Reporting'));
	echo '<div class="actions">';
	if ($global) {
	    		
	    echo '<a href="'.api_get_path(WEB_CODE_PATH).'auth/my_progress.php">'.
        Display::return_icon('stats.png', get_lang('MyStats'),'','32');
        echo '</a>';
        
        echo '<span style="float:right">';
        echo '<a href="'.api_get_self().'?export=1&score='.$filter_score.'&exercise_id='.$exercise_id.'">
		'.Display::return_icon('export_excel.png',get_lang('ExportAsXLS'),'','32').'</a>';
        echo '</span>';
        
        echo '<a href="javascript: void(0);" onclick="javascript: window.print()">
		'.Display::return_icon('printer.png',get_lang('Print'),'','32').'</a>';
	
		$menu_items[] = '<a href="'.api_get_path(WEB_CODE_PATH).'mySpace/?view=teacher">'.get_lang('TeacherInterface').'</a>';
        if (api_is_platform_admin()) {
		  $menu_items[] = '<a href="'.api_get_path(WEB_CODE_PATH).'mySpace/?view=admin">'.get_lang('AdminInterface').'</a>';
        } else {
            $menu_items[] = '<a href="'.api_get_path(WEB_CODE_PATH).'mySpace/?view=coach">'.get_lang('AdminInterface').'</a>';	
        }
		$menu_items[] = get_lang('ExamTracking');
		
		$nb_menu_items = count($menu_items);
		if($nb_menu_items>1) {
			foreach($menu_items as $key=> $item) {
				echo $item;
				if($key!=$nb_menu_items-1) {
					echo ' | ';
				}
			}
		}
	} else {
	   echo '<a href="courseLog.php?'.api_get_cidreq().'&studentlist=true">'.get_lang('StudentsTracking').'</a>&nbsp;| 
		     <a href="courseLog.php?'.api_get_cidreq().'&studentlist=false">'.get_lang('CourseTracking').'</a>&nbsp;|&nbsp';
       echo '<a href="courseLog.php?'.api_get_cidreq().'&studentlist=resources">'.get_lang('ResourcesTracking').'</a>';
		echo ' | '.get_lang('ExamTracking').'';
         echo '<a href="'.api_get_self().'?export=1&score='.$filter_score.'&exercise_id='.$exercise_id.'"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsXLS').'</a>';		
			
	}	
	echo '</div>';
	
	$form->display();		
	echo '<h3>'.sprintf(get_lang('FilteringWithScoreX'), $filter_score).'%</h3>';
}

if ($global) {
	$html_result .= '<table  class="data_table">';
	$html_result .= '<tr><th>'.get_lang('Courses').'</th>';
	$html_result .= '<th>'.get_lang('Exercises').'</th>';
	$html_result .= '<th>'.get_lang('ExamTaken').'</th>';
	$html_result .= '<th>'.get_lang('ExamNotTaken').'</th>';
	$html_result .= '<th>'.sprintf(get_lang('ExamPassX'), $filter_score).'%</th>';
	$html_result .= '<th>'.get_lang('ExamFail').'</th>';
	$html_result .= '<th>'.get_lang('TotalStudents').'</th>';
	$html_result .= '</tr>';
} else {	
	$html_result .= '<table  class="data_table">';	
	$html_result .= '<tr><th>'.get_lang('Exercises').'</th>';
	$html_result .= '<th>'.get_lang('User').'</th>';	
	//$html_result .= '<th>'.sprintf(get_lang('ExamPassX'), $filter_score).'</th>';
	$html_result .= '<th>'.get_lang('Percentage').' %</th>';
	$html_result .= '<th>'.get_lang('Status').'</th>';
	$html_result .= '<th>'.get_lang('Attempts').'</th>';
	$html_result .= '</tr>';
}

$export_array_global = $export_array =  array();
if(!empty($course_list) && is_array($course_list))
foreach($course_list as $current_course ) {
	$global_row = $row_not_global = array();
    
	$a_students = CourseManager :: get_student_list_from_course_code($current_course['code'], false);	
	$total_students = count($a_students);
	$t_quiz = Database::get_course_table(TABLE_QUIZ_TEST,$current_course['db_name']);
	
	$sqlExercices		= "SELECT count(id) as count FROM ".$t_quiz." AS quiz WHERE active='1' ";
	$resultExercices 	= Database::query($sqlExercices);
	$data_exercises  	= Database::store_result($resultExercices);
	$exercise_count 	= $data_exercises[0]['count'];	
	if ($global) {
        if ($exercise_count == 0) {
        	$exercise_count = 2;
        }
		$html_result .= "<tr class='$s_css_class'>
							<td rowspan=$exercise_count>";
		$html_result .= $current_course['title'];
		$html_result .= "</td>";		
	}

	$sql='SELECT visibility FROM '.$current_course['db_name'].'.'.TABLE_TOOL_LIST.' WHERE name="quiz" ';
	$resultVisibilityQuizz = Database::query($sql);
	
	if (Database::result($resultVisibilityQuizz, 0 ,'visibility') == 1) {		
		$sqlExercices = "	SELECT quiz.title,id FROM ".$t_quiz." AS quiz WHERE active='1' ORDER BY quiz.title ASC";		
		//Getting the exam list
		if (!$global) {
			if (!empty($exercise_id)) {
				$sqlExercices = "	SELECT quiz.title,id FROM ".$t_quiz." AS quiz WHERE active='1' AND id = $exercise_id ORDER BY quiz.title ASC";	
			}
		}		
		$resultExercices = Database::query($sqlExercices);
		$i = 0;
		if (Database::num_rows($resultExercices) > 0) {
			
			while($a_exercices = Database::fetch_array($resultExercices)) {
				$global_row[]= $current_course['title'];		
				if (!$global) {
					$html_result .= "<tr class='$s_css_class'>";
				}
				if (!$global) {
					$html_result .= '<td ROWSPAN="'.$total_students.'">';
				} else {
					$html_result .= '<td>';
				}			
				
				$html_result .= $a_exercices['title'];						
				$html_result .= '</td>';			
				
				$global_row[]=$a_exercices['title'];
				$row_not_global['exercise']= $a_exercices['title'];
					
				$taken = 0;
				$total_with_parameter = 0;
				$fail = 0;
				$not_taken = 0;
				
				$total_with_parameter_score = 0;
				$total_with_parameter_porcentage = 0;
				
				$student_result = array();	
			
				foreach ($a_students as $student ) {					
					$current_student_id = $student['user_id'];
					$sqlEssais = "	SELECT COUNT(ex.exe_id) as essais
									FROM $tbl_stats_exercices AS ex
									WHERE  ex.exe_cours_id = '".$current_course['code']."'
									AND ex.exe_exo_id = ".$a_exercices['id']."
									AND exe_user_id='".$current_student_id."'";
									
									
					$resultEssais = Database::query($sqlEssais);					
					$a_essais = Database::fetch_array($resultEssais);					
					
					$sqlScore = "SELECT exe_id, exe_result,exe_weighting
							 FROM $tbl_stats_exercices
							 WHERE exe_user_id = ".$current_student_id."
							 AND exe_cours_id = '".$current_course['code']."'
							 AND exe_exo_id = ".$a_exercices['id']."
							 ORDER BY exe_result DESC LIMIT 1"; // we take the higher value
							 //ORDER BY exe_date DESC LIMIT 1";
					
					$resultScore = Database::query($sqlScore);
					$score = 0;
					
					while($a_score = Database::fetch_array($resultScore)) {
						$score = $score + $a_score['exe_result'];
						$weighting = $weighting + $a_score['exe_weighting'];
						$exe_id = $a_score['exe_id'];
					}
					
					$pourcentageScore = 0;
					if ($weighting!=0) {
						$pourcentageScore = round(($score*100)/$weighting);
					}
	
					$weighting = 0;				
									
					if($i%2==0){
						$s_css_class="row_odd";
					} else {
						$s_css_class="row_even";
					}				
					$i++;
					
					/*echo "	<td align='right'>
						  ";
					echo 		$current_student_id.' ';
					echo "	</td>";
					*/
					
					//var_dump($pourcentageScore);
			/*		echo "	<td align='right'>
						  ";
					echo 		$pourcentageScore.' %';
					echo "	</td>";
					
					echo "<td align='right'>
						 ";
						 /*
					echo 		$a_essais['essais'];
					echo "	</td>
							<td align='center'>
						 ";*/
						 
					if ($a_essais['essais'] > 0 ) {
						$taken++;	
					}
					
					if ($pourcentageScore >= $parameter_porcentage) {
						$total_with_parameter_porcentage++;
					}

					if ($pourcentageScore >= $filter_score) {
						$total_with_parameter_score++;						
					}					
				
					/*				
					$sql_last_attempt='SELECT exe_id FROM '.$tbl_stats_exercices.' WHERE exe_exo_id="'.$a_exercices['id'].'" AND exe_user_id="'.$current_student_id.'" AND exe_cours_id="'.$current_course['code'].'" ORDER BY exe_date DESC LIMIT 1';
					$resultLastAttempt = Database::query($sql_last_attempt);
					if(Database::num_rows($resultLastAttempt)>0) {
						$id_last_attempt=Database::result($resultLastAttempt,0,0);					
						if($a_essais['essais']>0) {
						///	echo		'<a href="../exercice/exercise_show.php?id='.$id_last_attempt.'&cidReq='.$current_course['code'].'&student='.$current_student_id.'&origin='.(empty($_GET['origin']) ? 'tracking' : $_GET['origin']).'"> <img src="'.api_get_path(WEB_IMG_PATH).'quiz.gif" border="0"> </a>';
						}
					}		 
					*/
					
					
					if (!$global) {
						$user_info = api_get_user_info($current_student_id);						
						
						//User
						$user_row = '<td align="center">';					  
						$user_row .= 		$user_info['firstName'].' '.$user_info['lastName'];
						$user_row .= '</td>';
						$user_info = $user_info['firstName'].' '.$user_info['lastName'];						
						
						//Best result																								
						if (!empty($a_essais['essais'])) {
							$user_row .= '<td align="center" >';
							$user_row .= 		$pourcentageScore;	
							$temp_array [] = 		$pourcentageScore;				
							$user_row .= '</td>';
							
							if ($pourcentageScore >= $filter_score ) {
								$user_row .= '<td align="center" style="background-color:#DFFFA8">';
								$user_row .= get_lang('PassExam').'</td>';
								$temp_array [] = 		get_lang('PassExam');
							} else {
								$user_row .= '<td align="center" style="background-color:#FC9A9E"  >';
								$user_row .= get_lang('ExamFail').'</td>';
								$temp_array [] = 		get_lang('ExamFail');
							}
							
							$user_row .= '<td align="center">';					  
							$user_row .= $a_essais['essais'];
							$temp_array [] = 		$a_essais['essais'];
							$user_row .= '</td>';	
						} else {
							$score = '-';
							$user_row .= '<td align="center" >';
							$user_row .=  '-';
							$temp_array [] = 		'-';
							$user_row .= '</td>';
							
							$user_row .= '<td align="center"  style="background-color:#FCE89A">';					  
							$user_row .= get_lang('NoAttempt');
							$temp_array [] = 	get_lang('NoAttempt');
							$user_row .= '</td>';
							$user_row .= '<td align="center">';					  
							$user_row .= 0;
							$temp_array [] = 0;
							$user_row .= '</td>';
						}						
						$user_row .= '</tr>';
						$student_result[$current_student_id]  = array('html'=>$user_row,'score'=>$score,'array'=>$temp_array,'user'=>$user_info);	
						$temp_array = array();
					}
				}
		
				if (!$global) {					
					if (!empty($student_result)) {
						$student_result_empty = $student_result_content = array();
						foreach($student_result as $row) {
							if ($row['score'] == '-') {
								$student_result_empty[] = $row;
							} else {
								$student_result_content[] = $row;
							}
						}
						//Sort only users with content
						usort($student_result_content, 'sort_user');
						$student_result = array_merge($student_result_content, $student_result_empty );
						
						foreach($student_result as $row) {
							$html_result .=$row['html'];
							$row_not_global['results'][]= $row['array'];
                            $row_not_global['users'][]   = $row['user'];    						
						}
						$export_array[] = $row_not_global;
						$row_not_global = array();
					}
				}
				if ($global) {
					//Exam taken
					$html_result .= '<td align="center">';					  
					$html_result .= 		$taken;
					$global_row[]= $taken;
					//echo 		$total.' /  '.$total_students;
					$html_result .= '</td>';
					
					//Exam NOT taken 
					$html_result .= '<td align="center">';					  
					$html_result .= 		$not_taken = $total_students - $taken;
					$global_row[]= $not_taken;
					$html_result .= '</td>';
					
					//Examn pass
					if (!empty($total_with_parameter_score)) {
						$html_result .= '<td align="center"  style="background-color:#DFFFA8" >';
					} else {
						$html_result .= '<td align="center"  style="background-color:#FCE89A"  >';
					}
					
					$html_result .= $total_with_parameter_score;
					$global_row[]= $total_with_parameter_score;
					$html_result .= '</td>';
					
					//Exam fail 
					$html_result .= '<td align="center">';

					$html_result .= 		$fail = $taken - $total_with_parameter_score;
					$global_row[]= $fail;
					$html_result .= '</td>';
					
					$html_result .= '<td align="center">';
					$html_result .= 		$total_students;
					$global_row[]= $total_students;
				
					$global_counter++;
					$html_result .= '</td>';	
					$html_result .= '</tr>';					
					$export_array_global[] = $global_row;
					$global_row = array();					
				}
			}
		} else {
			$html_result .= "	<tr>	
						<td colspan='6'>
							".get_lang('NoExercise')."
						</td>
					</tr>
				 ";
		}		
	} else {
		$html_result .= "	<tr>	
					<td colspan='6'>
						".get_lang('NoExercise')."
					</td>
				</tr>
			 ";
	}	
}

$html_result .= '</table>';

if (!$export_to_xls) {
	echo $html_result;	
}
$filename = 'exam-reporting-'.date('Y-m-d-h:i:s').'.xls';
if ($export_to_xls) {
	if ($global) {
		export_complete_report_xls($filename, $export_array_global);
	} else {
		export_complete_report_xls($filename, $export_array);
	}
	exit;
}

function sort_user($a, $b) {
	if (is_numeric($a['score']) && is_numeric($b['score'])) {
		//echo $a['score'].' : '.$b['score']; echo '<br />';
		if ($a['score'] < $b['score']) {
			return 1;
		}
		return 0;
	}
	return 1;	
}

function export_complete_report_xls($filename, $array) {	
		global $charset, $global, $filter_score;
		$workbook = new Spreadsheet_Excel_Writer();
		$workbook ->setTempDir(api_get_path(SYS_ARCHIVE_PATH));
		$workbook->send($filename);
		$workbook->setVersion(8); // BIFF8
		$worksheet =& $workbook->addWorksheet('Report');
		//$worksheet->setInputEncoding(api_get_system_encoding());
		$worksheet->setInputEncoding($charset);	
		
		$line = 0;
		$column = 0; //skip the first column (row titles)
		
		if ($global) {
			$worksheet->write($line,$column,get_lang('Courses'));
			$column++;
			$worksheet->write($line,$column,get_lang('Exercises'));
			$column++;
			$worksheet->write($line,$column,get_lang('ExamTaken'));
			$column++;			
			$worksheet->write($line,$column,get_lang('ExamNotTaken'));
			$column++;
			$worksheet->write($line,$column,sprintf(get_lang('ExamPassX'), $filter_score).'%');
			$column++;
			$worksheet->write($line,$column,get_lang('ExamFail'));
			$column++;
			$worksheet->write($line,$column,get_lang('TotalStudents'));
			$column++;			
			$line++;		
			foreach ($array as $row) {
				$column = 0;
				foreach ($row as $item) {						
					$worksheet->write($line,$column,html_entity_decode(strip_tags($item)));
					$column++;
				}
				$line++;
			}	
			$line++;			
		} else {
			$worksheet->write($line,$column,get_lang('Exercises'));
			$column++;
			$worksheet->write($line,$column,get_lang('User'));
			$column++;
			$worksheet->write($line,$column,get_lang('Percentage'));
			$column++;			
			$worksheet->write($line,$column,get_lang('Status'));
			$column++;
			$worksheet->write($line,$column,get_lang('Attempts'));
			$column++;						
			$line++;		
			foreach ($array as $row) {				
				$column = 0;
				$worksheet->write($line,$column,html_entity_decode(strip_tags($row['exercise'])));
				$column++;
				foreach ($row['users'] as $key=>$user) {
					$column = 1;						
					$worksheet->write($line,$column,html_entity_decode(strip_tags($user)));
					$column++;			
					foreach ($row['results'][$key] as $result_item) {
						$worksheet->write($line,$column,html_entity_decode(strip_tags($result_item)));
						$column++;		
					}
					$line++;				
				}				
			}	
			$line++;				
		}
		$workbook->close();
		exit;
}
Display :: display_footer();
