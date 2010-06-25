<?php
/* For licensing terms, see /license.txt */
require_once ('HTML/QuickForm/date.php');
/**
 * Form element to select a date and hour (with popup datepicker)
 */
class HTML_QuickForm_datepickerdate extends HTML_QuickForm_date
{
	/**
	 * Constructor
	 */
	function HTML_QuickForm_datepickerdate($elementName = null, $elementLabel = null, $attributes = null) {
		global $myMinYear, $myMaxYear;
		$js_form_name = $attributes['form_name'];
		unset($attributes['form_name']);
		HTML_QuickForm_element :: HTML_QuickForm_element($elementName, $elementLabel, $attributes);
		$this->_persistantFreeze = true;
		$this->_appendName = true;
		$this->_type = 'datepicker';
		$popup_link = '<a href="javascript:openCalendar(\''.$js_form_name.'\',\''.$elementName.'\')"><img src="'.api_get_path(WEB_IMG_PATH).'calendar_select.gif" style="vertical-align:middle;" alt="Select Date" /></a>';
		$special_chars = array ('D', 'l', 'd', 'M', 'F', 'm', 'y', 'H', 'a', 'A', 's', 'i', 'h', 'g', ' ');
		foreach ($special_chars as $index => $char)
		{
			$popup_link = str_replace($char, "\\".$char, $popup_link);
		}
		$lang_code = api_get_language_isocode();
		// If translation not available in PEAR::HTML_QuickForm_date, add the Chamilo-translation
		if(! array_key_exists($lang_code,$this->_locale))
		{
			$this->_locale[$lang_code]['months_long'] = api_get_months_long();
		}
		$this->_options['format'] = 'dFY '.$popup_link;
		$this->_options['minYear'] = date('Y')-5;
		$this->_options['maxYear'] = date('Y')+10;
		$this->_options['language'] = $lang_code;
		//$this->_options['addEmptyOption'] = true;
		//$this->_options['emptyOptionValue'] = 0;
		//$this->_options['emptyOptionText'] = ' -- ';
	}
	/**
	 * HTML code to display this datepicker
	 */
	function toHtml()
	{
		$js = $this->getElementJS();
		return $js.parent :: toHtml();
	}
	/**
	 * Get the necessary javascript for this datepicker
	 */
	function getElementJS()
	{
		$js = '';
		if(!defined('DATEPICKER_JAVASCRIPT_INCLUDED'))
		{
			define('DATEPICKER_JAVASCRIPT_INCLUDED',1);
			$js = "\n";
			$js .= '<script src="';
			$js .= api_get_path(WEB_CODE_PATH).'inc/lib/formvalidator/Element/';
			$js .= 'tbl_change.js.php" type="text/javascript"></script>';
			$js .= "\n";
		}
		return $js;
	}
	/**
	 * Export the date value in MySQL format
	 * @return string YYYY-MM-DD HH:II:SS
	 */
	function exportValue()
	{
		$values = parent::getValue();
		$y = $values['Y'][0];
		$m = $values['F'][0];
		$d = $values['d'][0];
		$m = $m < 10 ? '0'.$m : $m;
		$d = $d < 10 ? '0'.$d : $d;
		$datetime = $y.'-'.$m.'-'.$d;
		$result[$this->getName()]= $datetime;
		return $result;
	}
	/**
	 * Sets an option to a value
	 */
	function setLocalOption($name,$value)
	{
		$this->_options[$name] = $value;
	}
}
?>