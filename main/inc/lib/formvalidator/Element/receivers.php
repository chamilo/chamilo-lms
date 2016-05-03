<?php
/* For licensing terms, see /license.txt */

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
	public function __construct($elementName = null, $elementLabel = null, $attributes = null)
	{
		$this->receivers = $attributes['receivers'];
		$this->receivers_selected = $attributes['receivers_selected'];
		unset($attributes['receivers']);
		unset($attributes['receivers_selected']);
		parent::__construct($elementName, $elementLabel, $attributes);
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
	public function toHtml()
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
    public function getElementJS()
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
