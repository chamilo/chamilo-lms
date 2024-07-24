<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Repository\LanguageRepository;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;

/**
 * Twig-related event listener. For filters, look into ChamiloExtension.php.
 */
class TwigListener
{
    public function __construct(
        private readonly Environment $twig,
        private readonly SerializerInterface $serializer,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly LanguageRepository $languageRepository,
    ) {}

    public function __invoke(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        $data = null;
        $isAuth = false;
        if (null !== $token) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                $data = $this->serializer->serialize($user, 'jsonld', [
                    'groups' => ['user_json:read'],
                ]);
                $isAuth = true;
            }
        }

        $languages = $this->languageRepository->getAllAvailable()->getQuery()->getArrayResult();

        // $this->twig->addGlobal('text_direction', api_get_text_direction());
        $this->twig->addGlobal('is_authenticated', json_encode($isAuth));
        $this->twig->addGlobal('user_json', $data ?? json_encode([]));
        $this->twig->addGlobal('access_url_id', $request->getSession()->get('access_url_id'));
        $this->twig->addGlobal('languages_json', json_encode($languages));
    }
}
