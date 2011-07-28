<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for thematic control 
* @author Christian Fasanando <christian1827@gmail.com>
* @author Julio Montoya <gugli100@gmail.com> Bug fixing
* @package chamilo.course_progress
*/

// protect a course script
api_protect_course_script(true);

$token = Security::get_token();
$url_token = "&sec_token=".$token;

if (api_is_allowed_to_edit(null, true)) {
	
	echo '<div class="actions" style="margin-bottom:30px">';	
	switch ($action) {		
		case 'thematic_add' :	
				echo '<a href="index.php?'.api_get_cidreq().'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('ThematicDetails'),'','32').'</a>';
				break;		
		case 'thematic_list' :	
				echo '<a href="index.php?'.api_get_cidreq().'&action=thematic_add'.$url_token.'">'.Display::return_icon('new_course_progress.png',get_lang('NewThematicSection'),'','32').'</a>';
				break;
		case 'thematic_details' :		
				echo '<a href="index.php?'.api_get_cidreq().'&action=thematic_add'.$url_token.'">'.Display::return_icon('new_course_progress.png',get_lang('NewThematicSection'),'','32').'</a>';
				break;
		default :
				echo '<a href="index.php?'.api_get_cidreq().'&action=thematic_add'.$url_token.'">'.Display::return_icon('new_course_progress.png',get_lang('NewThematicSection'),'','32').'</a>';		
	}			
	echo '</div>';
}

if ($action == 'thematic_list') {
	
	$table = new SortableTable('thematic_list', array('Thematic', 'get_number_of_thematics'), array('Thematic', 'get_thematic_data'));
	
	$parameters['action'] = $action;
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false, array('style'=>'width:20px;'));
	$table->set_header(1, get_lang('Title'), false );	
	if (api_is_allowed_to_edit(null, true)) {
		$table->set_header(2, get_lang('Actions'), false,array('style'=>'text-align:center;width:40%;'));
		$table->set_form_actions(array ('thematic_delete_select' => get_lang('DeleteAllThematics')));	
	}
	$table->display();
	
} elseif ($action == 'thematic_details') {
	
	if ($last_id) {
		$link_to_thematic_plan = '<a href="index.php?'.api_get_cidreq().'&action=thematic_plan_list&thematic_id='.$last_id.'">'.Display::return_icon('lesson_plan.png', get_lang('ThematicPlan'), array('style'=>'vertical-align:middle'),22).'</a>';
		$link_to_thematic_advance = '<a href="index.php?'.api_get_cidreq().'&action=thematic_advance_list&thematic_id='.$last_id.'">'.Display::return_icon('lesson_plan_calendar.png', get_lang('ThematicAdvance'), array('style'=>'vertical-align:middle'),22).'</a>';
		Display::display_confirmation_message(get_lang('ThematicSectionHasBeenCreatedSuccessfull').'<br />'.sprintf(get_lang('NowYouShouldAddThematicPlanXAndThematicAdvanceX'),$link_to_thematic_plan, $link_to_thematic_advance), false);
	}

	// display title
	if (!empty($thematic_id)) {
	} else {	
		// display information
		$message = '<strong>'.get_lang('Information').'</strong><br />';
		$message .= get_lang('ThematicDetailsDescription');	
		Display::display_normal_message($message, false);
		echo '<br />';			
	}
	
	// display thematic data	
	
	if (!empty($thematic_data)) {
		
		// display progress
		echo '<div style="text-align:right;"><h2>'.get_lang('Progress').': <span id="div_result">'.$total_average_of_advances.'</span> %</h2></div>';
		
		echo '<table width="100%" class="data_table">';	
		echo '<tr><th width="33%">'.get_lang('Thematic').'</th><th>'.get_lang('ThematicPlan').'</th><th width="33%">'.get_lang('ThematicAdvance').'</th></tr>';
		foreach ($thematic_data as $thematic) {
		    $my_thematic_id = $thematic['id'];
		    
		    $session_star = '';
		    if (api_is_allowed_to_edit(null, true)) {
		        if (api_get_session_id() == $thematic['session_id']) {
                    $session_star = api_get_session_image(api_get_session_id(), $user_info['status']);
		        }
		    }
		    
		    //@todo add a validation in order to load or not course thematics in the session thematic
		    /*
		    if (api_get_session_id() == $thematic['session_id']) {
                $session_star = api_get_session_image(api_get_session_id(), $user_info['status']);
            }  else { 
                continue;
            } */           
			echo '<tr>';
			$actions_first_col = '';
			if (api_is_allowed_to_edit(null, true)) {
    			// Thematic title		
    			$actions_first_col  = Display::url(Display::return_icon('cd.gif', get_lang('Copy')), 'index.php?'.api_get_cidreq().'&action=thematic_copy&thematic_id='.$my_thematic_id.$param_gradebook.$url_token);
    			if (api_get_session_id() == 0 ) {
    			    	    
    				if ($thematic['display_order'] > 1) {
						$actions_first_col .= ' <a href="'.api_get_self().'?action=moveup&'.api_get_cidreq().'&thematic_id='.$my_thematic_id.$param_gradebook.$url_token.'">'.Display::return_icon('up.png', get_lang('Up'),'',22).'</a>';
					} else {
						$actions_first_col .= ' '.Display::return_icon('up_na.png','&nbsp;','',22);
					}
					if ($thematic['display_order'] < $thematic['max_thematic_item']) {
						$actions_first_col .= ' <a href="'.api_get_self().'?action=movedown&a'.api_get_cidreq().'&thematic_id='.$my_thematic_id.$param_gradebook.$url_token.'">'.Display::return_icon('down.png',get_lang('Down'),'',22).'</a>';
					} else {
						$actions_first_col .= ' '.Display::return_icon('down_na.png','&nbsp;','',22);
					}
					
    			}
    			if (api_get_session_id() == $thematic['session_id']) {		
                    $actions_first_col .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_edit&thematic_id='.$my_thematic_id.$param_gradebook.$url_token.'">'.Display::return_icon('edit.png',get_lang('Edit'),'',22).'</a>';                        
                    $actions_first_col .= '<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToDelete').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=thematic_delete&thematic_id='.$my_thematic_id.$param_gradebook.$url_token.'">'.Display::return_icon('delete.png',get_lang('Delete'),'',22).'</a>';
    			}
    			
    			$actions_first_col = Display::div($actions_first_col, array('id'=>'thematic_id_content_'.$thematic['id'], 'class'=>'thematic_tools'));
    			$actions_first_col = Display::div($actions_first_col, array('style'=>'height:20px'));
			}
			                        
			echo Display::tag('td', Display::tag('h2', Security::remove_XSS($thematic['title'], STUDENT).$session_star).Security::remove_XSS($thematic['content'], STUDENT).$actions_first_col, array('id'=>'thematic_td_content_'.$thematic['id'], 'class'=>'thematic_content'));
			
			// Display 2nd column - thematic plan data
			 
			echo '<td>';	
							
			//if (api_is_allowed_to_edit(null, true) &&  api_get_session_id() == $thematic['session_id']) {
			if (api_is_allowed_to_edit(null, true)) {
				echo '<div style="text-align:right"><a class="thematic_plan_opener" href="index.php?'.api_get_cidreq().'&origin=thematic_details&action=thematic_plan_list&thematic_id='.$thematic['id'].'">'.
				Display::return_icon('edit.png', get_lang('EditThematicPlan'), array('style'=>'vertical-align:middle'),32).'</a></div><br />';
			}			
			
			if (empty($thematic_plan_div[$thematic['id']])) {
				echo Display::div('', array('id' => "thematic_plan_".$thematic['id']));
			} else {
				echo $thematic_plan_div[$thematic['id']];
			}
						
			echo '</td>';
			
			// Display 3rd column - thematic advance data						
			echo '<td style="vertical-align:top">';
			
			//if (api_is_allowed_to_edit(null, true) &&  api_get_session_id() == $thematic['session_id']) {					
			if (api_is_allowed_to_edit(null, true)) {
				//echo '<div style="text-align:right"><a href="index.php?'.api_get_cidreq().'&origin=thematic_details&action=thematic_advance_list&thematic_id='.$thematic['id'].'">'.Display::return_icon('edit.png',get_lang('EditThematicAdvance'),array('style'=>'vertical-align:middle'),22).'</a></div><br />';
				echo '<div style="text-align:right"><a class="thematic_advanced_add_opener" href="index.php?'.api_get_cidreq().'&action=thematic_advance_add&thematic_id='.$thematic['id'].'">'.Display::return_icon('add.png',get_lang('NewThematicAdvance'),'','32').'</a></div>';
			}						
			
			//if (api_is_allowed_to_edit(null, true) &&  api_get_session_id() == $thematic['session_id']) {
			if (!empty($thematic_advance_data[$thematic['id']])) {
			    echo '<table width="100%">';                
				foreach ($thematic_advance_data[$thematic['id']] as $thematic_advance) {
					
					$thematic_advance['start_date'] = api_get_local_time($thematic_advance['start_date']);
					$thematic_advance['start_date'] = api_format_date($thematic_advance['start_date'], DATE_TIME_FORMAT_LONG);
					echo '<tr>';
					echo '<td width="90%" class="thematic_advance_content" id="thematic_advance_content_id_'.$thematic_advance['id'].'">';
					
					$edit_link = '';
					if (api_is_allowed_to_edit(null, true)) {
						$edit_link  =  '<a class="thematic_advanced_opener" href="index.php?'.api_get_cidreq().'&action=thematic_advance_edit&thematic_id='.$thematic['id'].'&thematic_advance_id='.$thematic_advance['id'].'" >'.Display::return_icon('edit.png',get_lang('EditThematicAdvance'),array(),22).'</a>';
						$edit_link  .= '<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToDelete').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=thematic_advance_delete&thematic_id='.$thematic['id'].'&thematic_advance_id='.$thematic_advance['id'].'">'.Display::return_icon('delete.png',get_lang('Delete'),'',22).'</a></center>';
						
						//Links
						$edit_link = Display::div(Display::div($edit_link , array('id'=>'thematic_advance_tools_'.$thematic_advance['id'], 'class'=>'thematic_advance_actions')), array('style'=>'height:20px;'));
					}
					
					$thematic_advance_item =  isset($thematic_advance_div[$thematic['id']][$thematic_advance['id']]) ? $thematic_advance_div[$thematic['id']][$thematic_advance['id']] : null;
					
					echo Display::div($thematic_advance_item, array('id'=>'thematic_advance_'.$thematic_advance['id']));
					
					echo $edit_link;						
						
					echo '</td>';
					
					//if (api_is_allowed_to_edit(null, true) && api_get_session_id() == $thematic['session_id']) {
					if (api_is_allowed_to_edit(null, true)) {					    
    					if (empty($thematic_id)) {
    						$checked = '';    						
    						if ($last_done_thematic_advance == $thematic_advance['id']) {
    							$checked = 'checked';
    						}
    						$style = '';
    						if ($thematic_advance['done_advance'] == 1) {    						    
    							$style = ' style="background-color:#E5EDF9" ';
    						} else {
    							$style = ' style="background-color:#fff" ';
    						}											 			
    						echo '<td id="td_done_thematic_'.$thematic_advance['id'].'" '.$style.'><center>';
    						echo '<input type="radio" class="done_thematic" id="done_thematic_'.$thematic_advance['id'].'" name="done_thematic" value="'.$thematic_advance['id'].'" '.$checked.' onclick="update_done_thematic_advance(this.value)">';
    						echo '</center></td>';						
    					} else {    					    
    						if ($thematic_advance['done_advance'] == 1) {
    							echo '<td><center>'.get_lang('Done').'</center></td>';	
    						} else {
    							echo '<td><center>-</center></td>';
    						}									
    					}
					}					
					echo '</tr>';							 
				}
				echo '</table>';
			} else {
				echo '<div><em>'.get_lang('ThereIsNoAThematicAdvance').'</em></div>';
			}							
			echo '</td>';				
			echo '</tr>';
       } //End for
	   echo '</table>';
    } else {
	   echo '<div><em>'.get_lang('ThereIsNoAThematicSection').'</em></div>';		
    }	
} else if ($action == 'thematic_add' || $action == 'thematic_edit') {
		
	// Display form
	$form = new FormValidator('thematic_add','POST','index.php?action=thematic_add&'.api_get_cidreq());
	
	if ($action == 'thematic_edit') {
		$form->addElement('header', '', get_lang('EditThematicSection'));	
	}
	
	$form->addElement('hidden', 'sec_token', $token);
	$form->addElement('hidden', 'action', $action);
	
	if (!empty($thematic_id)) {
		$form->addElement('hidden', 'thematic_id',$thematic_id);
	}
		
	$form->add_textfield('title', get_lang('Title'), true, array('size'=>'50'));
	$form->add_html_editor('content', get_lang('Content'), false, false, array('ToolbarSet' => 'TrainingDescription', 'Width' => '80%', 'Height' => '150'));	
	$form->addElement('html','<div class="clear" style="margin-top:50px;"></div>');
	$form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');
	
    $show_form = true;
    
	if (!empty($thematic_data)) {
        
        if (api_get_session_id()) {
        	if ($thematic_data['session_id'] != api_get_session_id()) {
        		$show_form  = false;
                Display::display_error_message(get_lang('NotAllowedClickBack'),false);  
        	}
        }
		// set default values
		$default['title'] = $thematic_data['title'];
		$default['content'] = $thematic_data['content'];	
		$form->setDefaults($default);
	}
	
	// error messages
	if ($error) {	
		Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'),false);	
	}
    if ($show_form)
	$form->display();		
}