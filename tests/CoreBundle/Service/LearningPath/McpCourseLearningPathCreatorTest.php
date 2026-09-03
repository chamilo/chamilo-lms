<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\LearningPath\McpCourseLearningPathCreator;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class McpCourseLearningPathCreatorTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private McpCourseLearningPathCreator $creator;
    private Course $course;
    private User $user;
    private Session $session;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        // Legacy code (api_get_course_info(), etc.) reads services through this
        // static bridge, normally populated by an early controller/helper during
        // a real HTTP request. A bare KernelTestCase boot never sets it.
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $this->session = new Session(new MockArraySessionStorage());
        Container::setSession($this->session);

        $this->em = $container->get(EntityManagerInterface::class);
        $this->creator = $container->get(McpCourseLearningPathCreator::class);

        $course = $this->em->getRepository(Course::class)->find(2);
        $user = $this->em->getRepository(User::class)->find(1);
        if (!$course instanceof Course || !$user instanceof User) {
            self::markTestSkipped('Course #2 / User #1 fixtures are not present in this DB.');
        }

        $this->course = $course;
        $this->user = $user;

        // ResourceListener falls back to the Security token's user as the
        // resource creator when none is set explicitly — mirrors what the
        // real Bearer-token-authenticated MCP request provides in production.
        $container->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($user, 'api', $user->getRoles())
        );
    }

    public function testCreatesMixedPagesWithAndWithoutQuiz(): void
    {
        $pages = [
            [
                'title' => 'MCP LP Test Page One',
                'content' => '<h1>Page One</h1><p>Some real content written by the MCP client.</p>',
                'quiz' => [
                    'title' => 'Custom quiz title',
                    'questions' => [
                        [
                            'title' => 'What is 2 + 2?',
                            'answers' => ['3', '4', '5'],
                            'correct_index' => 1,
                            'feedback' => 'Basic arithmetic.',
                        ],
                        [
                            'title' => 'Pick the vowel',
                            'answers' => ['b', 'c', 'e', 'g'],
                            'correct_index' => 2,
                        ],
                    ],
                ],
            ],
            [
                'title' => 'MCP LP Test Page Two',
                'content' => '<h1>Page Two</h1><p>No quiz on this page.</p>',
            ],
        ];

        $result = $this->creator->create(
            $this->course,
            $this->user,
            'MCP LP Test Learning Path',
            $pages,
            null,
            false,
        );

        self::assertSame(2, $result['page_count']);
        self::assertSame(1, $result['quiz_page_count']);
        self::assertFalse($result['published']);
        self::assertTrue($result['ai_assisted']);
        self::assertSame('mcp_client', $result['content_source']);
        self::assertTrue($result['verified_in_course']);

        $items = $result['items'];
        self::assertCount(2, $items);

        self::assertSame('MCP LP Test Page One', $items[0]['title']);
        self::assertNotNull($items[0]['quiz_id']);
        self::assertNotNull($items[0]['quiz_item_id']);

        self::assertSame('MCP LP Test Page Two', $items[1]['title']);
        self::assertNull($items[1]['quiz_id']);
        self::assertNull($items[1]['quiz_item_id']);

        // The learning path itself persisted correctly.
        $lp = $this->em->find(CLp::class, $result['learning_path_id']);
        self::assertInstanceOf(CLp::class, $lp);
        self::assertSame('MCP LP Test Learning Path', $lp->getTitle());

        // The quiz was persisted with exactly the client-supplied questions/answers/correct index.
        $quiz = $this->em->find(CQuiz::class, $items[0]['quiz_id']);
        self::assertInstanceOf(CQuiz::class, $quiz);
        self::assertSame('Custom quiz title', $quiz->getTitle());
        self::assertTrue($quiz->getRandomAnswers());

        $question = $this->em->find(CQuizQuestion::class, $this->firstQuestionIdOf($quiz));
        self::assertInstanceOf(CQuizQuestion::class, $question);
        self::assertSame('What is 2 + 2?', $question->getQuestion());

        $answers = $this->em->getRepository(CQuizAnswer::class)->findBy(['question' => $question], ['position' => 'ASC']);
        self::assertCount(3, $answers);
        self::assertSame('3', $answers[0]->getAnswer());
        self::assertSame('4', $answers[1]->getAnswer());
        self::assertSame('5', $answers[2]->getAnswer());
        self::assertSame(1, $answers[1]->getCorrect());
        self::assertSame(0, $answers[0]->getCorrect());
        self::assertSame(0, $answers[2]->getCorrect());
    }

    /**
     * The creator drives the legacy learnpath class, whose constructor calls
     * api_get_session_id() and therefore reads the session -- so the session is
     * started by the time this returns, and asserting otherwise would be
     * asserting against the design. What has to hold is that nothing *writes* to
     * it: an MCP call carries no course context, and none must be invented.
     */
    public function testCreatingMiniTestDoesNotPopulateTheLegacySession(): void
    {
        self::assertSame([], $this->session->all());

        $result = $this->creator->create(
            $this->course,
            $this->user,
            'MCP LP Session-Safe Quiz Link',
            [
                [
                    'title' => 'MCP LP Session-Safe Page',
                    'content' => '<p>Content supplied by the MCP client.</p>',
                    'quiz' => [
                        'questions' => [
                            [
                                'title' => 'Choose the correct answer',
                                'answers' => ['Wrong', 'Correct'],
                                'correct_index' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            null,
            false,
        );

        self::assertSame([], $this->session->all());
        self::assertNotNull($result['items'][0]['quiz_item_id']);
    }

    public function testRejectsInvalidCorrectIndex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/correct_index/');

        $this->creator->create(
            $this->course,
            $this->user,
            'MCP LP Invalid Index',
            [
                [
                    'title' => 'Page',
                    'content' => '<p>content</p>',
                    'quiz' => [
                        'questions' => [
                            ['title' => 'Q', 'answers' => ['a', 'b'], 'correct_index' => 5],
                        ],
                    ],
                ],
            ],
            null,
            false,
        );
    }

    public function testRejectsTooFewAnswers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->creator->create(
            $this->course,
            $this->user,
            'MCP LP Too Few Answers',
            [
                [
                    'title' => 'Page',
                    'content' => '<p>content</p>',
                    'quiz' => [
                        'questions' => [
                            ['title' => 'Q', 'answers' => ['only one'], 'correct_index' => 0],
                        ],
                    ],
                ],
            ],
            null,
            false,
        );
    }

    public function testRejectsEmptyPages(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->creator->create($this->course, $this->user, 'Empty', [], null, false);
    }

    public function testDuplicatePageTitlesAreMadeUniqueAndLinkedWithThePersistedTitle(): void
    {
        $existingResult = $this->creator->create(
            $this->course,
            $this->user,
            'MCP LP Preexisting',
            [['title' => 'MCP LP Duplicate Title Page', 'content' => '<p>pre-existing</p>']],
            null,
            false,
        );
        self::assertSame(1, $existingResult['page_count']);

        $result = $this->creator->create(
            $this->course,
            $this->user,
            'MCP LP Unique Document Titles',
            [['title' => 'MCP LP Duplicate Title Page', 'content' => '<p>new page content</p>']],
            null,
            false,
        );

        self::assertSame(1, $result['page_count']);
        self::assertSame('MCP LP Duplicate Title Page (2)', $result['items'][0]['title']);

        $document = $this->em->find(CDocument::class, $result['items'][0]['document_id']);
        self::assertInstanceOf(CDocument::class, $document);
        self::assertSame('MCP LP Duplicate Title Page (2)', $document->getTitle());

        $learningPath = $this->em->find(CLp::class, $result['learning_path_id']);
        self::assertInstanceOf(CLp::class, $learningPath);
        self::assertSame('MCP LP Unique Document Titles', $learningPath->getTitle());

        $sameTitleDocuments = $this->em->getRepository(CDocument::class)
            ->findBy(['title' => 'MCP LP Duplicate Title Page'])
        ;
        self::assertCount(1, $sameTitleDocuments);
    }

    private function firstQuestionIdOf(CQuiz $quiz): int
    {
        $relations = $this->em->getRepository(CQuizRelQuestion::class)
            ->findBy(['quiz' => $quiz], ['questionOrder' => 'ASC'])
        ;

        return (int) $relations[0]->getQuestion()->getIid();
    }
}
