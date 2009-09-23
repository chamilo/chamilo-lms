<?php // $Id: Course.class.php 19948 2009-04-21 17:27:59Z juliomontoya $
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

require_once 'LinkCategory.class.php';
require_once 'Announcement.class.php';
require_once 'Event.class.php';

/**
 * A course-object to use in Export/Import/Backup/Copy
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
 */
class Course
{
	var $resources;
	var $code;
	var $path;
	var $destination_path;
	var $destination_db;
	/**
	 * Create a new Course-object
	 */
	function Course()
	{
		$this->resources = array ();
		$this->code = '';
		$this->path = '';
		$this->backup_path = '';
	}
	/**
	 * Check if a resource links to the given resource
	 */
	function is_linked_resource(& $resource_to_check)
	{
		foreach($this->resources as $type => $resources) {
			if (is_array($resources)) {
				foreach($resources as $id => $resource) {
					if( $resource->links_to($resource_to_check) ) {
						return true;
					}
					if( $type == RESOURCE_LEARNPATH && get_class($resource)=='Learnpath') {
						if($resource->has_item($resource_to_check)) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}
	/**
	 * Add a resource from a given type to this course
	 */
	function add_resource(& $resource)
	{
		$this->resources[$resource->get_type()][$resource->get_id()] = $resource;
	}
	/**
	 * Does this course has resources?
	 * @param const $resource_type Check if this course has resources of the
	 * given type. If no type is given, check if course has resources of any
	 * type.
	 */
	function has_resources($resource_type = null)
	{
		if( $resource_type != null)
		{
			return is_array($this->resources[$resource_type]) && ( count($this->resources[$resource_type]) > 0 );
		}
		return (count($this->resources) > 0);
	}
	/**
	 * Show this course resources
	 */
	function show()
	{
		echo '<pre>';
		print_r($this);
		echo '</pre>';

//		foreach ($this->resources as $id => $resources)
//		{
//			foreach ($resources as $type => $resource)
//			{
//				$resource->show();
//			}
//		}
	}

}
?>
