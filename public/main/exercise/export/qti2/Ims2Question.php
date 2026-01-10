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
     * Create the proper Answer handler.
     * Messages/strings in code must be English.
     */
    public function setAnswer()
    {
        $questionId = 0;

        if (method_exists($this, 'getIid')) {
            $questionId = (int) $this->getIid();
        } elseif (property_exists($this, 'iid')) {
            $questionId = (int) $this->iid;
        } elseif (property_exists($this, 'id')) {
            $questionId = (int) $this->id;
        }

        // Avoid null IDs: use 0 as safe fallback (some flows set the ID later).
        if ($questionId < 0) {
            $questionId = 0;
        }

        switch ($this->type) {
            case TF:
            case MCUA:
            case MCMA:
                $answer = new ImsAnswerMultipleChoice($questionId);
                break;

            case FIB:
                $answer = new ImsAnswerFillInBlanks($questionId);
                break;

            case MATCHING:
            case MATCHING_DRAGGABLE:
                $answer = new ImsAnswerMatching($questionId);
                break;

            case FREE_ANSWER:
                $answer = new ImsAnswerFree($questionId);
                break;

            case HOT_SPOT:
                $answer = new ImsAnswerHotspot($questionId);
                break;

            default:
                $answer = null;
        }

        // If the parent Question class expects an internal property, keep it.
        if (property_exists($this, 'answer')) {
            $this->answer = $answer;
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
