<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Course quizzes.
 *
 * @ORM\Table(
 *     name="c_quiz",
 *     indexes={
 *     }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CourseBundle\Repository\CQuizRepository")
 */
class CQuiz extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface
{
    public const ALL_ON_ONE_PAGE = 1;
    public const ONE_PER_PAGE = 2;

    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[Groups(['track_e_exercise:read'])]
    protected int $iid;

    /**
     * @ORM\Column(name="title", type="text", nullable=false)
     */
    #[Assert\NotBlank]
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
     * @ORM\Column(name="active", type="integer", nullable=false)
     */
    protected int $active;

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
     * @ORM\Column(name="hide_question_number", type="integer", nullable=false, options={"default":0})
     */
    protected int $hideQuestionNumber;

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
     * @ORM\Column(name="page_result_configuration", type="array")
     */
    protected array $pageResultConfiguration = [];

    /**
     * @var Collection|CQuizRelQuestion[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CQuizRelQuestion", mappedBy="quiz", cascade={"persist"}, orphanRemoval=true))
     */
    protected Collection $questions;

    /**
     * @var Collection|CQuizRelQuestionCategory[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CQuizRelQuestionCategory", mappedBy="quiz", cascade={"persist"}))
     */
    protected Collection $questionsCategories;

    /**
     * @var Collection<int, TrackEExercise>
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\TrackEExercise", mappedBy="quiz")
     */
    protected Collection $attempts;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->questionsCategories = new ArrayCollection();
        $this->hideQuestionTitle = false;
        $this->hideQuestionNumber = 0;
        $this->type = self::ONE_PER_PAGE;
        $this->showPreviousButton = true;
        $this->notifications = '';
        $this->autoLaunch = false;
        $this->preventBackwards = 0;
        $this->random = 0;
        $this->randomAnswers = false;
        $this->active = 1;
        $this->resultsDisabled = 0;
        $this->maxAttempt = 1;
        $this->feedbackType = 0;
        $this->expiredTime = 0;
        $this->propagateNeg = 0;
        $this->saveCorrectAnswers = 0;
        $this->reviewAnswers = 0;
        $this->randomByCategory = 0;
        $this->displayCategoryName = 0;
        $this->pageResultConfiguration = [];
        $this->attempts = new ArrayCollection();
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

    public function setActive(int $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getActive(): int
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

    public function setAccessCondition(string $accessCondition): self
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

    public function setEndTime(?DateTime $endTime): self
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

    public function setFeedbackType(int $feedbackType): self
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

    public function setExpiredTime(int $expiredTime): self
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

    public function setPropagateNeg(int $propagateNeg): self
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

    public function setSaveCorrectAnswers(int $saveCorrectAnswers): self
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

    public function setReviewAnswers(int $reviewAnswers): self
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

    public function setRandomByCategory(int $randomByCategory): self
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

    public function setTextWhenFinished(string $textWhenFinished): self
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

    public function setDisplayCategoryName(int $displayCategoryName): self
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

    public function setPassPercentage(int $passPercentage): self
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
    public function getIid(): int
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

    public function setPageResultConfiguration(array $pageResultConfiguration): self
    {
        $this->pageResultConfiguration = $pageResultConfiguration;

        return $this;
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

    public function getHideQuestionNumber(): ?int
    {
        return $this->hideQuestionNumber;
    }

    public function setHideQuestionNumber(int $hideQuestionNumber): self
    {
        $this->hideQuestionNumber = $hideQuestionNumber;

        return $this;
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
