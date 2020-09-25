<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CourseBundle\Traits\ShowCourseResourcesInSessionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CQuiz.
 *
 * @ORM\Table(
 *  name="c_quiz",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CQuiz extends AbstractResource implements ResourceInterface
{
    use ShowCourseResourcesInSessionTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="text", nullable=false)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="sound", type="string", length=255, nullable=true)
     */
    protected $sound;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="random", type="integer", nullable=false)
     */
    protected $random;

    /**
     * @var bool
     *
     * @ORM\Column(name="random_answers", type="boolean", nullable=false)
     */
    protected $randomAnswers;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    protected $active;

    /**
     * @var int
     *
     * @ORM\Column(name="results_disabled", type="integer", nullable=false)
     */
    protected $resultsDisabled;

    /**
     * @var string
     *
     * @ORM\Column(name="access_condition", type="text", nullable=true)
     */
    protected $accessCondition;

    /**
     * @var int
     *
     * @ORM\Column(name="max_attempt", type="integer", nullable=false)
     */
    protected $maxAttempt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime", nullable=true)
     */
    protected $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    protected $endTime;

    /**
     * @var int
     *
     * @ORM\Column(name="feedback_type", type="integer", nullable=false)
     */
    protected $feedbackType;

    /**
     * @var int
     *
     * @ORM\Column(name="expired_time", type="integer", nullable=false)
     */
    protected $expiredTime;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected $sessionId;

    /**
     * @var int
     *
     * @ORM\Column(name="propagate_neg", type="integer", nullable=false)
     */
    protected $propagateNeg;

    /**
     * @var int
     *
     * @ORm\Column(name="save_correct_answers", type="integer", nullable=true)
     */
    protected $saveCorrectAnswers;

    /**
     * @var int
     *
     * @ORM\Column(name="review_answers", type="integer", nullable=false)
     */
    protected $reviewAnswers;

    /**
     * @var int
     *
     * @ORM\Column(name="random_by_category", type="integer", nullable=false)
     */
    protected $randomByCategory;

    /**
     * @var string
     *
     * @ORM\Column(name="text_when_finished", type="text", nullable=true)
     */
    protected $textWhenFinished;

    /**
     * @var int
     *
     * @ORM\Column(name="display_category_name", type="integer", nullable=false)
     */
    protected $displayCategoryName;

    /**
     * @var int
     *
     * @ORM\Column(name="pass_percentage", type="integer", nullable=true)
     */
    protected $passPercentage;

    /**
     * @var int
     *
     * @ORM\Column(name="prevent_backwards", type="integer", nullable=false, options={"default":0})
     */
    protected $preventBackwards;

    /**
     * @var int
     *
     * @ORM\Column(name="question_selection_type", type="integer", nullable=true)
     */
    protected $questionSelectionType;

    /**
     * @var bool
     *
     * @ORM\Column(name="hide_question_title", type="boolean", nullable=true)
     */
    protected $hideQuestionTitle;

    /**
     * @var CExerciseCategory
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CExerciseCategory", cascade={"persist"})
     * @ORM\JoinColumn(name="exercise_category_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $exerciseCategory;

    /**
     * @var bool
     *
     * @ORM\Column(name="show_previous_button", type="boolean", nullable=true, options={"default":1})
     */
    protected $showPreviousButton;

    /**
     * @var string
     *
     * @ORM\Column(name="notifications", type="string", length=255, nullable=true)
     */
    protected $notifications;

    /**
     * @var bool
     *
     * @ORM\Column(name="autolaunch", type="boolean", nullable=true, options={"default":0})
     */
    protected $autoLaunch;

    /**
     * @var array
     *
     * @ORM\Column(name="page_result_configuration", type="array", nullable=true)
     */
    protected $pageResultConfiguration;

    /**
     * @var CQuizRelQuestion[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CQuizRelQuestion", mappedBy="quiz", cascade={"persist"}, orphanRemoval=true))
     */
    protected $questions;

    /**
     * CQuiz constructor.
     */
    public function __construct()
    {
        $this->hideQuestionTitle = false;
        $this->type = ONE_PER_PAGE;
        $this->showPreviousButton = true;
        $this->notifications = '';
        $this->autoLaunch = 0;
        $this->preventBackwards = 0;
        $this->random = 0;
        $this->randomAnswers = false;
        $this->active = true;
        $this->resultsDisabled = 0;
        $this->maxAttempt = 1;
        $this->feedbackType = 0;
        $this->expiredTime = 0;
        $this->propagateNeg = 0;
        $this->saveCorrectAnswers = false;
        $this->reviewAnswers = 0;
        $this->randomByCategory = 0;
        $this->displayCategoryName = 0;
        $this->questions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
     * @return CQuizRelQuestion[]|ArrayCollection
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return CQuiz
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return (string) $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CQuiz
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set sound.
     *
     * @param string $sound
     *
     * @return CQuiz
     */
    public function setSound($sound)
    {
        $this->sound = $sound;

        return $this;
    }

    /**
     * Get sound.
     *
     * @return string
     */
    public function getSound()
    {
        return $this->sound;
    }

    /**
     * Set type.
     */
    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Set random.
     *
     * @param int $random
     *
     * @return CQuiz
     */
    public function setRandom($random)
    {
        $this->random = $random;

        return $this;
    }

    /**
     * Get random.
     *
     * @return int
     */
    public function getRandom()
    {
        return $this->random;
    }

    /**
     * Set randomAnswers.
     *
     * @param bool $randomAnswers
     *
     * @return CQuiz
     */
    public function setRandomAnswers($randomAnswers)
    {
        $this->randomAnswers = $randomAnswers;

        return $this;
    }

    /**
     * Get randomAnswers.
     *
     * @return bool
     */
    public function getRandomAnswers()
    {
        return $this->randomAnswers;
    }

    /**
     * Set active.
     *
     * @param bool $active
     *
     * @return CQuiz
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active.
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set resultsDisabled.
     *
     * @param int $resultsDisabled
     *
     * @return CQuiz
     */
    public function setResultsDisabled($resultsDisabled)
    {
        $this->resultsDisabled = $resultsDisabled;

        return $this;
    }

    /**
     * Get resultsDisabled.
     *
     * @return int
     */
    public function getResultsDisabled()
    {
        return $this->resultsDisabled;
    }

    /**
     * Set accessCondition.
     *
     * @param string $accessCondition
     *
     * @return CQuiz
     */
    public function setAccessCondition($accessCondition)
    {
        $this->accessCondition = $accessCondition;

        return $this;
    }

    /**
     * Get accessCondition.
     *
     * @return string
     */
    public function getAccessCondition()
    {
        return $this->accessCondition;
    }

    /**
     * Set maxAttempt.
     *
     * @param int $maxAttempt
     *
     * @return CQuiz
     */
    public function setMaxAttempt($maxAttempt)
    {
        $this->maxAttempt = $maxAttempt;

        return $this;
    }

    /**
     * Get maxAttempt.
     *
     * @return int
     */
    public function getMaxAttempt()
    {
        return $this->maxAttempt;
    }

    /**
     * Set startTime.
     *
     * @param \DateTime $startTime
     *
     * @return CQuiz
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime.
     *
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime.
     *
     * @param \DateTime $endTime
     *
     * @return CQuiz
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime.
     *
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set feedbackType.
     *
     * @param int $feedbackType
     *
     * @return CQuiz
     */
    public function setFeedbackType($feedbackType)
    {
        $this->feedbackType = $feedbackType;

        return $this;
    }

    /**
     * Get feedbackType.
     *
     * @return int
     */
    public function getFeedbackType()
    {
        return $this->feedbackType;
    }

    /**
     * Set expiredTime.
     *
     * @param int $expiredTime
     *
     * @return CQuiz
     */
    public function setExpiredTime($expiredTime)
    {
        $this->expiredTime = $expiredTime;

        return $this;
    }

    /**
     * Get expiredTime.
     *
     * @return int
     */
    public function getExpiredTime()
    {
        return $this->expiredTime;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CQuiz
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set propagateNeg.
     *
     * @param int $propagateNeg
     *
     * @return CQuiz
     */
    public function setPropagateNeg($propagateNeg)
    {
        $this->propagateNeg = $propagateNeg;

        return $this;
    }

    /**
     * Get propagateNeg.
     *
     * @return int
     */
    public function getPropagateNeg()
    {
        return $this->propagateNeg;
    }

    /**
     * @param int $saveCorrectAnswers
     *
     * @return CQuiz
     */
    public function setSaveCorrectAnswers($saveCorrectAnswers)
    {
        $this->saveCorrectAnswers = $saveCorrectAnswers;

        return $this;
    }

    /**
     * @return int
     */
    public function getSaveCorrectAnswers()
    {
        return $this->saveCorrectAnswers;
    }

    /**
     * Set reviewAnswers.
     *
     * @param int $reviewAnswers
     *
     * @return CQuiz
     */
    public function setReviewAnswers($reviewAnswers)
    {
        $this->reviewAnswers = $reviewAnswers;

        return $this;
    }

    /**
     * Get reviewAnswers.
     *
     * @return int
     */
    public function getReviewAnswers()
    {
        return $this->reviewAnswers;
    }

    /**
     * Set randomByCategory.
     *
     * @param int $randomByCategory
     *
     * @return CQuiz
     */
    public function setRandomByCategory($randomByCategory)
    {
        $this->randomByCategory = $randomByCategory;

        return $this;
    }

    /**
     * Get randomByCategory.
     *
     * @return int
     */
    public function getRandomByCategory()
    {
        return $this->randomByCategory;
    }

    /**
     * Set textWhenFinished.
     *
     * @param string $textWhenFinished
     *
     * @return CQuiz
     */
    public function setTextWhenFinished($textWhenFinished)
    {
        $this->textWhenFinished = $textWhenFinished;

        return $this;
    }

    /**
     * Get textWhenFinished.
     *
     * @return string
     */
    public function getTextWhenFinished()
    {
        return $this->textWhenFinished;
    }

    /**
     * Set displayCategoryName.
     *
     * @param int $displayCategoryName
     *
     * @return CQuiz
     */
    public function setDisplayCategoryName($displayCategoryName)
    {
        $this->displayCategoryName = $displayCategoryName;

        return $this;
    }

    /**
     * Get displayCategoryName.
     *
     * @return int
     */
    public function getDisplayCategoryName()
    {
        return $this->displayCategoryName;
    }

    /**
     * Set passPercentage.
     *
     * @param int $passPercentage
     *
     * @return CQuiz
     */
    public function setPassPercentage($passPercentage)
    {
        $this->passPercentage = $passPercentage;

        return $this;
    }

    /**
     * Get passPercentage.
     *
     * @return int
     */
    public function getPassPercentage()
    {
        return $this->passPercentage;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CQuiz
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * @return CExerciseCategory
     */
    public function getExerciseCategory(): ?CExerciseCategory
    {
        return $this->exerciseCategory;
    }

    public function setExerciseCategory(CExerciseCategory $exerciseCategory): self
    {
        $this->exerciseCategory = $exerciseCategory;

        return $this;
    }

    /**
     * @return int
     */
    public function getQuestionSelectionType()
    {
        return $this->questionSelectionType;
    }

    /**
     * @param int $questionSelectionType
     *
     * @return CQuiz
     */
    public function setQuestionSelectionType($questionSelectionType)
    {
        $this->questionSelectionType = $questionSelectionType;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHideQuestionTitle()
    {
        return $this->hideQuestionTitle;
    }

    /**
     * @param bool $hideQuestionTitle
     *
     * @return CQuiz
     */
    public function setHideQuestionTitle($hideQuestionTitle)
    {
        $this->hideQuestionTitle = $hideQuestionTitle;

        return $this;
    }

    public function isShowPreviousButton(): bool
    {
        return $this->showPreviousButton;
    }

    public function setShowPreviousButton(bool $showPreviousButton): self
    {
        $this->showPreviousButton = $showPreviousButton;

        return $this;
    }

    public function getNotifications(): string
    {
        return $this->notifications;
    }

    public function setNotifications(string $notifications): self
    {
        $this->notifications = $notifications;

        return $this;
    }

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    public function getPreventBackwards(): int
    {
        return $this->preventBackwards;
    }

    public function setPreventBackwards(int $preventBackwards): self
    {
        $this->preventBackwards = $preventBackwards;

        return $this;
    }

    public function isAutoLaunch(): bool
    {
        return $this->autoLaunch;
    }

    public function setAutoLaunch(bool $autoLaunch): self
    {
        $this->autoLaunch = $autoLaunch;

        return $this;
    }

    public function getPageResultConfiguration(): array
    {
        return $this->pageResultConfiguration;
    }

    public function setPageResultConfiguration($pageResultConfiguration): self
    {
        $this->pageResultConfiguration = $pageResultConfiguration;

        return $this;
    }

    /**
     * @ORM\PostPersist()
     */
    public function postPersist(LifecycleEventArgs $args)
    {
    }

    /**
     * Returns the sum of question's ponderation.
     */
    public function getMaxScore(): int
    {
        $maxScore = 0;
        foreach ($this->questions as $relQuestion) {
            $maxScore += $relQuestion->getQuestion()->getPonderation();
        }

        return $maxScore;
    }

    /**
     * Resource identifier.
     */
    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
