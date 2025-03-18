<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Event\CourseCreatedEvent;
use Chamilo\CoreBundle\Event\ExerciseEndedEvent;
use Chamilo\CoreBundle\Event\ExerciseQuestionAnsweredEvent;
use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\LearningPathEndedEvent;
use Chamilo\CoreBundle\Event\LearningPathItemViewedEvent;
use Chamilo\CoreBundle\Event\PortfolioCommentEditedEvent;
use Chamilo\CoreBundle\Event\PortfolioCommentScoredEvent;
use Chamilo\CoreBundle\Event\PortfolioItemAddedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemCommentedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemDownloadedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemEditedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemHighlightedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemScoredEvent;
use Chamilo\CoreBundle\Event\PortfolioItemViewedEvent;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\LearningPathCompleted;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\LearningPathItemViewed;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioCommentEdited;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioCommentScored;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioDownloaded;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioItemCommented;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioItemScored;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioItemShared;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioItemViewed;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\QuizCompleted;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\QuizQuestionAnswered;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class XApiEventSubscriber implements EventSubscriberInterface
{
    use XApiActivityTrait;

    protected XApiPlugin $plugin;

    public function __construct()
    {
        $this->plugin = XApiPlugin::create();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::COURSE_CREATED => 'onCreateCourse',

            Events::EXERCISE_QUESTION_ANSWERED => 'onExerciseQuestionAnswered',
            Events::EXERCISE_ENDED => 'onExerciseEnded',

            Events::LP_ITEM_VIEWED => 'onLpItemViewed',
            Events::LP_ENDED => 'onLpEnded',

            Events::PORTFOLIO_ITEM_ADDED => 'onPortfolioItemAdded',
            Events::PORTFOLIO_ITEM_EDITED => 'onPortfolioItemEdited',
            Events::PORTFOLIO_ITEM_VIEWED => 'onPortfolioItemViewed',
            Events::PORTFOLIO_ITEM_COMMENTED => 'onPortfolioItemCommented',
            Events::PORTFOLIO_ITEM_HIGHLIGHTED => 'onPortfolioItemHighlighted',
            Events::PORTFOLIO_DOWNLOADED => 'onPortfolioItemDownloaded',
            Events::PORTFOLIO_ITEM_SCORED => 'onPortfolioItemScored',
            Events::PORTFOLIO_COMMENT_SCORED => 'onPortfolioCommentScored',
            Events::PORTFOLIO_COMMENT_EDITED => 'onPortfolioCommentEdited',
        ];
    }

    public function onCreateCourse(CourseCreatedEvent $event): void
    {
        $plugin = XApiPlugin::create();

        $course = $event->getCourse();

        if (AbstractEvent::TYPE_POST === $event->getType() && $course) {
            $plugin->addCourseToolForTinCan($course->getId());
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws TransactionRequiredException
     */
    public function onExerciseQuestionAnswered(ExerciseQuestionAnsweredEvent $event): void
    {
        $em = Database::getManager();
        $attemptRepo = $em->getRepository(TrackEAttempt::class);

        $exe = $em->find(TrackEExercise::class, $event->getTrackingExeId());
        $question = $em->find(CQuizQuestion::class, $event->getQuestionId());
        $attempt = $attemptRepo->findOneBy(
            [
                'exeId' => $exe->getExeId(),
                'questionId' => $question->getId(),
            ]
        );
        $quiz = $em->find(CQuiz::class, $event->getExerciseId());

        $quizQuestionAnswered = new QuizQuestionAnswered($attempt, $question, $quiz);

        $statement = $quizQuestionAnswered->generate();

        $this->saveSharedStatement($statement);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws TransactionRequiredException
     */
    public function onExerciseEnded(ExerciseEndedEvent $event): void
    {
        $em = Database::getManager();

        $exe = $em->find(TrackEExercise::class, $event->getTrackingExeId());
        $quiz = $em->find(CQuiz::class, $exe->getExeExoId());

        $quizCompleted = new QuizCompleted($exe, $quiz);

        $statement = $quizCompleted->generate();

        $this->saveSharedStatement($statement);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function onLpItemViewed(LearningPathItemViewedEvent $event): void
    {
        $em = Database::getManager();

        $lpItemView = $em->find(CLpItemView::class, $event->getItemViewId());
        $lpItem = $em->find(CLpItem::class, $lpItemView->getLpItemId());

        if ('quiz' == $lpItem->getItemType()) {
            return;
        }

        $lpView = $em->find(CLpView::class, $lpItemView->getLpViewId());

        $lpItemViewed = new LearningPathItemViewed($lpItemView, $lpItem, $lpView);

        $this->saveSharedStatement(
            $lpItemViewed->generate()
        );
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws TransactionRequiredException
     */
    public function onLpEnded(LearningPathEndedEvent $event): void
    {
        $em = Database::getManager();

        $lpView = $em->find(CLpView::class, $event->getLpViewId());
        $lp = $em->find(CLp::class, $lpView->getLpId());

        $learningPathEnded = new LearningPathCompleted($lpView, $lp);

        $this->saveSharedStatement(
            $learningPathEnded->generate()
        );
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function onPortfolioItemAdded(PortfolioItemAddedEvent $event): void
    {
        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        $statement = (new PortfolioItemShared($item))->generate();

        $this->saveSharedStatement($statement);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function onPortfolioItemEdited(PortfolioItemEditedEvent $event): void
    {
        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        $statement = (new PortfolioItemShared($item))->generate();

        $this->saveSharedStatement($statement);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function onPortfolioItemViewed(PortfolioItemViewedEvent $event): void
    {
        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        $statement = (new PortfolioItemViewed($item))->generate();

        $this->saveSharedStatement($statement);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function onPortfolioItemCommented(PortfolioItemCommentedEvent $event): void
    {
        $comment = $event->getComment();

        if (!$comment) {
            return;
        }

        $portfolioItemCommented = new PortfolioItemCommented($comment);

        $statement = $portfolioItemCommented->generate();

        $this->saveSharedStatement($statement);
    }

    public function onPortfolioItemHighlighted(PortfolioItemHighlightedEvent $event): void
    {
        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        $statement = (new PortfolioItemHighlighted($item))->generate();

        $this->saveSharedStatement($statement);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function onPortfolioItemDownloaded(PortfolioItemDownloadedEvent $event): void
    {
        $owner = $event->getOwner();

        if (!$owner) {
            return;
        }

        $statement = (new PortfolioDownloaded($owner))->generate();

        $this->saveSharedStatement($statement);
    }

    public function onPortfolioItemScored(PortfolioItemScoredEvent $event): void
    {
        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        $statement = (new PortfolioItemScored($item))->generate();

        $this->saveSharedStatement($statement);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function onPortfolioCommentScored(PortfolioCommentScoredEvent $event): void
    {
        $comment = $event->getComment();

        if (!$comment) {
            return;
        }

        $statement = (new PortfolioCommentScored($comment))->generate();

        $this->saveSharedStatement($statement);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function onPortfolioCommentEdited(PortfolioCommentEditedEvent $event): void
    {
        $comment = $event->getComment();

        if (!$comment) {
            return;
        }

        $statement = (new PortfolioCommentEdited($comment))->generate();

        $this->saveSharedStatement($statement);
    }
}
