<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class TrackEExerciseConfirmation.
 *
 * Add @ to the next line if api_get_configuration_value('quiz_confirm_saved_answers') is true
 * ORM\Table(name="track_e_exercise_confirmation")
 * ORM\Entity()
 */
class TrackEExerciseConfirmation
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;
    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;
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
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

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
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     *
     * @return TrackEExerciseConfirmation
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
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

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return TrackEExerciseConfirmation
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return TrackEExerciseConfirmation
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
