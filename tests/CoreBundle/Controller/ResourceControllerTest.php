<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

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

        $urlDownload = '/r/document/files/'.$id.'/download';

        $client->request('GET', $urlDownload);
        $this->assertResponseIsSuccessful();
    }
}