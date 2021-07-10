<?php

/* For licensing terms, see /license.txt */

/**
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com> - updated ImsAnswerHotspot to match QTI norms
 * @author César Perales <cesar.perales@gmail.com> Updated function names and import files for Aiken format support
 */

/**
 * Aiken2Question transformation class.
 */
class Aiken2Question extends Question
{
    /**
     * Include the correct answer class and create answer.
     */
    public function setAnswer()
    {
        switch ($this->type) {
            case MCUA:
                $answer = new AikenAnswerMultipleChoice($this->iid);

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

/**
 * Class.
 */
class AikenAnswerMultipleChoice extends Answer
{
}
