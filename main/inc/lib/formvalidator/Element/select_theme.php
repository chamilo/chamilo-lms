<?php
/* For licensing terms, see /license.txt */

require_once 'HTML/QuickForm/select.php';
/**
* A dropdownlist with all themes to use with QuickForm
*/
class HTML_QuickForm_Select_Theme extends HTML_QuickForm_select
{
	/**
	 * Class constructor
	 */
	function HTML_QuickForm_Select_Theme($elementName=null, $elementLabel=null, $options=null, $attributes=null) {
	    if (!isset($attributes['class'])) {
            $attributes['class'] = 'chzn-select';
        }        
		parent::HTML_QuickForm_Select($elementName, $elementLabel, $options, $attributes);
		// Get all languages
		$themes = api_get_themes();
		$this->_options = array();
		$this->_values = array();
		$this->addOption('--',''); // no theme select
		for ($i=0; $i< count($themes[0]);$i++) {
			$this->addOption($themes[1][$i],$themes[0][$i]);
		}		
	}
}