<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\LtiBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\LtiBundle\EventSubscriber\LtiProviderRequestSubscriber;
use Chamilo\LtiBundle\Security\LtiProviderLaunchToken;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * A launch token proves a launch on the Access URL that issued it, and nowhere else: the same
 * installation signs every portal's tokens with one secret, so without the binding a token
 * minted on one portal would restore its user's session on a sibling portal.
 */
final class LtiProviderRequestSubscriberTest extends TestCase
{
    private const SECRET = 'test-secret';

    public function testTokenIssuedForAnotherAccessUrlDoesNotReachUserLookup(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getRepository');

        $this->handle($this->token(['access_url_id' => 2]), $entityManager);
    }

    public function testTokenWithoutAccessUrlBindingDoesNotReachUserLookup(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getRepository');

        $this->handle($this->token([]), $entityManager);
    }

    public function testTokenIssuedForCurrentAccessUrlIsProcessed(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('find')->with(1001)->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('getRepository')->with(User::class)->willReturn($repository);

        $this->handle($this->token(['access_url_id' => 1]), $entityManager);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function token(array $extra): string
    {
        return (new LtiProviderLaunchToken(self::SECRET))->createToken($extra + [
            'user_id' => 1001,
            'course_id' => 1,
            'course_code' => 'TEMP',
            'launch_id' => 'launch-1',
            'exp' => time() + 600,
        ]);
    }

    private function handle(string $token, EntityManagerInterface $entityManager): void
    {
        $accessUrl = new AccessUrl();
        (new ReflectionProperty(AccessUrl::class, 'id'))->setValue($accessUrl, 1);

        $accessUrlRepository = $this->createMock(AccessUrlRepository::class);
        $accessUrlRepository->method('getFirstId')->willReturn(1);
        $accessUrlRepository->method('find')->willReturn($accessUrl);
        $accessUrlRepository->method('count')->willReturn(2);
        $accessUrlRepository->method('findOneBy')->willReturn($accessUrl);

        $request = Request::create('/main/lp/lp_controller.php', 'GET', ['lti_provider_token' => $token]);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $subscriber = new LtiProviderRequestSubscriber(
            new LtiProviderLaunchToken(self::SECRET),
            $entityManager,
            new TokenStorage(),
            new NullLogger(),
            new AccessUrlHelper($accessUrlRepository, $requestStack),
        );

        $subscriber->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));
    }
}
