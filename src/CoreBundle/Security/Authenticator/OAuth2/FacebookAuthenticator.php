<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator\OAuth2;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Chamilo\CoreBundle\ServiceHelper\AuthenticationConfigHelper;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\FacebookUser;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class FacebookAuthenticator extends AbstractAuthenticator
{
    protected string $providerName = 'facebook';

    public function __construct(
        ClientRegistry $clientRegistry,
        RouterInterface $router,
        UserRepository $userRepository,
        AuthenticationConfigHelper $authenticationConfigHelper,
        AccessUrlHelper $urlHelper,
        EntityManagerInterface $entityManager,
        protected readonly SlugifyInterface $slugify,
    ) {
        parent::__construct(
            $clientRegistry,
            $router,
            $userRepository,
            $authenticationConfigHelper,
            $urlHelper,
            $entityManager,
        );
    }

    public function supports(Request $request): ?bool
    {
        return 'chamilo.oauth2_facebook_check' === $request->attributes->get('_route');
    }

    protected function userLoader(AccessToken $accessToken): User
    {
        /** @var FacebookUser $resourceOwner */
        $resourceOwner = $this->client->fetchUserFromToken($accessToken);

        $user = $this->userRepository->findOneBy(['email' => $resourceOwner->getEmail()]);

        if (!$user) {
            $user = (new User())
                ->setCreatorId($this->userRepository->getRootUser()->getId())
            ;
        }

        $user
            ->setFirstname($resourceOwner->getFirstName())
            ->setLastname($resourceOwner->getLastName())
            // ->setLocale($resourceOwner->getLocale())
            ->setEmail($resourceOwner->getEmail())
            ->setUsername($this->changeToValidChamiloLogin($resourceOwner->getEmail()))
            ->setPlainPassword('facebook')
            ->setStatus(STUDENT)
            ->setAuthSource('facebook')
            ->setRoleFromStatus(STUDENT)
        ;

        $this->userRepository->updateUser($user);

        $url = $this->urlHelper->getCurrent();
        $url->addUser($user);

        $this->entityManager->flush();

        return $user;
    }

    private function changeToValidChamiloLogin(string $email): string
    {
        return $this->slugify->slugify($email);
    }
}
