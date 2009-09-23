<?php // $Id: Event.class.php 5243 2005-05-31 08:34:12Z bmol $
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
 * An event
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
class Event extends Resource
{
	/**
	 * The title
	 */
	var $title;
	/**
	 * The content
	 */
	var $content;
	/**
	 * The start date
	 */
	var $start_date;
	/**
	 * The end date
	 */
	var $end_date;
	/**
	 * Create a new Event
	 * @param int $id
	 * @param string $title
	 * @param string $content
	 * @param string $date
	 * @param string $hour
	 * @param int $duration
	 */
	function Event($id,$title,$content,$start_date,$end_date)
	{
		parent::Resource($id,RESOURCE_EVENT);
		$this->title = $title;
		$this->content = $content;
		$this->start_date = $start_date;
		$this->end_date = $end_date;
	}
	/**
	 * Show this Event
	 */
	function show()
	{
		parent::show();
		echo $this->title.' ('.$this->start_date.' -> '.$this->end_date.')';
	}
}
?>