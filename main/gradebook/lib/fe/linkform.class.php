<?php
include_once ('../../../inc/global.inc.php');
include_once ('../be.inc.php');
include_once ('../gradebook_functions.inc.php');
include_once (api_get_path(LIBRARY_PATH) . 'groupmanager.lib.php');
require_once (api_get_path(LIBRARY_PATH) . 'formvalidator/FormValidator.class.php');

/**
 * Forms related to links
 * @author Stijn Konings
 * @author Bert Steppé (made more generic)
 * @package dokeos.gradebook
 */
class LinkForm extends FormValidator
{

	const TYPE_CREATE = 1;
	const TYPE_MOVE = 2;

	private $category_object;
	private $link_object;
	private $extra;

	/**
	 * Builds a form containing form items based on a given parameter
	 * @param int form_type 1=choose link
	 * @param obj cat_obj the category object
	 * @param string form name
	 * @param method
	 * @param action
	 */
	function LinkForm($form_type, $category_object,$link_object, $form_name, $method = 'post', $action = null, $extra = null)
	{
		parent :: __construct($form_name, $method, $action);

		if (isset ($category_object))
			$this->category_object = $category_object;
		if (isset ($link_object))
			$this->link_object = $link_object;
		if (isset ($extra))
			$this->extra = $extra;

		if ($form_type == self :: TYPE_CREATE)
			$this->build_create();
		elseif ($form_type == self :: TYPE_MOVE)
			$this->build_move();

		//$this->setDefaults();
	}

	protected function build_move()
	{
		$renderer =& $this->defaultRenderer();
		$renderer->setElementTemplate('<span>{element}</span> ');
		$this->addElement('static',null,null,'"'.$this->link_object->get_name().'" ');
		$this->addElement('static',null,null,get_lang('MoveTo').' : ');
		$select = $this->addElement('select','move_cat',null,null);
		foreach ($this->link_object->get_target_categories() as $cat)
		{
			for ($i=0;$i<$cat[2];$i++)
			{
				$line .= '&mdash;';
			}
			$select->addoption($line.' '.$cat[1],$cat[0]);
			$line = '';
		}
   		$this->addElement('submit', null, get_lang('Ok'));		
	}

	protected function build_create()
	{
		
		$select = $this->addElement('select',
									'select_link',
									get_lang('ChooseLink'),
									null,
									array('onchange' => 'document.create_link.submit()'));

		$linktypes = LinkFactory :: get_all_types();

		$select->addoption('['.get_lang('ChooseLink').']', 0);

		foreach ($linktypes as $linktype)
		{
			$link = LinkFactory :: create ($linktype);
			$link->set_course_code($this->category_object->get_course_code());
			// disable this element if the link works with a dropdownlist
			// and if there are no links left
			if (!$link->needs_name_and_description()
				&& count($link->get_not_created_links()) == '0')
				$select->addoption($link->get_type_name(), $linktype, 'disabled');
			else
				$select->addoption($link->get_type_name(), $linktype);
		}

		if (isset($this->extra))
		{
			$this->setDefaults(array('select_link' => $this->extra));
		}

	}

}
?>