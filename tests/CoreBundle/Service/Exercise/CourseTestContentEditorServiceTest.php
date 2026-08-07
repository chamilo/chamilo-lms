<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Exercise\CourseTestContentEditorService;
use Chamilo\CoreBundle\Service\Exercise\CourseTestReaderService;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use const UNIQUE_ANSWER;

final class CourseTestContentEditorServiceTest extends KernelTestCase
{
    private CourseTestContentEditorService $editor;
    private CourseTestReaderService $reader;
    private EntityManagerInterface $entityManager;
    private Course $course;
    private CQuiz $quiz;
    private CQuizQuestion $question;
    private CQuizAnswer $answer;
    private string $originalQuestionTitle;

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

        $this->editor = $container->get(CourseTestContentEditorService::class);
        $this->reader = $container->get(CourseTestReaderService::class);
        $this->entityManager = $entityManager;
        $this->course = $course;

        $suffix = bin2hex(random_bytes(6));
        $this->originalQuestionTitle = 'English title '.$suffix;

        $quiz = (new CQuiz())
            ->setTitle('Editor test '.$suffix)
            ->setDescription('')
            ->setRandomAnswers(false)
            ->setParent($course)
            ->setCreator($user)
            ->addCourseLink($course)
        ;
        $entityManager->persist($quiz);

        $question = (new CQuizQuestion())
            ->setQuestion($this->originalQuestionTitle)
            ->setDescription('<p>Original description</p>')
            ->setPonderation(1.0)
            ->setPosition(1)
            ->setType(UNIQUE_ANSWER)
            ->setLevel(1)
            ->setParent($course)
            ->setCreator($user)
            ->addCourseLink($course)
        ;
        $entityManager->persist($question);

        $answer = (new CQuizAnswer())
            ->setQuestion($question)
            ->setAnswer('<p>Original answer</p>')
            ->setCorrect(1)
            ->setComment('Keep this feedback')
            ->setPonderation(1.0)
            ->setPosition(1)
        ;
        $entityManager->persist($answer);

        $relation = (new CQuizRelQuestion())
            ->setQuiz($quiz)
            ->setQuestion($question)
            ->setQuestionOrder(1)
        ;
        $entityManager->persist($relation);
        $entityManager->flush();

        $this->quiz = $quiz;
        $this->question = $question;
        $this->answer = $answer;
    }

    public function testEditQuestionDescriptionLeavesTitleUntouched(): void
    {
        $result = $this->editor->editQuestionDescription(
            $this->quiz,
            (int) $this->question->getIid(),
            '<p>Updated multilingual description.</p>',
        );

        self::assertTrue($result['updated']);
        self::assertStringContainsString('Updated multilingual description.', $result['question']['description']);

        $this->entityManager->refresh($this->question);
        self::assertSame($this->originalQuestionTitle, $this->question->getQuestion());
        self::assertStringContainsString('Updated multilingual description.', (string) $this->question->getDescription());
    }

    public function testEditAnswerDescriptionLeavesScoreAndFeedbackUntouched(): void
    {
        $result = $this->editor->editAnswerDescription(
            $this->quiz,
            (int) $this->question->getIid(),
            (int) $this->answer->getIid(),
            '<p>Updated answer body.</p>',
        );

        self::assertTrue($result['updated']);
        self::assertStringContainsString('Updated answer body.', $result['answer']['text']);
        self::assertSame('Keep this feedback', $result['answer']['feedback']);
        self::assertTrue($result['answer']['is_correct']);
        self::assertSame(1.0, $result['answer']['score']);

        $this->entityManager->refresh($this->answer);
        self::assertStringContainsString('Updated answer body.', $this->answer->getAnswer());
        self::assertSame('Keep this feedback', $this->answer->getComment());
        self::assertSame(1, $this->answer->getCorrect());
    }

    public function testReaderStillFindsTheEditedQuestion(): void
    {
        $this->editor->editQuestionDescription(
            $this->quiz,
            (int) $this->question->getIid(),
            '<div class="mce-translatehtml" lang="fr_FR"><p>Bonjour</p></div>',
        );

        $resolved = $this->reader->resolveQuestionWithPosition($this->quiz, (int) $this->question->getIid());
        self::assertStringContainsString('Bonjour', (string) $resolved['question']->getDescription());
    }
}
