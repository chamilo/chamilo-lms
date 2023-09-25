<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Exception;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;

/**
 * Works as old global.inc.php
 * Setting old php requirements so pages inside main/* could work correctly.
 */
class LegacyListener
{
    use ContainerAwareTrait;

    public function __construct(
        private readonly Environment $twig,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly RouterInterface $router,
        private readonly ParameterBagInterface $parameterBag,
        private readonly SettingsManager $settingsManager
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();
        $baseUrl = $request->getBaseUrl();

        $container = $this->container;

        // Fixes the router when loading in legacy mode (public/main)
        if (!empty($baseUrl)) {
            // We are inside main/
            /** @var RouterInterface $router */
            $router = $container->get('router');
            $context = $router->getContext();
            $context->setBaseUrl('');
            $router->setContext($context);
        }

        // Setting container
        Container::setRequest($request);
        Container::setContainer($container);
        Container::$twig = $this->twig;
        Container::setLegacyServices($container);

        // Legacy way of detect current access_url
        $installed = $container->getParameter('installed');

        if (empty($installed)) {
            throw new Exception('Chamilo is not installed');
        }

        $twig = $this->twig;
        $token = $this->tokenStorage->getToken();
        $userObject = null;
        if (null !== $token) {
            /** @var User $userObject */
            $userObject = $token->getUser();
        }

        $userInfo = [];
        $isAdmin = false;
        $allowedCreateCourse = false;
        if ($userObject instanceof UserInterface) {
            $userInfo = api_get_user_info_from_entity($userObject);
            $isAdmin = $userObject->isAdmin();
            $allowedCreateCourse = $userObject->isTeacher();
        }
        // @todo remove _user/is_platformAdmin/is_allowedCreateCourse
        $session->set('_user', $userInfo);
        $session->set('is_platformAdmin', $isAdmin);
        $session->set('is_allowedCreateCourse', $allowedCreateCourse);

        if ('true' === $this->settingsManager->getSetting('course.student_view_enabled')) {
            if ($request->query->has('isStudentView')) {
                $isStudentView = $request->query->get('isStudentView');

                if ('true' === $isStudentView) {
                    $session->set('studentview', 'studentview');
                } elseif ('false' === $isStudentView) {
                    $session->set('studentview', 'teacherview');
                }
            } elseif (!$session->has('studentview')) {
                $session->set('studentview', 'teacherview');
            }
        }

        // Theme icon is loaded in the TwigListener src/ThemeBundle/EventListener/TwigListener.php
        //$theme = api_get_visual_theme();
        /*$languages = api_get_languages();
        $languageList = [];
        foreach ($languages as $isoCode => $language) {
            $languageList[languageToCountryIsoCode($isoCode)] = $language;
        }

        $isoFixed = languageToCountryIsoCode($request->getLocale());

        if (!isset($languageList[$isoFixed])) {
            $isoFixed = 'en';
        }

        $twig->addGlobal(
            'current_locale_info',
            [
                'flag' => $isoFixed,
                'text' => $languageList[$isoFixed] ?? 'English',
            ]
        );*/
        //$twig->addGlobal('current_locale', $request->getLocale());
        //$twig->addGlobal('available_locales', $languages);
        //$twig->addGlobal('show_toolbar', \Template::isToolBarDisplayedForUser() ? 1 : 0);

        // Extra content
        $extraHeader = '';
        if (!$isAdmin) {
            $extraHeader = trim(api_get_setting('header_extra_content'));
        }
        $twig->addGlobal('header_extra_content', $extraHeader);

        // We set cid_reset = true if we enter inside a main/admin url
        // CourseListener check this variable and deletes the course session
        if (str_contains((string) $request->get('name'), 'admin/')) {
            $session->set('cid_reset', true);
        } else {
            $session->set('cid_reset', false);
        }

        $urlId = $this->accessUrlRepository->getFirstId();

        if (1 === (int) $this->parameterBag->get('multiple_access_url')) {
            $url = $this->router->generate('index', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $accessUrl = $this->accessUrlRepository->findOneBy(['url' => $url]);
            if (null !== $accessUrl) {
                $urlId = $accessUrl->getId();
            }
        }

        $session->set('access_url_id', $urlId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
    }

    public function onKernelController(ControllerEvent $event): void
    {
    }
}
