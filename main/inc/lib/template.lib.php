<?php

/* For licensing terms, see /license.txt */
/**
 * @author Julio Montoya <gugli100@gmail.com>
 * @todo better organization of the class, methods and variables
 *
 * */
use \ChamiloSession as Session;

class Template
{
    public $style = 'default'; //see the template folder
    public $preview_theme = null;
    public $theme; // the chamilo theme public_admin, chamilo, chamilo_red, etc
    public $title = null;
    public $show_header;
    public $show_footer;
    public $help;
    public $menu_navigation = array(); //Used in the userportal.lib.php function: return_navigation_course_links()
    public $show_learnpath = false; // This is a learnpath section or not?
    public $plugin = null;
    public $course_id = null;
    public $user_is_logged_in = false;
    public $twig = null;
    public $jquery_ui_theme;
    public $load_plugins = false; /* Loads chamilo plugins */
    public $force_plugin_load = true;
    public $app;
    public $navigation_array;

    function __construct($title = null, $app = null)
    {
        if (empty($app)) {
            global $app;
            $this->app = &$app;
        } else {
            //ugly fix just for now
            $this->app = &$app;
        }

        $this->app['classic_layout'] = true;

        $this->navigation_array = $this->return_navigation_array();

//      $this->app['template_style'] = 'default';
//        $this->app['default_layout'] = $this->app['template_style'].'/layout/layout_1_col.tpl';

        $show_header = $app['template.show_header'];
        $show_footer = $app['template.show_footer'];
        $show_learnpath = $app['template.show_learnpath'];
        $hide_global_chat = $app['template.hide_global_chat'];
        $load_plugins = $app['template.load_plugins'];

        //Page title
        $this->title = $title;
        $this->show_learnpath = $show_learnpath;
        $this->hide_global_chat = $hide_global_chat;
        $this->load_plugins = $load_plugins;

        // Current themes: cupertino, smoothness, ui-lightness. Find the themes folder in main/inc/lib/javascript/jquery-ui
        $this->jquery_ui_theme = 'smoothness';

        //Setting system variables
        $this->set_system_parameters();

        //Setting user variables
        $this->set_user_parameters();

        //Setting course variables
        $this->set_course_parameters();

        //header and footer are showed by default
        $this->set_footer($show_footer);
        $this->set_header($show_header);

        $this->set_header_parameters();
        $this->set_footer_parameters();

        $this->assign('style', $this->style);

        //Chamilo plugins
        if ($this->show_header) {
            if ($this->load_plugins) {

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

    public static function get_icon_path($image, $size = ICON_SIZE_SMALL)
    {
        return Display:: return_icon($image, '', array(), $size, false, true);
    }

    public static function format_date($timestamp, $format = null)
    {
        return api_format_date($timestamp, $format);
    }

    /**
     * Return the item's url key:
     *
     *      c_id=xx&id=xx
     *
     * @param object $item
     * @return string
     */
    public static function key($item)
    {
        $id = isset($item->id) ? $item->id : null;
        $c_id = isset($item->c_id) ? $item->c_id : null;
        $result = '';
        if ($c_id) {
            $result = "c_id=$c_id";
        }
        if ($id) {
            if ($result) {
                $result .= "&amp;id=$id";
            } else {
                $result .= "&amp;id=$id";
            }
        }
        return $result;
    }

    /**
     * @param string $help_input
     */
    public function set_help($help_input = null)
    {
        if (!empty($help_input)) {
            $help = $help_input;
        } else {
            $help = $this->help;
        }

        $help_content = '';
        if (api_get_setting('enable_help_link') == 'true') {
            if (!empty($help)) {
                $help = Security::remove_XSS($help);
                $help_content = '<li class="help">';
                $help_content .= '<a href="'.api_get_path(WEB_CODE_PATH).'help/help.php?open='.$help.'&height=400&width=600" class="ajax" title="'.get_lang('Help').'">';
                $help_content .= '<img src="'.api_get_path(WEB_IMG_PATH).'help.large.png" alt="'.get_lang(
                    'Help'
                ).'" title="'.get_lang('Help').'" />';
                $help_content .= '</a></li>';
            }
        }
        $this->assign('help_content', $help_content);
    }

    /*
     * Use template system to parse the actions menu
     * @todo finish it!
     * */
    function set_actions($actions)
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
    function display_one_col_template()
    {
        $tpl = $this->get_template('layout/layout_1_col.tpl');
        $this->display($tpl);
    }

    /**
     * Shortcut to display a 2 col layout (userportal.php)
     * */
    function display_two_col_template()
    {
        $tpl = $this->get_template('layout/layout_2_col.tpl');
        $this->display($tpl);
    }

    /**
     * Displays an empty template
     */
    function display_blank_template()
    {
        $tpl = $this->get_template('layout/blank.tpl');
        $this->display($tpl);
    }

    /**
     * Displays an empty template
     */
    function display_no_layout_template()
    {
        $tpl = $this->get_template('layout/no_layout.tpl');
        $this->display($tpl);
    }

    /**
     * Sets the footer visibility
     * @param bool true if we show the footer
     */
    function set_footer($status)
    {
        $this->show_footer = $status;
        $this->assign('show_footer', $status);
    }

    /**
     * Sets the header visibility
     * @param bool true if we show the header
     */
    function set_header($status)
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

    function get_template($name)
    {
        return $this->app['template_style'].'/'.$name;
    }

    /** Set course parameters */
    private function set_course_parameters()
    {
        //Setting course id
        $course_id = api_get_course_int_id();
        $this->course_id = $course_id;
    }

    /** Set user parameters */
    private function set_user_parameters()
    {
        $user_info = array();
        $user_info['logged'] = 0;
        $this->user_is_logged_in = false;

        if (api_user_is_login()) {
            $user_info = api_get_user_info(api_get_user_id());
            $user_info['logged'] = 1;

            $user_info['is_admin'] = 0;
            if (api_is_platform_admin()) {
                $user_info['is_admin'] = 1;
            }
            $new_messages = MessageManager::get_new_messages();
            $user_info['messages_count'] = $new_messages != 0 ? Display::label($new_messages, 'warning') : null;
            $messages_invitations_count = GroupPortalManager::get_groups_by_user_count(
                $user_info['user_id'],
                GROUP_USER_PERMISSION_PENDING_INVITATION,
                false
            );
            $user_info['messages_invitations_count'] = $messages_invitations_count != 0 ? Display::label(
                $messages_invitations_count,
                'warning'
            ) : null;
            $this->user_is_logged_in = true;
        }
        //Setting the $_u array that could be use in any template
        $this->assign('_u', $user_info);
    }

    /** Set system parameters */
    private function set_system_parameters()
    {
        global $_configuration;

        //Setting app paths/URLs
        $_p = array(
            'web' => api_get_path(WEB_PATH),
            'web_course' => api_get_path(WEB_COURSE_PATH),
            'web_main' => api_get_path(WEB_CODE_PATH),
            'web_css' => api_get_path(WEB_CSS_PATH),
            'web_ajax' => api_get_path(WEB_AJAX_PATH),
            'web_img' => api_get_path(WEB_IMG_PATH),
            'web_plugin' => api_get_path(WEB_PLUGIN_PATH),
            'web_lib' => api_get_path(WEB_LIBRARY_PATH),
        );
        $this->assign('_p', $_p);

        //Here we can add system parameters that can be use in any template
        $_s = array(
            'software_name' => $_configuration['software_name'],
            'system_version' => $_configuration['system_version'],
            'site_name' => api_get_setting('siteName'),
            'institution' => api_get_setting('Institution')
        );
        $this->assign('_s', $_s);
    }

    /**
     * Set theme, include CSS files  */
    function set_css_files()
    {
        global $disable_js_and_css_files;
        $css = array();

        $this->theme = api_get_visual_theme();

        if (!empty($this->preview_theme)) {
            $this->theme = $this->preview_theme;
        }

        //Base CSS
        $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'base.css');
        //Compressed version of default + all CSS files
        //$css[] = api_get_cdn_path(api_get_path(WEB_PATH).'web/css/'.$this->theme.'/style.css');

        //Default theme CSS
        $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).$this->theme.'/default.css');
        $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'bootstrap-responsive.css');
        $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'responsive.css');
        $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'font_awesome/font-awesome.css');

        //Extra CSS files
        $css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css';
        $css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/chosen/chosen.css';

        if ($this->show_learnpath) {
            $css[] = api_get_path(WEB_CSS_PATH).$this->theme.'/learnpath.css';
            $css[] = api_get_path(WEB_CSS_PATH).$this->theme.'/scorm.css';
        }

        if (api_is_global_chat_enabled()) {
            $css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/chat/css/chat.css';
        }

        $css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/'.$this->jquery_ui_theme.'/jquery-ui-custom.css';
        $css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/default.css';

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

            $style_print = api_get_css(api_get_cdn_path(api_get_path(WEB_CSS_PATH).$this->theme.'/print.css'), 'print');
            $this->assign('css_style_print', $style_print);
        }

        // Logo
        $logo = $this->return_logo($this->theme);
        $this->assign('logo', $logo);
    }

    function set_js_files()
    {
        global $disable_js_and_css_files, $htmlHeadXtra;
        //JS files
        $js_files = array(
            'modernizr.js',
            'jquery.min.js',
            'chosen/chosen.jquery.min.js',
            'jquery-ui/'.$this->jquery_ui_theme.'/jquery-ui-custom.min.js',
            'jquery-ui/jquery.ui.touch-punch.js',
            'thickbox.js',
            'bootstrap/bootstrap.js',
        );

        if (api_is_global_chat_enabled()) {
            //Do not include the global chat in LP
            if ($this->show_learnpath == false && $this->show_footer == true && $this->hide_global_chat == false) {
                $js_files[] = 'chat/js/chat.js';
            }
        }

        if (api_get_setting('accessibility_font_resize') == 'true') {
            $js_files[] = 'fontresize.js';
        }

        if (api_get_setting('include_asciimathml_script') == 'true') {
            $js_files[] = 'asciimath/ASCIIMathML.js';
        }

        $js_file_to_string = null;

        foreach ($js_files as $js_file) {
            $js_file_to_string .= api_get_js($js_file);
        }

        //Loading email_editor js
        if (!api_is_anonymous() && api_get_setting('allow_email_editor') == 'true') {
            $js_file_to_string .= $this->fetch('default/mail_editor/email_link.js.tpl');
        }

        //$js_file_to_string = api_get_js_simple(api_get_path(WEB_PATH).'web/js/script.js').$js_file_to_string;

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
    private function set_header_parameters() {
        global $interbreadcrumb;

        if (isset($this->app['breadcrumb']) && !empty($this->app['breadcrumb'])) {
            if (empty($interbreadcrumb)) {
                $interbreadcrumb = $this->app['breadcrumb'];
            } else {
                $interbreadcrumb = array_merge($interbreadcrumb, $this->app['breadcrumb']);
            }
        }
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
        $this->assign('document_language', api_get_language_isocode());

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
        $this->set_css_files();
        $this->set_js_files();

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
                $url_info = api_get_access_url($access_url_id);
                $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
                $clean_url = replace_dangerous_char($url);
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

        $this->set_help();

        //@todo move this in the template
        $bug_notification_link = '';
        if (api_get_setting('show_link_bug_notification') == 'true' && $this->user_is_logged_in) {
            $bug_notification_link = '<li class="report">
		        						<a href="http://support.chamilo.org/projects/chamilo-18/wiki/How_to_report_bugs" target="_blank">
		        						<img src="'.api_get_path(WEB_IMG_PATH).'bug.large.png" style="vertical-align: middle;" alt="'.get_lang('ReportABug').'" title="'.get_lang(
                'ReportABug'
            ).'"/></a>
		    						  </li>';
        }
        $this->assign('bug_notification_link', $bug_notification_link);

        $notification = $this->return_notification_menu();
        $this->assign('notification_menu', $notification);

        //Preparing values for the menu

        //Logout link
        $this->assign('logout_link', api_get_path(WEB_PUBLIC_PATH).'logout');

        //Profile link
        if (api_get_setting('allow_social_tool') == 'true') {
            $profile_link = '<a href="'.api_get_path(WEB_CODE_PATH).'social/home.php">'.get_lang('Profile').'</a>';
        } else {
            $profile_link = '<a href="'.api_get_path(WEB_CODE_PATH).'auth/profile.php">'.get_lang('Profile').'</a>';
        }
        $this->assign('profile_link', $profile_link);

        //Message link
        $message_link = null;
        if (api_get_setting('allow_message_tool') == 'true') {
            $message_link = '<a href="'.api_get_path(WEB_CODE_PATH).'messages/inbox.php">'.get_lang('Inbox').'</a>';
        }
        $this->assign('message_link', $message_link);

        $institution = api_get_setting('Institution');
        $portal_name = empty($institution) ? api_get_setting('siteName') : $institution;

        $this->assign('portal_name', $portal_name);

        //Menu
        $menu = $this->return_menu();

        $this->assign('menu', $menu);

        //Breadcrumb
        $breadcrumb = $this->return_breadcrumb($interbreadcrumb, $nameTools);
        $this->assign('breadcrumb', $breadcrumb);

        //Extra content
        $extra_header = null;
        if (!api_is_platform_admin()) {
            $extra_header = trim(api_get_setting('header_extra_content'));
        }
        $this->assign('header_extra_content', $extra_header);
    }

    /**
     * Set footer parameteres
     */
    private function set_footer_parameters()
    {
        global $_configuration;

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

        //Tutor name
        if (api_get_setting('show_tutor_data') == 'true') {
            // Course manager
            $id_course = api_get_course_id();
            $id_session = api_get_session_id();
            if (isset($id_course) && $id_course != -1) {
                $tutor_data = '';
                if ($id_session != 0) {
                    $coachs_email = CourseManager::get_email_of_tutor_to_session($id_session, $id_course);
                    $email_link = array();
                    foreach ($coachs_email as $coach) {
                        $email_link[] = Display::encrypted_mailto_link($coach['email'], $coach['complete_name']);
                    }
                    if (count($coachs_email) > 1) {
                        $tutor_data .= get_lang('Coachs').' : ';
                        $tutor_data .= array_to_string($email_link, CourseManager::USER_SEPARATOR);
                    } elseif (count($coachs_email) == 1) {
                        $tutor_data .= get_lang('Coach').' : ';
                        $tutor_data .= array_to_string($email_link, CourseManager::USER_SEPARATOR);
                    } elseif (count($coachs_email) == 0) {
                        $tutor_data .= '';
                    }
                }
                $this->assign('session_teachers', $tutor_data);
            }
        }

        if (api_get_setting('show_teacher_data') == 'true') {
            // course manager
            $id_course = api_get_course_id();
            if (isset($id_course) && $id_course != -1) {
                $teacher_data = '';
                $mail = CourseManager::get_emails_of_tutors_to_course($id_course);
                if (!empty($mail)) {
                    $teachers_parsed = array();
                    foreach ($mail as $value) {
                        foreach ($value as $email => $name) {
                            $teachers_parsed[] = Display::encrypted_mailto_link($email, $name);
                        }
                    }
                    $label = get_lang('Teacher');
                    if (count($mail) > 1) {
                        $label = get_lang('Teachers');
                    }
                    $teacher_data .= $label.' : '.array_to_string($teachers_parsed, CourseManager::USER_SEPARATOR);
                }
                $this->assign('teachers', $teacher_data);
            }
        }
    }

    function show_header_template()
    {
        $tpl = $this->get_template('layout/show_header.tpl');
        $this->display($tpl);
    }

    function show_footer_template()
    {
        $tpl = $this->get_template('layout/show_footer.tpl');
        $this->display($tpl);
    }

    function manage_display($content)
    {
        //$this->assign('content', $content);
    }

    /* Sets the plugin content in a template variable */
    function set_plugin_region($plugin_region)
    {
        if (!empty($plugin_region)) {
            $region_content = $this->plugin->load_region($plugin_region, $this, $this->force_plugin_load);
            if (!empty($region_content)) {
                $this->assign('plugin_'.$plugin_region, $region_content);
            } else {
                $this->assign('plugin_'.$plugin_region, null);
            }
        }
        return null;
    }

    public function fetch($template = null)
    {
        $template = $this->app['twig']->loadTemplate($template);
        return $template->render(array());
    }

    public function assign($key, $value = null)
    {
        $this->app['twig']->addGlobal($key, $value);
    }

    public function display($template = null)
    {
        if (!empty($template)) {
            $this->app['default_layout'] = $template;
        }
        $this->app->run();
    }

    function show_page_loaded_info()
    {
        //@todo will be removed before a stable release
        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1] + $mtime[0];
        error_log('--------------------------------------------------------');
        error_log("Page loaded in:".($mtime - START));
        error_log("memory_get_usage: ".format_file_size(memory_get_usage(true)));
        error_log("memory_get_peak_usage: ".format_file_size(memory_get_peak_usage(true)));
    }

    function return_menu()
    {
        $navigation = $this->navigation_array;
        $navigation = $navigation['navigation'];

        // Displaying the tabs

        $lang = ''; //el for "Edit Language"
        //$user_language_choice = Session::get('user_language_choice');
        $user_language_choice = isset($_SESSION['user_language_choice']) ? $_SESSION['user_language_choice'] : null;
        $user_info = api_get_user_id() ? api_get_user_info() : null;

        if (!empty($user_language_choice)) {
            $lang = $user_language_choice;
        } elseif (!empty($user_info['language'])) {
            $lang = $user_info['language'];
        } else {
            $lang = api_get_setting('platformLanguage');
        }

        //Preparing home folder for multiple urls

        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $url_info = api_get_access_url($access_url_id);
                $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
                $clean_url = replace_dangerous_char($url);
                $clean_url = str_replace('/', '-', $clean_url);
                $clean_url .= '/';
                $homep = api_get_path(SYS_PATH).'home/'.$clean_url; //homep for Home Path
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

    function return_navigation_links()
    {
        $html = '';

        // Deleting the myprofile link.
        if (api_get_setting('allow_social_tool') == 'true') {
            unset($this->menu_navigation['myprofile']);
        }

        // Main navigation section.
        // Tabs that are deactivated are added here.
        if (!empty($this->menu_navigation)) {
            $content = '<ul class="nav nav-list">';
            foreach ($this->menu_navigation as $section => $navigation_info) {
                $current = isset($GLOBALS['this_section']) && $section == $GLOBALS['this_section'] ? ' id="current"' : '';
                $content .= '<li'.$current.'>';
                $content .= '<a href="'.$navigation_info['url'].'" target="_self">'.$navigation_info['title'].'</a>';
                $content .= '</li>';
            }
            $content .= '</ul>';
            $html = PageController::show_right_block(get_lang('MainNavigation'), $content, 'navigation_link_block');
        }
        return $html;
    }

    function render_layout($layout = null)
    {
        if (empty($layout)) {
            $layout = $this->app['default_layout'];
        }
        return $this->app['twig']->render($this->app['template_style'].'/layout/'.$layout);
    }

    function render_template($template, $elements = array())
    {
        return $this->app['twig']->render($this->app['template_style'].'/'.$template, $elements);
    }

    /**
     * Determines the possible tabs (=sections) that are available.
     * This function is used when creating the tabs in the third header line and
     * all the sections that do not appear there (as determined by the
     * platform admin on the Dokeos configuration settings page)
     * will appear in the right hand menu that appears on several other pages
     * @return array containing all the possible tabs
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     */
    function get_tabs()
    {
        global $_course;

        $navigation = array();

        // Campus Homepage
        $navigation[SECTION_CAMPUS]['url'] = api_get_path(WEB_PATH).'index.php';
        $navigation[SECTION_CAMPUS]['title'] = get_lang('CampusHomepage');

        // My Courses

        if (api_is_allowed_to_create_course()) {
            // Link to my courses for teachers
            $navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php?nosession=true';
            $navigation['mycourses']['title'] = get_lang('MyCourses');
        } else {
            // Link to my courses for students
            $navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php';
            $navigation['mycourses']['title'] = get_lang('MyCourses');
        }

        // My Profile
        $navigation['myprofile']['url'] = api_get_path(WEB_CODE_PATH).'auth/profile.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '');
        $navigation['myprofile']['title'] = get_lang('ModifyProfile');

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
            $navigation['session_my_space']['url'] = api_get_path(WEB_CODE_PATH).'mySpace/';
            $navigation['session_my_space']['title'] = get_lang('MySpace');
        } else {
            // Link to my progress
            $navigation['session_my_progress']['url'] = api_get_path(WEB_CODE_PATH).'auth/my_progress.php';
            $navigation['session_my_progress']['title'] = get_lang('MyProgress');
        }


        // Social
        if (api_get_setting('allow_social_tool') == 'true') {
            $navigation['social']['url'] = api_get_path(WEB_CODE_PATH).'social/home.php';
            /*
            // get count unread message and total invitations
            $count_unread_message = MessageManager::get_number_of_messages(true);

            $number_of_new_messages_of_friend   = SocialManager::get_message_number_invitation_by_user_id(api_get_user_id());
            $group_pending_invitations = GroupPortalManager::get_groups_by_user(api_get_user_id(), GROUP_USER_PERMISSION_PENDING_INVITATION,false);
            if (!empty($group_pending_invitations )) {
                $group_pending_invitations = count($group_pending_invitations);
            }
            $total_invitations = intval($number_of_new_messages_of_friend) + $group_pending_invitations + intval($count_unread_message);
            $total_invitations = (!empty($total_invitations) ? Display::badge($total_invitations) :'');*/

            $navigation['social']['title'] = get_lang('SocialNetwork');
        }

        // Dashboard
        if (api_is_platform_admin() || api_is_drh() || api_is_session_admin()) {
            $navigation['dashboard']['url'] = api_get_path(WEB_CODE_PATH).'dashboard/index.php';
            $navigation['dashboard']['title'] = get_lang('Dashboard');
        }

        // Reports
        /*
        if (api_is_platform_admin() || api_is_drh() || api_is_session_admin()) {
            $navigation['reports']['url'] = api_get_path(WEB_CODE_PATH).'reports/index.php';
            $navigation['reports']['title'] = get_lang('Reports');
        }*/

        // Custom tabs
        for ($i = 1; $i <= 3; $i++) {
            if (api_get_setting('custom_tab_'.$i.'_name') && api_get_setting('custom_tab_'.$i.'_url')) {
                $navigation['custom_tab_'.$i]['url'] = api_get_setting('custom_tab_'.$i.'_url');
                $navigation['custom_tab_'.$i]['title'] = api_get_setting('custom_tab_'.$i.'_name');
            }
        }

        // Platform administration
        if (api_is_platform_admin(true)) {
            $navigation['platform_admin']['url'] = api_get_path(WEB_CODE_PATH).'admin/';
            $navigation['platform_admin']['title'] = get_lang('PlatformAdmin');
        }
        return $navigation;
    }

    function return_logo($theme)
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

        /* //  Course title section
         if (!empty($_cid) and $_cid != -1 and isset($_course)) {
             //Put the name of the course in the header
             $html .= '<div id="my_courses">';
             $html .= '</div>';
         } elseif (isset($nameTools) && $language_file != 'course_home') {
             //Put the name of the user-tools in the header
             if (!isset($user_id)) {
                 //echo '<div id="my_courses"></div>';
             } elseif (!$noPHP_SELF) {
                 $html .= '<div id="my_courses"><a href="'.api_get_self().'?'.api_get_cidreq(). '" target="_top">'.$nameTools.'</a></div>';
             } else {
                 $html .= '<div id="my_courses">'.$nameTools.'</div>';
             }
         }*/
        return $html;
    }

    function return_notification_menu()
    {
        $_course = api_get_course_info();
        $course_id = api_get_course_id();
        $user_id = api_get_user_id();

        $html = '';

        if ((api_get_setting('showonline', 'world') == 'true' AND !$user_id) OR (api_get_setting(
            'showonline',
            'users'
        ) == 'true' AND $user_id) OR (api_get_setting('showonline', 'course') == 'true' AND $user_id AND $course_id)
        ) {
            $number = who_is_online_count(api_get_setting('time_limit_whosonline'));

            $number_online_in_course = 0;
            if (!empty($_course['id'])) {
                $number_online_in_course = who_is_online_in_this_course_count(
                    $user_id,
                    api_get_setting('time_limit_whosonline'),
                    $_course['id']
                );
            }

            // Display the who's online of the platform
            if ($number) {
                if ((api_get_setting('showonline', 'world') == 'true' AND !$user_id) OR (api_get_setting(
                    'showonline',
                    'users'
                ) == 'true' AND $user_id)
                ) {
                    $html .= '<li><a href="'.api_get_path(WEB_PATH).'whoisonline.php" target="_top" title="'.get_lang(
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
                if (is_array($_course) AND api_get_setting(
                    'showonline',
                    'course'
                ) == 'true' AND isset($_course['sysCode'])
                ) {
                    $html .= '<li><a href="'.api_get_path(
                        WEB_PATH
                    ).'whoisonline.php?cidReq='.$_course['sysCode'].'" target="_top">'.
                        Display::return_icon(
                            'course.png',
                            get_lang('UsersOnline').' '.get_lang('InThisCourse'),
                            array(),
                            ICON_SIZE_TINY
                        ).' '.$number_online_in_course.' </a></li>';
                }
            }

            // Display the who's online for the session
            if (isset($user_id) && api_get_session_id() != 0) {
                if (api_is_allowed_to_edit()) {
                    $html .= '<li><a href="'.api_get_path(
                        WEB_PATH
                    ).'whoisonlinesession.php?session_id='.api_get_session_id().'&id_coach='.$user_id.'" >'.
                        Display::return_icon(
                            'session.png',
                            get_lang('UsersConnectedToMySessions'),
                            array(),
                            ICON_SIZE_TINY
                        ).' </a></li>';
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

    function return_navigation_array()
    {
        $navigation = array();
        $menu_navigation = array();
        $possible_tabs = $this->get_tabs();

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
            if (api_get_setting('show_tabs', 'my_profile') == 'true' && api_get_setting(
                'allow_social_tool'
            ) != 'true'
            ) {
                $navigation['myprofile'] = $possible_tabs['myprofile'];
            } else {
                $menu_navigation['myprofile'] = $possible_tabs['myprofile'];
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

            // Administration
            if (api_is_platform_admin(true)) {
                if (api_get_setting('show_tabs', 'platform_administration') == 'true') {
                    $navigation['platform_admin'] = $possible_tabs['platform_admin'];
                } else {
                    $menu_navigation['platform_admin'] = $possible_tabs['platform_admin'];
                }
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
                    $navigation['custom_tab_'.$i] = $possible_tabs['custom_tab_'.$i];
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

    function return_breadcrumb($interbreadcrumb)
    {
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
        $session_name = cut($session_name, MAX_LENGTH_BREADCRUMB);
        $my_session_name = is_null($session_name) ? '' : '&nbsp;('.$session_name.')';

        if (!empty($_course) && !isset($_GET['hide_course_breadcrumb'])) {

            $navigation_item['url'] = $web_course_path.$_course['path'].'/index.php'.(!empty($session_id) ? '?id_session='.$session_id : '');
            $course_title = cut($_course['name'], MAX_LENGTH_BREADCRUMB);

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
            /**
             * @todo could be useful adding the My courses in the breadcrumb
            $navigation_item_my_courses['title'] = get_lang('MyCourses');
            $navigation_item_my_courses['url'] = api_get_path(WEB_PATH).'user_portal.php';
            $navigation[] = $navigation_item_my_courses;
             */
            $navigation[] = $navigation_item;
        }

        // part 2: Interbreadcrumbs. If there is an array $interbreadcrumb defined then these have to appear before the last breadcrumb (which is the tool itself)
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
                    $userinfo = Database::get_user_info_from_id(substr($breadcrumb_step['name'], 8));
                    $navigation_item['title'] = api_get_person_name($userinfo['firstname'], $userinfo['lastname']);
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
                //Fixes breadcrumb title now we applied the Security::remove_XSS and we cut the string depending of the MAX_LENGTH_BREADCRUMB value

                $navigation_item['title'] = cut($navigation_item['title'], MAX_LENGTH_BREADCRUMB);
                $navigation_item['title'] = Security::remove_XSS($navigation_item['title']);
                $navigation[] = $navigation_item;
            }
        }


        // part 3: The tool itself. If we are on the course homepage we do not want to display the title of the course because this
        // is the same as the first part of the breadcrumbs (see part 1)
        /*
        if (isset($nameTools) && $language_file != 'course_home') { // TODO: This condition $language_file != 'course_home' might bring surprises.
            $navigation_item['url'] = '#';
            $navigation_item['title'] = $nameTools;
            $navigation[] = $navigation_item;
        }*/

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

        /* Part 4 . Show the teacher view/student view button at the right of the breadcrumb */
        $view_as_student_link = null;
        if ($user_id && isset($course_id)) {
            if ((api_is_course_admin() || api_is_platform_admin()) && api_get_setting(
                'student_view_enabled'
            ) == 'true'
            ) {
                $view_as_student_link = api_display_tool_view_option();
            }
        }

        if (!empty($final_navigation)) {
            $lis = '';
            $i = 0;
            //$home_link = Display::url(Display::img(api_get_path(WEB_CSS_PATH).'home.png', get_lang('Homepage'), array('align'=>'middle')), api_get_path(WEB_PATH), array('class'=>'home'));
            //$lis.= Display::tag('li', Display::url(get_lang('Homepage').'<span class="divider">/</span>', api_get_path(WEB_PATH)));
            $final_navigation_count = count($final_navigation);

            if (!empty($final_navigation)) {
                // $home_link.= '<span class="divider">/</span>';

                if (!empty($home_link)) {
                    $lis .= Display::tag('li', $home_link);
                }

                foreach ($final_navigation as $bread) {
                    $bread_check = trim(strip_tags($bread));
                    if (!empty($bread_check)) {
                        if ($final_navigation_count - 1 > $i) {
                            $bread .= '<span class="divider">/</span>';
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

            // View as student/teacher link
            if (!empty($view_as_student_link)) {
                $lis .= Display::tag(
                    'li',
                    $view_as_student_link,
                    array('id' => 'view_as_link', 'class' => 'pull-right')
                );
            }

            if (!empty($lis)) {
                $html .= Display::tag('ul', $lis, array('class' => 'breadcrumb'));
            }
        }
        return $html;
    }
}