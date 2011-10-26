<?php
/* For licensing terms, see /license.txt */

require_once ('HTML/QuickForm/select.php');
/**
* A dropdownlist with all languages to use with QuickForm
*/
class HTML_QuickForm_Select_Language extends HTML_QuickForm_select
{
	/**
	 * Class constructor
	 */
	function HTML_QuickForm_Select_Language($elementName=null, $elementLabel=null, $options=null, $attributes=null){
		if (!isset($attributes['class'])) {
			$attributes['class'] = 'chzn-select';
		}
		parent::HTML_QuickForm_Select($elementName, $elementLabel, $options, $attributes);
		// Get all languages
		$languages = api_get_languages();
		$this->_options = array();
		$this->_values = array();
		foreach ($languages['name'] as $index => $name) {
			if($languages['folder'][$index] == api_get_setting('platformLanguage')) {
				$this->addOption($name,$languages['folder'][$index],array('selected'=>'selected'));
			} else {
				$this->addOption($name,$languages['folder'][$index]);
			}
		}
	}
}
?>