<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Controller\SearchController;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;

final class SearchDocumentOpenUrlTest extends TestCase
{
    public function testLinkUsesCurrentDocumentUuidAndSessionContext(): void
    {
        $uuid = Uuid::v4();
        $node = $this->documentNode($uuid, 13, 7);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('find')->with(ResourceNode::class, 99)->willReturn($node);
        $router = $this->createMock(RouterInterface::class);
        $url = '/r/document/files/'.$uuid.'/view?cid=13&sid=7&gid=0';
        $router->expects(self::once())->method('generate')->with('chamilo_core_resource_view', [
            'tool' => 'document', 'type' => 'files', 'id' => (string) $uuid, 'cid' => 13, 'sid' => 7, 'gid' => 0,
        ])->willReturn($url);

        self::assertSame($url, $this->resolve($em, $router, ['is_accessible' => true, 'resource_node_id' => 99, 'course_id' => 13], 7));
    }

    public function testRestrictedAndStaleHitsNeverResolveAFile(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('find');
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');
        $data = ['resource_node_id' => 99, 'course_id' => 13];

        self::assertNull($this->resolve($em, $router, $data + ['is_accessible' => false], 7));
        self::assertNull($this->resolve($em, $router, $data + ['is_accessible' => true, 'search_is_stale' => true], 7));
    }

    public function testUnrelatedCourseSessionAndMissingNodeNeverGenerateAUrl(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn($this->documentNode(Uuid::v4(), 13, 7), $this->documentNode(Uuid::v4(), 13, 7), null);
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');
        $data = ['is_accessible' => true, 'resource_node_id' => 99];

        self::assertNull($this->resolve($em, $router, $data + ['course_id' => 12], 7));
        self::assertNull($this->resolve($em, $router, $data + ['course_id' => 13], 8));
        self::assertNull($this->resolve($em, $router, $data + ['course_id' => 13], 7));
    }

    private function documentNode(Uuid $uuid, int $courseId, int $sessionId): ResourceNode
    {
        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn($courseId);
        $session = $this->createMock(Session::class);
        $session->method('getId')->willReturn($sessionId);
        $link = (new ResourceLink())->setCourse($course)->setSession($session);
        $type = (new ResourceType())->setTitle('files')->setTool((new Tool())->setTitle('document'));
        $node = $this->createMock(ResourceNode::class);
        $node->method('getUuid')->willReturn($uuid);
        $node->method('hasResourceFile')->willReturn(true);
        $node->method('getResourceType')->willReturn($type);
        $node->method('getResourceLinks')->willReturn(new ArrayCollection([$link]));

        return $node;
    }

    private function resolve(EntityManagerInterface $em, RouterInterface $router, array $data, int $sessionId): ?string
    {
        $controller = (new ReflectionClass(SearchController::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(SearchController::class, 'em'))->setValue($controller, $em);
        $container = new Container();
        $container->set('router', $router);
        $controller->setContainer($container);

        return (new ReflectionMethod(SearchController::class, 'resolveDocumentOpenUrl'))->invoke($controller, $data, $sessionId);
    }
}
