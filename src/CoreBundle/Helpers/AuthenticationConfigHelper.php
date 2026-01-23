<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class AuthenticationConfigHelper
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    public function getOAuthProviderConfig(string $providerName, ?AccessUrl $url = null): array
    {
        $providers = $this->getOAuthProvidersForUrl($url);

        if ([] === $providers) {
            return [];
        }

        if (!isset($providers[$providerName])) {
            throw new InvalidArgumentException('Invalid authentication provider for access URL');
        }

        return $providers[$providerName];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getOAuthProvidersForUrl(?AccessUrl $url): array
    {
        $authentication = $this->getAuthSources($url);

        if (isset($authentication['oauth2'])) {
            return $authentication['oauth2'];
        }

        return [];
    }

    private function getAuthSources(?AccessUrl $url): array
    {
        $urlId = $url ?: $this->accessUrlHelper->getCurrent();

        $authentication = $this->parameterBag->has('authentication')
            ? $this->parameterBag->get('authentication')
            : [];

        if ($urlId && isset($authentication[$urlId->getId()])) {
            return $authentication[$urlId->getId()];
        }

        if (isset($authentication['default'])) {
            return $authentication['default'];
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    public function getAuthSourceAuthentications(?AccessUrl $url): array
    {
        $authentications = [UserAuthSource::PLATFORM];

        if ($this->getLdapConfig($url)['enabled']) {
            $authentications[] = UserAuthSource::LDAP;
        }

        $oauthProviders = array_map(
            static fn (string $provider) => 'generic' === $provider ? UserAuthSource::OAUTH2 : $provider,
            array_keys($this->getEnabledOAuthProviders($url))
        );

        return array_merge(
            $authentications,
            $oauthProviders
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getLdapConfig(?AccessUrl $url = null): array
    {
        $authentication = $this->getAuthSources($url);
        $ldapConfig = [];

        if (isset($authentication['ldap'])) {
            $ldapConfig = $authentication['ldap'];
        }

        return [
            'enabled' => $ldapConfig['enabled'] ?? false,
            'title' => $ldapConfig['title'] ?? 'LDAP',
            'force_as_login_method' => $ldapConfig['force_as_login_method'] ?? false,
            'connection_string' => $ldapConfig['connection_string'] ?? 'null://null',
            'protocol_version' => $ldapConfig['protocol_version'] ?? 3,
            'referrals' => $ldapConfig['referrals'] ?? false,
            'dn_string' => $ldapConfig['dn_string'] ?? '{user_identifier}',
            'query_string' => $ldapConfig['query_string'] ?? null,
            'base_dn' => $ldapConfig['base_dn'] ?? null,
            'search_dn' => $ldapConfig['search_dn'] ?? null,
            'search_password' => $ldapConfig['search_password'] ?? null,
            'filter' => $ldapConfig['filter'] ?? null,
            'uid_key' => $ldapConfig['uid_key'] ?? 'uid',
            'password_attribute' => $ldapConfig['password_attribute'] ?? null,
            'data_correspondence' => $this->getLdapDataCorrespondenceConfig(
                $ldapConfig['data_correspondence'] ?? []
            ),
            'object_class' => $ldapConfig['object_class'] ?? 'inetOrgPerson',
        ];
    }

    /**
     * @param array<string, mixed> $dataCorrespondence
     *
     * @return array<string, mixed>
     */
    private function getLdapDataCorrespondenceConfig(array $dataCorrespondence): array
    {
        return [
            'firstname' => $dataCorrespondence['firstname'] ?? null,
            'lastname' => $dataCorrespondence['lastname'] ?? 'sn',
            'email' => $dataCorrespondence['email'] ?? 'mail',
            'locale' => $dataCorrespondence['locale'] ?? null,
            'role' => $dataCorrespondence['role'] ?? null,
            'phone' => $dataCorrespondence['phone'] ?? null,
            'active' => $dataCorrespondence['active'] ?? null,
            'admin' => $dataCorrespondence['admin'] ?? null,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getEnabledOAuthProviders(?AccessUrl $url = null): array
    {
        $urlProviders = $this->getOAuthProvidersForUrl($url);

        return array_filter(
            $urlProviders,
            fn (array $providerParams) => $providerParams['enabled'] ?? false
        );
    }

    public function getOAuthProviderOptions(string $providerType, array $config): array
    {
        $defaults = match ($providerType) {
            'generic' => [
                'clientId' => $config['client_id'],
                'clientSecret' => $config['client_secret'],
                'urlAuthorize' => $config['urlAuthorize'],
                'urlAccessToken' => $config['urlAccessToken'],
                'urlResourceOwnerDetails' => $config['urlResourceOwnerDetails'],
                'accessTokenMethod' => $config['accessTokenMethod'] ?? null,
                'accessTokenResourceOwnerId' => $config['accessTokenResourceOwnerId'] ?? null,
                'scopeSeparator' => $config['scopeSeparator'] ?? null,
                'responseError' => $config['responseError'] ?? null,
                'responseCode' => $config['responseCode'] ?? null,
                'responseResourceOwnerId' => $config['responseResourceOwnerId'] ?? null,
                'scopes' => $config['scopes'] ?? null,
                'pkceMethod' => $config['pkceMethod'] ?? null,
            ],
            'facebook' => [
                'clientId' => $config['client_id'],
                'clientSecret' => $config['client_secret'],
                'graphApiVersion' => $config['graph_api_version'] ?? null,
            ],
            'keycloak' => [
                'clientId' => $config['client_id'],
                'clientSecret' => $config['client_secret'],
                'authServerUrl' => $config['auth_server_url'],
                'realm' => $config['realm'],
                'version' => $config['version'] ?? null,
                'encryptionAlgorithm' => $config['encryption_algorithm'] ?? null,
                'encryptionKeyPath' => $config['encryption_key_path'] ?? null,
                'encryptionKey' => $config['encryption_key'] ?? null,
            ],
            'azure' => [
                'clientId' => $config['client_id'],
                'clientSecret' => $config['client_secret'],
                'clientCertificatePrivateKey' => $config['client_certificate_private_key'] ?? null,
                'clientCertificateThumbprint' => $config['client_certificate_thumbprint'] ?? null,
                'urlLogin' => $config['url_login'] ?? null,
                'pathAuthorize' => $config['path_authorize'] ?? null,
                'pathToken' => $config['path_token'] ?? null,
                'scope' => $config['scope'] ?? null,
                'tenant' => $config['tenant'] ?? null,
                'urlAPI' => $config['url_api'] ?? null,
                'resource' => $config['resource'] ?? null,
                'API_VERSION' => $config['api_version'] ?? null,
                'authWithResource' => $config['auth_with_resource'] ?? null,
                'defaultEndPointVersion' => $config['default_end_point_version'] ?? null,
            ],
        };

        return array_filter($defaults, fn ($value) => null !== $value);
    }

    /**
     * Returns the first authentication method marked as "force_as_login_method" in the authentication configuration.
     * Checks LDAP first, then enabled OAuth providers. Returns the provider name or null if none is forced.
     */
    public function getForcedLoginMethod(?AccessUrl $url = null): ?string
    {
        $ldapConfig = $this->getLdapConfig($url);

        if ($ldapConfig['enabled'] && ($ldapConfig['force_as_login_method'] ?? false)) {
            return 'ldap';
        }

        $oauthProviders = $this->getEnabledOAuthProviders($url);

        foreach ($oauthProviders as $providerName => $providerConfig) {
            if ($providerConfig['force_as_login_method'] ?? false) {
                return $providerName;
            }
        }

        return null;
    }

    public function getScimConfig(?AccessUrl $accessUrl = null): array
    {
        $authentication = $this->getAuthSources($accessUrl);
        $config = [];

        if (isset($authentication['scim'])) {
            $config = $authentication['scim'];
        }

        return [
            'enabled' => $config['enabled'] ?? false,
            'auth_source' => $config['auth_source'] ?? UserAuthSource::PLATFORM,
        ];
    }
}
