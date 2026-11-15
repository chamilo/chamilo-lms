<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

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
