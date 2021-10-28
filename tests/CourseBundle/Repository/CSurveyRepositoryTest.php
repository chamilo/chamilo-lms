<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Chamilo\CourseBundle\Repository\CSurveyAnswerRepository;
use Chamilo\CourseBundle\Repository\CSurveyInvitationRepository;
use Chamilo\CourseBundle\Repository\CSurveyQuestionRepository;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class CSurveyRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $surveyRepo = self::getContainer()->get(CSurveyRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $survey = (new CSurvey())
            ->setTitle('survey')
            ->setCode('survey')
            ->setSubtitle('subtitle')
            ->setSurveythanks('thanks')
            ->setIsMandatory(false)
            ->setSurveyType(1)
            ->setSurveyVersion('v1')
            ->setAccessCondition('condition')
            ->setShuffle(false)
            ->setTemplate('tpl')
            ->setAnonymous('0')
            ->setVisibleResults(1)
            ->setReminderMail('reminder')
            ->setRgt(1)
            ->setMailSubject('subject')
            ->setInviteMail('invite')
            ->setLang('lang')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;

        $this->assertHasNoEntityViolations($survey);
        $em->persist($survey);
        $em->flush();

        $this->assertSame('survey', (string) $survey);
        $this->assertSame(1, $surveyRepo->count([]));

        $qb = $surveyRepo->findAllByCourse($course);
        $this->assertCount(1, $qb->getQuery()->getResult());

        $courseRepo->delete($course);
        $this->assertSame(0, $surveyRepo->count([]));
    }

    public function testCreateWithQuestions(): void
    {
        $em = $this->getEntityManager();

        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $surveyRepo = self::getContainer()->get(CSurveyRepository::class);
        $surveyQuestionRepo = self::getContainer()->get(CSurveyQuestionRepository::class);
        $surveyAnswerRepo = self::getContainer()->get(CSurveyAnswerRepository::class);
        $surveyInvitationRepo = self::getContainer()->get(CSurveyInvitationRepository::class);
        $surveyOptionRepo = $em->getRepository(CSurveyQuestionOption::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');
        $student = $this->createUser('student');

        $survey = (new CSurvey())
            ->setTitle('survey')
            ->setCode('survey')
            ->setAvailFrom(new DateTime())
            ->setAvailTill(new DateTime('now +30 days'))
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

        $option = (new CSurveyQuestionOption())
            ->setSurvey($survey)
            ->setQuestion($question)
            ->setOptionText('text')
            ->setValue(1)
            ->setSort(1)
        ;
        $em->persist($option);
        $this->assertHasNoEntityViolations($option);
        $em->flush();

        $this->assertSame('hola?', $question->getSurveyQuestion());

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

        $invitation = (new CSurveyInvitation())
            ->setCourse($course)
            ->setUser($student)
            ->setGroup(null)
            ->setSession(null)
            ->setSurvey($survey)
            ->setInvitationCode('code')
            ->setInvitationDate(new DateTime())
            ->setAnswered(0)
            ->setReminderDate(new DateTime())
        ;
        $em->persist($invitation);

        $em->flush();
        $em->clear();

        /** @var CSurvey $survey */
        $survey = $surveyRepo->find($survey->getIid());
        /** @var CSurveyQuestion $question */
        $question = $surveyQuestionRepo->find($question->getIid());

        $this->assertSame(1, $survey->getInvitations()->count());
        $this->assertSame(1, $survey->getQuestions()->count());
        $this->assertSame(1, $question->getOptions()->count());
        $this->assertSame(1, $question->getAnswers()->count());

        $this->assertSame(1, $surveyRepo->count([]));
        $this->assertSame(1, $surveyQuestionRepo->count([]));
        $this->assertSame(1, $surveyAnswerRepo->count([]));
        $this->assertSame(1, $surveyInvitationRepo->count([]));
        $this->assertSame(1, $surveyOptionRepo->count([]));
        $this->assertSame(1, $courseRepo->count([]));

        $invitations = $surveyInvitationRepo->getUserPendingInvitations($student);
        $this->assertCount(1, $invitations);

        $course = $this->getCourse($course->getId());
        $courseRepo->delete($course);

        $this->assertSame(0, $courseRepo->count([]));
        $this->assertSame(0, $surveyRepo->count([]));
        $this->assertSame(0, $surveyQuestionRepo->count([]));
        $this->assertSame(0, $surveyAnswerRepo->count([]));
        $this->assertSame(0, $surveyInvitationRepo->count([]));
        $this->assertSame(0, $surveyOptionRepo->count([]));
    }
}
