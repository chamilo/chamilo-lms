<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResourceControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testDownloadAction(): void
    {
        $client = static::createClient();
        $admin = $this->getUser('admin');
        $client->loginUser($admin);
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $course = $this->createCourse('Test');

        $document = (new CDocument())
            ->setFiletype('file')
            ->setTitle('title 123')
            ->setTemplate(false)
            ->setReadonly(false)
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;

        $documentRepo->create($document);
        $documentRepo->addFileFromString($document, 'test', 'text/html', 'my file', true);

        /** @var CDocument $document */
        $document = $documentRepo->find($document->getIid());
        $node = $document->getResourceNode();
        $this->assertTrue($node->hasResourceFile());

        $id = $node->getUuid()->toRfc4122();

        // Download document.
        $urlDownload = '/r/document/files/'.$id.'/download';
        $client->request('GET', $urlDownload);
        $this->assertResponseIsSuccessful();

        // Download all documents.
        $id = $course->getResourceNode()->getUuid()->toRfc4122();
        $urlDownload = '/r/document/files/'.$id.'/download';
        $client->request('GET', $urlDownload);
        $this->assertResponseIsSuccessful();
    }

    public function testViewAction(): void
    {
        $client = static::createClient();
        $em = $this->getEntityManager();
        $admin = $this->getUser('admin');
        $client->loginUser($admin);
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $course = $this->createCourse('Test');

        $document = (new CDocument())
            ->setFiletype('file')
            ->setTitle('title 123')
            ->setTemplate(false)
            ->setReadonly(false)
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;

        $documentRepo->create($document);
        $documentRepo->addFileFromString($document, 'test', 'text/html', 'my file', true);

        /** @var CDocument $document */
        $document = $documentRepo->find($document->getIid());
        $node = $document->getResourceNode();
        $this->assertTrue($node->hasResourceFile());
        $id = $document->getResourceNode()->getUuid()->toRfc4122();

        // View HTML document.
        $url = '/r/document/files/'.$id.'/view';
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $document = (new CDocument())
            ->setFiletype('file')
            ->setTitle('title')
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;
        $documentRepo->create($document);
        $resourceFile = $documentRepo->addFile($document, $this->getUploadedFile());
        $resourceFile->setCrop('100,100,100,100');
        $em->persist($resourceFile);
        $em->flush();

        $node = $document->getResourceNode();
        $this->assertTrue($node->hasResourceFile());
        $id = $document->getResourceNode()->getUuid()->toRfc4122();

        // View image document.
        $url = '/r/document/files/'.$id.'/view';
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        // View image document with params.
        $url = '/r/document/files/'.$id.'/view';
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    public function testLinkAction(): void
    {
        $client = static::createClient();
        $em = $this->getEntityManager();
        $admin = $this->getUser('admin');
        $client->loginUser($admin);

        $course = $this->createCourse('Test');

        $lpRepo = self::getContainer()->get(CLpRepository::class);

        $lp = (new CLp())
            ->setName('lp')
            ->setParent($course)
            ->setCreator($admin)
            ->setLpType(CLp::LP_TYPE)
        ;
        $lpRepo->createLp($lp);

        $url = '/r/learnpath/lps/'.$lp->getResourceNode()->getId().'/link?cid='.$course->getId();
        $client->request('GET', $url);

        $redirects = '/main/lp/lp_controller.php?lp_id='.$lp->getIid().'&action=view&cid='.$course->getId().'&sid=0';
        $this->assertResponseRedirects($redirects);

        $url = '/r/document/files/'.$lp->getResourceNode()->getId().'/link';
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(404);
    }
}
