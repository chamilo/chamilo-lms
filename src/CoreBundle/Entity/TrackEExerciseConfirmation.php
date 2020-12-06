<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Table(name="track_e_exercise_confirmation")
 * @ORM\Entity()
 */
class TrackEExerciseConfirmation
{
    use TimestampableEntity;
    use UserTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="trackEExerciseConfirmations")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="course_id", type="integer")
     */
    private $courseId;

    /**
     * @var int
     *
     * @ORM\Column(name="attempt_id", type="integer")
     */
    private $attemptId;

    /**
     * @var int
     *
     * @ORM\Column(name="quiz_id", type="integer")
     */
    private $quizId;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer")
     */
    private $sessionId;

    /**
     * @var bool
     *
     * @ORM\Column(name="confirmed", type="boolean", options={"default": false})
     */
    private $confirmed;

    /**
     * @var int
     *
     * @ORM\Column(name="questions_count", type="integer")
     */
    private $questionsCount;

    /**
     * @var int
     *
     * @ORM\Column(name="saved_answers_count", type="integer")
     */
    private $savedAnswersCount;

    /**
     * TrackEExerciseConfirmation constructor.
     */
    public function __construct()
    {
        $this->confirmed = false;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCourseId()
    {
        return $this->courseId;
    }

    /**
     * @param int $courseId
     *
     * @return TrackEExerciseConfirmation
     */
    public function setCourseId($courseId)
    {
        $this->courseId = $courseId;

        return $this;
    }

    /**
     * @return int
     */
    public function getAttemptId()
    {
        return $this->attemptId;
    }

    /**
     * @param int $attemptId
     *
     * @return TrackEExerciseConfirmation
     */
    public function setAttemptId($attemptId)
    {
        $this->attemptId = $attemptId;

        return $this;
    }

    /**
     * @return int
     */
    public function getQuizId()
    {
        return $this->quizId;
    }

    /**
     * @param int $quizId
     *
     * @return TrackEExerciseConfirmation
     */
    public function setQuizId($quizId)
    {
        $this->quizId = $quizId;

        return $this;
    }

    /**
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param int $sessionId
     *
     * @return TrackEExerciseConfirmation
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @return bool
     */
    public function isConfirmed()
    {
        return $this->confirmed;
    }

    /**
     * @param bool $confirmed
     *
     * @return TrackEExerciseConfirmation
     */
    public function setConfirmed($confirmed)
    {
        $this->confirmed = $confirmed;

        return $this;
    }

    /**
     * @return int
     */
    public function getQuestionsCount()
    {
        return $this->questionsCount;
    }

    /**
     * @param int $questionsCount
     *
     * @return TrackEExerciseConfirmation
     */
    public function setQuestionsCount($questionsCount)
    {
        $this->questionsCount = $questionsCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getSavedAnswersCount()
    {
        return $this->savedAnswersCount;
    }

    /**
     * @param int $savedAnswersCount
     *
     * @return TrackEExerciseConfirmation
     */
    public function setSavedAnswersCount($savedAnswersCount)
    {
        $this->savedAnswersCount = $savedAnswersCount;

        return $this;
    }
}
