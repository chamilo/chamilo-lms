<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\WhispeakAuth;

use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class LogEventQuiz.
 *
 * @package Chamilo\PluginBundle\Entity\WhispeakAuth
 *
 * @ORM\Entity()
 */
class LogEventQuiz extends LogEvent
{
    /**
     * @var CQuizQuestion
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CQuizQuestion")
     * @ORM\JoinColumn(name="question_id", referencedColumnName="iid")
     */
    private $question;
    /**
     * @var CQuiz
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CQuiz")
     * @ORM\JoinColumn(name="quiz_id", referencedColumnName="iid")
     */
    private $quiz;

    /**
     * @return CQuizQuestion
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @param CQuizQuestion $question
     *
     * @return LogEventQuiz
     */
    public function setQuestion($question)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * @return CQuiz
     */
    public function getQuiz()
    {
        return $this->quiz;
    }

    /**
     * @param CQuiz $quiz
     *
     * @return LogEventQuiz
     */
    public function setQuiz($quiz)
    {
        $this->quiz = $quiz;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeString()
    {
        $quiz = strip_tags($this->getQuiz()->getTitle());
        $question = strip_tags($this->getQuestion()->getQuestion());

        return "$quiz > $question";
    }
}
