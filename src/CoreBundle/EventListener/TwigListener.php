<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Repository\ColorThemeRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;

/**
 * Twig-related event listener. For filters, look into ChamiloExtension.php.
 */
class TwigListener implements EventSubscriberInterface
{
    private SerializerInterface $serializer;
    private Environment $twig;
    private TokenStorageInterface $tokenStorage;
    private SettingsManager $settingsManager;
    private LanguageRepository $languageRepository;

    public function __construct(
        Environment $twig,
        SerializerInterface $serializer,
        TokenStorageInterface $tokenStorage,
        SettingsManager $settingsManager,
        LanguageRepository $languageRepository,
        private readonly ColorThemeRepository $colorThemeRepository,
        private readonly RouterInterface $router,
    ) {
        $this->twig = $twig;
        $this->tokenStorage = $tokenStorage;
        $this->serializer = $serializer;
        $this->settingsManager = $settingsManager;
        $this->languageRepository = $languageRepository;
    }

    public function onControllerEvent(ControllerEvent $event): void
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
        $this->twig->addGlobal('from_vue', $request->request->get('from_vue') ? 1 : 0);
        $this->twig->addGlobal('is_authenticated', json_encode($isAuth));
        $this->twig->addGlobal('user_json', $data ?? json_encode([]));
        $this->twig->addGlobal('access_url_id', $request->getSession()->get('access_url_id'));
        $this->twig->addGlobal('languages_json', json_encode($languages));

        $this->loadColorTheme();
    }

    private function loadColorTheme(): void
    {
        $link = null;

        $colorTheme = $this->colorThemeRepository->getActiveOne();

        if ($colorTheme) {
            $path = $this->router->generate('chamilo_color_theme');

            $link = '<link rel="stylesheet" href="'.$path.'">';
        }

        $this->twig->addGlobal('color_theme_link', $link);
    }

    public static function getSubscribedEvents(): array
    {
        return [ControllerEvent::class => 'onControllerEvent'];
    }
}
