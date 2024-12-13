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

    public function getParams(string $providerName, ?AccessUrl $url = null): array
    {
        $providers = $this->getProvidersForUrl($url);

        if (!isset($providers[$providerName])) {
            throw new InvalidArgumentException('Invalid authentication provider for access URL');
        }

        return $providers[$providerName];
    }

    public function isEnabled(string $methodName, ?AccessUrl $url = null): bool
    {
        $configParams = $this->getParams($methodName, $url);

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
                    'url' => $this->urlGenerator->generate(sprintf("chamilo.oauth2_%s_start", $providerName)),
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
}
