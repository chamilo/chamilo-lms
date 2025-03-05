<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\OAuth2;

use Chamilo\CoreBundle\ServiceHelper\AuthenticationConfigHelper;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractOAuth2ProviderController extends AbstractController
{
    protected function getStartResponse(
        string $providerName,
        ClientRegistry $clientRegistry,
        AuthenticationConfigHelper $authenticationConfigHelper,
    ): Response {
        if (!$authenticationConfigHelper->isOAuth2ProviderEnabled($providerName)) {
            throw $this->createAccessDeniedException();
        }

        return $clientRegistry->getClient($providerName)->redirect([], []);
    }
}
