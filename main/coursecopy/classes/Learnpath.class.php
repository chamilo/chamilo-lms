<?php // $Id: Learnpath.class.php 11364 2007-03-03 10:48:36Z yannoo $
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
/**
 * A learnpath
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
 */
class Learnpath extends Resource
{
	/**
	 * Type of learnpath (can be dokeos (1), scorm (2), aicc (3))
	 */
	var $lp_type;
	/**
	 * The name
	 */
	var $name;
	/**
	 * The reference
	 */
	var $ref;
	/**
	 * The description
	 */
	var $description;
	/**
	 * Path to the learning path files
	 */
	var $path;
	/**
	 * Whether additional commits should be forced or not
	 */
	var $force_commit;
	/**
	 * View mode by default ('embedded' or 'fullscreen')
	 */
	var $default_view_mod;
	/**
	 * Default character encoding
	 */
	var $default_encoding;
	/**
	 * Display order
	 */
	var $display_order;
	/**
	 * Content editor/publisher
	 */
	var $content_maker;
	/**
	 * Location of the content (local or remote)
	 */
	var $content_local;
	/**
	 * License of the content
	 */
	var $content_license;
	/**
	 * Whether to prevent reinitialisation or not
	 */
	var $prevent_reinit;
	/**
	 * JavaScript library used
	 */
	var $js_lib;
	/**
	 * Debug level for this lp
	 */
	var $debug;
	/**
	 * The items
	 */
	var $items;
	/**
	 * The learnpath visibility on the homepage
	 */
	var $visibility;

	/**
	 * Create a new learnpath
	 * @param integer ID
	 * @param integer Type (1,2,3,...)
	 * @param string $name
	 * @param string $path
	 * @param string $ref
	 * @param string $description
	 * @param string $content_local
	 * @param string $default_encoding
	 * @param string $default_view_mode
	 * @param bool   $prevent_reinit
	 * @param bool   $force_commit
	 * @param string $content_maker
	 * @param integer $display_order
	 * @param string $js_lib
	 * @param string $content_license
	 * @param integer $debug
	 * @param string $visibility
	 * @param array  $items
	 */
	function Learnpath($id,$type,$name,$path,$ref,$description,$content_local,$default_encoding,$default_view_mode,$prevent_reinit,$force_commit,$content_maker,$display_order,$js_lib,$content_license,$debug,$visibility,$items)
	{
		parent::Resource($id,RESOURCE_LEARNPATH);
		$this->lp_type = $type; 
		$this->name = $name;
		$this->path = $path;
		$this->ref = $ref;
		$this->description = $description;
		$this->content_local = $content_local;
		$this->default_encoding = $default_encoding;
		$this->default_view_mod = $default_view_mode;
		$this->prevent_reinit = $prevent_reinit;
		$this->force_commit = $force_commit;
		$this->content_maker = $content_maker;
		$this->display_order = $display_order;
		$this->js_lib = $js_lib;
		$this->content_license = $content_license;
		$this->debug = $debug;
		$this->visibility=$visibility;
		$this->items = $items;
	}
	/**
	 * Get the items
	 */
	function get_items()
	{
		return $this->items;
	}
	/**
	 * Check if a given resource is used as an item in this chapter
	 */
	function has_item($resource)
	{
		foreach($this->items as $index => $item)
		{
			if( $item['id'] == $resource->get_id() && $item['type'] == $resource->get_type())
			{
				return true;
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