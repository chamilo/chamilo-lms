<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Service\EtherpadClient;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final readonly class EtherpadSessionSubscriber implements EventSubscriberInterface
{
    private const string COOKIE_NAME = 'sessionID';

    public function __construct(
        private EtherpadClient $etherpadClient,
        private LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => ['onLogout', 0],
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $sessionIds = $this->extractSessionIds((string) $request->cookies->get(self::COOKIE_NAME, ''));

        if ([] !== $sessionIds && $this->etherpadClient->isConfigured()) {
            foreach ($sessionIds as $sessionId) {
                try {
                    $this->etherpadClient->deleteSession($sessionId);
                } catch (RuntimeException $exception) {
                    $this->logger->warning('Unable to revoke an Etherpad session during logout.', [
                        'exception' => $exception,
                    ]);
                }
            }
        }

        $response = $event->getResponse();
        if (null === $response) {
            return;
        }

        $response->headers->setCookie(
            Cookie::create(self::COOKIE_NAME)
                ->withValue('')
                ->withExpires(1)
                ->withPath('/')
                ->withSecure($request->isSecure())
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX)
        );
    }

    /**
     * @return list<string>
     */
    private function extractSessionIds(string $cookieValue): array
    {
        $sessionIds = [];

        foreach (explode(',', $cookieValue) as $sessionId) {
            $sessionId = trim($sessionId);
            if (!str_starts_with($sessionId, 's.') || mb_strlen($sessionId) > 128) {
                continue;
            }

            $sessionIds[$sessionId] = true;
        }

        return \array_slice(array_keys($sessionIds), -10);
    }
}
