<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\EventSubscriber\EtherpadSessionSubscriber;
use Chamilo\CoreBundle\Service\EtherpadClient;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

use const JSON_THROW_ON_ERROR;

final class EtherpadSessionSubscriberTest extends TestCase
{
    public function testLogoutRevokesEtherpadSessionsAndClearsCookie(): void
    {
        $httpClient = new MockHttpClient([
            $this->jsonResponse([
                'code' => 0,
                'message' => 'ok',
                'data' => null,
            ]),
            $this->jsonResponse([
                'code' => 0,
                'message' => 'ok',
                'data' => null,
            ]),
        ]);
        $subscriber = new EtherpadSessionSubscriber(
            $this->createConfiguredClient($httpClient),
            new NullLogger(),
        );

        $request = Request::create('https://campus.example.org/logout');
        $request->cookies->set('sessionID', 's.first,s.second,invalid');
        $event = new LogoutEvent($request, null);
        $event->setResponse(new Response());

        $subscriber->onLogout($event);

        self::assertSame(2, $httpClient->getRequestsCount());

        $response = $event->getResponse();
        self::assertInstanceOf(Response::class, $response);

        $cookie = null;
        foreach ($response->headers->getCookies() as $candidate) {
            if ('sessionID' === $candidate->getName()) {
                $cookie = $candidate;

                break;
            }
        }

        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertSame('', $cookie->getValue());
        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    private function createConfiguredClient(MockHttpClient $httpClient): EtherpadClient
    {
        $settings = [
            'editor.etherpad_enabled' => 'true',
            'editor.etherpad_url' => 'https://campus.example.org/etherpad',
            'editor.etherpad_api_key' => 'secret-key',
        ];
        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager
            ->method('getSetting')
            ->willReturnCallback(static fn (string $name): ?string => $settings[$name] ?? null)
        ;

        return new EtherpadClient($httpClient, $settingsManager);
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
