<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\TicketCategory;
use Chamilo\CoreBundle\Entity\TicketProject;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * A ticket is written by a student and read by an administrator, so the detail endpoint
 * must not hand the administrator's browser a student's event-handler attributes.
 */
class TicketDetailHtmlSanitizationTest extends WebTestCase
{
    use ChamiloTestTrait;

    private const ACCEPT_LANGUAGE = 'en-US,en;q=0.9';

    public function testStudentScriptHooksAreStrippedFromTheTicketDetail(): void
    {
        $client = static::createClient();
        $this->enableStudentTickets();
        $id = $this->fileTicketAsStudent(
            $client,
            '<p>Please escalate me</p><img src="x" onerror="alert(document.domain)">'
        );

        $client->loginUser($this->getUser('admin'));
        $client->request('GET', '/api/ticket/'.$id, [], [], ['HTTP_ACCEPT_LANGUAGE' => self::ACCEPT_LANGUAGE]);

        $this->assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();

        $this->assertStringContainsString('Please escalate me', $content, 'Legitimate ticket text must survive.');
        $this->assertStringNotContainsString('onerror', $content, 'Event-handler attributes must not reach the administrator.');
    }

    public function testStudentScriptHooksAreStrippedFromTicketReplies(): void
    {
        $client = static::createClient();
        $this->enableStudentTickets();
        $id = $this->fileTicketAsStudent($client, '<p>First message</p>');

        $client->jsonRequest(
            'POST',
            '/api/ticket/'.$id.'/reply',
            ['content' => '<p>Follow-up text</p><img src="x" onerror="alert(document.domain)">'],
            ['HTTP_ACCEPT_LANGUAGE' => self::ACCEPT_LANGUAGE, 'HTTP_SEC_FETCH_SITE' => 'same-origin']
        );
        $this->assertResponseIsSuccessful();

        $client->loginUser($this->getUser('admin'));
        $client->request('GET', '/api/ticket/'.$id, [], [], ['HTTP_ACCEPT_LANGUAGE' => self::ACCEPT_LANGUAGE]);

        $this->assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();

        $this->assertStringContainsString('Follow-up text', $content, 'Legitimate reply text must survive.');
        $this->assertStringNotContainsString('onerror', $content, 'Event-handler attributes must not reach the administrator.');
    }

    private function enableStudentTickets(): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting('ticket.ticket_allow_student_add', 'true');
    }

    private function fileTicketAsStudent(KernelBrowser $client, string $content): int
    {
        $em = $this->getEntityManager();
        $project = $em->getRepository(TicketProject::class)->findOneBy([]);
        $this->assertNotNull($project, 'The test database must contain a ticket project.');
        $category = $em->getRepository(TicketCategory::class)->findOneBy(['project' => $project]);
        $this->assertNotNull($category, 'The test database must contain a ticket category.');

        $client->loginUser($this->createUser('ticket_xss_student'));
        $client->jsonRequest(
            'POST',
            '/api/ticket/create',
            [
                'projectId' => $project->getId(),
                'categoryId' => $category->getId(),
                'subject' => 'Role review request',
                'content' => $content,
            ],
            ['HTTP_ACCEPT_LANGUAGE' => self::ACCEPT_LANGUAGE, 'HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        return (int) json_decode((string) $client->getResponse()->getContent(), true)['id'];
    }
}
