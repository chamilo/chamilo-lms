<?php
/* For licensing terms, see /license.txt */
require_once ('HTML/QuickForm/date.php');
/**
 * Form element to select a date and hour (with popup datepicker)
 */
class HTML_QuickForm_datepicker extends HTML_QuickForm_date
{
	/**
	 * Constructor
	 */
	function HTML_QuickForm_datepicker($elementName = null, $elementLabel = null, $attributes = null, $optionIncrement = null)
	{
		$js_form_name = $attributes['form_name'];
		unset($attributes['form_name']);
		HTML_QuickForm_element :: HTML_QuickForm_element($elementName, $elementLabel, $attributes);
		$this->_persistantFreeze = true;
		$this->_appendName = true;
		$this->_type = 'datepicker';
		$popup_link = '<a href="javascript:openCalendar(\''.$js_form_name.'\',\''.$elementName.'\')"><img src="'.api_get_path(WEB_IMG_PATH).'calendar_select.gif" style="vertical-align:middle;" alt="Select Date" /></a>';
		$special_chars = array ('D', 'l', 'd', 'M', 'F', 'm', 'y', 'H', 'a', 'A', 's', 'i', 'h', 'g', ' ');
		$hour_minute_devider = get_lang("HourMinuteDivider");
		foreach ($special_chars as $index => $char)
		{
			$popup_link = str_replace($char, "\\".$char, $popup_link);
			$hour_minute_devider = str_replace($char, "\\".$char, $hour_minute_devider);
		}
		$lang_code = api_get_language_isocode();
		// If translation not available in PEAR::HTML_QuickForm_date, add the Chamilo-translation
		if(! array_key_exists($lang_code,$this->_locale))
		{
			$this->_locale[$lang_code]['months_long'] = api_get_months_long();
		}
		$this->_options['format'] = 'dFY '.$popup_link.'   H '.$hour_minute_devider.' i';
		$this->_options['minYear'] = date('Y')-5;
		$this->_options['maxYear'] = date('Y')+10;
		$this->_options['language'] = $lang_code;
		//$this->_options['addEmptyOption'] = true;
		//$this->_options['emptyOptionValue'] = 0;
		//$this->_options['emptyOptionText'] = ' -- ';
		if (isset($optionIncrement)) {
        $this->_options['optionIncrement']['i'] = intval($optionIncrement);
        }
		
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
	function exportValue(&$submitValues, $assoc = false)
	{
		$values = parent::getValue();
		$y = $values['Y'][0];
		$m = $values['F'][0];
		$d = $values['d'][0];
		$h = $values['H'][0];
		$i = $values['i'][0];
		$m = $m < 10 ? '0'.$m : $m;
		$d = $d < 10 ? '0'.$d : $d;
		$h = $h < 10 ? '0'.$h : $h;
		$i = $i < 10 ? '0'.$i : $i;
		$datetime = $y.'-'.$m.'-'.$d.' '.$h.':'.$i.':00';
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
