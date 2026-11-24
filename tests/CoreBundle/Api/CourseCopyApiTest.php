<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class CourseCopyApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testAdminCanCopyContentIntoAnExistingCourse(): void
    {
        $suffix = $this->uniqueSuffix();
        $source = $this->createCourse('Copy source '.$suffix);
        $target = $this->createCourse('Copy target '.$suffix);
        $documentTitle = 'Copied intro '.$suffix;
        $this->addHtmlDocument($source, $documentTitle, '<p>Welcome '.$suffix.'</p>');

        $client = $this->createClientWithCredentials($this->getUserToken());
        $client->request('POST', '/api/courses/copy', [
            'json' => [
                'sourceId' => $source->getId(),
                'targetId' => $target->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $payload = $client->getResponse()->toArray();
        $this->assertSame('copied', $payload['status']);
        $this->assertFalse($payload['created']);
        $this->assertSame($target->getId(), $payload['targetId']);
        $this->assertSame($target->getCode(), $payload['targetCode']);
        $this->assertSame(
            1,
            $this->countCourseDocumentsByTitle($target->getId(), $documentTitle),
            'The source document must appear in the target course.'
        );
    }

    public function testAdminCanCreateATargetCourseAndCopyIntoIt(): void
    {
        $suffix = $this->uniqueSuffix();
        $source = $this->createCourse('Copy new-target source '.$suffix);
        $documentTitle = 'New target intro '.$suffix;
        $this->addHtmlDocument($source, $documentTitle, '<p>New '.$suffix.'</p>');

        $targetCode = 'CPY'.$suffix;
        $targetTitle = 'Copied course '.$suffix;

        $client = $this->createClientWithCredentials($this->getUserToken());
        $client->request('POST', '/api/courses/copy', [
            'json' => [
                'sourceCode' => $source->getCode(),
                'targetCode' => $targetCode,
                'targetTitle' => $targetTitle,
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $payload = $client->getResponse()->toArray();
        $this->assertSame('copied', $payload['status']);
        $this->assertTrue($payload['created']);
        $this->assertSame($targetCode, $payload['targetCode']);
        $this->assertNotEmpty($payload['targetId']);

        $created = self::getContainer()->get(CourseRepository::class)->find($payload['targetId']);
        $this->assertNotNull($created);
        $this->assertSame($targetTitle, $created->getTitle());
        $this->assertSame(
            1,
            $this->countCourseDocumentsByTitle((int) $payload['targetId'], $documentTitle),
            'The source document must appear in the newly created course.'
        );
    }

    public function testNonAdminIsRefused(): void
    {
        $suffix = $this->uniqueSuffix();
        $source = $this->createCourse('Copy forbidden source '.$suffix);
        $target = $this->createCourse('Copy forbidden target '.$suffix);
        $teacher = $this->createUser('copy_teacher_'.$suffix, '', '', 'ROLE_TEACHER');

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($teacher));
        $client->request('POST', '/api/courses/copy', [
            'json' => [
                'sourceId' => $source->getId(),
                'targetId' => $target->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(
            0,
            $this->countCourseDocumentsByTitle($target->getId(), 'should-not-exist-'.$suffix)
        );
    }

    public function testUnknownSourceIsRefused(): void
    {
        $suffix = $this->uniqueSuffix();
        $target = $this->createCourse('Copy unknown-source target '.$suffix);

        $client = $this->createClientWithCredentials($this->getUserToken());
        $client->request('POST', '/api/courses/copy', [
            'json' => [
                'sourceId' => 2_147_483_647,
                'targetId' => $target->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testSourceEqualToTargetIsRefused(): void
    {
        $suffix = $this->uniqueSuffix();
        $course = $this->createCourse('Copy same course '.$suffix);

        $client = $this->createClientWithCredentials($this->getUserToken());
        $client->request('POST', '/api/courses/copy', [
            'json' => [
                'sourceCode' => $course->getCode(),
                'targetId' => $course->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testNewTargetKeepsRequestedCategory(): void
    {
        $suffix = $this->uniqueSuffix();
        $source = $this->createCourse('Copy category source '.$suffix);
        $category = (new CourseCategory())
            ->setTitle('Copy category '.$suffix)
            ->setCode('CAT'.$suffix)
            ->addUrl($this->getAccessUrl())
        ;
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();

        $client = $this->createClientWithCredentials($this->getUserToken());
        $client->request('POST', '/api/courses/copy', [
            'json' => [
                'sourceId' => $source->getId(),
                'targetCode' => 'CC'.$suffix,
                'targetTitle' => 'Copied category course '.$suffix,
                'targetCategoryId' => $category->getId(),
                'tools' => ['documents'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $payload = $client->getResponse()->toArray();
        $this->assertTrue($payload['created']);
        $this->getEntityManager()->clear();
        $created = self::getContainer()->get(CourseRepository::class)->find($payload['targetId']);
        $this->assertNotNull($created);
        $this->assertCount(1, $created->getCategories());
        $this->assertSame($category->getId(), $created->getCategories()->first()->getId());
    }

    public function testUnknownCategoryDoesNotCreateTargetCourse(): void
    {
        $suffix = $this->uniqueSuffix();
        $source = $this->createCourse('Copy invalid category source '.$suffix);
        $targetCode = 'IC'.$suffix;

        $client = $this->createClientWithCredentials($this->getUserToken());
        $client->request('POST', '/api/courses/copy', [
            'json' => [
                'sourceId' => $source->getId(),
                'targetCode' => $targetCode,
                'targetTitle' => 'Should not exist '.$suffix,
                'targetCategoryId' => 2_147_483_647,
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertNull(self::getContainer()->get(CourseRepository::class)->findOneBy(['code' => $targetCode]));
    }

    public function testCategoryNotAssignedToPortalDoesNotCreateTargetCourse(): void
    {
        $suffix = $this->uniqueSuffix();
        $source = $this->createCourse('Copy foreign category source '.$suffix);
        $category = (new CourseCategory())
            ->setTitle('Unassigned category '.$suffix)
            ->setCode('UN'.$suffix)
        ;
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();
        $targetCode = 'FC'.$suffix;

        $client = $this->createClientWithCredentials($this->getUserToken());
        $client->request('POST', '/api/courses/copy', [
            'json' => [
                'sourceId' => $source->getId(),
                'targetCode' => $targetCode,
                'targetTitle' => 'Should not exist '.$suffix,
                'targetCategoryId' => $category->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertNull(self::getContainer()->get(CourseRepository::class)->findOneBy(['code' => $targetCode]));
    }

    public function testRestorerReportsPerToolFailure(): void
    {
        $suffix = $this->uniqueSuffix();
        $source = $this->createCourse('Copy failure source '.$suffix);
        $target = $this->createCourse('Copy failure target '.$suffix);

        // The builder uses ChamiloSession, which requires an active Symfony request.
        $requestStack = self::getContainer()->get('request_stack');
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack->push($request);

        try {
            $sourceInfo = api_get_course_info($source->getCode());
            $this->assertIsArray($sourceInfo);

            $builder = new CourseBuilder('partial', $sourceInfo);
            $builder->set_tools_to_build(['documents']);
            $snapshot = $builder->build(0, $source->getCode());

            if (!\defined('FILE_RENAME')) {
                \define('FILE_RENAME', 2);
            }

            $restorer = new class($snapshot) extends CourseRestorer {
                public function restore_documents($session_id = 0, $respect_base_content = false, $destination_course_code = ''): void
                {
                    throw new RuntimeException('Simulated document restore failure.');
                }
            };
            $restorer->tools_to_restore = ['documents'];

            $this->assertNull($restorer->restore($target->getCode()));
            $this->assertSame(['documents'], $restorer->getFailedTools());
        } finally {
            $requestStack->pop();
        }
    }

    private function addHtmlDocument(Course $course, string $title, string $html): void
    {
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $document = (new CDocument())
            ->setFiletype('html')
            ->setTitle($title)
            ->setTemplate(false)
            ->setReadonly(false)
            ->setParent($course)
            ->setCreator($this->getUser('admin'))
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;
        $documentRepo->create($document);
        $documentRepo->addFileFromString($document, $title.'.html', 'text/html', $html, true);
    }

    private function countCourseDocumentsByTitle(int $courseId, string $title): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(d.iid)')
            ->from(CDocument::class, 'd')
            ->innerJoin('d.resourceNode', 'n')
            ->innerJoin('n.resourceLinks', 'l')
            ->where('IDENTITY(l.course) = :courseId')
            ->andWhere('d.title = :title')
            ->setParameter('courseId', $courseId)
            ->setParameter('title', $title)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function uniqueSuffix(): string
    {
        return strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}
