<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Decorator;

use Chamilo\CoreBundle\ServiceHelper\AuthenticationConfigHelper;
use InvalidArgumentException;
use KnpU\OAuth2ClientBundle\DependencyInjection\ProviderFactory;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\GenericProvider;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use TheNetworg\OAuth2\Client\Provider\Azure;

#[AsDecorator(decorates: 'knpu.oauth2.provider_factory')]
readonly class OAuth2ProviderFactoryDecorator
{
    public function __construct(
        #[AutowireDecorated]
        private ProviderFactory $inner,
        private AuthenticationConfigHelper $authenticationConfigHelper,
    ) {}

    public function createProvider(
        $class,
        array $options,
        ?string $redirectUri = null,
        array $redirectParams = [],
        array $collaborators = []
    ): AbstractProvider {
        $customConfig = match ($class) {
            GenericProvider::class => $this->authenticationConfigHelper->getProviderConfig('generic'),
            Facebook::class => $this->authenticationConfigHelper->getProviderConfig('facebook'),
            Keycloak::class => $this->authenticationConfigHelper->getProviderConfig('keycloak'),
            Azure::class => $this->authenticationConfigHelper->getProviderConfig('azure'),
            default => throw new InvalidArgumentException("Unsupported provider class: $class"),
        };

        $redirectParams = $customConfig['redirect_params'] ?? [];

        $customOptions = match ($class) {
            GenericProvider::class => $this->authenticationConfigHelper->getProviderOptions(
                'generic',
                [
                    'client_id' => $customConfig['client_id'],
                    'client_secret' => $customConfig['client_secret'],
                    ...$customConfig['provider_options'],
                ],
            ),
            Facebook::class => $this->authenticationConfigHelper->getProviderOptions('facebook', $customConfig),
            Keycloak::class => $this->authenticationConfigHelper->getProviderOptions('keycloak', $customConfig),
            Azure::class => $this->authenticationConfigHelper->getProviderOptions('azure', $customConfig),
            default => throw new \InvalidArgumentException("Unsupported provider class: $class"),
        };

        $options = $customOptions + $options;

        return $this->inner->createProvider($class, $options, $redirectUri, $redirectParams, $collaborators);
    }
}
