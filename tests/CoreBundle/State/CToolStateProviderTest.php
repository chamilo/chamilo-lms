<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use const JSON_THROW_ON_ERROR;

final class CToolStateProviderTest extends WebTestCase
{
    use ChamiloTestTrait;

    /**
     * A ROLE_GLOBAL_ADMIN inherits ROLE_ADMIN through Symfony's role hierarchy,
     * but User::hasRole()/User::isAdmin() only inspect the user's literal roles.
     *
     * A draft course tool must therefore remain in /api/c_tools for a global
     * administrator so the course-home visibility control can publish it again.
     *
     * @throws JsonException
     */
    public function testGlobalAdminReceivesDraftCourseTool(): void
    {
        $client = self::createClient();

        $course = $this->createCourse('Global admin course tool visibility');
        self::assertNotNull($course);

        $globalAdmin = $this->createUser(
            'ctool_global_admin',
            '',
            '',
            'ROLE_GLOBAL_ADMIN'
        );
        self::assertNotNull($globalAdmin);
        self::assertTrue($globalAdmin->hasRole('ROLE_GLOBAL_ADMIN'));
        self::assertFalse($globalAdmin->hasRole('ROLE_ADMIN'));

        $documentTool = null;

        foreach ($course->getTools() as $courseTool) {
            if (
                $courseTool instanceof CTool
                && 'document' === $courseTool->getTool()->getTitle()
            ) {
                $documentTool = $courseTool;

                break;
            }
        }

        self::assertInstanceOf(CTool::class, $documentTool);

        $resourceNode = $documentTool->getResourceNode();
        self::assertNotNull($resourceNode);

        $courseLink = null;

        foreach ($resourceNode->getResourceLinks() as $resourceLink) {
            if (
                $resourceLink instanceof ResourceLink
                && $resourceLink->getCourse()?->getId() === $course->getId()
                && null === $resourceLink->getSession()
            ) {
                $courseLink = $resourceLink;

                break;
            }
        }

        self::assertInstanceOf(ResourceLink::class, $courseLink);

        $courseLink->setVisibility(ResourceLink::VISIBILITY_DRAFT);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->flush();

        $client->loginUser($globalAdmin);
        $client->request(
            'GET',
            '/api/c_tools?cid='.$course->getId().'&sid=0',
            [],
            [],
            ['HTTP_ACCEPT' => 'application/ld+json']
        );

        self::assertResponseIsSuccessful();

        $data = json_decode(
            (string) $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $document = null;

        foreach ($data['hydra:member'] ?? $data['member'] ?? [] as $courseTool) {
            if ('document' === ($courseTool['tool']['title'] ?? null)) {
                $document = $courseTool;

                break;
            }
        }

        self::assertNotNull(
            $document,
            'A ROLE_GLOBAL_ADMIN must receive draft course tools so they can be made visible again.'
        );
        self::assertFalse($document['visibility']);
    }
}
