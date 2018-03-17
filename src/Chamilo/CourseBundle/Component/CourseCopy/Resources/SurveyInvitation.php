<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * An SurveyInvitation.
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 *
 * @package chamilo.backup
 */
class SurveyInvitation extends Resource
{
    /**
     * Survey code.
     */
    public $code;
    /**
     * User info.
     */
    public $user;
    /**
     * Invitation code.
     */
    public $invitation_code;
    /**
     * Invitation date.
     */
    public $invitation_date;
    /**
     * Reminder date.
     */
    public $reminder_date;

    /**
     * Create a new SurveyInvitation.
     *
     * @param int    $id
     * @param string $code
     * @param string $user
     * @param string $invitation_code
     * @param string $invitation_date
     * @param string $reminder_date
     */
    public function __construct($id, $code, $user, $invitation_code, $invitation_date, $reminder_date)
    {
        parent::__construct($id, RESOURCE_SURVEYINVITATION);
        $this->code = $code;
        $this->user = $user;
        $this->invitation_code = $invitation_code;
        $this->invitation_date = $invitation_date;
        $this->reminder_date = $reminder_date;
    }

    /**
     * Show this invitation.
     */
    public function show()
    {
        parent::show();
        echo $this->invitation_code;
    }
}
