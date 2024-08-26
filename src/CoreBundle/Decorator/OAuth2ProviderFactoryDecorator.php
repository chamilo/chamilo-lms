<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Decorator;

use Chamilo\CoreBundle\ServiceHelper\AuthenticationConfigHelper;
use KnpU\OAuth2ClientBundle\DependencyInjection\KnpUOAuth2ClientExtension;
use KnpU\OAuth2ClientBundle\DependencyInjection\ProviderFactory;
use KnpU\OAuth2ClientBundle\KnpUOAuth2ClientBundle;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\GenericProvider;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

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
        $options = match ($class) {
            GenericProvider::class => $this->getProviderOptions('generic'),
            Facebook::class => $this->getProviderOptions('facebook'),
        };

        return $this->inner->createProvider($class, $options, $redirectUri, $redirectParams, $collaborators);
    }

    private function getProviderOptions(string $providerName): array
    {
        /** @var KnpUOAuth2ClientExtension $extension */
        $extension = (new KnpUOAuth2ClientBundle())->getContainerExtension();

        $configParams = $this->authenticationConfigHelper->getParams($providerName);

        return $extension->getConfigurator($providerName)->getProviderOptions($configParams);
    }
}
