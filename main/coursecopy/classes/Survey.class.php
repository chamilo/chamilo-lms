<?php // $Id: $
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004-2007 Dokeos S.A.
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
 * A survey
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 * @package dokeos.backup
 */
class Survey extends Resource
{
	/**
	 * The survey code
	 */
	var $code;
	/**
	 * The title and subtitle
	 */
	var $title;
	var $subtitle;
	/**
	 * The author's name
	 */
	var $author;
	/**
	 * The survey's language
	 */
	var $lang;
	/**
	 * The availability period
	 */
	var $avail_from;
	var $avail_till;
	/**
	 * Flag for shared status
	 */
	var $is_shared;
	/**
	 * Template used
	 */
	var $template;
	/**
	 * Introduction text
	 */
	var $intro;
	/**
	 * Thanks text
	 */
	var $surveythanks;
	/**
	 * Creation date
	 */
	var $creation_date;
	/**
	 * Invitation status
	 */
	var $invited;
	/**
	 * Answer status
	 */
	var $answered;
	/**
	 * Invitation and reminder mail contents
	 */
	var $invite_mail;
	var $reminder_mail;
	/**
	 * Questions and invitations lists
	 */
	var $question_ids;
	var $invitation_ids;
	/**
	 * Create a new Survey
	 * @param string $code
	 * @param string $title
	 * @param string $subtitle
	 * @param string $author
	 * @param string $lang
	 * @param string $avail_from
	 * @param string $avail_till
	 * @param char $is_shared
	 * @param string $template
	 * @param string $intro
	 * @param string $surveythanks
	 * @param string $creation_date
	 * @param int $invited
	 * @param int $answered
	 * @param string $invite_mail
	 * @param string $reminder_mail
	 */
	function Survey($id,$code,$title,$subtitle,
					$author,$lang,$avail_from,$avail_till,
					$is_shared, $template,$intro,$surveythanks,
					$creation_date,$invited,$answered,$invite_mail,$reminder_mail)
	{
		parent::Resource($id,RESOURCE_SURVEY);
		$this->code = $code;
		$this->title = $title;
		$this->subtitle = $subtitle;
		$this->author = $author;
		$this->lang = $lang;
		$this->avail_from = $avail_from;
		$this->avail_till = $avail_till;
		$this->is_shared = $is_shared;
		$this->template = $template;
		$this->intro = $intro;
		$this->surveythanks = $surveythanks;
		$this->creation_date = $creation_date;
		$this->invited = $invited;
		$this->answered = $answered;
		$this->invite_mail = $invite_mail;
		$this->reminder_mail = $reminder_mail;
		$this->question_ids = array();
		$this->invitation_ids = array();
	}
	/**
	 * Add a question to this survey
	 */
	function add_question($id)
	{
		$this->question_ids[] = $id;
	}
	/**
	 * Add an invitation to this survey
	 */
	function add_invitation($id)
	{
		$this->invitation_ids[] = $id;
	}
	/**
	 * Show this survey
	 */
	function show()
	{
		parent::show();
		echo $this->code.' - '.$this->title;
	}
}
?>