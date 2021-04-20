<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;

class TwigListener
{
    private SerializerInterface $serializer;
    private Environment $twig;
    private TokenStorageInterface $tokenStorage;
    private IllustrationRepository $illustrationRepository;

    public function __construct(
        Environment $twig,
        SerializerInterface $serializer,
        TokenStorageInterface $tokenStorage,
        IllustrationRepository $illustrationRepository
    ) {
        $this->twig = $twig;
        $this->tokenStorage = $tokenStorage;
        $this->serializer = $serializer;
        $this->illustrationRepository = $illustrationRepository;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        $data = null;
        $avatar = null;
        $isAuth = false;
        if (null !== $token) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                /** @var User $userClone */
                $userClone = clone $user;
                $data = $this->serializer->serialize($userClone, 'jsonld', [
                    'groups' => ['user_json:read'],
                ]);
                $avatar = $this->illustrationRepository->getIllustrationUrl($userClone);
                $isAuth = true;
            }
        }

        //$this->twig->addGlobal('text_direction', api_get_text_direction());
        $this->twig->addGlobal('from_vue', $request->request->get('from_vue') ? 1 : 0);
        $this->twig->addGlobal('is_authenticated', json_encode($isAuth));
        $this->twig->addGlobal('user_json', $data ?? json_encode([]));
        $this->twig->addGlobal('user_avatar', $avatar);
    }
}
