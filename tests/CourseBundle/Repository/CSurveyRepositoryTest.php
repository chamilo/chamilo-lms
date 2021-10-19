<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Chamilo\CourseBundle\Repository\CSurveyAnswerRepository;
use Chamilo\CourseBundle\Repository\CSurveyQuestionRepository;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CSurveyRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $surveyRepo = self::getContainer()->get(CSurveyRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $survey = (new CSurvey())
            ->setTitle('survey')
            ->setCode('survey')
            ->setParent($course)
            ->setCreator($teacher)
        ;

        $this->assertHasNoEntityViolations($survey);
        $em->persist($survey);
        $em->flush();

        $this->assertSame('survey', (string) $survey);
        $this->assertSame(1, $surveyRepo->count([]));
    }

    public function testCreateWithQuestions(): void
    {
        $em = $this->getEntityManager();

        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $surveyRepo = self::getContainer()->get(CSurveyRepository::class);
        $surveyQuestionRepo = self::getContainer()->get(CSurveyQuestionRepository::class);
        $surveyAnswerRepo = self::getContainer()->get(CSurveyAnswerRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $survey = (new CSurvey())
            ->setTitle('survey')
            ->setCode('survey')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $em->persist($survey);
        $em->flush();

        $question = (new CSurveyQuestion())
            ->setSurvey($survey)
            ->setSurveyQuestion('hola?')
            ->setIsMandatory(false)
            ->setDisplay('display')
            ->setSort(0)
            ->setSharedQuestionId(0)
            ->setSurveyQuestionComment('comment')
            ->setMaxValue(100)
            ->setSurveyGroupPri(0)
            ->setSurveyGroupSec1(0)
            ->setSurveyGroupSec2(0)
            ->setType('type')
        ;
        $this->assertHasNoEntityViolations($question);
        $em->persist($question);
        $em->flush();

        $questionOption = (new CSurveyQuestionOption())
            ->setQuestion($question)
            ->setValue(1)
            ->setSurvey($survey)
            ->setSort(1)
            ->setOptionText('option text')
        ;
        $this->assertHasNoEntityViolations($questionOption);
        $em->persist($questionOption);
        $em->flush();

        $answer = (new CSurveyAnswer())
            ->setSurvey($survey)
            ->setUser('1')
            ->setValue(1)
            ->setOptionId('1')
            ->setQuestion($question)
        ;
        $this->assertHasNoEntityViolations($answer);
        $em->persist($answer);
        $em->flush();

        $em->clear();

        /** @var CSurvey $survey */
        $survey = $surveyRepo->find($survey->getIid());
        /** @var CSurveyQuestion $question */
        $question = $surveyQuestionRepo->find($question->getIid());

        $this->assertSame(1, $survey->getQuestions()->count());
        $this->assertSame(1, $question->getOptions()->count());
        $this->assertSame(1, $question->getAnswers()->count());

        $this->assertSame(1, $surveyRepo->count([]));
        $this->assertSame(1, $surveyQuestionRepo->count([]));
        $this->assertSame(1, $surveyAnswerRepo->count([]));

        $this->assertSame(1, $courseRepo->count([]));

        /** @var Course $course */
        $course = $courseRepo->find($course->getId());
        $courseRepo->delete($course);

        $this->assertSame(0, $courseRepo->count([]));
        $this->assertSame(0, $surveyRepo->count([]));
        $this->assertSame(0, $surveyQuestionRepo->count([]));
        $this->assertSame(0, $surveyAnswerRepo->count([]));
    }
}
