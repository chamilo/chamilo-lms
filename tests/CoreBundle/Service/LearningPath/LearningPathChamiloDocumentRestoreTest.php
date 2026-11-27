<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Component\CourseCopy\Course as LegacyCourse;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Document;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class LearningPathChamiloDocumentRestoreTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testChamiloZipDocumentEntriesRestoreFilesAndLearningPathReferences(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        Database::setManager($this->getEntityManager());
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/');
        $request->setSession($session);
        Container::setRequest($request);
        $requestStack = $container->get('request_stack');
        $requestStack->push($request);
        $admin = $this->getAdmin();
        $container->get('security.token_storage')->setToken(new UsernamePasswordToken($admin, 'main', $admin->getRoles()));
        $session->set('_user', ['id' => $admin->getId(), 'user_id' => $admin->getId()]);
        $root = sys_get_temp_dir().'/chamilo-document-restore-'.bin2hex(random_bytes(8));
        $filesystem = new Filesystem();

        try {
            $course = $this->createCourse('LP document restore '.bin2hex(random_bytes(4)));
            self::assertNotNull($course);
            $session->set('_real_cid', $course->getId());
            $session->set('_cid', $course->getCode());
            $courseInfo = api_get_course_info($course->getCode());
            $session->set('_course', $courseInfo);
            $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRfoAAAAASUVORK5CYII=', true);
            self::assertNotFalse($png);
            $rootUuid = '88c893cd-4bbe-4df3-b8e2-7a8d1016e946';
            $nestedUuid = '870e1667-b079-4209-a64d-5684a4a555e9';
            $html = '<p>Restored page</p><img src="/r/document/files/'.$rootUuid.'/view"><img src="/r/document/files/'.$nestedUuid.'/view">';
            $filesystem->mkdir($root.'/document/QA images');
            $filesystem->dumpFile($root.'/document/root.png', $png);
            $filesystem->dumpFile($root.'/document/QA images/nested.png', $png);
            $filesystem->dumpFile($root.'/document/page.html', $html);
            $filesystem->dumpFile($root.'/document/legacy.png', $png);
            $filesystem->dumpFile($root.'/document/legacy.html', '<p>Legacy HTML</p>');

            // These are the actual metadata values produced by the Chamilo ZIP exporter.
            // Sequential bucket keys reproduce its staging format, not the source IIDs.
            $folder = new Document(70001, 'QA images', '', 'QA images', 'folder', 0);
            $rootImage = new Document(70002, 'root.png', '', 'root.png', 'document', \strlen($png), ResourceLink::VISIBILITY_PUBLISHED, $rootUuid);
            $nestedImage = new Document(70003, 'QA images/nested.png', '', 'nested.png', 'document', \strlen($png), ResourceLink::VISIBILITY_PUBLISHED, $nestedUuid);
            $page = new Document(70004, 'page.html', '', 'page.html', 'document', \strlen($html));
            $legacyImage = new Document(70005, 'legacy.png', '', 'legacy.png', DOCUMENT, \strlen($png));
            $legacyPage = new Document(70006, 'legacy.html', '', 'legacy.html', 'html', 18);
            $lp = (object) [
                'title' => 'Restored document LP',
                'linked_resources' => [],
                'items' => [[
                    'id' => 70008,
                    'parent_item_id' => 0,
                    'title' => 'Embedded images',
                    'item_type' => 'document',
                    'path' => '70004',
                    'ref' => '70004',
                    'display_order' => 1,
                ]],
            ];
            $backup = new LegacyCourse();
            $backup->code = $course->getCode();
            $backup->backup_path = $root;
            $backup->resources = [
                RESOURCE_DOCUMENT => [$folder, $rootImage, $nestedImage, $page, $legacyImage, $legacyPage],
                RESOURCE_LEARNPATH => [70007 => $lp],
                '__meta' => ['archiver_root' => $root],
            ];

            $restorer = new CourseRestorer($backup);
            $restorer->destination_course_id = $course->getId();
            $restorer->destination_course_info = $courseInfo;
            $restorer->restore_learnpaths(0, false, $course->getCode());

            $em = $this->getEntityManager();
            $nodeRepo = $container->get(ResourceNodeRepository::class);
            $restored = [];
            foreach ([$folder, $rootImage, $nestedImage, $page, $legacyImage, $legacyPage] as $entry) {
                self::assertGreaterThan(0, $entry->destination_id, 'The backup entry must map to a destination document.');
                $document = $em->find(CDocument::class, $entry->destination_id);
                self::assertInstanceOf(CDocument::class, $document);
                $link = $document->getResourceNode()->getResourceLinkByContext($course);
                self::assertNotNull($link);
                self::assertSame($course->getId(), $link->getCourse()->getId());
                $restored[$entry->source_id] = $document;
            }
            self::assertSame($restored[70001]->getResourceNode()->getId(), $restored[70003]->getResourceNode()->getParent()->getId());
            foreach ([70002, 70003, 70005] as $sourceId) {
                self::assertSame(DOCUMENT, $restored[$sourceId]->getFiletype());
                self::assertSame($png, $nodeRepo->getResourceNodeFileContent($restored[$sourceId]->getResourceNode()));
            }
            self::assertSame('html', $restored[70004]->getFiletype());
            self::assertSame('<p>Legacy HTML</p>', $nodeRepo->getResourceNodeFileContent($restored[70006]->getResourceNode()));
            $restoredHtml = $nodeRepo->getResourceNodeFileContent($restored[70004]->getResourceNode());
            self::assertStringContainsString('<p>Restored page</p>', $restoredHtml);
            self::assertStringNotContainsString($rootUuid, $restoredHtml);
            self::assertStringNotContainsString($nestedUuid, $restoredHtml);
            self::assertStringContainsString((string) $restored[70002]->getResourceNode()->getUuid(), $restoredHtml);
            self::assertStringContainsString((string) $restored[70003]->getResourceNode()->getUuid(), $restoredHtml);

            $restoredLp = $em->find(CLp::class, $lp->destination_id);
            self::assertInstanceOf(CLp::class, $restoredLp);
            $item = $em->getRepository(CLpItem::class)->findOneBy(['lp' => $restoredLp, 'itemType' => 'document']);
            self::assertInstanceOf(CLpItem::class, $item);
            self::assertSame((string) $restored[70004]->getIid(), $item->getPath());
        } finally {
            $filesystem->remove($root);
            $requestStack->pop();
        }
    }
}
