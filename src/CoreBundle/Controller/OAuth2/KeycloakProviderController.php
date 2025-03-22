<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\OAuth2;

use Chamilo\CoreBundle\ServiceHelper\AuthenticationConfigHelper;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class KeycloakProviderController extends AbstractOAuth2ProviderController
{
    #[Route('/connect/keycloak', name: 'chamilo.oauth2_keycloak_start')]
    public function connect(
        ClientRegistry $clientRegistry,
        AuthenticationConfigHelper $authenticationConfigHelper,
    ): Response {
        return $this->getStartResponse('keycloak', $clientRegistry, $authenticationConfigHelper);
    }

    #[Route('/connect/keycloak/check', name: 'chamilo.oauth2_keycloak_check')]
    public function connectCheck(): void {}
}
