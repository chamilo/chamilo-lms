<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security\Authenticator\Cas;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Security\Authenticator\Cas\CasLoginFlow;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

use const PHP_URL_QUERY;

final class CasLoginFlowTest extends TestCase
{
    private const array CONFIG = ['base' => 'https://cas.example.org/cas', 'validation' => '/serviceValidate', 'creation' => 'false', 'ldap' => false];

    public function testStateIsBoundToServiceAndConsumedOnce(): void
    {
        [$flow, $request, $portal, $service] = $this->start();
        self::assertSame($service, $flow->consume($request, $portal, self::CONFIG));
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $flow->consume($request, $portal, self::CONFIG);
    }

    public function testStateCannotMoveToAnotherPortal(): void
    {
        [$flow, $request] = $this->start();
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $flow->consume($request, $this->portal(2), self::CONFIG);
    }

    public function testIncorrectStateIsRejected(): void
    {
        [$flow, $request, $portal] = $this->start();
        $request->query->set('state', str_repeat('a', 64));
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $flow->consume($request, $portal, self::CONFIG);
    }

    public function testConfigurationChangeInvalidatesPendingLogin(): void
    {
        [$flow, $request, $portal] = $this->start();
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $flow->consume($request, $portal, array_replace(self::CONFIG, ['base' => 'https://other.example.org']));
    }

    public function testExpiredStateIsRejected(): void
    {
        [$flow, $request, $portal] = $this->start();
        $session = $request->getSession();
        $pending = $session->get(CasLoginFlow::SESSION_KEY);
        $pending['created'] = time() - 301;
        $session->set(CasLoginFlow::SESSION_KEY, $pending);
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $flow->consume($request, $portal, self::CONFIG);
    }

    private function start(): array
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnCallback(static fn (string $route, array $parameters): string => '/connect/cas/check?state='.$parameters['state']);
        $flow = new CasLoginFlow($router);
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('https://portal.example.org/connect/cas');
        $request->setSession($session);
        $portal = $this->portal(1);
        $target = $flow->start($request, $portal, self::CONFIG);
        parse_str((string) parse_url($target, PHP_URL_QUERY), $query);
        $service = $query['service'];
        $callback = Request::create($service.'&ticket=ST-example');
        $callback->setSession($session);

        return [$flow, $callback, $portal, $service];
    }

    private function portal(int $id): AccessUrl
    {
        $portal = new AccessUrl();
        (new ReflectionProperty(AccessUrl::class, 'id'))->setValue($portal, $id);

        return $portal;
    }
}
