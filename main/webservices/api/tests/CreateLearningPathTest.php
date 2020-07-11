<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CForumForum;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;

require_once __DIR__.'/V2TestCase.php';
require_once __DIR__.'/../../../../vendor/autoload.php';

/**
 * Class CreateLearningPathTest
 *
 * SUBSCRIBE_USER_TO_SESSION_FROM_USERNAME webservice unit tests
 */
class CreateLearningPathTest extends V2TestCase
{
    /** @var Session */
    public static $session;

    /** @var Course */
    public static $course;

    /** @var CLpCategory */
    public static $category;

    /** @var CDocument */
    public static $document;

    /** @var CForumForum */
    public static $forum;

    /** @var CLink */
    public static $link;

    /** @var CQuiz */
    public static $quiz;

    /** @var CLp[] */
    public static $learningPaths;

    public function action()
    {
        return Rest::CREATE_LEARNINGPATH;
    }

    /**
     * @inheritDoc
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // create a test session
        self::$session = (new Session())
            ->setName('Test Session '.time());
        Database::getManager()->persist(self::$session);

        // create a test course
        self::$course = (new Course())
            ->setCode('TESTCOURSE'.time())
            ->setTitle('Test Course '.time());
        Database::getManager()->persist(self::$course);
        Database::getManager()->flush(self::$course); // ensures the course directory is initialized

        // create a test category
        self::$category = (new CLpCategory())
            ->setCourse(self::$course)
            ->setName('Test Category '.time());
        Database::getManager()->persist(self::$category);

        // create course elements
        self::$document = CDocument::fromFile(
            __FILE__,
            self::$course,
            'test_document'.time().'.txt',
            'Test Document'.time()
        );
        Database::getManager()->persist(self::$document);

        self::$forum = (new CForumForum())
            ->setCourse(self::$course)
            ->setForumTitle('Test Forum '.time());
        Database::getManager()->persist(self::$forum);

        self::$link = (new CLink())
            ->setCourse(self::$course)
            ->setTitle('Test Link '.time())
            ->setUrl('https://chamilo.org/');
        Database::getManager()->persist(self::$link);

        self::$quiz = (new CQuiz())
            ->setCourse(self::$course)
            ->setTitle('Test Quiz '.time());
        Database::getManager()->persist(self::$quiz);

        Database::getManager()->flush();

        self::$learningPaths = [];
    }

    /**
     * @inheritDoc
     *
     * @throws OptimisticLockException
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        foreach (self::$learningPaths as $learningPath) {
            Database::getManager()->remove($learningPath);
        }
        Database::getManager()->remove(self::$quiz);
        Database::getManager()->remove(self::$link);
        Database::getManager()->remove(self::$forum);
        Database::getManager()->remove(self::$document);
        Database::getManager()->remove(self::$category);
        Database::getManager()->remove(self::$course);
        Database::getManager()->remove(self::$session);
        Database::getManager()->flush();
    }

    /**
     * creates an empty learning path
     * asserts that the learning path was created for the right session and course, with the right name,
     * in the right category and that it has no item
     */
    public function testCreateEmptyLearningPathWithoutSessionNorCategory()
    {
        // call the webservice to create the learning path
        $name = 'Learning Path '.time();
        $learningPathId = $this->integer(
            [
                'session_id' => 0,
                'course_code' => self::$course->getCode(),
                'lp_name' => $name,
                'lp_cat_id' => 0,
                'items' => [],
            ]
        );

        // assert the learning path was created
        /** @var CLp $learningPath */
        $learningPath = CLp::getRepository()->find($learningPathId);
        self::$learningPaths[] = $learningPath;

        self::assertNotNull($learningPath);
        // in the right course
        self::assertEquals(0, $learningPath->getSessionId());
        // with no session nor category
        self::assertEquals(self::$course->getId(), $learningPath->getCId());
        self::assertEquals(0, $learningPath->getCategoryId());
        // with the right name
        self::assertEquals($name, $learningPath->getName());
        // with no item
        self::assertEmpty($learningPath->getItems());
    }

    /**
     * creates an empty learning path
     * asserts that the learning path was created for the right session and course, with the right name,
     * in the right category and that it has no item
     */
    public function testCreateEmptyLearningPath()
    {
        // call the webservice to create the learning path
        $name = 'Learning Path '.time();
        $learningPathId = $this->integer(
            [
                'session_id' => self::$session->getId(),
                'course_code' => self::$course->getCode(),
                'lp_name' => $name,
                'lp_cat_id' => self::$category->getId(),
                'items' => [],
            ]
        );

        // assert the learning path was created
        /** @var CLp $learningPath */
        $learningPath = CLp::getRepository()->find($learningPathId);
        self::$learningPaths[] = $learningPath;

        self::assertNotNull($learningPath);
        // in the right session, course and category
        self::assertEquals(self::$session->getId(), $learningPath->getSessionId());
        self::assertEquals(self::$course->getId(), $learningPath->getCId());
        self::assertEquals(self::$category->getId(), $learningPath->getCategoryId());
        // with the right name
        self::assertEquals($name, $learningPath->getName());
        // with no item
        self::assertEmpty($learningPath->getItems());
    }

    /**
     * creates a learning path with items
     * asserts that the learning path items have the right properties
     */
    public function testCreateLearningPathWithItems()
    {
        $name = 'Learning Path '.time();
        $items = [
            [
                'display_order_id' => 10,
                'parent_id' => 0,
                'type' => 'document',
                'name_to_find' => self::$document->getTitle(),
                'title' => 'Document title '.time(),
            ],
            [
                'display_order_id' => 40,
                'parent_id' => 0,
                'type' => 'forum',
                'name_to_find' => self::$forum->getForumTitle(),
                'title' => 'Forum title '.time(),
                'prerequisite_id' => 20,
                'prerequisite_min_score' => 1,
                'prerequisite_max_score' => 1,
            ],
            [
                'display_order_id' => 20,
                'parent_id' => 0,
                'type' => 'link',
                'name_to_find' => self::$link->getTitle(),
                'title' => 'Link title '.time(),
                'prerequisite_id' => 10,
                'prerequisite_min_score' => 1,
                'prerequisite_max_score' => 1,
            ],
            [
                'display_order_id' => 30,
                'parent_id' => 0,
                'type' => 'quiz',
                'name_to_find' => self::$quiz->getTitle(),
                'title' => 'Quiz title '.time(),
                'prerequisite_id' => 10,
                'prerequisite_min_score' => 1,
                'prerequisite_max_score' => 1,
            ],
            [
                'display_order_id' => 50,
                'parent_id' => 0,
                'type' => TOOL_LP_FINAL_ITEM,
                'name_to_find' => self::$document->getTitle(),
                'title' => 'Final item title '.time(),
                'prerequisite_id' => 40,
                'prerequisite_min_score' => 1,
                'prerequisite_max_score' => 1,
            ],
            [
                'display_order_id' => 35,
                'parent_id' => 0,
                'type' => 'dir',
                'title' => 'Chapter 1 title '.time(),
                'prerequisite_id' => 10,
                'prerequisite_min_score' => 1,
                'prerequisite_max_score' => 1,
            ],
            [
                'display_order_id' => 1,
                'parent_id' => 35,
                'type' => 'student_publication',
                'title' => 'Student publication title '.time(),
            ],
            [
                'display_order_id' => 2,
                'parent_id' => 35,
                'type' => 'student_publication',
                'title' => 'Another student publication title '.time(),
                'prerequisite_id' => 1,
                'prerequisite_min_score' => 1,
                'prerequisite_max_score' => 1,
            ],
            [
                'display_order_id' => 3,
                'parent_id' => 35,
                'type' => 'dir',
                'title' => 'Sub-chapter of chapter 1 title '.time(),
            ],
            [
                'display_order_id' => 38,
                'parent_id' => 0,
                'type' => 'dir',
                'title' => 'Chapter 2 title '.time(),
            ],
        ];
        $learningPathId = $this->integer(
            [
                'session_id' => self::$session->getId(),
                'course_code' => self::$course->getCode(),
                'lp_name' => $name,
                'lp_cat_id' => self::$category->getId(),
                'items' => $items,
            ]
        );

        // assert the learning path was created as specified
        /** @var CLp $learningPath */
        $learningPath = CLp::getRepository()->find($learningPathId);
        self::$learningPaths[] = $learningPath;

        self::assertNotNull($learningPath);
        self::assertEquals(self::$session->getId(), $learningPath->getSessionId());
        self::assertEquals(self::$course->getId(), $learningPath->getCId());
        self::assertEquals($name, $learningPath->getName());
        self::assertEquals(self::$category->getId(), $learningPath->getCategoryId());
        self::assertNotEmpty($learningPath->getItems());

        // assert its item list matches the input specifications
        $realIds = [0 => 0];
        foreach ($items as $spec) {
            $found = false;
            foreach ($learningPath->getItems() as $item) {
                if ($spec['type'] === $item->getItemType() && $spec['title'] == $item->getTitle()) {
                    $found = true;
                    $displayOrderId = $spec['display_order_id'];
                    $realIds[$displayOrderId] = $item->getId();
                    self::assertEquals($displayOrderId, $item->getDisplayOrder());
                    self::assertEquals(self::$course->getId(), $item->getCId());
                    if ($item->getItemType() === 'document') {
                        self::assertEquals(self::$document->getId(), $item->getPath());
                    } elseif ($item->getItemType() === 'link') {
                        self::assertEquals(self::$link->getId(), $item->getPath());
                    }
                    if (array_key_exists('prerequisite_id', $spec) && 0 != $spec['prerequisite_id']) {
                        self::assertEquals($spec['prerequisite_min_score'], $item->getPrerequisiteMinScore());
                        self::assertEquals($spec['prerequisite_max_score'], $item->getPrerequisiteMaxScore());
                    }
                    break;
                }
            }
            self::assertTrue($found, sprintf('item not found: %s', print_r($spec, true)));
        }
        foreach ($items as $spec) {
            foreach ($learningPath->getItems() as $item) {
                if ($spec['type'] === $item->getItemType() && $spec['title'] == $item->getTitle()) {
                    self::assertEquals($realIds[$spec['parent_id']], $item->getParentItemId());
                    if (array_key_exists('prerequisite_id', $spec) && 0 != $spec['prerequisite_id']) {
                        self::assertEquals($realIds[$spec['prerequisite_id']], $item->getPrerequisite());
                    }
                }
            }
        }
    }
}
