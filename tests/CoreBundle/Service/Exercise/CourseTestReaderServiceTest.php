<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Exercise\CourseTestReaderService;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use const UNIQUE_ANSWER;

final class CourseTestReaderServiceTest extends KernelTestCase
{
    private CourseTestReaderService $reader;
    private EntityManagerInterface $entityManager;
    private Course $course;
    private CQuiz $quiz;
    private CQuizQuestion $question;

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

        $this->reader = $container->get(CourseTestReaderService::class);
        $this->entityManager = $entityManager;
        $this->course = $course;

        $suffix = bin2hex(random_bytes(6));

        $quizCategory = (new CQuizCategory())
            ->setTitle('Test category '.$suffix)
            ->setCourse($course)
            ->setParent($course)
            ->setCreator($user)
            ->addCourseLink($course)
        ;
        $entityManager->persist($quizCategory);

        $quiz = (new CQuiz())
            ->setTitle('Reader test '.$suffix)
            ->setDescription('A test used by CourseTestReaderServiceTest.')
            ->setRandomAnswers(false)
            ->setQuizCategory($quizCategory)
            ->setParent($course)
            ->setCreator($user)
            ->addCourseLink($course)
        ;
        $entityManager->persist($quiz);

        $questionCategory = (new CQuizQuestionCategory())
            ->setTitle('Question category '.$suffix)
            ->setParent($course)
            ->setCreator($user)
            ->addCourseLink($course)
        ;
        $entityManager->persist($questionCategory);

        $question = (new CQuizQuestion())
            ->setQuestion('What is the answer, '.$suffix.'?')
            ->setPonderation(5.0)
            ->setPosition(1)
            ->setType(UNIQUE_ANSWER)
            ->setLevel(1)
            ->setParent($course)
            ->setCreator($user)
            ->addCourseLink($course)
        ;
        $question->addCategory($questionCategory);
        $entityManager->persist($question);

        $correctAnswer = (new CQuizAnswer())
            ->setQuestion($question)
            ->setAnswer('Correct option')
            ->setCorrect(1)
            ->setComment('Well done!')
            ->setPonderation(5.0)
            ->setPosition(1)
        ;
        $entityManager->persist($correctAnswer);

        $wrongAnswer = (new CQuizAnswer())
            ->setQuestion($question)
            ->setAnswer('Wrong option')
            ->setCorrect(0)
            ->setComment('Not quite.')
            ->setPonderation(0.0)
            ->setPosition(2)
        ;
        $entityManager->persist($wrongAnswer);

        $question->getAnswers()->add($correctAnswer);
        $question->getAnswers()->add($wrongAnswer);

        $relation = (new CQuizRelQuestion())
            ->setQuiz($quiz)
            ->setQuestion($question)
            ->setQuestionOrder(1)
        ;
        $entityManager->persist($relation);
        $quiz->getQuestions()->add($relation);

        $entityManager->flush();

        $this->quiz = $quiz;
        $this->question = $question;
    }

    public function testResolveQuizByIdAndByTitle(): void
    {
        $byId = $this->reader->resolveQuiz($this->course, (int) $this->quiz->getIid(), null);
        self::assertSame($this->quiz->getIid(), $byId->getIid());

        $byTitle = $this->reader->resolveQuiz($this->course, null, $this->quiz->getTitle());
        self::assertSame($this->quiz->getIid(), $byTitle->getIid());
    }

    public function testResolveQuizRequiresIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->reader->resolveQuiz($this->course, null, null);
    }

    public function testListQuizzesIncludesTheCreatedTest(): void
    {
        $ids = array_map(
            static fn (CQuiz $quiz): ?int => $quiz->getIid(),
            $this->reader->listQuizzes($this->course),
        );

        self::assertContains($this->quiz->getIid(), $ids);
    }

    public function testNormalizeTestReflectsSettingsAndTotals(): void
    {
        $normalized = $this->reader->normalizeTest($this->quiz, $this->course);

        self::assertSame((int) $this->quiz->getIid(), $normalized['quiz_id']);
        self::assertSame($this->quiz->getTitle(), $normalized['title']);
        self::assertFalse($normalized['random_answers']);
        self::assertSame(1, $normalized['question_count']);
        self::assertSame(5.0, $normalized['total_score']);
        self::assertIsArray($normalized['quiz_category']);
        self::assertStringContainsString('Test category', (string) $normalized['quiz_category']['title']);
    }

    public function testQuestionsAreListedWithTypeScoreAndCategory(): void
    {
        $links = $this->reader->listQuestionLinks($this->quiz);
        self::assertCount(1, $links);

        $normalized = $this->reader->normalizeQuestion($links[0]->getQuestion(), 1);

        self::assertSame((int) $this->question->getIid(), $normalized['question_id']);
        self::assertSame(1, $normalized['position']);
        self::assertSame(UNIQUE_ANSWER, $normalized['type']);
        self::assertSame('Unique answer', $normalized['type_label']);
        self::assertSame(5.0, $normalized['total_score']);
        self::assertIsArray($normalized['category']);
        self::assertStringContainsString('Question category', (string) $normalized['category']['title']);
        self::assertSame(2, $normalized['answer_count']);
    }

    public function testResolveQuestionWithPositionFindsTheQuestion(): void
    {
        $resolved = $this->reader->resolveQuestionWithPosition($this->quiz, (int) $this->question->getIid());

        self::assertSame((int) $this->question->getIid(), $resolved['question']->getIid());
        self::assertSame(1, $resolved['position']);
    }

    public function testResolveQuestionWithPositionRejectsAForeignQuestionId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->reader->resolveQuestionWithPosition($this->quiz, 999999999);
    }

    public function testAnswersIncludeFeedbackAndScore(): void
    {
        $answers = $this->reader->listAnswers($this->question);
        self::assertCount(2, $answers);

        $normalized = array_map(
            fn (CQuizAnswer $answer): array => $this->reader->normalizeAnswer($answer),
            $answers,
        );

        self::assertTrue($normalized[0]['is_correct']);
        self::assertSame('Well done!', $normalized[0]['feedback']);
        self::assertSame(5.0, $normalized[0]['score']);

        self::assertFalse($normalized[1]['is_correct']);
        self::assertSame('Not quite.', $normalized[1]['feedback']);
        self::assertSame(0.0, $normalized[1]['score']);
    }
}
