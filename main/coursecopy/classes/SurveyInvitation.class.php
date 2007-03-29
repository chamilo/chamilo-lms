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
require_once('Resource.class.php');
/**
 * An SurveyInvitation
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 * @package dokeos.backup
 */
class SurveyInvitation extends Resource
{
	/**
	 * Survey code
	 */
	var $code;
	/**
	 * User info
	 */
	var $user;
	/**
	 * Invitation code
	 */
	var $invitation_code;
	/**
	 * Invitation date
	 */
	var $invitation_date;
	/**
	 * Reminder date
	 */
	var $reminder_date;
	/**
	 * Create a new SurveyInvitation
	 * @param	int	 $id
	 * @param string $code
	 * @param string $user
	 * @param string $invitation_code
	 * @param string $invitation_date
	 * @param string $reminder_date
	 */
	function SurveyInvitation($id,$code,$user,$invitation_code,$invitation_date,$reminder_date)
	{
		parent::Resource($id,RESOURCE_SURVEYINVITATION);
		$this->code = $code;
		$this->user = $user;
		$this->invitation_code = $invitation_code;
		$this->invitation_date = $invitation_date;
		$this->reminder_date = $reminder_date;
	}
	/**
	 * Show this invitation
	 */
	function show()
	{
		parent::show();
		echo $this->invitation_code;	
	}
}
?>