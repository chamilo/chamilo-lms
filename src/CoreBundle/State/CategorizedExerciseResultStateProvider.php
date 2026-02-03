<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CategorizedExerciseResult;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Security\Authorization\Voter\TrackEExerciseVoter;
use Doctrine\ORM\EntityManagerInterface;
use Event;
use Exception;
use Exercise;
use ExerciseLib;
use Question;
use QuestionOptionsEvaluationPlugin;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use TestCategory;

use function count;

/**
 * @template-implements ProviderInterface<CategorizedExerciseResult>
 */
class CategorizedExerciseResultStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthorizationCheckerInterface $security,
        private readonly UserHelper $userHelper,
        private readonly RequestStack $requestStack
    ) {}

    /**
     * @throws Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $trackExercise = $this->entityManager->find(TrackEExercise::class, $uriVariables['exeId']);

        if (!$trackExercise) {
            return null;
        }

        if (!$this->security->isGranted(TrackEExerciseVoter::VIEW, $trackExercise)) {
            throw new Exception('Not allowed');
        }

        $course = $trackExercise->getCourse();

        $sessionHandler = $this->requestStack->getCurrentRequest()->getSession();
        $sessionHandler->set('_course', api_get_course_info_by_id($course->getId()));

        $objExercise = new Exercise($course->getId());
        $objExercise->read($trackExercise->getQuiz()->getIid());

        ob_start();

        $categoryList = $this->displayQuestionListByAttempt(
            $objExercise,
            $trackExercise
        );

        ob_end_clean();

        $stats = self::getStatsTableByAttempt($objExercise, $trackExercise, $categoryList);

        return new CategorizedExerciseResult($trackExercise, $stats);
    }

    /**
     * @throws Exception
     */
    private function displayQuestionListByAttempt(
        Exercise $objExercise,
        TrackEExercise $exerciseTracking
    ): array {
        $courseId = $exerciseTracking->getCourse()->getId();
        $sessionId = (int) $exerciseTracking->getSession()?->getId();

        $question_list = explode(',', $exerciseTracking->getDataTracking());
        $question_list = array_map('intval', $question_list);

        if ($objExercise->getResultAccess()) {
            $exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id(
                $exerciseTracking->getExeId()
            );

            if (false === $objExercise->hasResultsAccess($exercise_stat_info)) {
                throw new Exception(get_lang('You passed the time limit to see the results'), $objExercise->getResultsAccess());
            }
        }

        $total_score = $total_weight = 0;

        // Hide results
        $show_results = false;
        $show_only_score = false;

        if (\in_array(
            $objExercise->results_disabled,
            [
                RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
            ]
        )) {
            $show_results = true;
        }

        if (\in_array(
            $objExercise->results_disabled,
            [
                RESULT_DISABLE_SHOW_SCORE_ONLY,
                RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES,
                RESULT_DISABLE_RANKING,
            ]
        )) {
            $show_only_score = true;
        }

        // Not display expected answer, but score, and feedback
        if (RESULT_DISABLE_SHOW_SCORE_ONLY === $objExercise->results_disabled
            && EXERCISE_FEEDBACK_TYPE_END === $objExercise->getFeedbackType()
        ) {
            $show_results = true;
            $show_only_score = false;
        }

        $showTotalScoreAndUserChoicesInLastAttempt = true;
        $showTotalScore = true;

        if (\in_array(
            $objExercise->results_disabled,
            [
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
                RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
            ]
        )) {
            $show_only_score = true;
            $show_results = true;
            $numberAttempts = 0;

            if ($objExercise->attempts > 0) {
                $attempts = Event::getExerciseResultsByUser(
                    $this->userHelper->getCurrent()->getId(),
                    $objExercise->id,
                    $courseId,
                    $sessionId,
                    $exerciseTracking->getOrigLpId(),
                    $exerciseTracking->getOrigLpItemId(),
                    'desc'
                );

                if ($attempts) {
                    $numberAttempts = \count($attempts);
                }

                $showTotalScore = false;

                if (RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT === $objExercise->results_disabled) {
                    $showTotalScore = true;
                }

                $showTotalScoreAndUserChoicesInLastAttempt = false;

                if ($numberAttempts >= $objExercise->attempts) {
                    $showTotalScore = true;
                    $show_only_score = false;
                    $showTotalScoreAndUserChoicesInLastAttempt = true;
                }

                if (RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK === $objExercise->results_disabled) {
                    $showTotalScore = true;
                    $show_only_score = false;
                    $showTotalScoreAndUserChoicesInLastAttempt = false;

                    if ($numberAttempts >= $objExercise->attempts) {
                        $showTotalScoreAndUserChoicesInLastAttempt = true;
                    }

                    // Check if the current attempt is the last.
                    if (!empty($attempts)) {
                        $showTotalScoreAndUserChoicesInLastAttempt = false;
                        $position = 1;

                        foreach ($attempts as $attempt) {
                            if ($exerciseTracking->getExeId() === $attempt['exe_id']) {
                                break;
                            }

                            $position++;
                        }

                        if ($position === $objExercise->attempts) {
                            $showTotalScoreAndUserChoicesInLastAttempt = true;
                        }
                    }
                }
            }

            if (RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK ===
                $objExercise->results_disabled
            ) {
                $show_only_score = false;
                $showTotalScore = false;
                if ($numberAttempts >= $objExercise->attempts) {
                    $showTotalScore = true;
                }
            }
        }

        $category_list = [
            'none' => [
                'score' => 0,
                'total' => 0,
            ],
        ];
        $exerciseResultCoordinates = [];

        $result = [];
        // Loop over all question to show results for each of them, one by one
        foreach ($question_list as $questionId) {
            // Creates a temporary Question object
            $objQuestionTmp = Question::read($questionId, $objExercise->course);

            // We're inside *one* question. Go through each possible answer for this question
            $result = $objExercise->manage_answer(
                $exerciseTracking->getExeId(),
                $questionId,
                null,
                'exercise_result',
                $exerciseResultCoordinates,
                false,
                true,
                $show_results,
                $objExercise->selectPropagateNeg(),
                [],
                $showTotalScoreAndUserChoicesInLastAttempt
            );

            if (false === $result) {
                continue;
            }

            $total_score += $result['score'];
            $total_weight += $result['weight'];

            $my_total_score = $result['score'];
            $my_total_weight = $result['weight'];
            $scorePassed = ExerciseLib::scorePassed($my_total_score, $my_total_weight);

            // Category report
            $category_was_added_for_this_test = false;
            if (!empty($objQuestionTmp->category)) {
                if (!isset($category_list[$objQuestionTmp->category])) {
                    $category_list[$objQuestionTmp->category] = [
                        'score' => 0,
                        'total' => 0,
                        'total_questions' => 0,
                        'passed' => 0,
                        'wrong' => 0,
                        'no_answer' => 0,
                    ];
                }

                $category_list[$objQuestionTmp->category]['score'] += $my_total_score;
                $category_list[$objQuestionTmp->category]['total'] += $my_total_weight;

                if ($scorePassed) {
                    // Only count passed if score is not empty
                    if (!empty($my_total_score)) {
                        $category_list[$objQuestionTmp->category]['passed']++;
                    }
                } elseif ($result['user_answered']) {
                    $category_list[$objQuestionTmp->category]['wrong']++;
                } else {
                    $category_list[$objQuestionTmp->category]['no_answer']++;
                }

                $category_list[$objQuestionTmp->category]['total_questions']++;
                $category_was_added_for_this_test = true;
            }

            if (!empty($objQuestionTmp->category_list)) {
                foreach ($objQuestionTmp->category_list as $category_id) {
                    $category_list[$category_id]['score'] += $my_total_score;
                    $category_list[$category_id]['total'] += $my_total_weight;
                    $category_was_added_for_this_test = true;
                }
            }

            // No category for this question!
            if (!$category_was_added_for_this_test) {
                $category_list['none']['score'] += $my_total_score;
                $category_list['none']['total'] += $my_total_weight;
            }
        }

        if (($show_results || $show_only_score) && $showTotalScore) {
            if ($result
                && isset($result['answer_type'])
                && MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY !== $result['answer_type']
            ) {
                $pluginEvaluation = QuestionOptionsEvaluationPlugin::create();
                if ('true' === $pluginEvaluation->get(QuestionOptionsEvaluationPlugin::SETTING_ENABLE)) {
                    $formula = $pluginEvaluation->getFormulaForExercise($objExercise->getId());

                    if (!empty($formula)) {
                        $total_score = $pluginEvaluation->getResultWithFormula(
                            $exerciseTracking->getExeId(),
                            $formula
                        );
                        $total_weight = $pluginEvaluation->getMaxScore();
                    }
                }
            }
        }

        if ($this->isAllowedToSeeResults()) {
            $show_results = true;
        }

        if (!$show_results && !$show_only_score && RESULT_DISABLE_RADAR !== $objExercise->results_disabled) {
            throw new AccessDeniedException();
        }

        // Adding total
        $category_list['total'] = [
            'score' => $total_score,
            'total' => $total_weight,
        ];

        return $category_list;
    }

    private static function getStatsTableByAttempt(
        Exercise $exercise,
        TrackEExercise $exerciseTracking,
        array $category_list = []
    ): array {
        if (empty($category_list)) {
            return [];
        }

        $hide = (int) $exercise->getPageConfigurationAttribute('hide_category_table');

        if (1 === $hide) {
            return [];
        }

        $categoryNameList = TestCategory::getListOfCategoriesNameForTest(
            $exercise->iId,
            $exerciseTracking->getCourse()->getId()
        );

        if (empty($categoryNameList)) {
            return [];
        }

        $labelsWithId = array_column($categoryNameList, 'title', 'id');

        asort($labelsWithId);

        $stats = [];

        foreach ($labelsWithId as $category_id => $title) {
            if (!isset($category_list[$category_id])) {
                continue;
            }

            $absolute = ExerciseLib::show_score(
                $category_list[$category_id]['score'],
                $category_list[$category_id]['total'],
                false
            );
            $relative = ExerciseLib::show_score(
                $category_list[$category_id]['score'],
                $category_list[$category_id]['total'],
                true,
                false,
                true
            );

            $stats[] = [
                'title' => $title,
                'absolute' => strip_tags($absolute),
                'relative' => strip_tags($relative),
            ];
        }

        if (isset($category_list['none']) && $category_list['none']['score'] > 0) {
            $absolute = ExerciseLib::show_score(
                $category_list['none']['score'],
                $category_list['none']['total'],
                false
            );
            $relative = ExerciseLib::show_score(
                $category_list['none']['score'],
                $category_list['none']['total'],
                true,
                false,
                true
            );

            $stats[] = [
                'title' => get_lang('None'),
                'absolute' => strip_tags($absolute),
                'relative' => strip_tags($relative),
            ];
        }

        $absolute = ExerciseLib::show_score(
            $category_list['total']['score'],
            $category_list['total']['total'],
            false
        );
        $relative = ExerciseLib::show_score(
            $category_list['total']['score'],
            $category_list['total']['total'],
            true,
            false,
            true
        );

        $stats[] = [
            'title' => get_lang('Total'),
            'absolute' => strip_tags($absolute),
            'relative' => strip_tags($relative),
        ];

        return $stats;
    }

    private function isAllowedToSeeResults(): bool
    {
        $isStudentBoss = $this->security->isGranted('ROLE_STUDENT_BOSS');
        $isHRM = $this->security->isGranted('ROLE_HR');
        $isSessionAdmin = $this->security->isGranted('ROLE_SESSION_MANAGER');
        $isCourseTutor = $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
        $isAllowedToEdit = api_is_allowed_to_edit(null, true);

        return $isAllowedToEdit || $isCourseTutor || $isSessionAdmin || $isHRM || $isStudentBoss;
    }
}
