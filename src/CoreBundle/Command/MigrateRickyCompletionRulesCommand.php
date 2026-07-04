<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Component\Gradebook\CourseCompletionRuleEvaluator;
use Chamilo\CoreBundle\Entity\ExtraField;
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
    name: 'chamilo:migration:migrate-ricky-completion-rules',
    description: 'Persist Ricky Rescue legacy completion formulas as generic course completion rules.'
)]
final class MigrateRickyCompletionRulesCommand extends Command
{
    private const SOURCE = 'ricky_legacy_completion_rule';
    private const LEGACY_SOURCE_SHA256 = '20d36aeea40353265e15cdc4a07128108c98db18bc64b8e5bc8d52a080bc9436';
    private const AUDIT_REPORT_SHA256 = '7e9ae6aa2b26ee9a282c14314ef71e7df30f24eb24cbc044ab65a2e2d6ffa692';

    private const RULES = [
        'course:1301' => [
            'course_id' => 11,
            'forum' => [
                'thread_ids' => [161, 162, 163, 164, 166, 165],
                'one_post_points' => 2.33,
                'two_plus_points' => 3.33,
            ],
            'works' => [],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 17,
                    'current_id' => 332,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_from_migrated_course_metadata',
                    'mapping_confidence' => 'high',
                ],
                [
                    'legacy_id' => 22,
                    'current_id' => 337,
                    'weight' => 30.0,
                    'mapping_status' => 'verified_from_migrated_course_metadata',
                    'mapping_confidence' => 'high',
                ],
                [
                    'legacy_id' => 745,
                    'current_id' => 745,
                    'weight' => 30.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:1302' => [
            'course_id' => 20,
            'forum' => [
                'thread_ids' => [201, 202, 203, 204, 205, 206, 207, 208, 209],
                'one_post_points' => 1.55,
                'two_plus_points' => 2.22,
            ],
            'works' => [],
            'evaluations' => [
                2 => 40.0,
            ],
            'exercises' => [
                [
                    'legacy_id' => 18,
                    'current_id' => 138,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_course_sequence_anchor',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 30,
                    'current_id' => 150,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:1505' => [
            'course_id' => 8,
            'forum' => [
                'thread_ids' => [88, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101],
                'one_post_points' => 1.16,
                'two_plus_points' => 1.66,
            ],
            'works' => [],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 707,
                    'current_id' => 707,
                    'weight' => 5.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 708,
                    'current_id' => 708,
                    'weight' => 5.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 709,
                    'current_id' => 709,
                    'weight' => 5.0,
                    'mapping_status' => 'resolved_from_history',
                    'mapping_confidence' => 'high',
                ],
                [
                    'legacy_id' => 710,
                    'current_id' => 710,
                    'weight' => 5.0,
                    'mapping_status' => 'resolved_from_history',
                    'mapping_confidence' => 'high',
                ],
                [
                    'legacy_id' => 1,
                    'current_id' => 156,
                    'weight' => 30.0,
                    'mapping_status' => 'resolved_from_course_sequence_anchor',
                    'mapping_confidence' => 'high',
                ],
                [
                    'legacy_id' => 3,
                    'current_id' => 158,
                    'weight' => 30.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:1510' => [
            'course_id' => 6,
            'forum' => [
                'thread_ids' => [102, 103, 104, 105, 106, 107, 108, 109],
                'one_post_points' => 2.62,
                'two_plus_points' => 3.74,
            ],
            'works' => [
                12 => 30.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 5,
                    'current_id' => 43,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_course_sequence_anchor',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 4,
                    'current_id' => 42,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:1540' => [
            'course_id' => 13,
            'forum' => [
                'thread_ids' => [52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65],
                'one_post_points' => 1.49,
                'two_plus_points' => 2.14,
            ],
            'works' => [
                29 => 30.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 1,
                    'current_id' => 236,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_course_sequence_anchor',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 2,
                    'current_id' => 237,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:1740' => [
            'course_id' => 15,
            'forum' => [
                'thread_ids' => [211, 212, 213, 214, 215, 216, 217, 218],
                'one_post_points' => 1.74,
                'two_plus_points' => 2.49,
            ],
            'works' => [
                20 => 20.0,
                22 => 20.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 17,
                    'current_id' => 176,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_course_sequence_anchor',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 14,
                    'current_id' => 173,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:17402021' => [
            'course_id' => 87,
            'forum' => [
                'thread_ids' => [772, 769, 770, 773, 774, 775, 777, 776, 778, 779],
                'one_post_points' => 0.7,
                'two_plus_points' => 2.0,
            ],
            'works' => [
                39390 => 20.0,
                39389 => 20.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 1188,
                    'current_id' => 1188,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 1191,
                    'current_id' => 1191,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:1810' => [
            'course_id' => 9,
            'forum' => [
                'thread_ids' => [190, 189, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200],
                'one_post_points' => 1.74,
                'two_plus_points' => 2.49,
            ],
            'works' => [
                23 => 30.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 753,
                    'current_id' => 753,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_history',
                    'mapping_confidence' => 'high',
                ],
                [
                    'legacy_id' => 755,
                    'current_id' => 755,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_history',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:2111' => [
            'course_id' => 32,
            'forum' => [
                'thread_ids' => [571, 572, 573, 574, 712, 711, 577, 578, 579, 580, 581, 582],
                'one_post_points' => 1.75,
                'two_plus_points' => 2.47,
            ],
            'works' => [
                16088 => 30.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 866,
                    'current_id' => 866,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 868,
                    'current_id' => 868,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:2120' => [
            'course_id' => 1,
            'forum' => [
                'thread_ids' => [5, 2, 6, 7, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18],
                'one_post_points' => 1.5,
                'two_plus_points' => 2.14,
            ],
            'works' => [
                1 => 30.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 5,
                    'current_id' => 5,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_history',
                    'mapping_confidence' => 'high',
                ],
                [
                    'legacy_id' => 7,
                    'current_id' => 17,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:2521' => [
            'course_id' => 5,
            'forum' => [
                'thread_ids' => [50, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152],
                'one_post_points' => 1.07,
                'two_plus_points' => 1.53,
            ],
            'works' => [
                14 => 40.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 2,
                    'current_id' => 37,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_course_sequence_anchor',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 3,
                    'current_id' => 38,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:2541' => [
            'course_id' => 74,
            'forum' => [
                'thread_ids' => [714, 715, 716, 717, 718, 720, 721, 722, 723, 724, 725, 726, 727, 728, 729],
                'one_post_points' => 1.39,
                'two_plus_points' => 1.99,
            ],
            'works' => [
                16801 => 30.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 1036,
                    'current_id' => 1036,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 1038,
                    'current_id' => 1038,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:2610' => [
            'course_id' => 76,
            'forum' => null,
            'works' => [],
            'evaluations' => [
                9 => 100.0,
            ],
            'exercises' => [],
        ],
        'course:2706' => [
            'course_id' => 14,
            'forum' => [
                'thread_ids' => [167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177],
                'one_post_points' => 1.9,
                'two_plus_points' => 2.72,
            ],
            'works' => [
                24628 => 30.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 2,
                    'current_id' => 239,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_course_sequence_anchor',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 4,
                    'current_id' => 241,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:2720' => [
            'course_id' => 3,
            'forum' => [
                'thread_ids' => [3, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37],
                'one_post_points' => 0.74,
                'two_plus_points' => 1.05,
            ],
            'works' => [
                6 => 20.0,
                11 => 20.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 1,
                    'current_id' => 34,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_course_sequence_anchor',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 2,
                    'current_id' => 35,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_history',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:2741' => [
            'course_id' => 16,
            'forum' => [
                'thread_ids' => [219, 220, 221, 222, 223],
                'one_post_points' => 2.79,
                'two_plus_points' => 3.99,
            ],
            'works' => [
                21 => 60.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 1,
                    'current_id' => 184,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:27412021' => [
            'course_id' => 92,
            'forum' => [
                'thread_ids' => [781, 782, 783, 784, 785],
                'one_post_points' => 2.8,
                'two_plus_points' => 4.0,
            ],
            'works' => [
                42892 => 60.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 1213,
                    'current_id' => 1213,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:2770' => [
            'course_id' => 7,
            'forum' => [
                'thread_ids' => [126, 127, 128, 129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140],
                'one_post_points' => 1.39,
                'two_plus_points' => 1.99,
            ],
            'works' => [
                17 => 30.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 9,
                    'current_id' => 82,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_course_sequence_anchor',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 6,
                    'current_id' => 79,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:2811' => [
            'course_id' => 10,
            'forum' => [
                'thread_ids' => [178, 179, 180, 181, 182, 183, 184, 185, 186, 187],
                'one_post_points' => 2.09,
                'two_plus_points' => 2.99,
            ],
            'works' => [
                24 => 30.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 758,
                    'current_id' => 758,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_history',
                    'mapping_confidence' => 'high',
                ],
                [
                    'legacy_id' => 757,
                    'current_id' => 757,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:6741' => [
            'course_id' => 19,
            'forum' => [
                'thread_ids' => [225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237],
                'one_post_points' => 1.61,
                'two_plus_points' => 2.3,
            ],
            'works' => [
                26 => 15.0,
                25 => 15.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 25,
                    'current_id' => 482,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_from_migrated_course_metadata',
                    'mapping_confidence' => 'high',
                ],
                [
                    'legacy_id' => 484,
                    'current_id' => 484,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:6742' => [
            'course_id' => 17,
            'forum' => [
                'thread_ids' => [71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87],
                'one_post_points' => 1.23,
                'two_plus_points' => 1.76,
            ],
            'works' => [
                27 => 15.0,
                28 => 15.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 63,
                    'current_id' => 305,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_course_sequence_anchor',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 65,
                    'current_id' => 307,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:703' => [
            'course_id' => 49,
            'forum' => [
                'thread_ids' => [586, 591, 587, 592, 588, 593, 589, 594, 590, 595],
                'one_post_points' => 1.39,
                'two_plus_points' => 1.99,
            ],
            'works' => [],
            'evaluations' => [
                7 => 40.0,
            ],
            'exercises' => [
                [
                    'legacy_id' => 760,
                    'current_id' => 760,
                    'weight' => 8.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 761,
                    'current_id' => 761,
                    'weight' => 8.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 762,
                    'current_id' => 762,
                    'weight' => 8.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 763,
                    'current_id' => 763,
                    'weight' => 8.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 759,
                    'current_id' => 759,
                    'weight' => 8.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:7529' => [
            'course_id' => 69,
            'forum' => [
                'thread_ids' => [733, 738, 734, 737, 736],
                'one_post_points' => 4.19,
                'two_plus_points' => 5.99,
            ],
            'works' => [
                20870 => 15.0,
                20869 => 15.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 1062,
                    'current_id' => 1062,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_history',
                    'mapping_confidence' => 'high',
                ],
                [
                    'legacy_id' => 1064,
                    'current_id' => 1064,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:9516' => [
            'course_id' => 2,
            'forum' => [
                'thread_ids' => [38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49],
                'one_post_points' => 1.16,
                'two_plus_points' => 1.66,
            ],
            'works' => [
                8 => 40.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 3,
                    'current_id' => 32,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_course_sequence_anchor',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 4,
                    'current_id' => 33,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:9641' => [
            'course_id' => 31,
            'forum' => [
                'thread_ids' => [240, 241, 242, 243, 244],
                'one_post_points' => 4.19,
                'two_plus_points' => 5.99,
            ],
            'works' => [
                31 => 30.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 25,
                    'current_id' => 373,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_course_sequence_anchor',
                    'mapping_confidence' => 'high',
                ],
                [
                    'legacy_id' => 29,
                    'current_id' => 377,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:2521C' => [
            'course_id' => 82,
            'forum' => null,
            'works' => [
                71261 => 25.0,
            ],
            'evaluations' => [
                36 => 25.0,
            ],
            'exercises' => [
                [
                    'legacy_id' => 1408,
                    'current_id' => 1408,
                    'weight' => 25.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 1409,
                    'current_id' => 1409,
                    'weight' => 25.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:AERIALDRIVEROPERATOR' => [
            'course_id' => 39,
            'forum' => [
                'thread_ids' => [504, 677, 505, 678, 506, 679, 507, 680, 508, 681],
                'one_post_points' => 2.09,
                'two_plus_points' => 2.99,
            ],
            'works' => [
                761 => 30.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 779,
                    'current_id' => 779,
                    'weight' => 8.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 780,
                    'current_id' => 780,
                    'weight' => 8.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 781,
                    'current_id' => 781,
                    'weight' => 8.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 782,
                    'current_id' => 782,
                    'weight' => 8.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 778,
                    'current_id' => 778,
                    'weight' => 8.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:COURSEDELIVERY' => [
            'course_id' => 52,
            'forum' => [
                'thread_ids' => [622, 619, 620, 621, 614, 615, 616, 617],
                'one_post_points' => 1.75,
                'two_plus_points' => 2.5,
            ],
            'works' => [
                1620 => 20.0,
                1621 => 20.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 827,
                    'current_id' => 827,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 829,
                    'current_id' => 829,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_history',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:COURSEDESIGN' => [
            'course_id' => 53,
            'forum' => [
                'thread_ids' => [664, 670, 671, 672, 673, 674, 675, 665, 666, 667, 668, 669],
                'one_post_points' => 1.16,
                'two_plus_points' => 1.66,
            ],
            'works' => [
                1720 => 40.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 830,
                    'current_id' => 830,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 831,
                    'current_id' => 831,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:FIREINSPECTOR' => [
            'course_id' => 41,
            'forum' => [
                'thread_ids' => [597, 598, 599, 600, 601, 602, 603, 604, 605, 606, 607, 608, 609, 610, 611],
                'one_post_points' => 1.39,
                'two_plus_points' => 1.99,
            ],
            'works' => [
                1455 => 30.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 816,
                    'current_id' => 816,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 817,
                    'current_id' => 817,
                    'weight' => 20.0,
                    'mapping_status' => 'resolved_from_history',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:FIRESERVICEPROGRAMMANAGER' => [
            'course_id' => 80,
            'forum' => [
                'thread_ids' => [759, 756, 757, 758],
                'one_post_points' => 7.0,
                'two_plus_points' => 10.0,
            ],
            'works' => [
                32647 => 60.0,
            ],
            'evaluations' => [],
            'exercises' => [],
        ],
        'course:NFPA' => [
            'course_id' => 12,
            'forum' => [
                'thread_ids' => [67, 68, 69, 70],
                'one_post_points' => 6.99,
                'two_plus_points' => 9.99,
            ],
            'works' => [],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 3,
                    'current_id' => 230,
                    'weight' => 30.0,
                    'mapping_status' => 'resolved_from_course_sequence_anchor',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 7,
                    'current_id' => 234,
                    'weight' => 30.0,
                    'mapping_status' => 'resolved_from_final_exam_rule',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:NFPA2021' => [
            'course_id' => 77,
            'forum' => null,
            'works' => [],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 1124,
                    'current_id' => 1124,
                    'weight' => 15.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 1129,
                    'current_id' => 1129,
                    'weight' => 15.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 1128,
                    'current_id' => 1128,
                    'weight' => 15.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 1131,
                    'current_id' => 1131,
                    'weight' => 15.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 1133,
                    'current_id' => 1133,
                    'weight' => 40.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:PUMPERDRIVEROPERATOR' => [
            'course_id' => 38,
            'forum' => [
                'thread_ids' => [465, 397, 398, 399, 400, 401, 402, 403, 404, 405, 406, 411, 410, 407, 408, 409],
                'one_post_points' => 0.77,
                'two_plus_points' => 1.09,
            ],
            'works' => [
                35 => 30.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 513,
                    'current_id' => 513,
                    'weight' => 17.49,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 509,
                    'current_id' => 509,
                    'weight' => 17.49,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 512,
                    'current_id' => 512,
                    'weight' => 17.49,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:RN3842' => [
            'course_id' => 93,
            'forum' => [
                'thread_ids' => [787, 788, 789, 790],
                'one_post_points' => 7.0,
                'two_plus_points' => 9.9,
            ],
            'works' => [],
            'evaluations' => [
                37 => 30.0,
            ],
            'exercises' => [
                [
                    'legacy_id' => 1215,
                    'current_id' => 1215,
                    'weight' => 30.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:SERP' => [
            'course_id' => 43,
            'forum' => [
                'thread_ids' => [638, 643, 642, 641, 646, 645, 644, 649, 648, 647],
                'one_post_points' => 3.5,
                'two_plus_points' => 5.0,
            ],
            'works' => [],
            'evaluations' => [
                6 => 100.0,
            ],
            'exercises' => [],
        ],
        'course:TN1810' => [
            'course_id' => 44,
            'forum' => [
                'thread_ids' => [540, 542, 545, 546, 548, 550],
                'one_post_points' => 4.66,
                'two_plus_points' => 6.66,
            ],
            'works' => [],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 697,
                    'current_id' => 697,
                    'weight' => 10.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 699,
                    'current_id' => 699,
                    'weight' => 10.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 701,
                    'current_id' => 701,
                    'weight' => 10.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 703,
                    'current_id' => 703,
                    'weight' => 10.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 706,
                    'current_id' => 706,
                    'weight' => 10.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 744,
                    'current_id' => 744,
                    'weight' => 10.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:TNFDISO' => [
            'course_id' => 30,
            'forum' => [
                'thread_ids' => [327, 328, 329, 330, 331, 332, 333, 334, 335, 336, 337, 338, 339, 340, 341, 342, 343],
                'one_post_points' => 1.23,
                'two_plus_points' => 1.76,
            ],
            'works' => [
                38 => 15.0,
                1242 => 15.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 533,
                    'current_id' => 533,
                    'weight' => 15.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 534,
                    'current_id' => 534,
                    'weight' => 15.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:TNFI1' => [
            'course_id' => 28,
            'forum' => [
                'thread_ids' => [303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314],
                'one_post_points' => 1.16,
                'two_plus_points' => 1.66,
            ],
            'works' => [
                41 => 40.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 628,
                    'current_id' => 628,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 630,
                    'current_id' => 630,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:TNFI2' => [
            'course_id' => 29,
            'forum' => [
                'thread_ids' => [315, 316, 317, 318, 319, 320, 321, 322, 323, 324, 325, 326],
                'one_post_points' => 1.16,
                'two_plus_points' => 1.66,
            ],
            'works' => [
                42 => 40.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 652,
                    'current_id' => 652,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 653,
                    'current_id' => 653,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:TNFO1' => [
            'course_id' => 23,
            'forum' => [
                'thread_ids' => [466, 477, 478, 479, 480, 481, 482, 483, 484, 467, 468, 469, 470, 471, 259, 260, 261, 262, 263],
                'one_post_points' => 0.74,
                'two_plus_points' => 1.05,
            ],
            'works' => [
                36 => 20.0,
                37 => 20.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 514,
                    'current_id' => 514,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 515,
                    'current_id' => 515,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
        ],
        'course:TNFO1FO2' => [
            'course_id' => 26,
            'forum' => [
                'thread_ids' => [344, 345, 346, 347, 348, 349, 350, 351, 352, 353, 354, 355, 356, 357, 358, 359, 360, 361, 362, 363, 364, 365, 366, 367, 368, 369, 370, 371, 372, 373, 374, 375, 376, 377, 378, 379, 380, 381],
                'one_post_points' => 0.367,
                'two_plus_points' => 0.525,
            ],
            'works' => [
                112 => 10.0,
                113 => 10.0,
                114 => 10.0,
                115 => 10.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 1,
                    'current_id' => 732,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_from_migrated_course_metadata',
                    'mapping_confidence' => 'high',
                ],
                [
                    'legacy_id' => 2,
                    'current_id' => 733,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_from_migrated_course_metadata',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:TNFO2' => [
            'course_id' => 24,
            'forum' => [
                'thread_ids' => [264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280],
                'one_post_points' => 0.82,
                'two_plus_points' => 1.17,
            ],
            'works' => [
                359 => 20.0,
                358 => 20.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 5,
                    'current_id' => 740,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_from_migrated_course_metadata',
                    'mapping_confidence' => 'high',
                ],
                [
                    'legacy_id' => 1,
                    'current_id' => 736,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_from_migrated_course_metadata',
                    'mapping_confidence' => 'high',
                ],
            ],
        ],
        'course:TNFO3' => [
            'course_id' => 27,
            'forum' => [
                'thread_ids' => [509, 512, 513, 514, 515, 516, 522, 523, 524, 520, 521],
                'one_post_points' => 1.27,
                'two_plus_points' => 1.81,
            ],
            'works' => [
                40 => 40.0,
            ],
            'evaluations' => [],
            'exercises' => [
                [
                    'legacy_id' => 615,
                    'current_id' => 615,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
                [
                    'legacy_id' => 616,
                    'current_id' => 616,
                    'weight' => 20.0,
                    'mapping_status' => 'verified_direct_with_context',
                    'mapping_confidence' => 'medium',
                ],
            ],
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
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show the changes without writing extra-field definitions or values.'
            )
            ->addOption(
                'course-code',
                null,
                InputOption::VALUE_REQUIRED,
                'Migrate only one configured Ricky course code.'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Replace an existing value only when it was created by this Ricky migration command.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migrate Ricky completion rules');

        $dryRun = (bool) $input->getOption('dry-run');
        $force = (bool) $input->getOption('force');
        $requestedCourseCode = trim((string) $input->getOption('course-code'));

        try {
            $this->assertRequiredTables();
            $rules = $this->selectRules($requestedCourseCode);
            $fieldId = $this->getOrCreateCourseRuleField($dryRun);
            $summary = [
                'selected_rules' => count($rules),
                'configured' => 0,
                'updated' => 0,
                'already_configured' => 0,
                'conflicting_existing' => 0,
                'missing_course' => 0,
                'course_id_mismatch' => 0,
                'incomplete_rules' => 0,
                'unresolved_exercises' => 0,
            ];

            foreach ($rules as $key => $legacyRule) {
                $courseCode = substr($key, strlen('course:'));
                $course = $this->connection->fetchAssociative(
                    'SELECT id, code, title FROM course WHERE code = :code LIMIT 1',
                    ['code' => $courseCode]
                );
                if (false === $course) {
                    ++$summary['missing_course'];
                    $io->warning(sprintf('Course %s was not found; its completion rule was skipped.', $courseCode));

                    continue;
                }

                $courseId = (int) $course['id'];
                $expectedCourseId = (int) $legacyRule['course_id'];
                if ($courseId !== $expectedCourseId) {
                    ++$summary['course_id_mismatch'];
                    $io->warning(sprintf(
                        'Course %s has ID %d, but the verified audit expected ID %d; its completion rule was skipped.',
                        $courseCode,
                        $courseId,
                        $expectedCourseId
                    ));

                    continue;
                }

                $rule = $this->normalizeRule($courseCode, $legacyRule);
                $unresolvedCount = (int) $rule['unresolved_exercise_count'];
                if ($unresolvedCount > 0) {
                    ++$summary['incomplete_rules'];
                    $summary['unresolved_exercises'] += $unresolvedCount;
                    $io->warning(sprintf(
                        'Course %s has %d unresolved exercise mapping(s); the stored rule will fail closed.',
                        $courseCode,
                        $unresolvedCount
                    ));
                }

                $encodedRule = $this->encodeRule($rule);
                $existingValues = null === $fieldId
                    ? []
                    : $this->connection->fetchAllAssociative(
                        'SELECT id, field_value
                         FROM extra_field_values
                         WHERE field_id = :fieldId AND item_id = :courseId
                         ORDER BY id',
                        ['fieldId' => $fieldId, 'courseId' => $courseId]
                    );

                if (count($existingValues) > 1) {
                    ++$summary['conflicting_existing'];
                    $io->warning(sprintf(
                        'Course %s has multiple course completion rule values; no value was changed.',
                        $courseCode
                    ));

                    continue;
                }

                if (1 === count($existingValues)) {
                    $existingRule = $this->decodeRule((string) $existingValues[0]['field_value']);
                    if (null !== $existingRule && $existingRule == $rule) {
                        ++$summary['already_configured'];
                        $io->writeln(sprintf('Already configured: %s', $courseCode));

                        continue;
                    }

                    $existingSource = null === $existingRule
                        ? ''
                        : trim((string) ($existingRule['source'] ?? ''));
                    if (!$force || self::SOURCE !== $existingSource) {
                        ++$summary['conflicting_existing'];
                        $io->warning(sprintf(
                            'Course %s already has a different completion rule; use --force only for values created by this command.',
                            $courseCode
                        ));

                        continue;
                    }

                    if ($dryRun) {
                        ++$summary['updated'];
                        $io->writeln(sprintf('Would update: %s', $courseCode));

                        continue;
                    }

                    $this->connection->update(
                        'extra_field_values',
                        [
                            'field_value' => $encodedRule,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ],
                        ['id' => (int) $existingValues[0]['id']]
                    );
                    ++$summary['updated'];
                    $io->writeln(sprintf('Updated: %s', $courseCode));

                    continue;
                }

                if ($dryRun) {
                    ++$summary['configured'];
                    $io->writeln(sprintf('Would configure: %s', $courseCode));

                    continue;
                }

                if (null === $fieldId) {
                    throw new RuntimeException('The course completion rule field could not be resolved.');
                }

                $now = date('Y-m-d H:i:s');
                $this->connection->insert('extra_field_values', [
                    'field_id' => $fieldId,
                    'field_value' => $encodedRule,
                    'item_id' => $courseId,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'comment' => null,
                    'asset_id' => null,
                ]);
                ++$summary['configured'];
                $io->writeln(sprintf('Configured: %s', $courseCode));
            }

            $io->definitionList(
                ['Mode' => $dryRun ? 'dry-run' : 'write'],
                ['Selected rules' => $summary['selected_rules']],
                ['Configured' => $summary['configured']],
                ['Updated' => $summary['updated']],
                ['Already configured' => $summary['already_configured']],
                ['Conflicting existing' => $summary['conflicting_existing']],
                ['Missing courses' => $summary['missing_course']],
                ['Course ID mismatches' => $summary['course_id_mismatch']],
                ['Incomplete rules' => $summary['incomplete_rules']],
                ['Unresolved exercises' => $summary['unresolved_exercises']]
            );

            if ($summary['conflicting_existing'] > 0 || $summary['course_id_mismatch'] > 0) {
                $io->error('Some completion rules were not migrated because safety checks failed.');

                return Command::FAILURE;
            }

            $io->success($dryRun
                ? 'Ricky completion rule dry-run completed without changing data.'
                : 'Ricky completion rules were persisted as generic course configuration.'
            );

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    private function assertRequiredTables(): void
    {
        $tableNames = array_map(
            'strtolower',
            $this->connection->createSchemaManager()->listTableNames()
        );

        foreach (['course', 'extra_field', 'extra_field_values'] as $tableName) {
            if (!\in_array($tableName, $tableNames, true)) {
                throw new RuntimeException(sprintf('Required table %s was not found.', $tableName));
            }
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function selectRules(string $requestedCourseCode): array
    {
        if ('' === $requestedCourseCode) {
            return self::RULES;
        }

        $key = 'course:'.$requestedCourseCode;
        if (!isset(self::RULES[$key])) {
            throw new RuntimeException(sprintf(
                'No Ricky legacy completion rule is configured for course %s.',
                $requestedCourseCode
            ));
        }

        return [$key => self::RULES[$key]];
    }

    private function getOrCreateCourseRuleField(bool $dryRun): ?int
    {
        $fieldId = $this->connection->fetchOne(
            'SELECT id
             FROM extra_field
             WHERE item_type = :itemType AND variable = :variable
             LIMIT 1',
            [
                'itemType' => ExtraField::COURSE_FIELD_TYPE,
                'variable' => CourseCompletionRuleEvaluator::COURSE_RULE_FIELD_VARIABLE,
            ]
        );

        if (false !== $fieldId && (int) $fieldId > 0) {
            return (int) $fieldId;
        }

        if ($dryRun) {
            return null;
        }

        $fieldOrder = (int) $this->connection->fetchOne(
            'SELECT COALESCE(MAX(field_order), 0) + 1 FROM extra_field WHERE item_type = :itemType',
            ['itemType' => ExtraField::COURSE_FIELD_TYPE]
        );
        $this->connection->insert('extra_field', [
            'item_type' => ExtraField::COURSE_FIELD_TYPE,
            'value_type' => ExtraField::FIELD_TYPE_TEXTAREA,
            'variable' => CourseCompletionRuleEvaluator::COURSE_RULE_FIELD_VARIABLE,
            'display_text' => 'Course completion rule',
            'helper_text' => null,
            'default_value' => null,
            'field_order' => $fieldOrder,
            'visible_to_self' => 0,
            'visible_to_others' => 0,
            'changeable' => 0,
            'filter' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'auto_remove' => 0,
            'description' => 'JSON configuration for weighted course completion evaluation.',
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @param array<string, mixed> $legacyRule
     *
     * @return array<string, mixed>
     */
    private function normalizeRule(string $courseCode, array $legacyRule): array
    {
        $components = [];

        $forum = $legacyRule['forum'] ?? null;
        if (\is_array($forum)) {
            foreach ($forum['thread_ids'] as $threadId) {
                $components[] = [
                    'type' => 'forum',
                    'resource_id' => (int) $threadId,
                    'source_resource_id' => (int) $threadId,
                    'weight' => (float) $forum['two_plus_points'],
                    'calculation' => 'forum_post_count_points',
                    'one_post_points' => (float) $forum['one_post_points'],
                    'two_plus_points' => (float) $forum['two_plus_points'],
                    'status' => 'evaluated',
                ];
            }
        }

        foreach ($legacyRule['works'] as $workId => $weight) {
            $components[] = [
                'type' => 'work',
                'resource_id' => (int) $workId,
                'source_resource_id' => (int) $workId,
                'weight' => (float) $weight,
                'calculation' => 'percentage_weighted',
                'status' => 'evaluated',
            ];
        }

        foreach ($legacyRule['evaluations'] as $evaluationId => $weight) {
            $components[] = [
                'type' => 'evaluation',
                'resource_id' => (int) $evaluationId,
                'source_resource_id' => (int) $evaluationId,
                'weight' => (float) $weight,
                'calculation' => 'percentage_weighted',
                'status' => 'evaluated',
            ];
        }

        $unresolvedExerciseCount = 0;
        foreach ($legacyRule['exercises'] as $exercise) {
            $legacyId = (int) $exercise['legacy_id'];
            $currentId = null === $exercise['current_id'] ? null : (int) $exercise['current_id'];
            if (null === $currentId) {
                ++$unresolvedExerciseCount;
            }

            $components[] = [
                'type' => 'exercise',
                'resource_id' => $currentId,
                'source_resource_id' => $legacyId,
                'tracking_resource_ids' => null === $currentId
                    ? []
                    : array_values(array_unique([$legacyId, $currentId])),
                'weight' => (float) $exercise['weight'],
                'calculation' => 'best_percentage',
                'status' => (string) $exercise['mapping_status'],
                'confidence' => (string) $exercise['mapping_confidence'],
            ];
        }

        return [
            'version' => CourseCompletionRuleEvaluator::RULE_VERSION,
            'source' => self::SOURCE,
            'source_course_code' => $courseCode,
            'source_course_id' => (int) $legacyRule['course_id'],
            'legacy_source_sha256' => self::LEGACY_SOURCE_SHA256,
            'audit_report_sha256' => self::AUDIT_REPORT_SHA256,
            'migration_complete' => 0 === $unresolvedExerciseCount,
            'unresolved_exercise_count' => $unresolvedExerciseCount,
            'components' => $components,
        ];
    }

    /** @param array<string, mixed> $rule */
    private function encodeRule(array $rule): string
    {
        try {
            return json_encode($rule, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Could not encode a Ricky completion rule.', 0, $exception);
        }
    }

    /** @return array<string, mixed>|null */
    private function decodeRule(string $rawRule): ?array
    {
        $rawRule = trim($rawRule);
        if ('' === $rawRule) {
            return null;
        }

        try {
            $rule = json_decode($rawRule, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return \is_array($rule) ? $rule : null;
    }
}
