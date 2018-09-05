<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\EventListener;

use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class TwigListener.
 *
 * @package Chamilo\ThemeBundle\EventListener
 */
class TwigListener implements EventSubscriberInterface
{
    use ContainerAwareTrait;

    /**
     * TwigListener constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $container = $this->container;

        Container::setContainer($container);
        Container::setLegacyServices($container);

        $theme = api_get_visual_theme();
        $twig = $container->get('twig');
        $twig->addGlobal('favico', \Template::getPortalIcon($theme));

        if (api_get_setting('show_administrator_data') === 'true') {
            $firstName = api_get_setting('administratorName');
            $lastName = api_get_setting('administratorSurname');
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
                $adminName = get_lang('Manager').' : ';
                $adminName .= \Display::encrypted_mailto_link(api_get_setting('emailAdministrator'), $name);
            }
            $twig->addGlobal('administrator_name', $adminName);
        }

        $admin = [
            'email' => api_get_setting('emailAdministrator'),
            'surname' => api_get_setting('administratorSurname'),
            'name' => api_get_setting('administratorName'),
            'telephone' => api_get_setting('administratorTelephone'),
        ];

        $twig->addGlobal('_admin', $admin);

        if (api_get_setting('show_tutor_data') === 'true') {
            // Course manager
            $courseId = api_get_course_int_id();
            $sessionId = api_get_session_id();

            if (!empty($courseId)) {
                $tutorData = '';
                if ($sessionId != 0) {
                    $coachEmail = \CourseManager::get_email_of_tutor_to_session(
                        $sessionId,
                        $courseId
                    );
                    $email_link = [];
                    foreach ($coachEmail as $coach) {
                        $email_link[] = \Display::encrypted_mailto_link($coach['email'], $coach['complete_name']);
                    }
                    if (count($coachEmail) > 1) {
                        $tutorData .= get_lang('Coachs').' : ';
                        $tutorData .= array_to_string($email_link, \CourseManager::USER_SEPARATOR);
                    } elseif (count($coachEmail) == 1) {
                        $tutorData .= get_lang('Coach').' : ';
                        $tutorData .= array_to_string($email_link, \CourseManager::USER_SEPARATOR);
                    } elseif (count($coachEmail) == 0) {
                        $tutorData .= '';
                    }
                }
                $twig->addGlobal('session_teachers', $tutorData);
            }

            if (!empty($courseId)) {
                $teacherData = '';
                $mail = \CourseManager::get_emails_of_tutors_to_course($courseId);
                if (!empty($mail)) {
                    $teachersParsed = [];
                    foreach ($mail as $value) {
                        foreach ($value as $email => $name) {
                            $teachersParsed[] = \Display::encrypted_mailto_link($email, $name);
                        }
                    }
                    $label = get_lang('Teacher');
                    if (count($mail) > 1) {
                        $label = get_lang('Teachers');
                    }
                    $teacherData .= $label.' : '.array_to_string($teachersParsed, \CourseManager::USER_SEPARATOR);
                }
                $twig->addGlobal('teachers', $teacherData);
            }
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 5]],
        ];
    }
}
