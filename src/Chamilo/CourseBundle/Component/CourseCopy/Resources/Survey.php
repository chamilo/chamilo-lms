<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Survey.
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 *
 * @package chamilo.backup
 */
class Survey extends Resource
{
    /**
     * The survey code.
     */
    public $code;
    /**
     * The title and subtitle.
     */
    public $title;
    public $subtitle;
    /**
     * The author's name.
     */
    public $author;
    /**
     * The survey's language.
     */
    public $lang;
    /**
     * The availability period.
     */
    public $avail_from;
    public $avail_till;
    /**
     * Flag for shared status.
     */
    public $is_shared;
    /**
     * Template used.
     */
    public $template;
    /**
     * Introduction text.
     */
    public $intro;
    /**
     * Thanks text.
     */
    public $surveythanks;
    /**
     * Creation date.
     */
    public $creation_date;
    /**
     * Invitation status.
     */
    public $invited;
    /**
     * Answer status.
     */
    public $answered;
    /**
     * Invitation and reminder mail contents.
     */
    public $invite_mail;
    public $reminder_mail;
    /**
     * Questions and invitations lists.
     */
    public $question_ids;
    public $invitation_ids;

    /**
     * Create a new Survey.
     *
     * @param string $code
     * @param string $title
     * @param string $subtitle
     * @param string $author
     * @param string $lang
     * @param string $avail_from
     * @param string $avail_till
     * @param string $is_shared
     * @param string $template
     * @param string $intro
     * @param string $surveythanks
     * @param string $creation_date
     * @param int    $invited
     * @param int    $answered
     * @param string $invite_mail
     * @param string $reminder_mail
     * @param int    $oneQuestionPerPage
     * @param int    $shuffle
     */
    public function __construct(
        $id,
        $code,
        $title,
        $subtitle,
        $author,
        $lang,
        $avail_from,
        $avail_till,
        $is_shared,
        $template,
        $intro,
        $surveythanks,
        $creation_date,
        $invited,
        $answered,
        $invite_mail,
        $reminder_mail,
        $oneQuestionPerPage,
        $shuffle
    ) {
        parent::__construct($id, RESOURCE_SURVEY);
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
        $this->question_ids = [];
        $this->invitation_ids = [];
        $this->one_question_per_page = $oneQuestionPerPage;
        $this->shuffle = $shuffle;
    }

    /**
     * Add a question to this survey.
     */
    public function add_question($id)
    {
        $this->question_ids[] = $id;
    }

    /**
     * Add an invitation to this survey.
     */
    public function add_invitation($id)
    {
        $this->invitation_ids[] = $id;
    }

    /**
     * Show this survey.
     */
    public function show()
    {
        parent::show();
        echo $this->code.' - '.$this->title;
    }
}
