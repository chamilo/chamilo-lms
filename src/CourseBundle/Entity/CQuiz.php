<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CourseBundle\Traits\ShowCourseResourcesInSessionTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    public const ALL_ON_ONE_PAGE = 1;
    public const ONE_PER_PAGE = 2;

    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="text", nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description;

    /**
     * @ORM\Column(name="sound", type="string", length=255, nullable=true)
     */
    protected ?string $sound;

    /**
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    protected int $type;

    /**
     * @ORM\Column(name="random", type="integer", nullable=false)
     */
    protected int $random;

    /**
     * @ORM\Column(name="random_answers", type="boolean", nullable=false)
     */
    protected bool $randomAnswers;

    /**
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    protected bool $active;

    /**
     * @ORM\Column(name="results_disabled", type="integer", nullable=false)
     */
    protected int $resultsDisabled;

    /**
     * @ORM\Column(name="access_condition", type="text", nullable=true)
     */
    protected ?string $accessCondition;

    /**
     * @ORM\Column(name="max_attempt", type="integer", nullable=false)
     */
    protected int $maxAttempt;

    /**
     * @ORM\Column(name="start_time", type="datetime", nullable=true)
     */
    protected ?DateTime $startTime;

    /**
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    protected ?DateTime $endTime;

    /**
     * @ORM\Column(name="feedback_type", type="integer", nullable=false)
     */
    protected int $feedbackType;

    /**
     * @ORM\Column(name="expired_time", type="integer", nullable=false)
     */
    protected int $expiredTime;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected ?int $sessionId;

    /**
     * @ORM\Column(name="propagate_neg", type="integer", nullable=false)
     */
    protected int $propagateNeg;

    /**
     * @ORm\Column(name="save_correct_answers", type="integer", nullable=true)
     */
    protected ?int $saveCorrectAnswers;

    /**
     * @ORM\Column(name="review_answers", type="integer", nullable=false)
     */
    protected int $reviewAnswers;

    /**
     * @ORM\Column(name="random_by_category", type="integer", nullable=false)
     */
    protected int $randomByCategory;

    /**
     * @ORM\Column(name="text_when_finished", type="text", nullable=true)
     */
    protected ?string $textWhenFinished;

    /**
     * @ORM\Column(name="display_category_name", type="integer", nullable=false)
     */
    protected int $displayCategoryName;

    /**
     * @ORM\Column(name="pass_percentage", type="integer", nullable=true)
     */
    protected ?int $passPercentage;

    /**
     * @ORM\Column(name="prevent_backwards", type="integer", nullable=false, options={"default":0})
     */
    protected int $preventBackwards;

    /**
     * @ORM\Column(name="question_selection_type", type="integer", nullable=true)
     */
    protected ?int $questionSelectionType;

    /**
     * @ORM\Column(name="hide_question_title", type="boolean", nullable=false)
     */
    protected bool $hideQuestionTitle;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CExerciseCategory", cascade={"persist"})
     * @ORM\JoinColumn(name="exercise_category_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected ?CExerciseCategory $exerciseCategory = null;

    /**
     * @ORM\Column(name="show_previous_button", type="boolean", nullable=false, options={"default":1})
     */
    protected bool $showPreviousButton;

    /**
     * @ORM\Column(name="notifications", type="string", length=255, nullable=true)
     */
    protected ?string $notifications;

    /**
     * @ORM\Column(name="autolaunch", type="boolean", nullable=true, options={"default":0})
     */
    protected ?bool $autoLaunch;

    /**
     * @ORM\Column(name="page_result_configuration", type="array", nullable=true)
     */
    protected ?array $pageResultConfiguration;

    /**
     * @var Collection|CQuizRelQuestion[]
     *
     * @ORM\OneToMany(targetEntity="CQuizRelQuestion", mappedBy="quiz", cascade={"persist"}, orphanRemoval=true))
     */
    protected $questions;

    /**
     * @var Collection|CQuizRelQuestionCategory[]
     *
     * @ORM\OneToMany(targetEntity="CQuizRelQuestionCategory", mappedBy="quiz", cascade={"persist"}))
     */
    protected $questionsCategories;

    public function __construct()
    {
        $this->hideQuestionTitle = false;
        $this->type = self::ONE_PER_PAGE;
        $this->showPreviousButton = true;
        $this->notifications = '';
        $this->autoLaunch = false;
        $this->preventBackwards = 0;
        $this->random = 0;
        $this->randomAnswers = false;
        $this->active = true;
        $this->resultsDisabled = 0;
        $this->maxAttempt = 1;
        $this->feedbackType = 0;
        $this->expiredTime = 0;
        $this->propagateNeg = 0;
        $this->saveCorrectAnswers = 0;
        $this->reviewAnswers = 0;
        $this->randomByCategory = 0;
        $this->displayCategoryName = 0;
        $this->pageResultConfiguration = null;
        $this->questions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
     * @return Collection|CQuizRelQuestion[]
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription(string $description): self
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
     */
    public function setSound($sound): self
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

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Set random.
     *
     * @param int $random
     */
    public function setRandom($random): self
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
     */
    public function setRandomAnswers($randomAnswers): self
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
     */
    public function setActive($active): self
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
     */
    public function setResultsDisabled($resultsDisabled): self
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
     * @param DateTime $startTime
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
     * @return DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime.
     *
     * @param DateTime $endTime
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
     * @return DateTime
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
     */
    public function setQuestionSelectionType($questionSelectionType): self
    {
        $this->questionSelectionType = $questionSelectionType;

        return $this;
    }

    public function isHideQuestionTitle(): bool
    {
        return $this->hideQuestionTitle;
    }

    /**
     * @param bool $hideQuestionTitle
     */
    public function setHideQuestionTitle($hideQuestionTitle): self
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

    public function setPageResultConfiguration(?array $pageResultConfiguration): self
    {
        $this->pageResultConfiguration = $pageResultConfiguration;

        return $this;
    }

    /**
     * @ORM\PostPersist()
     */
    public function postPersist(LifecycleEventArgs $args): void
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

    public function getAutoLaunch(): ?bool
    {
        return $this->autoLaunch;
    }

    /**
     * @return CQuizRelQuestionCategory[]|Collection
     */
    public function getQuestionsCategories()
    {
        return $this->questionsCategories;
    }

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
