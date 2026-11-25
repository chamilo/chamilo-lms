<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use SessionManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class SessionAssignmentDuplicationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // Legacy code reads the current user from the HTTP session and queries through the
        // Database wrapper; both are normally prepared by LegacyListener.
        $httpSession = new HttpSession(new MockArraySessionStorage());
        $httpSession->set('_user', ['id' => $this->getAdmin()->getId()]);
        $httpSession->set('access_url_id', $this->getAccessUrl()->getId());
        $request = new Request();
        $request->setSession($httpSession);
        // Container::getSession() prefers this static over the request stack, and an earlier test in
        // the same process leaves its own session there, so point it at this test's session.
        Container::setSession($httpSession);
        Container::setRequest($request);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        $admin = $this->getAdmin();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($admin, 'main', $admin->getRoles())
        );
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());
    }

    public function testImportAssignmentsAndGradebooksReuseOneCopyPerSession(): void
    {
        $course = $this->createCourse('Session assignment import');
        $first = $this->createSession('Import assignments and gradebook');
        $second = $this->createSession('Import gradebook only');
        $third = $this->createSession('Import assignments only');
        $em = $this->getEntityManager();
        $teacher = $this->getAdmin();
        $assignment = (new CStudentPublication())
            ->setTitle('Base linked assignment')
            ->setUser($teacher)
            ->setCreator($teacher)
            ->setParent($course)
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;
        $unlinked = (new CStudentPublication())
            ->setTitle('Base unlinked assignment')
            ->setUser($teacher)
            ->setCreator($teacher)
            ->setParent($course)
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;
        $category = (new GradebookCategory())
            ->setTitle('Base gradebook')
            ->setCourse($course)
            ->setUser($teacher)
            ->setWeight(100)
            ->setVisible(true)
        ;
        $em->persist($assignment);
        $em->persist($unlinked);
        $em->persist($category);
        $em->flush();
        $link = (new GradebookLink())
            ->setType(GradebookLinkResourceResolver::LINK_STUDENT_PUBLICATION)
            ->setCourse($course)
            ->setCategory($category)
            ->setResourceNode($assignment->getResourceNode())
            ->setWeight(100)
        ;
        $em->persist($link);
        $em->flush();
        $courseId = $course->getId();
        $firstId = $first->getId();
        $secondId = $second->getId();
        $thirdId = $third->getId();
        $sourceId = $assignment->getIid();
        $unlinkedId = $unlinked->getIid();
        $sourceNodeId = $assignment->getResourceNode()->getId();
        $sourceLinkId = $link->getId();
        $em->clear();

        self::assertTrue(SessionManager::add_courses_to_session($firstId, [$courseId], false, true, false, true));
        $em->clear();
        self::assertTrue(SessionManager::add_courses_to_session($secondId, [$courseId], false, true));
        $em->clear();
        self::assertTrue(SessionManager::add_courses_to_session($thirdId, [$courseId], false, false, false, true));
        $em->clear();
        self::assertTrue(SessionManager::add_courses_to_session($firstId, [$courseId], false, true, false, true));
        $em->clear();
        $repo = $em->getRepository(CStudentPublication::class);
        $copies = $repo->findBy(['publicationParent' => $sourceId, 'filetype' => 'folder']);
        self::assertCount(3, $copies);
        $unlinkedCopies = $repo->findBy(['publicationParent' => $unlinkedId, 'filetype' => 'folder']);
        self::assertCount(2, $unlinkedCopies);
        foreach ($unlinkedCopies as $copy) {
            self::assertContains($copy->getFirstResourceLink()->getSession()?->getId(), [$firstId, $thirdId]);
        }
        foreach ($copies as $copy) {
            $sid = $copy->getFirstResourceLink()->getSession()?->getId();
            self::assertContains($sid, [$firstId, $secondId, $thirdId]);
            $sessionLinks = $em->getRepository(GradebookLink::class)->findBy(['resourceNode' => $copy->getResourceNode()]);
            if ($thirdId === $sid) {
                self::assertCount(0, $sessionLinks);
            } else {
                self::assertCount(1, $sessionLinks);
                self::assertSame($sid, $sessionLinks[0]->getCategory()->getSession()?->getId());
            }
        }
        self::assertSame($sourceNodeId, $em->find(GradebookLink::class, $sourceLinkId)->getRefId());
    }
}
