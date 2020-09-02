<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;

class TwigListener
{
    private $twig;
    private $tokenStorage;

    public function __construct(Environment $twig, SerializerInterface $serializer, TokenStorageInterface $tokenStorage)
    {
        $this->twig = $twig;
        $this->tokenStorage = $tokenStorage;
        $this->serializer = $serializer;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        $user = null;
        $data = null;
        $isAuth = false;

        if (null !== $token) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                $userClone = clone $user;
                $userClone->setPassword('');
                $data = $this->serializer->serialize($userClone, JsonEncoder::FORMAT);
                $isAuth = true;
            }
        }

        $this->twig->addGlobal('text_direction', api_get_text_direction());
        $this->twig->addGlobal('from_vue', $request->request->get('from_vue') ? 1 : 0);
        $this->twig->addGlobal('is_authenticated', json_encode($isAuth));
        $this->twig->addGlobal('user_json', $data ?? json_encode($data));
    }
}
