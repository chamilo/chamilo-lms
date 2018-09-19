<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class Administrator.
 *
 * @package Chamilo\CoreBundle\Controller
 *
 * @author Julio Montoya <gugli100@gmail.com>
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
            return $this->abort(403, 'Access denied');
        }*/
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->loadAdminMenu();
        }
    }

    /**
     * @param string $url
     *
     * @return \FormValidator
     */
    private function getSearchForm($url)
    {
        $form = new \FormValidator(
            'search-form',
            'get',
            $url,
            null,
            ['class' => 'form-inline']
        );
        $form->addElement('text', 'keyword');
        $form->addElement('button', 'submit', get_lang('Search'));

        return $form;
    }

    /**
     * Move in template.lib.
     */
    private function loadAdminMenu()
    {
        // Access restrictions.
        api_protect_admin_script(true);

        $adminUrl = api_get_path(WEB_CODE_PATH).'admin/';
        $blocks = [];

        /* Users */
        $blocks['users']['icon'] = \Display::return_icon(
            'members.png',
            get_lang('Users'),
            [],
            ICON_SIZE_MEDIUM,
            false
        );
        $blocks['users']['label'] = api_ucfirst(get_lang('Users'));

        if (api_is_platform_admin()) {
            $search_form = $this->getSearchForm($adminUrl.'user_list.php')->return_form();
            $blocks['users']['search_form'] = $search_form;
            $items = [
                [
                    'url' => $adminUrl.'user_list.php',
                    'label' => get_lang('UserList'),
                ],
                [
                    'url' => $adminUrl.'user_add.php',
                    'label' => get_lang('AddUsers'),
                ],
                [
                    'url' => $adminUrl.'user_export.php',
                    'label' => get_lang('ExportUserListXMLCSV'),
                ],
                [
                    'url' => $adminUrl.'user_import.php',
                    'label' => get_lang('ImportUserListXMLCSV'),
                ],
            ];
            if (isset($extAuthSource) && isset($extAuthSource['ldap']) && count(
                    $extAuthSource['ldap']
                ) > 0
            ) {
                $items[] = [
                    'url' => $adminUrl.'ldap_users_list.php',
                    'label' => get_lang('ImportLDAPUsersIntoPlatform'),
                ];
            }
            $items[] = [
                'url' => $adminUrl.'extra_fields.php?type=user',
                'label' => get_lang('ManageUserFields'),
            ];
        } else {
            $items = [
                [
                    'url' => $adminUrl.'user_list.php',
                    'label' => get_lang('UserList'),
                ],
                [
                    'url' => $adminUrl.'user_add.php',
                    'label' => get_lang('AddUsers'),
                ],
                [
                    'url' => $adminUrl.'user_import.php',
                    'label' => get_lang('ImportUserListXMLCSV'),
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
            $blocks['courses']['icon'] = \Display::return_icon(
                'course.png',
                get_lang('Courses'),
                [],
                ICON_SIZE_MEDIUM,
                false
            );
            $blocks['courses']['label'] = api_ucfirst(get_lang('Courses'));

            $search_form = $this->getSearchForm(
                $adminUrl.'course_list.php'
            )->return_form();
            $blocks['courses']['search_form'] = $search_form;

            $items = [];
            $items[] = [
                'url' => $adminUrl.'course_list.php',
                'label' => get_lang('CourseList'),
            ];

            if (api_get_setting('course.course_validation') != 'true') {
                $items[] = [
                    'url' => $adminUrl.'course_add.php',
                    'label' => get_lang('AddCourse'),
                ];
            } else {
                $items[] = [
                    'url' => $adminUrl.'course_request_review.php',
                    'label' => get_lang('ReviewCourseRequests'),
                ];
                $items[] = [
                    'url' => $adminUrl.'course_request_accepted.php',
                    'label' => get_lang('AcceptedCourseRequests'),
                ];
                $items[] = [
                    'url' => $adminUrl.'course_request_rejected.php',
                    'label' => get_lang('RejectedCourseRequests'),
                ];
            }

            $items[] = [
                'url' => $adminUrl.'course_export.php',
                'label' => get_lang('ExportCourses'),
            ];
            $items[] = [
                'url' => $adminUrl.'course_import.php',
                'label' => get_lang('ImportCourses'),
            ];
            $items[] = [
                'url' => $adminUrl.'course_category.php',
                'label' => get_lang('AdminCategories'),
            ];
            $items[] = [
                'url' => $adminUrl.'subscribe_user2course.php',
                'label' => get_lang('AddUsersToACourse'),
            ];
            $items[] = [
                'url' => $adminUrl.'course_user_import.php',
                'label' => get_lang('ImportUsersToACourse'),
            ];
            $items[] = [
                'url' => $adminUrl.'extra_fields.php?type=course',
                'label' => get_lang('ManageCourseFields'),
            ];
            $items[] = [
                'url' => $adminUrl.'extra_fields.php?type=question',
                'label' => get_lang('ManageQuestionFields'),
            ];

            /*if (api_get_setting('gradebook.gradebook_enable_grade_model') == 'true') {
                $items[] = array(
                    'url' => $adminUrl.'grade_models.php',
                    'label' => get_lang('GradeModel'),
                );
            }*/

            if (isset($extAuthSource) &&
                isset($extAuthSource['ldap']) &&
                count($extAuthSource['ldap']) > 0
            ) {
                $items[] = [
                    'url' => $adminUrl.'ldap_import_students.php',
                    'label' => get_lang('ImportLDAPUsersIntoCourse'),
                ];
            }
            $blocks['courses']['items'] = $items;
            $blocks['courses']['extra'] = null;

            /* Platform */
            $blocks['platform']['icon'] = \Display::return_icon(
                'platform.png',
                get_lang('Platform'),
                [],
                ICON_SIZE_MEDIUM,
                false
            );
            $blocks['platform']['label'] = api_ucfirst(get_lang('Platform'));

            $form = $this->getSearchForm($adminUrl.'settings.php');
            $form->addElement('hidden', 'category', 'search_setting');
            $search_form = $form->return_form();
            $blocks['platform']['search_form'] = $search_form;

            $items = [];
            $items[] = [
                'url' => $this->generateUrl(
                    'admin_settings'
                ),
                'label' => get_lang('PlatformConfigSettings'),
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
                'label' => get_lang('SystemAnnouncements'),
            ];
            $items[] = [
                'url' => api_get_path(
                        WEB_CODE_PATH
                    ).'calendar/agenda_js.php?type=admin',
                'label' => get_lang('GlobalAgenda'),
            ];
            $items[] = [
                'url' => $adminUrl.'configure_homepage.php',
                'label' => get_lang('ConfigureHomePage'),
            ];
            $items[] = [
                'url' => $adminUrl.'configure_inscription.php',
                'label' => get_lang('ConfigureInscription'),
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

            if (api_get_multiple_access_url()) {
                if (api_is_global_platform_admin()) {
                    $items[] = [
                        'url' => $adminUrl.'access_urls.php',
                        'label' => get_lang('ConfigureMultipleAccessURLs'),
                    ];
                }
            }

            if (api_get_setting('registration.allow_terms_conditions') ==
                'true'
            ) {
                $items[] = [
                    'url' => $adminUrl.'legal_add.php',
                    'label' => get_lang('TermsAndConditions'),
                ];
            }
            $blocks['platform']['items'] = $items;
            $blocks['platform']['extra'] = null;
        }

        /* Sessions */
        $blocks['sessions']['icon'] = \Display::return_icon(
            'session.png',
            get_lang('Sessions'),
            [],
            ICON_SIZE_MEDIUM,
            false
        );
        $blocks['sessions']['label'] = api_ucfirst(get_lang('Sessions'));
        $search_form = $this->getSearchForm(
            api_get_path(WEB_CODE_PATH).'session/session_list.php'
        )->return_form();
        $blocks['sessions']['search_form'] = $search_form;
        $items = [];
        $items[] = [
            'url' => api_get_path(
                    WEB_CODE_PATH
                ).'session/session_list.php',
            'label' => get_lang('ListSession'),
        ];
        $items[] = [
            'url' => api_get_path(
                    WEB_CODE_PATH
                ).'session/session_add.php',
            'label' => get_lang('AddSession'),
        ];
        $items[] = [
            'url' => api_get_path(WEB_CODE_PATH).'session/session_category_list.php',
            'label' => get_lang('ListSessionCategory'),
        ];
        $items[] = [
            'url' => api_get_path(WEB_CODE_PATH).'session/session_import.php',
            'label' => get_lang('ImportSessionListXMLCSV'),
        ];
        if (isset($extAuthSource) && isset($extAuthSource['ldap']) && count(
                $extAuthSource['ldap']
            ) > 0
        ) {
            $items[] = [
                'url' => $adminUrl.'ldap_import_students_to_session.php',
                'label' => get_lang('ImportLDAPUsersIntoSession'),
            ];
        }
        $items[] = [
            'url' => api_get_path(WEB_CODE_PATH).'session/session_export.php',
            'label' => get_lang('ExportSessionListXMLCSV'),
        ];
        $items[] = [
            'url' => $adminUrl.'../coursecopy/copy_course_session.php',
            'label' => get_lang('CopyFromCourseInSessionToAnotherSession'),
        ];

        if (api_is_platform_admin()) {
            if (is_dir(
                api_get_path(SYS_TEST_PATH).'datafiller/'
            )) { // option only visible in development mode. Enable through code if required
                $items[] = [
                    'url' => $adminUrl.'user_move_stats.php',
                    'label' => get_lang('MoveUserStats'),
                ];
            }
            $items[] = [
                'url' => $adminUrl.'career_dashboard.php',
                'label' => get_lang('CareersAndPromotions'),
            ];
        }

        $items[] = [
            'url' => $adminUrl.'exercise_report.php',
            'label' => get_lang('ExerciseReport'),
        ];
        $items[] = [
            'url' => $adminUrl.'extra_fields.php?type=session',
            'label' => get_lang('ManageSessionFields'),
        ];

        $blocks['sessions']['items'] = $items;
        $blocks['sessions']['extra'] = null;

        /* Settings */
        if (api_is_platform_admin()) {
            $blocks['settings']['icon'] = \Display::return_icon(
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
                'label' => get_lang('SpecialExports'),
            ];
            $dbPath = api_get_configuration_value('db_admin_path');
            if (!empty($dbPath)) {
                $items[] = [
                    'url' => $dbPath,
                    'label' => get_lang('AdminDatabases').' ('.get_lang('DBManagementOnlyForServerAdmin').') ',
                ];
            }
            $items[] = [
                'url' => $adminUrl.'system_status.php',
                'label' => get_lang('SystemStatus'),
            ];
            if (is_dir(api_get_path(SYS_TEST_PATH).'datafiller/')) {
                $items[] = [
                    'url' => $adminUrl.'filler.php',
                    'label' => get_lang('DataFiller'),
                ];
            }
            $items[] = [
                'url' => $adminUrl.'archive_cleanup.php',
                'label' => get_lang('ArchiveDirCleanup'),
            ];
            //$items[] = array('url' => $adminUrl.'system_management.php', 'label' => get_lang('SystemManagement'));

            $blocks['settings']['items'] = $items;
            $blocks['settings']['extra'] = null;

            $blocks['settings']['search_form'] = null;

            //Skills
            if (api_get_setting('skill.allow_skills_tool') == 'true') {
                $blocks['skills']['icon'] = \Display::return_icon(
                    'skill-badges.png',
                    get_lang('Skills'),
                    [],
                    ICON_SIZE_MEDIUM,
                    false
                );
                $blocks['skills']['label'] = get_lang('Skills');

                $items = [];
                //$items[] = array('url' => $adminUrl.'skills.php',           'label' => get_lang('SkillsTree'));
                $items[] = [
                    'url' => $adminUrl.'skills_wheel.php',
                    'label' => get_lang('SkillsWheel'),
                ];
                $items[] = [
                    'url' => $adminUrl.'skills_import.php',
                    'label' => get_lang('SkillsImport'),
                ];
                //$items[] = array('url' => $adminUrl.'skills_profile.php',   'label' => get_lang('SkillsProfile'));
                $items[] = [
                    'url' => api_get_path(
                            WEB_CODE_PATH
                        ).'social/skills_ranking.php',
                    'label' => get_lang('SkillsRanking'),
                ];
                $items[] = [
                    'url' => $adminUrl.'skills_gradebook.php',
                    'label' => get_lang('SkillsAndGradebooks'),
                ];
                $blocks['skills']['items'] = $items;
                $blocks['skills']['extra'] = null;
                $blocks['skills']['search_form'] = null;
            }

            /** Chamilo.org */
            $blocks['chamilo']['icon'] = \Display::return_icon(
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
                'label' => get_lang('ChamiloHomepage'),
            ];
            $items[] = [
                'url' => 'http://www.chamilo.org/forum',
                'label' => get_lang('ChamiloForum'),
            ];
            $items[] = [
                'url' => '../../documentation/installation_guide.html',
                'label' => get_lang('InstallationGuide'),
            ];
            $items[] = [
                'url' => '../../documentation/changelog.html',
                'label' => get_lang('ChangesInLastVersion'),
            ];
            $items[] = [
                'url' => '../../documentation/credits.html',
                'label' => get_lang('ContributorsList'),
            ];
            $items[] = [
                'url' => '../../documentation/security.html',
                'label' => get_lang('SecurityGuide'),
            ];
            $items[] = [
                'url' => '../../documentation/optimization.html',
                'label' => get_lang('OptimizationGuide'),
            ];
            $items[] = [
                'url' => 'http://www.chamilo.org/extensions',
                'label' => get_lang('ChamiloExtensions'),
            ];
            $items[] = [
                'url' => 'http://www.chamilo.org/en/providers',
                'label' => get_lang('ChamiloOfficialServicesProviders'),
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
