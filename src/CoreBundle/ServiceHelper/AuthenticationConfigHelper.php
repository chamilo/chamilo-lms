<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ServiceHelper;

use Chamilo\CoreBundle\Entity\AccessUrl;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function Symfony\Component\String\u;

readonly class AuthenticationConfigHelper
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private AccessUrlHelper $urlHelper,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function getProviderConfig(string $providerName, ?AccessUrl $url = null): array
    {
        $providers = $this->getProvidersForUrl($url);

        if (!isset($providers[$providerName])) {
            throw new InvalidArgumentException('Invalid authentication provider for access URL');
        }

        return $providers[$providerName];
    }

    public function isEnabled(string $methodName, ?AccessUrl $url = null): bool
    {
        $configParams = $this->getProviderConfig($methodName, $url);

        return $configParams['enabled'] ?? false;
    }

    public function getEnabledProviders(?AccessUrl $url = null): array
    {
        $urlProviders = $this->getProvidersForUrl($url);

        $enabledProviders = [];

        foreach ($urlProviders as $providerName => $providerParams) {
            if ($providerParams['enabled'] ?? false) {
                $enabledProviders[] = [
                    'name' => $providerName,
                    'title' => $providerParams['title'] ?? u($providerName)->title(),
                    'url' => $this->urlGenerator->generate(\sprintf('chamilo.oauth2_%s_start', $providerName)),
                ];
            }
        }

        return $enabledProviders;
    }

    private function getProvidersForUrl(?AccessUrl $url): array
    {
        $urlId = $url ? $url->getId() : $this->urlHelper->getCurrent()->getId();

        $authentication = $this->parameterBag->get('authentication');

        if (isset($authentication[$urlId])) {
            return $authentication[$urlId];
        }

        if (isset($authentication['default'])) {
            return $authentication['default'];
        }

        throw new InvalidArgumentException('Invalid access URL configuration');
    }

    public function getProviderOptions(string $providerType, array $config): array
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
}
