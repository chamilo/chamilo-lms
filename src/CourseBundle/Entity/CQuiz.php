<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Course quizzes.
 */
#[ORM\Table(name: 'c_quiz')]
#[ORM\Entity(repositoryClass: CQuizRepository::class)]
class CQuiz extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    public const ALL_ON_ONE_PAGE = 1;
    public const ONE_PER_PAGE = 2;

    #[Groups(['track_e_exercise:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'text', nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'sound', type: 'string', length: 255, nullable: true)]
    protected ?string $sound = null;

    #[ORM\Column(name: 'type', type: 'integer', nullable: false)]
    protected int $type;

    #[ORM\Column(name: 'random', type: 'integer', nullable: false)]
    protected int $random;

    #[ORM\Column(name: 'random_answers', type: 'boolean', nullable: false)]
    protected bool $randomAnswers;

    #[ORM\Column(name: 'active', type: 'integer', nullable: false)]
    protected int $active;

    #[ORM\Column(name: 'results_disabled', type: 'integer', nullable: false)]
    protected int $resultsDisabled;

    #[ORM\Column(name: 'access_condition', type: 'text', nullable: true)]
    protected ?string $accessCondition = null;

    #[ORM\Column(name: 'max_attempt', type: 'integer', nullable: false)]
    protected int $maxAttempt;

    #[ORM\Column(name: 'start_time', type: 'datetime', nullable: true)]
    protected ?DateTime $startTime = null;

    #[ORM\Column(name: 'end_time', type: 'datetime', nullable: true)]
    protected ?DateTime $endTime = null;

    #[ORM\Column(name: 'feedback_type', type: 'integer', nullable: false)]
    protected int $feedbackType;

    #[ORM\Column(name: 'expired_time', type: 'integer', nullable: false)]
    protected int $expiredTime;

    #[ORM\Column(name: 'propagate_neg', type: 'integer', nullable: false)]
    protected int $propagateNeg;

    #[ORM\Column(name: 'save_correct_answers', type: 'integer', nullable: true)]
    protected ?int $saveCorrectAnswers;

    #[ORM\Column(name: 'review_answers', type: 'integer', nullable: false)]
    protected int $reviewAnswers;

    #[ORM\Column(name: 'random_by_category', type: 'integer', nullable: false)]
    protected int $randomByCategory;

    #[ORM\Column(name: 'text_when_finished', type: 'text', nullable: true)]
    protected ?string $textWhenFinished = null;

    #[ORM\Column(name: 'text_when_finished_failure', type: 'text', nullable: true)]
    protected ?string $textWhenFinishedFailure = null;

    #[ORM\Column(name: 'display_category_name', type: 'integer', nullable: false)]
    protected int $displayCategoryName;

    #[ORM\Column(name: 'pass_percentage', type: 'integer', nullable: true)]
    protected ?int $passPercentage = null;

    #[ORM\Column(name: 'prevent_backwards', type: 'integer', nullable: false, options: ['default' => 0])]
    protected int $preventBackwards;

    #[ORM\Column(name: 'question_selection_type', type: 'integer', nullable: true)]
    protected ?int $questionSelectionType = null;

    #[ORM\Column(name: 'hide_question_number', type: 'integer', nullable: false, options: ['default' => 0])]
    protected int $hideQuestionNumber;

    #[ORM\Column(name: 'hide_question_title', type: 'boolean', nullable: false)]
    protected bool $hideQuestionTitle;

    #[ORM\ManyToOne(targetEntity: CQuizCategory::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'quiz_category_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?CQuizCategory $quizCategory = null;

    #[ORM\Column(name: 'show_previous_button', type: 'boolean', nullable: false, options: ['default' => 1])]
    protected bool $showPreviousButton;

    #[ORM\Column(name: 'notifications', type: 'string', length: 255, nullable: true)]
    protected ?string $notifications;

    #[ORM\Column(name: 'autolaunch', type: 'boolean', nullable: true, options: ['default' => 0])]
    protected ?bool $autoLaunch;

    #[ORM\Column(name: 'hide_attempts_table', type: 'boolean', nullable: false, options: ['default' => 0])]
    protected bool $hideAttemptsTable;

    #[ORM\Column(name: 'page_result_configuration', type: 'array')]
    protected array $pageResultConfiguration = [];

    #[ORM\Column(name: 'display_chart_degree_certainty', type: 'integer', options: ['default' => 0])]
    protected int $displayChartDegreeCertainty = 0;

    #[ORM\Column(name: 'send_email_chart_degree_certainty', type: 'integer', options: ['default' => 0])]
    protected int $sendEmailChartDegreeCertainty = 0;

    #[ORM\Column(name: 'not_display_balance_percentage_categorie_question', type: 'integer', options: ['default' => 0])]
    protected int $notDisplayBalancePercentageCategorieQuestion = 0;

    #[ORM\Column(name: 'display_chart_degree_certainty_category', type: 'integer', options: ['default' => 0])]
    protected int $displayChartDegreeCertaintyCategory = 0;

    #[ORM\Column(name: 'gather_questions_categories', type: 'integer', options: ['default' => 0])]
    protected int $gatherQuestionsCategories = 0;

    /**
     * @var Collection<int, CQuizRelQuestion>
     */
    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: CQuizRelQuestion::class, cascade: ['persist'], orphanRemoval: true)]
    protected Collection $questions;

    /**
     * @var Collection<int, CQuizRelQuestionCategory>
     */
    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: CQuizRelQuestionCategory::class, cascade: ['persist'])]
    protected Collection $questionsCategories;

    /**
     * @var Collection<int, TrackEExercise>
     */
    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: TrackEExercise::class)]
    protected Collection $attempts;

    #[ORM\Column(name: 'duration', type: 'integer', nullable: true)]
    protected ?int $duration = null;

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
        $this->hideAttemptsTable = false;
        $this->pageResultConfiguration = [];
        $this->attempts = new ArrayCollection();
        $this->hideAttemptsTable = false;
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
     * @return ArrayCollection<int, CQuizRelQuestion>
     */
    public function getQuestions(): Collection
    {
        return $this->questions instanceof ArrayCollection ?
            $this->questions :
            new ArrayCollection($this->questions->toArray());
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

    public function getSound(): ?string
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

    public function getRandomAnswers(): bool
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

    public function getResultsDisabled(): int
    {
        return $this->resultsDisabled;
    }

    public function setAccessCondition(string $accessCondition): self
    {
        $this->accessCondition = $accessCondition;

        return $this;
    }

    public function getAccessCondition(): ?string
    {
        return $this->accessCondition;
    }

    public function setMaxAttempt(int $maxAttempt): self
    {
        $this->maxAttempt = $maxAttempt;

        return $this;
    }

    public function getMaxAttempt(): int
    {
        return $this->maxAttempt;
    }

    public function setStartTime(?DateTime $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getStartTime(): ?DateTime
    {
        return $this->startTime;
    }

    public function setEndTime(?DateTime $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getEndTime(): ?DateTime
    {
        return $this->endTime;
    }

    public function setFeedbackType(int $feedbackType): self
    {
        $this->feedbackType = $feedbackType;

        return $this;
    }

    public function getFeedbackType(): int
    {
        return $this->feedbackType;
    }

    public function setExpiredTime(int $expiredTime): self
    {
        $this->expiredTime = $expiredTime;

        return $this;
    }

    public function getExpiredTime(): int
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
     */
    public function getPropagateNeg(): int
    {
        return $this->propagateNeg;
    }

    public function setSaveCorrectAnswers(int $saveCorrectAnswers): self
    {
        $this->saveCorrectAnswers = $saveCorrectAnswers;

        return $this;
    }

    public function getSaveCorrectAnswers(): ?int
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
     */
    public function getReviewAnswers(): int
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
     */
    public function getRandomByCategory(): int
    {
        return $this->randomByCategory;
    }

    /**
     * Set text to display to user when they succeed to the test or, when no pass percentage has been set, when the
     * test is finished.
     */
    public function setTextWhenFinished(string $textWhenFinished): self
    {
        $this->textWhenFinished = $textWhenFinished;

        return $this;
    }

    /**
     * Get text to display to user when they succeed to the test or, when no pass percentage has been set, when the
     * test is finished.
     */
    public function getTextWhenFinished(): ?string
    {
        return $this->textWhenFinished;
    }

    /**
     * Set text to display to user when they fail to the test (when pass percentage has been set).
     */
    public function setTextWhenFinishedFailure(?string $textWhenFinished): self
    {
        $this->textWhenFinishedFailure = $textWhenFinished;

        return $this;
    }

    /**
     * Get text to display to user when they fail to the test (when pass percentage has been set).
     */
    public function getTextWhenFinishedFailure(): ?string
    {
        if (empty($this->textWhenFinishedFailure)) {
            return '';
        }

        return $this->textWhenFinishedFailure;
    }

    public function setDisplayCategoryName(int $displayCategoryName): self
    {
        $this->displayCategoryName = $displayCategoryName;

        return $this;
    }

    /**
     * Get displayCategoryName.
     */
    public function getDisplayCategoryName(): int
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
     */
    public function getPassPercentage(): ?int
    {
        return $this->passPercentage;
    }

    public function getQuizCategory(): ?CQuizCategory
    {
        return $this->quizCategory;
    }

    public function setQuizCategory(CQuizCategory $quizCategory): self
    {
        $this->quizCategory = $quizCategory;

        return $this;
    }

    public function getQuestionSelectionType(): ?int
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

    public function getIid(): ?int
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

    public function getDisplayChartDegreeCertainty(): int
    {
        return $this->displayChartDegreeCertainty;
    }

    public function setDisplayChartDegreeCertainty(int $displayChartDegreeCertainty): self
    {
        $this->displayChartDegreeCertainty = $displayChartDegreeCertainty;

        return $this;
    }

    public function getSendEmailChartDegreeCertainty(): int
    {
        return $this->sendEmailChartDegreeCertainty;
    }

    public function setSendEmailChartDegreeCertainty(int $sendEmailChartDegreeCertainty): self
    {
        $this->sendEmailChartDegreeCertainty = $sendEmailChartDegreeCertainty;

        return $this;
    }

    public function getNotDisplayBalancePercentageCategorieQuestion(): int
    {
        return $this->notDisplayBalancePercentageCategorieQuestion;
    }

    public function setNotDisplayBalancePercentageCategorieQuestion(int $notDisplayBalancePercentageCategorieQuestion): self
    {
        $this->notDisplayBalancePercentageCategorieQuestion = $notDisplayBalancePercentageCategorieQuestion;

        return $this;
    }

    public function getDisplayChartDegreeCertaintyCategory(): int
    {
        return $this->displayChartDegreeCertaintyCategory;
    }

    public function setDisplayChartDegreeCertaintyCategory(int $displayChartDegreeCertaintyCategory): self
    {
        $this->displayChartDegreeCertaintyCategory = $displayChartDegreeCertaintyCategory;

        return $this;
    }

    public function getGatherQuestionsCategories(): int
    {
        return $this->gatherQuestionsCategories;
    }

    public function setGatherQuestionsCategories(int $gatherQuestionsCategories): self
    {
        $this->gatherQuestionsCategories = $gatherQuestionsCategories;

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

    public function isHideAttemptsTable(): bool
    {
        return $this->hideAttemptsTable;
    }

    public function setHideAttemptsTable(bool $hideAttemptsTable): self
    {
        $this->hideAttemptsTable = $hideAttemptsTable;

        return $this;
    }

    /**
     * @return Collection<int, CQuizRelQuestionCategory>
     */
    public function getQuestionsCategories(): Collection
    {
        return $this->questionsCategories instanceof ArrayCollection ?
            $this->questionsCategories :
            new ArrayCollection($this->questionsCategories->toArray());
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getResourceIdentifier(): int|Uuid
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

    /**
     * @return Collection<int, TrackEExercise>
     */
    public function getAttempts(): Collection
    {
        return $this->attempts;
    }
}
