<?php // $Id: Document.class.php 4733 2005-05-02 08:54:49Z bmol $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Bart Mollet (bart.mollet@hogent.be)

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