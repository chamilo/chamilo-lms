<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class LearningPathEmbeddedImageBackupTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testPartialBackupResolvesModernDocumentUrlsByUuidInTheSourceCourse(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
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

        try {
            $suffix = bin2hex(random_bytes(4));
            $course = $this->createCourse('LP backup source '.$suffix);
            $foreignCourse = $this->createCourse('LP backup foreign '.$suffix);
            self::assertNotNull($course);
            self::assertNotNull($foreignCourse);
            $session->set('_real_cid', $course->getId());
            $session->set('_cid', $course->getCode());
            $session->set('_course', api_get_course_info($course->getCode()));

            $repo = $container->get(CDocumentRepository::class);
            $folder = (new CDocument())
                ->setTitle('QA images')
                ->setFiletype('folder')
                ->setParent($course)
                ->setCreator($admin)
                ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
            ;
            $repo->create($folder);

            $image = $this->createImage($course, $folder, 'nested.png');
            $absolute = $this->createImage($course, $course, 'absolute.png');
            $legacy = $this->createImage($course, $course, 'legacy.png');
            $unused = $this->createImage($course, $course, 'unreferenced.png');
            $foreign = $this->createImage($foreignCourse, $foreignCourse, 'unreferenced.png');
            $imageUuid = (string) $image->getResourceNode()->getUuid();
            $absoluteUuid = (string) $absolute->getResourceNode()->getUuid();
            $unusedUuid = (string) $unused->getResourceNode()->getUuid();
            $foreignUuid = (string) $foreign->getResourceNode()->getUuid();

            $builder = new CourseBuilder('partial', api_get_course_info($course->getCode()));
            $builder->set_tools_to_build([]);
            $builder->build(0, $course->getCode(), true);
            $builder->findAndSetDocumentsInText(
                '<img src="/r/document/files/'.$imageUuid.'/view">'
                .'<img src="/r/document/files/'.$imageUuid.'/view?cid='.$course->getId().'&amp;sid=0">'
                .'<img src="'.rtrim(api_get_path(WEB_PATH), '/').'/r/document/files/'.$absoluteUuid.'/view">'
                .'<img src="/document/legacy.png">'
                .'<img src="/r/document/files/'.$foreignUuid.'/view">'
                .'<img src="https://external.invalid/r/document/files/'.$unusedUuid.'/view">'
                .'<img src="//external.invalid/r/document/files/'.$unusedUuid.'/view">'
            );
            $builder->restoreDocumentsFromList($course);

            $resources = $builder->course->resources[RESOURCE_DOCUMENT] ?? [];
            self::assertArrayHasKey($image->getIid(), $resources, 'An extensionless image URL must include the actual image.');
            self::assertSame($imageUuid, $resources[$image->getIid()]->resource_node_uuid);
            self::assertArrayHasKey($folder->getIid(), $resources, 'The image folder must be included.');
            self::assertArrayHasKey($absolute->getIid(), $resources, 'Absolute URLs on this portal must resolve.');
            self::assertArrayHasKey($legacy->getIid(), $resources, 'Legacy image paths must still resolve.');
            self::assertArrayNotHasKey($foreign->getIid(), $resources, 'A reference to another course is not an export authorization.');
            self::assertArrayNotHasKey($unused->getIid(), $resources, 'A foreign UUID or external origin must not match a local file.');
            self::assertCount(4, $resources);
        } finally {
            $requestStack->pop();
        }
    }

    private function createImage(Course $course, CDocument|Course $parent, string $title): CDocument
    {
        $repo = self::getContainer()->get(CDocumentRepository::class);
        $document = (new CDocument())
            ->setTitle($title)
            ->setFiletype('file')
            ->setParent($parent)
            ->setCreator($this->getAdmin())
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;
        $repo->create($document);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRfoAAAAASUVORK5CYII=', true);
        self::assertNotFalse($png);
        $repo->addFileFromString($document, $title, 'image/png', $png, true);

        return $document;
    }
}
