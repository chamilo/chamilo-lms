<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrlRelCourseCategory;
use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\CourseCategoryRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CourseCategoryRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CourseCategoryRepository::class);
        $defaultCount = $repo->count([]);

        $item = (new CourseCategory())
            ->setCode('Course cat')
            ->setName('Course cat')
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        // On a fresh installation there are already 3 categories.
        // See the src/CoreBundle/DataFixtures/CourseCategoryFixtures.php
        $this->assertSame($defaultCount + 1, $repo->count([]));

        $this->assertSame('Course cat', $item->getCode());
        $this->assertSame('Course cat (Course cat)', (string) $item);
    }

    public function testCreateWithParent(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CourseCategoryRepository::class);
        $defaultCount = $repo->count([]);

        $item = (new CourseCategory())
            ->setCode('Course cat')
            ->setName('Course cat')
        ;
        $em->persist($item);
        $em->flush();

        $sub = (new CourseCategory())
            ->setCode('Sub cat')
            ->setName('Sub cat')
            ->setParent($item)
        ;
        $em->persist($sub);
        $em->flush();

        $this->assertNotNull($sub->getParent());
        $this->assertSame($defaultCount + 2, $repo->count([]));
    }

    public function testCreateWithAsset(): void
    {
        $em = $this->getEntityManager();

        /** @var CourseCategoryRepository $repoCourseCategory */
        $repoCourseCategory = self::getContainer()->get(CourseCategoryRepository::class);
        $defaultCount = $repoCourseCategory->count([]);

        /** @var AssetRepository $assetRepo */
        $assetRepo = self::getContainer()->get(AssetRepository::class);

        $file = $this->getUploadedFile();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('file')
            ->setCategory(Asset::COURSE_CATEGORY)
            ->setFile($file)
        ;
        $em->persist($asset);

        $item = (new CourseCategory())
            ->setCode('cat')
            ->setName('cat')
            ->setAsset($asset)
        ;

        $this->assertHasNoEntityViolations($item);
        $repoCourseCategory->save($item);

        $this->assertSame($defaultCount + 1, $repoCourseCategory->count([]));
        $this->assertTrue($item->hasAsset());
        $this->assertSame(1, $assetRepo->count([]));

        $repoCourseCategory->delete($item);

        $this->assertSame($defaultCount, $repoCourseCategory->count([]));
    }

    public function testDelete(): void
    {
        $client = static::createClient();

        $em = $this->getEntityManager();

        /** @var CourseCategoryRepository $repoCourseCategory */
        $repoCourseCategory = self::getContainer()->get(CourseCategoryRepository::class);
        $defaultCount = $repoCourseCategory->count([]);

        /** @var AssetRepository $assetRepo */
        $assetRepo = self::getContainer()->get(AssetRepository::class);

        $file = $this->getUploadedFile();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('file')
            ->setCategory(Asset::COURSE_CATEGORY)
            ->setFile($file)
        ;
        $em->persist($asset);

        $courseCategory = (new CourseCategory())
            ->setCode('cat')
            ->setName('cat')
            ->setAsset($asset)
        ;
        $repoCourseCategory->save($courseCategory);

        $url = $assetRepo->getAssetUrl($asset);
        $this->assertNotEmpty($url);
        $content = $assetRepo->getAssetContent($asset);
        $this->assertNotEmpty($content);

        $response = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $this->assertSame(1, $assetRepo->count([]));
        $em->clear();

        $courseCategory = $repoCourseCategory->find($courseCategory->getId());

        $repoCourseCategory->delete($courseCategory);

        $this->assertSame(0, $assetRepo->count([]));
        $this->assertSame($defaultCount, $repoCourseCategory->count([]));

        $content = $assetRepo->getAssetContent($asset);
        $this->assertEmpty($content);
    }

    public function testEditAndDeleteAsset(): void
    {
        $em = $this->getEntityManager();

        $repoCourseCategory = self::getContainer()->get(CourseCategoryRepository::class);
        $defaultCount = $repoCourseCategory->count([]);

        /** @var AssetRepository $assetRepo */
        $assetRepo = self::getContainer()->get(AssetRepository::class);

        $file = $this->getUploadedFile();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('file')
            ->setCategory(Asset::COURSE_CATEGORY)
            ->setFile($file)
        ;
        $em->persist($asset);

        $courseCategory = (new CourseCategory())
            ->setCode('cat')
            ->setName('cat')
            ->setAsset($asset)
        ;
        $repoCourseCategory->save($courseCategory);

        $this->assertSame($defaultCount + 1, $repoCourseCategory->count([]));

        $this->assertSame(1, $assetRepo->count([]));

        $courseCategory = $repoCourseCategory->find($courseCategory->getId());
        $repoCourseCategory->deleteAsset($courseCategory);

        $this->assertSame(0, $assetRepo->count([]));
        $this->assertSame($defaultCount + 1, $repoCourseCategory->count([]));
    }

    public function testFindAllInAccessUrl(): void
    {
        $repoCourseCategory = self::getContainer()->get(CourseCategoryRepository::class);
        $urlId = $this->getAccessUrl()->getId();

        $categories = $repoCourseCategory->findAllInAccessUrl($urlId);

        $this->assertCount(3, $categories);

        $categories = $repoCourseCategory->findAllInAccessUrl($urlId, false);
        $this->assertCount(3, $categories);

        $categories = $repoCourseCategory->findAllInAccessUrl($urlId, false, 99);
        $this->assertCount(0, $categories);
    }

    public function testGetCategoriesByCourseIdAndAccessUrlId(): void
    {
        $repoCourseCategory = self::getContainer()->get(CourseCategoryRepository::class);
        $urlId = $this->getAccessUrl()->getId();
        $course = $this->createCourse('new');

        $em = $this->getEntityManager();

        $category = (new CourseCategory())
            ->setCode('Course cat')
            ->setName('Course cat')
        ;
        $em->persist($category);

        $urlRelCategory = (new AccessUrlRelCourseCategory())
            ->setUrl($this->getAccessUrl())
            ->setCourseCategory($category)
        ;

        $em->persist($urlRelCategory);
        $em->flush();

        $course->addCategory($category);
        $em->persist($course);
        $em->flush();

        $categories = $repoCourseCategory->getCategoriesByCourseIdAndAccessUrlId($urlId, $course->getId());
        $this->assertCount(1, $categories);

        $categories = $repoCourseCategory->getCategoriesByCourseIdAndAccessUrlId($urlId, $course->getId(), true);
        $this->assertCount(1, $categories);
    }
}
