<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\EtherpadClient;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CWiki;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

use const JSON_THROW_ON_ERROR;

final class EtherpadClientTest extends TestCase
{
    public function testSameOriginConfigurationIsRequiredForEmbeddedSessions(): void
    {
        $client = $this->createClient(new MockHttpClient(), [
            'editor.etherpad_enabled' => 'true',
            'editor.etherpad_url' => 'https://campus.example.org/etherpad',
            'editor.etherpad_api_key' => 'secret-key',
        ]);

        $client->assertReady(Request::create('https://campus.example.org/courses/1'));
        self::assertTrue($client->isEnabled());
        self::assertTrue($client->isConfigured());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('same origin');
        $client->assertReady(Request::create('https://other.example.org/courses/1'));
    }

    public function testCreatesDeterministicGroupPadAndSeedsWikiContent(): void
    {
        $httpClient = new MockHttpClient([
            $this->jsonResponse([
                'code' => 0,
                'message' => 'ok',
                'data' => ['groupID' => 'g.test'],
            ]),
            $this->jsonResponse([
                'code' => 0,
                'message' => 'ok',
                'data' => ['padID' => 'g.test$wiki-42'],
            ]),
            $this->jsonResponse([
                'code' => 0,
                'message' => 'ok',
                'data' => null,
            ]),
        ]);
        $client = $this->createConfiguredClient($httpClient);
        $wiki = $this->createWiki(42, '<p>Initial Wiki content</p>');

        self::assertSame('g.test$wiki-42', $client->ensurePad($wiki));
        self::assertSame(3, $httpClient->getRequestsCount());
    }

    public function testExistingPadIdentifierDoesNotCreateAnotherPad(): void
    {
        $httpClient = new MockHttpClient();
        $client = $this->createConfiguredClient($httpClient);
        $wiki = $this->createWiki(42, '<p>Content</p>');
        $wiki->setEtherpadId('g.existing$wiki-42');

        self::assertSame('g.existing$wiki-42', $client->ensurePad($wiki));
        self::assertSame(0, $httpClient->getRequestsCount());
    }

    public function testExistingDeterministicPadIsReseededWhenLatestWikiVersionHasNoPadId(): void
    {
        $httpClient = new MockHttpClient([
            $this->jsonResponse([
                'code' => 0,
                'message' => 'ok',
                'data' => ['groupID' => 'g.test'],
            ]),
            $this->jsonResponse([
                'code' => 1,
                'message' => 'padName does already exist',
                'data' => null,
            ]),
            $this->jsonResponse([
                'code' => 0,
                'message' => 'ok',
                'data' => null,
            ]),
        ]);
        $client = $this->createConfiguredClient($httpClient);
        $wiki = $this->createWiki(42, '<p>Content saved while Etherpad was disabled</p>');

        self::assertSame('g.test$wiki-42', $client->ensurePad($wiki));
        self::assertSame(3, $httpClient->getRequestsCount());
    }

    public function testCreatesAuthorSessionAndEditorUrl(): void
    {
        $httpClient = new MockHttpClient([
            $this->jsonResponse([
                'code' => 0,
                'message' => 'ok',
                'data' => ['authorID' => 'a.user'],
            ]),
            $this->jsonResponse([
                'code' => 0,
                'message' => 'ok',
                'data' => ['sessionID' => 's.session'],
            ]),
        ]);
        $client = $this->createConfiguredClient($httpClient);
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(7);
        $user->method('getFullName')->willReturn('John Doe');

        $session = $client->createEditorSession($user, 'g.test$wiki-42');

        self::assertSame('s.session', $session['sessionId']);
        self::assertStringStartsWith(
            'https://campus.example.org/etherpad/p/g.test%24wiki-42?',
            $session['editorUrl'],
        );
        self::assertGreaterThan(time(), $session['expiresAt']);
    }

    public function testReadsCurrentPadHtml(): void
    {
        $client = $this->createConfiguredClient(new MockHttpClient([
            $this->jsonResponse([
                'code' => 0,
                'message' => 'ok',
                'data' => ['html' => '<p>Collaborative content</p>'],
            ]),
        ]));

        self::assertSame(
            '<p>Collaborative content</p>',
            $client->getHtml('g.test$wiki-42'),
        );
    }

    public function testDeletesSessionAndAcceptsAlreadyDeletedSession(): void
    {
        $httpClient = new MockHttpClient([
            $this->jsonResponse([
                'code' => 0,
                'message' => 'ok',
                'data' => null,
            ]),
            $this->jsonResponse([
                'code' => 1,
                'message' => 'sessionID does not exist',
                'data' => null,
            ]),
        ]);
        $client = $this->createConfiguredClient($httpClient);

        $client->deleteSession('s.first');
        $client->deleteSession('s.already-deleted');

        self::assertSame(2, $httpClient->getRequestsCount());
    }

    public function testApiErrorsDoNotExposeConfiguredApiKey(): void
    {
        $client = $this->createConfiguredClient(new MockHttpClient([
            $this->jsonResponse([
                'code' => 1,
                'message' => 'Rejected secret-key while deleting the pad',
                'data' => null,
            ]),
        ]));

        try {
            $client->deletePad('g.test$wiki-42');
            self::fail('A failed Etherpad API call must throw an exception.');
        } catch (RuntimeException $exception) {
            self::assertStringNotContainsString('secret-key', $exception->getMessage());
            self::assertStringContainsString('[redacted]', $exception->getMessage());
        }
    }

    private function createConfiguredClient(MockHttpClient $httpClient): EtherpadClient
    {
        return $this->createClient($httpClient, [
            'editor.etherpad_enabled' => 'true',
            'editor.etherpad_url' => 'https://campus.example.org/etherpad',
            'editor.etherpad_api_key' => 'secret-key',
        ]);
    }

    /**
     * @param array<string, string> $settings
     */
    private function createClient(MockHttpClient $httpClient, array $settings): EtherpadClient
    {
        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager
            ->method('getSetting')
            ->willReturnCallback(static fn (string $name): ?string => $settings[$name] ?? null)
        ;

        return new EtherpadClient($httpClient, $settingsManager);
    }

    private function createWiki(int $pageId, string $content): CWiki
    {
        return (new CWiki())
            ->setCId(10)
            ->setPageId($pageId)
            ->setSessionId(0)
            ->setGroupId(0)
            ->setContent($content)
        ;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(array $payload): MockResponse
    {
        return new MockResponse(
            json_encode($payload, JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type: application/json']],
        );
    }
}
