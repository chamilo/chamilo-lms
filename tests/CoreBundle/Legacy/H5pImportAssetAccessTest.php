<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageTools;
use Database;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Access control of the files served by public/plugin/H5pImport/package_asset.php.
 *
 * Every course keeps its H5P files under its own folder of the shared plugins storage, and a content package
 * belongs to the session it was imported in. The page that shows a package (view.php) only does so inside that
 * course and session, so the files it links to must follow the same rule: being logged in is not enough.
 *
 * The decision lives in H5pPackageTools::isStoragePathInContext() (behavioural tests). The handler is procedural,
 * so its guards are pinned at source level: they must come BEFORE the file is looked up.
 *
 * The plugin tables only exist where the plugin is installed, so the packages are served by a stub repository
 * that answers the same criteria the real one would. Courses 1 and 2 and the sessions are real. Tags start with
 * "h5a_".
 */
final class H5pImportAssetAccessTest extends PluginCharacterizationTestCase
{
    private const HANDLER = __DIR__.'/../../../public/plugin/H5pImport/package_asset.php';

    private EntityManager $realManager;

    /**
     * @var H5pImport[]
     */
    private array $packages = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->realManager = Database::getManager();
        $this->packages = [];

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturnCallback(function (array $criteria): ?H5pImport {
            foreach ($this->packages as $package) {
                if ($package->getRelativePath() === $criteria['relativePath']
                    && $package->getCourse()->getId() === $criteria['course']->getId()
                ) {
                    return $package;
                }
            }

            return null;
        });
        $manager = $this->createMock(EntityManager::class);
        $manager->method('getRepository')->with(H5pImport::class)->willReturn($repository);
        Database::setManager($manager);
    }

    protected function tearDown(): void
    {
        Database::setManager($this->realManager);
        parent::tearDown();
    }

    public function testLibraryFilesAreServedOnlyInsideTheirCourse(): void
    {
        $session = $this->session('h5a_session');
        $path = 'course_2/libraries/H5P.Example-1.0/example.js';

        // Libraries are shared by the course: the same folder serves the base course and its sessions.
        self::assertTrue(H5pPackageTools::isStoragePathInContext($path, $this->course(2), null));
        self::assertTrue(H5pPackageTools::isStoragePathInContext($path, $this->course(2), $session));
        self::assertTrue(H5pPackageTools::isStoragePathInContext('H5pImport/'.$path, $this->course(2), null));

        self::assertFalse(
            H5pPackageTools::isStoragePathInContext($path, $this->course(1), null),
            'a file of course 2 is not reachable from course 1'
        );
    }

    public function testContentPackageIsServedOnlyInTheCourseItBelongsTo(): void
    {
        $this->package(2, null, 'course_2/content/h5a_base');
        $path = 'course_2/content/h5a_base/content.json';

        self::assertTrue(H5pPackageTools::isStoragePathInContext($path, $this->course(2), null));
        self::assertFalse(H5pPackageTools::isStoragePathInContext($path, $this->course(1), null));
        // course_20 starts like course_2: the folder is compared as a whole, not as a prefix.
        self::assertFalse(
            H5pPackageTools::isStoragePathInContext('course_20/content/h5a_base/content.json', $this->course(2), null)
        );
    }

    public function testContentPackageIsServedOnlyInTheSessionItWasImportedIn(): void
    {
        $session = $this->session('h5a_session');
        $otherSession = $this->session('h5a_other_session');
        $this->package(2, null, 'course_2/content/h5a_base');
        $this->package(2, $session, 'course_2/content/h5a_in_session');

        $base = 'course_2/content/h5a_base/content.json';
        $inSession = 'course_2/content/h5a_in_session/content.json';

        self::assertTrue(H5pPackageTools::isStoragePathInContext($inSession, $this->course(2), $session));
        self::assertFalse(
            H5pPackageTools::isStoragePathInContext($inSession, $this->course(2), null),
            'a learner of the base course does not get the files of a session package'
        );
        self::assertFalse(H5pPackageTools::isStoragePathInContext($inSession, $this->course(2), $otherSession));
        // Same rule as view.php: inside a session only the packages of that session are shown.
        self::assertFalse(H5pPackageTools::isStoragePathInContext($base, $this->course(2), $session));
    }

    /**
     * @dataProvider pathsOutsideAnyPackage
     */
    public function testPathsThatAreNotAFileOfAKnownPackageAreRejected(string $path): void
    {
        $this->package(2, null, 'course_2/content/h5a_base');

        self::assertFalse(H5pPackageTools::isStoragePathInContext($path, $this->course(2), null));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pathsOutsideAnyPackage(): array
    {
        return [
            'package without a row' => ['course_2/content/h5a_unknown/content.json'],
            'package folder itself' => ['course_2/content/h5a_base'],
            'folder that is neither content nor libraries' => ['course_2/uploads/h5a_base/content.json'],
            'file at the course root' => ['course_2/content.json'],
            'storage root' => ['content.json'],
            'climbing out of the storage root' => ['../course_2/content/h5a_base/content.json'],
            'climbing into another course' => ['course_1/../course_2/../course_1/content/h5a_base/content.json'],
            'empty' => [''],
        ];
    }

    public function testHandlerChecksTheCourseAndTheContextBeforeLookingTheFileUp(): void
    {
        $source = (string) file_get_contents(self::HANDLER);

        $anonymous = strpos($source, 'api_block_anonymous_users();');
        $course = strpos($source, 'api_protect_course_script();');
        $context = strpos($source, 'H5pPackageTools::isStoragePathInContext(');
        $lookup = strpos($source, 'H5pPackageTools::persistentFileExists(');

        self::assertNotFalse($anonymous);
        self::assertNotFalse($course, 'the handler must require access to the current course');
        self::assertNotFalse($context, 'the handler must check that the file belongs to the current course and session');
        self::assertNotFalse($lookup);
        self::assertLessThan($course, $anonymous);
        self::assertLessThan($context, $course);
        self::assertLessThan($lookup, $context, 'nothing is read, not even its existence, before the checks');
        // A response that depends on who asks must not be stored by a shared cache.
        self::assertStringNotContainsString('Cache-Control: public', $source);
    }

    // ---------------------------------------------------------------- fixtures

    private function course(int $id): Course
    {
        return $this->realManager->find(Course::class, $id);
    }

    private function session(string $title): Session
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position)
             VALUES (?, 0, 0, 0, 1, 0, 0)',
            [$title]
        );

        return $this->realManager->find(Session::class, (int) $this->conn->lastInsertId());
    }

    private function package(int $courseId, ?Session $session, string $relativePath): void
    {
        $package = new H5pImport();
        $package->setCourse($this->course($courseId));
        $package->setSession($session);
        $package->setRelativePath($relativePath);
        $this->packages[] = $package;
    }
}
