<?php
/* For licensing terms, see /license.txt */
use Chamilo\CoreBundle\Framework\Container;

/**
* A dropdownlist with all languages to use with QuickForm
*/
class HTML_QuickForm_Select_Language extends HTML_QuickForm_select
{
	/**
	 * Class constructor
	 */
	function HTML_QuickForm_Select_Language($elementName=null, $elementLabel=null, $options=null, $attributes=null)
    {
		if (!isset($attributes['class'])) {
			$attributes['class'] = 'chzn-select';
		}
		parent::HTML_QuickForm_Select($elementName, $elementLabel, $options, $attributes);
		// Get all languages
		$languages = api_get_languages();
		$this->_options = array();
		$this->_values = array();
        $platformLanguage = Container::getTranslator()->getLocale();
		foreach ($languages as $language) {
            if ($language['isocode'] == $platformLanguage) {
                $this->addOption(
                    $language['english_name'],
                    $language['isocode'],
                    array('selected' => 'selected')
                );
            } else {
                $this->addOption($language['english_name'], $language['isocode']);
            }
		}
	}
}
