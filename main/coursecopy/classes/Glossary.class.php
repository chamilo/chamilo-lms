<?php
/* For licensing terms, see /license.txt */

require_once 'Resource.class.php';

/**
 * Add resource glossary
 * @author Isaac flores <florespaz@bidsoftperu.com>
 * @package dokeos.backup
 */
class Glossary extends Resource
{
	public $glossary_id;
	public $name;
	public $description;
	public $display_order;

	/**
	 * Create a new Glossary
	 * @param int $id
	 * @param string $name
	 * @param string $description
	 * @param int $display_order
	 */
	function Glossary($id,$name,$description,$display_order)
	{
		parent::Resource($id,RESOURCE_GLOSSARY);
		$this->glossary_id = $id;
		$this->name = $name;
		$this->description = $description;
		$this->display_order = $display_order;


	}
	/**
	 * Show this glossary
	 */
	function show() {
		parent::show();
		echo $this->name;
	}
}
?>