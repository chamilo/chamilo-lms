<?php
$language_file=array('exercice');
include_once('../inc/global.inc.php');
include api_get_path(INCLUDE_PATH) . 'reduced_header.inc.php';
include_once(api_get_path(LIBRARY_PATH).'geometry.lib.php');

$dbg_local = 0;

// answer types
define('UNIQUE_ANSWER',	1);
define('MULTIPLE_ANSWER',	2);
define('FILL_IN_BLANKS',	3);
define('MATCHING',		4);
define('FREE_ANSWER', 5);
define('HOT_SPOT', 6);
define('HOT_SPOT_ORDER', 	7);
define('HOT_SPOT_DELINEATION', 	8);

require_once('exercise.class.php');
require_once('question.class.php');
require_once('answer.class.php');
require_once('exercise.lib.php');

if ( empty ( $exerciseResult ) ) {
    $exerciseResult = $_SESSION['exerciseResult'];
}

if ( empty ( $exerciseResultCoordinates ) ) {
    $exerciseResultCoordinates = $_REQUEST['exerciseResultCoordinates'];
}
if ( empty ( $origin ) ) {
    $origin = $_REQUEST['origin'];
}

$_SESSION['hotspot_coord']=array();
$newquestionList=$_SESSION['newquestionList'];
$questionList = $_SESSION['questionList'];
$exerciseId=$_GET['exerciseId'];
$exerciseType=$_GET['exerciseType'];
$questionNum=$_GET['questionnum'];
$nbrQuestions=$_GET['nbrQuestions'];

//round-up the coordinates
$coords = explode('/',$_GET['hotspot']);
$user_array = '';
foreach ($coords as $coord) {
    list($x,$y) = explode(';',$coord);
    $user_array .= round($x).';'.round($y).'/';
}
$user_array = substr($user_array,0,-1);

if ( isset (  $_GET['choice'] ) ) 
{ 
    $choice_value = $_GET['choice'];
}
// getting the options by js
if (empty($choice_value) )
{
	echo '<script type="text/javascript">'."		
		// this works for only radio buttons		
		var f= self.parent.window.document.frm_exercise;
		var choice_js=''; 
		
		var hotspot = new Array();
		var hotspotcoord = new Array();
		var counter=0;
		for( var i = 0; i < f.elements.length; i++ ) 
		{ 					
			if (f.elements[i].type=='radio' && f.elements[i].checked)
			{				
				//alert( f.elements[i].name);				
				choice_js = f.elements[i].value;
                counter ++;				
			}															
																	
																	
			if (f.elements[i].type=='hidden' )
			{	
				name = f.elements[i].name;
					
				if (name.substr(0,7)=='hotspot')
					hotspot.push(f.elements[i].value);	
			
				if (name.substr(0,20)=='hotspot_coordinates')
					hotspotcoord.push(f.elements[i].value);			
				//hotspot = f.elements[i].value;			
														
			}														
																		
		}					
		if (counter==0)					
		{
			choice_js=-1; // this is an error	
		}				
		//alert(choice_js);						
								
	";		
	echo 'window.location.href = "exercise_submit_modal.php?hotspotcoord="+ hotspotcoord + "&hotspot="+ hotspot + "&choice="+ choice_js + "&exerciseId='.$exerciseId.'&questionnum='.$questionNum.'&exerciseType='.$exerciseType.'&origin='.$origin.'gradebook='.$gradebook.'";</script>';
}

$choice=array();
$questionid= $questionList[$questionNum];
// $choice_value => value of the user selection
$choice[$questionid]=$choice_value;
	
// initializing
if(!is_array($exerciseResult))
{
    $exerciseResult=array();
}


// if the user has answered at least one question
if(is_array($choice))
{
    if($exerciseType == 1)
    {
        // $exerciseResult receives the content of the form.
        // Each choice of the student is stored into the array $choice
        $exerciseResult=$choice; 
    }
    else
    {
        // gets the question ID from $choice. It is the key of the array
        list($key)=array_keys($choice);
        // if the user didn't already answer this question
        if(!isset($exerciseResult[$key]))
        {
            // stores the user answer into the array
            $exerciseResult[$key]=$choice[$key];   
        }
    }        
}

// the script "exercise_result.php" will take the variable $exerciseResult from the session

api_session_register('exerciseResult');
api_session_register('exerciseResultCoordinates'); 

/* 
// if it is the last question (only for a sequential exercise)
if($questionNum >= $nbrQuestions)
{	
    if($debug>0){echo str_repeat('&nbsp;',0).'Redirecting to exercise_result.php - Remove debug option to let this happen'."<br />\n";}
	// goes to the script that will show the result of the exercise
    // header("Location: exercise_result.php?origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id");
  	// echo 'location result'; 
}*/

// gets the student choice for this question	
//print_r($choice); echo "<br>";

// creates a temporary Question object
if (in_array($questionid,$questionList))
{ 
	$objQuestionTmp = Question :: read($questionid);
	$questionName=$objQuestionTmp->selectTitle();
	$questionDescription=$objQuestionTmp->selectDescription();
	$questionWeighting=$objQuestionTmp->selectWeighting();
	$answerType=$objQuestionTmp->selectType();
	$quesId =$objQuestionTmp->selectId(); //added by priya saini
}

$objAnswerTmp=new Answer($questionid);
$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
//echo 'answe_type '.$answerType;echo '<br />';

if($answerType == FREE_ANSWER)
	$nbrAnswers = 1;

$choice=$exerciseResult[$questionid];
$destination=array();
$comment='';
$next=1;
$_SESSION['hotspot_coord']=array();
$_SESSION['hotspot_dest']=array();
$overlap_color=$missing_color=$excess_color=false;
$organs_at_risk_hit=0;

if (!empty($choice_value))
{	
	for($answerId=1;$answerId <= $nbrAnswers;$answerId++) {		
		$answer=$objAnswerTmp->selectAnswer($answerId);
		$answerComment=$objAnswerTmp->selectComment($answerId);	
		$answerDestination=$objAnswerTmp->selectDestination($answerId);
		$answerCorrect=$objAnswerTmp->isCorrect($answerId);  	
		$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
		//delineation
		$delineation_cord=$objAnswerTmp->selectHotspotCoordinates(1);
		$answer_delineation_destination=$objAnswerTmp->selectDestination(1);
        if ($dbg_local>0) { error_log(__LINE__.' answerId: '.$answerId.'('.$answerType.') - user delineation_cord: '.$delineation_cord.' - $answer_delineation_destination: '.$answer_delineation_destination,0);}
        
		switch($answerType) {
			// for unique answer
			case UNIQUE_ANSWER : 
				$studentChoice=($choice_value == $answerId)?1:0;		
				if($studentChoice) {					
					$questionScore+=$answerWeighting;
					$totalScore+=$answerWeighting;
					$newquestionList[]=$questionid;				
				}
				break;
			case HOT_SPOT_DELINEATION :	$studentChoice=$choice[$answerId];
				if($studentChoice) {
					$questionScore+=$answerWeighting;
					$totalScore+=$answerWeighting;
					$newquestionList[]=$questionid;	
				}
					
				if ($answerId===1) {					
					$_SESSION['hotspot_coord'][1]=$delineation_cord;
					$_SESSION['hotspot_dest'][1]=$answer_delineation_destination;
				}		
				break;					
		}
					
		if($answerType != MATCHING || $answerCorrect)
		{
			if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER)
			{							
				//display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect);
				//echo $questionScore;				
				if ($studentChoice)
				{
					$destination=$answerDestination;
					$comment=$answerComment;
				}
			}
			elseif($answerType == HOT_SPOT_DELINEATION)
			{	
					if ($next)
					{							
                        if ($dbg_local>0) { error_log(__LINE__.' - next',0);}
						$tbl_track_e_hotspot = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
								
						// Save into db
						$sql = "INSERT INTO $tbl_track_e_hotspot (hotspot_user_id, hotspot_course_code, hotspot_exe_id, hotspot_question_id, hotspot_answer_id, hotspot_correct, hotspot_coordinate ) 
								VALUES ('".Database::escape_string($_user['user_id'])."', '".Database::escape_string($_course['id'])."', '".Database::escape_string($exeId)."', '".Database::escape_string($questionId)."', '".Database::escape_string($answerId)."', '".Database::escape_string($studentChoice)."', '".Database::escape_string($user_array)."')";
						
						$result = api_sql_query($sql,__FILE__,__LINE__);						
						$user_answer = $user_array;
						//$_SESSION['exerciseResultCoordinates'][$questionId]=$exerciseResultCoordinates;
						
						// we compare only the delineation not the other points
						$answer_question= $_SESSION['hotspot_coord'][1];	
						$answerDestination=  $_SESSION['hotspot_dest'][1];
						
                        $poly_user = convert_coordinates($user_answer,'/');
                        $poly_answer = convert_coordinates($answer_question,'|');
                        $max_coord = poly_get_max($poly_user,$poly_answer);
                        
                        $poly_user_compiled = poly_compile($poly_user,$max_coord);
                        $poly_answer_compiled = poly_compile($poly_answer,$max_coord);
                        $poly_results = poly_result($poly_answer_compiled,$poly_user_compiled,$max_coord);
                        $overlap = $poly_results['both'];
                        $poly_answer_area = $poly_results['s1'];
                        $poly_user_area = $poly_results['s2'];
                        $missing = $poly_results['s1Only'];
                        $excess = $poly_results['s2Only'];
                        //$overlap = round(polygons_overlap($poly_answer,$poly_user)); //this is an area in pixels
                        if ($dbg_local>0) { error_log(__LINE__.' - Polygons results are '.print_r($poly_results,1),0);}
                        if ($overlap < 1) {
                            //shortcut to avoid complicated calculations
                        	$final_overlap = 0;
                            $final_missing = 100;
                            $final_excess = 100;
                        } else {
                            // the final overlap is the percentage of the initial polygon that is overlapped by the user's polygon
                        	$final_overlap = round(((float)$overlap / (float)$poly_answer_area)*100);
                            if ($dbg_local>1) { error_log(__LINE__.' - Final overlap is '.$final_overlap,0);}
                            // the final missing area is the percentage of the initial polygon that is not overlapped by the user's polygon
                            $final_missing = 100 - $final_overlap;
                            if ($dbg_local>1) { error_log(__LINE__.' - Final missing is '.$final_missing,0);}
                            // the final excess area is the percentage of the initial polygon's size that is covered by the user's polygon outside of the initial polygon
                            $final_excess = round((((float)$poly_user_area-(float)$overlap)/(float)$poly_answer_area)*100);
                            if ($dbg_local>1) { error_log(__LINE__.' - Final excess is '.$final_excess,0);}
                        }
										
						$destination_items= explode('@@', $answerDestination);	                        
				        $threadhold_total = $destination_items[0];			            
				        $threadhold_items=explode(';',$threadhold_total);				        		            
			            $threadhold1 = $threadhold_items[0]; // overlap
			            $threadhold2 = $threadhold_items[1]; // excess
			            $threadhold3 = $threadhold_items[2];	 //missing          

						// if is delineation
						if ($answerId===1)
						{ 
							//setting colors
							if ($final_overlap>=$threadhold1)
							{	
								$overlap_color=true; //echo 'a';
							}
							//echo $excess.'-'.$threadhold2;
							if ($final_excess<=$threadhold2) 
							{	
								$excess_color=true; //echo 'b';
							}
							//echo '--------'.$missing.'-'.$threadhold3;
							if ($final_missing<=$threadhold3)
							{	
								$missing_color=true; //echo 'c';
							}					
							
							// if pass
							if ($final_overlap>=$threadhold1 && $final_missing<=$threadhold2 && $final_excess<=$threadhold3)
							{								
								$next=1; //go to the oars	
								$result_comment=get_lang('Acceptable');		
							}	
							else
							{
								$next=0;
								$result_comment=get_lang('Unacceptable');								
								$comment=$answerDestination=$objAnswerTmp->selectComment(1);								
								$answerDestination=$objAnswerTmp->selectDestination(1);
								$destination_items= explode('@@', $answerDestination);
								$try_hotspot=$destination_items[1];
	            				$lp_hotspot=$destination_items[2];
	           					$select_question_hotspot=$destination_items[3];
	            				$url_hotspot=$destination_items[4];	 
	            											
								 //echo 'show the feedback';
							}
						}
						elseif($answerId>1)
						{
                            if ($objAnswerTmp->selectHotspotType($answerId) == 'noerror') {
                                if ($dbg_local>0) { error_log(__LINE__.' - answerId is of type noerror',0);}
                            	//type no error shouldn't be treated
                                $next = 1;
                                continue;
                            }
                            if ($dbg_local>0) { error_log(__LINE__.' - answerId is >1 so we\'re probably in OAR',0);}
							//check the intersection between the oar and the user												
							//echo 'user';	print_r($x_user_list);		print_r($y_user_list);
							//echo 'official';print_r($x_list);print_r($y_list);												
							//$result = get_intersection_data($x_list,$y_list,$x_user_list,$y_user_list);
							$inter= $result['success'];

                            //$delineation_cord=$objAnswerTmp->selectHotspotCoordinates($answerId);
                            $delineation_cord=$objAnswerTmp->selectHotspotCoordinates($answerId);

                            $poly_answer = convert_coordinates($delineation_cord,'|');
                            $max_coord = poly_get_max($poly_user,$poly_answer);
                            $poly_answer_compiled = poly_compile($poly_answer,$max_coord); 
                            $overlap = poly_touch($poly_user_compiled, $poly_answer_compiled,$max_coord);

                            if ($overlap == false) {
                            	//all good, no overlap
                                $next = 1;
                                continue;
                            } else {
                                if ($dbg_local>0) { error_log(__LINE__.' - Overlap is '.$overlap.': OAR hit',0);}
                                $organs_at_risk_hit++;  
                                //show the feedback
                                $next=0;
                                $comment=$answerDestination=$objAnswerTmp->selectComment($answerId);                                
                                $answerDestination=$objAnswerTmp->selectDestination($answerId);                                                                 
                                $destination_items= explode('@@', $answerDestination);
                                $try_hotspot=$destination_items[1];
                                $lp_hotspot=$destination_items[2];
                                $select_question_hotspot=$destination_items[3];
                                $url_hotspot=$destination_items[4];                                                                                 
                            }
						}
					}
					else
					{	// the first delineation feedback		
                        if ($dbg_local>0) { error_log(__LINE__.' first',0);}
							
						//we send the error
					}
				}
						
		}
	}
	
	if ($overlap_color) {
		$overlap_color='green';
    } else {
		$overlap_color='red';
    }
	if ($missing_color) {
		$missing_color='green';
    } else {
		$missing_color='red';
    }
	if ($excess_color) {
		$excess_color='green';
    } else {
		$excess_color='red';
    }
	$table_resume='<table class="data_table" >				
	<tr class="row_odd" >
	<td></td>
	<td ><b>'.get_lang('Required').'</b></td>
	<td><b>'.get_lang('YourAnswer').'</b></td>
	</tr>
		
	<tr class="row_even">
		<td><b>'.get_lang('Overlap').'</b></td>
		<td>'.get_lang('Min').' '.$threadhold1.'</td>
		<td><div style="color:'.$overlap_color.'">'.$final_overlap.'</div></td>
	</tr>
	
	<tr class="row_even">
		<td><b>'.get_lang('Missing').'</b></td>
		<td>'.get_lang('Max').' '.$threadhold3.'</td>
		<td><div style="color:'.$missing_color.'">'.$final_missing.'</div></td>
	</tr>			
	<tr>
		<td><b>'.get_lang('Excess').'</b></td>
		<td>'.get_lang('Max').' '.$threadhold2.'</td>
		<td><div style="color:'.$excess_color.'">'.$final_excess.'</div></td>
	</tr>
	</table>';
}

$_SESSION['newquestionList']=$newquestionList;

if ($choice_value==-1)
{		
	$links. '<a href="#" onclick="self.parent.tb_remove();">'.get_lang('ChooseAnAnswer').'</a>';
}


if ($answerType!= HOT_SPOT_DELINEATION)
{
	$item_list=explode('@@',$destination);
	//print_R($item_list);
	$try = $item_list[0];
	$lp = $item_list[1];
	$destinationid= $item_list[2];
	$url=$item_list[3];
	$table_resume='';
}
else
{
	if ($next==0) {
		$try = $try_hotspot;
		$lp = $lp_hotspot;
		$destinationid= $select_question_hotspot;
		$url=$url_hotspot;
	} else {
		//show if no error
		//echo 'no error';
		$comment=$answerComment=$objAnswerTmp->selectComment($nbrAnswers);	
		$answerDestination=$objAnswerTmp->selectDestination($nbrAnswers);						

		//we send the error
		$destination_items= explode('@@', $answerDestination);
		$try=$destination_items[1];
		$lp=$destination_items[2];
		$destinationid=$destination_items[3];
		$url=$destination_items[4];		
	}
}



//$pre_list_destination=explode(';',$list_dest);
/*
$destination_list=array();				
foreach($pre_list_destination as $value)
{
	if ($value!='')
		$destination_list[]=$value;
}*/

//echo '<pre>';print_r($destination);
$links='';

// the link to retry the question
if ($try==1)
{	
	$num_value_array= (array_keys($questionList, $questionid));	
	$links.= Display :: return_icon('reload.gif', '', array ('style' => 'padding-left:0px;padding-right:5px;')).'<a onclick="SendEx('.$num_value_array[0].');" href="#">'.get_lang('TryAgain').'</a><br /><br />';
}

// the link to theory (a learning path)
if (!empty($lp))
{	
	$lp_url= api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$lp;
	require_once('../newscorm/learnpathList.class.php');
	$list = new LearnpathList(api_get_user_id());	
	$flat_list = $list->get_flat_list();		
	$links.= Display :: return_icon('theory.gif', '', array ('style' => 'padding-left:0px;padding-right:5px;')).'<a target="_blank" href="'.$lp_url.'">'.get_lang('SeeTheory').'</a><br />';
}
$links.='<br />';


// the link to an external website or link
if (!empty($url)) {
	$links.= Display :: return_icon('link.gif', '', array ('style' => 'padding-left:0px;padding-right:5px;')).'<a target="_blank" href="'.$url.'">'.get_lang('VisitUrl').'</a><br /><br />';
}

// the link to finish the test
if ($destinationid==-1)
{
	$links.= Display :: return_icon('finish.gif', '', array ('style' => 'width:22px; height:22px; padding-left:0px;padding-right:5px;')).'<a onclick="SendEx(-1);" href="#">'.get_lang('EndActivity').'</a><br /><br />';
}
// the link to other question
else
{
	if (in_array($destinationid,$questionList))
	{ 
		$objQuestionTmp = Question :: read($destinationid);
		$questionName=$objQuestionTmp->selectTitle();
		$num_value_array= (array_keys($questionList, $destinationid));																	
		$links.= Display :: return_icon('quiz.gif', '', array ('style' => 'padding-left:0px;padding-right:5px;')).'<a onclick="SendEx('.$num_value_array[0].');" href="#">'.get_lang('GoToQuestion').' '.$num_value_array[0].'</a><br /><br />';
	}
}



echo '<script> function SendEx(num) 	
	  { 		  
	  	if (num==-1)
	  	{	  	
	  		self.parent.window.location.href = "exercise_result.php?origin='.$origin.'"; 	
	   		self.parent.tb_remove();  	
	  	}
	  	else
	  	{
	  		self.parent.window.location.href = "exercice_submit.php?tryagain=1&exerciseId='.$exerciseId.'&questionNum="+num+"&exerciseType='.$exerciseType.'&origin='.$origin.'"; 	
	   		self.parent.tb_remove();
	  	}
	  }
	  </script>';

api_protect_course_script();
	  	  
if ($links!='')
{
	echo '<div id="ModalContent" style="padding-bottom:30px;padding-top:10px;padding-left:20px;padding-right:20px;">
    <a onclick="self.parent.tb_remove();" href="#" style="float:right; margin-top:-10px;" id="exercise_close_link">'.get_lang('Close').'</a>
	<h1><div style="color:#333;">'.get_lang('Feedback').'</div></h1>
	<p style="text-align:center">';
	
	if ($answerType == HOT_SPOT_DELINEATION)
	{
		$message='<p>'.get_lang('YourDelineation').'</p>';
		$message.=$table_resume;	
		$message.='<br />'.get_lang('ResultIs').' '.$result_comment.'<br />';	
		if ($organs_at_risk_hit>0)
			$message.='<p><b>'.get_lang('OARHit').'</b></p>';		
		$message.='<p>'.$comment.'</p>';	
		echo $message;
	}
	else
	{
		echo '<p>'.$comment.'</p>';
	}
	
	echo '<h3>'.$links.'</h3>';
	//echo '<a onclick="self.parent.tb_remove();" href="#" style="float:right;">'.get_lang('Close').'</a>';
	echo '</div>';
	$_SESSION['hot_spot_result']=$message; 
}
else
{

	$questionNum++;
	echo '<script>
			self.parent.window.location.href = "exercice_submit.php?exerciseId='.$exerciseId.'&questionNum='.$questionNum.'&exerciseType='.$exerciseType.'&origin='.$origin.'";	  		 	
   			//self.parent.tb_remove();	  	
 	 	</script>';
	
}
?>