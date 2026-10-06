<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'chamilo:migration:repair-quiz-relations-from-legacy',
    description: 'Audits target quiz-question relations and, when a legacy database is supplied, safely restores only verified missing relations.',
)]
final class RepairQuizRelationsFromLegacyCommand extends Command
{
    private const int MAX_DEFAULT_DETAIL_ROWS = 100;

    public function __construct(
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'source-database',
                null,
                InputOption::VALUE_REQUIRED,
                'Optional legacy Chamilo database on the same database server. Required only for legacy comparison and --apply.'
            )
            ->addOption(
                'quiz',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Limit the audit/repair to one or more target quiz iids. Omit to audit every legacy quiz with relations.'
            )
            ->addOption(
                'expected-missing-relations',
                null,
                InputOption::VALUE_REQUIRED,
                'Exact missing-relation count observed during the read-only audit. Required with --apply.'
            )
            ->addOption(
                'expected-affected-quizzes',
                null,
                InputOption::VALUE_REQUIRED,
                'Exact affected-quiz count observed during the read-only audit. Required with --apply.'
            )
            ->addOption(
                'apply',
                null,
                InputOption::VALUE_NONE,
                'Insert only verified missing relations. Without this option the command is read-only.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Audit and repair quiz-question relations from legacy database');

        $sourceDatabase = trim((string) $input->getOption('source-database'));
        $apply = (bool) $input->getOption('apply');

        if ('' !== $sourceDatabase && 1 !== preg_match('/^[A-Za-z0-9_]+$/D', $sourceDatabase)) {
            $io->error('--source-database must contain only letters, numbers, and underscores.');

            return Command::INVALID;
        }

        try {
            $quizFilter = $this->normalizeQuizFilter($input->getOption('quiz'));

            if ('' === $sourceDatabase) {
                if ($apply) {
                    throw new RuntimeException('--source-database is required with --apply because missing relations cannot be reconstructed safely from the migrated database alone.');
                }

                $this->assertTargetDatabaseIsUsable();
                $targetAudit = $this->auditTargetOnly($quizFilter);
                $this->printTargetOnlyAudit($targetAudit, $quizFilter, $io);
                $io->note('Read-only target audit. Empty quizzes are candidates for review, not proof of lost relations. Supply --source-database to compare against legacy evidence.');

                return Command::SUCCESS;
            }

            $this->assertSourceDatabaseIsUsable($sourceDatabase);

            $audit = $this->audit($sourceDatabase, $quizFilter);
            $this->printAudit($audit, $sourceDatabase, $quizFilter, $io);

            if (!$apply) {
                $io->note('Read-only mode. No database changes were made.');

                return Command::SUCCESS;
            }

            $expectedMissing = $this->requiredExpectedCount($input, 'expected-missing-relations');
            $expectedAffected = $this->requiredExpectedCount($input, 'expected-affected-quizzes');

            if ($expectedMissing !== $audit['summary']['missing_relations']) {
                throw new RuntimeException(\sprintf('Repair refused: missing relation count is %d, expected %d.', $audit['summary']['missing_relations'], $expectedMissing));
            }

            if ($expectedAffected !== $audit['summary']['affected_quizzes']) {
                throw new RuntimeException(\sprintf('Repair refused: affected quiz count is %d, expected %d.', $audit['summary']['affected_quizzes'], $expectedAffected));
            }

            if (0 === $audit['summary']['missing_relations']) {
                $io->success('Nothing to repair. All resolved legacy relations are already present.');

                return Command::SUCCESS;
            }

            if ($audit['summary']['unsafe_affected_quizzes'] > 0) {
                throw new RuntimeException(\sprintf('Repair refused: %d affected quiz(es) contain conflicts or unresolved data. Review them before applying.', $audit['summary']['unsafe_affected_quizzes']));
            }

            $inserted = $this->apply($sourceDatabase, $quizFilter, $expectedMissing, $expectedAffected);
            $after = $this->audit($sourceDatabase, $quizFilter);

            if ($after['summary']['missing_relations'] > 0) {
                throw new RuntimeException(\sprintf('Post-repair verification failed: %d resolved legacy relation(s) are still missing.', $after['summary']['missing_relations']));
            }

            $io->success(\sprintf(
                'Repair completed safely. Inserted=%d; remaining missing relations=0.',
                $inserted
            ));

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @return list<int>
     */
    private function normalizeQuizFilter(mixed $rawQuizFilter): array
    {
        $values = \is_array($rawQuizFilter) ? $rawQuizFilter : [];
        $quizIds = [];

        foreach ($values as $value) {
            $value = trim((string) $value);
            if ('' === $value || !ctype_digit($value) || (int) $value <= 0) {
                throw new RuntimeException('--quiz must contain only positive integer quiz iids.');
            }

            $quizIds[(int) $value] = (int) $value;
        }

        ksort($quizIds);

        return array_values($quizIds);
    }

    /**
     * @param list<int> $quizFilter
     *
     * @return array{
     *     summary: array{
     *         audited_quizzes: int,
     *         relation_rows: int,
     *         empty_quizzes: int,
     *         null_question_references: int,
     *         missing_question_references: int,
     *         duplicate_pairs: int,
     *         orphan_quiz_references: int
     *     },
     *     quizzes: array<int, array<string, int|bool>>
     * }
     */
    private function auditTargetOnly(array $quizFilter): array
    {
        $quizFilterSql = '';
        $quizFilterParams = [];
        $quizFilterTypes = [];

        if ([] !== $quizFilter) {
            $quizFilterSql = ' WHERE quiz.iid IN (:quizIds)';
            $quizFilterParams['quizIds'] = $quizFilter;
            $quizFilterTypes['quizIds'] = ArrayParameterType::INTEGER;
        }

        $quizRows = $this->connection->executeQuery(
            'SELECT
                quiz.iid AS quiz_id,
                COUNT(relation.iid) AS relation_count,
                SUM(CASE WHEN relation.iid IS NOT NULL AND relation.question_id IS NULL THEN 1 ELSE 0 END) AS null_question_references,
                SUM(CASE WHEN relation.question_id IS NOT NULL AND question.iid IS NULL THEN 1 ELSE 0 END) AS missing_question_references
             FROM c_quiz quiz
             LEFT JOIN c_quiz_rel_question relation ON relation.quiz_id = quiz.iid
             LEFT JOIN c_quiz_question question ON question.iid = relation.question_id'
            .$quizFilterSql.
            ' GROUP BY quiz.iid
              ORDER BY quiz.iid ASC',
            $quizFilterParams,
            $quizFilterTypes
        )->fetchAllAssociative();

        $relationFilterSql = '';
        $relationFilterParams = [];
        $relationFilterTypes = [];
        if ([] !== $quizFilter) {
            $relationFilterSql = ' WHERE relation.quiz_id IN (:quizIds)';
            $relationFilterParams['quizIds'] = $quizFilter;
            $relationFilterTypes['quizIds'] = ArrayParameterType::INTEGER;
        }

        $duplicateRows = $this->connection->executeQuery(
            'SELECT relation.quiz_id, relation.question_id, COUNT(*) AS copies
             FROM c_quiz_rel_question relation'
            .$relationFilterSql
            .('' === $relationFilterSql ? ' WHERE' : ' AND')
            .' relation.question_id IS NOT NULL
             GROUP BY relation.quiz_id, relation.question_id
             HAVING COUNT(*) > 1',
            $relationFilterParams,
            $relationFilterTypes
        )->fetchAllAssociative();

        $duplicatePairsByQuiz = [];
        foreach ($duplicateRows as $row) {
            $quizId = (int) $row['quiz_id'];
            $duplicatePairsByQuiz[$quizId] = ($duplicatePairsByQuiz[$quizId] ?? 0) + 1;
        }

        $orphanFilter = '';
        $orphanParams = [];
        $orphanTypes = [];
        if ([] !== $quizFilter) {
            $orphanFilter = ' AND relation.quiz_id IN (:quizIds)';
            $orphanParams['quizIds'] = $quizFilter;
            $orphanTypes['quizIds'] = ArrayParameterType::INTEGER;
        }

        $orphanQuizReferences = (int) $this->connection->executeQuery(
            'SELECT COUNT(*)
             FROM c_quiz_rel_question relation
             LEFT JOIN c_quiz quiz ON quiz.iid = relation.quiz_id
             WHERE quiz.iid IS NULL'.$orphanFilter,
            $orphanParams,
            $orphanTypes
        )->fetchOne();

        $quizzes = [];
        $summary = [
            'audited_quizzes' => \count($quizRows),
            'relation_rows' => 0,
            'empty_quizzes' => 0,
            'null_question_references' => 0,
            'missing_question_references' => 0,
            'duplicate_pairs' => array_sum($duplicatePairsByQuiz),
            'orphan_quiz_references' => $orphanQuizReferences,
        ];

        foreach ($quizRows as $row) {
            $quizId = (int) $row['quiz_id'];
            $relationCount = (int) $row['relation_count'];
            $nullQuestionReferences = (int) $row['null_question_references'];
            $missingQuestionReferences = (int) $row['missing_question_references'];
            $duplicatePairs = $duplicatePairsByQuiz[$quizId] ?? 0;
            $empty = 0 === $relationCount;

            $quizzes[$quizId] = [
                'quiz_id' => $quizId,
                'relation_count' => $relationCount,
                'empty' => $empty,
                'null_question_references' => $nullQuestionReferences,
                'missing_question_references' => $missingQuestionReferences,
                'duplicate_pairs' => $duplicatePairs,
            ];

            $summary['relation_rows'] += $relationCount;
            $summary['null_question_references'] += $nullQuestionReferences;
            $summary['missing_question_references'] += $missingQuestionReferences;
            if ($empty) {
                ++$summary['empty_quizzes'];
            }
        }

        return [
            'summary' => $summary,
            'quizzes' => $quizzes,
        ];
    }

    /**
     * @param array{
     *     summary: array<string, int>,
     *     quizzes: array<int, array<string, int|bool>>
     * } $audit
     * @param list<int> $quizFilter
     */
    private function printTargetOnlyAudit(array $audit, array $quizFilter, SymfonyStyle $io): void
    {
        $summary = $audit['summary'];
        $targetDatabase = (string) $this->connection->fetchOne('SELECT DATABASE()');

        $io->definitionList(
            ['Target database' => $targetDatabase],
            ['Scope' => [] === $quizFilter ? 'All target quizzes' : 'Quiz iid(s): '.implode(', ', $quizFilter)],
            ['Audited quizzes' => $summary['audited_quizzes']],
            ['Target relation rows' => $summary['relation_rows']],
            ['Quizzes with no relations' => $summary['empty_quizzes']],
            ['NULL question references' => $summary['null_question_references']],
            ['Missing question records' => $summary['missing_question_references']],
            ['Duplicate quiz/question pairs' => $summary['duplicate_pairs']],
            ['Relations with missing quiz records' => $summary['orphan_quiz_references']]
        );

        $detailRows = [];
        foreach ($audit['quizzes'] as $quiz) {
            $hasIssue = true === $quiz['empty']
                || (int) $quiz['null_question_references'] > 0
                || (int) $quiz['missing_question_references'] > 0
                || (int) $quiz['duplicate_pairs'] > 0;

            if (!$hasIssue) {
                continue;
            }

            $detailRows[] = [
                $quiz['quiz_id'],
                $quiz['relation_count'],
                true === $quiz['empty'] ? 'yes' : 'no',
                $quiz['null_question_references'],
                $quiz['missing_question_references'],
                $quiz['duplicate_pairs'],
            ];
        }

        if ([] === $detailRows && 0 === $summary['orphan_quiz_references']) {
            $io->success('No structural quiz-relation issues were detected in the target database.');

            return;
        }

        if (!$io->isVerbose() && \count($detailRows) > self::MAX_DEFAULT_DETAIL_ROWS) {
            $omitted = \count($detailRows) - self::MAX_DEFAULT_DETAIL_ROWS;
            $detailRows = \array_slice($detailRows, 0, self::MAX_DEFAULT_DETAIL_ROWS);
            $io->warning(\sprintf(
                'Showing the first %d quiz(es) requiring review; %d additional row(s) omitted. Re-run with -v to show all.',
                self::MAX_DEFAULT_DETAIL_ROWS,
                $omitted
            ));
        }

        if ([] !== $detailRows) {
            $io->section('Target quizzes requiring review');
            $io->table(
                ['Quiz', 'Relations', 'Empty', 'NULL question', 'Missing question', 'Duplicate pairs'],
                $detailRows
            );
        }
    }

    /**
     * @param list<int> $quizFilter
     *
     * @return array{
     *     summary: array{
     *         legacy_relation_rows: int,
     *         resolved_legacy_relations: int,
     *         unresolved_legacy_question_relations: int,
     *         unresolved_legacy_quiz_relations: int,
     *         audited_quizzes: int,
     *         matching_relations: int,
     *         missing_relations: int,
     *         affected_quizzes: int,
     *         repairable_quizzes: int,
     *         unsafe_affected_quizzes: int,
     *         extra_target_relations: int,
     *         order_conflicts: int,
     *         null_target_relations: int,
     *         duplicate_source_pairs: int,
     *         duplicate_target_pairs: int,
     *         missing_target_quizzes: int,
     *         missing_target_questions: int
     *     },
     *     quizzes: array<int, array<string, int|string|bool>>,
     *     source_rows: array<int, list<array{question_order: int, question_id: int}>>
     * }
     */
    private function audit(string $sourceDatabase, array $quizFilter): array
    {
        $source = '`'.$sourceDatabase.'`';
        $filterSql = '';
        $filterParams = [];
        $filterTypes = [];

        if ([] !== $quizFilter) {
            $filterSql = ' AND quiz.iid IN (:quizIds)';
            $filterParams['quizIds'] = $quizFilter;
            $filterTypes['quizIds'] = ArrayParameterType::INTEGER;
        }

        $legacyRows = $this->connection->executeQuery(
            "SELECT
                relation.iid AS relation_iid,
                quiz.iid AS quiz_iid,
                quiz.title AS quiz_title,
                relation.question_order,
                question.iid AS question_iid
             FROM {$source}.c_quiz_rel_question relation
             LEFT JOIN {$source}.c_quiz quiz
               ON quiz.c_id = relation.c_id
              AND quiz.id = relation.exercice_id
             LEFT JOIN {$source}.c_quiz_question question
               ON question.c_id = relation.c_id
              AND question.id = relation.question_id
             WHERE 1 = 1{$filterSql}
             ORDER BY quiz.iid ASC, relation.question_order ASC, relation.iid ASC",
            $filterParams,
            $filterTypes
        )->fetchAllAssociative();

        $unresolvedLegacyQuizRelations = 0;
        if ([] === $quizFilter) {
            $unresolvedLegacyQuizRelations = (int) $this->connection->fetchOne(
                "SELECT COUNT(*)
                 FROM {$source}.c_quiz_rel_question relation
                 LEFT JOIN {$source}.c_quiz quiz
                   ON quiz.c_id = relation.c_id
                  AND quiz.id = relation.exercice_id
                 WHERE quiz.iid IS NULL"
            );
        }

        $sourceByQuiz = [];
        $quizTitles = [];
        $sourcePairSeen = [];
        $duplicateSourcePairsByQuiz = [];
        $unresolvedSourceByQuiz = [];
        $resolvedLegacyRelations = 0;

        foreach ($legacyRows as $row) {
            if (null === $row['quiz_iid']) {
                continue;
            }

            $quizId = (int) $row['quiz_iid'];
            $quizTitles[$quizId] = (string) ($row['quiz_title'] ?? '');

            if (null === $row['question_iid']) {
                $unresolvedSourceByQuiz[$quizId] = ($unresolvedSourceByQuiz[$quizId] ?? 0) + 1;

                continue;
            }

            $questionId = (int) $row['question_iid'];
            $pairKey = $quizId.':'.$questionId;
            if (isset($sourcePairSeen[$pairKey])) {
                $duplicateSourcePairsByQuiz[$quizId] = ($duplicateSourcePairsByQuiz[$quizId] ?? 0) + 1;

                continue;
            }

            $sourcePairSeen[$pairKey] = true;
            $sourceByQuiz[$quizId][] = [
                'question_order' => (int) $row['question_order'],
                'question_id' => $questionId,
            ];
            ++$resolvedLegacyRelations;
        }

        $auditQuizIds = array_values(array_unique(array_merge(
            array_keys($sourceByQuiz),
            array_keys($unresolvedSourceByQuiz),
            array_keys($duplicateSourcePairsByQuiz),
            $quizFilter
        )));
        sort($auditQuizIds);

        if ([] === $auditQuizIds) {
            return [
                'summary' => [
                    'legacy_relation_rows' => \count($legacyRows),
                    'resolved_legacy_relations' => $resolvedLegacyRelations,
                    'unresolved_legacy_question_relations' => array_sum($unresolvedSourceByQuiz),
                    'unresolved_legacy_quiz_relations' => $unresolvedLegacyQuizRelations,
                    'audited_quizzes' => 0,
                    'matching_relations' => 0,
                    'missing_relations' => 0,
                    'affected_quizzes' => 0,
                    'repairable_quizzes' => 0,
                    'unsafe_affected_quizzes' => 0,
                    'extra_target_relations' => 0,
                    'order_conflicts' => 0,
                    'null_target_relations' => 0,
                    'duplicate_source_pairs' => array_sum($duplicateSourcePairsByQuiz),
                    'duplicate_target_pairs' => 0,
                    'missing_target_quizzes' => 0,
                    'missing_target_questions' => 0,
                ],
                'quizzes' => [],
                'source_rows' => [],
            ];
        }

        $targetQuizIds = array_fill_keys(array_map(
            'intval',
            $this->connection->fetchFirstColumn('SELECT iid FROM c_quiz')
        ), true);
        $targetQuestionIds = array_fill_keys(array_map(
            'intval',
            $this->connection->fetchFirstColumn('SELECT iid FROM c_quiz_question')
        ), true);

        $targetRows = $this->connection->executeQuery(
            'SELECT quiz_id, question_order, question_id
             FROM c_quiz_rel_question
             WHERE quiz_id IN (:quizIds)
             ORDER BY quiz_id ASC, question_order ASC, iid ASC',
            ['quizIds' => $auditQuizIds],
            ['quizIds' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $targetByQuiz = [];
        foreach ($targetRows as $row) {
            $quizId = (int) $row['quiz_id'];
            $targetByQuiz[$quizId][] = [
                'question_order' => (int) $row['question_order'],
                'question_id' => null === $row['question_id'] ? null : (int) $row['question_id'],
            ];
        }

        $quizzes = [];
        $summary = [
            'legacy_relation_rows' => \count($legacyRows),
            'resolved_legacy_relations' => $resolvedLegacyRelations,
            'unresolved_legacy_question_relations' => array_sum($unresolvedSourceByQuiz),
            'unresolved_legacy_quiz_relations' => $unresolvedLegacyQuizRelations,
            'audited_quizzes' => \count($auditQuizIds),
            'matching_relations' => 0,
            'missing_relations' => 0,
            'affected_quizzes' => 0,
            'repairable_quizzes' => 0,
            'unsafe_affected_quizzes' => 0,
            'extra_target_relations' => 0,
            'order_conflicts' => 0,
            'null_target_relations' => 0,
            'duplicate_source_pairs' => array_sum($duplicateSourcePairsByQuiz),
            'duplicate_target_pairs' => 0,
            'missing_target_quizzes' => 0,
            'missing_target_questions' => 0,
        ];

        foreach ($auditQuizIds as $quizId) {
            $sourceRows = $sourceByQuiz[$quizId] ?? [];
            $targetQuizRows = $targetByQuiz[$quizId] ?? [];
            $sourceByQuestion = [];
            $missingTargetQuestions = 0;

            foreach ($sourceRows as $sourceRow) {
                $sourceByQuestion[$sourceRow['question_id']] = $sourceRow['question_order'];
                if (!isset($targetQuestionIds[$sourceRow['question_id']])) {
                    ++$missingTargetQuestions;
                }
            }

            $targetOrdersByQuestion = [];
            $nullTargetRelations = 0;
            $duplicateTargetPairs = 0;
            foreach ($targetQuizRows as $targetRow) {
                if (null === $targetRow['question_id']) {
                    ++$nullTargetRelations;

                    continue;
                }

                $questionId = $targetRow['question_id'];
                if (isset($targetOrdersByQuestion[$questionId])) {
                    ++$duplicateTargetPairs;
                }
                $targetOrdersByQuestion[$questionId][] = $targetRow['question_order'];
            }

            $matching = 0;
            $missing = 0;
            $orderConflicts = 0;
            foreach ($sourceByQuestion as $questionId => $questionOrder) {
                if (!isset($targetOrdersByQuestion[$questionId])) {
                    ++$missing;

                    continue;
                }

                if (!\in_array($questionOrder, $targetOrdersByQuestion[$questionId], true)) {
                    ++$orderConflicts;

                    continue;
                }

                ++$matching;
            }

            $extra = 0;
            foreach ($targetOrdersByQuestion as $questionId => $orders) {
                if (!isset($sourceByQuestion[$questionId])) {
                    $extra += \count($orders);
                }
            }

            $missingTargetQuiz = !isset($targetQuizIds[$quizId]);
            $sourceUnresolved = $unresolvedSourceByQuiz[$quizId] ?? 0;
            $sourceDuplicates = $duplicateSourcePairsByQuiz[$quizId] ?? 0;
            $unsafe = $missingTargetQuiz
                || $missingTargetQuestions > 0
                || $sourceUnresolved > 0
                || $sourceDuplicates > 0
                || $extra > 0
                || $orderConflicts > 0
                || $nullTargetRelations > 0
                || $duplicateTargetPairs > 0;
            $affected = $missing > 0;
            $repairable = $affected && !$unsafe;

            $quizzes[$quizId] = [
                'quiz_id' => $quizId,
                'title' => $quizTitles[$quizId] ?? '',
                'source_relations' => \count($sourceRows),
                'target_relations' => \count($targetQuizRows),
                'matching' => $matching,
                'missing' => $missing,
                'extra' => $extra,
                'order_conflicts' => $orderConflicts,
                'source_unresolved' => $sourceUnresolved,
                'source_duplicates' => $sourceDuplicates,
                'target_null' => $nullTargetRelations,
                'target_duplicates' => $duplicateTargetPairs,
                'missing_target_quiz' => $missingTargetQuiz,
                'missing_target_questions' => $missingTargetQuestions,
                'affected' => $affected,
                'repairable' => $repairable,
            ];

            $summary['matching_relations'] += $matching;
            $summary['missing_relations'] += $missing;
            $summary['extra_target_relations'] += $extra;
            $summary['order_conflicts'] += $orderConflicts;
            $summary['null_target_relations'] += $nullTargetRelations;
            $summary['duplicate_target_pairs'] += $duplicateTargetPairs;
            $summary['missing_target_questions'] += $missingTargetQuestions;
            if ($missingTargetQuiz) {
                ++$summary['missing_target_quizzes'];
            }
            if ($affected) {
                ++$summary['affected_quizzes'];
                if ($repairable) {
                    ++$summary['repairable_quizzes'];
                } else {
                    ++$summary['unsafe_affected_quizzes'];
                }
            }
        }

        return [
            'summary' => $summary,
            'quizzes' => $quizzes,
            'source_rows' => $sourceByQuiz,
        ];
    }

    /**
     * @param array{
     *     summary: array<string, int>,
     *     quizzes: array<int, array<string, int|string|bool>>,
     *     source_rows: array<int, list<array{question_order: int, question_id: int}>>
     * } $audit
     * @param list<int> $quizFilter
     */
    private function printAudit(array $audit, string $sourceDatabase, array $quizFilter, SymfonyStyle $io): void
    {
        $summary = $audit['summary'];

        $io->definitionList(
            ['Source database' => $sourceDatabase],
            ['Scope' => [] === $quizFilter ? 'All legacy quizzes with relations' : 'Quiz iid(s): '.implode(', ', $quizFilter)],
            ['Legacy relation rows scanned' => $summary['legacy_relation_rows']],
            ['Resolved legacy relations' => $summary['resolved_legacy_relations']],
            ['Unresolved legacy question relations' => $summary['unresolved_legacy_question_relations']],
            ['Unresolved legacy quiz relations' => $summary['unresolved_legacy_quiz_relations']],
            ['Audited quizzes' => $summary['audited_quizzes']],
            ['Matching relations' => $summary['matching_relations']],
            ['Missing relations' => $summary['missing_relations']],
            ['Affected quizzes' => $summary['affected_quizzes']],
            ['Repairable affected quizzes' => $summary['repairable_quizzes']],
            ['Unsafe affected quizzes' => $summary['unsafe_affected_quizzes']],
            ['Extra target relations' => $summary['extra_target_relations']],
            ['Order conflicts' => $summary['order_conflicts']],
            ['NULL target relations' => $summary['null_target_relations']],
            ['Duplicate source pairs' => $summary['duplicate_source_pairs']],
            ['Duplicate target pairs' => $summary['duplicate_target_pairs']],
            ['Missing target quizzes' => $summary['missing_target_quizzes']],
            ['Missing target questions' => $summary['missing_target_questions']]
        );

        $detailRows = [];
        foreach ($audit['quizzes'] as $quiz) {
            $hasIssue = (bool) $quiz['affected']
                || (int) $quiz['extra'] > 0
                || (int) $quiz['order_conflicts'] > 0
                || (int) $quiz['source_unresolved'] > 0
                || (int) $quiz['source_duplicates'] > 0
                || (int) $quiz['target_null'] > 0
                || (int) $quiz['target_duplicates'] > 0
                || (bool) $quiz['missing_target_quiz']
                || (int) $quiz['missing_target_questions'] > 0;

            if (!$hasIssue) {
                continue;
            }

            $detailRows[] = [
                $quiz['quiz_id'],
                mb_strimwidth((string) $quiz['title'], 0, 44, '...'),
                $quiz['source_relations'],
                $quiz['target_relations'],
                $quiz['missing'],
                $quiz['extra'],
                $quiz['order_conflicts'],
                $quiz['source_unresolved'],
                $quiz['target_null'],
                true === $quiz['repairable'] ? 'yes' : 'no',
            ];
        }

        if ([] === $detailRows) {
            $io->success('No resolved legacy quiz-question relations are missing.');

            return;
        }

        if (!$io->isVerbose() && \count($detailRows) > self::MAX_DEFAULT_DETAIL_ROWS) {
            $omitted = \count($detailRows) - self::MAX_DEFAULT_DETAIL_ROWS;
            $detailRows = \array_slice($detailRows, 0, self::MAX_DEFAULT_DETAIL_ROWS);
            $io->warning(\sprintf(
                'Showing the first %d quiz(es) with issues; %d additional row(s) omitted. Re-run with -v to show all.',
                self::MAX_DEFAULT_DETAIL_ROWS,
                $omitted
            ));
        }

        $io->section('Quizzes requiring attention');
        $io->table(
            ['Quiz', 'Title', 'Source', 'Target', 'Missing', 'Extra', 'Order', 'Src unresolved', 'Target NULL', 'Repairable'],
            $detailRows
        );
    }

    /**
     * @param list<int> $quizFilter
     */
    private function apply(string $sourceDatabase, array $quizFilter, int $expectedMissing, int $expectedAffected): int
    {
        $this->connection->beginTransaction();

        try {
            $audit = $this->audit($sourceDatabase, $quizFilter);

            if ($expectedMissing !== $audit['summary']['missing_relations']) {
                throw new RuntimeException(\sprintf('Target changed before repair: missing relation count is now %d, expected %d.', $audit['summary']['missing_relations'], $expectedMissing));
            }

            if ($expectedAffected !== $audit['summary']['affected_quizzes']) {
                throw new RuntimeException(\sprintf('Target changed before repair: affected quiz count is now %d, expected %d.', $audit['summary']['affected_quizzes'], $expectedAffected));
            }

            if ($audit['summary']['unsafe_affected_quizzes'] > 0) {
                throw new RuntimeException('Target changed before repair and now contains unsafe affected quizzes.');
            }

            $repairQuizIds = [];
            foreach ($audit['quizzes'] as $quizId => $quiz) {
                if (true === $quiz['repairable']) {
                    $repairQuizIds[] = (int) $quizId;
                }
            }

            if ([] === $repairQuizIds) {
                $this->connection->commit();

                return 0;
            }

            $lockedIds = array_map(
                'intval',
                $this->connection->executeQuery(
                    'SELECT iid FROM c_quiz WHERE iid IN (:quizIds) FOR UPDATE',
                    ['quizIds' => $repairQuizIds],
                    ['quizIds' => ArrayParameterType::INTEGER]
                )->fetchFirstColumn()
            );

            if (\count($lockedIds) !== \count($repairQuizIds)) {
                throw new RuntimeException('Repair refused: one or more target quizzes disappeared before write.');
            }

            $targetQuestionIds = array_fill_keys(array_map(
                'intval',
                $this->connection->fetchFirstColumn('SELECT iid FROM c_quiz_question')
            ), true);

            $inserted = 0;
            foreach ($repairQuizIds as $quizId) {
                $existingPairs = [];
                $rows = $this->connection->fetchAllAssociative(
                    'SELECT question_id FROM c_quiz_rel_question WHERE quiz_id = :quizId',
                    ['quizId' => $quizId],
                    ['quizId' => ParameterType::INTEGER]
                );
                foreach ($rows as $row) {
                    if (null !== $row['question_id']) {
                        $existingPairs[(int) $row['question_id']] = true;
                    }
                }

                foreach ($audit['source_rows'][$quizId] ?? [] as $sourceRow) {
                    $questionId = $sourceRow['question_id'];
                    if (isset($existingPairs[$questionId])) {
                        continue;
                    }

                    if (!isset($targetQuestionIds[$questionId])) {
                        throw new RuntimeException(\sprintf('Repair refused: target question iid=%d disappeared before write.', $questionId));
                    }

                    $this->connection->insert(
                        'c_quiz_rel_question',
                        [
                            'question_order' => $sourceRow['question_order'],
                            'question_id' => $questionId,
                            'quiz_id' => $quizId,
                            'destination' => null,
                        ],
                        [
                            'question_order' => ParameterType::INTEGER,
                            'question_id' => ParameterType::INTEGER,
                            'quiz_id' => ParameterType::INTEGER,
                        ]
                    );
                    $existingPairs[$questionId] = true;
                    ++$inserted;
                }
            }

            $verification = $this->audit($sourceDatabase, $quizFilter);
            if ($verification['summary']['missing_relations'] > 0) {
                throw new RuntimeException(\sprintf('Verification failed after inserts: %d resolved legacy relation(s) are still missing.', $verification['summary']['missing_relations']));
            }

            $this->connection->commit();

            return $inserted;
        } catch (Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw $e;
        }
    }

    private function requiredExpectedCount(InputInterface $input, string $option): int
    {
        $value = $input->getOption($option);
        if (null === $value || '' === (string) $value || !ctype_digit((string) $value)) {
            throw new RuntimeException(\sprintf('--%s is required with --apply and must be a non-negative integer.', $option));
        }

        return (int) $value;
    }

    private function assertSourceDatabaseIsUsable(string $database): void
    {
        $targetDatabase = (string) $this->connection->fetchOne('SELECT DATABASE()');
        if ($database === $targetDatabase) {
            throw new RuntimeException('Source and target databases must be different.');
        }

        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :database',
            ['database' => $database]
        );

        if (1 !== $exists) {
            throw new RuntimeException(\sprintf('Source database %s does not exist or is not visible to this DB user.', $database));
        }

        $this->assertColumns($database, [
            'c_quiz' => ['iid', 'id', 'c_id', 'title'],
            'c_quiz_question' => ['iid', 'id', 'c_id'],
            'c_quiz_rel_question' => ['iid', 'c_id', 'question_order', 'question_id', 'exercice_id'],
        ], 'legacy source');

        $this->assertTargetDatabaseIsUsable();
    }

    private function assertTargetDatabaseIsUsable(): void
    {
        $targetDatabase = (string) $this->connection->fetchOne('SELECT DATABASE()');
        if ('' === $targetDatabase) {
            throw new RuntimeException('No target database is selected by the Chamilo connection.');
        }

        $this->assertColumns($targetDatabase, [
            'c_quiz' => ['iid'],
            'c_quiz_question' => ['iid'],
            'c_quiz_rel_question' => ['iid', 'question_order', 'question_id', 'quiz_id', 'destination'],
        ], 'target');
    }

    /**
     * @param array<string, list<string>> $required
     */
    private function assertColumns(string $database, array $required, string $label): void
    {
        foreach ($required as $table => $columns) {
            $existing = array_map(
                'strval',
                $this->connection->fetchFirstColumn(
                    'SELECT COLUMN_NAME
                     FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = :database
                       AND TABLE_NAME = :table',
                    ['database' => $database, 'table' => $table]
                )
            );
            $existingMap = array_fill_keys($existing, true);

            foreach ($columns as $column) {
                if (!isset($existingMap[$column])) {
                    throw new RuntimeException(\sprintf('%s database %s is missing %s.%s.', ucfirst($label), $database, $table, $column));
                }
            }
        }
    }
}
