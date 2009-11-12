<?php
// $Id: receivers.php 7727 2006-02-09 13:37:04Z turboke $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
require_once 'HTML/QuickForm/group.php';
require_once 'HTML/QuickForm/radio.php';
require_once 'HTML/QuickForm/advmultiselect.php';
/**
 * Form element to select receivers
 * This element contains 1 radio-buttons. One with label 'everybody' and one
 * with label 'select users/groups'. Only if the second radio-button is
 * selected, 2 select-list show up. The user can move items between the 2
 * checkboxes.
 */
class HTML_QuickForm_receivers extends HTML_QuickForm_group
{
	/**
	 * Array of all receivers
	 */
	var $receivers;
	/**
	 * Array of selected receivers
	 */
	var $receivers_selected;
	/**
	 * Constructor
	 * @param string $elementName
	 * @param string $elementLabel
	 * @param array $attributes This should contain the keys 'receivers' and
	 * 'receivers_selected'
	 */
	function HTML_QuickForm_receivers($elementName = null, $elementLabel = null, $attributes = null)
	{
		$this->receivers = $attributes['receivers'];
		$this->receivers_selected = $attributes['receivers_selected'];
		unset($attributes['receivers']);
		unset($attributes['receivers_selected']);
		$this->HTML_QuickForm_element($elementName, $elementLabel, $attributes);
		$this->_persistantFreeze = true;
		$this->_appendName = true;
		$this->_type = 'receivers';
	}
	/**
	 * Create the form elements to build this element group
	 */
	function _createElements()
	{
		$this->_elements[] = new HTML_QuickForm_Radio('receivers', '', get_lang('Everybody'), '0', array ('onclick' => 'javascript:receivers_hide(\'receivers_to\')'));
		$this->_elements[0]->setChecked(true);
		$this->_elements[] = new HTML_QuickForm_Radio('receivers', '', get_lang('SelectGroupsUsers'), '1', array ('onclick' => 'javascript:receivers_show(\'receivers_to\')'));
		$this->_elements[] = new HTML_QuickForm_advmultiselect('to', '', $this->receivers);
		$this->_elements[2]->setSelected($this->receivers_selected);
	}
	/**
	 * HTML representation
	 */
	function toHtml()
	{
		include_once ('HTML/QuickForm/Renderer/Default.php');
		$this->_separator = '<br/>';
		$renderer = & new HTML_QuickForm_Renderer_Default();
		$renderer->setElementTemplate('{element}');
		$select_boxes = $this->_elements[2];
		$select_boxes->setElementTemplate('<div style="margin-left:20px;display:block;" id="receivers_'.$select_boxes->getName().'">'.$select_boxes->_elementTemplate.'</div>');
		parent :: accept($renderer);
		$js = $this->getElementJS();
		return $renderer->toHtml().$js;
	}
	/**
	 * Get the necessary javascript
	 */
	function getElementJS()
	{
		$js = "<script type=\"text/javascript\">
					/* <![CDATA[ */
					receivers_hide('receivers_to');
					function receivers_show(item) {
						el = document.getElementById(item);
						el.style.display='';
					}
					function receivers_hide(item) {
						el = document.getElementById(item);
						el.style.display='none';
					}
					/* ]]> */
					</script>\n";
		return $js;
	}
	/**
	 * accept renderer
	 */
	function accept(& $renderer, $required = false, $error = null)
	{
		$renderer->renderElement($this, $required, $error);
	}
}
?>