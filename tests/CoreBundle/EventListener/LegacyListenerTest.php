<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\EventListener;

use Chamilo\CoreBundle\EventListener\LegacyListener;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

/**
 * LegacyListener is the single place that interprets the isStudentView query
 * parameter, normalizing it into the `studentview` session key that
 * StudentViewHelper reads. Every other reader goes through the helper, so the
 * cases below are the whole contract: which literals switch the state, that an
 * unrecognized value leaves it alone, and that the course.student_view_enabled
 * setting is honoured before anything is written.
 */
final class LegacyListenerTest extends TestCase
{
    /**
     * @dataProvider studentViewLiterals
     */
    public function testAcceptedLiteralsSwitchTheState(string $value, string $expected): void
    {
        // Must stay in sync with IndexController::toggleStudentView(): a link
        // built by one and read by the other has to agree on the vocabulary.
        self::assertSame($expected, $this->listenAndReadState('/courses/TEMP/?isStudentView='.$value));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function studentViewLiterals(): iterable
    {
        yield '1' => ['1', 'studentview'];

        yield 'true' => ['true', 'studentview'];

        yield 'yes' => ['yes', 'studentview'];

        yield 'on' => ['on', 'studentview'];

        yield '0' => ['0', 'teacherview'];

        yield 'false' => ['false', 'teacherview'];

        yield 'no' => ['no', 'teacherview'];

        yield 'off' => ['off', 'teacherview'];
    }

    public function testValueIsCaseInsensitiveAndTrimmed(): void
    {
        self::assertSame('studentview', $this->listenAndReadState('/courses/TEMP/?isStudentView='.rawurlencode(' TRUE ')));
    }

    public function testUnrecognizedValueLeavesTheStateUntouched(): void
    {
        // A malformed legacy link must not silently drop a teacher into student
        // view, nor kick a student out of it.
        self::assertSame(
            'studentview',
            $this->listenAndReadState('/courses/TEMP/?isStudentView=maybe', current: 'studentview')
        );
    }

    public function testMissingParameterInitializesToTeacherView(): void
    {
        self::assertSame('teacherview', $this->listenAndReadState('/courses/TEMP/'));
    }

    public function testMissingParameterPreservesAnExistingState(): void
    {
        self::assertSame('teacherview', $this->listenAndReadState('/courses/TEMP/', current: 'teacherview'));
        self::assertSame('studentview', $this->listenAndReadState('/courses/TEMP/', current: 'studentview'));
    }

    public function testDisabledSettingNeverWritesTheKey(): void
    {
        // With the feature off the key must not even be initialized: that is
        // what lets StudentViewHelper treat "no key" as "not applicable".
        self::assertNull($this->listenAndReadState('/courses/TEMP/?isStudentView=true', enabled: false));
    }

    public function testApiRequestDoesNotSwitchTheState(): void
    {
        // The api firewall shares the main session, so without this guard any GET to
        // /api/* could switch the whole browsing session, with no role check and from
        // any origin. Only /toggle_student_view is allowed to carry the parameter.
        self::assertSame(
            'teacherview',
            $this->listenAndReadState('/api/forums?cid=1&isStudentView=true', current: 'teacherview')
        );
    }

    public function testApiRequestStillInitializesTheState(): void
    {
        // CToolStateProvider reads a missing key as the student view, so leaving it
        // unset on API requests would hide course tools from everyone.
        self::assertSame('teacherview', $this->listenAndReadState('/api/forums?cid=1'));
    }

    public function testLegacyPageStillSwitchesTheState(): void
    {
        // The legacy student view links live under public/main, and the Playwright
        // suite navigates to them; the guard must only catch API paths.
        self::assertSame(
            'studentview',
            $this->listenAndReadState('/main/lp/lp_controller.php?cid=1&isStudentView=true')
        );
    }

    public function testSubRequestIsIgnored(): void
    {
        self::assertNull(
            $this->listenAndReadState(
                '/courses/TEMP/?isStudentView=true',
                requestType: HttpKernelInterface::SUB_REQUEST
            )
        );
    }

    /**
     * Runs the listener over a request and returns the resulting session value.
     */
    private function listenAndReadState(
        string $uri,
        ?string $current = null,
        bool $enabled = true,
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
    ): ?string {
        $request = Request::create($uri);
        $session = new Session(new MockArraySessionStorage());

        if (null !== $current) {
            $session->set('studentview', $current);
        }

        $request->setSession($session);

        $this->listener($enabled)(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $requestType
        ));

        return $session->get('studentview');
    }

    private function listener(bool $studentViewEnabled): LegacyListener
    {
        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager
            ->method('getSetting')
            ->willReturnCallback(
                fn (string $variable): string => 'course.student_view_enabled' === $variable
                    ? ($studentViewEnabled ? 'true' : 'false')
                    : ''
            )
        ;

        // AccessUrlHelper is readonly, so it cannot be doubled; a real one over a
        // mocked repository resolves to no access URL, which is all this needs.
        $accessUrlRepository = $this->createMock(AccessUrlRepository::class);

        return new LegacyListener(
            $this->createMock(Environment::class),
            $this->createMock(TokenStorageInterface::class),
            $accessUrlRepository,
            $this->createMock(RouterInterface::class),
            new AccessUrlHelper($accessUrlRepository, new RequestStack()),
            new ParameterBag(['installed' => true]),
            $settingsManager,
            $this->legacyContainer($settingsManager),
        );
    }

    /**
     * The listener boots the legacy globals before touching the student view,
     * so the container has to answer the two services that bootstrap needs.
     */
    private function legacyContainer(SettingsManager $settingsManager): ContainerInterface
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getConnection')->willReturn($this->createMock(Connection::class));
        $doctrine->method('getManager')->willReturn($this->createMock(EntityManager::class));

        $container = $this->createMock(ContainerInterface::class);
        $container->method('getParameter')->willReturnCallback(
            fn (string $name): mixed => 'installed' === $name ? true : null
        );
        $container
            ->method('get')
            ->willReturnCallback(fn (string $id): ?object => match ($id) {
                'doctrine' => $doctrine,
                SettingsManager::class => $settingsManager,
                default => null,
            })
        ;

        return $container;
    }
}
