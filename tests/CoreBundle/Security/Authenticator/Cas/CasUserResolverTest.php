<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security\Authenticator\Cas;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Legal;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\LegalRepository;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Security\Authenticator\Cas\CasProfileResolver;
use Chamilo\CoreBundle\Security\Authenticator\Cas\CasUserResolver;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class CasUserResolverTest extends TestCase
{
    private const array IDENTITY = ['username' => 'cas_student', 'attributes' => ['firstname' => ['CAS'], 'lastname' => ['Student'], 'email' => ['student@example.org'], 'role' => ['ROLE_ADMIN']]];
    private const array CONFIG = ['creation' => 'platform', 'ldap' => false];

    public function testExistingCasIdentityKeepsPasswordAndRoles(): void
    {
        $portal = $this->portal(1);
        $user = $this->user()->addAuthSourceByAuthentication(UserAuthSource::CAS, $portal);
        $users = $this->users($user);
        $users->expects(self::never())->method('updateUser');
        self::assertSame($user, $this->resolver($users, $portal)->resolve(self::IDENTITY, $portal, self::CONFIG));
        self::assertSame('existing-hash', $user->getPassword());
        self::assertFalse($user->isAdmin());
    }

    public function testExistingPlatformAccountCannotBeClaimed(): void
    {
        $portal = $this->portal(1);
        $user = $this->user()->addAuthSourceByAuthentication(UserAuthSource::PLATFORM, $portal)->setRoles(['ROLE_ADMIN']);
        $users = $this->users($user);
        $users->expects(self::never())->method('updateUser');
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->resolver($users, $portal)->resolve(self::IDENTITY, $portal, self::CONFIG);
    }

    public function testSourceFromAnotherPortalIsNotSufficient(): void
    {
        $portal = $this->portal(1);
        $user = $this->user()->addAuthSourceByAuthentication(UserAuthSource::CAS, $this->portal(2));
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->resolver($this->users($user), $portal)->resolve(self::IDENTITY, $portal, self::CONFIG);
    }

    public function testInactiveAccountIsRejected(): void
    {
        $portal = $this->portal(1);
        $user = $this->user()->addAuthSourceByAuthentication(UserAuthSource::CAS, $portal)->setActive(0);
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->resolver($this->users($user), $portal)->resolve(self::IDENTITY, $portal, self::CONFIG);
    }

    public function testExpiredAccountIsRejected(): void
    {
        $portal = $this->portal(1);
        $user = $this->user()->addAuthSourceByAuthentication(UserAuthSource::CAS, $portal)->setExpirationDate(new DateTime('-1 day'));
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->resolver($this->users($user), $portal)->resolve(self::IDENTITY, $portal, self::CONFIG);
    }

    public function testLocalMfaIsNotBypassed(): void
    {
        $portal = $this->portal(1);
        $user = $this->user()->addAuthSourceByAuthentication(UserAuthSource::CAS, $portal)->setMfaEnabled(true);
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->resolver($this->users($user), $portal)->resolve(self::IDENTITY, $portal, self::CONFIG);
    }

    public function testCreationCanBeDisabled(): void
    {
        $portal = $this->portal(1);
        $users = $this->users(null);
        $users->expects(self::never())->method('updateUser');
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->resolver($users, $portal)->resolve(self::IDENTITY, $portal, ['creation' => 'false', 'ldap' => false]);
    }

    public function testNewAccountCannotReceiveExternalAdminRole(): void
    {
        $portal = $this->portal(1);
        $users = $this->users(null);
        $root = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($root, 1);
        $users->method('getRootUser')->willReturn($root);
        $users->expects(self::once())->method('updateUser');
        $user = $this->resolver($users, $portal)->resolve(self::IDENTITY, $portal, self::CONFIG);
        self::assertSame('cas_student', $user->getUsername());
        self::assertNotEmpty($user->getPlainPassword());
        self::assertTrue($user->isStudent());
        self::assertFalse($user->isAdmin());
        self::assertNotNull($user->getAuthSourceByAuthentication(UserAuthSource::CAS, $portal));
        self::assertNull($user->getAuthSourceByAuthentication(UserAuthSource::PLATFORM, $portal));
    }

    public function testTermsEnabledWithoutContentDoesNotNeedLegacyDatabase(): void
    {
        $portal = $this->portal(1);
        $user = $this->user()->addAuthSourceByAuthentication(UserAuthSource::CAS, $portal);
        $resolver = $this->termsResolver($this->users($user), $portal, null, null, false);
        self::assertSame($user, $resolver->resolve(self::IDENTITY, $portal, self::CONFIG));
    }

    public function testTermsEnabledWithBlankMainSectionDoesNotBlockLogin(): void
    {
        $portal = $this->portal(1);
        $user = $this->user()->addAuthSourceByAuthentication(UserAuthSource::CAS, $portal);
        $term = (new Legal())->setLanguageId(1)->setVersion(2)->setContent('<p></p>');
        $resolver = $this->termsResolver($this->users($user), $portal, $term, null, false);
        self::assertSame($user, $resolver->resolve(self::IDENTITY, $portal, self::CONFIG));
    }

    public function testTermsAlreadyAcceptedAllowLogin(): void
    {
        $portal = $this->portal(1);
        $user = $this->user()->addAuthSourceByAuthentication(UserAuthSource::CAS, $portal);
        $term = (new Legal())->setLanguageId(1)->setVersion(2)->setContent('<p>Terms</p>');
        $resolver = $this->termsResolver($this->users($user), $portal, $term, '2:1', 2);
        self::assertSame($user, $resolver->resolve(self::IDENTITY, $portal, self::CONFIG));
    }

    /**
     * @dataProvider invalidAcceptances
     */
    public function testPendingOrInvalidTermsPreventLogin(?string $acceptance, false|int $version): void
    {
        $portal = $this->portal(1);
        $user = $this->user()->addAuthSourceByAuthentication(UserAuthSource::CAS, $portal);
        $term = (new Legal())->setLanguageId(1)->setVersion(2)->setContent('<p>Terms</p>');
        $resolver = $this->termsResolver($this->users($user), $portal, $term, $acceptance, $version);
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $resolver->resolve(self::IDENTITY, $portal, self::CONFIG);
    }

    public static function invalidAcceptances(): iterable
    {
        yield 'missing' => [null, 2];

        yield 'outdated' => ['1:1', 2];

        yield 'unknown language' => ['2:999', false];

        yield 'malformed' => ['invalid:1', 2];
    }

    public function testDefaultLanguageTermsPreventLoginWhenCurrentLanguageHasNoTerms(): void
    {
        $portal = $this->portal(1);
        $user = $this->user()->addAuthSourceByAuthentication(UserAuthSource::CAS, $portal);
        $term = (new Legal())->setLanguageId(1)->setVersion(2)->setContent('Default terms');
        $resolver = $this->termsResolver($this->users($user), $portal, $term, null, false, 'es_ES');
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $resolver->resolve(self::IDENTITY, $portal, self::CONFIG);
    }

    public function testNewUserWithPendingTermsIsNotPersisted(): void
    {
        $portal = $this->portal(1);
        $users = $this->users(null);
        $root = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($root, 1);
        $users->method('getRootUser')->willReturn($root);
        $users->expects(self::never())->method('updateUser');
        $term = (new Legal())->setLanguageId(1)->setVersion(2)->setContent('Terms');
        $resolver = $this->termsResolver($users, $portal, $term, null, false);
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $resolver->resolve(self::IDENTITY, $portal, self::CONFIG);
    }

    private function termsResolver(UserRepository $users, AccessUrl $portal, ?Legal $term, ?string $acceptance, false|int $version, string $locale = 'en_US'): CasUserResolver
    {
        $settings = $this->createMock(SettingsManager::class);
        $settings->method('getSetting')->willReturnMap([
            ['registration.allow_terms_conditions', true, 'true'],
            ['workflows.load_term_conditions_section', true, 'login'],
            ['language.platform_language', true, 'en_US'],
        ]);
        $english = (new Language())->setIsocode('en_US');
        (new ReflectionProperty(Language::class, 'id'))->setValue($english, 1);
        $spanish = (new Language())->setIsocode('es_ES');
        (new ReflectionProperty(Language::class, 'id'))->setValue($spanish, 2);
        $languages = $this->createMock(LanguageRepository::class);
        $languages->method('findByIsoCode')->willReturnMap([['en_US', $english], ['es_ES', $spanish]]);
        $legal = $this->createMock(LegalRepository::class);
        $legal->method('getLastConditionByLanguage')->willReturnMap([[1, $term], [2, null]]);
        $legal->method('findTermsByLanguageAndVersion')->willReturn(null === $term ? [] : [$term]);
        $legal->method('getLastVersion')->willReturn($version);
        $extra = $this->createMock(ExtraFieldValuesRepository::class);
        $extra->method('findLegalAcceptByItemId')->willReturn(null === $acceptance ? null : ['value' => $acceptance]);
        $stack = new RequestStack();
        $request = Request::create('/connect/cas/check');
        $request->setLocale($locale);
        $stack->push($request);

        return $this->resolver($users, $portal, $settings, $extra, $legal, $languages, $stack);
    }

    private function user(): User
    {
        $user = (new User())->setUsername('cas_student')->setEmail('student@example.org')->setRoles(['ROLE_STUDENT'])->setPassword('existing-hash');
        (new ReflectionProperty(User::class, 'id'))->setValue($user, 100);

        return $user;
    }

    private function portal(int $id): AccessUrl
    {
        $portal = new AccessUrl();
        (new ReflectionProperty(AccessUrl::class, 'id'))->setValue($portal, $id);

        return $portal;
    }

    private function users(?User $user): UserRepository
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findByUsernameCaseInsensitive')->willReturn($user);
        $repo->method('findByEmailCaseInsensitive')->willReturn(null);

        return $repo;
    }

    private function resolver(
        UserRepository $users,
        AccessUrl $portal,
        ?SettingsManager $settings = null,
        ?ExtraFieldValuesRepository $extra = null,
        ?LegalRepository $legal = null,
        ?LanguageRepository $languages = null,
        ?RequestStack $stack = null,
    ): CasUserResolver {
        $portals = $this->createMock(AccessUrlRepository::class);
        $portals->method('getFirstId')->willReturn(1);
        $portals->method('find')->willReturn($portal);
        $portals->method('isUrlActiveForUser')->willReturn(true);
        $auth = new AuthenticationConfigHelper(new ParameterBag([]), new AccessUrlHelper($portals, new RequestStack()));
        if (null === $settings) {
            $settings = $this->createMock(SettingsManager::class);
            $settings->method('getSetting')->willReturn('false');
        }

        return new CasUserResolver(
            $users,
            $portals,
            new CasProfileResolver($auth),
            $settings,
            $extra ?? $this->createMock(ExtraFieldValuesRepository::class),
            $legal ?? $this->createMock(LegalRepository::class),
            $languages ?? $this->createMock(LanguageRepository::class),
            $stack ?? new RequestStack(),
        );
    }
}
