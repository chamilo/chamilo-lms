<?php

declare(strict_types=1);

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="trackEExerciseConfirmations")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(name="course_id", type="integer")
     */
    private int $courseId;

    /**
     * @ORM\Column(name="attempt_id", type="integer")
     */
    private int $attemptId;

    /**
     * @ORM\Column(name="quiz_id", type="integer")
     */
    private int $quizId;

    /**
     * @ORM\Column(name="session_id", type="integer")
     */
    private int $sessionId;

    /**
     * @ORM\Column(name="confirmed", type="boolean", options={"default":false})
     */
    private bool $confirmed;

    /**
     * @ORM\Column(name="questions_count", type="integer")
     */
    private int $questionsCount;

    /**
     * @ORM\Column(name="saved_answers_count", type="integer")
     */
    private int $savedAnswersCount;

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
     * @return TrackEExerciseConfirmation
     */
    public function setCourseId(int $courseId)
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
     * @return TrackEExerciseConfirmation
     */
    public function setAttemptId(int $attemptId)
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
     * @return TrackEExerciseConfirmation
     */
    public function setQuizId(int $quizId)
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
     * @return TrackEExerciseConfirmation
     */
    public function setSessionId(int $sessionId)
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
     * @return TrackEExerciseConfirmation
     */
    public function setConfirmed(bool $confirmed)
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
     * @return TrackEExerciseConfirmation
     */
    public function setQuestionsCount(int $questionsCount)
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
     * @return TrackEExerciseConfirmation
     */
    public function setSavedAnswersCount(int $savedAnswersCount)
    {
        $this->savedAnswersCount = $savedAnswersCount;

        return $this;
    }
}
