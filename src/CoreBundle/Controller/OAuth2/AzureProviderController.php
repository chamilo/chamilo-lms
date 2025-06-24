<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\OAuth2;

use Chamilo\CoreBundle\Utils\AuthenticationConfigUtil;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AzureProviderController extends AbstractOAuth2ProviderController
{
    #[Route('/connect/azure', name: 'chamilo.oauth2_azure_start')]
    public function connect(
        ClientRegistry $clientRegistry,
        AuthenticationConfigUtil $authenticationConfigHelper,
    ): Response {
        return $this->getStartResponse('azure', $clientRegistry, $authenticationConfigHelper);
    }

    #[Route('/connect/azure/check', name: 'chamilo.oauth2_azure_check')]
    public function connectCheck(): void {}
}
