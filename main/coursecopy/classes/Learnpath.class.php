<?php // $Id: Learnpath.class.php 5703 2005-07-05 09:22:24Z olivierb78 $
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
require_once('Learnpath.class.php');
/**
 * A learnpath
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
 */
class Learnpath extends Resource
{
	/**
	 * The name
	 */
	var $name;
	/**
	 * The description
	 */
	var $description;
	/**
	 * The chapters
	 */
	var $chapters;

	var $visibility;

	/**
	 * Create a new learnpath
	 * @param string $name
	 * @param string $description
	 */
	function Learnpath($id,$name,$description,$visibility,$chapters)
	{
		parent::Resource($id,RESOURCE_LEARNPATH);
		$this->name = $name;
		$this->description = $description;
		$this->visibility=$visibility;
		$this->chapters = $chapters;
	}
	/**
	 * Get the chapters
	 */
	function get_chapters()
	{
		return $this->chapters;
	}
	/**
	 * Check if a given resource is used as an item in this chapter
	 */
	function has_item($resource)
	{
		foreach($this->chapters as $index => $chapter)
		{
			foreach($chapter['items'] as $index => $item)
			{
				if( $item['id'] == $resource->get_id() && $item['type'] == $resource->get_type())
				{
					return true;
				}
			}
		}
		return false;
	}
	/**
	 * Show this learnpath
	 */
	function show()
	{
		parent::show();
		echo $this->name;
	}
}
?>