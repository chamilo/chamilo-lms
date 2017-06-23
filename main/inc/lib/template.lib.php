<?php
/* For licensing terms, see /license.txt */

/**
 * Class Template
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @todo better organization of the class, methods and variables
 *
 */
class Template
{
    /**
     * The Template folder name see main/template
     * @var string
     */
    public $templateFolder = 'default';

    /**
     * The theme that will be used: chamilo, public_admin, chamilo_red, etc
     * This variable is set from the database
     * @var string
     */
    public $theme = '';
    private $themeDir;

    /**
     * @var string
     */
    public $preview_theme = '';
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

    /* Loads chamilo plugins */
    public $load_plugins = false;
    public $params = array();
    public $force_plugin_load = false;

    /**
     * @param string $title
     * @param bool $show_header
     * @param bool $show_footer
     * @param bool $show_learnpath
     * @param bool $hide_global_chat
     * @param bool $load_plugins
     * @param bool $sendHeaders send http headers or not
     */
    public function __construct(
        $title = '',
        $show_header = true,
        $show_footer = true,
        $show_learnpath = false,
        $hide_global_chat = false,
        $load_plugins = true,
        $sendHeaders = true
    ) {
        // Page title
        $this->title = $title;

        $this->show_learnpath = $show_learnpath;

        if (empty($this->show_learnpath)) {
            $origin = api_get_origin();
            if ($origin === 'learnpath') {
                $this->show_learnpath = true;
                $show_footer = false;
                $show_header = false;
            }
        }
        $this->hide_global_chat = $hide_global_chat;
        $this->load_plugins = $load_plugins;

        $template_paths = array(
            api_get_path(SYS_CODE_PATH).'template/overrides', // user defined templates
            api_get_path(SYS_CODE_PATH).'template', //template folder
            api_get_path(SYS_PLUGIN_PATH) // plugin folder
        );

        $urlId = api_get_current_access_url_id();

        $cache_folder = api_get_path(SYS_ARCHIVE_PATH).'twig/'.$urlId.'/';

        if (!is_dir($cache_folder)) {
            mkdir($cache_folder, api_get_permissions_for_new_directories(), true);
        }

        $loader = new Twig_Loader_Filesystem($template_paths);

        $isTestMode = api_get_setting('server_type') === 'test';

        //Setting Twig options depending on the server see http://twig.sensiolabs.org/doc/api.html#environment-options
        if ($isTestMode) {
            $options = array(
                //'cache' => api_get_path(SYS_ARCHIVE_PATH), //path to the cache folder
                'autoescape' => false,
                'debug' => true,
                'auto_reload' => true,
                'optimizations' => 0,
                // turn on optimizations with -1
                'strict_variables' => false,
                //If set to false, Twig will silently ignore invalid variables
            );
        } else {
            $options = array(
                'cache' => $cache_folder,
                //path to the cache folder
                'autoescape' => false,
                'debug' => false,
                'auto_reload' => false,
                'optimizations' => -1,
                // turn on optimizations with -1
                'strict_variables' => false
                //If set to false, Twig will silently ignore invalid variables
            );
        }

        $this->twig = new Twig_Environment($loader, $options);

        if ($isTestMode) {
            $this->twig->addExtension(new Twig_Extension_Debug());
        }

        // Twig filters setup
        $filters = [
            'get_plugin_lang',
            'get_lang',
            'api_get_path',
            'api_get_local_time',
            'api_convert_and_format_date',
            'api_is_allowed_to_edit',
            'api_get_user_info',
            'api_get_configuration_value',
            'api_get_setting',
            [
                'name' => 'return_message',
                'callable' => 'Display::return_message_and_translate'
            ],
            [
                'name' => 'display_page_header',
                'callable' => 'Display::page_header_and_translate'
            ],
            [
                'name' => 'display_page_subheader',
                'callable' => 'Display::page_subheader_and_translate'
            ],
            [
                'name' => 'icon',
                'callable' => 'Template::get_icon_path'
            ],
            [
                'name' => 'img',
                'callable' => 'Template::get_image'
            ],
            [
                'name' => 'format_date',
                'callable' => 'Template::format_date'
            ]
        ];

        foreach ($filters as $filter) {
            if (is_array($filter)) {
                $this->twig->addFilter(new Twig_SimpleFilter($filter['name'], $filter['callable']));
            } else {
                $this->twig->addFilter(new Twig_SimpleFilter($filter, $filter));
            }
        }

        // Setting system variables
        $this->set_system_parameters();

        // Setting user variables
        $this->set_user_parameters();

        // Setting course variables
        $this->set_course_parameters();

        // Setting administrator variables
        $this->setAdministratorParams();
        $this->setCSSEditor();

        // Header and footer are showed by default
        $this->set_footer($show_footer);
        $this->set_header($show_header);

        $this->set_header_parameters($sendHeaders);
        $this->set_footer_parameters();

        $defaultStyle = api_get_configuration_value('default_template');
        if (!empty($defaultStyle)) {
            $this->templateFolder = $defaultStyle;
        }

        $this->assign('template', $this->templateFolder);
        $this->assign('locale', api_get_language_isocode());
        $this->assign('login_class', null);

        $this->setLoginForm();

        // Chamilo plugins
        if ($this->show_header) {
            if ($this->load_plugins) {
                $this->plugin = new AppPlugin();

                //1. Showing installed plugins in regions
                $pluginRegions = $this->plugin->get_plugin_regions();
                foreach ($pluginRegions as $region) {
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
     * @param string $image
     * @param int $size
     * @param string $name
     * @return string
     */
    public static function get_image($image, $size = ICON_SIZE_SMALL, $name = '')
    {
        return Display::return_icon($image, $name, array(), $size);
    }

    /**
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
     * Return the item's url key:
     *
     *      c_id=xx&id=xx
     *
     * @param object $item
     * @return string
     */
    public static function key($item)
    {
        $id     = isset($item->id) ? $item->id : null;
        $c_id   = isset($item->c_id) ? $item->c_id : null;
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
     * @param string $helpInput
     */
    public function setHelp($helpInput = null)
    {
        if (!empty($helpInput)) {
            $help = $helpInput;
        } else {
            $help = $this->help;
        }

        $content = '';
        if (api_get_setting('enable_help_link') == 'true') {
            if (!empty($help)) {
                $help = Security::remove_XSS($help);
                $content = '<div class="help">';
                $content .= Display::url(
                    Display::return_icon('help.large.png', get_lang('Help')),
                    api_get_path(WEB_CODE_PATH).'help/help.php?open='.$help,
                    [
                        'class' => 'ajax',
                        'data-title' => get_lang('Help')
                    ]
                );
                $content .= '</div>';
            }
        }
        $this->assign('help_content', $content);
    }

    /**
     * Use template system to parse the actions menu
     * @todo finish it!
     **/
    public function set_actions($actions)
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
     **/
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
    public function set_footer($status)
    {
        $this->show_footer = $status;
        $this->assign('show_footer', $status);
    }

    /**
     * return true if toolbar has to be displayed for user
     * @return bool
     */
    public static function isToolBarDisplayedForUser()
    {
        //Toolbar
        $show_admin_toolbar = api_get_setting('show_admin_toolbar');
        $show_toolbar = false;

        switch ($show_admin_toolbar) {
            case 'do_not_show':
                break;
            case 'show_to_admin':
                if (api_is_platform_admin()) {
                    $show_toolbar = true;
                }
                break;
            case 'show_to_admin_and_teachers':
                if (api_is_platform_admin() || api_is_allowed_to_edit()) {
                    $show_toolbar = true;
                }
                break;
            case 'show_to_all':
                $show_toolbar = true;
                break;
        }
        return $show_toolbar;
    }

    /**
     * Sets the header visibility
     * @param bool true if we show the header
     */
    public function set_header($status)
    {
        $this->show_header = $status;
        $this->assign('show_header', $status);

        $show_toolbar = 0;

        if (self::isToolBarDisplayedForUser()) {
            $show_toolbar = 1;
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
     *
     * @return string
     */
    public function get_template($name)
    {
        return $this->templateFolder.'/'.$name;
    }

    /**
     * Set course parameters
     */
    private function set_course_parameters()
    {
        //Setting course id
        $course = api_get_course_info();
        if (empty($course)) {
            $this->assign('course_is_set', false);
            return;
        }
        $this->assign('course_is_set', true);
        $this->course_id = $course['id'];
        $_c = array(
            'id' => $course['id'],
            'code' => $course['code'],
            'title' => $course['name'],
            'visibility' => $course['visibility'],
            'language' => $course['language'],
            'directory' => $course['directory'],
            'session_id' => api_get_session_id(),
            'user_is_teacher' => api_is_course_admin(),
            'student_view' => (!empty($_GET['isStudentView']) && $_GET['isStudentView'] == 'true'),
        );
        $this->assign('course_code', $course['code']);
        $this->assign('_c', $_c);
    }

    /**
     * Set user parameters
     */
    private function set_user_parameters()
    {
        $user_info = array();
        $user_info['logged'] = 0;
        $this->user_is_logged_in = false;
        if (api_user_is_login()) {
            $user_info = api_get_user_info(api_get_user_id(), true);
            $user_info['logged'] = 1;

            $user_info['is_admin'] = 0;
            if (api_is_platform_admin()) {
                $user_info['is_admin'] = 1;
            }

            $user_info['messages_count'] = MessageManager::getCountNewMessages();
            $this->user_is_logged_in = true;
        }
        // Setting the $_u array that could be use in any template
        $this->assign('_u', $user_info);
    }

    /**
     * Get theme dir
     * @param string $theme
     * @return string
     */
    public static function getThemeDir($theme)
    {
        $themeDir = 'themes/'.$theme.'/';
        $virtualTheme = api_get_configuration_value('virtual_css_theme_folder');
        if (!empty($virtualTheme)) {
            $virtualThemeList = api_get_themes(true);
            $isVirtualTheme = in_array($theme, array_keys($virtualThemeList));
            if ($isVirtualTheme) {
                $themeDir = 'themes/'.$virtualTheme.'/'.$theme.'/';
            }
        }

        return $themeDir;
    }

    /**
     * Set system parameters
     */
    public function set_system_parameters()
    {
        $this->theme = api_get_visual_theme();
        if (!empty($this->preview_theme)) {
            $this->theme = $this->preview_theme;
        }

        $this->themeDir = self::getThemeDir($this->theme);

        // Setting app paths/URLs
        $_p = array(
            'web' => api_get_path(WEB_PATH),
            'web_relative' => api_get_path(REL_PATH),
            'web_course' => api_get_path(WEB_COURSE_PATH),
            'web_main' => api_get_path(WEB_CODE_PATH),
            'web_css' => api_get_path(WEB_CSS_PATH),
            'web_css_theme' => api_get_path(WEB_CSS_PATH).$this->themeDir,
            'web_ajax' => api_get_path(WEB_AJAX_PATH),
            'web_img' => api_get_path(WEB_IMG_PATH),
            'web_plugin' => api_get_path(WEB_PLUGIN_PATH),
            'web_lib' => api_get_path(WEB_LIBRARY_PATH),
            'web_upload' => api_get_path(WEB_UPLOAD_PATH),
            'web_self' => api_get_self(),
            'web_query_vars' => api_htmlentities($_SERVER['QUERY_STRING']),
            'web_self_query_vars' => api_htmlentities($_SERVER['REQUEST_URI']),
            'web_cid_query' => api_get_cidreq(),
        );
        $this->assign('_p', $_p);

        // Here we can add system parameters that can be use in any template
        $_s = array(
            'software_name' => api_get_configuration_value('software_name'),
            'system_version' => api_get_configuration_value('system_version'),
            'site_name' => api_get_setting('siteName'),
            'institution' => api_get_setting('Institution'),
            'date' => api_format_date('now', DATE_FORMAT_LONG),
            'timezone' => api_get_timezone(),
            'gamification_mode' => api_get_setting('gamification_mode')
        );
        $this->assign('_s', $_s);
    }

    /**
     * Set theme, include mainstream CSS files
     * @return void
     * @see setCssCustomFiles() for additional CSS sheets
     */
    public function setCssFiles()
    {
        global $disable_js_and_css_files;
        $css = array();

        // Default CSS Bootstrap
        $bowerCSSFiles = [
            'fontawesome/css/font-awesome.min.css',
            'jquery-ui/themes/smoothness/theme.css',
            'jquery-ui/themes/smoothness/jquery-ui.min.css',
            'mediaelement/build/mediaelementplayer.min.css',
            'jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.css',
            'bootstrap/dist/css/bootstrap.min.css',
            'jquery.scrollbar/jquery.scrollbar.css',
            'bootstrap-daterangepicker/daterangepicker.css',
            'bootstrap-select/dist/css/bootstrap-select.min.css',
            'select2/dist/css/select2.min.css'
        ];

        foreach ($bowerCSSFiles as $file) {
            $css[] = api_get_path(WEB_PATH).'web/assets/'.$file;
        }

        $css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/chosen/chosen.css';

        if (api_is_global_chat_enabled()) {
            $css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/chat/css/chat.css';
        }
        $css_file_to_string = '';
        foreach ($css as $file) {
            $css_file_to_string .= api_get_css($file);
        }

        if (!$disable_js_and_css_files) {
            $this->assign('css_static_file_to_string', $css_file_to_string);
        }
    }

    /**
     *
     */
    public function setCSSEditor()
    {
        $cssEditor = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'editor.css');
        if (is_file(api_get_path(SYS_CSS_PATH).$this->themeDir.'editor.css')) {
            $cssEditor = api_get_path(WEB_CSS_PATH).$this->themeDir.'editor.css';
        }

        $this->assign('cssEditor', $cssEditor);
    }

    /**
     * Prepare custom CSS to be added at the very end of the <head> section
     * @return void
     * @see setCssFiles() for the mainstream CSS files
     */
    public function setCssCustomFiles()
    {
        global $disable_js_and_css_files;
        // Base CSS
        $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'base.css');

        if ($this->show_learnpath) {
            $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'scorm.css');
            if (is_file(api_get_path(SYS_CSS_PATH).$this->themeDir.'learnpath.css')) {
                $css[] = api_get_path(WEB_CSS_PATH).$this->themeDir.'learnpath.css';
            }
        }

        if (is_file(api_get_path(SYS_CSS_PATH).$this->themeDir.'editor.css')) {
            $css[] = api_get_path(WEB_CSS_PATH).$this->themeDir.'editor.css';
        } else {
            $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'editor.css');
        }

        $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).$this->themeDir.'default.css');

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
            $css_file_to_string .= 'img, div { behavior: url('.api_get_path(WEB_LIBRARY_PATH).'javascript/iepngfix/iepngfix.htc) } '."\n";
        }

        if (!$disable_js_and_css_files) {
            $this->assign('css_custom_file_to_string', $css_file_to_string);

            $style_print = '';
            if (is_readable(api_get_path(SYS_CSS_PATH).$this->theme.'/print.css')) {
                $style_print = api_get_css(
                    api_get_cdn_path(api_get_path(WEB_CSS_PATH).$this->theme.'/print.css'),
                    'print'
                );
            }
            $this->assign('css_style_print', $style_print);
        }

        // Logo
        $logo = return_logo($this->theme);
        $this->assign('logo', $logo);
        $this->assign('show_media_element', 1);
    }

    /**
     * Declare and define the template variable that will be used to load
     * javascript libraries in the header.
     */
    public function set_js_files()
    {
        global $disable_js_and_css_files, $htmlHeadXtra;
        $isoCode = api_get_language_isocode();
        $selectLink = 'bootstrap-select/dist/js/i18n/defaults-'.$isoCode.'_'.strtoupper($isoCode).'.min.js';

        if ($isoCode == 'en') {
            $selectLink = 'bootstrap-select/dist/js/i18n/defaults-'.$isoCode.'_US.min.js';
        }
        // JS files
        $js_files = array(
            'chosen/chosen.jquery.min.js'
        );

        $viewBySession = api_get_setting('my_courses_view_by_session') === 'true';

        if (api_is_global_chat_enabled() || $viewBySession) {
            // Do not include the global chat in LP
            if ($this->show_learnpath == false &&
                $this->show_footer == true &&
                $this->hide_global_chat == false
            ) {
                $js_files[] = 'chat/js/chat.js';
            }
        }

        if (api_get_setting('accessibility_font_resize') == 'true') {
            $js_files[] = 'fontresize.js';
        }

        $js_file_to_string = '';
        $bowerJsFiles = [
            'modernizr/modernizr.js',
            'jquery/dist/jquery.min.js',
            'bootstrap/dist/js/bootstrap.min.js',
            'jquery-ui/jquery-ui.min.js',
            'moment/min/moment-with-locales.js',
            'bootstrap-daterangepicker/daterangepicker.js',
            'jquery-timeago/jquery.timeago.js',
            'mediaelement/build/mediaelement-and-player.min.js',
            'jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.js',
            'image-map-resizer/js/imageMapResizer.min.js',
            'jquery.scrollbar/jquery.scrollbar.min.js',
            'readmore-js/readmore.min.js',
            'bootstrap-select/dist/js/bootstrap-select.min.js',
            $selectLink,
            'select2/dist/js/select2.min.js',
            "select2/dist/js/i18n/$isoCode.js"
        ];
        if (CHAMILO_LOAD_WYSIWYG == true) {
            $bowerJsFiles[] = 'ckeditor/ckeditor.js';
        }

        if (api_get_setting('include_asciimathml_script') == 'true') {
            $bowerJsFiles[] = 'MathJax/MathJax.js?config=TeX-AMS_HTML';
        }

        if ($isoCode != 'en') {
            $bowerJsFiles[] = 'jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-'.$isoCode.'.js';
            $bowerJsFiles[] = 'jquery-ui/ui/minified/i18n/datepicker-'.$isoCode.'.min.js';
        }

        foreach ($bowerJsFiles as $file) {
            $js_file_to_string .= '<script type="text/javascript" src="'.api_get_path(WEB_PATH).'web/assets/'.$file.'"></script>'."\n";
        }

        foreach ($js_files as $file) {
            $js_file_to_string .= api_get_js($file);
        }

        // Loading email_editor js
        if (!api_is_anonymous() && api_get_setting('allow_email_editor') == 'true') {
            $template = $this->get_template('mail_editor/email_link.js.tpl');
            $js_file_to_string .= $this->fetch($template);
        }

        if (!$disable_js_and_css_files) {
            $this->assign('js_file_to_string', $js_file_to_string);

            //Adding jquery ui by default
            $extra_headers = api_get_jquery_ui_js();

            //$extra_headers = '';
            if (isset($htmlHeadXtra) && $htmlHeadXtra) {
                foreach ($htmlHeadXtra as & $this_html_head) {
                    $extra_headers .= $this_html_head."\n";
                }
            }
            $this->assign('extra_headers', $extra_headers);
        }
    }

    /**
     * Special function to declare last-minute JS libraries which depend on
     * other things to be declared first. In particular, it might be useful
     * under IE9 with compatibility mode, which for some reason is getting
     * upset when a variable is used in a function (even if not used yet)
     * when this variable hasn't been defined yet.
     */
    public function set_js_files_post()
    {
        global $disable_js_and_css_files;
        $js_files = array();
        if (api_is_global_chat_enabled()) {
            //Do not include the global chat in LP
            if ($this->show_learnpath == false && $this->show_footer == true && $this->hide_global_chat == false) {
                $js_files[] = 'chat/js/chat.js';
            }
        }
        $js_file_to_string = null;

        foreach ($js_files as $js_file) {
            $js_file_to_string .= api_get_js($js_file);
        }
        if (!$disable_js_and_css_files) {
            $this->assign('js_file_to_string_post', $js_file_to_string);
        }
    }

    /**
     * Set header parameters
     * @param bool $sendHeaders send headers
     */
    private function set_header_parameters($sendHeaders)
    {
        global $httpHeadXtra, $interbreadcrumb, $language_file, $_configuration, $this_section;
        $_course = api_get_course_info();
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

        $this->assign(
            'online_button',
            Display::return_icon('statusonline.png', null, [], ICON_SIZE_ATOM)
        );
        $this->assign(
            'offline_button',
            Display::return_icon('statusoffline.png', null, [], ICON_SIZE_ATOM)
        );

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

        // Setting the theme and CSS files
        $css = $this->setCssFiles();
        $this->set_js_files();
        $this->setCssCustomFiles($css);

        $browser = api_browser_support('check_browser');
        if ($browser[0] == 'Internet Explorer' && $browser[1] >= '11') {
            $browser_head = '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />';
            $this->assign('browser_specific_head', $browser_head);
        }

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

        // Default root chamilo favicon
        $favico = '<link rel="shortcut icon" href="'.api_get_path(WEB_PATH).'favicon.ico" type="image/x-icon" />';

        //Added to verify if in the current Chamilo Theme exist a favicon
        $favicoThemeUrl = api_get_path(SYS_CSS_PATH).$this->themeDir.'images/';

        //If exist pick the current chamilo theme favicon
        if (is_file($favicoThemeUrl.'favicon.ico')) {
            $favico = '<link rel="shortcut icon" href="'.api_get_path(WEB_CSS_PATH).$this->themeDir.'images/favicon.ico" type="image/x-icon" />';
        }

        if (api_is_multiple_url_enabled()) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $url_info = api_get_access_url($access_url_id);
                $url = api_remove_trailing_slash(
                    preg_replace('/https?:\/\//i', '', $url_info['url'])
                );
                $clean_url = api_replace_dangerous_char($url);
                $clean_url = str_replace('/', '-', $clean_url);
                $clean_url .= '/';
                $homep = api_get_path(REL_PATH).'home/'.$clean_url; //homep for Home Path
                $icon_real_homep = api_get_path(SYS_APP_PATH).'home/'.$clean_url;
                //we create the new dir for the new sites
                if (is_file($icon_real_homep.'favicon.ico')) {
                    $favico = '<link rel="shortcut icon" href="'.$homep.'favicon.ico" type="image/x-icon" />';
                }
            }
        }

        $this->assign('favico', $favico);
        $this->setHelp();

        //@todo move this in the template
        $rightFloatMenu = '';
        $iconBug = Display::return_icon(
            'bug.png',
            get_lang('ReportABug'),
            [],
            ICON_SIZE_LARGE
        );
        if (api_get_setting('show_link_bug_notification') == 'true' && $this->user_is_logged_in) {
            $rightFloatMenu = '<div class="report">
		        <a href="https://github.com/chamilo/chamilo-lms/wiki/How-to-report-issues" target="_blank">
                    '.$iconBug.'
                </a>
		        </div>';
        }

        if (api_get_setting('show_link_ticket_notification') == 'true' && $this->user_is_logged_in) {
            // by default is project_id = 1
            $iconTicket = Display::return_icon(
                'bug.png',
                get_lang('Ticket'),
                [],
                ICON_SIZE_LARGE
            );
            $courseInfo = api_get_course_info();
            $courseParams = '';
            if (!empty($courseInfo)) {
                $courseParams = api_get_cidreq();
            }
            $url = api_get_path(WEB_CODE_PATH).'ticket/tickets.php?project_id=1&'.$courseParams;
            $rightFloatMenu .= '<div class="report">
		        <a href="'.$url.'" target="_blank">
                    '.$iconTicket.'
                </a>
		    </div>';
        }

        $this->assign('bug_notification', $rightFloatMenu);

        $resize = '';
        if (api_get_setting('accessibility_font_resize') == 'true') {
            $resize .= '<div class="resize_font">';
            $resize .= '<div class="btn-group">';
            $resize .= '<a title="'.get_lang('DecreaseFontSize').'" href="#" class="decrease_font btn btn-default"><em class="fa fa-font"></em></a>';
            $resize .= '<a title="'.get_lang('ResetFontSize').'" href="#" class="reset_font btn btn-default"><em class="fa fa-font"></em></a>';
            $resize .= '<a title="'.get_lang('IncreaseFontSize').'" href="#" class="increase_font btn btn-default"><em class="fa fa-font"></em></a>';
            $resize .= '</div>';
            $resize .= '</div>';
        }
        $this->assign('accessibility', $resize);

        // Preparing values for the menu

        // Logout link
        $hideLogout = api_get_setting('hide_logout_button');
        if ($hideLogout === 'true') {
            $this->assign('logout_link', null);
        } else {
            $this->assign('logout_link', api_get_path(WEB_PATH).'index.php?logout=logout&uid='.api_get_user_id());
        }

        // Profile link
        if (api_get_setting('allow_social_tool') == 'true') {
            $profile_url = api_get_path(WEB_CODE_PATH).'social/home.php';
        } else {
            $profile_url = api_get_path(WEB_CODE_PATH).'auth/profile.php';
        }

        $this->assign('profile_url', $profile_url);

        //Message link
        $message_link = null;
        $message_url = null;
        if (api_get_setting('allow_message_tool') == 'true') {
            $message_url = api_get_path(WEB_CODE_PATH).'messages/inbox.php';
            $message_link = '<a href="'.api_get_path(WEB_CODE_PATH).'messages/inbox.php">'.get_lang('Inbox').'</a>';
        }
        $this->assign('message_link', $message_link);
        $this->assign('message_url', $message_url);

        // Certificate Link

        $allow = api_get_configuration_value('hide_my_certificate_link');
        if ($allow === false) {
            $certificateUrl = api_get_path(WEB_CODE_PATH).'gradebook/my_certificates.php';
            $certificateLink = Display::url(
                get_lang('MyCertificates'),
                $certificateUrl
            );
            $this->assign('certificate_link', $certificateLink);
            $this->assign('certificate_url', $certificateUrl);
        }

        $institution = api_get_setting('Institution');
        $portal_name = empty($institution) ? api_get_setting('siteName') : $institution;

        $this->assign('portal_name', $portal_name);

        //Menu
        $menu = menuArray();
        $this->assign('menu', $menu);

        $breadcrumb = '';
        // Hide breadcrumb in LP
        if ($this->show_learnpath == false) {
            $breadcrumb = return_breadcrumb(
                $interbreadcrumb,
                $language_file,
                $nameTools
            );
        }
        $this->assign('breadcrumb', $breadcrumb);

        //Extra content
        $extra_header = null;
        if (!api_is_platform_admin()) {
            $extra_header = trim(api_get_setting('header_extra_content'));
        }
        $this->assign('header_extra_content', $extra_header);

        if ($sendHeaders) {
            header('Content-Type: text/html; charset='.api_get_system_encoding());
            header(
                'X-Powered-By: '.$_configuration['software_name'].' '.substr($_configuration['system_version'], 0, 1)
            );
        }

        self::addHTTPSecurityHeaders();

        $socialMeta = '';
        $metaTitle = api_get_setting('meta_title');
        if (!empty($metaTitle)) {
            $socialMeta .= '<meta name="twitter:card" content="summary" />'."\n";
            $metaSite = api_get_setting('meta_twitter_site');
            if (!empty($metaSite)) {
                $socialMeta .= '<meta name="twitter:site" content="'.$metaSite.'" />'."\n";
                $metaCreator = api_get_setting('meta_twitter_creator');
                if (!empty($metaCreator)) {
                    $socialMeta .= '<meta name="twitter:creator" content="'.$metaCreator.'" />'."\n";
                }
            }

            // The user badge page emits its own meta tags, so if this is
            // enabled, ignore the global ones
            $userId = isset($_GET['user']) ? intval($_GET['user']) : 0;
            $skillId = isset($_GET['skill']) ? intval($_GET['skill']) : 0;

            if (!$userId && !$skillId) {
                // no combination of user and skill ID has been defined,
                // so print the normal OpenGraph meta tags
                $socialMeta .= '<meta property="og:title" content="'.$metaTitle.'" />'."\n";
                $socialMeta .= '<meta property="og:url" content="'.api_get_path(WEB_PATH).'" />'."\n";

                $metaDescription = api_get_setting('meta_description');
                if (!empty($metaDescription)) {
                    $socialMeta .= '<meta property="og:description" content="'.$metaDescription.'" />'."\n";
                }

                $metaImage = api_get_setting('meta_image_path');
                if (!empty($metaImage)) {
                    if (is_file(api_get_path(SYS_PATH).$metaImage)) {
                        $path = api_get_path(WEB_PATH).$metaImage;
                        $socialMeta .= '<meta property="og:image" content="'.$path.'" />'."\n";
                    }
                }
            }
        }

        $this->assign('social_meta', $socialMeta);
    }

    /**
     * Set footer parameters
     */
    private function set_footer_parameters()
    {
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
                $adminName = get_lang('Manager').' : '.
                    Display::encrypted_mailto_link(
                        api_get_setting('emailAdministrator'),
                        $name
                    );
            }
            $this->assign('administrator_name', $adminName);
        }

        // Loading footer extra content
        if (!api_is_platform_admin()) {
            $extra_footer = trim(api_get_setting('footer_extra_content'));
            if (!empty($extra_footer)) {
                $this->assign('footer_extra_content', $extra_footer);
            }
        }

        // Tutor name
        if (api_get_setting('show_tutor_data') == 'true') {
            // Course manager
            $courseId = api_get_course_int_id();
            $id_session = api_get_session_id();
            if (!empty($courseId)) {
                $tutor_data = '';
                if ($id_session != 0) {
                    $coachs_email = CourseManager::get_email_of_tutor_to_session(
                        $id_session,
                        $courseId
                    );
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
            $courseId = api_get_course_int_id();
            if (!empty($courseId)) {
                $teacher_data = '';
                $mail = CourseManager::get_emails_of_tutors_to_course($courseId);
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

    /**
     * Show header template.
     */
    public function show_header_template()
    {
        $tpl = $this->get_template('layout/show_header.tpl');
        $this->display($tpl);
    }

    /**
     * Show footer template.
     */
    public function show_footer_template()
    {
        $tpl = $this->get_template('layout/show_footer.tpl');
        $this->display($tpl);
    }

    /**
     * Show footer js template.
     */
    public function show_footer_js_template()
    {
        $tpl = $this->get_template('layout/footer.js.tpl');
        $this->display($tpl);
    }

    /**
     * Sets the plugin content in a template variable
     * @param string $pluginRegion
     * @return null
     */
    public function set_plugin_region($pluginRegion)
    {
        if (!empty($pluginRegion)) {
            $regionContent = $this->plugin->load_region($pluginRegion, $this, $this->force_plugin_load);

            $pluginList = $this->plugin->get_installed_plugins();
            foreach ($pluginList as $plugin_name) {

                // The plugin_info variable is available inside the plugin index
                $pluginInfo = $this->plugin->getPluginInfo($plugin_name);

                if (isset($pluginInfo['is_course_plugin']) && $pluginInfo['is_course_plugin']) {
                    $courseInfo = api_get_course_info();

                    if (!empty($courseInfo)) {
                        if (isset($pluginInfo['obj']) && $pluginInfo['obj'] instanceof Plugin) {
                            /** @var Plugin $plugin */
                            $plugin = $pluginInfo['obj'];
                            $regionContent .= $plugin->renderRegion($pluginRegion);
                        }
                    }
                } else {
                    continue;
                }
            }

            if (!empty($regionContent)) {
                $this->assign('plugin_'.$pluginRegion, $regionContent);
            } else {
                $this->assign('plugin_'.$pluginRegion, null);
            }
        }
        return null;
    }

    /**
     * @param string $template
     * @return string
     */
    public function fetch($template = null)
    {
        $template = $this->twig->loadTemplate($template);
        return $template->render($this->params);
    }

    /**
     * @param string $variable
     * @param mixed $value
     */
    public function assign($variable, $value = '')
    {
        $this->params[$variable] = $value;
    }

    /**
     * Render the template
     * @param string $template The template path
     * @param boolean $clearFlashMessages Clear the $_SESSION variables for flash messages
     */
    public function display($template, $clearFlashMessages = true)
    {
        $this->assign('flash_messages', Display::getFlashToString());

        if ($clearFlashMessages) {
            Display::cleanFlashMessages();
        }

        echo $this->twig->render($template, $this->params);
    }

    /**
     * Adds a body class for login pages
     */
    public function setLoginBodyClass()
    {
        $this->assign('login_class', 'section-login');
    }

    /**
     * The theme that will be used if the database is not working.
     * @return string
     */
    public static function getThemeFallback()
    {
        $theme = api_get_configuration_value('theme_fallback');
        if (empty($theme)) {
            $theme = 'chamilo';
        }
        return $theme;
    }

    /**
     * @param bool|true $setLoginForm
     */
    public function setLoginForm($setLoginForm = true)
    {
        global $loginFailed;
        $userId = api_get_user_id();
        if (!($userId) || api_is_anonymous($userId)) {
            // Only display if the user isn't logged in.
            $this->assign(
                'login_language_form',
                api_display_language_form(true)
            );
            if ($setLoginForm) {
                $this->assign('login_form', $this->displayLoginForm());

                if ($loginFailed) {
                    $this->assign('login_failed', $this::handleLoginFailed());
                }
            }
        }
    }

    /**
     * @return string
     */
    public function handleLoginFailed()
    {
        $message = get_lang('InvalidId');

        if (!isset($_GET['error'])) {
            if (api_is_self_registration_allowed()) {
                $message = get_lang('InvalidForSelfRegistration');
            }
        } else {
            switch ($_GET['error']) {
                case '':
                    if (api_is_self_registration_allowed()) {
                        $message = get_lang('InvalidForSelfRegistration');
                    }
                    break;
                case 'account_expired':
                    $message = get_lang('AccountExpired');
                    break;
                case 'account_inactive':
                    $message = get_lang('AccountInactive');
                    break;
                case 'user_password_incorrect':
                    $message = get_lang('InvalidId');
                    break;
                case 'access_url_inactive':
                    $message = get_lang('AccountURLInactive');
                    break;
                case 'wrong_captcha':
                    $message = get_lang('TheTextYouEnteredDoesNotMatchThePicture');
                    break;
                case 'blocked_by_captcha':
                    $message = get_lang('AccountBlockedByCaptcha');
                    break;
                case 'multiple_connection_not_allowed':
                    $message = get_lang('MultipleConnectionsAreNotAllow');
                    break;
                case 'unrecognize_sso_origin':
                    //$message = get_lang('SSOError');
                    break;
            }
        }
        return Display::return_message($message, 'error');
    }

    /**
     * @return string
     */
    public function displayLoginForm()
    {
        $form = new FormValidator(
            'formLogin',
            'POST',
            null,
            null,
            null,
            FormValidator::LAYOUT_BOX_NO_LABEL
        );

        $form->addText(
            'login',
            get_lang('UserName'),
            true,
            array(
                'id' => 'login',
                'autofocus' => 'autofocus',
                'icon' => 'user fa-fw',
                'placeholder' => get_lang('UserName'),
                'autocapitalize' => 'none'
            )
        );

        $form->addElement(
            'password',
            'password',
            get_lang('Pass'),
            array(
                'id' => 'password',
                'icon' => 'lock fa-fw',
                'placeholder' => get_lang('Pass'),
                'autocapitalize' => 'none',
            )
        );

        // Captcha
        $captcha = api_get_setting('allow_captcha');
        $allowCaptcha = $captcha === 'true';

        if ($allowCaptcha) {
            $useCaptcha = isset($_SESSION['loginFailed']) ? $_SESSION['loginFailed'] : null;
            if ($useCaptcha) {
                $ajax = api_get_path(WEB_AJAX_PATH).'form.ajax.php?a=get_captcha';
                $options = array(
                    'width' => 250,
                    'height' => 90,
                    'callback'     => $ajax.'&var='.basename(__FILE__, '.php'),
                    'sessionVar'   => basename(__FILE__, '.php'),
                    'imageOptions' => array(
                        'font_size' => 20,
                        'font_path' => api_get_path(SYS_FONTS_PATH).'opensans/',
                        'font_file' => 'OpenSans-Regular.ttf',
                        //'output' => 'gif'
                    )
                );

                // Minimum options using all defaults (including defaults for Image_Text):
                //$options = array('callback' => 'qfcaptcha_image.php');
                $captcha_question = $form->addElement('CAPTCHA_Image', 'captcha_question', '', $options);
                $form->addHtml(get_lang('ClickOnTheImageForANewOne'));

                $form->addElement(
                    'text',
                    'captcha',
                    get_lang('EnterTheLettersYouSee')
                );
                $form->addRule(
                    'captcha',
                    get_lang('EnterTheCharactersYouReadInTheImage'),
                    'required',
                    null,
                    'client'
                );
                $form->addRule(
                    'captcha',
                    get_lang('TheTextYouEnteredDoesNotMatchThePicture'),
                    'CAPTCHA',
                    $captcha_question
                );
            }
        }

        $form->addButton(
            'submitAuth',
            get_lang('LoginEnter'),
            null,
            'primary',
            null,
            'btn-block'
        );

        $html = $form->returnForm();
        if (api_get_setting('openid_authentication') == 'true') {
            include_once 'main/auth/openid/login.php';
            $html .= '<div>'.openid_form().'</div>';
        }

        return $html;
    }

    /**
     * Set administrator variables
     */
    private function setAdministratorParams()
    {
        $_admin = [
            'email' => api_get_setting('emailAdministrator'),
            'surname' => api_get_setting('administratorSurname'),
            'name' => api_get_setting('administratorName'),
            'telephone' => api_get_setting('administratorTelephone')
        ];

        $this->assign('_admin', $_admin);
    }

    /**
     * Manage specific HTTP headers security
     * @return void (prints headers directly)
     */
    private function addHTTPSecurityHeaders()
    {
        // Implementation of HTTP headers security, as suggested and checked
        // by https://securityheaders.io/
        // Enable these settings in configuration.php to use them on your site
        // Strict-Transport-Security
        $setting = api_get_configuration_value('security_strict_transport');
        if (!empty($setting)) {
            header('Strict-Transport-Security: '.$setting);
        }
        // Content-Security-Policy
        $setting = api_get_configuration_value('security_content_policy');
        if (!empty($setting)) {
            header('Content-Security-Policy: '.$setting);
        }
        $setting = api_get_configuration_value('security_content_policy_report_only');
        if (!empty($setting)) {
            header('Content-Security-Policy-Report-Only: '.$setting);
        }
        // Public-Key-Pins
        $setting = api_get_configuration_value('security_public_key_pins');
        if (!empty($setting)) {
            header('Public-Key-Pins: '.$setting);
        }
        $setting = api_get_configuration_value('security_public_key_pins_report_only');
        if (!empty($setting)) {
            header('Public-Key-Pins-Report-Only: '.$setting);
        }
        // X-Frame-Options
        $setting = api_get_configuration_value('security_x_frame_options');
        if (!empty($setting)) {
            header('X-Frame-Options: '.$setting);
        }
        // X-XSS-Protection
        $setting = api_get_configuration_value('security_xss_protection');
        if (!empty($setting)) {
            header('X-XSS-Protection: '.$setting);
        }
        // X-Content-Type-Options
        $setting = api_get_configuration_value('security_x_content_type_options');
        if (!empty($setting)) {
            header('X-Content-Type-Options: '.$setting);
        }
        // Referrer-Policy
        $setting = api_get_configuration_value('security_referrer_policy');
        if (!empty($setting)) {
            header('Referrer-Policy: '.$setting);
        }
        // end of HTTP headers security block
    }
}
