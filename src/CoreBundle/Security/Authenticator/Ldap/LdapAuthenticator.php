<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator\Ldap;

use Chamilo\CoreBundle\Controller\SecurityController;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Security\LdapBadge;
use Symfony\Component\Ldap\Security\LdapUser;
use Symfony\Component\Ldap\Security\LdapUserProvider;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Based on Symfony\Component\Ldap\Security\LdapAuthenticator.
 */
class LdapAuthenticator extends AbstractAuthenticator implements InteractiveAuthenticatorInterface
{
    private LdapBadge $ldapBadge;
    private LdapUserProvider $userProvider;

    private array $dataCorrespondence = [];

    private bool $isEnabled = false;

    private bool $synchUserRoleOnUpdate = true;

    public function __construct(
        protected readonly AuthenticationConfigHelper $authConfigHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly UserRepository $userRepo,
        private readonly EntityManagerInterface $entityManager,
        private readonly SecurityController $securityController,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TranslatorInterface $translator,
        private readonly ExtraFieldRepository $extraFieldRepo,
        private readonly ExtraFieldValuesRepository $extraFieldValuesRepo,
        Ldap $ldap,
    ) {
        $ldapConfig = $this->authConfigHelper->getLdapConfig();

        if (!$ldapConfig['enabled']) {
            return;
        }

        $this->isEnabled = true;

        $dnString = $ldapConfig['dn_string'] ?? '{user_identifier}';
        $searchDn = $ldapConfig['search_dn'] ?? '';
        $searchPassword = $ldapConfig['search_password'] ?? '';
        $queryString = $ldapConfig['query_string'] ?? null;
        $objectClass = $ldapConfig['object_class'] ?? 'inetOrgPerson';
        $uidKey = $ldapConfig['uid_key'] ?? 'uid';

        $this->dataCorrespondence = array_filter($ldapConfig['data_correspondence']) ?: [];
        $this->synchUserRoleOnUpdate = (bool) ($ldapConfig['synch_user_role_on_update'] ?? true);

        if (null !== $ldapConfig['password_attribute']) {
            $dataCorrespondence = array_values($this->dataCorrespondence + [$ldapConfig['password_attribute']]);
        } else {
            $dataCorrespondence = $this->dataCorrespondence;
        }

        // Always use the queryString approach (search for actual DN, then bind) unless the admin
        // has explicitly set query_string in config. Templating the bind DN via dn_string fails on
        // AD because the real DN is CN=...,OU=... which cannot be guessed from a uid/sAMAccountName.
        // CheckLdapCredentialsListener: binds as service account → searches with queryString →
        // gets real DN from entry → binds as user. This requires search_dn + search_password.
        if (null === $queryString) {
            $queryString = '(&(objectClass='.$objectClass.')('.$uidKey.'={user_identifier}))';
            $dnString = $ldapConfig['base_dn'];
        }

        $this->ldapBadge = new LdapBadge(Ldap::class, $dnString, $searchDn, $searchPassword, $queryString);

        // The login lookup only needs to find the user by uid — no extra filter restrictions.
        // The config "filter" (e.g. bitwise userAccountControl OID, memberOf...) applies only
        // to listing and sync (LdapAuthenticatorHelper / LdapSyncUsersCommand).
        // Applying it here too causes ldap_search() to fail with AD's extensible-match OIDs,
        // and is also unnecessary: a disabled AD account fails at the ldap_bind() step anyway.
        $providerFilter = '(&(objectClass='.$objectClass.')({uid_key}={user_identifier}))';

        $this->userProvider = new LdapUserProvider(
            $ldap,
            $ldapConfig['base_dn'],
            $searchDn ?: '',
            $searchPassword ?: null,
            ['ROLE_STUDENT'],
            $ldapConfig['uid_key'] ?? null,
            $providerFilter,
            $ldapConfig['password_attribute'] ?? null,
            [],
        );
    }

    public function supports(Request $request): ?bool
    {
        if (
            !str_contains($request->getRequestFormat() ?? '', 'json')
            && !str_contains($request->getContentTypeFormat() ?? '', 'json')
        ) {
            return false;
        }

        return 'login_ldap_check' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        try {
            if (!$this->isEnabled) {
                throw new BadRequestHttpException('Authentication method not enabled.');
            }

            $data = json_decode($request->getContent());

            if (!$data instanceof stdClass) {
                throw new BadRequestHttpException('Invalid JSON.');
            }

            $credentials = $this->getCredentials($data);
        } catch (BadRequestHttpException $e) {
            $request->setRequestFormat('json');

            throw $e;
        }

        $userBadge = new UserBadge($credentials['username'], $this->userProvider->loadUserByIdentifier(...));

        $passport = new Passport(
            $userBadge,
            new PasswordCredentials($credentials['password']),
            [new RememberMeBadge((array) $data)]
        );
        // $passport->addBadge(new PasswordUpgradeBadge($credentials['password'], $this->userProvider));
        $passport->addBadge($this->ldapBadge);

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->securityController->loginJson(
            $request,
            $this->tokenStorage,
            $this->translator
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $errorMessage = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new JsonResponse(['error' => $errorMessage], Response::HTTP_UNAUTHORIZED);
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        /** @var LdapUser $ldapUser */
        $ldapUser = $passport->getUser();

        $user = $this->createUser($ldapUser);

        return new UsernamePasswordToken(
            $user,
            $firewallName,
            $user->getRoles()
        );
    }

    private function getCredentials(stdClass $data): array
    {
        $credentials = [];

        try {
            $credentials['username'] = $data->username;

            if (!\is_string($credentials['username'])) {
                throw new BadRequestHttpException(\sprintf('The key "%s" must be a string.', 'username'));
            }
        } catch (AccessException $e) {
            throw new BadRequestHttpException(\sprintf('The key "%s" must be provided.', 'username'), $e);
        }

        try {
            $credentials['password'] = $data->password;
            $data->password = null;

            if (!\is_string($credentials['password'])) {
                throw new BadRequestHttpException(\sprintf('The key "%s" must be a string.', 'password'));
            }
        } catch (AccessException $e) {
            throw new BadRequestHttpException(\sprintf('The key "%s" must be provided.', 'password'), $e);
        }

        if ('' === $credentials['username'] || '' === $credentials['password']) {
            trigger_deprecation('symfony/security', '6.2', 'Passing an empty string as username or password parameter is deprecated.');
        }

        return $credentials;
    }

    public function createUser(LdapUser $ldapUser): User
    {
        $currentAccessUrl = $this->accessUrlHelper->getCurrent();

        $user = $this->userRepo->findOneBy(['username' => $ldapUser->getUserIdentifier()]);
        $isNew = null === $user;

        if (!$user) {
            $user = (new User())
                ->setCreatorId($this->userRepo->getRootUser()->getId())
                ->addAuthSourceByAuthentication(UserAuthSource::LDAP, $currentAccessUrl)
            ;
        }

        $ldapEntry = $ldapUser->getEntry();

        $fieldsMap = [
            'firstname' => 'setFirstname',
            'lastname' => 'setLastname',
            'email' => 'setEmail',
            'active' => 'setActive',
            'role' => 'setRoles',
            'locale' => 'setLocale',
            'phone' => 'setPhone',
        ];

        foreach ($fieldsMap as $key => $setter) {
            if (isset($this->dataCorrespondence[$key]) && $fieldKey = $this->dataCorrespondence[$key]) {
                $fieldKey = (string) $fieldKey;
                $value = str_starts_with($fieldKey, '=')
                    ? substr($fieldKey, 1)
                    : ($ldapEntry->getAttribute($fieldKey) ?? [])[0] ?? '';
                if ('active' === $key) {
                    $user->{$setter}((int) $value);
                } elseif ('role' === $key) {
                    if ($isNew || $this->synchUserRoleOnUpdate) {
                        $user->{$setter}([$value]);
                    }
                } else {
                    $user->{$setter}($value);
                }
            } elseif ('firstname' === $key || 'lastname' === $key || 'email' === $key) {
                $user->{$setter}('');
            } elseif ('role' === $key) {
                if ($isNew || $this->synchUserRoleOnUpdate) {
                    $user->setRoles($ldapUser->getRoles());
                }
            }
        }

        $user
            ->setUsername($ldapUser->getUserIdentifier())
            ->setPlainPassword($ldapUser->getPassword())
        ;

        $this->userRepo->updateUser($user);

        $currentAccessUrl->addUser($user);

        $this->entityManager->flush();

        $this->syncExtraFields($user, $ldapEntry);

        return $user;
    }

    private function syncExtraFields(User $user, Entry $ldapEntry): void
    {
        foreach ($this->dataCorrespondence as $key => $ldapAttr) {
            if (!str_starts_with($key, 'extra_') || '' === (string) $ldapAttr) {
                continue;
            }

            $variable = substr($key, \strlen('extra_'));
            $extraField = $this->extraFieldRepo->findByVariable(ExtraField::USER_FIELD_TYPE, $variable);

            if (null === $extraField) {
                continue;
            }

            $ldapAttrStr = (string) $ldapAttr;
            $value = str_starts_with($ldapAttrStr, '=')
                ? substr($ldapAttrStr, 1)
                : ($ldapEntry->getAttribute($ldapAttrStr) ?? [])[0] ?? null;
            $this->extraFieldValuesRepo->updateItemData($extraField, $user, $value);
        }
    }

    public function isInteractive(): bool
    {
        return true;
    }

    public function getUserProvider(): LdapUserProvider
    {
        return $this->userProvider;
    }
}
