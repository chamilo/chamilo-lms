<?php
/* For licensing terms, see /license.txt */

/**
* A dropdownlist with all themes to use with QuickForm
*/
class SelectTheme extends HTML_QuickForm_select
{
	/**
	 * Class constructor
	 */
	public function __construct($elementName=null, $elementLabel=null, $options=null, $attributes=null)
	{
		parent::__construct($elementName, $elementLabel, $options, $attributes);
		// Get all languages
		$themes = api_get_themes();
		$this->_options = array();
		$this->_values = array();
		$this->addOption('--',''); // no theme select
		for ($i=0; $i< count($themes[0]); $i++) {
			$this->addOption($themes[1][$i],$themes[0][$i]);
		}
	}
}
