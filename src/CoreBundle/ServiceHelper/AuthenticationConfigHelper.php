<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ServiceHelper;

use Chamilo\CoreBundle\Entity\AccessUrl;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class AuthenticationConfigHelper
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private AccessUrlHelper $urlHelper,
    ) {}

    public function getParams(string $providerName, ?AccessUrl $url = null): array
    {
        $urlId = $url ? $url->getId() : $this->urlHelper->getCurrent()->getId();

        $authentication = $this->parameterBag->get('authentication');

        if (isset($authentication[$urlId])) {
            $urlParams = $authentication[$urlId];
        } elseif (isset($authentication['default'])) {
            $urlParams = $authentication['default'];
        } else {
            throw new InvalidArgumentException('Invalid access URL configuration');
        }

        if (!isset($urlParams[$providerName])) {
            throw new InvalidArgumentException('Invalid authentication provider for access URL');
        }

        return $urlParams[$providerName];
    }

    public function isEnabled(string $methodName, ?AccessUrl $url = null): bool
    {
        $configParams = $this->getParams($methodName, $url);

        return $configParams['enabled'] ?? false;
    }
}
