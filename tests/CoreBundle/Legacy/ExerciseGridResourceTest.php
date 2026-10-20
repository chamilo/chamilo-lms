<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Exercise;
use SortableTableFromArrayConfig;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The learner's tests list must show the tests in the same order as the teacher's:
 * natural, case-insensitive order by title ("Quiz 2" before "Quiz 10"), applied to
 * the whole list before it is split into pages (refs #1416).
 */
final class ExerciseGridResourceTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const TOTAL = Exercise::PAGINATION_ITEMS_PER_PAGE + 5;

    private Session $session;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        // Legacy code reads its services, session and request through this static bridge.
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $this->session = new Session(new MockArraySessionStorage());
        Container::setSession($this->session);
        $request = Request::create('/main/exercise/exercise.php');
        $request->setSession($this->session);
        Container::setRequest($request);
    }

    protected function tearDown(): void
    {
        $_GET = $_REQUEST = $_SESSION = [];

        parent::tearDown();
    }

    public function testLearnerListHasTheTeacherNaturalTitleOrderAcrossPages(): void
    {
        $course = $this->createCourse('ExerciseGridResource order');
        $admin = $this->getAdmin();
        $this->loginAs($admin);

        // Unpadded numbers, created in reverse order: neither the insertion order nor
        // a plain alphabetical sort ("Quiz 1", "Quiz 10", "Quiz 11"...) is the natural one.
        $repo = self::getContainer()->get(CQuizRepository::class);
        for ($i = self::TOTAL; $i >= 1; $i--) {
            $quiz = (new CQuiz())
                ->setTitle('Quiz '.$i)
                ->setParent($course)
                ->setCreator($admin)
                ->addCourseLink($course)
            ;
            $repo->create($quiz);
        }

        $expected = array_map(static fn (int $i): string => 'Quiz '.$i, range(1, self::TOTAL));

        $this->setCourseContext($course, $admin, COURSEMANAGER);
        self::assertTrue(api_is_allowed_to_edit(false, true), 'The teacher list must be built for an editor.');
        $teacherTitles = $this->getAllPageTitles($course, $admin, 1);
        self::assertSame($expected, $teacherTitles, 'The teacher tests list is not in natural title order.');

        $student = $this->createUser('grid_order_student');
        $student->setStatus(STUDENT);
        $this->getEntityManager()->flush();
        $this->loginAs($student);
        $this->setCourseContext($course, $student, STUDENT);
        self::assertFalse(api_is_allowed_to_edit(false, true), 'The learner list must be built for a learner.');

        self::assertSame(
            $teacherTitles,
            $this->getAllPageTitles($course, $student, 0),
            'The learner tests list is not in the same order as the teacher one.'
        );
    }

    /**
     * Titles of every page, in display order, each page checked to be full but the last.
     *
     * @return string[]
     */
    private function getAllPageTitles(Course $course, User $user, int $titleColumn): array
    {
        $titles = [];
        $pages = (int) ceil(self::TOTAL / Exercise::PAGINATION_ITEMS_PER_PAGE);
        for ($page = 1; $page <= $pages; $page++) {
            $_GET = $_REQUEST = ['exercises_cat_0_'.$course->getId().'_0_page_nr' => $page];

            $table = Exercise::exerciseGridResource(0, '', $user->getId(), $course->getId(), 0, false, 0, 0, 0, null, true);
            self::assertInstanceOf(SortableTableFromArrayConfig::class, $table);
            self::assertSame(self::TOTAL, $table->get_total_number_of_items());

            $pageTitles = array_map(
                static fn (array $row): string => trim(strip_tags((string) $row[$titleColumn])),
                $table->get_table_data()
            );
            self::assertCount(min(Exercise::PAGINATION_ITEMS_PER_PAGE, self::TOTAL - \count($titles)), $pageTitles);
            $titles = array_merge($titles, $pageTitles);
        }

        return $titles;
    }

    /**
     * Each user starts from a fresh session, as the table keeps its sort column there
     * (legacy ChamiloSession mirrors it into $_SESSION).
     */
    private function setCourseContext(Course $course, User $user, int $status): void
    {
        $this->session->clear();
        $_SESSION = [];
        $this->session->set('_user', ['user_id' => $user->getId(), 'status' => $status]);
        $this->session->set('_cid', $course->getId());
        $this->session->set('_real_cid', $course->getId());
    }

    private function loginAs(User $user): void
    {
        self::getContainer()->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles())
        );
    }
}
