<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator\Ldap;

use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Security\LdapBadge;
use Symfony\Component\Ldap\Security\LdapUserProvider;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

use function is_string;
use function sprintf;

final class LdapAuthenticator extends AbstractAuthenticator
{

    private Ldap $ldap;
    private LdapUserProvider $userProvider;
    private string $baseDn = '';
    private ?string $searchDn = '';
    private ?string $searchPassword = '';
    private ?string $queryString = '';

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuthenticationConfigHelper $authenticationConfigHelper,
        AccessUrlHelper $accessUrlHelper,
    ) {
        $providerParams = $this->authenticationConfigHelper->getLdapConfig($accessUrlHelper->getCurrent());

        $this->baseDn = $providerParams['base_dn'];
        $this->searchDn = $providerParams['search_dn'];
        $this->searchPassword = $providerParams['search_password'];
        $this->queryString = $providerParams['query_string'];

        $adapter = new Adapter(
            [
                'connection_string' => $providerParams['connection_string'],
                'options'           => [
                    'protocol_version' => $providerParams['protocol_version'],
                    'referrals'        => $providerParams['referrals'],
                ],
            ]
        );

        $this->ldap = new Ldap($adapter);

        $this->userProvider = new LdapUserProvider(
            $this->ldap,
            $this->baseDn,
            $this->searchDn,
            $this->searchPassword,
            $providerParams['default_roles'],
            $providerParams['uid_key'],
            $providerParams['filter'],
            $providerParams['password_attribute'],
            $providerParams['extra_fields']
        );
    }

    public function supports(Request $request): ?bool
    {
        return 'login_ldap_check' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $data = json_decode($request->getContent());
            if (!$data instanceof stdClass) {
                throw new BadRequestHttpException('Invalid JSON.');
            }

            $credentials = $this->getCredentials($data);
        } catch (BadRequestHttpException $e) {
            $request->setRequestFormat('json');

            throw $e;
        }

        $userBadge = new UserBadge(
            $credentials['username'],
            $this->userProvider->loadUserByIdentifier(...)
        );
        $passport = new Passport(
            $userBadge,
            new PasswordCredentials($credentials['password']),
            [new RememberMeBadge((array) $data)]
        );

        $passport->addBadge(
            new LdapBadge(
                Ldap::class,
                $this->baseDn,
                $this->searchDn,
                $this->searchPassword,
                $this->queryString
            )
        );

        return $passport;
    }

    private function getCredentials(stdClass $data): array
    {
        $credentials = [];
        try {
            $credentials['username'] = $data->username;

            if (!is_string($credentials['username'])) {
                throw new BadRequestHttpException(sprintf('The key "%s" must be a string.', 'username_path'));
            }
        } catch (AccessException $e) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be provided.', 'username_path'), $e);
        }

        try {
            $credentials['password'] = $data->password;
            $data->password_path = null;

            if (!is_string($credentials['password'])) {
                throw new BadRequestHttpException(sprintf('The key "%s" must be a string.', 'password_path'));
            }
        } catch (AccessException $e) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be provided.', 'password_path'), $e);
        }

        if ('' === $credentials['username'] || '' === $credentials['password']) {
            trigger_deprecation('symfony/security', '6.2',
                'Passing an empty string as username or password parameter is deprecated.');
        }

        return $credentials;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessage(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}