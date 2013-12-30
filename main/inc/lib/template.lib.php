<?php
/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com>
 * @todo better organization of the class, methods and variables
 *
 * */
use \ChamiloSession as Session;
use Silex\Application;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Routing\Router;

class Template
{
    private $app;
    public $preview_theme = null;
    public $theme; // the chamilo theme public_admin, chamilo, chamilo_red, etc
    public $title = null;
    public $show_header;
    public $show_footer;
    public $help;
    public $menu_navigation = array();
    public $show_learnpath = false; // This is a learnpath section or not?
    public $plugin = null;
    public $course_id = null;
    public $user_is_logged_in = false;
    public $twig = null;
    public $jquery_ui_theme;
    public $force_plugin_load = true;
    public $navigation_array;
    public $loadBreadcrumb = true;

    /** @var SecurityContext */
    private $security;
    /** @var Translator */
    private $translator;
    /** @var Router */
    private $urlGenerator;

    /**
     * @param Application $app
     * @param Database $database
     * @param SecurityContext $security
     * @param Translator $translator
     * @param Router $urlGenerator
     */
    public function __construct(
        Application $app,
        Database $database,
        SecurityContext $security,
        Translator $translator,
        Router $urlGenerator
    ) {
        $this->app = &$app;
        $this->security = $security;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;

        $this->app['classic_layout'] = true;
        $this->navigation_array = $this->returnNavigationArray();

        // Just in case
        global $tool_name;

        // Page title
        $this->title = isset($app['title']) ? $app['title'] : $tool_name;
        $this->show_learnpath = $app['template.show_learnpath'];

        /* Current themes: cupertino, smoothness, ui-lightness.
           Find the themes folder in main/inc/lib/javascript/jquery-ui */
        $this->jquery_ui_theme = 'smoothness';

        // Setting system variables.
        $this->setSystemParameters();

        // Setting user variables.
        $this->setUserParameters();

        // Setting course variables.
        $this->setCourseParameters();

        // header and footer are showed by default
        $this->setFooter($app['template.show_footer']);
        $this->setHeader($app['template.show_header']);

        $this->setHeaderParameters();
        $this->setFooterParameters();

        $this->assign('style', $app['template_style']);

        //Chamilo plugins
        if ($this->show_header) {
            if ($app['template.load_plugins'] && !empty($app['plugins'])) {

                $this->plugin = new AppPlugin();

                //1. Showing installed plugins in regions
                $plugin_regions = $this->plugin->get_plugin_regions();
                foreach ($plugin_regions as $region) {
                    $this->set_plugin_region($region);
                }

                //2. Loading the course plugin info
                global $course_plugin;
                if (isset($course_plugin) && !empty($course_plugin) && !empty($this->course_id)) {
                    //Load plugin get_langs
                    $this->plugin->load_plugin_lang_variables($course_plugin);
                }
            }
        }
    }

    /**
     * @param array $breadCrumb
     */
    public function setBreadcrumb($breadCrumb)
    {
        if (isset($this->app['breadcrumb']) && !empty($this->app['breadcrumb'])) {
            if (empty($breadCrumb)) {
                $breadCrumb = $this->app['breadcrumb'];
            } else {
                $breadCrumb = array_merge($breadCrumb, $this->app['breadcrumb']);
            }
        }

        if (!empty($breadCrumb)) {
            $this->app['breadcrumb'] = $breadCrumb;
        }
    }

    /**
     * Get icon path
     * @param string $image
     * @param int $size
     *
     * @return string
     */
    public static function get_icon_path($image, $size = ICON_SIZE_SMALL)
    {
        return Display::return_icon($image, '', array(), $size, false, true);
    }

    /**
     * Format date
     * @param string $timestamp
     * @param string $format
     *
     * @return string
     */
    public static function format_date($timestamp, $format = null)
    {
        return api_format_date($timestamp, $format);
    }

    /**
     * @param string $help_input
     */
    public function setHelp($help_input = null)
    {
        if (!empty($help_input)) {
            $help = $help_input;
        } else {
            $help = $this->help;
        }
        $this->assign('help_content', $help);
    }

    /**
     * Use template system to parse the actions menu
     * @todo finish it!
     * @param array $actions
     *
     **/
    public function setActions($actions)
    {
        $action_string = '';
        if (!empty($actions)) {
            foreach ($actions as $action) {
                $action_string .= $action;
            }
        }
        $this->assign('actions', $actions);
    }

    /**
     * Shortcut to display a 1 col layout (index.php)
     * */
    public function display_one_col_template()
    {
        $tpl = $this->get_template('layout/layout_1_col.tpl');
        $this->display($tpl);
    }

    /**
     * Shortcut to display a 2 col layout (userportal.php)
     * */
    public function display_two_col_template()
    {
        $tpl = $this->get_template('layout/layout_2_col.tpl');
        $this->display($tpl);
    }

    /**
     * Displays an empty template
     */
    public function display_blank_template()
    {
        $tpl = $this->get_template('layout/blank.tpl');
        $this->display($tpl);
    }

    /**
     * Displays an empty template
     */
    public function display_no_layout_template()
    {
        $tpl = $this->get_template('layout/no_layout.tpl');
        $this->display($tpl);
    }

    /**
     * Sets the footer visibility
     * @param bool true if we show the footer
     */
    public function setFooter($status)
    {
        $this->show_footer = $status;
        $this->assign('show_footer', $status);
    }

    /**
     * Sets the header visibility
     * @param bool true if we show the header
     */
    public function setHeader($status)
    {
        $this->show_header = $status;
        $this->assign('show_header', $status);

        //Toolbar
        $show_admin_toolbar = api_get_setting('show_admin_toolbar');
        $show_toolbar = 0;

        switch ($show_admin_toolbar) {
            case 'do_not_show':
                break;
            case 'show_to_admin':
                if (api_is_platform_admin()) {
                    $show_toolbar = 1;
                }
                break;
            case 'show_to_admin_and_teachers':
                if (api_is_platform_admin() || api_is_allowed_to_edit()) {
                    $show_toolbar = 1;
                }
                break;
            case 'show_to_all':
                $show_toolbar = 1;
                break;
        }
        $this->assign('show_toolbar', $show_toolbar);

        //Only if course is available
        $show_course_shortcut = null;
        $show_course_navigation_menu = null;

        if (!empty($this->course_id) && $this->user_is_logged_in) {
            if (api_get_setting('show_toolshortcuts') != 'false') {
                //Course toolbar
                $show_course_shortcut = CourseHome::show_navigation_tool_shortcuts();
            }
            if (api_get_setting('show_navigation_menu') != 'false') {
                //Course toolbar
                $show_course_navigation_menu = CourseHome::show_navigation_menu();
            }
        }
        $this->assign('show_course_shortcut', $show_course_shortcut);
        $this->assign('show_course_navigation_menu', $show_course_navigation_menu);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getTemplate($name)
    {
        return $this->app['template_style'].'/'.$name;
    }

    /**
     * @deprecated use getTemplate
     */
    public function get_template($name)
    {
        return $this->getTemplate($name);
    }

    /**
     * Set course parameters
     */
    private function setCourseParameters()
    {
        //Setting course id
        $this->course_id = api_get_course_int_id();
        $this->app['course_code'] = api_get_course_id();
        $this->app['session_id'] = api_get_session_id();
    }

    /**
     * Set user parameters
     */
    private function setUserParameters()
    {
        $user_info = array();
        $user_info['logged'] = 0;
        $this->user_is_logged_in = false;
        $user_info = isset($this->app['current_user']) ? $this->app['current_user'] : null;

        if (api_user_is_login() && !empty($user_info)) {

            $user_info['logged'] = 1;
            $user_info['is_admin'] = 0;
            if (api_is_platform_admin()) {
                $user_info['is_admin'] = 1;
            }

            $new_messages = MessageManager::get_new_messages();
            $user_info['messages_count'] = $new_messages != 0 ? Display::label($new_messages, 'warning') : null;
            $this->user_is_logged_in = true;
        }

        //Setting the $_u array that could be use in any template
        $this->assign('_u', $user_info);
    }

    /**
     * Set system parameters
     */
    private function setSystemParameters()
    {
        $version = $this->app['configuration']['system_version'];

        // Setting app paths/URLs.
        $_p = array(
            'web' => api_get_path(WEB_PATH),
            'web_course' => api_get_path(WEB_COURSE_PATH),
            'web_course_path' => api_get_path(WEB_COURSE_PATH),
            'web_code_path' => api_get_path(WEB_CODE_PATH),
            'web_main' => api_get_path(WEB_CODE_PATH),
            'web_css' => api_get_path(WEB_CSS_PATH),
            'web_css_path' => api_get_path(WEB_CSS_PATH),
            'web_ajax' => api_get_path(WEB_AJAX_PATH),
            'web_ajax_path' => api_get_path(WEB_AJAX_PATH),
            'web_img' => api_get_path(WEB_IMG_PATH),
            'web_img_path' => api_get_path(WEB_IMG_PATH),
            'web_plugin' => api_get_path(WEB_PLUGIN_PATH),
            'web_plugin_path' => api_get_path(WEB_PLUGIN_PATH),
            'web_lib' => api_get_path(WEB_LIBRARY_PATH),
            'web_library_path' => api_get_path(WEB_LIBRARY_PATH),
            'public_web' => api_get_path(WEB_PUBLIC_PATH)
        );

        $this->assign('_p', $_p);

        //Here we can add system parameters that can be use in any template
        $_s = array(
            'software_name' => api_get_software_name(),
            'system_version' => $version,
            'site_name' => api_get_setting('siteName'),
            'institution' => api_get_setting('Institution')
        );
        $this->assign('_s', $_s);
    }

    /**
     * Set theme, include CSS files
     */
    private function setCssFiles()
    {
        global $disable_js_and_css_files;
        $css = array();

        $this->theme = api_get_visual_theme();
        if (isset($_POST['style']) && api_is_platform_admin()) {
            $this->preview_theme = $_POST['style'];
        }
        if (!empty($this->preview_theme)) {
            $this->theme = $this->preview_theme;
        }

        $this->app['theme'] = $this->theme;

        $cssPath = api_get_path(WEB_CSS_PATH);

        // Loads only 1 css file
        if ($this->app['assetic.enabled']) {
            $css[] = api_get_path(WEB_PUBLIC_PATH).'css/'.$this->theme.'/style.css';
        } else {
            // Bootstrap
            $css[] = api_get_cdn_path(api_get_path(WEB_LIBRARY_PATH).'javascript/bootstrap/css/bootstrap.css');

            //$css[] = api_get_cdn_path(api_get_path(WEB_LIBRARY_PATH).'javascript/bootstrap/css/bootstrap-theme.css');

            // Base CSS.
            $css[] = api_get_cdn_path($cssPath.'base.css');

            // Default theme CSS.
            $css[] = api_get_cdn_path($cssPath.$this->theme.'/default.css');

            // Extra CSS files.
            if ($this->show_learnpath) {
                //$css[] = $cssPath.$this->theme.'/learnpath.css';
                //$css[] = $cssPath.$this->theme.'/scorm.css';
            }

            if (api_is_global_chat_enabled()) {
                $css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/chat/css/chat.css';
            }

            $css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/css/'.$this->jquery_ui_theme.'/jquery-ui-custom.css';
            //$css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/default.css';
        }

        $css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/font-awesome/css/font-awesome.css';
        $css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css';
        $css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/chosen/chosen.css';

        $css_file_to_string = null;
        foreach ($css as $file) {
            $css_file_to_string .= api_get_css($file);
        }

        // @todo move this somewhere else. Special fix when using tablets in order to see the text near icons
        if (SHOW_TEXT_NEAR_ICONS == true) {
            //hack in order to fix the actions buttons
            $css_file_to_string .= '<style>
                .td_actions a {
                    float:left;
                    width:100%;
                }
                .forum_message_left a {
                    float:left;
                    width:100%;
                }
                </style>';
        }

        $navigator_info = api_get_navigator();
        if ($navigator_info['name'] == 'Internet Explorer' && $navigator_info['version'] == '6') {
            $css_file_to_string .= 'img, div { behavior: url('.api_get_path(
                WEB_LIBRARY_PATH
            ).'javascript/iepngfix/iepngfix.htc) } '."\n";
        }

        if (!$disable_js_and_css_files) {
            $this->assign('css_file_to_string', $css_file_to_string);

            $style_print = api_get_css(api_get_cdn_path($cssPath.$this->theme.'/print.css'), 'print');
            $this->assign('css_style_print', $style_print);
        }
    }

    /**
     * @param array $htmlHeadXtra
     */
    public function addJsFiles($htmlHeadXtra = array())
    {
        $extra_headers = null;
        if (isset($htmlHeadXtra) && $htmlHeadXtra) {
            foreach ($htmlHeadXtra as $this_html_head) {
                $extra_headers .= $this_html_head."\n";
            }
        }

        if (isset($this->app['extraJS'])) {
            foreach ($this->app['extraJS'] as $this_html_head) {
                $extra_headers .= $this_html_head."\n";
            }
        }
        $this->assign('extra_headers', $extra_headers);
    }

    /**
     * Sets JS files
     */
    private function setJsFiles()
    {
        global $disable_js_and_css_files, $htmlHeadXtra;

        $jsFolder = api_get_path(WEB_LIBRARY_PATH).'javascript/';

        if ($this->app['assetic.enabled']) {
            $js_files = array(
                api_get_path(WEB_PATH).'web/js/script.js',
                $jsFolder.'chosen/chosen.jquery.min.js',
                $jsFolder.'thickbox.js',
                $jsFolder.'ckeditor/ckeditor.js',
            );
        } else {
            //JS files
            $js_files = array(
                $jsFolder.'modernizr.js',
                $jsFolder.'jquery.js',
                $jsFolder.'chosen/chosen.jquery.min.js',
                $jsFolder.'jquery-ui/js/jquery-ui.custom.js',
                $jsFolder.'thickbox.js',

                $jsFolder.'bootstrap/js/bootstrap.js',
            );
        }

        $this->app['html_editor']->getJavascriptToInclude($js_files);

        if (api_is_global_chat_enabled()) {
            //Do not include the global chat in LP
            if ($this->show_learnpath == false && $this->show_footer == true && $this->app['template.hide_global_chat'] == false) {
                $js_files[] = $jsFolder.'chat/js/chat.js';
            }
        }

        if (api_get_setting('accessibility_font_resize') == 'true') {
            $js_files[] = $jsFolder.'fontresize.js';
        }

        if (api_get_setting('include_asciimathml_script') == 'true') {
            $js_files[] = $jsFolder.'asciimath/ASCIIMathML.js';
        }

        if (api_get_setting('disable_copy_paste') == 'true') {
            $js_files[] = $jsFolder.'jquery.nocutcopypaste.js';
        }

        $js_file_to_string = null;

        foreach ($js_files as $js_file) {
            $js_file_to_string .= api_get_js_simple($js_file);
        }

        // Loading email_editor js.
        if (!api_is_anonymous() && api_get_setting('allow_email_editor') == 'true') {
            $js_file_to_string .= $this->fetch($this->app['template_style'].'/mail_editor/email_link.js.tpl');
        }

        if (!$disable_js_and_css_files) {
            $this->assign('js_file_to_string', $js_file_to_string);

            $extra_headers = null;
            if (isset($htmlHeadXtra) && $htmlHeadXtra) {
                foreach ($htmlHeadXtra as $this_html_head) {
                    $extra_headers .= $this_html_head."\n";
                }
            }

            if (isset($this->app['extraJS'])) {
                foreach ($this->app['extraJS'] as $this_html_head) {
                    $extra_headers .= $this_html_head."\n";
                }
            }
            $this->assign('extra_headers', $extra_headers);
        }
    }

    /**
     * Set header parameters
     */
    private function setHeaderParameters()
    {
        $_course = api_get_course_info();
        $_configuration = $this->app['configuration'];
        $this_section = $this->app['this_section'];

        $nameTools = $this->title;
        $navigation = $this->navigation_array;

        $this->menu_navigation = $navigation['menu_navigation'];

        $this->assign('system_charset', api_get_system_encoding());
        $this->assign('online_button', Display::return_icon('online.png'));
        $this->assign('offline_button', Display::return_icon('offline.png'));

        // Get language iso-code for this page - ignore errors
        $this->assign('document_language', $this->translator->getLocale());

        $course_title = isset($_course['name']) ? $_course['name'] : null;

        $title_list = array();

        $title_list[] = api_get_setting('Institution');
        $title_list[] = api_get_setting('siteName');

        if (!empty($course_title)) {
            $title_list[] = $course_title;
        }
        if ($nameTools != '') {
            $title_list[] = $nameTools;
        }

        $title_string = '';
        for ($i = 0; $i < count($title_list); $i++) {
            $title_string .= $title_list[$i];
            if (isset($title_list[$i + 1])) {
                $item = trim($title_list[$i + 1]);
                if (!empty($item)) {
                    $title_string .= ' - ';
                }
            }
        }

        $this->assign('title_string', $title_string);

        //Setting the theme and CSS files
        $this->setCssFiles();
        $this->setJsFiles();

        // Implementation of prefetch.
        // See http://cdn.chamilo.org/main/img/online.png for details
        $prefetch = '';
        if (!empty($_configuration['cdn_enable'])) {
            $prefetch .= '<meta http-equiv="x-dns-prefetch-control" content="on">';
            foreach ($_configuration['cdn'] as $host => $exts) {
                $prefetch .= '<link rel="dns-prefetch" href="'.$host.'">';
            }
        }

        $this->assign('prefetch', $prefetch);
        $this->assign('text_direction', api_get_text_direction());
        $this->assign('section_name', 'section-'.$this_section);

        $favico = '<link rel="shortcut icon" href="'.api_get_path(WEB_PATH).'favicon.ico" type="image/x-icon" />';

        if (isset($_configuration['multiple_access_urls']) && $_configuration['multiple_access_urls']) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $url_info = api_get_current_access_url_info();
                $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
                $clean_url = api_replace_dangerous_char($url);
                $clean_url = str_replace('/', '-', $clean_url);
                $clean_url .= '/';
                $homep = api_get_path(REL_PATH).'home/'.$clean_url; //homep for Home Path
                $icon_real_homep = api_get_path(SYS_PATH).'home/'.$clean_url;

                //we create the new dir for the new sites
                if (is_file($icon_real_homep.'favicon.ico')) {
                    $favico = '<link rel="shortcut icon" href="'.$homep.'favicon.ico" type="image/x-icon" />';
                }
            }
        }

        $this->assign('favico', $favico);

        $this->setHelp();

        $notification = $this->returnNotificationMenu();
        $this->assign('notification_menu', $notification);

        // Preparing values for the menu

        // Profile link.

        $this->assign('is_profile_editable', api_is_profile_readable());

        $profile_link = null;
        if (api_get_setting('allow_social_tool') == 'true') {
            $profile_link = '<a href="'.api_get_path(WEB_CODE_PATH).'social/home.php">'.get_lang('Profile').'</a>';
        } else {
            if (api_is_profile_readable()) {
                $profile_link = '<a href="'.api_get_path(WEB_CODE_PATH).'auth/profile.php">'.get_lang('Profile').'</a>';
            }
        }
        $this->assign('profile_link', $profile_link);

        // Message link.
        $message_link = null;
        if (api_get_setting('allow_message_tool') == 'true') {
            $message_link = '<a href="'.api_get_path(WEB_CODE_PATH).'messages/inbox.php">'.get_lang('Inbox').'</a>';
        }
        $this->assign('message_link', $message_link);

        $institution = api_get_setting('Institution');
        $portal_name = empty($institution) ? api_get_setting('siteName') : $institution;

        $this->assign('portal_name', $portal_name);

        // Menu.
        $menu = $this->returnMenu();

        $this->assign('menu', $menu);

        // Breadcrumb
        if ($this->loadBreadcrumb) {
            $this->loadBreadcrumbToTemplate();
        }

        // Extra content
        $extra_header = null;
        if (!api_is_platform_admin()) {
            $extra_header = trim(api_get_setting('header_extra_content'));
        }
        $this->assign('header_extra_content', $extra_header);
    }

    /**
     *
     */
    public function loadBreadcrumbToTemplate()
    {
        if (api_get_setting('breadcrumb_navigation_display') == 'false') {
            return;
        }
        $breadcrumb = $this->returnBreadcrumb();
        $this->assign('breadcrumb', $breadcrumb);
    }

    /**
     * Set footer parameters
     */
    private function setFooterParameters()
    {
        //Show admin data
        //$this->assign('show_administrator_data', api_get_setting('show_administrator_data'));

        if (api_get_setting('show_administrator_data') == 'true') {
            //Administrator name
            $administrator_data = get_lang('Manager').' : '.Display::encrypted_mailto_link(
                api_get_setting('emailAdministrator'),
                api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))
            );
            $this->assign('administrator_name', $administrator_data);
        }

        //Loading footer extra content
        if (!api_is_platform_admin()) {
            $extra_footer = trim(api_get_setting('footer_extra_content'));
            if (!empty($extra_footer)) {
                $this->assign('footer_extra_content', $extra_footer);
            }
        }

        $courseId = api_get_course_int_id();

        //Tutor name
        if (api_get_setting('show_tutor_data') == 'true') {
            // Course manager
            $id_session = api_get_session_id();
            if (isset($courseId) && $courseId != -1 && !empty($courseId)) {
                $tutor_data = '';
                if ($id_session != 0) {
                    $coachs_email = CourseManager::get_email_of_tutor_to_session($id_session, $courseId);
                    $email_link = array();
                    foreach ($coachs_email as $coach) {
                        $email_link[] = Display::encrypted_mailto_link($coach['email'], $coach['complete_name']);
                    }
                    if (count($coachs_email) > 1) {
                        $tutor_data .= get_lang('Coachs').' : ';
                        $tutor_data .= ArrayClass::array_to_string($email_link, CourseManager::USER_SEPARATOR);
                    } elseif (count($coachs_email) == 1) {
                        $tutor_data .= get_lang('Coach').' : ';
                        $tutor_data .= ArrayClass::array_to_string($email_link, CourseManager::USER_SEPARATOR);
                    } elseif (count($coachs_email) == 0) {
                        $tutor_data .= '';
                    }
                }
                $this->assign('session_teachers', $tutor_data);
            }
        }

        if (api_get_setting('show_teacher_data') == 'true') {
            // course manager
            if (isset($courseId) && $courseId != -1 && !empty($courseId)) {
                $courseInfo = api_get_course_info();
                $teacher_data = null;
                $label = get_lang('Teacher');
                if (count($courseInfo['teacher_list']) > 1) {
                    $label = get_lang('Teachers');
                }
                $teacher_data .= $label.' : '.$courseInfo['teacher_list_formatted'];
                $this->assign('teachers', $teacher_data);
            }
        }
    }

    public function manageDisplay($content)
    {
        //$this->assign('content', $content);
    }

    /**
     * Sets the plugin content in a template variable
     * @param string
     */
    private function set_plugin_region($plugin_region)
    {
        if (!empty($plugin_region)) {
            $region_content = $this->plugin->load_region($this->app['plugins'], $plugin_region, $this, $this->force_plugin_load);
            if (!empty($region_content)) {
                $this->assign('plugin_'.$plugin_region, $region_content);
            } else {
                $this->assign('plugin_'.$plugin_region, null);
            }
        }
    }

    /**
     * @param string $template
     * @return mixed
     */
    public function fetch($template = null)
    {
        $template = $this->app['twig']->loadTemplate($template);
        return $template->render(array());
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function assign($key, $value = null)
    {
        if ($this->app['allowed'] == true) {
            $this->app['twig']->addGlobal($key, $value);
        }
    }

    /**
     * @param string $template
     */
    public function display($template = null)
    {
        if (!empty($template)) {
            $this->app['default_layout'] = $template;
        }
    }

    /**
     * @return null|string
     */
    public function returnMenu()
    {
        $navigation = $this->navigation_array;
        $navigation = $navigation['navigation'];

        // Displaying the tabs
        $lang = api_get_user_language();

        // Preparing home folder for multiple urls

        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $url_info = api_get_current_access_url_info();
                $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
                $clean_url = api_replace_dangerous_char($url);
                $clean_url = str_replace('/', '-', $clean_url);
                $clean_url .= '/';
                $homep = api_get_path(SYS_DATA_PATH).'home/'.$clean_url; //homep for Home Path
                //we create the new dir for the new sites
                if (!is_dir($homep)) {
                    mkdir($homep, api_get_permissions_for_new_directories());
                }
            }
        } else {
            $homep = api_get_path(SYS_PATH).'home/';
        }

        $ext = '.html';
        $menutabs = 'home_tabs';
        $home_top = '';

        if (is_file($homep.$menutabs.'_'.$lang.$ext) && is_readable($homep.$menutabs.'_'.$lang.$ext)) {
            $home_top = @(string)file_get_contents($homep.$menutabs.'_'.$lang.$ext);
        } elseif (is_file($homep.$menutabs.$lang.$ext) && is_readable($homep.$menutabs.$lang.$ext)) {
            $home_top = @(string)file_get_contents($homep.$menutabs.$lang.$ext);
        } else {
            //$errorMsg = get_lang('HomePageFilesNotReadable');
        }

        $home_top = api_to_system_encoding($home_top, api_detect_encoding(strip_tags($home_top)));

        $open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top);
        $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));

        $lis = '';

        if (!empty($open)) {
            if (strpos($open, 'show_menu') === false) {
                if (api_is_anonymous()) {
                    $navigation[SECTION_CAMPUS] = null;
                }
            } else {
                //$lis .= Display::tag('li', $open);
                $lis .= $open;
            }
        }

        if (count($navigation) > 0 || !empty($lis)) {
            $pre_lis = '';
            foreach ($navigation as $section => $navigation_info) {
                if (isset($GLOBALS['this_section'])) {
                    $current = $section == $GLOBALS['this_section'] ? ' id="current" class="active" ' : '';
                } else {
                    $current = '';
                }
                if (!empty($navigation_info['title'])) {
                    $pre_lis .= '<li'.$current.' ><a  href="'.$navigation_info['url'].'" target="_top">'.$navigation_info['title'].'</a></li>';
                }
            }
            $lis = $pre_lis.$lis;
        }

        $menu = null;
        if (!empty($lis)) {
            $menu .= $lis;
        }
        return $menu;
    }

    /**
     * @return string
     */
    public function getNavigationLinks()
    {
        // Deleting the my profile link.
        if (api_get_setting('allow_social_tool') == 'true') {
            unset($this->menu_navigation['myprofile']);
        }
        return $this->menu_navigation;
    }

    /**
     * @param string $layout
     * @return mixed
     */
    public function renderLayout($layout = null)
    {
        if (empty($layout)) {
            $layout = $this->app['default_layout'];
        }
        $this->addJsFiles();
        return $this->app['twig']->render($this->app['template_style'].'/layout/'.$layout);
    }

    /**
     * @param string $layout
     * @deprecated use renderLayout
     * @return mixed
     */
    public function render_layout($layout = null)
    {
        return $this->renderLayout($layout);
    }

    /**
     * @param string $template
     * @param array $elements
     * @deprecated use renderTemplate
     * @return mixed
     */
    public function render_template($template, $elements = array())
    {
        return $this->renderTemplate($template, $elements);
    }

    /**
     * @param string $template
     * @param array $elements
     * @return mixed
     */
    public function renderTemplate($template, $elements = array())
    {
        $this->addJsFiles();
        return $this->app['twig']->render($this->app['template_style'].'/'.$template, $elements);
    }

    /**
     * Determines the possible tabs (=sections) that are available.
     * This function is used when creating the tabs in the third header line and
     * all the sections that do not appear there (as determined by the
     * platform admin on the Chamilo configuration settings page)
     * will appear in the right hand menu that appears on several other pages
     * @return array containing all the possible tabs
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     */
    public function getTabs()
    {
        $_course = api_get_course_info();
        $navigation = array();

        // Campus Homepage
        $navigation[SECTION_CAMPUS]['url'] = api_get_path(WEB_PUBLIC_PATH).'index';
        $navigation[SECTION_CAMPUS]['title'] = get_lang('CampusHomepage');

        // My Courses
        $navigation['mycourses']['url'] = api_get_path(WEB_PUBLIC_PATH).'userportal';
        $navigation['mycourses']['title'] = get_lang('MyCourses');

        // My Profile
        if (api_is_profile_readable()) {
            $navigation['myprofile']['url'] = api_get_path(WEB_CODE_PATH).'auth/profile.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '');
            $navigation['myprofile']['title'] = get_lang('ModifyProfile');
        }

        // Link to my agenda
        $navigation['myagenda']['url'] = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?type=personal';
        $navigation['myagenda']['title'] = get_lang('MyAgenda');

        // Gradebook
        if (api_get_setting('gradebook_enable') == 'true') {
            $navigation['mygradebook']['url'] = api_get_path(
                WEB_CODE_PATH
            ).'gradebook/gradebook.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '');
            $navigation['mygradebook']['title'] = get_lang('MyGradebook');
        }

        // Reporting
        if (api_is_allowed_to_create_course() || api_is_drh() || api_is_session_admin()) {
            // Link to my space
            $navigation['session_my_space']['url'] = api_get_path(WEB_CODE_PATH).'mySpace/index.php';
            $navigation['session_my_space']['title'] = get_lang('MySpace');
        } else {
            // Link to my progress
            $navigation['session_my_progress']['url'] = api_get_path(WEB_CODE_PATH).'auth/my_progress.php';
            $navigation['session_my_progress']['title'] = get_lang('MyProgress');
        }

        // Social
        if (api_get_setting('allow_social_tool') == 'true') {
            $navigation['social']['url'] = api_get_path(WEB_CODE_PATH).'social/home.php';
            $navigation['social']['title'] = get_lang('SocialNetwork');
        }

        // Dashboard
        if (api_is_platform_admin() || api_is_drh() || api_is_session_admin()) {
            $navigation['dashboard']['url'] = api_get_path(WEB_CODE_PATH).'dashboard/index.php';
            $navigation['dashboard']['title'] = get_lang('Dashboard');
        }

        // Custom tabs
        for ($i = 1; $i <= 3; $i++) {
            if (api_get_setting('custom_tab_'.$i.'_name') && api_get_setting('custom_tab_'.$i.'_url')) {
                $navigation['custom_tab_'.$i]['url'] = api_get_setting('custom_tab_'.$i.'_url');
                $navigation['custom_tab_'.$i]['title'] = api_get_setting('custom_tab_'.$i.'_name');
            }
        }

        // Adding block settings for each role
        if (isset($this->app['allow_admin_toolbar'])) {
            $roleTemplate = array();
            foreach ($this->app['allow_admin_toolbar'] as $role) {
                if ($this->security->getToken() && $this->security->isGranted($role)) {
                    // Fixes in order to match the templates
                    if ($role == 'ROLE_ADMIN') {
                        $role = 'administrator';
                    }
                    if ($role == 'ROLE_QUESTION_MANAGER') {
                        $role = 'QUESTIONMANAGER';
                    }
                    $stripRole = strtolower(str_replace('ROLE_', '', $role));
                    $roleTemplate[] = $stripRole;
                }
            }

            if (!empty($roleTemplate)) {
                if (api_get_setting('show_tabs', 'platform_administration') == 'true') {
                    $navigation['admin']['url'] = api_get_path(WEB_PUBLIC_PATH).'admin';
                    $navigation['admin']['title'] = get_lang('PlatformAdmin');
                }
            }
            $this->app['admin_toolbar_roles'] = $roleTemplate;
        }
        return $navigation;
    }

    /**
     * @param string $theme
     * @deprecated the logo is wrote in the main_header.tpl file
     * @return string
     */
    public function returnLogo($theme)
    {
        $_course = api_get_course_info();
        $html = '';
        $logo = api_get_path(SYS_CODE_PATH).'css/'.$theme.'/images/header-logo.png';

        $site_name = api_get_setting('siteName');
        if (file_exists($logo)) {
            $site_name = api_get_setting('Institution').' - '.$site_name;
            $html .= '<div id="logo">';
            $image_url = api_get_path(WEB_CSS_PATH).$theme.'/images/header-logo.png';
            $logo = Display::img($image_url, $site_name, array('title' => $site_name));
            $html .= Display::url($logo, api_get_path(WEB_PATH).'index.php');
            $html .= '</div>';
        } else {
            $html .= '<a href="'.api_get_path(WEB_PATH).'index.php" target="_top">'.$site_name.'</a>';
            $iurl = api_get_setting('InstitutionUrl');
            $iname = api_get_setting('Institution');

            if (!empty($iname)) {
                $html .= '-&nbsp;<a href="'.$iurl.'" target="_top">'.$iname.'</a>';
            }
            // External link section a.k.a Department - Department URL
            if (isset($_course['extLink']) && $_course['extLink']['name'] != '') {
                $html .= '<span class="extLinkSeparator"> - </span>';
                if ($_course['extLink']['url'] != '') {
                    $html .= '<a class="extLink" href="'.$_course['extLink']['url'].'" target="_top">';
                    $html .= $_course['extLink']['name'];
                    $html .= '</a>';
                } else {
                    $html .= $_course['extLink']['name'];
                }
            }
        }

        return $html;
    }

    /**
     * @return string
     */
    public function returnNotificationMenu()
    {
        $_course = api_get_course_info();
        $course_id = api_get_course_id();
        $user_id = api_get_user_id();

        $html = '';

        if ((api_get_setting('showonline', 'world') == 'true' AND !$user_id) OR
            (api_get_setting('showonline', 'users') == 'true' AND $user_id) OR
            (api_get_setting('showonline', 'course') == 'true' AND $user_id AND $course_id)
        ) {
            $number = Online::who_is_online_count(api_get_setting('time_limit_whosonline'));

            $number_online_in_course = 0;
            if (!empty($_course['id'])) {
                $number_online_in_course = Online::who_is_online_in_this_course_count(
                    $user_id,
                    api_get_setting('time_limit_whosonline'),
                    $_course['id']
                );
            }

            // Display the who's online of the platform
            if ($number) {
                if ((api_get_setting('showonline', 'world') == 'true' AND !$user_id) OR
                    (api_get_setting('showonline', 'users' ) == 'true' AND $user_id)
                ) {
                    $html .= '<li><a href="'.SocialManager::getUserOnlineLink().'" target="_top" title="'.get_lang(
                        'UsersOnline'
                    ).'" >'.
                        Display::return_icon(
                            'user.png',
                            get_lang('UsersOnline'),
                            array(),
                            ICON_SIZE_TINY
                        ).' '.$number.'</a></li>';
                }
            }

            // Display the who's online for the course
            if ($number_online_in_course) {
                if (is_array($_course) AND
                    api_get_setting('showonline', 'course' ) == 'true' AND
                    isset($_course['sysCode'])
                ) {
                    $html .= '<li><a href="'.SocialManager::getUserOnlineLink($_course['sysCode']).'" target="_top">'.
                        Display::return_icon(
                            'course.png',
                            get_lang('UsersOnline').' '.get_lang('InThisCourse'),
                            array(),
                            ICON_SIZE_TINY
                        ).' '.$number_online_in_course.' </a></li>';
                }
            }

            // Display the who's online for the session
            if (api_get_setting('showonline', 'session') == 'true') {
                if (isset($user_id) && api_get_session_id() != 0) {
                    if (api_is_allowed_to_edit()) {
                        $html .= '<li><a href="'.SocialManager::getUserOnlineLink(null, api_get_session_id()).'&id_coach='.$user_id.'" >'.
                            Display::return_icon(
                                'session.png',
                                get_lang('UsersConnectedToMySessions'),
                                array(),
                                ICON_SIZE_TINY
                            ).' </a></li>';
                    }
                }
            }
        }

        if (api_get_setting('accessibility_font_resize') == 'true') {
            $html .= '<li class="resize_font">';
            $html .= '<span class="decrease_font" title="'.get_lang(
                'DecreaseFontSize'
            ).'">A</span> <span class="reset_font" title="'.get_lang(
                'ResetFontSize'
            ).'">A</span> <span class="increase_font" title="'.get_lang('IncreaseFontSize').'">A</span>';
            $html .= '</li>';
        }
        return $html;
    }

    /**
     * Gets the main menu
     *
     * @return array
     */
    public function returnNavigationArray()
    {
        $navigation = array();
        $menu_navigation = array();
        $possible_tabs = $this->getTabs();

        // Campus Homepage
        if (api_get_setting('show_tabs', 'campus_homepage') == 'true') {
            $navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
        } else {
            $menu_navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
        }

        if (api_get_user_id() && !api_is_anonymous()) {
            // My Courses
            if (api_get_setting('show_tabs', 'my_courses') == 'true') {
                $navigation['mycourses'] = $possible_tabs['mycourses'];
            } else {
                $menu_navigation['mycourses'] = $possible_tabs['mycourses'];
            }

            // My Profile
            if (api_get_setting('show_tabs', 'my_profile') == 'true' && api_get_setting('allow_social_tool') != 'true') {
                if (isset($possible_tabs['myprofile'])) {
                    $navigation['myprofile'] = $possible_tabs['myprofile'];
                }
            } else {
                if (isset($possible_tabs['myprofile'])) {
                    $menu_navigation['myprofile'] = $possible_tabs['myprofile'];
                }
            }

            // My Agenda
            if (api_get_setting('show_tabs', 'my_agenda') == 'true') {
                $navigation['myagenda'] = $possible_tabs['myagenda'];
            } else {
                $menu_navigation['myagenda'] = $possible_tabs['myagenda'];
            }

            // Gradebook
            if (api_get_setting('gradebook_enable') == 'true') {
                if (api_get_setting('show_tabs', 'my_gradebook') == 'true') {
                    $navigation['mygradebook'] = $possible_tabs['mygradebook'];
                } else {
                    $menu_navigation['mygradebook'] = $possible_tabs['mygradebook'];
                }
            }

            // Reporting
            if (api_get_setting('show_tabs', 'reporting') == 'true') {
                if (api_is_allowed_to_create_course() || api_is_drh() || api_is_session_admin()) {
                    $navigation['session_my_space'] = $possible_tabs['session_my_space'];
                } else {
                    $navigation['session_my_space'] = $possible_tabs['session_my_progress'];
                }
            } else {
                if (api_is_allowed_to_create_course() || api_is_drh() || api_is_session_admin()) {
                    $menu_navigation['session_my_space'] = $possible_tabs['session_my_space'];
                } else {
                    $menu_navigation['session_my_space'] = $possible_tabs['session_my_progress'];
                }
            }

            // Social Networking
            if (api_get_setting('show_tabs', 'social') == 'true') {
                if (api_get_setting('allow_social_tool') == 'true') {
                    $navigation['social'] = $possible_tabs['social'];
                }
            } else {
                $menu_navigation['social'] = isset($possible_tabs['social']) ? $possible_tabs['social'] : null;
            }

            // Dashboard
            if (api_get_setting('show_tabs', 'dashboard') == 'true') {
                if (api_is_platform_admin() || api_is_drh() || api_is_session_admin()) {
                    $navigation['dashboard'] = $possible_tabs['dashboard'];
                }
            } else {
                $menu_navigation['dashboard'] = isset($possible_tabs['dashboard']) ? $possible_tabs['dashboard'] : null;
            }

            if (isset($possible_tabs['admin'])) {
                $navigation['platform_admin'] = $possible_tabs['admin'];
                $navigation['platform_admin'] = $possible_tabs['admin'];
            }

            // Reports
            if (!empty($possible_tabs['reports'])) {
                if (api_get_setting('show_tabs', 'reports') == 'true') {
                    if ((api_is_platform_admin() || api_is_drh() || api_is_session_admin()) && Rights::hasRight(
                        'show_tabs:reports'
                    )
                    ) {
                        $navigation['reports'] = $possible_tabs['reports'];
                    }
                } else {
                    $menu_navigation['reports'] = $possible_tabs['reports'];
                }
            }

            // Custom tabs
            for ($i = 1; $i <= 3; $i++) {
                if (api_get_setting('show_tabs', 'custom_tab_'.$i) == 'true') {
                    if (isset($possible_tabs['custom_tab_'.$i])) {
                        $navigation['custom_tab_'.$i] = $possible_tabs['custom_tab_'.$i];
                    }
                } else {
                    if (isset($possible_tabs['custom_tab_'.$i])) {
                        $menu_navigation['custom_tab_'.$i] = $possible_tabs['custom_tab_'.$i];
                    }
                }
            }
        }

        $return = array(
            'menu_navigation' => $menu_navigation,
            'navigation' => $navigation,
            'possible_tabs' => $possible_tabs
        );

        return $return;
    }

    /**
     * Return breadcrumb
     * @return string
     */
    public function returnBreadcrumb()
    {
        $interbreadcrumb = $this->app['breadcrumb'];

        $session_id = api_get_session_id();
        $session_name = api_get_session_name($session_id);
        $_course = api_get_course_info();
        $user_id = api_get_user_id();
        $course_id = api_get_course_id();

        /*  Plugins for banner section */
        $web_course_path = api_get_path(WEB_COURSE_PATH);

        /* If the user is a coach he can see the users who are logged in its session */
        $navigation = array();

        // part 1: Course Homepage. If we are in a course then the first breadcrumb is a link to the course homepage
        // hide_course_breadcrumb the parameter has been added to hide the name of the course, that appeared in the default $interbreadcrumb
        $session_name = Text::cut($session_name, MAX_LENGTH_BREADCRUMB);
        $my_session_name = is_null($session_name) ? '' : '&nbsp;('.$session_name.')';

        if (!empty($_course) && !isset($_GET['hide_course_breadcrumb'])) {

            $navigation_item['url'] = $web_course_path.$_course['path'].'/index.php'.(!empty($session_id) ? '?id_session='.$session_id : '');
            $course_title = Text::cut($_course['name'], MAX_LENGTH_BREADCRUMB);

            switch (api_get_setting('breadcrumbs_course_homepage')) {
                case 'get_lang':
                    $navigation_item['title'] = Display::img(
                        api_get_path(WEB_CSS_PATH).'home.png',
                        get_lang('CourseHomepageLink')
                    ).' '.get_lang('CourseHomepageLink');
                    break;
                case 'course_code':
                    $navigation_item['title'] = Display::img(
                        api_get_path(WEB_CSS_PATH).'home.png',
                        $_course['official_code']
                    ).' '.$_course['official_code'];
                    break;
                case 'session_name_and_course_title':
                    $navigation_item['title'] = Display::img(
                        api_get_path(WEB_CSS_PATH).'home.png',
                        $_course['name'].$my_session_name
                    ).' '.$course_title.$my_session_name;
                    break;
                default:
                    if (api_get_session_id() != -1) {
                        $navigation_item['title'] = Display::img(
                            api_get_path(WEB_CSS_PATH).'home.png',
                            $_course['name'].$my_session_name
                        ).' '.$course_title.$my_session_name;
                    } else {
                        $navigation_item['title'] = Display::img(
                            api_get_path(WEB_CSS_PATH).'home.png',
                            $_course['name']
                        ).' '.$course_title;
                    }
                    break;
            }
            $navigation[] = $navigation_item;
        }

        // Part 2: breadcrumbs.
        // If there is an array $interbreadcrumb defined then these have to appear before the last breadcrumb
        // (which is the tool itself)
        if (isset($interbreadcrumb) && is_array($interbreadcrumb)) {
            foreach ($interbreadcrumb as $breadcrumb_step) {
                if ($breadcrumb_step['url'] != '#') {
                    $sep = (strrchr($breadcrumb_step['url'], '?') ? '&amp;' : '?');
                    $navigation_item['url'] = $breadcrumb_step['url'].$sep.api_get_cidreq();
                } else {
                    $navigation_item['url'] = '#';
                }
                $navigation_item['title'] = $breadcrumb_step['name'];
                // titles for shared folders
                if ($breadcrumb_step['name'] == 'shared_folder') {
                    $navigation_item['title'] = get_lang('UserFolders');
                } elseif (strstr($breadcrumb_step['name'], 'shared_folder_session_')) {
                    $navigation_item['title'] = get_lang('UserFolders');
                } elseif (strstr($breadcrumb_step['name'], 'sf_user_')) {
                    $userinfo = api_get_user_info(substr($breadcrumb_step['name'], 8));
                    $navigation_item['title'] = $userinfo['complete_name'];
                } elseif ($breadcrumb_step['name'] == 'chat_files') {
                    $navigation_item['title'] = get_lang('ChatFiles');
                } elseif ($breadcrumb_step['name'] == 'images') {
                    $navigation_item['title'] = get_lang('Images');
                } elseif ($breadcrumb_step['name'] == 'video') {
                    $navigation_item['title'] = get_lang('Video');
                } elseif ($breadcrumb_step['name'] == 'audio') {
                    $navigation_item['title'] = get_lang('Audio');
                } elseif ($breadcrumb_step['name'] == 'flash') {
                    $navigation_item['title'] = get_lang('Flash');
                } elseif ($breadcrumb_step['name'] == 'gallery') {
                    $navigation_item['title'] = get_lang('Gallery');
                }
                // Fixes breadcrumb title now we applied the Security::remove_XSS and we cut the string depending of the MAX_LENGTH_BREADCRUMB value

                $navigation_item['title'] = Text::cut($navigation_item['title'], MAX_LENGTH_BREADCRUMB);
                $navigation_item['title'] = Security::remove_XSS($navigation_item['title']);
                $navigation[] = $navigation_item;
            }
        }

        // part 3: The tool itself. If we are on the course homepage we do not want to display the title of the course because this
        // is the same as the first part of the breadcrumbs (see part 1)

        $final_navigation = array();
        $counter = 0;

        foreach ($navigation as $index => $navigation_info) {
            if (!empty($navigation_info['title'])) {

                if ($navigation_info['url'] == '#') {
                    $final_navigation[$index] = $navigation_info['title'];
                } else {
                    $final_navigation[$index] = '<a href="'.$navigation_info['url'].'" class="" target="_top">'.$navigation_info['title'].'</a>';
                }
                $counter++;
            }
        }

        $html = '';
        if (!empty($final_navigation)) {
            $lis = '';
            $i = 0;
            $final_navigation_count = count($final_navigation);

            if (!empty($final_navigation)) {
                if (!empty($home_link)) {
                    $lis .= Display::tag('li', $home_link);
                }

                foreach ($final_navigation as $bread) {
                    $bread_check = trim(strip_tags($bread));
                    if (!empty($bread_check)) {
                        if ($final_navigation_count - 1 > $i) {
                            //$bread .= '<span class="divider">/</span>';
                        }
                        $lis .= Display::tag('li', $bread);
                        $i++;
                    }
                }
            } else {
                if (!empty($home_link)) {
                    $lis .= Display::tag('li', $home_link);
                }
            }
            $html .= $lis;
        }
        return $html;
    }

    /**
     * Returns a list of directories inside CSS
     * @return array
     */
    public function getStyleSheetFolderList()
    {
        $dirs = $this->app['chamilo.filesystem']->getStyleSheetFolders();
        $themes = array();
        foreach ($dirs as $dir) {
            $themes[] = $dir->getFilename();
        }
        return $themes;
    }
}
