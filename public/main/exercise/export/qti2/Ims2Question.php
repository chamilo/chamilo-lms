<?php

/* For licensing terms, see /license.txt */

/**
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com> -
 * updated ImsAnswerHotspot to match QTI norms
 */
class Ims2Question extends Question
{
    /**
     * Include the correct answer class and create answer.
     *
     * @return Answer
     */
    public function setAnswer()
    {
        switch ($this->type) {
            case MCUA:
                $answer = new ImsAnswerMultipleChoice($this->id);

                return $answer;
            case MCMA:
                $answer = new ImsAnswerMultipleChoice($this->id);

                return $answer;
            case TF:
                $answer = new ImsAnswerMultipleChoice($this->id);

                return $answer;
            case FIB:
                $answer = new ImsAnswerFillInBlanks($this->id);

                return $answer;
            case MATCHING:
            case MATCHING_DRAGGABLE:
                $answer = new ImsAnswerMatching($this->id);

                return $answer;
            case FREE_ANSWER:
                $answer = new ImsAnswerFree($this->id);

                return $answer;
            case HOT_SPOT:
                $answer = new ImsAnswerHotspot($this->id);

                return $answer;
            default:
                $answer = null;

                break;
        }

        return $answer;
    }

    public function createAnswersForm($form)
    {
        return true;
    }

    public function processAnswersCreation($form, $exercise)
    {
        return true;
    }
}
