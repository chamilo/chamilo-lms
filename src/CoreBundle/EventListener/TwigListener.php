<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
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
        private readonly LanguageRepository $languageRepository,
        private readonly UserHelper $userHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    public function __invoke(ControllerEvent $event): void
    {
        $currentAccessUrl = $this->accessUrlHelper->getCurrent();
        $user = $this->userHelper->getCurrent();

        $data = null;
        $isAuth = false;
        if ($user) {
            $data = $this->serializer->serialize($user, 'jsonld', [
                'groups' => ['user_json:read'],
            ]);
            $isAuth = true;
        }

        $languages = $this->languageRepository->getAllAvailable()->getQuery()->getArrayResult();

        // $this->twig->addGlobal('text_direction', api_get_text_direction());
        $this->twig->addGlobal('is_authenticated', json_encode($isAuth));
        $this->twig->addGlobal('user_json', $data ?? json_encode([]));
        if (!empty($currentAccessUrl)) {
            $this->twig->addGlobal('is_login_url', (int) $currentAccessUrl->isLoginOnly());
            $this->twig->addGlobal('access_url_id', $currentAccessUrl->getId());
        } else {
            // when the URL has not been defined in the access_url table, we need some fallback mechanism
            $this->twig->addGlobal('is_login_url', 0);
            $this->twig->addGlobal('access_url_id', 1);
        }
        $this->twig->addGlobal('languages_json', json_encode($languages));
    }
}
