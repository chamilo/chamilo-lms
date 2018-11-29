<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class LeftMenuBuilder.
 *
 * @package Chamilo\CoreBundle\Menu
 */
class LeftMenuBuilder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Course menu.
     *
     * @param FactoryInterface $factory
     * @param array            $options
     *
     * @return ItemInterface
     */
    public function courseMenu(FactoryInterface $factory, array $options)
    {
        //$checker = $this->container->get('security.authorization_checker');
        $menu = $factory->createItem('root');
        $translator = $this->container->get('translator');
        $checked = $this->container->get('session')->get('IS_AUTHENTICATED_FULLY');
        $settingsManager = $this->container->get('chamilo.settings.manager');

        if ($checked) {
            $menu->setChildrenAttribute('class', 'nav nav-pills nav-stacked');
            $menu->addChild(
                $translator->trans('MyCourses'),
                [
                    'route' => 'userportal',
                    'routeParameters' => ['type' => 'courses'],
                ]
            );

            return $menu;

            if (api_is_allowed_to_create_course()) {
                $lang = $translator->trans('Create course');
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
     * Course menu.
     *
     * @param FactoryInterface $factory
     * @param array            $options
     *
     * @return ItemInterface
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
                $translator->trans('My sessions'),
                [
                    'route' => 'userportal',
                    'routeParameters' => ['type' => 'sessions'],
                ]
            );

            if ($checker->isGranted('ROLE_ADMIN')) {
                $menu->addChild(
                    $translator->trans('Add a training session'),
                    [
                        'route' => 'legacy_main',
                        'routeParameters' => ['name' => 'session/session_add.php'],
                    ]
                );
            }
        }

        return $menu;
    }

    /**
     * @param FactoryInterface $factory
     * @param array            $options
     *
     * @return ItemInterface
     */
    public function profileMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $security = $this->container->get('security.authorization_checker');
        $translator = $this->container->get('translator');

        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->setChildrenAttribute('class', 'navbar-nav');

            $menu->addChild(
                $translator->trans('Inbox'),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'messages/inbox.php',
                    ],
                ]
            );

            $menu->addChild(
                $translator->trans('Compose'),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'messages/new_message.php',
                    ],
                ]
            );

            $menu->addChild(
                $translator->trans('Pending invitations'),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'social/invitations.php',
                    ],
                ]
            );

            $menu->addChild(
                $translator->trans('My files'),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'social/myfiles.php',
                    ],
                ]
            );

            $menu->addChild(
                $translator->trans('Edit profile'),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'messages/inbox.php',
                    ],
                ]
            );

            $menu->addChild(
                $translator->trans('Inbox'),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'messages/inbox.php',
                    ],
                ]
            );
        }

        // Set CSS classes for the items
        foreach ($menu->getChildren() as $child) {
            $child
                ->setLinkAttribute('class', 'nav-link')
                ->setAttribute('class', 'nav-item');
        }

        return $menu;
    }

    /**
     * @todo add validations
     *
     * @param FactoryInterface $factory
     * @param array            $options
     *
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
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'social/home.php',
                    ],
                ]
            );

            $menu->addChild(
                $translator->trans('Messages'),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'messages/inbox.php',
                    ],
                ]
            );

            $menu->addChild(
                $translator->trans('Invitations'),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'social/invitations.php',
                    ],
                ]
            );

            $menu->addChild(
                $translator->trans('My shared profile'),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'social/profile.php',
                    ],
                ]
            );

            $menu->addChild(
                $translator->trans('Friends'),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'social/friends.php',
                    ],
                ]
            );

            $menu->addChild(
                $translator->trans('Social groups'),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'social/groups.php',
                    ],
                ]
            );

            $menu->addChild(
                $translator->trans('Search'),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'social/search.php',
                    ],
                ]
            );

            $menu->addChild(
                $translator->trans('My files'),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'social/myfiles.php',
                    ],
                ]
            );
        }

        return $menu;
    }

    /**
     * Skills menu.
     *
     * @param FactoryInterface $factory
     * @param array            $options
     *
     * @return ItemInterface
     */
    public function skillsMenu(FactoryInterface $factory, array $options)
    {
        $checker = $this->container->get('security.authorization_checker');
        $translator = $this->container->get('translator');
        $settingsManager = $this->container->get('chamilo.settings.manager');
        $allow = $settingsManager->getSetting('certificate.hide_my_certificate_link');
        $menu = $factory->createItem('root');

        if ($checker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->setChildrenAttribute('class', 'nav nav-pills nav-stacked');

            if ('false' === $allow) {
                $menu->addChild(
                    $translator->trans('My certificates'),
                    [
                        'route' => 'legacy_main',
                        'routeParameters' => ['name' => 'gradebook/my_certificates.php'],
                    ]
                );
            }

            if ($settingsManager->getSetting('allow_public_certificates') === 'true') {
                $menu->addChild(
                    $translator->trans('Search'),
                    [
                        'route' => 'legacy_main',
                        'routeParameters' => ['name' => 'gradebook/search.php'],
                    ]
                );
            }

            if ($settingsManager->getSetting('allow_skills_tool') === 'true') {
                $menu->addChild(
                    $translator->trans('MySkills'),
                    [
                        'route' => 'legacy_main',
                        'routeParameters' => ['name' => 'social/my_skills_report.php'],
                    ]
                );

                if ($checker->isGranted('ROLE_TEACHER')) {
                    $menu->addChild(
                        $translator->trans('ManageSkills'),
                        [
                            'route' => 'legacy_main',
                            'routeParameters' => ['name' => 'admin/skills_wheel.php'],
                        ]
                    );
                }
            }
        }

        return $menu;
    }

    /**
     * Register/reset password menu.
     *
     * @param FactoryInterface $factory
     * @param array            $options
     *
     * @return ItemInterface
     */
    public function loginMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('legacy_main');
        $translator = $this->container->get('translator');
        $settingManager = $this->container->get('chamilo.settings.manager');

        if ($settingManager->getSetting('allow_registration') === 'true') {
            $menu->addChild(
                $translator->trans(
                    'registration.submit',
                    [],
                    'FOSUserBundle'
                ),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => ['name' => 'auth/inscription.php'],
                    ['attributes' => ['id' => 'nav']],
                ]
            );
        }

        if ($settingManager->getSetting('allow_lostpassword') === 'true') {
            $menu->addChild(
                $translator->trans(
                    'resetting.request.submit',
                    [],
                    'FOSUserBundle'
                ),
                [
                    'route' => 'legacy_main',
                    'routeParameters' => ['name' => 'auth/lostPassword.php'],
                    ['attributes' => ['id' => 'nav']],
                ]
            );
        }

        return $menu;
    }

    /**
     * @param FactoryInterface $factory
     * @param array            $options
     *
     * @return ItemInterface
     */
    public function helpMenu(FactoryInterface $factory, array $options)
    {
        $translator = $this->container->get('translator');
        $menu = $factory->createItem('legacy_main');
        $menu->addChild(
            $translator->trans('Forum'),
            [
                'uri' => 'https://chamilo.org/forum/',
                ['attributes' => ['id' => 'nav']],
            ]
        );

        return $menu;
    }
}
