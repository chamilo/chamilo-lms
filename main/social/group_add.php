<?php
$language_file= 'admin';
$cidReset=true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'/formvalidator/FormValidator.class.php';
$request=api_is_xml_http_request();
$nameTools = api_xml_http_response_encode(get_lang('AddGroup'));

global $charset;
$table_message = Database::get_main_table(TABLE_MESSAGE);
$request=api_is_xml_http_request();
if ($request===true) {
	$form = new FormValidator('add_group','post','index.php?add_group=1#remote-tab-7');
} else {
	$form = new FormValidator('add_group');
}

// name
$form->addElement('text', 'name', get_lang('Name'));
$form->applyFilter('name', 'html_filter');
$form->applyFilter('name', 'trim');
$form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

// Description
$form->addElement('text', 'description', get_lang('Description'));
$form->applyFilter('description', 'html_filter');
$form->applyFilter('description', 'trim');


// url
$form->addElement('text', 'url', get_lang('URL'));
$form->applyFilter('url', 'html_filter');
$form->applyFilter('url', 'trim');

// Picture
$form->addElement('file', 'picture', get_lang('AddPicture'));

$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');

$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);


/*
	$form->add_textfield('id_text_name', api_xml_http_response_encode(get_lang('SendMessageTo')),true,array('size' => 40,'id'=>'id_text_name','onkeyup'=>'send_request_and_search()','autocomplete'=>'off','style'=>'padding:0px'));
	$form->addRule('id_text_name', api_xml_http_response_encode(get_lang('ThisFieldIsRequired')), 'required');
	$form->addElement('html','<div id="id_div_search" style="padding:0px" class="message-select-box" >&nbsp;</div>');
	$form->addElement('hidden','user_list',0,array('id'=>'user_list'));
	
$form->add_textfield('title', api_xml_http_response_encode(get_lang('Title')));
$form->add_html_editor('content', '', false, false, array('ToolbarSet' => 'Messages', 'Width' => '95%', 'Height' => '250'));
if (isset($_GET['re_id'])) {
	$form->addElement('hidden','re_id',Security::remove_XSS($_GET['re_id']));
	$form->addElement('hidden','save_form','save_form');
}

*/

$form->addElement('style_submit_button','add_group', api_xml_http_response_encode(get_lang('AddGroup')),'class="save"');

$form->setRequiredNote(api_xml_http_response_encode('<span class="form_required">*</span> <small>'.get_lang('ThisFieldIsRequired').'</small>'));
$form->setDefaults($default);
if ($form->validate()) {
	$values = $form->exportValues();
	var_dump($values);
	$receiver_user_id = $values['user_list'];
	$title = $values['title'];
	$content = $values['content'];
	//all is well, send the message
	//MessageManager::send_message($receiver_user_id, $title, $content);
	//MessageManager::display_success_message($receiver_user_id);
} else {
	$form->display();	
}



	
?>