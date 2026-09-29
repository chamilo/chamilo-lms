<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
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
    /**
     * @psalm-suppress ContainerDependency
     */
    public function __construct(
        private readonly Environment $twig,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly RouterInterface $router,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly ParameterBagInterface $parameterBag,
        private readonly SettingsManager $settingsManager,
        private readonly ContainerInterface $container,
    ) {}

    public function __invoke(RequestEvent $event): void
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
            $context = $this->router->getContext();
            $context->setBaseUrl('');
            $this->router->setContext($context);
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
            // API requests must not interpret the parameter. The api firewall shares the main
            // session (`context: main`, and `stateless: true` is commented out), so a plain GET
            // such as /api/forums?isStudentView=true would switch the whole browsing session,
            // with no role check and from any origin. Only /toggle_student_view may do that.
            //
            // Full page loads keep working, which is what the flows that rely on this need:
            // the legacy pages under public/main, the learning path auto launch, the builder
            // preview and the legacy to SPA bridges. None of them is served from /api.
            $isApiRequest = str_starts_with($request->getPathInfo(), '/api/');

            if (!$isApiRequest && $request->query->has('isStudentView')) {
                // Same values IndexController::toggleStudentView() accepts. This is the only
                // place the parameter is interpreted; readers go through StudentViewHelper.
                $isStudentView = strtolower(trim((string) $request->query->get('isStudentView')));

                if (\in_array($isStudentView, ['1', 'true', 'yes', 'on'], true)) {
                    $session->set('studentview', 'studentview');
                } elseif (\in_array($isStudentView, ['0', 'false', 'no', 'off'], true)) {
                    $session->set('studentview', 'teacherview');
                }
            } elseif (!$session->has('studentview')) {
                // Still initialize on API requests: CToolStateProvider treats a missing key as
                // the student view, so leaving it unset would hide course tools.
                $session->set('studentview', 'teacherview');
            }
        }

        // Theme icon is loaded in the TwigListener src/ThemeBundle/EventListener/TwigListener.php
        // $theme = api_get_visual_theme();
        /*$languages = api_get_languages();
         * $languageList = [];
         * foreach ($languages as $isoCode => $language) {
         * $languageList[languageToCountryIsoCode($isoCode)] = $language;
         * }
         * $isoFixed = languageToCountryIsoCode($request->getLocale());
         * if (!isset($languageList[$isoFixed])) {
         * $isoFixed = 'en';
         * }
         * $twig->addGlobal(
         * 'current_locale_info',
         * [
         * 'flag' => $isoFixed,
         * 'text' => $languageList[$isoFixed] ?? 'English',
         * ]
         * );*/
        // $twig->addGlobal('current_locale', $request->getLocale());
        // $twig->addGlobal('available_locales', $languages);
        // $twig->addGlobal('show_toolbar', \Template::isToolBarDisplayedForUser() ? 1 : 0);

        // Extra content
        $extraHeader = '';
        if (!$isAdmin) {
            $extraHeader = trim(api_get_setting('header_extra_content'));
        }
        $twig->addGlobal('header_extra_content', $extraHeader);

        // We set cid_reset = true if we enter inside a main/admin url
        // CidReqListener check this variable and deletes the course session
        if (str_contains((string) ($request->attributes->get('name') ?? $request->query->get('name') ?? $request->request->get('name')), 'admin/')) {
            $session->set('cid_reset', true);
        } else {
            $session->set('cid_reset', false);
        }

        $currentAccessUrl = $this->accessUrlHelper->getCurrent();
        if (null !== $currentAccessUrl) {
            $session->set('access_url_id', $currentAccessUrl->getId());
        }
    }
}
