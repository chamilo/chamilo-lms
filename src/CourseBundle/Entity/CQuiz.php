<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
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
 *     name="c_quiz",
 *     indexes={
 *     }
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
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="text", nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="sound", type="string", length=255, nullable=true)
     */
    protected ?string $sound = null;

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
    protected ?string $accessCondition = null;

    /**
     * @ORM\Column(name="max_attempt", type="integer", nullable=false)
     */
    protected int $maxAttempt;

    /**
     * @ORM\Column(name="start_time", type="datetime", nullable=true)
     */
    protected ?DateTime $startTime = null;

    /**
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    protected ?DateTime $endTime = null;

    /**
     * @ORM\Column(name="feedback_type", type="integer", nullable=false)
     */
    protected int $feedbackType;

    /**
     * @ORM\Column(name="expired_time", type="integer", nullable=false)
     */
    protected int $expiredTime;

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
    protected ?string $textWhenFinished = null;

    /**
     * @ORM\Column(name="display_category_name", type="integer", nullable=false)
     */
    protected int $displayCategoryName;

    /**
     * @ORM\Column(name="pass_percentage", type="integer", nullable=true)
     */
    protected ?int $passPercentage = null;

    /**
     * @ORM\Column(name="prevent_backwards", type="integer", nullable=false, options={"default":0})
     */
    protected int $preventBackwards;

    /**
     * @ORM\Column(name="question_selection_type", type="integer", nullable=true)
     */
    protected ?int $questionSelectionType = null;

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
    protected Collection $questions;

    /**
     * @var Collection|CQuizRelQuestionCategory[]
     *
     * @ORM\OneToMany(targetEntity="CQuizRelQuestionCategory", mappedBy="quiz", cascade={"persist"}))
     */
    protected Collection $questionsCategories;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->questionsCategories = new ArrayCollection();
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

    public function setSound(string $sound): self
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

    public function setRandom(int $random): self
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

    public function setRandomAnswers(bool $randomAnswers): self
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

    public function setActive(bool $active): self
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

    public function setResultsDisabled(int $resultsDisabled): self
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
     * @return CQuiz
     */
    public function setAccessCondition(string $accessCondition)
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

    public function setMaxAttempt(int $maxAttempt): self
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

    public function setStartTime(?DateTime $startTime): self
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
     * @return CQuiz
     */
    public function setEndTime(?DateTime $endTime)
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
     * @return CQuiz
     */
    public function setFeedbackType(int $feedbackType)
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
     * @return CQuiz
     */
    public function setExpiredTime(int $expiredTime)
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
     * Set propagateNeg.
     *
     * @return CQuiz
     */
    public function setPropagateNeg(int $propagateNeg)
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
     * @return CQuiz
     */
    public function setSaveCorrectAnswers(int $saveCorrectAnswers)
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
     * @return CQuiz
     */
    public function setReviewAnswers(int $reviewAnswers)
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
     * @return CQuiz
     */
    public function setRandomByCategory(int $randomByCategory)
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
     * @return CQuiz
     */
    public function setTextWhenFinished(string $textWhenFinished)
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
     * @return CQuiz
     */
    public function setDisplayCategoryName(int $displayCategoryName)
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
     * @return CQuiz
     */
    public function setPassPercentage(int $passPercentage)
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

    public function setQuestionSelectionType(int $questionSelectionType): self
    {
        $this->questionSelectionType = $questionSelectionType;

        return $this;
    }

    public function isHideQuestionTitle(): bool
    {
        return $this->hideQuestionTitle;
    }

    public function setHideQuestionTitle(bool $hideQuestionTitle): self
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
