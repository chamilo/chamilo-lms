<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\EventListener;

use CourseManager;
use SessionManager;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class TwigListener.
 */
class TwigListener implements EventSubscriberInterface
{
    use ContainerAwareTrait;

    /**
     * TwigListener constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return false;
        }

        $container = $this->container;
        $twig = $container->get('twig');

        if (empty($twig)) {
            return false;
        }

        $translator = $this->container->get('translator');

        $settingsManager = $container->get('chamilo.settings.manager');
        $theme = api_get_visual_theme();

        $twig->addGlobal('favico', \Template::getPortalIcon($theme));

        if ('true' === $settingsManager->getSetting('display.show_administrator_data')) {
            // Admin data
            $firstName = $settingsManager->getSetting('admin.administrator_name');
            $lastName = $settingsManager->getSetting('admin.administrator_surname');
            $email = $settingsManager->getSetting('admin.administrator_email');
            $phone = $settingsManager->getSetting('admin.administrator_phone');

            if (!empty($firstName) && !empty($lastName)) {
                $name = api_get_person_name($firstName, $lastName);
            } else {
                $name = $lastName;
                if (empty($lastName)) {
                    $name = $firstName;
                }
            }

            $adminName = '';
            // Administrator name
            if (!empty($name)) {
                $adminName = $translator->trans('Administrator').' : ';
                $adminName .= \Display::encrypted_mailto_link($email, $name);
            }
            $twig->addGlobal('administrator_name', $adminName);

            $admin = [
                'email' => $email,
                'surname' => $lastName,
                'name' => $firstName,
                'telephone' => $phone,
            ];

            $twig->addGlobal('_admin', $admin);
        }

        $extraFooter = trim($settingsManager->getSetting('tracking.footer_extra_content'));
        $twig->addGlobal('footer_extra_content', $extraFooter);

        $extraHeader = trim($settingsManager->getSetting('tracking.header_extra_content'));
        $twig->addGlobal('header_extra_content', $extraHeader);

        if ('true' === $settingsManager->getSetting('display.show_tutor_data')) {
            // Course manager
            // @todo load data using ajax
            /* $courseId = api_get_course_int_id();
            $sessionId = api_get_session_id();

            if (!empty($courseId)) {
                $tutorData = '';
                if (0 !== $sessionId) {
                    $users = SessionManager::getCoachesByCourseSession($sessionId, $courseId);
                    $links = [];
                    if (!empty($users)) {
                        $coaches = [];
                        foreach ($users as $userId) {
                            $coaches[] = api_get_user_info($userId);
                        }
                        $links = array_column($coaches, 'complete_name_with_message_link');
                    }
                    $count = count($links);
                    if ($count > 1) {
                        $tutorData .= get_lang('Coachs').' : ';
                        $tutorData .= array_to_string($links, CourseManager::USER_SEPARATOR);
                    } elseif (1 === $count) {
                        $tutorData .= get_lang('Coach').' : ';
                        $tutorData .= array_to_string($links, CourseManager::USER_SEPARATOR);
                    } elseif (0 === $count) {
                        $tutorData .= '';
                    }
                }
                $twig->addGlobal('session_teachers', $tutorData);
            }

            if (!empty($courseId)) {
                $teacherData = '';
                $teachers = CourseManager::getTeachersFromCourse($courseId);
                if (!empty($teachers)) {
                    $teachersParsed = [];
                    foreach ($teachers as $teacher) {
                        $userId = $teacher['id'];
                        $teachersParsed[] = api_get_user_info($userId);
                    }
                    $links = array_column($teachersParsed, 'complete_name_with_message_link');
                    $label = get_lang('Trainer');
                    if (count($links) > 1) {
                        $label = get_lang('Trainers');
                    }
                    $teacherData .= $label.' : '.array_to_string($links, CourseManager::USER_SEPARATOR);
                }
                $twig->addGlobal('teachers', $teacherData);
            }*/
        }

        $appPlugin = new \AppPlugin();
        $installedPlugins = $appPlugin->getInstalledPluginListName();
        if (!empty($installedPlugins)) {
            // Plugins - Region list
            $pluginConfiguration = api_get_settings('Plugins', 'list', 1);
            $pluginRegionList = [];
            foreach ($pluginConfiguration as $plugin) {
                if ('region' === $plugin['type']) {
                    $pluginRegionList[$plugin['variable']][] = $plugin['subkey'];
                }
            }

            $pluginRegions = $appPlugin->getPluginRegions();
            $courseInfo = api_get_course_info();
            $regionListContent = [];

            foreach ($installedPlugins as $pluginName) {
                foreach ($pluginRegions as $region) {
                    if (!isset($pluginRegionList[$region])) {
                        continue;
                    }

                    if ('course_tool_plugin' === $region) {
                        $courseRegions = $appPlugin->getPluginRegions();
                        $pluginInfo = $appPlugin->getPluginInfo($pluginName, true);

                        if (empty($courseInfo)) {
                            continue;
                        }
                        foreach ($courseRegions as $subRegion) {
                            if (isset($pluginInfo['obj']) && $pluginInfo['obj'] instanceof \Plugin) {
                                /** @var \Plugin $plugin */
                                $plugin = $pluginInfo['obj'];
                                $regionListContent[$subRegion][] = $plugin->renderRegion($subRegion);
                            }
                        }
                    } else {
                        if (in_array($pluginName, $pluginRegionList[$region])) {
                            $regionListContent[$region][] = $appPlugin->loadRegion(
                                $pluginName,
                                $region,
                                $twig,
                                true //$this->force_plugin_load
                            );
                        }
                    }
                }
            }

            foreach ($regionListContent as $region => $contentList) {
                $contentToString = '';
                foreach ($contentList as $content) {
                    $contentToString .= $content;
                }
                $twig->addGlobal("plugin_$region", $contentToString);
            }
        }

        /*$userInfo = api_get_user_info();
        $rightFloatMenu = '';
        $iconBug = \Display::return_icon(
            'bug.png',
            get_lang('Report a bug'),
            [],
            ICON_SIZE_LARGE
        );

        $allow = ANONYMOUS !== $userInfo['status'];
        if ($allow && 'true' === api_get_setting('show_link_bug_notification')) {
            $rightFloatMenu = '<div class="report">
                <a href="https://github.com/chamilo/chamilo-lms/wiki/How-to-report-issues" target="_blank">
                    '.$iconBug.'
                </a>
                </div>';
        }

        if ($allow && 'true' === api_get_setting('show_link_ticket_notification')) {
            // by default is project_id = 1
            $defaultProjectId = 1;
            $allow = \TicketManager::userIsAllowInProject($userInfo, $defaultProjectId);
            if ($allow) {
                $iconTicket = \Display::return_icon(
                    'help.png',
                    get_lang('Ticket'),
                    [],
                    ICON_SIZE_LARGE
                );
                $courseInfo = api_get_course_info();
                $courseParams = '';
                if (!empty($courseInfo)) {
                    $courseParams = api_get_cidreq();
                }
                $url = api_get_path(WEB_CODE_PATH).
                    'ticket/tickets.php?project_id='.$defaultProjectId.'&'.$courseParams;
                $rightFloatMenu .= '<div class="help">
                        <a href="'.$url.'" target="_blank">
                            '.$iconTicket.'
                        </a>
                    </div>';
            }
        }

        $twig->addGlobal('bug_notification', $rightFloatMenu);*/

        return true;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 5]],
        ];
    }
}
