<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CoursesControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testHomeRedirectAction(): void
    {
        $client = static::createClient();
        $course = $this->createCourse('new');
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/courses/'.$course->getCode().'/index.php');
        $this->assertResponseRedirects('/course/'.$course->getId().'/home');
    }

    public function testDocumentRedirect(): void
    {
        $client = static::createClient();
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $course = $this->createCourse('Test');
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $admin = $this->getUser('admin');

        $document = (new CDocument())
            ->setFiletype('file')
            ->setTitle('title123')
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;

        $documentRepo->create($document);

        $this->assertSame(1, $documentRepo->count([]));

        $path = $this->getUploadedFile()->getRealPath();
        $resourceFile = $documentRepo->addFileFromPath($document, 'logo.png', $path, true);

        $this->assertNotNull($resourceFile);

        /** @var CDocument $document */
        $document = $documentRepo->find($document->getIid());
        $node = $document->getResourceNode();
        $this->assertTrue($node->hasResourceFile());

        $client->request('GET', '/courses/'.$course->getCode().'/document/title123');
        $id = $node->getUuid()->toRfc4122();
        $this->assertResponseRedirects('/r/document/file/'.$id.'/view');
    }
}
