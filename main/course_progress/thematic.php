<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for thematic control 
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.course_progress
*/

// protect a course script
api_protect_course_script(true);

if (api_is_allowed_to_edit(null, true)) {
	echo '<div class="actions" style="margin-bottom:30px">';
	echo '<a href="index.php?'.api_get_cidreq().'&action=thematic_details">'.Display::return_icon('view_table.gif',get_lang('ThematicDetails')).' '.get_lang('ThematicDetails').'</a>';	
	echo '<a href="index.php?'.api_get_cidreq().'&action=thematic_list">'.Display::return_icon('view_list.gif',get_lang('ThematicList')).' '.get_lang('ThematicList').'</a>';
	if ($action == 'thematic_list') {
		echo '<a href="index.php?'.api_get_cidreq().'&action=thematic_add">'.Display::return_icon('introduction_add.gif',get_lang('NewThematicSection')).' '.get_lang('NewThematicSection').'</a>';
	}
	echo '</div>';
}

if ($action == 'thematic_list') {
	
	$table = new SortableTable('thematic_list', array('Thematic', 'get_number_of_thematics'), array('Thematic', 'get_thematic_data'));
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false, array('style'=>'width:20px;'));
	$table->set_header(1, get_lang('Title'), false );	
	if (api_is_allowed_to_edit(null, true)) {
		$table->set_header(2, get_lang('Actions'), false,array('style'=>'text-align:center;width:40%;'));
		$table->set_form_actions(array ('thematic_delete_select' => get_lang('DeleteAllThematics')));	
	}
	
	echo '<div><strong>'.get_lang('ThematicList').'</strong></div><br />';	
	$table->display();
	
} else if ($action == 'thematic_details') {

	// display title
	if (!empty($thematic_id)) {
		echo '<div><strong>'.Security::remove_XSS($thematic_data[$thematic_id]['title'], STUDENT).': '.get_lang('Details').'</strong></div><br />';							
	} else {
		echo '<div><strong>'.get_lang('ThematicDetails').'</strong></div><br />';	
		// display information
		$message = '<strong>'.get_lang('Information').'</strong><br />';
		$message .= get_lang('ThematicDetailsDescription');	
		Display::display_normal_message($message, false);
		echo '<br />';			
	}
	
	// display thematic data
	if (!empty($thematic_data)) {
		
		// display progress
		if (!empty($thematic_id)) {
			echo '<div style="text-align:right;">'.get_lang('Progress').': <strong>'.$total_average_of_advances.'</strong>%</div><br />';
		} else {
			echo '<div style="text-align:right;">'.get_lang('Progress').': <strong><span id="div_result">'.$total_average_of_advances.'</span></strong>%</div><br />';
		}
		
		echo '<table width="100%" class="data_table">';	
		echo '<tr><th width="33%">'.get_lang('Thematic').'</th><th>'.get_lang('ThematicPlan').'</th><th width="33%">'.get_lang('ThematicAdvance').'</th></tr>';
	
			foreach ($thematic_data as $thematic) {			
				echo '<tr>';
				
				// display thematic data		
				echo '<td><div><strong>'.Security::remove_XSS($thematic['title'], STUDENT).'</strong></div><div>'.Security::remove_XSS($thematic['content'], STUDENT).'</div></td>';
				
				// display thematic plan data
				echo '<td>';					
					if (api_is_allowed_to_edit(null, true)) {
						echo '<div style="text-align:right"><a href="index.php?'.api_get_cidreq().'&origin=thematic_details&action=thematic_plan_list&thematic_id='.$thematic['id'].'">'.Display::return_icon('lp_quiz.png',get_lang('EditThematicPlan'),array('style'=>'vertical-align:middle')).'</a></div><br />';
					}
					if (!empty($thematic_plan_data[$thematic['id']])) {
						foreach ($thematic_plan_data[$thematic['id']] as $thematic_plan) {
							echo '<div><strong>'.Security::remove_XSS($thematic_plan['title'], STUDENT).'</strong></div><div>'.Security::remove_XSS($thematic_plan['description'], STUDENT).'</div>'; 
						}
					} else {
						echo '<div><em>'.get_lang('StillDoNotHaveAThematicPlan').'</em></div>';
					}				
				echo '</td>';
				
				// display thematic advance data
				echo '<td>';					
					if (api_is_allowed_to_edit(null, true)) {
						echo '<div style="text-align:right"><a href="index.php?'.api_get_cidreq().'&origin=thematic_details&action=thematic_advance_list&thematic_id='.$thematic['id'].'">'.Display::return_icon('lp_quiz.png',get_lang('EditThematicAdvance'),array('style'=>'vertical-align:middle')).'</a></div><br />';
					}					
					echo '<table width="100%">';
						if (!empty($thematic_advance_data[$thematic['id']])) {						
							foreach ($thematic_advance_data[$thematic['id']] as $thematic_advance) {
								$thematic_advance['start_date'] = api_get_local_time($thematic_advance['start_date']);
								$thematic_advance['start_date'] = api_format_date($thematic_advance['start_date'], DATE_TIME_FORMAT_LONG);
								echo '<tr>';
								echo '<td width="90%">';
									echo '<div><strong>'.$thematic_advance['start_date'].'</strong></div>';
									echo '<div>'.Security::remove_XSS($thematic_advance['content'], STUDENT).'</div>';
									echo '<div>'.get_lang('DurationInHours').' : '.$thematic_advance['duration'].'</div>';
								echo '</td>';
								if (empty($thematic_id) && api_is_allowed_to_edit(null, true)) {
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
									echo '<td id="td_done_thematic_'.$thematic_advance['id'].'" '.$style.'><center><input type="radio" id="done_thematic_'.$thematic_advance['id'].'" name="done_thematic" value="'.$thematic_advance['id'].'" '.$checked.' onclick="update_done_thematic_advance(this.value)"></center></td>';									
								} else {
									if ($thematic_advance['done_advance'] == 1) {
										echo '<td><center>'.get_lang('Done').'</center></td>';	
									} else {
										echo '<td><center>-</center></td>';
									}									
								}
								echo '</tr>';							 
							}
						} else {
							echo '<tr><td width="90%"><div><em>'.get_lang('ThereIsNoAThematicAdvance').'</em></div></td><td>&nbsp;</td>';
						}									
					echo '</table>';							
				echo '</td>';				
				echo '</tr>';				
			}
		echo '</table>';
	} else {
		echo '<div><em>'.get_lang('ThereIsNoAThematicSection').'</em><br /><br />';
		if (api_is_allowed_to_edit(null, true)) {
			echo '<a href="index.php?'.api_get_cidreq().'&action=thematic_add">'.Display::return_icon('addd.gif',get_lang('CreateAThematicSection'), array('style'=>'vertical-align:middle')).' '.get_lang('CreateAThematicSection').'</a>';
		}
		echo '</div>';
	}
	
} else if ($action == 'thematic_add' || $action == 'thematic_edit') {

	if (!$error) {
		$token = md5(uniqid(rand(),TRUE));
		$_SESSION['thematic_token'] = $token;
	}
	
	$header_form = get_lang('NewThematicSection');
	if ($action == 'thematic_edit') {
		$header_form = get_lang('EditThematicSection');	
	}
	
	// display form
	$form = new FormValidator('thematic_add','POST','index.php?action=thematic_list&'.api_get_cidreq(),'','style="width: 100%;"');
	
	$form->addElement('header', '', $header_form);	
	$form->addElement('hidden', 'thematic_token',$token);
	$form->addElement('hidden', 'action', $action);
	
	if (!empty($thematic_id)) {
		$form->addElement('hidden', 'thematic_id',$thematic_id);
	}
		
	$form->add_textfield('title', get_lang('Title'), true, array('size'=>'50'));
	$form->add_html_editor('content', get_lang('Content'), false, false, array('ToolbarSet' => 'TrainingDescription', 'Width' => '100%', 'Height' => '250'));	
	$form->addElement('html','<div class="clear" style="margin-top:50px;"></div>');
	$form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');
	
	if (!empty($thematic_data)) {
		// set default values
		$default['title'] = $thematic_data['title'];
		$default['content'] = $thematic_data['content'];	
		$form->setDefaults($default);
	}
	
	// error messages
	if ($error) {	
		Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'),false);	
	}
	$form->display();
		
} 
?>