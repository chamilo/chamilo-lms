<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller\Admin;

use ChamiloLMS\CoreBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Entity;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class Administrator
 * @package ChamiloLMS\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class AdminController extends BaseController
{
    /**
     * @Route("/", name="administration")
     * @Method({"GET"})
     *
     * @return Response
     */
    public function indexAction()
    {
        $security = $this->getSecurity();
        // Already filter by the router
        /*if (!$security->isGranted('ROLE_ADMIN')) {
            return $this->abort(403, 'Access denied');
        }*/

        if ($security->isGranted('ROLE_ADMIN')) {
            return $this->loadAdminMenu();
        }
    }

    /**
     * @param string $url
     * @return \FormValidator
     */
    private function getSearchForm($url)
    {
        $form = new \FormValidator('search-form', 'get', $url, null, array('class' => 'form-inline'));
        $form->addElement('text', 'keyword');
        $form->addElement('button', 'submit', get_lang('Search'));
        return $form;
    }

    /**
     * Move in template.lib
     */
    private function loadAdminMenu()
    {
        // Access restrictions.
        api_protect_admin_script(true);

        $adminUrl = api_get_path(WEB_CODE_PATH).'admin/';
        $blocks = array();

        /* Users */
        $blocks['users']['icon'] = \Display::return_icon('members.gif', get_lang('Users'), array(), ICON_SIZE_SMALL, false);
        $blocks['users']['label'] = api_ucfirst(get_lang('Users'));

        if (api_is_platform_admin()) {
            $search_form = $this->getSearchForm($adminUrl.'user_list.php')->return_form();
            $blocks['users']['search_form'] = $search_form;
            $items = array(
                array('url' => $adminUrl.'user_list.php', 	'label' => get_lang('UserList')),
                array('url' => $adminUrl.'user_add.php', 	'label' => get_lang('AddUsers')),
                array('url' => $adminUrl.'user_export.php', 'label' => get_lang('ExportUserListXMLCSV')),
                array('url' => $adminUrl.'user_import.php', 'label' => get_lang('ImportUserListXMLCSV')),
            );
            if (isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap']) > 0) {
                $items[] = array('url' => $adminUrl.'ldap_users_list.php', 'label' => get_lang('ImportLDAPUsersIntoPlatform'));
            }
            $items[] = array('url' => $adminUrl.'extra_fields.php?type=user', 'label' => get_lang('ManageUserFields'));
            $items[] = array('url'=> api_get_path(WEB_PUBLIC_PATH).'admin/administrator/roles', 'label' => get_lang('Roles'));
        } else {
            $items = array(
                array('url' => $adminUrl.'user_list.php', 	'label' => get_lang('UserList')),
                array('url' => $adminUrl.'user_add.php', 	'label' => get_lang('AddUsers')),
                array('url' => $adminUrl.'user_import.php', 'label' => get_lang('ImportUserListXMLCSV')),
            );
        }

        $items[] = array('url' => $adminUrl.'usergroups.php', 	'label' => get_lang('Classes'));

        $blocks['users']['items'] = $items;
        $blocks['users']['extra'] = null;

        if (api_is_platform_admin()) {
            /* Courses */
            $blocks['courses']['icon']  = \Display::return_icon('course.gif', get_lang('Courses'), array(), ICON_SIZE_MEDIUM, false);
            $blocks['courses']['label'] = api_ucfirst(get_lang('Courses'));

            $search_form = $this->getSearchForm($adminUrl.'course_list.php')->return_form();
            $blocks['courses']['search_form'] = $search_form;

            $items = array();
            $items[] = array('url' => $adminUrl.'course_list.php', 	'label' => get_lang('CourseList'));

            if (api_get_setting('course_validation') != 'true') {
                $items[] = array('url' => $adminUrl.'course_add.php', 	'label' => get_lang('AddCourse'));
            } else {
                $items[] = array('url' => $adminUrl.'course_request_review.php', 	'label' => get_lang('ReviewCourseRequests'));
                $items[] = array('url' => $adminUrl.'course_request_accepted.php', 	'label' => get_lang('AcceptedCourseRequests'));
                $items[] = array('url' => $adminUrl.'course_request_rejected.php', 	'label' => get_lang('RejectedCourseRequests'));
            }

            $items[] = array('url' => $adminUrl.'course_export.php', 			'label' => get_lang('ExportCourses'));
            $items[] = array('url' => $adminUrl.'course_import.php', 			'label' => get_lang('ImportCourses'));
            $items[] = array('url' => $adminUrl.'course_category.php', 			'label' => get_lang('AdminCategories'));
            $items[] = array('url' => $adminUrl.'subscribe_user2course.php', 	'label' => get_lang('AddUsersToACourse'));
            $items[] = array('url' => $adminUrl.'course_user_import.php', 		'label' => get_lang('ImportUsersToACourse'));
            $items[] = array('url' => $adminUrl.'extra_fields.php?type=course', 	'label' => get_lang('ManageCourseFields'));
            $items[] = array('url' => $adminUrl.'extra_fields.php?type=question', 	'label' => get_lang('ManageQuestionFields'));

            if (api_get_setting('gradebook_enable_grade_model') == 'true') {
                $items[] = array('url' => $adminUrl.'grade_models.php',             'label' => get_lang('GradeModel'));
            }

            if (isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap']) > 0) {
                $items[] = array('url' => $adminUrl.'ldap_import_students.php', 	'label' => get_lang('ImportLDAPUsersIntoCourse'));
            }
            $blocks['courses']['items'] = $items;
            $blocks['courses']['extra'] = null;

            /* Platform */
            $blocks['platform']['icon']  = \Display::return_icon('platform.png', get_lang('Platform'), array(), ICON_SIZE_MEDIUM, false);
            $blocks['platform']['label'] = api_ucfirst(get_lang('Platform'));

            $form = $this->getSearchForm($adminUrl.'settings.php');
            $form->addElement('hidden', 'category', 'search_setting');
            $search_form = $form->return_form();
            $blocks['platform']['search_form'] = $search_form;

            $items = array();
            $items[] = array('url' => $adminUrl.'settings.php', 				'label' => get_lang('PlatformConfigSettings'));
            $items[] = array('url' => $adminUrl.'settings.php?category=Plugins','label' => get_lang('Plugins'));
            $items[] = array('url' => $adminUrl.'settings.php?category=Regions','label' => get_lang('Regions'));
            $items[] = array('url' => $adminUrl.'system_announcements.php', 	'label' => get_lang('SystemAnnouncements'));
            $items[] = array('url'=> api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?type=admin', 'label' => get_lang('GlobalAgenda'));
            $items[] = array('url' => $adminUrl.'configure_homepage.php', 		'label' => get_lang('ConfigureHomePage'));
            $items[] = array('url' => $adminUrl.'configure_inscription.php', 	'label' => get_lang('ConfigureInscription'));
            $items[] = array('url' => $adminUrl.'statistics/index.php', 		'label' => get_lang('Statistics'));
            $items[] = array('url'=> api_get_path(WEB_CODE_PATH).'mySpace/company_reports.php',          'label' => get_lang('Reports'));

            /* Event settings */

            if (api_get_setting('activate_email_template') == 'true') {
                $items[] = array('url' => $adminUrl.'event_controller.php?action=listing', 		'label' => get_lang('EventMessageManagement'));
            }

            if (api_get_multiple_access_url()) {
                if (api_is_global_platform_admin()) {
                        $items[] = array('url' => $adminUrl.'access_urls.php', 	'label' => get_lang('ConfigureMultipleAccessURLs'));
                }
            }

            if (api_get_setting('allow_reservation') == 'true') {
                //$items[] = array('url' => $adminUrl.'../reservation/m_category.php', 	'label' => get_lang('BookingSystem'));
            }
            if (api_get_setting('allow_terms_conditions') == 'true') {
                $items[] = array('url' => $adminUrl.'legal_add.php', 	'label' => get_lang('TermsAndConditions'));
            }
            $blocks['platform']['items'] = $items;
            $blocks['platform']['extra'] = null;
        }

        /* Sessions */
        $blocks['sessions']['icon']  = \Display::return_icon('session.png', get_lang('Sessions'), array(), ICON_SIZE_SMALL, false);
        $blocks['sessions']['label'] = api_ucfirst(get_lang('Sessions'));
        $search_form = $this->getSearchForm(api_get_path(WEB_CODE_PATH).'session/session_list.php')->return_form();
        $blocks['sessions']['search_form'] = $search_form;
        $items = array();
        $items[] = array('url'=> api_get_path(WEB_CODE_PATH).'session/session_list.php', 'label' => get_lang('ListSession'));
        $items[] = array('url'=> api_get_path(WEB_CODE_PATH).'session/session_add.php', 	'label' => get_lang('AddSession'));
        $items[] = array('url'=> 'session_category_list.php', 	'label' => get_lang('ListSessionCategory'));
        $items[] = array('url'=> api_get_path(WEB_CODE_PATH).'session/session_import.php', 	'label' => get_lang('ImportSessionListXMLCSV'));
        if (isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap']) > 0) {
            $items[] = array('url' => $adminUrl.'ldap_import_students_to_session.php', 	'label' => get_lang('ImportLDAPUsersIntoSession'));
        }
        $items[] = array('url'=>api_get_path(WEB_CODE_PATH).'session/session_export.php', 	'label' => get_lang('ExportSessionListXMLCSV'));
        $items[] = array('url' => $adminUrl.'../coursecopy/copy_course_session.php', 	'label' => get_lang('CopyFromCourseInSessionToAnotherSession'));

        if (api_is_platform_admin()) {
            if (is_dir(api_get_path(SYS_TEST_PATH).'datafiller/')) { // option only visible in development mode. Enable through code if required
                $items[] = array('url' => $adminUrl.'user_move_stats.php', 	'label' => get_lang('MoveUserStats'));
            }
            $items[] = array('url' => $adminUrl.'career_dashboard.php', 	'label' => get_lang('CareersAndPromotions'));
        }

        $items[] = array('url' => $adminUrl.'exercise_report.php', 	'label' => get_lang('ExerciseReport'));
        $items[] = array('url' => $adminUrl.'extra_fields.php?type=session', 	'label' => get_lang('ManageSessionFields'));

        $blocks['sessions']['items'] = $items;
        $blocks['sessions']['extra'] = null;

        /* Settings */
        if (api_is_platform_admin()) {

            $blocks['settings']['icon'] = \Display::return_icon('settings.png', get_lang('System'), array(), ICON_SIZE_SMALL, false);
            $blocks['settings']['label'] = api_ucfirst(get_lang('System'));

            $items = array();
            $items[] = array('url' => $adminUrl.'special_exports.php', 	'label' => get_lang('SpecialExports'));
            if (!empty($_configuration['db_admin_path'])) {
                $items[] = array('url'=>$_configuration['db_admin_path'], 	'label' => get_lang('AdminDatabases').' ('.get_lang('DBManagementOnlyForServerAdmin').') ');
            }
            $items[] = array('url' => $adminUrl.'system_status.php', 	'label' => get_lang('SystemStatus'));
            if (is_dir(api_get_path(SYS_TEST_PATH).'datafiller/')) {
                $items[] = array('url' => $adminUrl.'filler.php', 	'label' => get_lang('DataFiller'));
            }
            $items[] = array('url' => $adminUrl.'archive_cleanup.php', 	'label' => get_lang('ArchiveDirCleanup'));
            //$items[] = array('url' => $adminUrl.'system_management.php', 'label' => get_lang('SystemManagement'));

            $blocks['settings']['items'] = $items;
            $blocks['settings']['extra'] = null;

            $blocks['settings']['search_form'] = null;

            //Skills
            if (api_get_setting('allow_skills_tool') == 'true') {
                $blocks['skills']['icon']  = \Display::return_icon('logo.png', get_lang('Skills'), array(), ICON_SIZE_SMALL, false);
                $blocks['skills']['label'] = get_lang('Skills');

                $items = array();
                //$items[] = array('url' => $adminUrl.'skills.php',           'label' => get_lang('SkillsTree'));
                $items[] = array('url' => $adminUrl.'skills_wheel.php',     'label' => get_lang('SkillsWheel'));
                $items[] = array('url' => $adminUrl.'skills_import.php',    'label' => get_lang('SkillsImport'));
                //$items[] = array('url' => $adminUrl.'skills_profile.php',   'label' => get_lang('SkillsProfile'));
                $items[] = array('url'=>api_get_path(WEB_CODE_PATH).'social/skills_ranking.php',   'label' => get_lang('SkillsRanking'));
                $items[] = array('url' => $adminUrl.'skills_gradebook.php', 'label' => get_lang('SkillsAndGradebooks'));
                $blocks['skills']['items'] = $items;
                $blocks['skills']['extra'] = null;
                $blocks['skills']['search_form'] = null;
            }

            /** Chamilo.org */

            $blocks['chamilo']['icon']  = \Display::return_icon('logo.png', 'Chamilo.org', array(), ICON_SIZE_SMALL, false);
            $blocks['chamilo']['label'] = 'Chamilo.org';

            $items = array();
            $items[] = array('url'=>'http://www.chamilo.org/', 	'label' => get_lang('ChamiloHomepage'));
            $items[] = array('url'=>'http://www.chamilo.org/forum', 	'label' => get_lang('ChamiloForum'));
            $items[] = array('url'=>'../../documentation/installation_guide.html', 	'label' => get_lang('InstallationGuide'));
            $items[] = array('url'=>'../../documentation/changelog.html', 	'label' => get_lang('ChangesInLastVersion'));
            $items[] = array('url'=>'../../documentation/credits.html', 	'label' => get_lang('ContributorsList'));
            $items[] = array('url'=>'../../documentation/security.html', 	'label' => get_lang('SecurityGuide'));
            $items[] = array('url'=>'../../documentation/optimization.html', 	'label' => get_lang('OptimizationGuide'));
            $items[] = array('url'=>'http://www.chamilo.org/extensions', 	'label' => get_lang('ChamiloExtensions'));
            $items[] = array('url'=>'http://www.chamilo.org/en/providers', 	'label' => get_lang('ChamiloOfficialServicesProviders'));

            $blocks['chamilo']['items'] = $items;
            $blocks['chamilo']['extra'] = null;
            $blocks['chamilo']['search_form'] = null;
        }

        $admin_ajax_url = api_get_path(WEB_AJAX_PATH).'admin.ajax.php';

        return $this->render(
            'ChamiloLMSCoreBundle:Admin:index.html.twig',
            array(
                'blocks' => $blocks,
                'web_admin_ajax_url' => $admin_ajax_url
            )
        );
    }
}
