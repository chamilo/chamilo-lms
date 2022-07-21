<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use TicketManager;
use Twig\Environment;

class TwigListener
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
        LanguageRepository $languageRepository
    ) {
        $this->twig = $twig;
        $this->tokenStorage = $tokenStorage;
        $this->serializer = $serializer;
        $this->settingsManager = $settingsManager;
        $this->languageRepository = $languageRepository;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();
        $userIsAllowedInProject = false;

        $data = null;
        $isAuth = false;
        if (null !== $token) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                /** @var User $userClone */
                $userClone = clone $user;
                $data = $this->serializer->serialize($userClone, 'jsonld', [
                    'groups' => ['user_json:read'],
                ]);
                $isAuth = true;
                $userIsAllowedInProject = TicketManager::userIsAllowInProject($userClone, 1);
            }
        }

        $settings = [
            'platform.site_name',
            'platform.timezone',
            'platform.theme',
            'platform.administrator_name',
            'platform.administrator_surname',

            'editor.enabled_mathjax',
            'editor.translate_html',
            'platform.load_term_conditions_section',
            'registration.allow_terms_conditions',
            'agenda.personal_calendar_show_sessions_occupation',

            'social.social_enable_messages_feedback',
            'social.disable_dislike_option',
        ];

        // @todo get variables in 1 query.
        $config = [];
        foreach ($settings as $variable) {
            $value = $this->settingsManager->getSetting($variable);
            $config[$variable] = $value;
        }

        if (true === api_get_configuration_value('show_pending_survey_in_menu')) {
            $config['display.show_pending_survey_in_menu'] = 'true';
        } else {
            $config['display.show_pending_survey_in_menu'] = 'false';
        }

        if ($userIsAllowedInProject && 'true' === $this->settingsManager->getSetting('display.show_link_ticket_notification')) {
            $config['display.show_link_ticket_notification'] = 'true';
        } else {
            $config['display.show_link_ticket_notification'] = 'false';
        }

        $languages = $this->languageRepository->getAllAvailable()->getQuery()->getArrayResult();

        //$this->twig->addGlobal('text_direction', api_get_text_direction());
        $this->twig->addGlobal('from_vue', $request->request->get('from_vue') ? 1 : 0);
        $this->twig->addGlobal('is_authenticated', json_encode($isAuth));
        $this->twig->addGlobal('user_json', $data ?? json_encode([]));
        $this->twig->addGlobal('access_url_id', $request->getSession()->get('access_url_id'));
        $this->twig->addGlobal('config_json', json_encode($config));
        $this->twig->addGlobal('languages_json', json_encode($languages));
    }
}
