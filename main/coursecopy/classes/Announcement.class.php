<?php // $Id: Announcement.class.php 11326 2007-03-02 10:34:18Z yannoo $
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
require_once('Resource.class.php');
/**
 * An announcement
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
 */
class Announcement extends Resource
{
	/**
	 * The title of the announcement
	 */
	var $title;
	/**
	 * The content of the announcement
	 */
	var $content;
	/**
	 * The date on which this announcement was made
	 */
	var $date;
	/**
	 * The display order of this announcement
	 */
	var $display_order;
	/**
	 * Has the e-mail been sent?
	 */
	var $email_sent;
	/**
	 * Create a new announcement
	 * @param int $id
	 * @param string $title
	 * @param string $content
	 * @param string $date
	 * @param int display_order
	 */
	function Announcement($id,$title,$content,$date,$display_order,$email_sent)
	{
		parent::Resource($id,RESOURCE_ANNOUNCEMENT);
		$this->content = $content;
		$this->title = $title;
		$this->date = $date;
		$this->display_order = $display_order;
		$this->email_sent = $email_sent;
	}
	/**
	 * Show this announcement
	 */
	function show()
	{
		parent::show();
		echo $this->date.': '.$this->title;	
	}
}
?>