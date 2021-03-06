<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Display;
use FormValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class Administrator.
 *
 * @Route("/admin")
 */
class AdminController extends BaseController
{
    /**
     * @Route("/", methods={"GET"}, name="administration")
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        // Already filter by the router
        /*if (!$security->isGranted('ROLE_ADMIN')) {
            $this->abort(403, 'Access denied');
        }*/
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->loadAdminMenu();
        }

        throw $this->createAccessDeniedException();
    }

    /**
     * @return FormValidator
     */
    private function getSearchForm(string $url)
    {
        $form = new FormValidator(
            'search-form',
            'get',
            $url,
            '',
            [
                'class' => 'form-inline',
            ]
        );
        $form->addElement('text', 'keyword');
        $form->addElement('button', 'submit', get_lang('Search'));

        return $form;
    }

    /**
     * Move in template.lib.
     */
    private function loadAdminMenu(): Response
    {
        // Access restrictions.
        api_protect_admin_script(true);

        $adminUrl = api_get_path(WEB_CODE_PATH).'admin/';
        $blocks = [];

        /* Users */
        $blocks['users']['icon'] = Display::return_icon(
            'members.png',
            get_lang('Users'),
            [],
            ICON_SIZE_MEDIUM,
            false
        );
        $blocks['users']['label'] = api_ucfirst(get_lang('Users'));

        if (api_is_platform_admin()) {
            $search_form = $this->getSearchForm($adminUrl.'user_list.php')->returnForm();
            $blocks['users']['search_form'] = $search_form;
            $items = [
                [
                    'url' => $adminUrl.'user_list.php',
                    'label' => get_lang('User list'),
                ],
                [
                    'url' => $adminUrl.'user_add.php',
                    'label' => get_lang('Add a user'),
                ],
                [
                    'url' => $adminUrl.'user_export.php',
                    'label' => get_lang('ExportUser listXMLCSV'),
                ],
                [
                    'url' => $adminUrl.'user_import.php',
                    'label' => get_lang('ImportUser listXMLCSV'),
                ],
            ];
            $items[] = [
                'url' => $adminUrl.'extra_fields.php?type=user',
                'label' => get_lang('Profiling'),
            ];
        } else {
            $items = [
                [
                    'url' => $adminUrl.'user_list.php',
                    'label' => get_lang('User list'),
                ],
                [
                    'url' => $adminUrl.'user_add.php',
                    'label' => get_lang('Add a user'),
                ],
                [
                    'url' => $adminUrl.'user_import.php',
                    'label' => get_lang('ImportUser listXMLCSV'),
                ],
            ];
        }

        $items[] = [
            'url' => $adminUrl.'usergroups.php',
            'label' => get_lang('Classes'),
        ];

        $blocks['users']['items'] = $items;
        $blocks['users']['extra'] = null;

        if (api_is_platform_admin()) {
            /* Courses */
            $blocks['courses']['icon'] = Display::return_icon(
                'course.png',
                get_lang('Courses'),
                [],
                ICON_SIZE_MEDIUM,
                false
            );
            $blocks['courses']['label'] = api_ucfirst(get_lang('Courses'));

            $search_form = $this->getSearchForm(
                $adminUrl.'course_list.php'
            )->returnForm();
            $blocks['courses']['search_form'] = $search_form;

            $items = [];
            $items[] = [
                'url' => $adminUrl.'course_list.php',
                'label' => get_lang('Course list'),
            ];

            if ('true' !== api_get_setting('course.course_validation')) {
                $items[] = [
                    'url' => $adminUrl.'course_add.php',
                    'label' => get_lang('Create a course'),
                ];
            } else {
                $items[] = [
                    'url' => $adminUrl.'course_request_review.php',
                    'label' => get_lang('Review incoming course requests'),
                ];
                $items[] = [
                    'url' => $adminUrl.'course_request_accepted.php',
                    'label' => get_lang('Accepted course requests'),
                ];
                $items[] = [
                    'url' => $adminUrl.'course_request_rejected.php',
                    'label' => get_lang('Rejected course requests'),
                ];
            }

            $items[] = [
                'url' => $adminUrl.'course_export.php',
                'label' => get_lang('Export courses'),
            ];
            $items[] = [
                'url' => $adminUrl.'course_import.php',
                'label' => get_lang('Import courses list'),
            ];
            $items[] = [
                'url' => $adminUrl.'course_category.php',
                'label' => get_lang('Courses categories'),
            ];
            $items[] = [
                'url' => $adminUrl.'subscribe_user2course.php',
                'label' => get_lang('Add a user to a course'),
            ];
            $items[] = [
                'url' => $adminUrl.'course_user_import.php',
                'label' => get_lang('Import users list'),
            ];
            $items[] = [
                'url' => $adminUrl.'extra_fields.php?type=course',
                'label' => get_lang('Manage extra fields for courses'),
            ];
            $items[] = [
                'url' => $adminUrl.'extra_fields.php?type=question',
                'label' => get_lang('Manage extra fields for questions'),
            ];

            /*if (api_get_setting('gradebook.gradebook_enable_grade_model') == 'true') {
                $items[] = array(
                    'url' => $adminUrl.'grade_models.php',
                    'label' => get_lang('Grading model'),
                );
            }*/
            $blocks['courses']['items'] = $items;
            $blocks['courses']['extra'] = null;

            /* Portal */
            $blocks['platform']['icon'] = Display::return_icon(
                'platform.png',
                get_lang('Portal'),
                [],
                ICON_SIZE_MEDIUM,
                false
            );
            $blocks['platform']['label'] = api_ucfirst(get_lang('Portal'));

            $form = $this->getSearchForm($adminUrl.'settings.php');
            $form->addElement('hidden', 'category', 'search_setting');
            $search_form = $form->returnForm();
            $blocks['platform']['search_form'] = $search_form;

            $items = [];
            $items[] = [
                'url' => $this->generateUrl(
                    'admin_settings'
                ),
                'label' => get_lang('PortalConfigSettings'),
            ];
            $items[] = [
                'url' => $adminUrl.'settings.php?category=Plugins',
                'label' => get_lang('Plugins'),
            ];
            $items[] = [
                'url' => $adminUrl.'settings.php?category=Regions',
                'label' => get_lang('Regions'),
            ];
            $items[] = [
                'url' => $adminUrl.'system_announcements.php',
                'label' => get_lang('Portal news'),
            ];
            $items[] = [
                'url' => api_get_path(
                    WEB_CODE_PATH
                ).'calendar/agenda_js.php?type=admin',
                'label' => get_lang('Global agenda'),
            ];
            $items[] = [
                'url' => $adminUrl.'configure_homepage.php',
                'label' => get_lang('Edit portal homepage'),
            ];
            $items[] = [
                'url' => $adminUrl.'configure_inscription.php',
                'label' => get_lang('Setting the registration page'),
            ];
            $items[] = [
                'url' => $adminUrl.'statistics/index.php',
                'label' => get_lang('Statistics'),
            ];
            $items[] = [
                'url' => api_get_path(
                    WEB_CODE_PATH
                ).'mySpace/company_reports.php',
                'label' => get_lang('Reports'),
            ];

            if (api_get_multiple_access_url() && api_is_global_platform_admin()) {
                $items[] = [
                    'url' => $adminUrl.'access_urls.php',
                    'label' => get_lang('Configure multiple access URL'),
                ];
            }

            if ('true' ===
                api_get_setting('registration.allow_terms_conditions')
            ) {
                $items[] = [
                    'url' => $adminUrl.'legal_add.php',
                    'label' => get_lang('Terms and Conditions'),
                ];
            }
            $blocks['platform']['items'] = $items;
            $blocks['platform']['extra'] = null;
        }

        /* Course sessions */
        $blocks['sessions']['icon'] = Display::return_icon(
            'session.png',
            get_lang('Course sessions'),
            [],
            ICON_SIZE_MEDIUM,
            false
        );
        $blocks['sessions']['label'] = api_ucfirst(get_lang('Course sessions'));
        $search_form = $this->getSearchForm(
            api_get_path(WEB_CODE_PATH).'session/session_list.php'
        )->returnForm();
        $blocks['sessions']['search_form'] = $search_form;
        $items = [];
        $items[] = [
            'url' => api_get_path(
                WEB_CODE_PATH
            ).'session/session_list.php',
            'label' => get_lang('Training sessions list'),
        ];
        $items[] = [
            'url' => api_get_path(
                WEB_CODE_PATH
            ).'session/session_add.php',
            'label' => get_lang('Add a training session'),
        ];
        $items[] = [
            'url' => api_get_path(WEB_CODE_PATH).'session/session_category_list.php',
            'label' => get_lang('Training sessions listCategory'),
        ];
        $items[] = [
            'url' => api_get_path(WEB_CODE_PATH).'session/session_import.php',
            'label' => get_lang('Import sessions list'),
        ];

        $items[] = [
            'url' => api_get_path(WEB_CODE_PATH).'session/session_export.php',
            'label' => get_lang('Export sessions list'),
        ];
        $items[] = [
            'url' => $adminUrl.'../coursecopy/copy_course_session.php',
            'label' => get_lang('Copy from course in session to another session'),
        ];

        if (api_is_platform_admin()) {
            if (is_dir(
                api_get_path(SYS_TEST_PATH).'datafiller/'
            )) { // option only visible in development mode. Enable through code if required
                $items[] = [
                    'url' => $adminUrl.'user_move_stats.php',
                    'label' => get_lang('Move users results from/to a session'),
                ];
            }
            $items[] = [
                'url' => $adminUrl.'career_dashboard.php',
                'label' => get_lang('Careers and promotions'),
            ];
        }

        $items[] = [
            'url' => $adminUrl.'exercise_report.php',
            'label' => get_lang('ExerciseReport'),
        ];
        $items[] = [
            'url' => $adminUrl.'extra_fields.php?type=session',
            'label' => get_lang('Manage session fields'),
        ];

        $blocks['sessions']['items'] = $items;
        $blocks['sessions']['extra'] = null;

        /* Settings */
        if (api_is_platform_admin()) {
            $blocks['settings']['icon'] = Display::return_icon(
                'settings.png',
                get_lang('System'),
                [],
                ICON_SIZE_MEDIUM,
                false
            );
            $blocks['settings']['label'] = api_ucfirst(get_lang('System'));

            $items = [];
            $items[] = [
                'url' => $adminUrl.'special_exports.php',
                'label' => get_lang('Special exports'),
            ];
            $dbPath = api_get_configuration_value('db_admin_path');
            if (!empty($dbPath)) {
                $items[] = [
                    'url' => $dbPath,
                    'label' => get_lang('Databases (phpMyAdmin)').' ('.get_lang('Database management is only available for the server administrator').') ',
                ];
            }
            $items[] = [
                'url' => $adminUrl.'system_status.php',
                'label' => get_lang('System status'),
            ];
            if (is_dir(api_get_path(SYS_TEST_PATH).'datafiller/')) {
                $items[] = [
                    'url' => $adminUrl.'filler.php',
                    'label' => get_lang('Data filler'),
                ];
            }
            $items[] = [
                'url' => $adminUrl.'archive_cleanup.php',
                'label' => get_lang('Cleanup of cache and temporary files'),
            ];
            //$items[] = array('url' => $adminUrl.'system_management.php', 'label' => get_lang('System Management'));

            $blocks['settings']['items'] = $items;
            $blocks['settings']['extra'] = null;

            $blocks['settings']['search_form'] = null;

            //Skills
            if ('true' === api_get_setting('skill.allow_skills_tool')) {
                $blocks['skills']['icon'] = Display::return_icon(
                    'skill-badges.png',
                    get_lang('Skills'),
                    [],
                    ICON_SIZE_MEDIUM,
                    false
                );
                $blocks['skills']['label'] = get_lang('Skills');

                $items = [];
                //$items[] = array('url' => $adminUrl.'skills.php',           'label' => get_lang('Skills Tree'));
                $items[] = [
                    'url' => $adminUrl.'skills_wheel.php',
                    'label' => get_lang('Skills wheel'),
                ];
                $items[] = [
                    'url' => $adminUrl.'skills_import.php',
                    'label' => get_lang('Skills import'),
                ];
                //$items[] = array('url' => $adminUrl.'skills_profile.php',   'label' => get_lang('Skills Profile'));
                $items[] = [
                    'url' => api_get_path(
                        WEB_CODE_PATH
                    ).'social/skills_ranking.php',
                    'label' => get_lang('Skills ranking'),
                ];
                $items[] = [
                    'url' => $adminUrl.'skills_gradebook.php',
                    'label' => get_lang('Skills and assessments'),
                ];
                $blocks['skills']['items'] = $items;
                $blocks['skills']['extra'] = null;
                $blocks['skills']['search_form'] = null;
            }

            /* Chamilo.org */
            $blocks['chamilo']['icon'] = Display::return_icon(
                'platform.png',
                'Chamilo.org',
                [],
                ICON_SIZE_MEDIUM,
                false
            );
            $blocks['chamilo']['label'] = 'Chamilo.org';

            $items = [];
            $items[] = [
                'url' => 'http://www.chamilo.org/',
                'label' => get_lang('Chamilo homepage'),
            ];
            $items[] = [
                'url' => 'http://www.chamilo.org/forum',
                'label' => get_lang('Chamilo forum'),
            ];
            $items[] = [
                'url' => '../../documentation/installation_guide.html',
                'label' => get_lang('Installation guide'),
            ];
            $items[] = [
                'url' => '../../documentation/changelog.html',
                'label' => get_lang('Changes in last version'),
            ];
            $items[] = [
                'url' => '../../documentation/credits.html',
                'label' => get_lang('Contributors list'),
            ];
            $items[] = [
                'url' => '../../documentation/security.html',
                'label' => get_lang('Security guide'),
            ];
            $items[] = [
                'url' => '../../documentation/optimization.html',
                'label' => get_lang('Optimization guide'),
            ];
            $items[] = [
                'url' => 'http://www.chamilo.org/extensions',
                'label' => get_lang('Chamilo extensions'),
            ];
            $items[] = [
                'url' => 'http://www.chamilo.org/en/providers',
                'label' => get_lang('Chamilo official services providers'),
            ];

            $blocks['chamilo']['items'] = $items;
            $blocks['chamilo']['extra'] = null;
            $blocks['chamilo']['search_form'] = null;
        }

        $admin_ajax_url = api_get_path(WEB_AJAX_PATH).'admin.ajax.php';

        return $this->render(
            'ChamiloCoreBundle:Admin:index.html.twig',
            [
                'blocks' => $blocks,
                'web_admin_ajax_url' => $admin_ajax_url,
            ]
        );
    }
}
