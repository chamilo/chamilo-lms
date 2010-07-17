<?php
/* For licensing terms, see /license.txt */
require_once dirname(__FILE__).'/../../../inc/global.inc.php';
require_once dirname(__FILE__).'/../be.inc.php';
require_once dirname(__FILE__).'/../gradebook_functions.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'formvalidator/FormValidator.class.php';
/**
 * Extends formvalidator with import and export forms
 * @author Stijn Konings
 * @package dokeos.gradebook
 */
class UserForm extends FormValidator
{
	const TYPE_USER_INFO= 1;
	const TYPE_SIMPLE_SEARCH = 3;
	/**
	 * Builds a form containing form items based on a given parameter
	 * @param int form_type 1 = user_info
	 * @param user array
	 * @param string form name
	 * @param method
	 * @param action
	 */
	function UserForm($form_type, $user, $form_name, $method= 'post', $action= null) {
		parent :: __construct($form_name, $method, $action);
		$this->form_type= $form_type;
		if (isset ($user)) {
			$this->user_info= $user;
		}
		if (isset ($result_object)) {
			$this->result_object= $result_object;
		}
		if ($this->form_type == self :: TYPE_USER_INFO) {
			$this->build_user_info_form();
		}
		elseif ($this->form_type == self :: TYPE_SIMPLE_SEARCH) {
			$this->build_simple_search();
		}
		$this->setDefaults();
	}

	protected function build_simple_search() {
		if (isset($_GET['search']) && (!empty($_GET['search']))) {
		   	$this->setDefaults(array(
   		    'keyword' => Security::remove_XSS($_GET['search'])
   		    ));
		}
		$renderer =& $this->defaultRenderer();
		$renderer->setElementTemplate('<span>{element}</span> ');
		$this->addElement('text','keyword','');
		$this->addElement('style_submit_button','submit',get_lang('Search'),'class="search"');
	}

	protected function build_user_info_form() {
		if (api_is_western_name_order()) {
			$this->addElement('static', 'fname', get_lang('FirstName'), $this->user_info['firstname']);
			$this->addElement('static', 'lname', get_lang('LastName'), $this->user_info['lastname']);
		} else {
			$this->addElement('static', 'lname', get_lang('LastName'), $this->user_info['lastname']);
			$this->addElement('static', 'fname', get_lang('FirstName'), $this->user_info['firstname']);
		}
		$this->addElement('static', 'uname', get_lang('UserName'), $this->user_info['username']);
		$this->addElement('static', 'email', get_lang('Email'), '<a href="mailto:' . $this->user_info['email'] . '">' . $this->user_info['email'] . '</a>');
		$this->addElement('static', 'ofcode', get_lang('OfficialCode'), $this->user_info['official_code']);
		$this->addElement('static', 'phone', get_lang('Phone'), $this->user_info['phone']);
		$this->addElement('style_submit_button', 'submit', get_lang('Back'),'class="save"');
	}
	function display() {
		parent :: display();
	}
	function setDefaults($defaults= array ()) {
		parent :: setDefaults($defaults);
	}
}