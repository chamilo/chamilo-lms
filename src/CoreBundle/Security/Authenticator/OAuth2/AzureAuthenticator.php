<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator\OAuth2;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Helpers\AzureAuthenticatorHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\RouterInterface;
use TheNetworg\OAuth2\Client\Provider\Azure;

class AzureAuthenticator extends AbstractAuthenticator
{
    protected string $providerName = 'azure';

    public function __construct(
        ClientRegistry $clientRegistry,
        RouterInterface $router,
        UserRepository $userRepository,
        AuthenticationConfigHelper $authenticationConfigHelper,
        AccessUrlHelper $urlHelper,
        EntityManagerInterface $entityManager,
        private readonly AzureAuthenticatorHelper $azureHelper,
    ) {
        parent::__construct(
            $clientRegistry,
            $router,
            $userRepository,
            $authenticationConfigHelper,
            $urlHelper,
            $entityManager
        );
    }

    public function supports(Request $request): ?bool
    {
        return 'chamilo.oauth2_azure_check' === $request->attributes->get('_route');
    }

    /**
     * @throws NonUniqueResultException
     */
    protected function userLoader(AccessToken $accessToken): User
    {
        /** @var Azure $provider */
        $provider = $this->client->getOAuth2Provider();

        $me = $provider->get(
            sprintf('/v1.0/me?$select=%s', implode(',', AzureAuthenticatorHelper::QUERY_FIELDS)),
            $accessToken
        );

        if (empty($me['mail'])) {
            throw new UnauthorizedHttpException('The mail field is empty in Azure AD and is needed to set the organisation email for this user.');
        }

        if (empty($me['mailNickname'])) {
            throw new UnauthorizedHttpException('The mailNickname field is empty in Azure AD and is needed to set the unique username for this user.');
        }

        if (empty($me['id'])) {
            throw new UnauthorizedHttpException('The id field is empty in Azure AD and is needed to set the unique Azure ID for this user.');
        }

        $userId = $this->azureHelper->registerUser($me);

        return $this->userRepository->find($userId);
    }
}
