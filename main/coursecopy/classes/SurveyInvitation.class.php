<?php
/* For licensing terms, see /license.txt */
/**
 * Survey invitations backup script
 * @package chamilo.backup
 */
/**
 * Code
 */
require_once 'Resource.class.php';
/**
 * An SurveyInvitation
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @package chamilo.backup
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
