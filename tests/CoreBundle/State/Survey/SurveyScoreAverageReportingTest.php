<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\Survey;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\State\Survey\SurveyReportingProvider;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class SurveyScoreAverageReportingTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $container->get('request_stack')->push($request);

        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::setSession($session);
        Container::setRequest($request);
    }

    public function testAveragesAreCalculatedForEachCriterionAndForTheQuestion(): void
    {
        $question = $this->question(101, 'score', 5);
        $quality = $this->option(201, $question, 'Quality');
        $clarity = $this->option(202, $question, 'Clarity');
        $answers = [
            $this->answer($question, 201, 3, 'student1'),
            $this->answer($question, 201, 5, 'student2'),
            $this->answer($question, 201, 4, 'student3'),
            $this->answer($question, 202, 2, 'student1'),
            $this->answer($question, 202, 4, 'student2'),
        ];

        $report = $this->report($question, [$quality, $clarity], $answers);

        self::assertSame(3.6, $report['scoreAverage']);
        self::assertSame(5, $report['scoreMax']);
        self::assertSame(3, $report['totalAnswers']);
        self::assertSame(4.0, $report['scoreRows'][0]['averageScore']);
        self::assertSame(3, $report['scoreRows'][0]['responseCount']);
        self::assertSame(3.0, $report['scoreRows'][1]['averageScore']);
        self::assertSame(2, $report['scoreRows'][1]['responseCount']);
        self::assertSame(['score' => 3, 'count' => 1], $report['scoreRows'][0]['distribution'][2]);
        self::assertSame(['score' => 4, 'count' => 1], $report['scoreRows'][0]['distribution'][3]);
    }

    public function testSkippedInvalidAndUnknownOptionScoresDoNotDistortAverages(): void
    {
        $question = $this->question(102, 'score', 5);
        $quality = $this->option(203, $question, 'Quality');
        $clarity = $this->option(204, $question, 'Clarity');
        $answers = [
            $this->answer($question, 203, 0, 'student1'),
            $this->answer($question, 203, -1, 'student2'),
            $this->answer($question, 203, 6, 'student3'),
            $this->answer($question, 999, 5, 'student4'),
            $this->answer($question, 203, 4, 'student5'),
        ];

        $report = $this->report($question, [$quality, $clarity], $answers);

        self::assertSame(4.0, $report['scoreAverage']);
        self::assertSame(4.0, $report['scoreRows'][0]['averageScore']);
        self::assertSame(1, $report['scoreRows'][0]['responseCount']);
        self::assertSame(['score' => 4, 'count' => 1], $report['scoreRows'][0]['distribution'][3]);
        self::assertNull($report['scoreRows'][1]['averageScore']);
        self::assertSame(0, $report['scoreRows'][1]['responseCount']);
    }

    public function testQuestionsWithoutScoresHaveNoArtificialZeroAverage(): void
    {
        $question = $this->question(103, 'score', 5);
        $report = $this->report($question, [$this->option(205, $question, 'Quality')], []);

        self::assertNull($report['scoreAverage']);
        self::assertNull($report['scoreRows'][0]['averageScore']);
        self::assertSame(0, $report['scoreRows'][0]['responseCount']);
        self::assertSame(5, \count($report['scoreRows'][0]['distribution']));
    }

    public function testNonScoreQuestionsKeepTheirExistingReportingShape(): void
    {
        $question = $this->question(104, 'multiplechoice', 0);
        $report = $this->report($question, [$this->option(206, $question, 'Yes')], []);

        self::assertNull($report['scoreAverage']);
        self::assertNull($report['scoreMax']);
        self::assertSame([], $report['scoreRows']);
        self::assertCount(1, $report['options']);
    }

    public function testMaximumScoreTwentyUsesActualStoredScale(): void
    {
        $question = $this->question(105, 'score', 20);
        $report = $this->report($question, [$this->option(207, $question, 'Content')], [
            $this->answer($question, 207, 2, 'student1'),
            $this->answer($question, 207, 12, 'student2'),
        ]);

        self::assertSame(7.0, $report['scoreAverage']);
        self::assertSame(20, $report['scoreMax']);
        self::assertSame(7.0, $report['scoreRows'][0]['averageScore']);
        self::assertCount(20, $report['scoreRows'][0]['distribution']);
    }

    private function question(int $id, string $type, int $maxValue): CSurveyQuestion
    {
        $question = (new CSurveyQuestion())
            ->setSurveyQuestion('How do you rate this activity?')
            ->setSurveyQuestionComment('')
            ->setType($type)
            ->setMaxValue($maxValue)
        ;
        (new ReflectionProperty(CSurveyQuestion::class, 'iid'))->setValue($question, $id);

        return $question;
    }

    private function option(int $id, CSurveyQuestion $question, string $label): CSurveyQuestionOption
    {
        $option = (new CSurveyQuestionOption())
            ->setQuestion($question)
            ->setOptionText($label)
        ;
        (new ReflectionProperty(CSurveyQuestionOption::class, 'iid'))->setValue($option, $id);

        return $option;
    }

    private function answer(CSurveyQuestion $question, int $optionId, int $score, string $user): CSurveyAnswer
    {
        return (new CSurveyAnswer())
            ->setQuestion($question)
            ->setOptionId((string) $optionId)
            ->setValue($score)
            ->setUser($user)
        ;
    }

    /**
     * @param array<int, CSurveyQuestionOption> $options
     * @param array<int, CSurveyAnswer>         $answers
     *
     * @return array<string, mixed>
     */
    private function report(CSurveyQuestion $question, array $options, array $answers): array
    {
        $provider = (new ReflectionClass(SurveyReportingProvider::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(SurveyReportingProvider::class, 'buildQuestionReports');
        $reports = $method->invoke($provider, [$question], [(int) $question->getIid() => $options], $answers);

        return $reports[0];
    }
}
