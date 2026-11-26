<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class AssetControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testShowFile(): void
    {
        $client = static::createClient();
        $file = $this->getUploadedFile();

        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $em = $this->getEntityManager();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('test')
            ->setCategory(Asset::WATERMARK)
            ->setFile($file)
        ;
        $em->persist($asset);
        $em->flush();

        $url = $assetRepo->getAssetUrl($asset);
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->request('GET', $url.'not-existed');
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }

    public function testShowFileNoImage(): void
    {
        $client = static::createClient();
        $file = $this->getUploadedZipFile();

        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $em = $this->getEntityManager();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('test')
            ->setCategory(Asset::WATERMARK)
            ->setFile($file)
        ;
        $em->persist($asset);
        $em->flush();

        $url = $assetRepo->getAssetUrl($asset);
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    /**
     * A user-uploaded file (e.g. a profile file field) must never render as markup in the
     * platform origin, whoever opens its link.
     */
    public function testShowFileDeliversUserMarkupAsDownload(): void
    {
        $client = static::createClient();
        $url = $this->createMarkupAsset(Asset::EXTRA_FIELD, 'FILE_1_profile.html');

        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $headers = $client->getResponse()->headers;
        $this->assertSame('application/octet-stream', $headers->get('Content-Type'));
        $this->assertStringStartsWith('attachment', (string) $headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $headers->get('X-Content-Type-Options'));
    }

    /**
     * SCORM packages are served from the assets too, and their pages must still render for the
     * learners of the course.
     */
    public function testShowFileRendersScormMarkupInline(): void
    {
        $client = static::createClient();
        $course = $this->createRegisteredCourse();
        $student = $this->createUser('scorm_student_'.uniqid());
        $course->addUserAsStudent($student);
        $this->getEntityManager()->flush();
        $filePath = $this->createScormPackage($course, ResourceLink::VISIBILITY_PUBLISHED);

        try {
            $client->loginUser($student);
            $client->request('GET', '/assets/'.$filePath);
            $this->assertResponseIsSuccessful();

            $headers = $client->getResponse()->headers;
            $this->assertStringContainsString('text/html', (string) $headers->get('Content-Type'));
            $this->assertStringStartsWith('inline', (string) $headers->get('Content-Disposition'));
        } finally {
            $this->deleteScormFolder($filePath);
        }
    }

    /**
     * A SCORM package is the content of a private course: neither anonymous visitors nor users
     * outside the course may read it, and learners may not read it while its learning path is a draft.
     */
    public function testShowFileDeniesScormPackageOutsideItsCourse(): void
    {
        $client = static::createClient();
        $course = $this->createRegisteredCourse();
        $student = $this->createUser('scorm_student_'.uniqid());
        $teacher = $this->createUser('scorm_teacher_'.uniqid());
        $outsider = $this->createUser('scorm_outsider_'.uniqid());
        $course->addUserAsStudent($student);
        $course->addUserAsTeacher($teacher);
        $this->getEntityManager()->flush();
        $filePath = $this->createScormPackage($course, ResourceLink::VISIBILITY_DRAFT);
        $orphanPath = Asset::SCORM.'/orphan_'.uniqid().'.zip/pkg/index.html';
        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $assetRepo->getFileSystem()->write($orphanPath, '<!doctype html><p>asset</p>');

        try {
            $client->request('GET', '/assets/'.$filePath);
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

            $client->loginUser($outsider);
            $client->request('GET', '/assets/'.$filePath);
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

            $client->loginUser($student);
            $client->request('GET', '/assets/'.$filePath);
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

            // Files left behind by a deleted learning path belong to no course anymore.
            $client->request('GET', '/assets/'.$orphanPath);
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

            $client->loginUser($teacher);
            $client->request('GET', '/assets/'.$filePath);
            $this->assertResponseIsSuccessful();
        } finally {
            $this->deleteScormFolder($filePath);
            $this->deleteScormFolder($orphanPath);
        }
    }

    /**
     * The storage resolves ".." and "\" in the path, so a link that names the SCORM category but walks
     * out of it must not bring back inline rendering for a user upload.
     */
    public function testShowFileDoesNotTrustTheCategoryOfATraversingPath(): void
    {
        $client = static::createClient();
        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $dir = 'test_traversal_'.uniqid();
        $assetRepo->getFileSystem()->write(Asset::EXTRA_FIELD.'/'.$dir.'/profile.html', '<!doctype html><p>asset</p>');

        try {
            foreach (['/assets/scorm/..%5Cef/', '/assets/scorm/../ef/', '/assets/scorm/x/..%5C..%5Cef/'] as $prefix) {
                $client->request('GET', $prefix.$dir.'/profile.html');
                $this->assertResponseIsSuccessful($prefix);

                $headers = $client->getResponse()->headers;
                $this->assertSame('application/octet-stream', $headers->get('Content-Type'), $prefix);
                $this->assertStringStartsWith('attachment', (string) $headers->get('Content-Disposition'), $prefix);
            }
        } finally {
            $assetRepo->getFileSystem()->deleteDirectory(Asset::EXTRA_FIELD.'/'.$dir);
        }
    }

    private function createRegisteredCourse(): Course
    {
        $course = $this->createCourse('SCORM asset '.uniqid());
        $course->setVisibility(Course::REGISTERED);
        $this->getEntityManager()->flush();

        return $course;
    }

    /**
     * Creates a SCORM learning path in the course and returns the path of its entry page in the assets.
     */
    private function createScormPackage(Course $course, int $visibility): string
    {
        $title = 'test_package_'.uniqid().'.zip';
        $asset = (new Asset())
            ->setTitle($title)
            ->setCategory(Asset::SCORM)
            ->setCompressed(true)
            ->setFile($this->getUploadedZipFile())
        ;
        $em = $this->getEntityManager();
        $em->persist($asset);
        $em->flush();

        $lp = (new CLp())
            ->setTitle('SCORM package')
            ->setParent($course)
            ->setCreator($this->getAdmin())
            ->setLpType(CLp::SCORM_TYPE)
            ->setPath('pkg')
            ->setAsset($asset)
            ->addCourseLink($course, null, null, $visibility)
        ;
        self::getContainer()->get(CLpRepository::class)->createLp($lp);

        $filePath = Asset::SCORM.'/'.$title.'/pkg/index.html';
        self::getContainer()->get(AssetRepository::class)->getFileSystem()
            ->write($filePath, '<!doctype html><p>asset</p>')
        ;

        return $filePath;
    }

    private function deleteScormFolder(string $filePath): void
    {
        $folder = implode('/', \array_slice(explode('/', $filePath), 0, 2));
        self::getContainer()->get(AssetRepository::class)->getFileSystem()->deleteDirectory($folder);
    }

    private function createMarkupAsset(string $category, string $title): string
    {
        $path = tempnam(sys_get_temp_dir(), 'asset');
        file_put_contents($path, '<!doctype html><p>asset</p>');

        $asset = (new Asset())
            ->setTitle($title)
            ->setCategory($category)
            ->setFile(new UploadedFile($path, $title, 'text/html', null, true))
        ;
        $em = $this->getEntityManager();
        $em->persist($asset);
        $em->flush();

        return self::getContainer()->get(AssetRepository::class)->getAssetUrl($asset);
    }
}
