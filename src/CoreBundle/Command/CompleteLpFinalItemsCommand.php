<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Command\Concerns\BoundedConsoleOptionsTrait;
use Chamilo\CoreBundle\Component\LegacyCliBootstrapper;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\LearningPath\LearningPathFinalItemManager;
use Chamilo\CoreBundle\State\LearningPath\LearningPathRuntimeProgressManager;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

use const PHP_INT_MAX;

/**
 * A learning path's final item (item_type 'final_item' — the "Congratulations"
 * page holding the certificate/skill blocks) only gets marked complete when a
 * learner actually opens it in the runtime (see
 * LearningPathRuntimeItemProcessor::shouldCompleteWhenOpened(), which treats
 * every item type except quiz/hotpotatoes/sco/au/survey as complete-on-open).
 * A learner who finishes every other item but never clicks through to that
 * last page stays stuck just under 100%, and — since achievement-certificate
 * eligibility is gated on the learning path actually reaching 100% — never
 * becomes eligible for a certificate they have otherwise earned.
 *
 * This command finds every such learner, across every course and session, and
 * completes the final item for them exactly as if they had opened it: same
 * CLpItemView shape as LearningPathRuntimeItemProcessor, then the same
 * LearningPathRuntimeProgressManager::synchronize() and
 * LearningPathFinalItemManager::completeForLearner() calls a real open would
 * trigger — so certificate generation and skill awarding follow normally,
 * through the exact same code the runtime itself uses.
 */
#[AsCommand(
    name: 'chamilo:learning-path:complete-final-items',
    description: 'Completes a learning path\'s final item for every learner who has finished every other item but never opened it, across every course and session.'
)]
final class CompleteLpFinalItemsCommand extends Command
{
    use BoundedConsoleOptionsTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LearningPathRuntimeProgressManager $progressManager,
        private readonly LearningPathFinalItemManager $finalItemManager,
        private readonly LoggerInterface $logger,
        private readonly KernelInterface $kernel,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum final items to complete (or, in dry-run, report) in one run. Use 0 for no limit.',
                '500'
            )
            ->addOption(
                'scan-limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum learner progress records scanned in one run, across every learning path. Use 0 for no limit.',
                '5000'
            )
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_REQUIRED,
                'Learner progress records loaded per database batch, within one learning path.',
                '50'
            )
            ->addOption(
                'lp-batch-size',
                null,
                InputOption::VALUE_REQUIRED,
                'Learning paths loaded per database batch.',
                '50'
            )
            ->addOption(
                'after-lp-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Resume scanning after this learning path ID. Safe to reuse after an interrupted run: a learner whose final item is already completed is never touched again.',
                '0'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Evaluate candidates without completing anything.'
            )
            ->addOption(
                'sender-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Active platform administrator ID, used as the resource creator when completing a final '
                .'item triggers certificate generation or skill awarding. Required for a real (non-dry-run) '
                .'execution.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Complete learning path final items');

        try {
            // Completing the final item can flush through legacy certificate/skill
            // code (LearningPathFinalItemManager::completeForLearner()), which reads
            // the legacy Container::$container — never anchored to this command's own
            // container otherwise. See LegacyCliBootstrapper.
            LegacyCliBootstrapper::bootstrap($this->kernel, $this->entityManager);

            $limit = $this->boundedNonNegativeOption($input, 'limit', 100000);
            $scanLimit = $this->boundedNonNegativeOption($input, 'scan-limit', 1000000);
            $batchSize = $this->boundedPositiveOption($input, 'batch-size', 500);
            $lpBatchSize = $this->boundedPositiveOption($input, 'lp-batch-size', 500);
            $afterLpId = $this->boundedNonNegativeOption($input, 'after-lp-id', PHP_INT_MAX);
            $dryRun = (bool) $input->getOption('dry-run');

            $senderId = $input->getOption('sender-id');
            $senderId = null !== $senderId ? (int) $senderId : null;

            $creator = null;
            if (!$dryRun) {
                if (null === $senderId) {
                    $io->error(
                        '--sender-id is required for a real (non-dry-run) execution — used as the resource '
                        .'creator when completing a final item triggers certificate generation or skill '
                        .'awarding. Use --dry-run for a read-only evaluation.'
                    );

                    return Command::INVALID;
                }
                $creator = LegacyCliBootstrapper::authenticateSender($senderId, $this->entityManager, $this->tokenStorage);
            }

            $io->definitionList(
                ['Mode' => $dryRun ? 'dry-run' : 'complete final items'],
                ['Completion limit' => 0 === $limit ? 'unlimited' : (string) $limit],
                ['Scan limit' => 0 === $scanLimit ? 'unlimited' : (string) $scanLimit],
                ['After learning path ID' => (string) $afterLpId]
            );

            $summary = [
                'learning_paths_scanned' => 0,
                'scanned' => 0,
                'incomplete' => 0,
                'would_complete' => 0,
                'completed' => 0,
                'failed' => 0,
                'last_lp_id' => $afterLpId,
            ];
            $previewRows = [];
            $lastLpId = $afterLpId;

            while (true) {
                if ($this->runLimitsReached($summary, $limit, $scanLimit, $dryRun)) {
                    break;
                }

                $lpIds = $this->loadEligibleLearningPathIds($lastLpId, $lpBatchSize);
                if ([] === $lpIds) {
                    break;
                }

                foreach ($lpIds as $lpId) {
                    $lastLpId = $lpId;
                    $summary['last_lp_id'] = $lpId;

                    if ($this->runLimitsReached($summary, $limit, $scanLimit, $dryRun)) {
                        break 2;
                    }

                    ++$summary['learning_paths_scanned'];

                    $this->processLearningPath(
                        $lpId,
                        $limit,
                        $scanLimit,
                        $batchSize,
                        $dryRun,
                        $creator,
                        $summary,
                        $previewRows
                    );

                    // Release every entity hydrated for this learning path before moving to
                    // the next one — an unbounded identity map across every course/session on
                    // the platform is exactly the pattern that has caused OOM elsewhere in
                    // this codebase's own migrations.
                    $this->entityManager->clear();
                }
            }

            if ([] !== $previewRows) {
                $io->table(
                    ['LP ID', 'User ID', 'Result'],
                    \array_slice($previewRows, 0, 100)
                );
            }

            $io->definitionList(
                ['Learning paths scanned' => $summary['learning_paths_scanned']],
                ['Learner progress records scanned' => $summary['scanned']],
                ['Not actually eligible' => $summary['incomplete']],
                ['Would complete' => $summary['would_complete']],
                ['Completed' => $summary['completed']],
                ['Failed' => $summary['failed']],
                ['Last scanned learning path ID' => $summary['last_lp_id']]
            );

            if ($summary['failed'] > 0) {
                $io->error(
                    'One or more final items failed to complete. See the table above for the reason, '
                    .'or the application log for full details.'
                );

                return Command::FAILURE;
            }

            if ($dryRun) {
                $io->success('Read-only evaluation completed.');
            } else {
                $io->success('Eligible final items were completed safely.');
            }

            if (!$this->generationLimitReached($summary, $limit, $dryRun)
                && 0 !== $scanLimit
                && $summary['scanned'] >= $scanLimit
            ) {
                $io->note(\sprintf(
                    'The scan limit was reached. Continue with --after-lp-id=%d.',
                    $summary['last_lp_id']
                ));
            }

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Processes one learning path: for every learner progress record below
     * 100%, checks whether every item except the final one is already
     * completed, and if so, completes the final item exactly as
     * LearningPathRuntimeItemProcessor would on a real open.
     *
     * @param array<string, int>                     $summary
     * @param list<array{0: int, 1: int, 2: string}> $previewRows
     */
    private function processLearningPath(
        int $lpId,
        int $limit,
        int $scanLimit,
        int $batchSize,
        bool $dryRun,
        ?User $creator,
        array &$summary,
        array &$previewRows
    ): void {
        /** @var CLp|null $lp */
        $lp = $this->entityManager->find(CLp::class, $lpId);
        if (!$lp instanceof CLp) {
            return;
        }

        $items = $this->progressManager->getItems($lp);
        $finalItem = null;
        foreach ($items as $item) {
            if ('final_item' === strtolower(trim($item->getItemType()))) {
                $finalItem = $item;

                break;
            }
        }
        if (!$finalItem instanceof CLpItem) {
            // Defensive only: loadEligibleLearningPathIds() already filtered on this.
            return;
        }

        $lastUserId = 0;

        while (true) {
            if ($this->runLimitsReached($summary, $limit, $scanLimit, $dryRun)) {
                return;
            }

            $remainingScan = 0 === $scanLimit ? $batchSize : max(0, $scanLimit - $summary['scanned']);
            if (0 === $remainingScan) {
                return;
            }

            $views = $this->loadPendingViews($lp, $lastUserId, min($batchSize, $remainingScan));
            if ([] === $views) {
                return;
            }

            foreach ($views as $view) {
                $lastUserId = (int) $view->getUser()->getId();
                ++$summary['scanned'];

                $latestViews = $this->progressManager->indexLatestItemViews($view);

                $finalItemView = $latestViews[(int) $finalItem->getIid()] ?? null;
                if ($finalItemView instanceof CLpItemView
                    && $this->progressManager->isCompletedStatus($finalItemView->getStatus())
                ) {
                    // The final item is already completed; something else keeps this
                    // learner below 100% — not this command's concern.
                    ++$summary['incomplete'];

                    continue;
                }

                $allOthersCompleted = true;
                foreach ($items as $item) {
                    // Same exclusion as LearningPathRuntimeProgressManager::calculateProgress():
                    // structural nodes are never "viewed" on their own and don't count.
                    if ((int) $item->getIid() === (int) $finalItem->getIid()
                        || \in_array(strtolower(trim($item->getItemType())), ['root', 'dir'], true)
                    ) {
                        continue;
                    }

                    $itemView = $latestViews[(int) $item->getIid()] ?? null;
                    if (!$itemView instanceof CLpItemView
                        || !$this->progressManager->isCompletedStatus($itemView->getStatus())
                    ) {
                        $allOthersCompleted = false;

                        break;
                    }
                }

                if (!$allOthersCompleted) {
                    ++$summary['incomplete'];

                    continue;
                }

                if ($dryRun) {
                    ++$summary['would_complete'];
                    $previewRows[] = [$lpId, $lastUserId, 'would complete'];

                    if ($this->generationLimitReached($summary, $limit, $dryRun)) {
                        return;
                    }

                    continue;
                }

                try {
                    $this->completeFinalItemForLearner($lp, $finalItem, $view, $finalItemView, $creator);
                    ++$summary['completed'];
                    $previewRows[] = [$lpId, $lastUserId, 'completed'];
                } catch (Throwable $exception) {
                    ++$summary['failed'];
                    $previewRows[] = [$lpId, $lastUserId, 'failed: '.$exception->getMessage()];
                    $this->logger->error(
                        'chamilo:learning-path:complete-final-items: failed to complete final item.',
                        [
                            'lpId' => $lpId,
                            'userId' => $lastUserId,
                            'exception' => $exception,
                        ]
                    );
                }

                if ($this->generationLimitReached($summary, $limit, $dryRun)) {
                    return;
                }
            }
        }
    }

    private function completeFinalItemForLearner(
        CLp $lp,
        CLpItem $finalItem,
        CLpView $view,
        ?CLpItemView $finalItemView,
        ?User $creator
    ): void {
        if (!$finalItemView instanceof CLpItemView) {
            $finalItemView = (new CLpItemView())
                ->setItem($finalItem)
                ->setView($view)
                ->setViewCount($this->progressManager->getNextItemAttempt($view, $finalItem))
                ->setStartTime(time())
                ->setTotalTime(0)
                ->setScore(0.0)
            ;
            $this->entityManager->persist($finalItemView);
        }
        // Same effect as LearningPathRuntimeItemProcessor::process() opening it for
        // real: 'final_item' is not in EXTERNALLY_COMPLETED_TYPES, so opening it
        // completes it immediately.
        $finalItemView->setStatus('completed');
        $this->entityManager->flush();

        $this->progressManager->synchronize($lp, $view);

        $this->finalItemManager->completeForLearner(
            $finalItem,
            $view->getCourse(),
            $view->getSession(),
            $view->getUser(),
            false,
            $creator
        );
    }

    /**
     * @return list<int>
     */
    private function loadEligibleLearningPathIds(int $afterLpId, int $batchSize): array
    {
        /** @var list<array{lp_id: int}> $rows */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT IDENTITY(i.lp) AS lp_id')
            ->from(CLpItem::class, 'i')
            ->where('i.itemType = :finalItem')
            ->andWhere('IDENTITY(i.lp) > :afterLpId')
            ->setParameter('finalItem', 'final_item')
            ->setParameter('afterLpId', $afterLpId)
            ->orderBy('IDENTITY(i.lp)', 'ASC')
            ->setMaxResults($batchSize)
            ->getQuery()
            ->getArrayResult()
        ;

        return array_map(static fn (array $row): int => (int) $row['lp_id'], $rows);
    }

    /**
     * @return list<CLpView>
     */
    private function loadPendingViews(CLp $lp, int $afterUserId, int $batchSize): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('v')
            ->from(CLpView::class, 'v')
            ->where('v.lp = :lp')
            ->andWhere('(v.progress IS NULL OR v.progress < 100)')
            ->andWhere('IDENTITY(v.user) > :afterUserId')
            ->setParameter('lp', $lp->getIid())
            ->setParameter('afterUserId', $afterUserId)
            ->orderBy('v.user', 'ASC')
            ->setMaxResults($batchSize)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param array<string, int> $summary
     */
    private function generationLimitReached(array $summary, int $limit, bool $dryRun): bool
    {
        if (0 === $limit) {
            return false;
        }

        $processed = $dryRun ? $summary['would_complete'] : $summary['completed'];

        return $processed >= $limit;
    }

    /**
     * @param array<string, int> $summary
     */
    private function runLimitsReached(array $summary, int $limit, int $scanLimit, bool $dryRun): bool
    {
        if ($this->generationLimitReached($summary, $limit, $dryRun)) {
            return true;
        }

        return 0 !== $scanLimit && $summary['scanned'] >= $scanLimit;
    }
}
