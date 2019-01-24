<?php
/* For licensing terms, see /license.txt */

/**
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com> - updated ImsAnswerHotspot to match QTI norms
 * @author César Perales <cesar.perales@gmail.com> Updated function names and import files for Aiken format support
 *
 * @package chamilo.exercise
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
                $answer = new AikenAnswerMultipleChoice($this->id);

                return $answer;
            default:
                $answer = null;
                break;
        }

        return $answer;
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function addCode($code)
    {
        if (api_get_configuration_value('allow_question_code') && !empty($this->id)) {
            $code = Database::escape_string($code);
            $table = Database::get_course_table(TABLE_QUIZ_QUESTION);
            $sql = "UPDATE $table SET code = '$code' 
                    WHERE iid = {$this->id} AND c_id = {$this->course['real_id']}";
            Database::query($sql);

            return true;
        }

        return false;
    }

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
}

/**
 * Class.
 *
 * @package chamilo.exercise
 */
class AikenAnswerMultipleChoice extends Answer
{
}
