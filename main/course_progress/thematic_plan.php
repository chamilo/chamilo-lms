<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for thematic plan 
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.course_progress
*/

// actions menu
$new_thematic_plan_data = array();
if (!empty($thematic_plan_data))
foreach($thematic_plan_data as $thematic_item) {    
    $thematic_simple_list[] = $thematic_item['description_type'];
    $new_thematic_plan_data[$thematic_item['description_type']] = $thematic_item;       
}

$new_id = ADD_THEMATIC_PLAN;
if (!empty($thematic_simple_list))
foreach($thematic_simple_list as $item) {    
    if ($item >= ADD_THEMATIC_PLAN) {        
    	$new_id = $item + 1;
        $default_thematic_plan_title[$item] = $new_thematic_plan_data[$item]['title'];               
    }
}

$i=1;

echo '<div class="actions" style="margin-bottom:30px">';

if ($action == 'thematic_plan_edit') {
    echo '<a href="index.php?action=thematic_plan_list&'.api_get_cidreq().'&thematic_id='.$thematic_id.'">'.Display::return_icon('back.png', get_lang('Back'), array(), 32).'</a>&nbsp;&nbsp;';
} else {    
    echo '<a href="index.php?action=thematic_plan_list&'.api_get_cidreq().'&action=thematic_details&'.api_get_cidreq().'">'.Display::return_icon('back.png', get_lang('Back'), array(), 32).'</a>&nbsp;&nbsp;';
    echo '<a href="index.php?action=thematic_plan_edit&'.api_get_cidreq().'&description_type='.$new_id.'&thematic_id='.$thematic_id.'">'.Display::return_icon('wizard.png', get_lang('NewBloc'), array(), 32).'</a>';    
}
echo '</div>';

echo Display::tag('h2', $thematic_data['title']);
echo $thematic_data['content'];


if ($message == 'ok') {
    Display::display_normal_message(get_lang('ThematicSectionHasBeenCreatedSuccessfull'));    
}
if ($action == 'thematic_plan_list') {    
            
        $form = new FormValidator('thematic_plan_add','POST','index.php?action=thematic_plan_list&thematic_id='.$thematic_id.'&'.api_get_cidreq().$param_gradebook,'','style="width: 100%;"');                
        $form->addElement('hidden', 'action', $action);
        $form->addElement('hidden', 'thematic_plan_token', $token);
        $form->addElement('hidden', 'thematic_id', $thematic_id);   
                    
        //var_dump($default_thematic_plan_title);
        //var_dump($thematic_simple_list);
        
        foreach ($default_thematic_plan_title as $id => $title) {
        //foreach ($thematic_simple_list as $id) {
            //$title = $default_thematic_plan_title[$id];
            $form->addElement('hidden', 'description_type['.$id.']', $id);
            $form->add_textfield('title['.$id.']', get_lang('Title'), true, array('size'=>'50'));
            $form->add_html_editor('description['.$id.']', get_lang('Description'), false, false, array('ToolbarSet' => 'TrainingDescription', 'Width' => '100%', 'Height' => '200'));   
            $form->addElement('html','<div class="clear" style="margin-top:50px;"></div>');
              
            if (!empty($thematic_simple_list) && in_array($id, $thematic_simple_list)) {
                $thematic_plan = $new_thematic_plan_data[$id];           
      
                // set default values
                $default['title['.$id.']']       = $thematic_plan['title'];
                $default['description['.$id.']'] = $thematic_plan['description'];                
                $thematic_plan = null;

            } else {
                $thematic_plan = null;               
                $default['title['.$id.']']       = $title;
                $default['description['.$id.']']= '';                    
            }            
            
            $form->setDefaults($default);            
		}        
        
        $form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');        
        $form->display();
        	
} else if ($action == 'thematic_plan_add' || $action == 'thematic_plan_edit') {

	if ($description_type >= ADD_THEMATIC_PLAN) {
		$header_form = get_lang('NewBloc');
	} else {
		$header_form = $default_thematic_plan_title[$description_type];		
	}	
	if (!$error) {
		$token = md5(uniqid(rand(),TRUE));
		$_SESSION['thematic_plan_token'] = $token;
	}

	// display form
	$form = new FormValidator('thematic_plan_add','POST','index.php?action=thematic_plan_edit&thematic_id='.$thematic_id.'&'.api_get_cidreq().$param_gradebook,'','style="width: 100%;"');
	//$form->addElement('header', '', $header_form);
	$form->addElement('hidden', 'action', $action);
	$form->addElement('hidden', 'thematic_plan_token', $token);
	
	if (!empty($thematic_id)) {
		$form->addElement('hidden', 'thematic_id', $thematic_id);	
	}
	if (!empty($description_type)) {
		$form->addElement('hidden', 'description_type', $description_type);	
	}

	$form->add_textfield('title', get_lang('Title'), true, array('size'=>'50'));
	$form->add_html_editor('description', get_lang('Description'), false, false, array('ToolbarSet' => 'TrainingDescription', 'Width' => '100%', 'Height' => '200'));	
	$form->addElement('html','<div class="clear" style="margin-top:50px;"></div>');
	$form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');
	
	if ($description_type < ADD_THEMATIC_PLAN) {
		$default['title'] = $default_thematic_plan_title[$description_type];
	}
	if (!empty($thematic_plan_data)) {
		// set default values
		$default['title'] = $thematic_plan_data[0]['title'];
		$default['description'] = $thematic_plan_data[0]['description'];		
	}	
	$form->setDefaults($default);
	
	if (isset($default_thematic_plan_question[$description_type])) {
		$message = '<strong>'.get_lang('QuestionPlan').'</strong><br />';
		$message .= $default_thematic_plan_question[$description_type];
		Display::display_normal_message($message, false);
	}

	// error messages
	if ($error) { 	
		Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'),false);	
	}
	
	$form->display();		
}

?>