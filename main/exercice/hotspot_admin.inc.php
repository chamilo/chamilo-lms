<?php
/* For licensing terms, see /license.txt */
/**
 * This script allows to manage answers. It is included from the 
 * script admin.php
 * @package chamilo.exercise
 * @author Toon Keppens
 */
/**
 * Code
 * ALLOWED_TO_INCLUDE is defined in admin.php
 */
if (!defined('ALLOWED_TO_INCLUDE')) {
	exit();
}
$modifyAnswers = intval($_GET['hotspotadmin']);

if (!is_object($objQuestion)) {
	$objQuestion = Question :: read($modifyAnswers);
}

$questionName   = $objQuestion->selectTitle();
$answerType     = $objQuestion->selectType();
$pictureName    = $objQuestion->selectPicture();
$debug = 0; // debug variable to get where we are

$okPicture = empty($pictureName)?false:true;

// if we come from the warning box "this question is used in serveral exercises"
if ($modifyIn) {    
    if($debug>0){echo '$modifyIn was set'."<br />\n";}
    // if the user has chosed to modify the question only in the current exercise
    if ($modifyIn == 'thisExercise') {
        // duplicates the question
        $questionId=$objQuestion->duplicate();

        // deletes the old question
        $objQuestion->delete($exerciseId);

        // removes the old question ID from the question list of the Exercise object
        $objExercise->removeFromList($modifyAnswers);

        // adds the new question ID into the question list of the Exercise object
        $objExercise->addToList($questionId);

        // construction of the duplicated Question
        $objQuestion = Question :: read($questionId);

        // adds the exercise ID into the exercise list of the Question object
        $objQuestion->addToList($exerciseId);

        // copies answers from $modifyAnswers to $questionId
        $objAnswer->duplicate($questionId);

        // construction of the duplicated Answers

        $objAnswer=new Answer($questionId);
    }
    $color				= unserialize($color);
    $reponse			= unserialize($reponse);
    $comment			= unserialize($comment);
    $weighting			= unserialize($weighting);
    $hotspot_coordinates= unserialize($hotspot_coordinates);
    $hotspot_type		= unserialize($hotspot_type);
    $destination		= unserialize($destination); 
    unset($buttonBack);
}

$hotspot_admin_url = api_get_path(WEB_CODE_PATH) . 'exercice/admin.php?'.api_get_cidreq().'&exerciseId='.$exerciseId;	

// the answer form has been submitted
if ($submitAnswers || $buttonBack) {
	
	if ($answerType==HOT_SPOT) {
		
		if($debug>0){echo '$submitAnswers or $buttonBack was set'."<br />\n";}
	    $questionWeighting=$nbrGoodAnswers=0;
	    for($i=1;$i <= $nbrAnswers;$i++) {
	        if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is HOT_SPOT'."<br />\n";}
	
	        $reponse[$i]=trim($reponse[$i]);
	        $comment[$i]=trim($comment[$i]);
	        $weighting[$i]=$weighting[$i]; // it can be float
	
	        // checks if field is empty
	        if(empty($reponse[$i]) && $reponse[$i] != '0') {
	            $msgErr=get_lang('HotspotGiveAnswers');
	
	            // clears answers already recorded into the Answer object
	            $objAnswer->cancel();
	            break;
	        }
	
	        if($weighting[$i] <= 0) {
	        	$msgErr=get_lang('HotspotWeightingError');
	        	// clears answers already recorded into the Answer object
	            $objAnswer->cancel();
	            break;
	        }
	        
	        if($hotspot_coordinates[$i] == '0;0|0|0' || empty($hotspot_coordinates[$i])) {
	        	$msgErr=get_lang('HotspotNotDrawn');
	        	// clears answers already recorded into the Answer object
	            $objAnswer->cancel();
	            break;
	        }
	
	    }  // end for()
	
	
	    if (empty($msgErr)) {
	    	for($i=1;$i <= $nbrAnswers;$i++) {
	            if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is HOT_SPOT'."<br />\n";}
	            $reponse[$i]=trim($reponse[$i]);
	            $comment[$i]=trim($comment[$i]);
	            $weighting[$i]=($weighting[$i]); //it can be float
				if($weighting[$i]) {
					$questionWeighting+=$weighting[$i];
				}
				// creates answer
				$objAnswer->createAnswer($reponse[$i], '',$comment[$i],$weighting[$i],$i,$hotspot_coordinates[$i],$hotspot_type[$i]);
	        }  // end for()
			// saves the answers into the data base
			$objAnswer->save();
	
	        // sets the total weighting of the question
	        $objQuestion->updateWeighting($questionWeighting);
	        $objQuestion->save($exerciseId);
	
	        $editQuestion=$questionId;
	        unset($modifyAnswers);
	        echo '<script type="text/javascript">window.location.href="'.$hotspot_admin_url.'&message=ItemUpdated"</script>';
	
	    }
	    if($debug>0){echo '$modifyIn was set - end'."<br />\n";}
	} else {
        
	    if($debug>0){echo '$submitAnswers or $buttonBack was set'."<br />\n";}
	    $questionWeighting=$nbrGoodAnswers=0;	
		$select_question=$_POST['select_question'];
		$try=$_POST['try'];	
		$url=$_POST['url'];
		$destination=array(); 	
		 
		$threadhold1 = $_POST['threadhold1'];	
		$threadhold2 = $_POST['threadhold2'];
		$threadhold3 = $_POST['threadhold3'];	
		
	    for($i=1;$i <= $nbrAnswers;$i++) {
	        if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is HOT_SPOT'."<br />\n";}
			
	        $reponse[$i]=trim($reponse[$i]);
	        $comment[$i]=trim($comment[$i]);
	        $weighting[$i] = $weighting[$i];
	                        
	        if (empty($threadhold1[$i]))
				$threadhold1_str=0;
			else
				$threadhold1_str=intval($threadhold1[$i]);
				
			if (empty($threadhold2[$i]))
				$threadhold2_str=0;
			else
				$threadhold2_str=intval($threadhold2[$i]);
							
			if (empty($threadhold3[$i]))
				$threadhold3_str=0;
			else
				$threadhold3_str=intval($threadhold3[$i]);
			
			$threadhold_total=$threadhold1_str.';'.$threadhold2_str.';'.$threadhold3_str;		
			//echo '<pre>';print_r($_POST);echo '</pre>';
					
			if ($try[$i]=='on') {
				$try_str=1;
			} else {
				$try_str=0;
			}
				
			if (empty($lp[$i])) {
				$lp_str=0;
			} else {
				$lp_str=$lp[$i];
			}
			
			if ($url[$i]=='') {
				$url_str='';
			} else {
				$url_str=$url[$i];
			}
	 			
	 		if ($select_question[$i]=='') {
				$question_str=0;
			} else {
				$question_str=$select_question[$i];
			}		
			$destination[$i]= $threadhold_total.'@@'.$try_str.'@@'.$lp_str.'@@'.$question_str.'@@'.$url_str;
			
			// the last answer is the IF NO ERROR section witch has not have the reponse, weight and coordinates values
			//if ($i!=$nbrAnswers && !($answerType==HOT_SPOT_DELINEATION))
		//	{
			
	        // checks if field is empty
	        if(empty($reponse[$i]) && $reponse[$i] != '0') {
	            $msgErr=get_lang('HotspotGiveAnswers');
	
	            // clears answers already recorded into the Answer object
	            $objAnswer->cancel();
	            break;
	        }
	
	        if($weighting[$i] <= 0  && $_SESSION['tmp_answers']['hotspot_type'][$i] != 'oar') {
	        	$msgErr=get_lang('HotspotWeightingError');
	        	// clears answers already recorded into the Answer object
	            $objAnswer->cancel();
	            break;
	        }
	        
	        if($hotspot_coordinates[$i] == '0;0|0|0' || empty($hotspot_coordinates[$i])) {
	        	$msgErr=get_lang('HotspotNotDrawn');
	        	// clears answers already recorded into the Answer object
	            $objAnswer->cancel();
	            break;
	        }
	    }  // end for()

		//now the noerror section    
	    $select_question_noerror=$_POST['select_question_noerror'];
	    $lp_noerror=$_POST['lp_noerror'];    
		$try_noerror=$_POST['try_noerror'];
		$url_noerror=$_POST['url_noerror'];
		$comment_noerror=$_POST['comment_noerror'];
	    $threadhold_total='0;0;0';		
	
		if ($try_noerror=='on') {
			$try_str=1;
		} else {
			$try_str=0;
		}
			
		if (empty($lp_noerror)) {
			$lp_str=0;
		} else {
			$lp_str=$lp_noerror;
		}
		
		if ($url_noerror=='') {
			$url_str='';
		} else {
			$url_str=$url_noerror;
		}
			
		if ($select_question_noerror=='') {
			$question_str=0;
		} else {
			$question_str=$select_question_noerror;
		}
		
		$destination_noerror= $threadhold_total.'@@'.$try_str.'@@'.$lp_str.'@@'.$question_str.'@@'.$url_str; 		
		
	    if(empty($msgErr)) {	        
	    	for($i=1;$i <= $nbrAnswers;$i++) {
	            if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is HOT_SPOT'."<br />\n";}
	
	            $reponse[$i]=trim($reponse[$i]);
	            $comment[$i]=trim($comment[$i]);
	            $weighting[$i]=($weighting[$i]); //it can be float
				if($weighting[$i]) {
					$questionWeighting+=$weighting[$i];
				}
				// creates answer			
				$objAnswer->createAnswer($reponse[$i], '',$comment[$i],$weighting[$i],$i,$hotspot_coordinates[$i],$hotspot_type[$i],$destination[$i]);					
	        }  // end for()
	        
			// saves the answers into the data base
			
			$objAnswer->createAnswer('noerror', '',$comment_noerror,'0',$nbrAnswers+1,null,'noerror',$destination_noerror);	
			$objAnswer->save();		
	
	        // sets the total weighting of the question
	        $objQuestion->updateWeighting($questionWeighting);
	        $objQuestion->save($exerciseId);
	
	        $editQuestion=$questionId;
	        unset($modifyAnswers);
	        
	        echo '<script type="text/javascript">window.location.href="'.$hotspot_admin_url.'&message=ItemUpdated"</script>';
	    }
	}
}

if ($modifyAnswers) {

    if($debug>0){echo str_repeat('&nbsp;',0).'$modifyAnswers is set'."<br />\n";}

    // construction of the Answer object
    $objAnswer=new Answer($objQuestion -> id);        
    api_session_register('objAnswer');
	if($debug>0){echo str_repeat('&nbsp;',2).'$answerType is HOT_SPOT'."<br />\n";}
		
	if ($answerType == HOT_SPOT_DELINEATION) {
		$try=$_POST['try'];
		
		for($i=1;$i <= $nbrAnswers;$i++) {
			if ($try[$i]=='on') {
				$try[$i]=1;
			} else {
				$try[$i]=0;
			}
		}
		
		if ($_POST['try_noerror']=='on') {
			$try_noerror=1;
		} else {
			$try_noerror=0;
		}
	}
	
	if(!$nbrAnswers) {
        $nbrAnswers=$objAnswer->selectNbrAnswers();        
	    if ($answerType == HOT_SPOT_DELINEATION) {
        	// the magic happens here ...
	        // we do this to not count the if no error section
    	    if ($nbrAnswers>=2)
        		$nbrAnswers--;
        }
        $reponse=array();
        $comment=array();
        $weighting=array();
        $hotspot_coordinates=array();
        $hotspot_type=array();
        $destination_items	= array();
        $destination =	array();


        for($i=1;$i <= $nbrAnswers;$i++) {
            $reponse[$i]=$objAnswer->selectAnswer($i);
            if ($objExercise->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
            	$comment[$i]=$objAnswer->selectComment($i);
            }
            $weighting[$i]=$objAnswer->selectWeighting($i);
            $hotspot_coordinates[$i]=$objAnswer->selectHotspotCoordinates($i);            	
            $hotspot_type[$i]=$objAnswer->selectHotspotType($i);
            
            if ($answerType==HOT_SPOT_DELINEATION) {            
            	$destination[$i]=$objAnswer->selectDestination($i);            	
	                 
	            $destination_items= explode('@@', $destination[$i]);                        
	            $threadhold_total = $destination_items[0];            
	            $threadhold_items=explode(';',$threadhold_total);            
	            $threadhold1[$i] = $threadhold_items[0];
	            $threadhold2[$i] = $threadhold_items[1];
	            $threadhold3[$i] = $threadhold_items[2];   
	            
	           	$try[$i]=$destination_items[1];
	            $lp[$i]=$destination_items[2];	     
	            $select_question[$i]=$destination_items[3];
	            $url[$i]=$destination_items[4];
            } 
        }
    }
    
  	if ($answerType==HOT_SPOT_DELINEATION) {
		//added the noerror answer 
    	$reponse_noerror='noerror';
        $comment_noerror=$objAnswer->selectComment($nbrAnswers+1);
        $destination_noerror_list=$objAnswer->selectDestination($nbrAnswers+1);            
        $destination_items= explode('@@', $destination_noerror_list);
         
       	$try_noerror=$destination_items[1];
        $lp_noerror=$destination_items[2];
        $select_question_noerror=$destination_items[3];
        $url_noerror=$destination_items[4];
  	 }  	 

    $_SESSION['tmp_answers'] = array();
    $_SESSION['tmp_answers']['answer'] = $reponse;
    if ($objExercise->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
    	$_SESSION['tmp_answers']['comment'] = $comment;
    }
    $_SESSION['tmp_answers']['weighting'] = $weighting;
    $_SESSION['tmp_answers']['hotspot_coordinates'] = $hotspot_coordinates;
    $_SESSION['tmp_answers']['hotspot_type'] = $hotspot_type;
    if ($answerType==HOT_SPOT_DELINEATION) {
    	$_SESSION['tmp_answers']['destination'] = $destination;
    }

    if ($lessAnswers) {
		if ($answerType==HOT_SPOT_DELINEATION) {
	    	$lest_answer=1;    		
	    	// At least 1 answer
	    	if ($nbrAnswers > $lest_answer) {
	            $nbrAnswers--;
	            // Remove the last answer
				$tmp = array_pop($_SESSION['tmp_answers']['answer']);
				$tmp = array_pop($_SESSION['tmp_answers']['comment']);
				$tmp = array_pop($_SESSION['tmp_answers']['weighting']);
				$tmp = array_pop($_SESSION['tmp_answers']['hotspot_coordinates']);
				$tmp = array_pop($_SESSION['tmp_answers']['hotspot_type']);
								
				if (is_array($_SESSION['tmp_answers']['destination'])) {
					$tmp = array_pop($_SESSION['tmp_answers']['destination']);	
				}							
				
	    	} else {
	    		$msgErr=get_lang('MinHotspot');
	    	}	    
    	} else {
    		// At least 1 answer
    		if ($nbrAnswers > 1) {
        	    $nbrAnswers--;
         	   // Remove the last answer
				$tmp = array_pop($_SESSION['tmp_answers']['answer']);
				if ($objExercise->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
					$tmp = array_pop($_SESSION['tmp_answers']['comment']);
				}
				$tmp = array_pop($_SESSION['tmp_answers']['weighting']);
				$tmp = array_pop($_SESSION['tmp_answers']['hotspot_coordinates']);
				$tmp = array_pop($_SESSION['tmp_answers']['hotspot_type']);
    		} else {
    			$msgErr=get_lang('MinHotspot');
    		}
		}
    }

    if ($moreAnswers) {
    	if ($nbrAnswers < 12) {
            $nbrAnswers++;

            // Add a new answer
            $_SESSION['tmp_answers']['answer'][]='';
            if ($objExercise->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
				$_SESSION['tmp_answers']['comment'][]='';
            }
			$_SESSION['tmp_answers']['weighting'][]='1';
			$_SESSION['tmp_answers']['hotspot_coordinates'][]='0;0|0|0';
			$_SESSION['tmp_answers']['hotspot_type'][]='square';
			$_SESSION['tmp_answers']['destination'][]='';	
    	} else {
    		$msgErr=get_lang('MaxHotspot');
    	}
    }
    
    if($moreOARAnswers) {
    	if ($nbrAnswers < 12) {            
            // Add a new answer            
            $nbrAnswers++;
            
            $_SESSION['tmp_answers']['answer'][]='';
			$_SESSION['tmp_answers']['comment'][]='';
			$_SESSION['tmp_answers']['weighting'][]='1';
			$_SESSION['tmp_answers']['hotspot_coordinates'][]='0;0|0|0';			
			$_SESSION['tmp_answers']['hotspot_type'][]='oar';				
			$_SESSION['tmp_answers']['destination'][]='';
    	} else {
    		$msgErr=get_lang('MaxHotspot');
    	}
    }

    if($debug>0){echo str_repeat('&nbsp;',2).'$usedInSeveralExercises is untrue'."<br />\n";}
    if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is HOT_SPOT'."<br />\n";}
    if 	($answerType==HOT_SPOT_DELINEATION) {        	        	        	        	
	    $hotspot_colors = array("", "#4271B5", "#FE8E16", "#45C7F0", "#BCD631", "#D63173", "#D7D7D7", "#90AFDD", "#AF8640", "#4F9242", "#F4EB24", "#ED2024", "#3B3B3B");        	        		
    } else {
        $hotspot_colors = array("", // $i starts from 1 on next loop (ugly fix)
        						"#4271B5",
								"#FE8E16",
								"#45C7F0",
								"#BCD631",
								"#D63173",
								"#D7D7D7",
								"#90AFDD",
								"#AF8640",
								"#4F9242",
								"#F4EB24",
								"#ED2024",
								"#3B3B3B",
								"#F7BDE2");
	}
                                
    Display::tag('h3',get_lang('Question').": ".$questionName.' <img src="../img/info3.gif" title="'.strip_tags(get_lang('HotspotChoose')).'" alt="'.strip_tags(get_lang('HotspotChoose')).'" />');
    if(!empty($msgErr)) {
        Display::display_normal_message($msgErr); //main API
    }

$hotspot_admin_url = api_get_path(WEB_CODE_PATH) . 'exercice/admin.php?' . api_get_cidreq() . '&hotspotadmin='.$modifyAnswers. '&exerciseId='.$exerciseId;
?>

<form method="post" action="<?php echo $hotspot_admin_url; ?>" id="frm_exercise" name="frm_exercise">
<table border="0" cellpadding="0" cellspacing="2" width="100%">
	<tr>
		<td colspan="2" valign="bottom">
		<?php
			$navigator_info = api_get_navigator();
			
//cancel button
/* 
 * <input type="submit" class="cancel" name="cancelAnswers" value="<?php echo get_lang('Cancel'); ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('ConfirmYourChoice'))); ?>')) return false;" >
 * <button type="submit" class="cancel" name="cancelAnswers" value="<?php echo get_lang('Cancel'); ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('ConfirmYourChoice'))); ?>')) return false;" ><?php echo get_lang('Cancel'); ?></button> 
 * */
			//ie6 fix
			if ($navigator_info['name']=='Internet Explorer' &&  $navigator_info['version']=='6') {
		?>
			<?php if ($answerType==HOT_SPOT_DELINEATION) {?>		
				<input type="submit" class="minus" name="lessAnswers" value="<?php echo get_lang('LessOAR'); ?>" >
				<input type="submit" class="plus" name="moreOARAnswers" value="<?php echo get_lang('MoreOAR'); ?>" />
			<?php } else { ?>
				<input type="submit" class="minus" name="lessAnswers" value="<?php echo get_lang('LessHotspots'); ?>" >
				<input type="submit" class="plus" name="moreAnswers" value="<?php echo get_lang('MoreHotspots'); ?>" />			
			<?php } ?>			
			<input type="submit" class="save" name="submitAnswers" value="<?php echo get_lang('Ok'); ?>" />			 
		<?php
			} else {
			    			
		      if ($answerType==HOT_SPOT_DELINEATION) {?>		
				<button type="submit" class="minus" name="lessAnswers" value="<?php echo get_lang('LessOAR'); ?>" ><?php echo get_lang('LessOAR'); ?></button>
				<button type="submit" class="plus" name="moreOARAnswers" value="<?php echo get_lang('MoreOAR'); ?>" /><?php echo get_lang('MoreOAR'); ?></button>
			<?php } else { ?>
				<button type="submit" class="minus" name="lessAnswers" value="<?php echo get_lang('LessHotspots'); ?>" ><?php echo get_lang('LessHotspots'); ?></button>
				<button type="submit" class="plus" name="moreAnswers" value="<?php echo get_lang('MoreHotspots'); ?>" /><?php echo get_lang('MoreHotspots'); ?></button>			
			<?php } ?>
			<button type="submit" class="save" name="submitAnswers" value="<?php echo get_lang('Ok'); ?>" /><?php echo get_lang('AddQuestionToExercise'); ?></button>
		<?php
			}
		?>
		</td>
	</tr>
	<tr>
		<td valign="top">			
				<input type="hidden" name="formSent" value="1" />
				<input type="hidden" name="nbrAnswers" value="<?php echo $nbrAnswers; ?>" />
				<table class="data_table">
					<!--
					<tr>
					  <td colspan="5"><?php echo get_lang('AnswerHotspot'); ?> :</td>
					</tr>
					-->
					<tr>
					  <th width="5">&nbsp;<?php /* echo get_lang('Hotspot'); */ ?></th>
					  <th><?php echo get_lang('HotspotDescription'); ?> *</th>				
					  <?php if ($answerType==HOT_SPOT_DELINEATION) echo '<th >'.get_lang('Thresholds').'</th>'; ?>
					  
					  
					  <?php if ($objExercise->selectFeedbackType()== EXERCISE_FEEDBACK_TYPE_DIRECT) {?>
					  	<th><?php echo get_lang('Comment'); ?></th>					  
					  	<?php if ($answerType==HOT_SPOT_DELINEATION) echo '<th >'.get_lang('Scenario').'</th>'; ?>					  
					  <?php } else {?>
					  	<th colspan="2"><?php echo get_lang('Comment'); ?></th>					  	
					  <?php }?>	
					  
					  <th><?php echo get_lang('QuestionWeighting'); ?> *</th>					  
					  
					</tr>				
					<?php 		
												
					require_once '../newscorm/learnpathList.class.php';										
					$list = new LearnpathList(api_get_user_id());
					$flat_list = $list->get_flat_list(); //loading list of LPs
			
					for($i=1;$i <= $nbrAnswers; $i++) {
						// is an delineation
						if ($answerType==HOT_SPOT_DELINEATION) {	
						    				
							$select_lp_id=array();						
							$option_lp='';
							
							// setting the LP 
							$is_selected = false;
							foreach ($flat_list as $id => $details) {
						 		$select_lp_id[$id] = $details['lp_name'];
						 		$selected = '';	 		
						 		if ($id==$lp[$i]) {
						 			$is_selected = true;
						 			$selected='selected="selected"';					 			
						 		}						 		
						 		$option_lp.='<option value="'.$id.'" '.$selected.'>'.$details['lp_name'].'</option>';					 							 		
							}						
							if ($is_selected) {
								$option_lp = '<option value="0">'.get_lang('SelectTargetLP').'</option>'.$option_lp;
							} else {
								$option_lp = '<option value="0" selected="selected" >'.get_lang('SelectTargetLP').'</option>'.$option_lp;
							}
							//Feedback SELECT
							
							$question_list=$objExercise->selectQuestionList();						
							$option_feed='';						
							$option_feed.='<option value="0">'.get_lang('SelectTargetQuestion').'</option>';
							
							foreach ($question_list as $key=>$questionid) {
								$selected='';				
								$question = Question::read($questionid);
								$val='Q'.$key.' :'.substrwords($question->selectTitle(),ICON_SIZE_SMALL);							
								$select_lp_id[$id] = $details['lp_name'];							
						 		if ($questionid==$select_question[$i]){
						 			$selected='selected="selected"';					 			
						 		}		
								$option_feed.='<option value="'.$questionid.'" '.$selected.' >'.$val.'</option>';												
							}
							if ($select_question[$i]==-1)
								$option_feed.='<option value="-1" selected="selected" >'.get_lang('ExitTest').'</option>';
							else
								$option_feed.='<option value="-1">'.get_lang('ExitTest').'</option>';
								
					  		//-------- IF it is a delineation	
					  					  		
				  			if ($_SESSION['tmp_answers']['hotspot_type'][$i]=='delineation') {					  				
				  				for($k=1;$k<=100;$k++) {
				  					$selected1=$selected2=$selected3='';						  					
				  					if ($k==$threadhold1[$i])
				  						$selected1='selected="selected"';
				  					if ($k==$threadhold2[$i])
				  						$selected2='selected="selected"';
				  					if ($k==$threadhold3[$i])
				  						$selected3='selected="selected"';				  							  					
				  					$option1.='<option '.$selected1.' >'.$k.'</option>';
				  					$option2.='<option '.$selected2.' >'.$k.'</option>';
				  					$option3.='<option '.$selected3.'>'.$k.'</option>';
				  				}
				  				
				  				?>
				  				<tr>	 
				  				<td valign="top">
					  				<div style="height: 15px; width: 15px; background-color: <?php echo $hotspot_colors[$i]; ?>"> </div>
					  				<input type="hidden" name="reponse[<?php echo $i; ?>]" value="delineation" />
					  			</td>
				  				<td valign="top" align="left">
						 		 	<b><?php echo get_lang('Delineation'); ?></b><br /><br />
						 		 	<?php echo get_lang('MinOverlap'); ?><br/><br/>
						 		 	<?php echo get_lang('MaxExcess'); ?><br/><br/>
						 		 	<?php echo get_lang('MaxMissing'); ?><br/><br/>
					 		 	</td>					 
								<td>
									<br/><br/>
									<select name="threadhold1[<?php echo $i; ?>]" >
									<?php echo $option1; ?>
									</select>%
									<br/><br/>
									<select name="threadhold2[<?php echo $i; ?>]" >
									<?php echo $option2; ?>
									</select>%
									<br/><br/>									
									<select name="threadhold3[<?php echo $i; ?>]" >									
									<?php echo $option3; ?>
									</select>%
									<br/>
								</td>	
															
							  	<td align="left">
							  	<br />
				 					<textarea wrap="virtual" rows="3" cols="25" name="comment[<?php echo $i; ?>]" style="width: 100%">
                                        <?php echo stripslashes(htmlentities($comment[$i])); ?>
                                    </textarea>
				 					<input type="hidden" name="hotspot_type[<?php echo $i; ?>]" value="delineation" />
				 					<input type="hidden" name="hotspot_coordinates[<?php echo $i; ?>]" value="<?php echo (empty($hotspot_coordinates[$i]) ? '0;0|0|0' : $hotspot_coordinates[$i]); ?>" />
				 				<br/>
								 	<?php echo get_lang('LearnerIsInformed');?>
							  	</td>	
							  	
							  	<?php
							  	
							  	if ($objExercise->selectFeedbackType()== EXERCISE_FEEDBACK_TYPE_DIRECT) {?>
							  		<td>
									<table>
										<tr>
										<td>																	
										<input type="checkbox" class="checkbox" name="<?php echo 'try['.$i; ?>]"  <?php if ($try[$i]==1) echo'checked'; ?> />
										<?php echo get_lang('TryAgain'); ?>
										<br /><br />
										
											<?php echo get_lang('SeeTheory');?>	  	<br />
												
										  	<select name="lp[<?php echo $i; ?>]" >
										  		<?php echo $option_lp; ?>
										 	</select>
										 	
									   	<br /><br />							   		
									  	<?php echo get_lang('Other');?>	<br />	
									  	<input name="url[<?php echo $i; ?>]" value="<?php echo $url[$i];?>">
									  	<br />	<br />
									  		<?php echo get_lang('SelectQuestion');?>	<br />	
										   	<select name="select_question[<?php echo $i; ?>]" >
										  	<?php echo $option_feed; ?>
										  	</select>		
										  	
										  	
									   	</td>
									
										</tr>						
									</table>
									</td>
							  	<?php } else {?>
							  		<td> &nbsp;</td>	
							  	<?php } 
					  		}							  		
					  		//elseif ($_SESSION['tmp_answers']['hotspot_type'][$i]=='noerror' || $_SESSION['tmp_answers']['answer'][$i]=='noerror')
					  		elseif (false)
							{ 
							?>							
							<tr>							  
							  <th colspan="2" ><?php echo get_lang('IfNoError'); ?></th>								  					  
							  <th colspan="3" ><?php echo get_lang('Feedback'); ?></th>
							  <!-- th colspan="1" ><?php echo get_lang('Scenario'); ?></th -->
							  <th></th>
							</tr>
							<tr>												  			
						  	<td  colspan="2" valign="top" align="left">
								<?php echo get_lang('LearnerHasNoMistake'); ?>
								<input type="hidden" name="reponse[<?php echo $i; ?>]" value="noerror" />
								<input type="hidden" name="weighting[<?php echo $i; ?>]" value="0" />
								<input type="hidden" name="hotspot_type[<?php echo $i; ?>]" value="noerror" />
								<input type="hidden" name="hotspot_coordinates[<?php echo $i; ?>]" value="0;0|0|0" />
							</td>							 
						 	<td colspan="2"  align="left">
								<textarea wrap="virtual" rows="3" cols="25" name="comment[<?php echo $i; ?>]" style="width: 100%"><?php echo stripslashes(htmlentities($comment[$i])); ?></textarea>
							</td>
							
							<?php if ($objExercise->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {?>
								<td>
								<table>
								<tr>
								<td>
									<input type="checkbox" class="checkbox" name="<?php echo 'try['.$i; ?>]" <?php if ($try[$i]==1) echo'checked'; ?> />
									<?php echo get_lang('TryAgain'); ?>
									<br /><br />
									
									<?php echo get_lang('SeeTheory');?>	  	<br />	
								  	<select name="lp[<?php echo $i; ?>]" >
								  		<?php echo $option_lp; ?>
								 	</select>
							   	<br /><br />
							   		
							  	<?php echo get_lang('Other');?>	<br />	
							  	<input name="url[<?php echo $i; ?>]" value="<?php echo $url[$i]; ?>">
							  	<br />	<br />	
							  		<?php echo get_lang('SelectQuestion');?>	<br />	
								   	<select name="select_question[<?php echo $i; ?>]">
								  	<?php echo $option_feed; ?>
								  	</select>								  	
							   	</td>								
								</tr>						
								</table>
								</td>	
							<?php } else { ?>
								<td>&nbsp;</td>	
							<?php } ?>	

				  			</tr>
							<?php
							}
					  		// if it's an OAR		
							elseif ($_SESSION['tmp_answers']['hotspot_type'][$i]=='oar') {
								if ($i==2) {
								?>								
								<tr>
								  <th width="5">&nbsp;<?php /* echo get_lang('Hotspot'); */ ?></th>
								  <th ><?php echo get_lang('OAR'); ?>*</th>	
								  <?php if ($objExercise->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {?>							  			  
								  	<th colspan="2" ><?php echo get_lang('Comment'); ?></th>
								  	<th ><?php if ($answerType==HOT_SPOT_DELINEATION) echo get_lang('Scenario'); ?></th>
								  <?php } else { ?>
									<th colspan="3" ><?php echo get_lang('Comment'); ?></th>								  	
								  <?php } ?>

								  <th>&nbsp;</th>
								</tr>
								<?php
								}
								?>
								<tr>
								<td valign="top">
							  		<div style="height: 15px; width: 15px; background-color: <?php echo $hotspot_colors[$i]; ?>"> </div>
							  	</td>
							  									
								<td valign="top" align="left">
							  		<input type="text" name="reponse[<?php echo $i; ?>]" value="<?php echo htmlentities($reponse[$i]); ?>" size="20" />							  		
							  	</td>
							 
						 	 	<td colspan="2"  align="left">
							 		<textarea wrap="virtual" rows="3" cols="25" name="comment[<?php echo $i; ?>]" style="width: 100%"><?php echo stripslashes(htmlentities($comment[$i])); ?></textarea>
							 		<input type="hidden" name="hotspot_type[<?php echo $i; ?>]" value="oar" />
		 					 	 	<input type="hidden" name="hotspot_coordinates[<?php echo $i; ?>]" value="<?php echo (empty($hotspot_coordinates[$i]) ? '0;0|0|0' : $hotspot_coordinates[$i]); ?>" />
							  	</td>
							  	
							  	<?php if ($objExercise->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) { ?>
							  		<td>
									<table>
									<tr>
									<td>
										<input type="checkbox" class="checkbox" name="<?php echo 'try['.$i; ?>]" <?php if ($try[$i]==1) echo'checked'; ?> />
										<?php echo get_lang('TryAgain'); ?>
										<br /><br />
										
										<?php echo get_lang('SeeTheory');?>	  	<br />	
									  	<select name="lp[<?php echo $i; ?>]" >
									  		<?php echo $option_lp; ?>
									 	</select>
								   	<br /><br />								   		
								  	<?php echo get_lang('Other');?>	<br />	
								  	<input name="url[<?php echo $i; ?>]" value="<?php echo $url[$i]; ?>">
								  	 	<br /><br />	
								  		<?php echo get_lang('SelectQuestion');?>	<br />	
									   	<select name="select_question[<?php echo $i; ?>]">
									  	<?php echo $option_feed; ?>
									  	</select>	
									  	
								   	</td>
									</tr>						
									</table>
									</td>						
							  	<?php } else {?>
							  		<td>&nbsp;</td>	
							  	<?php } ?>	
															
							<?php							
						}							
					} else { //end if is delineation
						?>					
						<td valign="top">
					  		<div style="height: 15px; width: 15px; background-color: <?php echo $hotspot_colors[$i]; ?>"> </div>
					  	</td>					  			
					  	<td valign="top" align="left">
					  		<input type="text" name="reponse[<?php echo $i; ?>]" value="<?php echo htmlentities($reponse[$i]); ?>" size="45" />
					 	</td>
					 	
					 	<?php
						require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");
						$oFCKeditor = new FCKeditor("comment[$i]") ;
						$content = $comment[$i];
						$oFCKeditor->ToolbarSet = 'TestProposedAnswer';	
						$oFCKeditor->Config['ToolbarStartExpanded'] = 'false';		
						$oFCKeditor->Width		= '100%';
						$oFCKeditor->Height		= '100';
						$oFCKeditor->Value		= $content;
						$return =	$oFCKeditor->CreateHtml();
						/*<td align="left"><textarea wrap="virtual" rows="1" cols="25" name="comment[<?php echo $i; ?>]" style="width: 100%"><?php echo api_htmlentities($comment[$i], ENT_QUOTES, api_get_system_encoding()); ?></textarea></td>*/
						?>
						<td>&nbsp;</td>
				 	 	<td align="left" ><?php echo $return; ?></td>					 			 				
					<?php
					}
					?>			
			  <td valign="top">
				<?php				
				
				//if ($answerType==HOT_SPOT_DELINEATION && $i!=2)
				if ($answerType==HOT_SPOT_DELINEATION) {					
					if ($_SESSION['tmp_answers']['hotspot_type'][$i]=='oar') { ?>
						<input type="hidden" name="weighting[<?php echo $i; ?>]" class="span3" value="0" />
					<?php } else { ?>
						<input type="text" name="weighting[<?php echo $i; ?>]" class="span3" value="<?php echo (isset($weighting[$i]) ? $weighting[$i] : 10); ?>" />
					<?php }				
				}							
				if ($answerType==HOT_SPOT) {
				?>
			  		<input type="text" name="weighting[<?php echo $i; ?>]" class="span3" value="<?php echo (isset($weighting[$i]) ? $weighting[$i] : 10); ?>" />
				 	<input type="hidden" name="hotspot_coordinates[<?php echo $i; ?>]" value="<?php echo (empty($hotspot_coordinates[$i]) ? '0;0|0|0' : $hotspot_coordinates[$i]); ?>" />
				 	<input type="hidden" name="hotspot_type[<?php echo $i; ?>]" value="<?php echo (empty($hotspot_type[$i]) ? 'square' : $hotspot_type[$i]); ?>" />
				<?php	
				}								
				?>
			  </td>				  	
			</tr>					
				<?php					  		
			}			
			require_once '../newscorm/learnpathList.class.php';											
			$list = new LearnpathList(api_get_user_id());
			$flat_list = $list->get_flat_list();
			$select_lp_id=array();						
			$option_lp='';
			//$option_lp.='<option value="0">'.get_lang('SelectTargetLP').'</option>';							
			foreach ($flat_list as $id => $details) {
				$selected = '';
		 		$select_lp_id[$id] = $details['lp_name'];		 						 		
		 		if ($id==$lp_noerror) {
		 			$selected='selected="selected"';
		 			$is_selected = true;					 			
		 		}		 		
		 		$option_lp.='<option value="'.$id.'" '.$selected.'>'.$details['lp_name'].'</option>';					 							 		
			}			
			
			if ($is_selected) {
				$option_lp = '<option value="0">'.get_lang('SelectTargetLP').'</option>'.$option_lp;
			} else {
				$option_lp = '<option value="0" selected="selected" >'.get_lang('SelectTargetLP').'</option>'.$option_lp;
			}
												
			//Feedback SELECT
			
			$question_list=$objExercise->selectQuestionList();						
			$option_feed='';						
			$option_feed.='<option value="0">'.get_lang('SelectTargetQuestion').'</option>';			
			foreach ($question_list as $key=>$questionid)
			{
				$selected='';				
				$question = Question::read($questionid);
				$val='Q'.$key.' :'.substrwords($question->selectTitle(),ICON_SIZE_SMALL);							
				$select_lp_id[$id] = $details['lp_name'];							
		 		if ($questionid==$select_question_noerror){ 
		 			$selected='selected="selected"';					 			
		 		}
				$option_feed.='<option value="'.$questionid.'" '.$selected.' >'.$val.'</option>';												
			}
			if ($select_question_noerror==-1)
					$option_feed.='<option value="-1" selected="selected" >'.get_lang('ExitTest').'</option>';
				else
					$option_feed.='<option value="-1">'.get_lang('ExitTest').'</option>';
				
			if ($answerType==HOT_SPOT_DELINEATION) {
			?>
				<tr>							  
				  <th colspan="2" ><?php echo get_lang('IfNoError'); ?></th>
				  <?php if ($objExercise->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) { ?>
				  		<th colspan="2" ><?php echo get_lang('Feedback'); ?></th>
				  		<th><?php echo get_lang('Scenario'); ?></th>
				  <?php } else  { ?>
				  		<th colspan="3" ><?php echo get_lang('Feedback'); ?></th>		
				  <?php } ?>				  
				  <th>&nbsp;</th>
				</tr>
				<tr>												  			
			  	<td  colspan="2" valign="top" align="left">
					<?php echo get_lang('LearnerHasNoMistake'); ?>												
				</td>							 
			 	<td colspan="2"  align="left">
					<textarea wrap="virtual" rows="3" cols="25" name="comment_noerror" style="width: 100%"><?php echo stripslashes(htmlentities($comment_noerror)); ?></textarea>
				</td>
				
				<?php if ($objExercise->selectFeedbackType()== EXERCISE_FEEDBACK_TYPE_DIRECT) { ?>
					<td>
					<table>
					<tr>
					<td>
						<input type="checkbox" class="checkbox" name="try_noerror" <?php if ($try_noerror==1) echo'checked'; ?> />
						<?php echo get_lang('TryAgain'); ?>
						<br /><br />									
						<?php echo get_lang('SeeTheory');?>	  	<br />	
					  	<select name="lp_noerror" >
					  		<?php echo $option_lp; ?>
					 	</select>
				   	<br /><br />
				   		
				  	<?php echo get_lang('Other');?>	<br />	
				  	<input name="url_noerror" value="<?php echo $url_noerror; ?>">
				  		<br /><br />
				  		<?php echo get_lang('SelectQuestion');?>	<br />	
					   	<select name="select_question_noerror">
					  	<?php echo $option_feed; ?>
					  	</select>				  	
				   	</td>			
					</tr>						
					</table>
					</td>
					<td>&nbsp;</td>				
				<?php } else { ?>
					<td colspan="2">&nbsp;</td>
				<?php } ?>
	  			</tr>				
			<?php
			}
			?>				
				</table>
		</td>	
	</tr>
	<tr>
		<td colspan="2" valign="top" style="border-top:none">
			<script type="text/javascript">
				<!--
				// Version check based upon the values entered above in "Globals"
				var hasReqestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);
				<?php
					$swf_loaded = $answerType==HOT_SPOT_DELINEATION ? 'hotspot_delineation_admin' : 'hotspot_admin';
					$height = 450;								
				?>
				// Check to see if the version meets the requirements for playback
				if (hasReqestedVersion) {  // if we've detected an acceptable version
				    var oeTags = '<object type="application/x-shockwave-flash" data="../plugin/hotspot/<?php echo $swf_loaded ?>.swf?modifyAnswers=<?php echo $modifyAnswers ?>" width="600" height="<?php echo $height ?>">'
								+ '<param name="movie" value="../plugin/hotspot/<?php echo $swf_loaded ?>.swf?modifyAnswers=<?php echo $modifyAnswers ?>" />'
								+ '<param name="test" value="OOoowww fo shooww" />'
								+ '</object>';
				    document.write(oeTags);   // embed the Flash Content SWF when all tests are passed
				} else {  // flash is too old or we can't detect the plugin
					var alternateContent = 'Error<br \/>'
						+ 'This content requires the Macromedia Flash Player.<br \/>'
						+ '<a href=http://www.macromedia.com/go/getflash/>Get Flash<\/a>';
					document.write(alternateContent);  // insert non-flash content
				}
				// -->
			</script>
		</td>
		
	</tr>
</table>
</form>
<?php
    if($debug>0){echo str_repeat('&nbsp;',0).'$modifyAnswers was set - end'."<br />\n";}
}
