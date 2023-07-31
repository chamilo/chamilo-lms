<?php

/* For licensing terms, see /license.txt */

/**
 * The ScormQuestion class is a gateway to getting the answers exported
 * (the question is just an HTML text, while the answers are the most important).
 * It is important to note that the SCORM export process is done in two parts.
 * First, the HTML part (which is the presentation), and second the JavaScript
 * part (the process).
 * The two bits are separate to allow for a one-big-javascript and a one-big-html
 * files to be built. Each export function thus returns an array of HTML+JS.
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
class ScormQuestion extends Question
{
    public $js_id;
    public $answer;

    /**
     * ScormQuestion constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns the HTML + JS flow corresponding to one question.
     *
     * @param int $questionId The question ID
     * @param int $jsId       The JavaScript ID for this question.
     *                        Due to the nature of interactions, we must have a natural sequence for
     *                        questions in the generated JavaScript.
     *
     * @return string|array
     */
    public function exportQuestionToScorm(
        $questionId,
        $jsId
    ) {
        $question = self::read($questionId);
        if (!$question) {
            return '';
        }
        $this->iid = $question->iid;
        $this->js_id = $jsId;
        $this->type = $question->type;
        $this->question = $question->question;
        $this->description = $question->description;
        $this->weighting = $question->weighting;
        $this->position = $question->position;
        $this->picture = $question->picture;
        $assessmentItem = new ScormAssessmentItem($this);

        return $assessmentItem->export();
    }

    /**
     * Include the correct answer class and create answer.
     */
    public function setAnswer()
    {
        switch ($this->type) {
            case MCUA:
                $this->answer = new ScormAnswerMultipleChoice($this->iid);
                $this->answer->questionJSId = $this->js_id;
                break;
            case MCMA:
            case GLOBAL_MULTIPLE_ANSWER:
                $this->answer = new ScormAnswerMultipleChoice($this->iid);
                $this->answer->questionJSId = $this->js_id;
                break;
            case TF:
                $this->answer = new ScormAnswerTrueFalse($this->iid);
                $this->answer->questionJSId = $this->js_id;
                break;
            case FIB:
                $this->answer = new ScormAnswerFillInBlanks($this->iid);
                $this->answer->questionJSId = $this->js_id;
                break;
            case MATCHING:
            case MATCHING_DRAGGABLE:
            case DRAGGABLE:
                $this->answer = new ScormAnswerMatching($this->iid);
                $this->answer->questionJSId = $this->js_id;
                break;
            case ORAL_EXPRESSION:
            case FREE_ANSWER:
                $this->answer = new ScormAnswerFree($this->iid);
                $this->answer->questionJSId = $this->js_id;
                break;
            case HOT_SPOT:
            case HOT_SPOT_COMBINATION:
                $this->answer = new ScormAnswerHotspot($this->iid);
                $this->answer->questionJSId = $this->js_id;
                break;
            case MULTIPLE_ANSWER_COMBINATION:
                $this->answer = new ScormAnswerMultipleChoice($this->iid);
                $this->answer->questionJSId = $this->js_id;
                break;
            case HOT_SPOT_ORDER:
                $this->answer = new ScormAnswerHotspot($this->iid);
                $this->answer->questionJSId = $this->js_id;
                break;
            case HOT_SPOT_DELINEATION:
                $this->answer = new ScormAnswerHotspot($this->iid);
                $this->answer->questionJSId = $this->js_id;
                break;
            // not supported
            case UNIQUE_ANSWER_NO_OPTION:
            case MULTIPLE_ANSWER_TRUE_FALSE:
            case MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
            case UNIQUE_ANSWER_IMAGE:
            case CALCULATED_ANSWER:
                $this->answer = new ScormAnswerMultipleChoice($this->iid);
                $this->answer->questionJSId = $this->js_id;
                break;
            default:
                $this->answer = new stdClass();
                $this->answer->questionJSId = $this->js_id;
                break;
        }

        return true;
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function export()
    {
        $html = $this->getQuestionHTML();
        $js = $this->getQuestionJS();

        if (is_object($this->answer) && $this->answer instanceof Answer) {
            list($js2, $html2) = $this->answer->export();
            $js .= $js2;
            $html .= $html2;
        } else {
            throw new \Exception('Question not supported. Exercise: '.$this->selectTitle());
        }

        return [$js, $html];
    }

    /**
     * {@inheritdoc}
     */
    public function createAnswersForm($form)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function processAnswersCreation($form, $exercise)
    {
        return true;
    }

    /**
     * Returns an HTML-formatted question.
     */
    public function getQuestionHTML()
    {
        $title = $this->selectTitle();
        $description = $this->selectDescription();
        $cols = 2;

        return '<tr>
            <td colspan="'.$cols.'" id="question_'.$this->iid.'_title" valign="middle" style="background-color:#d6d6d6;">
            '.$title.'
            </td>
            </tr>
            <tr>
            <td valign="top" colspan="'.$cols.'">
            <i>'.$description.'</i>
            </td>
            </tr>';
    }

    /**
     * Return the JavaScript code bound to the question.
     */
    public function getQuestionJS()
    {
        $weight = $this->selectWeighting();
        $js = '
            questions.push('.$this->js_id.');
            $(function() {
                if (exerciseInfo.randomAnswers == true) {
                    $("#question_'.$this->js_id.'").shuffleRows();
                }
            });';
        $js .= "\n";

        switch ($this->type) {
            case ORAL_EXPRESSION:
                /*$script = file_get_contents(api_get_path(LIBRARY_PATH) . 'javascript/rtc/RecordRTC.js');
                $script .= file_get_contents(api_get_path(LIBRARY_PATH) . 'wami-recorder/recorder.js');
                $script .= file_get_contents(api_get_path(LIBRARY_PATH) . 'wami-recorder/gui.js');
                $js .= $script;*/
                break;
            case HOT_SPOT:
            case HOT_SPOT_COMBINATION:
                //put the max score to 0 to avoid discounting the points of
                //non-exported quiz types in the SCORM
                $weight = 0;
                break;
        }
        $js .= 'questions_score_max['.$this->js_id.'] = '.$weight.';';

        return $js;
    }
}
