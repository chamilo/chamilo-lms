<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\OAuth2;

use Chamilo\CoreBundle\Utils\AuthenticationConfigUtil;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GenericProviderController extends AbstractOAuth2ProviderController
{
    #[Route('/connect/generic', name: 'chamilo.oauth2_generic_start')]
    public function connect(
        ClientRegistry $clientRegistry,
        AuthenticationConfigUtil $authenticationConfigHelper,
    ): Response {
        return $this->getStartResponse('generic', $clientRegistry, $authenticationConfigHelper);
    }

    #[Route('/connect/generic/check', name: 'chamilo.oauth2_generic_check')]
    public function connectCheck(): void {}
}
