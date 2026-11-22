<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authenticator\Cas;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Legal;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\LegalRepository;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final readonly class CasUserResolver
{
    public function __construct(
        private UserRepository $users,
        private AccessUrlRepository $portals,
        private CasProfileResolver $profiles,
        private SettingsManager $settings,
        private ExtraFieldValuesRepository $extraFields,
        private LegalRepository $legal,
        private LanguageRepository $languages,
        private RequestStack $requests,
    ) {}

    public function resolve(array $identity, AccessUrl $portal, array $config): User
    {
        $username = $identity['username'];
        $user = $this->users->findByUsernameCaseInsensitive($username);
        if (null !== $user) {
            // CAS identifies an exact principal. A case-folded collision must not
            // link to an existing account, nor may an email be used as identity.
            if ($username !== $user->getUsername() || null === $user->getAuthSourceByAuthentication(UserAuthSource::CAS, $portal)
                || !$this->portals->isUrlActiveForUser($portal, $user)
            ) {
                throw new CustomUserMessageAuthenticationException('User authentication method not allowed');
            }
            $this->checkAccount($user);
            $this->checkTerms($user, $portal);

            if ($config['ldap']) {
                $this->applyProfile($user, $this->profiles->fromLdap($username));
                $this->users->updateUser($user);
            }

            return $user;
        }

        if ('false' === $config['creation'] || \strlen($username) > User::USERNAME_MAX_LENGTH) {
            throw new CustomUserMessageAuthenticationException('User authentication method not allowed');
        }

        $profile = 'extldap' === $config['creation'] || $config['ldap']
            ? $this->profiles->fromLdap($username)
            : $this->profiles->fromCas($identity);

        // New identities are students only. No provider attribute may grant admin,
        // teacher, HR, session admin or any platform authentication source.
        $user = (new User())
            ->setUsername($username)
            ->setRoles(['ROLE_STUDENT'])
            ->setPlainPassword(bin2hex(random_bytes(32)))
            ->setCreatorId($this->users->getRootUser()->getId())
            ->setCurrentUrl($portal)
            ->addAuthSourceByAuthentication(UserAuthSource::CAS, $portal)
        ;
        $this->checkTerms($user, $portal);
        $this->applyProfile($user, $profile);

        try {
            $this->users->updateUser($user);
        } catch (UniqueConstraintViolationException) {
            // Concurrent provisioning or username collisions never reassign an account.
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }

        return $user;
    }

    private function checkAccount(User $user): void
    {
        if (!$user->isActive() || $user->getLocked() || $user->getExpired() || User::ANONYMOUS === $user->getStatus()
            || (null !== $user->getExpirationDate() && $user->getExpirationDate() <= new DateTimeImmutable())
        ) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }
        // Local MFA is not asserted by the CAS protocol. Reject, rather than silently
        // bypass, an account that requires Chamilo's own second factor.
        if ($user->getMfaEnabled()) {
            throw new CustomUserMessageAuthenticationException('Two-factor authentication is required for this account.');
        }
    }

    private function checkTerms(User $user, AccessUrl $portal): void
    {
        $settings = clone $this->settings;
        $settings->setUrl($portal);
        if (!$user->isStudent()
            || !\in_array($settings->getSetting('registration.allow_terms_conditions', true), [true, 'true'], true)
            || 'login' !== $settings->getSetting('workflows.load_term_conditions_section', true)
        ) {
            return;
        }

        // Authentication runs before LegacyListener. Use Doctrine and the locale
        // resolved by LocaleSubscriber, not the legacy Database singleton.
        $locale = $this->requests->getCurrentRequest()?->getLocale() ?? '';
        $language = $this->findLanguage($locale);
        $term = null !== $language?->getId() ? $this->legal->getLastConditionByLanguage($language->getId()) : null;
        if (null === $term) {
            $defaultLocale = (string) $settings->getSetting('language.platform_language', true);
            $defaultLanguage = $this->findLanguage($defaultLocale);
            if (null !== $defaultLanguage?->getId() && $defaultLanguage->getId() !== $language?->getId()) {
                $term = $this->legal->getLastConditionByLanguage($defaultLanguage->getId());
            }

            // Do not treat an unresolved language configuration as consent.
            if (null === $language?->getId() && null === $defaultLanguage?->getId()) {
                throw new CustomUserMessageAuthenticationException('Authentication failed.');
            }
        }

        if (null === $term || !$this->hasRenderableTerms($term)) {
            return;
        }

        if (null !== $user->getId()) {
            $acceptance = $this->extraFields->findLegalAcceptByItemId($user->getId());
            $value = $acceptance['value'] ?? '';
            if (\is_string($value) && preg_match('/\A([1-9][0-9]*):([1-9][0-9]*)\z/D', $value, $parts)) {
                $version = $this->legal->getLastVersion((int) $parts[2]);
                // A deleted/unknown language must not make any acceptance valid.
                if (false !== $version && (int) $version > 0 && (int) $parts[1] >= (int) $version) {
                    return;
                }
            }
        }

        throw new CustomUserMessageAuthenticationException('Authentication failed.');
    }

    private function findLanguage(string $value): ?Language
    {
        if ('' === $value || 'false' === $value) {
            return null;
        }

        $language = $this->languages->findByIsoCode($value) ?? $this->languages->findOneBy(['englishName' => $value]);
        if (null === $language && preg_match('/\A[a-z]{2,3}\z/D', $value)) {
            $language = $this->languages->findFirstAvailableByIsoPrefix($value);
        }

        return $language;
    }

    private function hasRenderableTerms(Legal $term): bool
    {
        $version = $term->getVersion();
        $languageId = $term->getLanguageId();
        if (null === $version || $version <= 0 || $languageId <= 0) {
            return false;
        }

        foreach ($this->legal->findTermsByLanguageAndVersion($languageId, $version) as $section) {
            $content = trim((string) $section->getContent());
            // The current terms page also renders a heading for each non-empty
            // privacy section, even when its HTML has no plain text.
            if ('' !== $content && ('' !== trim(strip_tags($content)) || Legal::isPrivacyType($section->getType()))) {
                return true;
            }
        }

        return false;
    }

    private function applyProfile(User $user, array $profile): void
    {
        $emailOwner = $this->users->findByEmailCaseInsensitive($profile['email']);
        if (null !== $emailOwner && $emailOwner->getId() !== $user->getId()) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }
        $user->setFirstname($profile['firstname']);
        $user->setLastname($profile['lastname']);
        $user->setEmail($profile['email']);
    }
}
