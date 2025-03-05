<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\HookEvent\CourseCreatedHookEvent;
use Chamilo\CoreBundle\HookEvent\ExerciseEndedHookEvent;
use Chamilo\CoreBundle\HookEvent\ExerciseQuestionAnsweredHookEvent;
use Chamilo\CoreBundle\HookEvent\HookEvent;
use Chamilo\CoreBundle\HookEvent\HookEvents;
use Chamilo\CoreBundle\HookEvent\LearningPathEndedHookEvent;
use Chamilo\CoreBundle\HookEvent\LearningPathItemViewedHookEvent;
use Chamilo\CoreBundle\HookEvent\PortfolioCommentEditedHookEvent;
use Chamilo\CoreBundle\HookEvent\PortfolioCommentScoredHookEvent;
use Chamilo\CoreBundle\HookEvent\PortfolioItemAddedHookEvent;
use Chamilo\CoreBundle\HookEvent\PortfolioItemCommentedHookEvent;
use Chamilo\CoreBundle\HookEvent\PortfolioItemDownloadedHookEvent;
use Chamilo\CoreBundle\HookEvent\PortfolioItemEditedHookEvent;
use Chamilo\CoreBundle\HookEvent\PortfolioItemHighlightedHookEvent;
use Chamilo\CoreBundle\HookEvent\PortfolioItemScoredHookEvent;
use Chamilo\CoreBundle\HookEvent\PortfolioItemViewedHookEvent;
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
            HookEvents::COURSE_CREATED => 'onCreateCourse',

            HookEvents::EXERCISE_QUESTION_ANSWERED => 'onExerciseQuestionAnswered',
            HookEvents::EXERCISE_ENDED => 'onExerciseEnded',

            HookEvents::LP_ITEM_VIEWED => 'onLpItemViewed',
            HookEvents::LP_ENDED => 'onLpEnded',

            HookEvents::PORTFOLIO_ITEM_ADDED => 'onPortfolioItemAdded',
            HookEvents::PORTFOLIO_ITEM_EDITED => 'onPortfolioItemEdited',
            HookEvents::PORTFOLIO_ITEM_VIEWED => 'onPortfolioItemViewed',
            HookEvents::PORTFOLIO_ITEM_COMMENTED => 'onPortfolioItemCommented',
            HookEvents::PORTFOLIO_ITEM_HIGHLIGHTED => 'onPortfolioItemHighlighted',
            HookEVents::PORTFOLIO_DOWNLOADED => 'onPortfolioItemDownloaded',
            HookEvents::PORTFOLIO_ITEM_SCORED => 'onPortfolioItemScored',
            HookEvents::PORTFOLIO_COMMENT_SCORED => 'onPortfolioCommentScored',
            HookEvents::PORTFOLIO_COMMENT_EDITED => 'onPortfolioCommentEdited',
        ];
    }

    public function onCreateCourse(CourseCreatedHookEvent $event): void
    {
        $plugin = XApiPlugin::create();

        if (HookEvent::TYPE_POST === $event->getType()) {
            $plugin->addCourseToolForTinCan($event->getCourseInfo()['id']);
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws TransactionRequiredException
     */
    public function onExerciseQuestionAnswered(ExerciseQuestionAnsweredHookEvent $event): void
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
    public function onExerciseEnded(ExerciseEndedHookEvent $event): void
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
    public function onLpItemViewed(LearningPathItemViewedHookEvent $event): void
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
    public function onLpEnded(LearningPathEndedHookEvent $event): void
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
    public function onPortfolioItemAdded(PortfolioItemAddedHookEvent $event): void
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
    public function onPortfolioItemEdited(PortfolioItemEditedHookEvent $event): void
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
    public function onPortfolioItemViewed(PortfolioItemViewedHookEvent $event): void
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
    public function onPortfolioItemCommented(PortfolioItemCommentedHookEvent $event): void
    {
        $comment = $event->getComment();

        if (!$comment) {
            return;
        }

        $portfolioItemCommented = new PortfolioItemCommented($comment);

        $statement = $portfolioItemCommented->generate();

        $this->saveSharedStatement($statement);
    }

    public function onPortfolioItemHighlighted(PortfolioItemHighlightedHookEvent $event): void
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
    public function onPortfolioItemDownloaded(PortfolioItemDownloadedHookEvent $event): void
    {
        $owner = $event->getOwner();

        if (!$owner) {
            return;
        }

        $statement = (new PortfolioDownloaded($owner))->generate();

        $this->saveSharedStatement($statement);
    }

    public function onPortfolioItemScored(PortfolioItemScoredHookEvent $event): void
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
    public function onPortfolioCommentScored(PortfolioCommentScoredHookEvent $event): void
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
    public function onPortfolioCommentEdited(PortfolioCommentEditedHookEvent $event): void
    {
        $comment = $event->getComment();

        if (!$comment) {
            return;
        }

        $statement = (new PortfolioCommentEdited($comment))->generate();

        $this->saveSharedStatement($statement);
    }
}
