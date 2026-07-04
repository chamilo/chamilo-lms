<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use JsonException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'chamilo:migration:audit-ricky-completion-rules',
    description: 'Audit Ricky completion references and separate verified exercise mappings from review-required candidates without changing data.'
)]
final class AuditRickyCompletionRulesCommand extends Command
{
    private const LEGACY_SOURCE_SHA256 = '20d36aeea40353265e15cdc4a07128108c98db18bc64b8e5bc8d52a080bc9436';


    private const EXERCISE_RULE_FIELD_VARIABLE = 'final_exam_access_rule';

    /**
     * Semantic hints extracted from the legacy score formulas. They are used
     * only to audit candidates; they never change data or runtime scoring.
     *
     * @var array<string, array<int, array{role: string, weight: float}>>
     */
    private const LEGACY_EXERCISE_HINTS = [
        '1301' => [
            17 => ['role' => 'midterm', 'weight' => 0.20],
            22 => ['role' => 'assessment', 'weight' => 0.30],
            745 => ['role' => 'final_exam', 'weight' => 0.30],
        ],
        '1302' => [
            18 => ['role' => 'midterm', 'weight' => 0.20],
            30 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '1505' => [
            1 => ['role' => 'midterm', 'weight' => 0.30],
            3 => ['role' => 'final_exam', 'weight' => 0.30],
        ],
        '1510' => [
            5 => ['role' => 'midterm', 'weight' => 0.20],
            4 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '1540' => [
            1 => ['role' => 'midterm', 'weight' => 0.20],
            2 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '1740' => [
            17 => ['role' => 'midterm', 'weight' => 0.20],
            14 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '1810' => [
            753 => ['role' => 'midterm', 'weight' => 0.20],
            755 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '2120' => [
            5 => ['role' => 'midterm', 'weight' => 0.20],
            7 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '2521' => [
            2 => ['role' => 'midterm', 'weight' => 0.20],
            3 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '2706' => [
            2 => ['role' => 'midterm', 'weight' => 0.20],
            4 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '2720' => [
            1 => ['role' => 'midterm', 'weight' => 0.20],
            2 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '2741' => [
            1 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '2770' => [
            9 => ['role' => 'midterm', 'weight' => 0.20],
            6 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '2811' => [
            758 => ['role' => 'midterm', 'weight' => 0.20],
            757 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '6741' => [
            25 => ['role' => 'midterm', 'weight' => 0.20],
            484 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '6742' => [
            63 => ['role' => 'midterm', 'weight' => 0.20],
            65 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '9516' => [
            3 => ['role' => 'midterm', 'weight' => 0.20],
            4 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        '9641' => [
            25 => ['role' => 'midterm', 'weight' => 0.20],
            29 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        'COURSEDELIVERY' => [
            827 => ['role' => 'midterm', 'weight' => 0.20],
            829 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        'COURSEDESIGN' => [
            830 => ['role' => 'midterm', 'weight' => 0.20],
            831 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        'NFPA' => [
            3 => ['role' => 'midterm', 'weight' => 0.30],
            7 => ['role' => 'final_exam', 'weight' => 0.30],
        ],
        'PUMPERDRIVEROPERATOR' => [
            509 => ['role' => 'assessment', 'weight' => 0.20],
            512 => ['role' => 'final_exam', 'weight' => 0.20],
            513 => ['role' => 'assessment', 'weight' => 0.20],
        ],
        'TNFI1' => [
            628 => ['role' => 'midterm', 'weight' => 0.20],
            630 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        'TNFO1' => [
            514 => ['role' => 'midterm', 'weight' => 0.20],
            515 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        'TNFO1FO2' => [
            1 => ['role' => 'midterm', 'weight' => 0.20],
            2 => ['role' => 'final_exam', 'weight' => 0.20],
        ],
        'TNFO2' => [
            5 => ['role' => 'assessment', 'weight' => 0.20],
            1 => ['role' => 'assessment', 'weight' => 0.20],
        ],
    ];

    /** @var list<string> */
    private const FLATVIEW_ONLY_COURSE_CODES = [
        '1510C',
        'CROWDMANAGERTRAINING',
        'EMERGENCYVEHICLEOPERATORCOURSE',
        'NFPA2018',
        'NFPA2024',
    ];

    /**
     * Reference inventory extracted from both legacy methods:
     * Category::userFinishedCourse() and Category::userFinishedScore().
     * Their resource-reference inventories matched for all 44 course codes.
     *
     * @var list<array{
     *     code: string,
     *     legacy_course_id: int,
     *     forum_thread_ids: list<int>,
     *     work_ids: list<int>,
     *     evaluation_ids: list<int>,
     *     exercise_ids: list<int>
     * }>
     */
    private const LEGACY_RULES = [
        [
            'code' => '1301',
            'legacy_course_id' => 11,
            'forum_thread_ids' => [161, 162, 163, 164, 165, 166],
            'work_ids' => [],
            'evaluation_ids' => [],
            'exercise_ids' => [17, 22, 745],
        ],
        [
            'code' => '1302',
            'legacy_course_id' => 20,
            'forum_thread_ids' => [201, 202, 203, 204, 205, 206, 207, 208, 209],
            'work_ids' => [],
            'evaluation_ids' => [2],
            'exercise_ids' => [18, 30],
        ],
        [
            'code' => '1505',
            'legacy_course_id' => 8,
            'forum_thread_ids' => [88, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101],
            'work_ids' => [],
            'evaluation_ids' => [],
            'exercise_ids' => [1, 3, 707, 708, 709, 710],
        ],
        [
            'code' => '1510',
            'legacy_course_id' => 6,
            'forum_thread_ids' => [102, 103, 104, 105, 106, 107, 108, 109],
            'work_ids' => [12],
            'evaluation_ids' => [],
            'exercise_ids' => [4, 5],
        ],
        [
            'code' => '1540',
            'legacy_course_id' => 13,
            'forum_thread_ids' => [52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65],
            'work_ids' => [29],
            'evaluation_ids' => [],
            'exercise_ids' => [1, 2],
        ],
        [
            'code' => '1740',
            'legacy_course_id' => 15,
            'forum_thread_ids' => [211, 212, 213, 214, 215, 216, 217, 218],
            'work_ids' => [20, 22],
            'evaluation_ids' => [],
            'exercise_ids' => [14, 17],
        ],
        [
            'code' => '17402021',
            'legacy_course_id' => 87,
            'forum_thread_ids' => [769, 770, 772, 773, 774, 775, 776, 777, 778, 779],
            'work_ids' => [39389, 39390, 39391, 39392],
            'evaluation_ids' => [],
            'exercise_ids' => [1188, 1191],
        ],
        [
            'code' => '1810',
            'legacy_course_id' => 9,
            'forum_thread_ids' => [189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200],
            'work_ids' => [23],
            'evaluation_ids' => [],
            'exercise_ids' => [753, 755],
        ],
        [
            'code' => '2111',
            'legacy_course_id' => 32,
            'forum_thread_ids' => [571, 572, 573, 574, 577, 578, 579, 580, 581, 582, 711, 712],
            'work_ids' => [16088],
            'evaluation_ids' => [],
            'exercise_ids' => [866, 868],
        ],
        [
            'code' => '2120',
            'legacy_course_id' => 1,
            'forum_thread_ids' => [5, 2, 6, 7, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18],
            'work_ids' => [1],
            'evaluation_ids' => [],
            'exercise_ids' => [5, 7],
        ],
        [
            'code' => '2521',
            'legacy_course_id' => 5,
            'forum_thread_ids' => [50, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152],
            'work_ids' => [14],
            'evaluation_ids' => [],
            'exercise_ids' => [2, 3],
        ],
        [
            'code' => '2521C',
            'legacy_course_id' => 82,
            'forum_thread_ids' => [],
            'work_ids' => [71261],
            'evaluation_ids' => [36],
            'exercise_ids' => [1408, 1409],
        ],
        [
            'code' => '2541',
            'legacy_course_id' => 74,
            'forum_thread_ids' => [714, 715, 716, 717, 718, 720, 721, 722, 723, 724, 725, 726, 727, 728, 729],
            'work_ids' => [16801],
            'evaluation_ids' => [],
            'exercise_ids' => [1036, 1038],
        ],
        [
            'code' => '2610',
            'legacy_course_id' => 76,
            'forum_thread_ids' => [],
            'work_ids' => [],
            'evaluation_ids' => [9],
            'exercise_ids' => [],
        ],
        [
            'code' => '2706',
            'legacy_course_id' => 14,
            'forum_thread_ids' => [167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177],
            'work_ids' => [24628],
            'evaluation_ids' => [],
            'exercise_ids' => [2, 4],
        ],
        [
            'code' => '2720',
            'legacy_course_id' => 3,
            'forum_thread_ids' => [3, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37],
            'work_ids' => [6, 11],
            'evaluation_ids' => [],
            'exercise_ids' => [1, 2],
        ],
        [
            'code' => '2741',
            'legacy_course_id' => 16,
            'forum_thread_ids' => [219, 220, 221, 222, 223],
            'work_ids' => [21],
            'evaluation_ids' => [],
            'exercise_ids' => [1],
        ],
        [
            'code' => '27412021',
            'legacy_course_id' => 92,
            'forum_thread_ids' => [781, 782, 783, 784, 785],
            'work_ids' => [42892, 42893],
            'evaluation_ids' => [],
            'exercise_ids' => [1213],
        ],
        [
            'code' => '2770',
            'legacy_course_id' => 7,
            'forum_thread_ids' => [126, 127, 128, 129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140],
            'work_ids' => [17],
            'evaluation_ids' => [],
            'exercise_ids' => [6, 9],
        ],
        [
            'code' => '2811',
            'legacy_course_id' => 10,
            'forum_thread_ids' => [178, 179, 180, 181, 182, 183, 184, 185, 186, 187],
            'work_ids' => [24],
            'evaluation_ids' => [],
            'exercise_ids' => [757, 758],
        ],
        [
            'code' => '6741',
            'legacy_course_id' => 19,
            'forum_thread_ids' => [225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237],
            'work_ids' => [25, 26],
            'evaluation_ids' => [],
            'exercise_ids' => [25, 484],
        ],
        [
            'code' => '6742',
            'legacy_course_id' => 17,
            'forum_thread_ids' => [71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87],
            'work_ids' => [27, 28],
            'evaluation_ids' => [],
            'exercise_ids' => [63, 65],
        ],
        [
            'code' => '703',
            'legacy_course_id' => 49,
            'forum_thread_ids' => [586, 587, 588, 589, 590, 591, 592, 593, 594, 595],
            'work_ids' => [],
            'evaluation_ids' => [7],
            'exercise_ids' => [759, 760, 761, 762, 763],
        ],
        [
            'code' => '7529',
            'legacy_course_id' => 69,
            'forum_thread_ids' => [733, 734, 736, 737, 738],
            'work_ids' => [20869, 20870],
            'evaluation_ids' => [],
            'exercise_ids' => [1062, 1064],
        ],
        [
            'code' => '9516',
            'legacy_course_id' => 2,
            'forum_thread_ids' => [38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49],
            'work_ids' => [8],
            'evaluation_ids' => [],
            'exercise_ids' => [3, 4],
        ],
        [
            'code' => '9641',
            'legacy_course_id' => 31,
            'forum_thread_ids' => [240, 241, 242, 243, 244],
            'work_ids' => [31],
            'evaluation_ids' => [],
            'exercise_ids' => [25, 29],
        ],
        [
            'code' => 'AERIALDRIVEROPERATOR',
            'legacy_course_id' => 39,
            'forum_thread_ids' => [504, 505, 506, 507, 508, 677, 678, 679, 680, 681],
            'work_ids' => [761],
            'evaluation_ids' => [],
            'exercise_ids' => [778, 779, 780, 781, 782],
        ],
        [
            'code' => 'COURSEDELIVERY',
            'legacy_course_id' => 52,
            'forum_thread_ids' => [614, 615, 616, 617, 619, 620, 621, 622],
            'work_ids' => [1620, 1621],
            'evaluation_ids' => [],
            'exercise_ids' => [827, 829],
        ],
        [
            'code' => 'COURSEDESIGN',
            'legacy_course_id' => 53,
            'forum_thread_ids' => [664, 665, 666, 667, 668, 669, 670, 671, 672, 673, 674, 675],
            'work_ids' => [1720],
            'evaluation_ids' => [],
            'exercise_ids' => [830, 831],
        ],
        [
            'code' => 'FIREINSPECTOR',
            'legacy_course_id' => 41,
            'forum_thread_ids' => [597, 598, 599, 600, 601, 602, 603, 604, 605, 606, 607, 608, 609, 610, 611],
            'work_ids' => [1455],
            'evaluation_ids' => [],
            'exercise_ids' => [816, 817],
        ],
        [
            'code' => 'FIRESERVICEPROGRAMMANAGER',
            'legacy_course_id' => 80,
            'forum_thread_ids' => [756, 757, 758, 759],
            'work_ids' => [32647],
            'evaluation_ids' => [],
            'exercise_ids' => [],
        ],
        [
            'code' => 'NFPA',
            'legacy_course_id' => 12,
            'forum_thread_ids' => [67, 68, 69, 70],
            'work_ids' => [],
            'evaluation_ids' => [],
            'exercise_ids' => [3, 7],
        ],
        [
            'code' => 'NFPA2021',
            'legacy_course_id' => 77,
            'forum_thread_ids' => [],
            'work_ids' => [],
            'evaluation_ids' => [],
            'exercise_ids' => [1124, 1128, 1129, 1131, 1133],
        ],
        [
            'code' => 'PUMPERDRIVEROPERATOR',
            'legacy_course_id' => 38,
            'forum_thread_ids' => [397, 398, 399, 400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 465],
            'work_ids' => [35],
            'evaluation_ids' => [],
            'exercise_ids' => [509, 512, 513],
        ],
        [
            'code' => 'RN3842',
            'legacy_course_id' => 93,
            'forum_thread_ids' => [787, 788, 789, 790],
            'work_ids' => [],
            'evaluation_ids' => [37],
            'exercise_ids' => [1215],
        ],
        [
            'code' => 'SERP',
            'legacy_course_id' => 43,
            'forum_thread_ids' => [638, 641, 642, 643, 644, 645, 646, 647, 648, 649],
            'work_ids' => [],
            'evaluation_ids' => [6],
            'exercise_ids' => [],
        ],
        [
            'code' => 'TN1810',
            'legacy_course_id' => 44,
            'forum_thread_ids' => [540, 542, 545, 546, 548, 550],
            'work_ids' => [],
            'evaluation_ids' => [],
            'exercise_ids' => [697, 699, 701, 703, 706, 744],
        ],
        [
            'code' => 'TNFDISO',
            'legacy_course_id' => 30,
            'forum_thread_ids' => [327, 328, 329, 330, 331, 332, 333, 334, 335, 336, 337, 338, 339, 340, 341, 342, 343],
            'work_ids' => [38, 1242],
            'evaluation_ids' => [],
            'exercise_ids' => [533, 534],
        ],
        [
            'code' => 'TNFI1',
            'legacy_course_id' => 28,
            'forum_thread_ids' => [303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314],
            'work_ids' => [41],
            'evaluation_ids' => [],
            'exercise_ids' => [628, 630],
        ],
        [
            'code' => 'TNFI2',
            'legacy_course_id' => 29,
            'forum_thread_ids' => [315, 316, 317, 318, 319, 320, 321, 322, 323, 324, 325, 326],
            'work_ids' => [42],
            'evaluation_ids' => [],
            'exercise_ids' => [652, 653],
        ],
        [
            'code' => 'TNFO1',
            'legacy_course_id' => 23,
            'forum_thread_ids' => [259, 260, 261, 262, 263, 466, 467, 468, 469, 470, 471, 477, 478, 479, 480, 481, 482, 483, 484],
            'work_ids' => [36, 37],
            'evaluation_ids' => [],
            'exercise_ids' => [514, 515],
        ],
        [
            'code' => 'TNFO1FO2',
            'legacy_course_id' => 26,
            'forum_thread_ids' => [344, 345, 346, 347, 348, 349, 350, 351, 352, 353, 354, 355, 356, 357, 358, 359, 360, 361, 362, 363, 364, 365, 366, 367, 368, 369, 370, 371, 372, 373, 374, 375, 376, 377, 378, 379, 380, 381],
            'work_ids' => [112, 113, 114, 115],
            'evaluation_ids' => [],
            'exercise_ids' => [1, 2],
        ],
        [
            'code' => 'TNFO2',
            'legacy_course_id' => 24,
            'forum_thread_ids' => [264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280],
            'work_ids' => [358, 359],
            'evaluation_ids' => [],
            'exercise_ids' => [1, 5],
        ],
        [
            'code' => 'TNFO3',
            'legacy_course_id' => 27,
            'forum_thread_ids' => [509, 512, 513, 514, 515, 516, 520, 521, 522, 523, 524],
            'work_ids' => [40],
            'evaluation_ids' => [],
            'exercise_ids' => [615, 616],
        ],
    ];

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'course-code',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Limits the audit to one or more Ricky course codes.'
            )
            ->addOption(
                'output',
                null,
                InputOption::VALUE_REQUIRED,
                'Optional absolute path for the JSON audit report.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Audit Ricky completion rules');

        $requestedCodes = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            (array) $input->getOption('course-code')
        ))));

        $knownCodes = array_map(
            static fn (array $rule): string => $rule['code'],
            self::LEGACY_RULES
        );
        $unknownCodes = array_values(array_diff($requestedCodes, $knownCodes));
        if ([] !== $unknownCodes) {
            $io->error('Unknown Ricky course code(s): '.implode(', ', $unknownCodes));

            return Command::INVALID;
        }

        try {
            $tableNames = array_map(
                static fn (string $tableName): string => strtolower($tableName),
                $this->connection->createSchemaManager()->listTableNames()
            );
            $this->assertRequiredTables($tableNames);

            $hasTrackingBackup = $this->hasUsableTrackingBackup($tableNames);
            $selectedRules = array_values(array_filter(
                self::LEGACY_RULES,
                static fn (array $rule): bool => [] === $requestedCodes
                    || in_array($rule['code'], $requestedCodes, true)
            ));

            $report = [
                'generated_at' => gmdate('c'),
                'audit_version' => 4,
                'legacy_source_sha256' => self::LEGACY_SOURCE_SHA256,
                'selected_rule_count' => count($selectedRules),
                'tracking_backup_available' => $hasTrackingBackup,
                'summary' => $this->newSummary(),
                'courses' => [],
                'flatview_only_rules' => $this->auditFlatviewOnlyRules(),
            ];

            foreach ($selectedRules as $rule) {
                $courseReport = $this->auditCourse($rule, $hasTrackingBackup);
                $report['courses'][] = $courseReport;
                $this->addToSummary($report['summary'], $courseReport);

                if ($output->isVerbose()) {
                    $this->renderCourseReport($io, $courseReport);
                }
            }

            $this->addFlatviewOnlySummary($report['summary'], $report['flatview_only_rules']);
            $report['verified_exercise_mappings'] = $this->collectExerciseMappings(
                $report['courses'],
                true
            );
            $report['review_required_exercises'] = $this->collectExerciseMappings(
                $report['courses'],
                false
            );
            $this->renderSummary($io, $report['summary'], $hasTrackingBackup);
            if ($report['summary']['exercise_review_required'] > 0) {
                $io->warning(sprintf(
                    '%d exercise reference(s) still require manual confirmation. Suggested IDs are audit hints only and must not be used by runtime scoring.',
                    $report['summary']['exercise_review_required']
                ));
            }
            $this->renderFlatviewOnlyRules($io, $report['flatview_only_rules']);
            $this->writeJsonReport($input, $io, $report);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success('Read-only Ricky completion-rule audit completed.');

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $tableNames
     */
    private function assertRequiredTables(array $tableNames): void
    {
        $required = [
            'course',
            'c_forum_thread',
            'c_student_publication',
            'c_quiz',
            'c_lp',
            'c_lp_item',
            'extra_field',
            'extra_field_values',
            'gradebook_evaluation',
            'gradebook_link',
            'resource_link',
            'track_e_exercises',
        ];

        foreach ($required as $tableName) {
            if (!in_array($tableName, $tableNames, true)) {
                throw new RuntimeException("Required table '{$tableName}' is missing.");
            }
        }
    }

    /**
     * @param list<string> $tableNames
     */
    private function hasUsableTrackingBackup(array $tableNames): bool
    {
        if (!in_array('track_e_exercises_backup', $tableNames, true)) {
            return false;
        }

        $indexes = $this->connection->createSchemaManager()->listTableIndexes('track_e_exercises_backup');
        foreach ($indexes as $index) {
            $columns = array_map('strtolower', $index->getColumns());
            if ([] !== $columns && 'c_id' === $columns[0]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, int>
     */
    private function newSummary(): array
    {
        return [
            'courses_selected' => 0,
            'courses_found' => 0,
            'courses_missing' => 0,
            'legacy_course_id_mismatches' => 0,
            'forum_thread_references' => 0,
            'forum_thread_resolved' => 0,
            'forum_thread_missing' => 0,
            'work_references' => 0,
            'work_resolved' => 0,
            'work_missing' => 0,
            'evaluation_references' => 0,
            'evaluation_resolved' => 0,
            'evaluation_missing' => 0,
            'exercise_references' => 0,
            'exercise_resolved_from_history' => 0,
            'exercise_resolved_from_final_exam_rule' => 0,
            'exercise_verified_direct_with_context' => 0,
            'exercise_resolved_from_course_sequence_anchor' => 0,
            'exercise_candidate_role_match' => 0,
            'exercise_candidate_display_order' => 0,
            'exercise_ambiguous_candidates' => 0,
            'exercise_unresolved' => 0,
            'exercise_verified_references' => 0,
            'exercise_review_required' => 0,
            'flatview_only_rules' => 0,
            'flatview_only_courses_found' => 0,
            'flatview_only_courses_missing' => 0,
        ];
    }

    /**
     * @param array{
     *     code: string,
     *     legacy_course_id: int,
     *     forum_thread_ids: list<int>,
     *     work_ids: list<int>,
     *     evaluation_ids: list<int>,
     *     exercise_ids: list<int>
     * } $rule
     *
     * @return array<string, mixed>
     */
    private function auditCourse(array $rule, bool $hasTrackingBackup): array
    {
        $course = $this->connection->fetchAssociative(
            'SELECT id, code, title FROM course WHERE code = :code LIMIT 1',
            ['code' => $rule['code']]
        );

        if (false === $course) {
            return [
                'code' => $rule['code'],
                'legacy_course_id' => $rule['legacy_course_id'],
                'status' => 'missing_course',
                'course_id' => null,
                'course_title' => null,
                'legacy_course_id_matches' => false,
                'forum_threads' => $this->missingResourceAudit($rule['forum_thread_ids']),
                'works' => $this->missingResourceAudit($rule['work_ids']),
                'evaluations' => $this->missingResourceAudit($rule['evaluation_ids']),
                'exercises' => $this->missingExerciseAudit($rule['exercise_ids']),
            ];
        }

        $courseId = (int) $course['id'];

        return [
            'code' => $rule['code'],
            'legacy_course_id' => $rule['legacy_course_id'],
            'status' => 'found',
            'course_id' => $courseId,
            'course_title' => (string) $course['title'],
            'legacy_course_id_matches' => $courseId === $rule['legacy_course_id'],
            'forum_threads' => $this->auditLinkedResourceIds(
                'c_forum_thread',
                'iid',
                $courseId,
                $rule['forum_thread_ids']
            ),
            'works' => $this->auditLinkedResourceIds(
                'c_student_publication',
                'iid',
                $courseId,
                $rule['work_ids']
            ),
            'evaluations' => $this->auditEvaluationIds($courseId, $rule['evaluation_ids']),
            'exercises' => $this->auditExerciseIds(
                $rule['code'],
                $courseId,
                $rule['exercise_ids'],
                $hasTrackingBackup
            ),
        ];
    }

    /**
     * @param list<int> $ids
     *
     * @return array{total: int, resolved: int, missing: int, resolved_ids: list<int>, missing_ids: list<int>}
     */
    private function auditLinkedResourceIds(string $tableName, string $idColumn, int $courseId, array $ids): array
    {
        if ([] === $ids) {
            return $this->emptyResourceAudit();
        }

        $sql = sprintf(
            <<<'SQL'
SELECT DISTINCT entity.%s AS resource_id
FROM %s entity
INNER JOIN resource_link link
    ON link.resource_node_id = entity.resource_node_id
   AND link.c_id = :courseId
   AND link.deleted_at IS NULL
   AND link.session_id IS NULL
   AND link.usergroup_id IS NULL
   AND link.group_id IS NULL
   AND link.user_id IS NULL
WHERE entity.%s IN (:ids)
ORDER BY entity.%s
SQL,
            $idColumn,
            $tableName,
            $idColumn,
            $idColumn
        );

        $resolvedIds = array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                $sql,
                ['courseId' => $courseId, 'ids' => $ids],
                ['ids' => ArrayParameterType::INTEGER]
            )
        );
        sort($resolvedIds);
        $missingIds = array_values(array_diff($ids, $resolvedIds));
        sort($missingIds);

        return [
            'total' => count($ids),
            'resolved' => count($resolvedIds),
            'missing' => count($missingIds),
            'resolved_ids' => $resolvedIds,
            'missing_ids' => $missingIds,
        ];
    }

    /**
     * @param list<int> $ids
     *
     * @return array{total: int, resolved: int, missing: int, resolved_ids: list<int>, missing_ids: list<int>}
     */
    private function auditEvaluationIds(int $courseId, array $ids): array
    {
        if ([] === $ids) {
            return $this->emptyResourceAudit();
        }

        $resolvedIds = array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                <<<'SQL'
SELECT id
FROM gradebook_evaluation
WHERE c_id = :courseId
  AND id IN (:ids)
ORDER BY id
SQL,
                ['courseId' => $courseId, 'ids' => $ids],
                ['ids' => ArrayParameterType::INTEGER]
            )
        );
        sort($resolvedIds);
        $missingIds = array_values(array_diff($ids, $resolvedIds));
        sort($missingIds);

        return [
            'total' => count($ids),
            'resolved' => count($resolvedIds),
            'missing' => count($missingIds),
            'resolved_ids' => $resolvedIds,
            'missing_ids' => $missingIds,
        ];
    }

    /**
     * @param list<int> $legacyIds
     *
     * @return array<string, mixed>
     */
    private function auditExerciseIds(
        string $courseCode,
        int $courseId,
        array $legacyIds,
        bool $hasTrackingBackup
    ): array {
        if ([] === $legacyIds) {
            return $this->emptyExerciseAudit();
        }

        $courseCandidates = $this->findCourseExerciseCandidates($courseId);
        $candidateById = [];
        foreach ($courseCandidates as $candidate) {
            $candidateById[(int) $candidate['exercise_id']] = $candidate;
        }

        $finalExamIds = $this->findConfiguredFinalExamIds($courseId);
        $historicalMappings = $this->findHistoricalExerciseMappings(
            $courseId,
            $legacyIds,
            $hasTrackingBackup
        );
        $courseSequenceAnchorOffset = $this->findCourseSequenceAnchorOffset(
            $courseCode,
            $legacyIds,
            $candidateById,
            $finalExamIds
        );

        $details = [];
        $counters = [
            'resolved_from_history' => 0,
            'resolved_from_final_exam_rule' => 0,
            'verified_direct_with_context' => 0,
            'resolved_from_course_sequence_anchor' => 0,
            'candidate_role_match' => 0,
            'candidate_display_order' => 0,
            'ambiguous_candidates' => 0,
            'unresolved' => 0,
        ];

        foreach ($legacyIds as $legacyId) {
            $hint = self::LEGACY_EXERCISE_HINTS[$courseCode][$legacyId] ?? [
                'role' => 'assessment',
                'weight' => 0.0,
            ];
            $legacyRole = (string) $hint['role'];
            $legacyWeight = (float) $hint['weight'];

            $mappings = array_values(array_filter(
                $historicalMappings[$legacyId] ?? [],
                static fn (array $mapping): bool => true === $mapping['linked_to_course']
                    && null !== $mapping['current_exercise_id']
            ));
            $historicalIds = array_values(array_unique(array_map(
                static fn (array $mapping): int => (int) $mapping['current_exercise_id'],
                $mappings
            )));
            sort($historicalIds);

            $directCandidate = $candidateById[$legacyId] ?? null;
            $selectedId = null;
            $suggestedId = null;
            $strategy = null;
            $confidence = 'none';
            $status = 'unresolved';
            $evidence = [];

            if (1 === count($historicalIds)) {
                $selectedId = $historicalIds[0];
                $strategy = 'tracking_history';
                $confidence = 'high';
                $status = 'resolved_from_history';
                $evidence[] = 'unique_backup_to_current_attempt_mapping';
            } elseif (count($historicalIds) > 1) {
                $status = 'ambiguous_candidates';
                $strategy = 'tracking_history';
                $confidence = 'none';
                $evidence[] = 'multiple_backup_to_current_attempt_mappings';
            } elseif ('final_exam' === $legacyRole && 1 === count($finalExamIds)) {
                $selectedId = $finalExamIds[0];
                $strategy = 'final_exam_access_rule';
                $confidence = 'high';
                $status = 'resolved_from_final_exam_rule';
                $evidence[] = 'exercise_is_configured_by_final_exam_access_rule';
            } elseif (null !== $directCandidate) {
                $directEvidence = $this->candidateEvidence(
                    $directCandidate,
                    $legacyId,
                    $legacyRole
                );
                $roleConflict = in_array('role_title_conflict', $directEvidence, true);
                $contextEvidenceCount = count(array_intersect(
                    $directEvidence,
                    [
                        'used_by_learning_path',
                        'has_course_attempts',
                        'has_gradebook_link',
                        'has_questions',
                        'role_title_match',
                    ]
                ));

                if (!$roleConflict && $contextEvidenceCount >= 2) {
                    $selectedId = $legacyId;
                    $strategy = 'direct_id_with_context';
                    $confidence = 'medium';
                    $status = 'verified_direct_with_context';
                    $evidence = $directEvidence;
                }
            }

            if (
                'unresolved' === $status
                && null !== $courseSequenceAnchorOffset
                && in_array($legacyRole, ['midterm', 'final_exam'], true)
            ) {
                $targetDisplayOrder = $legacyId - 1 + $courseSequenceAnchorOffset;
                $sequenceMatches = array_values(array_filter(
                    $courseCandidates,
                    static fn (array $candidate): bool => (int) $candidate['display_order'] === $targetDisplayOrder
                ));

                if (1 === count($sequenceMatches)) {
                    $sequenceCandidate = $sequenceMatches[0];
                    $sequenceEvidence = $this->candidateEvidence(
                        $sequenceCandidate,
                        $legacyId,
                        $legacyRole
                    );
                    $roleConflict = in_array('role_title_conflict', $sequenceEvidence, true);
                    $roleMatch = in_array('role_title_match', $sequenceEvidence, true);

                    if (!$roleConflict && $roleMatch) {
                        $selectedId = (int) $sequenceCandidate['exercise_id'];
                        $strategy = 'verified_final_exam_display_offset';
                        $confidence = 'medium';
                        $status = 'resolved_from_course_sequence_anchor';
                        $evidence = $sequenceEvidence;
                        $evidence[] = 'same_course_verified_final_exam_offset';
                        $evidence[] = 'display_order_offset_match';
                    }
                }
            }

            $rankedCandidates = $this->rankExerciseCandidates(
                $courseCandidates,
                $legacyId,
                $legacyRole,
                $finalExamIds
            );

            if ('unresolved' === $status) {
                $roleMatches = array_values(array_filter(
                    $rankedCandidates,
                    static fn (array $candidate): bool => in_array(
                        'role_title_match',
                        $candidate['evidence'],
                        true
                    )
                ));
                if (1 === count($roleMatches)) {
                    $suggestedId = (int) $roleMatches[0]['exercise_id'];
                    $strategy = 'unique_role_title_match';
                    $confidence = 'medium';
                    $status = 'candidate_role_match';
                    $evidence = $roleMatches[0]['evidence'];
                    $evidence[] = 'manual_confirmation_required';
                } else {
                    $displayOrderMatches = array_values(array_filter(
                        $rankedCandidates,
                        static fn (array $candidate): bool => in_array(
                            'legacy_display_order_match',
                            $candidate['evidence'],
                            true
                        )
                    ));
                    if (1 === count($displayOrderMatches)) {
                        $displayEvidence = $displayOrderMatches[0]['evidence'];
                        $suggestedId = (int) $displayOrderMatches[0]['exercise_id'];
                        $strategy = 'unique_legacy_display_order';

                        if (in_array('role_title_conflict', $displayEvidence, true)) {
                            $confidence = 'none';
                            $status = 'unresolved';
                            $evidence = $displayEvidence;
                            $evidence[] = 'display_order_candidate_rejected_due_role_conflict';
                        } else {
                            $confidence = 'low';
                            $status = 'candidate_display_order';
                            $evidence = $displayEvidence;
                            $evidence[] = 'manual_confirmation_required';
                        }
                    } elseif ([] !== $rankedCandidates) {
                        $status = 'ambiguous_candidates';
                        $strategy = 'candidate_ranking';
                        $confidence = 'none';
                        $evidence[] = 'multiple_or_unverified_course_candidates';
                    }
                }
            }

            ++$counters[$status];

            $details[] = [
                'legacy_exercise_id' => $legacyId,
                'legacy_role' => $legacyRole,
                'legacy_weight' => $legacyWeight,
                'status' => $status,
                'confidence' => $confidence,
                'selection_strategy' => $strategy,
                'selected_current_exercise_id' => $selectedId,
                'suggested_current_exercise_id' => $suggestedId,
                'evidence' => array_values(array_unique($evidence)),
                'direct_current_id_match' => null !== $directCandidate,
                'historical_current_ids' => $historicalIds,
                'historical_mappings' => $mappings,
                'configured_final_exam_ids' => $finalExamIds,
                'candidates' => array_slice($rankedCandidates, 0, 12),
            ];
        }

        return [
            'total' => count($legacyIds),
            ...$counters,
            'details' => $details,
        ];
    }

    /**
     * @param list<int>                  $legacyIds
     * @param array<int, array<string, mixed>> $candidateById
     * @param list<int>                  $finalExamIds
     */
    private function findCourseSequenceAnchorOffset(
        string $courseCode,
        array $legacyIds,
        array $candidateById,
        array $finalExamIds
    ): ?int {
        if (1 !== count($finalExamIds)) {
            return null;
        }

        $configuredCandidate = $candidateById[$finalExamIds[0]] ?? null;
        if (null === $configuredCandidate) {
            return null;
        }

        $offsets = [];
        foreach ($legacyIds as $legacyId) {
            $hint = self::LEGACY_EXERCISE_HINTS[$courseCode][$legacyId] ?? null;
            if (null === $hint || 'final_exam' !== (string) $hint['role']) {
                continue;
            }

            $offsets[] = (int) $configuredCandidate['display_order'] - ($legacyId - 1);
        }

        $offsets = array_values(array_unique($offsets));

        return 1 === count($offsets) ? $offsets[0] : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findCourseExerciseCandidates(int $courseId): array
    {
        $hasQuestionRelation = $this->connection->createSchemaManager()->tablesExist([
            'c_quiz_rel_question',
        ]);
        $questionCountSql = $hasQuestionRelation
            ? '(SELECT COUNT(*) FROM c_quiz_rel_question relation WHERE relation.quiz_id = quiz.iid)'
            : '0';

        $rows = $this->connection->fetchAllAssociative(
            "SELECT DISTINCT
                quiz.iid AS exercise_id,
                quiz.title,
                quiz.max_attempt,
                quiz.expired_time,
                quiz.duration,
                quiz.pass_percentage,
                course_link.display_order,
                {$questionCountSql} AS question_count,
                (
                    SELECT COUNT(*)
                    FROM gradebook_link gradebook
                    WHERE gradebook.c_id = :gradebookCourseId
                      AND gradebook.ref_id = quiz.iid
                ) AS gradebook_links,
                (
                    SELECT COUNT(*)
                    FROM track_e_exercises attempt
                    WHERE attempt.c_id = :attemptCourseId
                      AND attempt.exe_exo_id = quiz.iid
                ) AS attempt_rows,
                (
                    SELECT COUNT(*)
                    FROM c_lp_item item
                    INNER JOIN c_lp learning_path
                        ON learning_path.iid = item.lp_id
                    INNER JOIN resource_link learning_path_link
                        ON learning_path_link.resource_node_id = learning_path.resource_node_id
                       AND learning_path_link.c_id = :learningPathCourseId
                       AND learning_path_link.deleted_at IS NULL
                       AND learning_path_link.session_id IS NULL
                       AND learning_path_link.usergroup_id IS NULL
                       AND learning_path_link.group_id IS NULL
                       AND learning_path_link.user_id IS NULL
                    WHERE item.item_type = 'quiz'
                      AND TRIM(item.path) REGEXP '^[0-9]+$'
                      AND CAST(TRIM(item.path) AS UNSIGNED) = quiz.iid
                ) AS learning_path_items,
                (
                    SELECT COUNT(*)
                    FROM c_lp_item item
                    INNER JOIN c_lp learning_path
                        ON learning_path.iid = item.lp_id
                    INNER JOIN resource_link learning_path_link
                        ON learning_path_link.resource_node_id = learning_path.resource_node_id
                       AND learning_path_link.c_id = :midtermCourseId
                       AND learning_path_link.deleted_at IS NULL
                       AND learning_path_link.session_id IS NULL
                       AND learning_path_link.usergroup_id IS NULL
                       AND learning_path_link.group_id IS NULL
                       AND learning_path_link.user_id IS NULL
                    WHERE item.item_type = 'quiz'
                      AND TRIM(item.path) REGEXP '^[0-9]+$'
                      AND CAST(TRIM(item.path) AS UNSIGNED) = quiz.iid
                      AND (
                          LOWER(TRIM(item.title)) LIKE '%midterm%'
                          OR LOWER(TRIM(learning_path.title)) LIKE '%midterm%'
                          OR LOWER(TRIM(item.title)) LIKE '%mid term%'
                          OR LOWER(TRIM(learning_path.title)) LIKE '%mid term%'
                      )
                ) AS midterm_lp_matches,
                (
                    SELECT COUNT(*)
                    FROM c_lp_item item
                    INNER JOIN c_lp learning_path
                        ON learning_path.iid = item.lp_id
                    INNER JOIN resource_link learning_path_link
                        ON learning_path_link.resource_node_id = learning_path.resource_node_id
                       AND learning_path_link.c_id = :finalCourseId
                       AND learning_path_link.deleted_at IS NULL
                       AND learning_path_link.session_id IS NULL
                       AND learning_path_link.usergroup_id IS NULL
                       AND learning_path_link.group_id IS NULL
                       AND learning_path_link.user_id IS NULL
                    WHERE item.item_type = 'quiz'
                      AND TRIM(item.path) REGEXP '^[0-9]+$'
                      AND CAST(TRIM(item.path) AS UNSIGNED) = quiz.iid
                      AND (
                          LOWER(TRIM(item.title)) LIKE '%final%'
                          OR LOWER(TRIM(learning_path.title)) LIKE '%final%'
                      )
                ) AS final_lp_matches
            FROM c_quiz quiz
            INNER JOIN resource_link course_link
                ON course_link.resource_node_id = quiz.resource_node_id
               AND course_link.c_id = :courseId
               AND course_link.deleted_at IS NULL
               AND course_link.session_id IS NULL
               AND course_link.usergroup_id IS NULL
               AND course_link.group_id IS NULL
               AND course_link.user_id IS NULL
            ORDER BY quiz.iid",
            [
                'courseId' => $courseId,
                'gradebookCourseId' => $courseId,
                'attemptCourseId' => $courseId,
                'learningPathCourseId' => $courseId,
                'midtermCourseId' => $courseId,
                'finalCourseId' => $courseId,
            ]
        );

        return array_map(
            static fn (array $row): array => [
                'exercise_id' => (int) $row['exercise_id'],
                'title' => (string) $row['title'],
                'max_attempt' => (int) $row['max_attempt'],
                'expired_time' => (int) $row['expired_time'],
                'duration' => null === $row['duration'] ? null : (int) $row['duration'],
                'pass_percentage' => null === $row['pass_percentage']
                    ? null
                    : (int) $row['pass_percentage'],
                'display_order' => (int) $row['display_order'],
                'question_count' => (int) $row['question_count'],
                'gradebook_links' => (int) $row['gradebook_links'],
                'attempt_rows' => (int) $row['attempt_rows'],
                'learning_path_items' => (int) $row['learning_path_items'],
                'midterm_lp_matches' => (int) $row['midterm_lp_matches'],
                'final_lp_matches' => (int) $row['final_lp_matches'],
            ],
            $rows
        );
    }

    /**
     * @param list<int> $legacyIds
     *
     * @return array<int, list<array<string, mixed>>>
     */
    private function findHistoricalExerciseMappings(
        int $courseId,
        array $legacyIds,
        bool $hasTrackingBackup
    ): array {
        if (!$hasTrackingBackup) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
SELECT
    backup.exe_exo_id AS legacy_exercise_id,
    current_attempt.exe_exo_id AS current_exercise_id,
    quiz.title AS current_exercise_title,
    COUNT(DISTINCT backup.exe_id) AS mapped_rows,
    MAX(CASE WHEN course_link.c_id = :linkedCourseId THEN 1 ELSE 0 END) AS linked_to_course
FROM track_e_exercises_backup backup
INNER JOIN track_e_exercises current_attempt
    ON current_attempt.exe_id = backup.exe_id
LEFT JOIN c_quiz quiz
    ON quiz.iid = current_attempt.exe_exo_id
LEFT JOIN resource_link course_link
    ON course_link.resource_node_id = quiz.resource_node_id
   AND course_link.deleted_at IS NULL
   AND course_link.session_id IS NULL
   AND course_link.usergroup_id IS NULL
   AND course_link.group_id IS NULL
   AND course_link.user_id IS NULL
WHERE backup.c_id = :courseId
  AND backup.exe_exo_id IN (:ids)
GROUP BY
    backup.exe_exo_id,
    current_attempt.exe_exo_id,
    quiz.title
ORDER BY backup.exe_exo_id, mapped_rows DESC, current_attempt.exe_exo_id
SQL,
            ['courseId' => $courseId, 'linkedCourseId' => $courseId, 'ids' => $legacyIds],
            ['ids' => ArrayParameterType::INTEGER]
        );

        $mappings = [];
        foreach ($rows as $row) {
            $legacyId = (int) $row['legacy_exercise_id'];
            $mappings[$legacyId][] = [
                'current_exercise_id' => null === $row['current_exercise_id']
                    ? null
                    : (int) $row['current_exercise_id'],
                'current_exercise_title' => null === $row['current_exercise_title']
                    ? null
                    : (string) $row['current_exercise_title'],
                'mapped_rows' => (int) $row['mapped_rows'],
                'linked_to_course' => 1 === (int) $row['linked_to_course'],
            ];
        }

        return $mappings;
    }

    /**
     * @return list<int>
     */
    private function findConfiguredFinalExamIds(int $courseId): array
    {
        $ids = array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                <<<'SQL'
SELECT DISTINCT values_table.item_id
FROM extra_field field
INNER JOIN extra_field_values values_table
    ON values_table.field_id = field.id
INNER JOIN c_quiz quiz
    ON quiz.iid = values_table.item_id
INNER JOIN resource_link course_link
    ON course_link.resource_node_id = quiz.resource_node_id
   AND course_link.c_id = :courseId
   AND course_link.deleted_at IS NULL
   AND course_link.session_id IS NULL
   AND course_link.usergroup_id IS NULL
   AND course_link.group_id IS NULL
   AND course_link.user_id IS NULL
WHERE field.item_type = 17
  AND field.variable = :variable
ORDER BY values_table.item_id
SQL,
                [
                    'courseId' => $courseId,
                    'variable' => self::EXERCISE_RULE_FIELD_VARIABLE,
                ]
            )
        );
        sort($ids);

        return array_values(array_unique($ids));
    }

    /**
     * @param array<string, mixed> $candidate
     *
     * @return list<string>
     */
    private function candidateEvidence(
        array $candidate,
        int $legacyExerciseId,
        string $legacyRole
    ): array {
        $evidence = [];
        if ((int) $candidate['exercise_id'] === $legacyExerciseId) {
            $evidence[] = 'direct_id_match';
        }
        if ((int) $candidate['learning_path_items'] > 0) {
            $evidence[] = 'used_by_learning_path';
        }
        if ((int) $candidate['attempt_rows'] > 0) {
            $evidence[] = 'has_course_attempts';
        }
        if ((int) $candidate['gradebook_links'] > 0) {
            $evidence[] = 'has_gradebook_link';
        }
        if ((int) $candidate['question_count'] > 0) {
            $evidence[] = 'has_questions';
        }
        if ((int) $candidate['display_order'] === $legacyExerciseId - 1) {
            $evidence[] = 'legacy_display_order_match';
        }

        $roleMatch = $this->candidateMatchesRole($candidate, $legacyRole);
        if (true === $roleMatch) {
            $evidence[] = 'role_title_match';
        } elseif (false === $roleMatch) {
            $evidence[] = 'role_title_conflict';
        }

        return $evidence;
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function candidateMatchesRole(array $candidate, string $legacyRole): ?bool
    {
        if (!in_array($legacyRole, ['midterm', 'final_exam'], true)) {
            return null;
        }

        $title = strtolower(trim((string) $candidate['title']));
        if ('final_exam' === $legacyRole) {
            return str_contains($title, 'final')
                || (int) $candidate['final_lp_matches'] > 0;
        }

        return str_contains($title, 'midterm')
            || str_contains($title, 'mid term')
            || (int) $candidate['midterm_lp_matches'] > 0;
    }

    /**
     * @param list<array<string, mixed>> $courseCandidates
     * @param list<int>                  $configuredFinalExamIds
     *
     * @return list<array<string, mixed>>
     */
    private function rankExerciseCandidates(
        array $courseCandidates,
        int $legacyExerciseId,
        string $legacyRole,
        array $configuredFinalExamIds
    ): array {
        $ranked = [];
        foreach ($courseCandidates as $candidate) {
            $evidence = $this->candidateEvidence(
                $candidate,
                $legacyExerciseId,
                $legacyRole
            );
            if (in_array((int) $candidate['exercise_id'], $configuredFinalExamIds, true)) {
                $evidence[] = 'configured_final_exam';
            }

            $score = 0;
            foreach ($evidence as $item) {
                $score += match ($item) {
                    'role_title_match' => 40,
                    'configured_final_exam' => 40,
                    'direct_id_match' => 25,
                    'used_by_learning_path' => 15,
                    'has_course_attempts' => 10,
                    'has_gradebook_link' => 10,
                    'legacy_display_order_match' => 10,
                    'has_questions' => 5,
                    'role_title_conflict' => -60,
                    default => 0,
                };
            }

            $ranked[] = [
                ...$candidate,
                'score' => $score,
                'evidence' => array_values(array_unique($evidence)),
            ];
        }

        usort(
            $ranked,
            static fn (array $left, array $right): int => [$right['score'], $left['exercise_id']]
                <=> [$left['score'], $right['exercise_id']]
        );

        return $ranked;
    }

    /**
     * @param list<int> $ids
     *
     * @return array{total: int, resolved: int, missing: int, resolved_ids: list<int>, missing_ids: list<int>}
     */
    private function missingResourceAudit(array $ids): array
    {
        return [
            'total' => count($ids),
            'resolved' => 0,
            'missing' => count($ids),
            'resolved_ids' => [],
            'missing_ids' => $ids,
        ];
    }

    /**
     * @param list<int> $ids
     *
     * @return array<string, mixed>
     */
    private function missingExerciseAudit(array $ids): array
    {
        return [
            'total' => count($ids),
            'resolved_from_history' => 0,
            'resolved_from_final_exam_rule' => 0,
            'verified_direct_with_context' => 0,
            'resolved_from_course_sequence_anchor' => 0,
            'candidate_role_match' => 0,
            'candidate_display_order' => 0,
            'ambiguous_candidates' => 0,
            'unresolved' => count($ids),
            'details' => array_map(
                static fn (int $id): array => [
                    'legacy_exercise_id' => $id,
                    'legacy_role' => 'assessment',
                    'legacy_weight' => 0.0,
                    'status' => 'unresolved',
                    'confidence' => 'none',
                    'selection_strategy' => null,
                    'selected_current_exercise_id' => null,
                    'suggested_current_exercise_id' => null,
                    'evidence' => ['course_missing'],
                    'direct_current_id_match' => false,
                    'historical_current_ids' => [],
                    'historical_mappings' => [],
                    'configured_final_exam_ids' => [],
                    'candidates' => [],
                ],
                $ids
            ),
        ];
    }

    /**
     * @return array{total: int, resolved: int, missing: int, resolved_ids: list<int>, missing_ids: list<int>}
     */
    private function emptyResourceAudit(): array
    {
        return [
            'total' => 0,
            'resolved' => 0,
            'missing' => 0,
            'resolved_ids' => [],
            'missing_ids' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyExerciseAudit(): array
    {
        return [
            'total' => 0,
            'resolved_from_history' => 0,
            'resolved_from_final_exam_rule' => 0,
            'verified_direct_with_context' => 0,
            'resolved_from_course_sequence_anchor' => 0,
            'candidate_role_match' => 0,
            'candidate_display_order' => 0,
            'ambiguous_candidates' => 0,
            'unresolved' => 0,
            'details' => [],
        ];
    }

    /**
     * @return list<array{code: string, course_id: int|null, course_title: string|null, status: string}>
     */
    private function auditFlatviewOnlyRules(): array
    {
        $rules = [];
        foreach (self::FLATVIEW_ONLY_COURSE_CODES as $courseCode) {
            $course = $this->connection->fetchAssociative(
                'SELECT id, title FROM course WHERE code = :code LIMIT 1',
                ['code' => $courseCode]
            );
            $rules[] = [
                'code' => $courseCode,
                'course_id' => false === $course ? null : (int) $course['id'],
                'course_title' => false === $course ? null : (string) $course['title'],
                'status' => false === $course ? 'missing_course' : 'found_without_category_formula',
            ];
        }

        return $rules;
    }

    /**
     * @param list<array<string, mixed>> $courses
     *
     * @return list<array<string, mixed>>
     */
    private function collectExerciseMappings(array $courses, bool $verified): array
    {
        $verifiedStatuses = [
            'resolved_from_history',
            'resolved_from_final_exam_rule',
            'verified_direct_with_context',
            'resolved_from_course_sequence_anchor',
        ];
        $mappings = [];

        foreach ($courses as $course) {
            foreach ($course['exercises']['details'] as $detail) {
                $isVerified = in_array($detail['status'], $verifiedStatuses, true);
                if ($verified !== $isVerified) {
                    continue;
                }

                $mappings[] = [
                    'course_code' => $course['code'],
                    'course_id' => $course['course_id'],
                    'legacy_exercise_id' => $detail['legacy_exercise_id'],
                    'legacy_role' => $detail['legacy_role'],
                    'legacy_weight' => $detail['legacy_weight'],
                    'status' => $detail['status'],
                    'confidence' => $detail['confidence'],
                    'selected_current_exercise_id' => $detail['selected_current_exercise_id'],
                    'suggested_current_exercise_id' => $detail['suggested_current_exercise_id'] ?? null,
                    'selection_strategy' => $detail['selection_strategy'],
                    'evidence' => $detail['evidence'],
                ];
            }
        }

        return $mappings;
    }

    /**
     * @param array<string, int> $summary
     * @param list<array{status: string}> $flatviewOnlyRules
     */
    private function addFlatviewOnlySummary(array &$summary, array $flatviewOnlyRules): void
    {
        $summary['flatview_only_rules'] = count($flatviewOnlyRules);
        foreach ($flatviewOnlyRules as $rule) {
            if ('found_without_category_formula' === $rule['status']) {
                ++$summary['flatview_only_courses_found'];
            } else {
                ++$summary['flatview_only_courses_missing'];
            }
        }
    }

    /**
     * @param array<string, int>   $summary
     * @param array<string, mixed> $courseReport
     */
    private function addToSummary(array &$summary, array $courseReport): void
    {
        ++$summary['courses_selected'];
        if ('found' === $courseReport['status']) {
            ++$summary['courses_found'];
            if (!$courseReport['legacy_course_id_matches']) {
                ++$summary['legacy_course_id_mismatches'];
            }
        } else {
            ++$summary['courses_missing'];
        }

        foreach ([
            'forum_threads' => 'forum_thread',
            'works' => 'work',
            'evaluations' => 'evaluation',
        ] as $reportKey => $summaryPrefix) {
            $summary[$summaryPrefix.'_references'] += $courseReport[$reportKey]['total'];
            $summary[$summaryPrefix.'_resolved'] += $courseReport[$reportKey]['resolved'];
            $summary[$summaryPrefix.'_missing'] += $courseReport[$reportKey]['missing'];
        }

        $summary['exercise_references'] += $courseReport['exercises']['total'];
        foreach ([
            'resolved_from_history',
            'resolved_from_final_exam_rule',
            'verified_direct_with_context',
            'resolved_from_course_sequence_anchor',
            'candidate_role_match',
            'candidate_display_order',
            'ambiguous_candidates',
            'unresolved',
        ] as $status) {
            $summary['exercise_'.$status] += $courseReport['exercises'][$status];
        }

        $summary['exercise_verified_references'] +=
            $courseReport['exercises']['resolved_from_history']
            + $courseReport['exercises']['resolved_from_final_exam_rule']
            + $courseReport['exercises']['verified_direct_with_context']
            + $courseReport['exercises']['resolved_from_course_sequence_anchor'];
        $summary['exercise_review_required'] +=
            $courseReport['exercises']['candidate_role_match']
            + $courseReport['exercises']['candidate_display_order']
            + $courseReport['exercises']['ambiguous_candidates']
            + $courseReport['exercises']['unresolved'];
    }

    /**
     * @param array<string, mixed> $courseReport
     */
    private function renderCourseReport(SymfonyStyle $io, array $courseReport): void
    {
        $io->section(sprintf(
            '%s — %s',
            $courseReport['code'],
            $courseReport['course_title'] ?? 'course not found'
        ));

        $io->definitionList(
            ['Status' => $courseReport['status']],
            ['Current course ID' => $courseReport['course_id'] ?? 'n/a'],
            ['Legacy course ID' => $courseReport['legacy_course_id']],
            ['Legacy/current course ID match' => $courseReport['legacy_course_id_matches'] ? 'yes' : 'no'],
            ['Forum threads' => $this->formatResourceAudit($courseReport['forum_threads'])],
            ['Works' => $this->formatResourceAudit($courseReport['works'])],
            ['Evaluations' => $this->formatResourceAudit($courseReport['evaluations'])],
            ['Exercises' => sprintf(
                '%d total; %d history; %d final-rule; %d direct-context; %d sequence-anchor; %d role candidates; %d order candidates; %d ambiguous; %d unresolved',
                $courseReport['exercises']['total'],
                $courseReport['exercises']['resolved_from_history'],
                $courseReport['exercises']['resolved_from_final_exam_rule'],
                $courseReport['exercises']['verified_direct_with_context'],
                $courseReport['exercises']['resolved_from_course_sequence_anchor'],
                $courseReport['exercises']['candidate_role_match'],
                $courseReport['exercises']['candidate_display_order'],
                $courseReport['exercises']['ambiguous_candidates'],
                $courseReport['exercises']['unresolved']
            )]
        );

        if ([] !== $courseReport['forum_threads']['missing_ids']) {
            $io->writeln('<comment>Missing forum thread IDs: '.implode(', ', $courseReport['forum_threads']['missing_ids']).'</comment>');
        }
        if ([] !== $courseReport['works']['missing_ids']) {
            $io->writeln('<comment>Missing work IDs: '.implode(', ', $courseReport['works']['missing_ids']).'</comment>');
        }
        if ([] !== $courseReport['evaluations']['missing_ids']) {
            $io->writeln('<comment>Missing evaluation IDs: '.implode(', ', $courseReport['evaluations']['missing_ids']).'</comment>');
        }

        foreach ($courseReport['exercises']['details'] as $detail) {
            if (in_array($detail['status'], [
                'resolved_from_history',
                'resolved_from_final_exam_rule',
                'verified_direct_with_context',
                'resolved_from_course_sequence_anchor',
            ], true)) {
                continue;
            }

            $candidatePreview = array_map(
                static fn (array $candidate): string => sprintf(
                    '%d:%s(score=%d)',
                    $candidate['exercise_id'],
                    $candidate['title'],
                    $candidate['score']
                ),
                array_slice($detail['candidates'], 0, 3)
            );

            $io->writeln(sprintf(
                '<comment>Exercise legacy=%d role=%s status=%s selected=%s suggested=%s confidence=%s candidates=%s</comment>',
                $detail['legacy_exercise_id'],
                $detail['legacy_role'],
                $detail['status'],
                null === $detail['selected_current_exercise_id']
                    ? 'none'
                    : (string) $detail['selected_current_exercise_id'],
                null === ($detail['suggested_current_exercise_id'] ?? null)
                    ? 'none'
                    : (string) $detail['suggested_current_exercise_id'],
                $detail['confidence'],
                [] === $candidatePreview ? 'none' : implode(' | ', $candidatePreview)
            ));
        }
    }

    /**
     * @param array{total: int, resolved: int, missing: int} $audit
     */
    private function formatResourceAudit(array $audit): string
    {
        return sprintf('%d total; %d resolved; %d missing', $audit['total'], $audit['resolved'], $audit['missing']);
    }

    /**
     * @param list<array{code: string, course_id: int|null, course_title: string|null, status: string}> $rules
     */
    private function renderFlatviewOnlyRules(SymfonyStyle $io, array $rules): void
    {
        $rows = array_map(
            static fn (array $rule): array => [
                $rule['code'],
                $rule['course_id'] ?? 'n/a',
                $rule['status'],
            ],
            $rules
        );

        $io->section('Flatview-only legacy rules');
        $io->table(['Course code', 'Course ID', 'Status'], $rows);
        $io->warning('These course codes have custom flatview logic but no matching Category::userFinishedCourse()/userFinishedScore() formula in the audited legacy source.');
    }

    /**
     * @param array<string, int> $summary
     */
    private function renderSummary(SymfonyStyle $io, array $summary, bool $hasTrackingBackup): void
    {
        $io->definitionList(
            ['Legacy formulas audited' => $summary['courses_selected']],
            ['Courses found' => $summary['courses_found']],
            ['Courses missing' => $summary['courses_missing']],
            ['Legacy/current course ID mismatches' => $summary['legacy_course_id_mismatches']],
            ['Forum references' => sprintf('%d total; %d resolved; %d missing', $summary['forum_thread_references'], $summary['forum_thread_resolved'], $summary['forum_thread_missing'])],
            ['Work references' => sprintf('%d total; %d resolved; %d missing', $summary['work_references'], $summary['work_resolved'], $summary['work_missing'])],
            ['Evaluation references' => sprintf('%d total; %d resolved; %d missing', $summary['evaluation_references'], $summary['evaluation_resolved'], $summary['evaluation_missing'])],
            ['Exercise references' => sprintf(
                '%d total; %d history; %d final-rule; %d direct-context; %d sequence-anchor; %d role candidates; %d order candidates; %d ambiguous; %d unresolved',
                $summary['exercise_references'],
                $summary['exercise_resolved_from_history'],
                $summary['exercise_resolved_from_final_exam_rule'],
                $summary['exercise_verified_direct_with_context'],
                $summary['exercise_resolved_from_course_sequence_anchor'],
                $summary['exercise_candidate_role_match'],
                $summary['exercise_candidate_display_order'],
                $summary['exercise_ambiguous_candidates'],
                $summary['exercise_unresolved']
            )],
            ['Verified exercise references' => $summary['exercise_verified_references']],
            ['Exercise references requiring review' => $summary['exercise_review_required']],
            ['Indexed tracking backup usable' => $hasTrackingBackup ? 'yes' : 'no'],
            ['Flatview-only rules' => sprintf('%d total; %d courses found; %d missing', $summary['flatview_only_rules'], $summary['flatview_only_courses_found'], $summary['flatview_only_courses_missing'])]
        );
    }

    /**
     * @param array<string, mixed> $report
     */
    private function writeJsonReport(InputInterface $input, SymfonyStyle $io, array $report): void
    {
        $outputPath = trim((string) $input->getOption('output'));
        if ('' === $outputPath) {
            return;
        }
        if (!str_starts_with($outputPath, '/')) {
            throw new RuntimeException('--output must be an absolute path.');
        }

        $directory = dirname($outputPath);
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new RuntimeException(sprintf('Output directory is not writable: %s', $directory));
        }

        try {
            $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Could not encode the JSON audit report.', 0, $exception);
        }

        if (false === file_put_contents($outputPath, $json."\n")) {
            throw new RuntimeException(sprintf('Could not write JSON report: %s', $outputPath));
        }

        $io->note('JSON report written to '.$outputPath);
    }
}
