<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * A SurveyQuestion.
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 *
 * @package chamilo.backup
 */
class SurveyQuestion extends Resource
{
    /**
     * Survey ID.
     */
    public $survey_id;
    /**
     * Question and question comment.
     */
    public $survey_question;
    public $survey_question_comment;
    /**
     * Question type.
     */
    public $survey_question_type;
    /**
     * Display ?
     */
    public $display;
    /**
     * Sorting order.
     */
    public $sort;
    /**
     * Shared question ID.
     */
    public $shared_question_id;
    /**
     * Maximum value for the vote.
     */
    public $max_value;

    /**
     * Question's options.
     */
    public $options;

    /**
     * Is this question required (0: no, 1: yes).
     */
    public $is_required;

    /**
     * Create a new SurveyQuestion.
     *
     * @param int    $id
     * @param int    $survey_id
     * @param string $survey_question
     * @param string $survey_question_comment
     * @param string $type
     * @param string $display
     * @param int    $sort
     * @param int    $shared_question_id
     * @param int    $max_value
     * @param bool   $is_required
     */
    public function __construct(
        $id,
        $survey_id,
        $survey_question,
        $survey_question_comment,
        $type,
        $display,
        $sort,
        $shared_question_id,
        $max_value,
        $is_required = false
    ) {
        parent::__construct($id, RESOURCE_SURVEYQUESTION);
        $this->survey_id = $survey_id;
        $this->survey_question = $survey_question;
        $this->survey_question_comment = $survey_question_comment;
        $this->survey_question_type = $type;
        $this->display = $display;
        $this->sort = $sort;
        $this->shared_question_id = $shared_question_id;
        $this->max_value = $max_value;
        $this->answers = [];
        if (api_get_configuration_value('allow_required_survey_questions')) {
            $this->is_required = $is_required;
        }
    }

    /**
     * Add an answer option to this SurveyQuestion.
     *
     * @param string $option_text
     * @param int    $sort
     */
    public function add_answer($option_text, $sort)
    {
        $answer = [];
        $answer['option_text'] = $option_text;
        $answer['sort'] = $sort;
        $this->answers[] = $answer;
    }

    /**
     * Show this question.
     */
    public function show()
    {
        parent::show();
        echo $this->survey_question;
    }
}
