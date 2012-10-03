<?php

/* For licensing terms, see /license.txt */
/**
 *  @author Julio Montoya <gugli100@gmail.com>
 *  @todo better organization of the class, methods and variables 
 * 
 * */

require_once api_get_path(LIBRARY_PATH) . 'banner.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'symfony/Twig/Autoloader.php';

/*use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;*/

class Template {

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

    /* Loads chamilo plugins */
    var $load_plugins = false;
    var $params = array();

    function __construct($title = '', $show_header = true, $show_footer = true, $show_learnpath = false, $hide_global_chat = false, $load_plugins = true) {
        //Page title
        $this->title = $title;
        $this->show_learnpath = $show_learnpath;
        $this->hide_global_chat = $hide_global_chat;
        $this->load_plugins = $load_plugins;
            
         // Current themes: cupertino, smoothness, ui-lightness. Find the themes folder in main/inc/lib/javascript/jquery-ui 
        $this->jquery_ui_theme = 'smoothness'; 

        //Twig settings        
        Twig_Autoloader::register();

        $template_paths = array(
            api_get_path(SYS_CODE_PATH) . 'template', //template folder
            api_get_path(SYS_PLUGIN_PATH)           //plugin folder
        );

        $cache_folder = api_get_path(SYS_ARCHIVE_PATH) . 'twig';

        if (!is_dir($cache_folder)) {
            mkdir($cache_folder, api_get_permissions_for_new_directories());
        }

        $loader = new Twig_Loader_Filesystem($template_paths);

        //Setting Twig options depending on the server see http://twig.sensiolabs.org/doc/api.html#environment-options
        if (api_get_setting('server_type') == 'test') {
            $options = array(
                //'cache' => api_get_path(SYS_ARCHIVE_PATH), //path to the cache folder
                'autoescape' => false,
                'debug' => true,
                'auto_reload' => true,
                'optimizations' => 0, // turn on optimizations with -1
                'strict_variables' => false, //If set to false, Twig will silently ignore invalid variables
            );
        } else {
            $options = array(
                'cache' => $cache_folder, //path to the cache folder
                'autoescape' => false,
                'debug' => false,
                'auto_reload' => false,
                'optimizations' => -1, // turn on optimizations with -1
                'strict_variables' => false //If set to false, Twig will silently ignore invalid variables
            );
        }

        $this->twig = new Twig_Environment($loader, $options);

        $this->twig->addFilter('get_lang', new Twig_Filter_Function('get_lang'));
        $this->twig->addFilter('get_path', new Twig_Filter_Function('api_get_path'));
        $this->twig->addFilter('get_setting', new Twig_Filter_Function('api_get_setting'));
        $this->twig->addFilter('var_dump', new Twig_Filter_Function('var_dump'));
        $this->twig->addFilter('return_message', new Twig_Filter_Function('Display::return_message_and_translate'));

        $this->twig->addFilter('display_page_header', new Twig_Filter_Function('Display::page_header_and_translate'));
        $this->twig->addFilter('display_page_subheader', new Twig_Filter_Function('Display::page_subheader_and_translate'));
        $this->twig->addFilter('icon', new Twig_Filter_Function('Template::get_icon_path'));
        $this->twig->addFilter('format_date', new Twig_Filter_Function('Template::format_date'));

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
    
    public static function get_icon_path($image, $size = ICON_SIZE_SMALL) {
        return Display:: return_icon($image, '', array(), $size, false, true);
    }

    public static function format_date($timestamp, $format = null) {
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
    public static function key($item){
        $id = isset($item->id) ? $item->id : null;
        $c_id = isset($item->c_id) ? $item->c_id : null;
        $result = '';
        if($c_id){
            $result = "c_id=$c_id";
        }
        if($id){
            if($result){
                $result .= "&amp;id=$id";
            }else{
                $result .= "&amp;id=$id";
            }
        }
        return $result;
    }

    function set_help($help_input = null) {
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
                $help_content .= '<a href="' . api_get_path(WEB_CODE_PATH) . 'help/help.php?open=' . $help . '&height=400&width=600" class="ajax" title="' . get_lang('Help') . '">';
                $help_content .= '<img src="' . api_get_path(WEB_IMG_PATH) . 'help.large.png" alt="' . get_lang('Help') . '" title="' . get_lang('Help') . '" />';
                $help_content .= '</a></li>';
            }
        }
        $this->assign('help_content', $help_content);
    }

    /*
     * Use template system to parse the actions menu
     * @todo finish it!
     * */

    function set_actions($actions) {
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
    function display_one_col_template() {
        $tpl = $this->get_template('layout/layout_1_col.tpl');
        $this->display($tpl);
    }

    /**
     * Shortcut to display a 2 col layout (userportal.php)
     * */
    function display_two_col_template() {
        $tpl = $this->get_template('layout/layout_2_col.tpl');
        $this->display($tpl);
        $this->show_page_loaded_info();
    }

    /**
     * Displays an empty template
     */
    function display_blank_template() {
        $tpl = $this->get_template('layout/blank.tpl');
        $this->display($tpl);
    }

    /**
     * Displays an empty template
     */
    function display_no_layout_template() {
        $tpl = $this->get_template('layout/no_layout.tpl');
        $this->display($tpl);
    }

    /** 	  
     * Sets the footer visibility 
     * @param bool true if we show the footer
     */
    function set_footer($status) {
        $this->show_footer = $status;
        $this->assign('show_footer', $status);
    }

    /**
     * Sets the header visibility
     * @param bool true if we show the header
     */
    function set_header($status) {
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

    function get_template($name) {
        return $this->style . '/' . $name;
    }

    /** Set course parameters */
    private function set_course_parameters() {
        //Setting course id
        $course_id = api_get_course_int_id();
        $this->course_id = $course_id;
    }

    /** Set user parameters */
    private function set_user_parameters() {
        $user_info = array();
        $user_info['logged'] = 0;
        $this->user_is_logged_in = false;
        if (api_user_is_login()) {
            $user_info = api_get_user_info();
            $user_info['logged'] = 1;

            $user_info['is_admin'] = 0;
            if (api_is_platform_admin()) {
                $user_info['is_admin'] = 1;
            }

            $user_info['messages_count'] = MessageManager::get_new_messages();
            $this->user_is_logged_in = true;
        }
        //Setting the $_u array that could be use in any template 
        $this->assign('_u', $user_info);
    }

    /** Set system parameters */
    private function set_system_parameters() {
        global $_configuration;

        //Setting app paths/URLs
        $_p = array('web' => api_get_path(WEB_PATH),
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
    function set_css_files() {
        global $disable_js_and_css_files;
        $css = array();
        
        $this->theme = api_get_visual_theme();

        if (!empty($this->preview_theme)) {
            $this->theme = $this->preview_theme;
        }

        //Base CSS
        $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'base.css');

        //Default theme CSS
        $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).$this->theme.'/default.css');
        $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'bootstrap-responsive.css');
        $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'responsive.css');
        
        //Extra CSS files
        $css[] = api_get_path(WEB_LIBRARY_PATH) . 'javascript/thickbox.css';
        $css[] = api_get_path(WEB_LIBRARY_PATH) . 'javascript/chosen/chosen.css';
            
        if ($this->show_learnpath) {
            $css[] = api_get_path(WEB_CSS_PATH) . $this->theme . '/learnpath.css';
            $css[] = api_get_path(WEB_CSS_PATH) . $this->theme . '/scorm.css';
        }

        if (api_is_global_chat_enabled()) {
            $css[] = api_get_path(WEB_LIBRARY_PATH) . 'javascript/chat/css/chat.css';
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
            $css_file_to_string .= 'img, div { behavior: url(' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/iepngfix/iepngfix.htc) } ' . "\n";
        }
        
        if (!$disable_js_and_css_files) {
            $this->assign('css_file_to_string', $css_file_to_string);
            
            $style_print = api_get_css(api_get_cdn_path(api_get_path(WEB_CSS_PATH) . $this->theme . '/print.css'), 'print');
            $this->assign('css_style_print', $style_print);
        }
        
        // Logo
        $logo = return_logo($this->theme);
        $this->assign('logo', $logo);
    }
    
    function set_js_files() {
        global $disable_js_and_css_files, $htmlHeadXtra;
    
        
        //JS files        
        $js_files = array(
            'modernizr.js',
            'jquery.min.js',
            'chosen/chosen.jquery.min.js',
            'jquery-ui/'.$this->jquery_ui_theme.'/jquery-ui-custom.min.js',      
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
        
        if (!$disable_js_and_css_files) {       
            $this->assign('js_file_to_string', $js_file_to_string);
            
            $extra_headers = null;            
            if (isset($htmlHeadXtra) && $htmlHeadXtra) {
                foreach ($htmlHeadXtra as & $this_html_head) {
                    $extra_headers .= $this_html_head . "\n";
                }
            }
            $this->assign('extra_headers', $extra_headers);
        }
    }

    /**
     * Set header parameters
     */
    private function set_header_parameters() {        
        global $httpHeadXtra, $_course, $interbreadcrumb, $language_file, $noPHP_SELF, $_configuration, $this_section;
        $help = $this->help;
        $nameTools = $this->title;
        $navigation = return_navigation_array();
        $this->menu_navigation = $navigation['menu_navigation'];

        $this->assign('system_charset', api_get_system_encoding());

        if (isset($httpHeadXtra) && $httpHeadXtra) {
            foreach ($httpHeadXtra as & $thisHttpHead) {
                header($thisHttpHead);
            }
        }

        $this->assign('online_button', Security::remove_XSS(Display::return_icon('online.png')));
        $this->assign('offline_button', Security::remove_XSS(Display::return_icon('offline.png')));

        // Get language iso-code for this page - ignore errors				
        $this->assign('document_language', api_get_language_isocode());

        $course_title = $_course['name'];

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
            $title_string .=$title_list[$i];
            if (isset($title_list[$i + 1])) {
                $item = trim($title_list[$i + 1]);
                if (!empty($item))
                    $title_string .=' - ';
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
                $prefetch .= '<link rel="dns-prefetch" href="' . $host . '">';
            }
        }
        
        $this->assign('prefetch', $prefetch);
        $this->assign('text_direction', api_get_text_direction());
        $this->assign('section_name', 'section-' . $this_section);

        $favico = '<link rel="shortcut icon" href="' . api_get_path(WEB_PATH) . 'favicon.ico" type="image/x-icon" />';

        if (isset($_configuration['multiple_access_urls']) && $_configuration['multiple_access_urls']) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $url_info = api_get_access_url($access_url_id);
                $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
                $clean_url = replace_dangerous_char($url);
                $clean_url = str_replace('/', '-', $clean_url);
                $clean_url .= '/';
                $homep = api_get_path(REL_PATH) . 'home/' . $clean_url; //homep for Home Path
                $icon_real_homep = api_get_path(SYS_PATH) . 'home/' . $clean_url;

                //we create the new dir for the new sites
                if (is_file($icon_real_homep . 'favicon.ico')) {
                    $favico = '<link rel="shortcut icon" href="' . $homep . 'favicon.ico" type="image/x-icon" />';
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
		        						<img src="' . api_get_path(WEB_IMG_PATH) . 'bug.large.png" style="vertical-align: middle;" alt="' . get_lang('ReportABug') . '" title="' . get_lang('ReportABug') . '"/></a>
		    						  </li>';
        }

        $this->assign('bug_notification_link', $bug_notification_link);

        $notification = return_notification_menu();
        $this->assign('notification_menu', $notification);        
        
        //Preparing values for the menu
        
        //Logout link
        $this->assign('logout_link', api_get_path(WEB_PATH).'index.php?logout=logout&&uid='.api_get_user_id());        
        
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
        $menu = return_menu();
        $this->assign('menu', $menu);
        
        //Breadcrumb        
        $breadcrumb = return_breadcrumb($interbreadcrumb, $language_file, $nameTools);
        $this->assign('breadcrumb', $breadcrumb);

        //Extra content
        $extra_header = null;
        if (!api_is_platform_admin()) {
            $extra_header = trim(api_get_setting('header_extra_content'));
        }
        $this->assign('header_extra_content', $extra_header);
        
        if ($this->show_header == 1) {
            header('Content-Type: text/html; charset=' . api_get_system_encoding());
            header('X-Powered-By: ' . $_configuration['software_name'] . ' ' . substr($_configuration['system_version'], 0, 1));
        }
    }

    /**
     * Set footer parameteres
     */
    private function set_footer_parameters() {
        global $_configuration;

        //Show admin data
        //$this->assign('show_administrator_data', api_get_setting('show_administrator_data'));

        if (api_get_setting('show_administrator_data') == 'true') {
            //Administrator name
            $administrator_data = get_lang('Manager') . ' : ' . Display::encrypted_mailto_link(api_get_setting('emailAdministrator'), api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname')));
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
                        $tutor_data .= get_lang('Coachs') . ' : ';
                        $tutor_data .= array_to_string($email_link, CourseManager::USER_SEPARATOR);
                    } elseif (count($coachs_email) == 1) {
                        $tutor_data .= get_lang('Coach') . ' : ';
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
                    $teacher_data .= $label . ' : ' . array_to_string($teachers_parsed, CourseManager::USER_SEPARATOR);
                }
                $this->assign('teachers', $teacher_data);
            }
        }
        /* $stats = '';	
          $this->assign('execution_stats', $stats); */
    }

    function show_header_template() {
        $tpl = $this->get_template('layout/show_header.tpl');
        $this->display($tpl);
    }

    function show_footer_template() {
        $tpl = $this->get_template('layout/show_footer.tpl');
        $this->show_page_loaded_info();
        $this->display($tpl);
        
    }

    /* Sets the plugin content in a template variable */
    function set_plugin_region($plugin_region) {
        if (!empty($plugin_region)) {
            $region_content = $this->plugin->load_region($plugin_region, $this, $this->force_plugin_load);
            if (!empty($region_content)) {
                $this->assign('plugin_' . $plugin_region, $region_content);
            } else {
                $this->assign('plugin_' . $plugin_region, null);
            }
        }
        return null;
    }

    public function fetch($template = null) {
        $template = $this->twig->loadTemplate($template);
        return $template->render($this->params);
    }

    public function assign($tpl_var, $value = null) {
        $this->params[$tpl_var] = $value;
    }

    public function display($template) {
        echo $this->twig->render($template, $this->params);
    }
    
    function show_page_loaded_info() {   
        //@todo will be removed before a stable release
        $mtime = microtime(); 
        $mtime = explode(" ",$mtime); 
        $mtime = $mtime[1] + $mtime[0]; 
        error_log('--------------------------------------------------------');
        error_log("Page loaded in:".($mtime-START));
        error_log("memory_get_usage: ".format_file_size(memory_get_usage(true)));
        error_log("memory_get_peak_usage: ".format_file_size(memory_get_peak_usage(true)));
    }
}
