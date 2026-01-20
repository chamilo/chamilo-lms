<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator\Ldap;

use Chamilo\CoreBundle\Controller\SecurityController;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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

    public function __construct(
        protected readonly AuthenticationConfigHelper $authConfigHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly UserRepository $userRepo,
        private readonly EntityManagerInterface $entityManager,
        private readonly SecurityController $securityController,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TranslatorInterface $translator,
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

        $this->dataCorrespondence = array_filter($ldapConfig['data_correspondence']) ?: [];

        if (null !== $ldapConfig['password_attribute']) {
            $dataCorrespondence = array_values($this->dataCorrespondence + [$ldapConfig['password_attribute']]);
        } else {
            $dataCorrespondence = $this->dataCorrespondence;
        }

        $this->ldapBadge = new LdapBadge(Ldap::class, $dnString, $searchDn, $searchPassword, $queryString);

        $this->userProvider = new LdapUserProvider(
            $ldap,
            $ldapConfig['base_dn'],
            $searchDn ?: '',
            $searchPassword ?: null,
            ['ROLE_STUDENT'],
            $ldapConfig['uid_key'] ?? null,
            $ldapConfig['filter'] ?? null,
            $ldapConfig['password_attribute'] ?? null,
            $dataCorrespondence,
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

        if (!$user) {
            $user = (new User())
                ->setCreatorId($this->userRepo->getRootUser()->getId())
                ->addAuthSourceByAuthentication(UserAuthSource::LDAP, $currentAccessUrl)
            ;
        }

        $ldapFields = $ldapUser->getExtraFields();

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
                $value = $ldapFields[$fieldKey][0] ?? '';
                if ('active' === $key) {
                    $user->{$setter}((int) $value);
                } elseif ('role' === $key) {
                    $user->{$setter}([$value]);
                } else {
                    $user->{$setter}($value);
                }
            } elseif ('firstname' === $key || 'lastname' === $key || 'email' === $key) {
                $user->{$setter}('');
            } elseif ('role' === $key) {
                $user->setRoles($ldapUser->getRoles());
            }
        }

        $user
            ->setUsername($ldapUser->getUserIdentifier())
            ->setPlainPassword($ldapUser->getPassword())
        ;

        $this->userRepo->updateUser($user);

        $currentAccessUrl->addUser($user);

        $this->entityManager->flush();

        return $user;
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
