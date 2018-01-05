<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class LeftMenuBuilder
 * @package Chamilo\CoreBundle\Menu
 */
class LeftMenuBuilder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Course menu
     *
     * @param FactoryInterface $factory
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function courseMenu(FactoryInterface $factory, array $options)
    {
        $checker = $this->container->get('security.authorization_checker');
        $menu = $factory->createItem('root');
        $translator = $this->container->get('translator');
        $settingsManager = $this->container->get('chamilo.settings.manager');

        if ($checker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->setChildrenAttribute('class', 'nav nav-pills nav-stacked');
            $menu->addChild(
                $translator->trans('MyCourses'),
                [
                    'route' => 'userportal',
                    'routeParameters' => ['type' => 'courses'],
                ]
            );

            if (api_is_allowed_to_create_course()) {
                $lang = $translator->trans('CreateCourse');
                if ($settingsManager->getSetting('course.course_validation') == 'true') {
                    $lang = $translator->trans('CreateCourseRequest');
                }
                $menu->addChild(
                    $lang,
                    ['route' => 'add_course']
                );
            }

            $link = $this->container->get('router')->generate('web.main');

            $menu->addChild(
                $translator->trans('ManageCourses'),
                [
                    'uri' => $link.'auth/courses.php?action=sortmycourses',
                ]
            );

            $browse = $settingsManager->getSetting('display.allow_students_to_browse_courses');

            if ($browse == 'true') {
                if ($checker->isGranted('ROLE_STUDENT') && !api_is_drh(
                    ) && !api_is_session_admin()
                ) {
                    $menu->addChild(
                        $translator->trans('CourseCatalog'),
                        [
                            'uri' => $link.'auth/courses.php',
                        ]
                    );
                } else {
                    $menu->addChild(
                        $translator->trans('Dashboard'),
                        [
                            'uri' => $link.'dashboard/index.php',
                        ]
                    );
                }
            }

            /** @var \Knp\Menu\MenuItem $menu */
            $menu->addChild(
                $translator->trans('History'),
                [
                    'route' => 'userportal',
                    'routeParameters' => [
                        'type' => 'sessions',
                        'filter' => 'history',
                    ],
                ]
            );

        }

        return $menu;
    }

    /**
     * Course menu
     *
     * @param FactoryInterface $factory
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function sessionMenu(FactoryInterface $factory, array $options)
    {
        $checker = $this->container->get('security.authorization_checker');
        $menu = $factory->createItem('root');
        $translator = $this->container->get('translator');
        $settingsManager = $this->container->get('chamilo.settings.manager');

        if ($checker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->setChildrenAttribute('class', 'nav nav-pills nav-stacked');

            $menu->addChild(
                $translator->trans('MySessions'),
                [
                    'route' => 'userportal',
                    'routeParameters' => ['type' => 'sessions'],
                ]
            );

            if ($checker->isGranted('ROLE_ADMIN')) {
                $menu->addChild(
                    $translator->trans('AddSession'),
                    [
                        'route' => 'main',
                        'routeParameters' => ['name' => 'session/session_add.php'],
                    ]
                );
            }
        }

        return $menu;
    }

    /**
     * @param FactoryInterface $factory
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function profileMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $security = $this->container->get('security.authorization_checker');
        $translator = $this->container->get('translator');

        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->setChildrenAttribute('class', 'nav nav-pills nav-stacked');

            $menu->addChild(
                $translator->trans('Inbox'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'messages/inbox.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('Compose'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'messages/new_message.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('PendingInvitations'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/invitations.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('MyFiles'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/myfiles.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('EditProfile'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'messages/inbox.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('Inbox'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'messages/inbox.php',
                    ),
                )
            );
        }

        return $menu;
    }

    /**
     * @todo add validations
     * @param FactoryInterface $factory
     * @param array $options
     * @return ItemInterface
     */
    public function socialMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $security = $this->container->get('security.authorization_checker');
        $translator = $this->container->get('translator');

        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->setChildrenAttribute('class', 'nav nav-pills nav-stacked');

            $menu->addChild(
                $translator->trans('Home'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/home.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('Messages'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'messages/inbox.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('Invitations'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/invitations.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('ViewMySharedProfile'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/profile.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('Friends'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/friends.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('SocialGroups'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/groups.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('Search'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/search.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('MyFiles'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/myfiles.php',
                    ),
                )
            );
        }

        return $menu;
    }

    /**
     * Skills menu
     * @param FactoryInterface $factory
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function skillsMenu(FactoryInterface $factory, array $options)
    {
        $checker = $this->container->get('security.authorization_checker');
        $translator = $this->container->get('translator');
        $settingsManager = $this->container->get('chamilo.settings.manager');
        //$allow = $settingsManager->getSetting('hide_my_certificate_link');
        $allow = api_get_configuration_value('hide_my_certificate_link');

        $menu = $factory->createItem('root');
        if ($checker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->setChildrenAttribute('class', 'nav nav-pills nav-stacked');

            if ($allow == false) {
                $menu->addChild(
                    $translator->trans('MyCertificates'),
                    [
                        'route' => 'main',
                        'routeParameters' => ['name' => 'gradebook/my_certificates.php'],
                    ]
                );
            }

            if ($settingsManager->getSetting(
                    'allow_public_certificates'
                ) === 'true'
            ) {
                $menu->addChild(
                    $translator->trans('Search'),
                    [
                        'route' => 'main',
                        'routeParameters' => ['name' => 'gradebook/search.php'],
                    ]
                );
            }

            if ($settingsManager->getSetting('allow_skills_tool') === 'true') {
                $menu->addChild(
                    $translator->trans('MySkills'),
                    [
                        'route' => 'main',
                        'routeParameters' => ['name' => 'social/my_skills_report.php'],
                    ]
                );

                if ($checker->isGranted('ROLE_TEACHER')) {
                    $menu->addChild(
                        $translator->trans('ManageSkills'),
                        [
                            'route' => 'main',
                            'routeParameters' => ['name' => 'admin/skills_wheel.php'],
                        ]
                    );
                }
            }
        }

        return $menu;
    }

    /**
     * Register/reset password menu
     * @param FactoryInterface $factory
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function loginMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('main');
        $translator = $this->container->get('translator.default');
        $settingManager = $this->container->get('chamilo.settings.manager');

        if ($settingManager->getSetting('allow_registration') == 'true') {
            $menu->addChild(
                $translator->trans(
                    'registration.submit',
                    array(),
                    'FOSUserBundle'
                ),
                array(
                    'route' => 'main',
                    'routeParameters' => ['name' => 'auth/inscription.php'],
                    array("attributes" => array("id" => 'nav')),
                )
            );
        }

        if ($settingManager->getSetting('allow_lostpassword') == 'true') {
            $menu->addChild(
                $translator->trans(
                    'resetting.request.submit',
                    array(),
                    'FOSUserBundle'
                ),
                array(
                    //'route' => 'fos_user_resetting_request',
                    'route' => 'main',
                    'routeParameters' => ['name' => 'auth/lostPassword.php'],
                    array("attributes" => array("id" => 'nav')),
                )
            );
        }

        return $menu;
    }

    /**
     * @param FactoryInterface $factory
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function helpMenu(FactoryInterface $factory, array $options)
    {
        $translator = $this->container->get('translator.default');
        $menu = $factory->createItem('main');
        $menu->addChild(
            $translator->trans('Forum'),
            array(
                'uri' => 'https://chamilo.org/forum/',
                array("attributes" => array("id" => 'nav')),
            )
        );

        return $menu;
    }
}
