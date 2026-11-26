<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingSectionQueryService;
use Chamilo\CoreBundle\State\LearningPath\LearningPathReportingProvider;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\Tests\ChamiloTestTrait;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LearningPathReportingScoresTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use GenericRowInsertTrait;
    use RawReportingFixturesTrait;
    use ReportingResourceFixturesTrait;

    public function testOnlyEvaluableItemsContributeToScoresButAllItemsContributeToTime(): void
    {
        self::bootKernel();
        $learner = $this->rawUser('lp_score');
        $this->courseUser($learner, 1);
        $lpId = $this->newLp('r3_lp_evaluable');
        $quiz = $this->newQuiz('r3_lp_evaluable_quiz');
        $quizItem = $this->newLpItem($lpId);
        $this->conn()->update('c_lp_item', ['max_score' => 10, 'path' => (string) $quiz], ['iid' => $quizItem]);
        $document = $this->newLpItem($lpId, 'document');
        $video = $this->newLpItem($lpId, 'video');
        $view = $this->lpView($learner, $lpId, 100);
        $this->lpItemView($view, $quizItem, 10, '10', 10);
        $quizView = (int) $this->conn()->lastInsertId();
        $this->lpItemView($view, $document, 0, '100', 20);
        $this->lpItemView($view, $video, 0, '100', 30);
        $this->conn()->update('c_lp', ['use_max_score' => 0], ['iid' => $lpId]);

        $legacy = $this->exerciseAttempt($learner, $quiz, 1, 10, null, 1, '2001-01-01 10:00:00', $lpId);
        $completed = $this->exerciseAttempt($learner, $quiz, 10, 10, null, 1, '2001-01-02 10:00:00', $lpId);
        $incomplete = $this->exerciseAttempt($learner, $quiz, 0, 10, null, 1, '2001-01-03 10:00:00', $lpId);
        foreach ([$legacy => '', $completed => 'completed', $incomplete => 'incomplete'] as $id => $status) {
            $this->conn()->update('track_e_exercises', [
                'status' => $status,
                'orig_lp_item_id' => $quizItem,
                'orig_lp_item_view_id' => $quizView,
            ], ['exe_id' => $id]);
        }

        $this->fresh();
        $course = $this->getEntityManager()->find(Course::class, 1);
        $lp = $this->getEntityManager()->find(CLp::class, $lpId);
        $user = $this->entityUser($learner);
        $provider = self::getContainer()->get(LearningPathReportingProvider::class);
        $metricsMethod = new ReflectionMethod($provider, 'getMetrics');
        $detailMethod = new ReflectionMethod($provider, 'buildDetail');
        $metrics = $metricsMethod->invoke($provider, $lp, $course, null, [$learner])[$learner];
        self::assertSame(100.0, $metrics['score']);
        self::assertSame(100, $metrics['progress']);
        self::assertSame(60, $metrics['timeSeconds']);

        $detail = $detailMethod->invoke($provider, $lp, $course, null, null, $user, $metrics);
        $items = array_column($detail['items'], null, 'id');
        self::assertSame($completed, $items[$quizItem]['attempts'][0]['exerciseAttemptId']);
        self::assertSame(10.0, $items[$quizItem]['attempts'][0]['score']);
        self::assertNull($items[$document]['attempts'][0]['maxScore']);
        self::assertNull($items[$video]['attempts'][0]['score']);
        self::assertSame(30, $items[$video]['attempts'][0]['timeSeconds']);

        // The same item-type selection applies to the global reporting detail and course summary.
        $section = self::getContainer()->get(GlobalReportingSectionQueryService::class);
        $rows = (new ReflectionMethod($section, 'learningPathDetailRows'))->invoke($section, $learner, 1, 0);
        $row = array_column($rows, null, 'id')[$lpId];
        self::assertSame(100.0, $row['score']);
        self::assertSame(60, $row['timeSeconds']);
        $scores = (new ReflectionMethod($section, 'learningPathScoreByCourse'))->invoke($section, $learner, [1], 0);
        self::assertSame(100.0, $scores[1]);

        $lp->setUseMaxScore(1);
        self::assertSame(100.0, $metricsMethod->invoke($provider, $lp, $course, null, [$learner])[$learner]['score']);
    }
}
