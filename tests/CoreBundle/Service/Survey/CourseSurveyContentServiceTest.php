<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Survey;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Survey\CourseSurveyContentService;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class CourseSurveyContentServiceTest extends KernelTestCase
{
    private CourseSurveyContentService $service;
    private EntityManagerInterface $entityManager;
    private Course $course;
    private CSurvey $survey;
    private CSurveyQuestion $question;
    private CSurveyQuestionOption $option;
    private string $originalSurveyTitle;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::setSession(new Session(new MockArraySessionStorage()));

        $entityManager = $container->get(EntityManagerInterface::class);
        $course = $entityManager->getRepository(Course::class)->find(2);
        $user = $entityManager->getRepository(User::class)->find(1);
        if (!$course instanceof Course || !$user instanceof User) {
            self::markTestSkipped('Course #2 / User #1 fixtures are not present in this DB.');
        }

        $container->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($user, 'api', $user->getRoles())
        );

        $this->service = $container->get(CourseSurveyContentService::class);
        $this->entityManager = $entityManager;
        $this->course = $course;

        $suffix = bin2hex(random_bytes(6));
        $this->originalSurveyTitle = 'Survey English Title '.$suffix;

        $survey = (new CSurvey())
            ->setCode('mcp'.$suffix)
            ->setTitle($this->originalSurveyTitle)
            ->setSubtitle('')
            ->setLang('en_US')
            ->setAvailFrom(new DateTime())
            ->setAvailTill((new DateTime())->modify('+1 year'))
            ->setIsShared('0')
            ->setTemplate('template')
            ->setIntro('<p>Original intro</p>')
            ->setSurveythanks('<p>Thanks</p>')
            ->setAnonymous('1')
            ->setVisibleResults(0)
            ->setDisplayQuestionNumber(true)
            ->setOneQuestionPerPage(false)
            ->setShuffle(false)
            ->setDuration(null)
            ->setSurveyType(0)
            ->setShowFormProfile(0)
            ->setFormFields('')
            ->setParent($course)
            ->setCreator($user)
            ->addCourseLink($course)
        ;
        $entityManager->persist($survey);
        $entityManager->flush();

        $question = (new CSurveyQuestion())
            ->setSurvey($survey)
            ->setSurveyQuestion('<p>Original question</p>')
            ->setSurveyQuestionComment('')
            ->setType('yesno')
            ->setDisplay('vertical')
            ->setSort(1)
            ->setSharedQuestionId(0)
            ->setMaxValue(0)
            ->setIsMandatory(true)
        ;
        $entityManager->persist($question);
        $entityManager->flush();

        $option = (new CSurveyQuestionOption())
            ->setSurvey($survey)
            ->setQuestion($question)
            ->setOptionText('<p>Yes</p>')
            ->setSort(1)
            ->setValue(1)
        ;
        $entityManager->persist($option);
        $entityManager->flush();

        $this->survey = $survey;
        $this->question = $question;
        $this->option = $option;
    }

    public function testEditSurveyDescriptionLeavesTitleUntouched(): void
    {
        $result = $this->service->editSurveyDescription(
            $this->course,
            $this->survey,
            '<p>Updated intro with <strong>HTML</strong>.</p>',
        );

        self::assertTrue($result['updated']);
        self::assertSame($this->originalSurveyTitle, $result['survey']['title']);
        self::assertStringContainsString('Updated intro', $result['survey']['description']);

        $this->entityManager->refresh($this->survey);
        self::assertSame($this->originalSurveyTitle, $this->survey->getTitle());
        self::assertStringContainsString('Updated intro', (string) $this->survey->getIntro());
    }

    public function testEditQuestionAndAnswerDescriptions(): void
    {
        $questionResult = $this->service->editQuestionDescription(
            $this->course,
            $this->survey,
            (int) $this->question->getIid(),
            '<p>Updated survey question body.</p>',
        );
        self::assertStringContainsString('Updated survey question body.', $questionResult['question']['description']);

        $answerResult = $this->service->editAnswerDescription(
            $this->course,
            $this->survey,
            (int) $this->question->getIid(),
            (int) $this->option->getIid(),
            '<p>Updated option body.</p>',
        );
        self::assertStringContainsString('Updated option body.', $answerResult['answer']['description']);
        self::assertSame(1, $answerResult['answer']['value']);

        $listed = $this->service->listQuestions($this->survey);
        self::assertCount(1, $listed);
        self::assertStringContainsString('Updated survey question body.', $listed[0]->getSurveyQuestion());
    }

    public function testResolveSurveyByTitle(): void
    {
        $resolved = $this->service->resolveSurvey($this->course, null, $this->originalSurveyTitle);
        self::assertSame($this->survey->getIid(), $resolved->getIid());
    }
}
