<?php
/* For licensing terms, see /license.txt */

/**
 * Class SelectLanguage
 * A dropdownlist with all languages to use with QuickForm
 */
class SelectLanguage extends HTML_QuickForm_select
{
	/**
	 * Class constructor
	 */
	public function __construct($elementName = null, $elementLabel = null, $options = null, $attributes = null)
	{
		parent::__construct($elementName, $elementLabel, $options, $attributes);
		// Get all languages
		$languages = api_get_languages();
		$this->_options = array();
		$this->_values = array();
		foreach ($languages['name'] as $index => $name) {
			if ($languages['folder'][$index] == api_get_setting('platformLanguage')) {
				$this->addOption($name, $languages['folder'][$index], array('selected'=>'selected'));
			} else {
				$this->addOption($name, $languages['folder'][$index]);
			}
		}
	}
}
