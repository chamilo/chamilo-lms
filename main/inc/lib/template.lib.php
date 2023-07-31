<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\UserBundle\Entity\User;

/**
 * Class Template.
 *
 * @author Julio Montoya <gugli100@gmail.com>
 *
 * @todo better organization of the class, methods and variables
 */
class Template
{
    /**
     * The Template folder name see main/template.
     *
     * @var string
     */
    public $templateFolder = 'default';

    /**
     * The theme that will be used: chamilo, public_admin, chamilo_red, etc
     * This variable is set from the database.
     *
     * @var string
     */
    public $theme = '';

    /**
     * @var string
     */
    public $preview_theme = '';
    public $title = null;
    public $show_header;
    public $show_footer;
    public $help;
    public $menu_navigation = []; //Used in the userportal.lib.php function: return_navigation_course_links()
    public $show_learnpath = false; // This is a learnpath section or not?
    public $plugin = null;
    public $course_id = null;
    public $user_is_logged_in = false;
    public $twig = null;

    /* Loads chamilo plugins */
    public $load_plugins = false;
    public $params = [];
    public $force_plugin_load = false;
    public $responseCode = 0;
    private $themeDir;

    /**
     * @param string $title
     * @param bool   $show_header
     * @param bool   $show_footer
     * @param bool   $show_learnpath
     * @param bool   $hide_global_chat
     * @param bool   $load_plugins
     * @param int    $responseCode
     * @param bool   $sendHeaders      send http headers or not
     */
    public function __construct(
        $title = '',
        $show_header = true,
        $show_footer = true,
        $show_learnpath = false,
        $hide_global_chat = false,
        $load_plugins = true,
        $sendHeaders = true,
        $responseCode = 0
    ) {
        // Page title
        $this->title = $title;
        $this->show_learnpath = $show_learnpath;
        $this->setResponseCode($responseCode);

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

        $template_paths = [
            api_get_path(SYS_CODE_PATH).'template/overrides', // user defined templates
            api_get_path(SYS_CODE_PATH).'template', //template folder
            api_get_path(SYS_PLUGIN_PATH), // plugin folder
        ];

        $urlId = api_get_current_access_url_id();
        $cache_folder = api_get_path(SYS_ARCHIVE_PATH).'twig/'.$urlId.'/';

        if (!is_dir($cache_folder)) {
            mkdir($cache_folder, api_get_permissions_for_new_directories(), true);
        }

        $loader = new Twig_Loader_Filesystem($template_paths);

        $isTestMode = api_get_setting('server_type') === 'test';

        //Setting Twig options depending on the server see http://twig.sensiolabs.org/doc/api.html#environment-options
        if ($isTestMode) {
            $options = [
                //'cache' => api_get_path(SYS_ARCHIVE_PATH), //path to the cache folder
                'autoescape' => false,
                'debug' => true,
                'auto_reload' => true,
                'optimizations' => 0,
                // turn on optimizations with -1
                'strict_variables' => false,
                //If set to false, Twig will silently ignore invalid variables
            ];
        } else {
            $options = [
                'cache' => $cache_folder,
                //path to the cache folder
                'autoescape' => false,
                'debug' => false,
                'auto_reload' => false,
                'optimizations' => -1,
                // turn on optimizations with -1
                'strict_variables' => false,
                //If set to false, Twig will silently ignore invalid variables
            ];
        }

        $this->twig = new Twig_Environment($loader, $options);

        if ($isTestMode) {
            $this->twig->addExtension(new Twig_Extension_Debug());
        }

        // Twig filters setup
        $filters = [
            'var_dump',
            'get_plugin_lang',
            'get_lang',
            'api_get_path',
            'api_get_local_time',
            'api_convert_and_format_date',
            'api_is_allowed_to_edit',
            'api_get_user_info',
            'api_get_configuration_value',
            'api_get_setting',
            'api_get_course_setting',
            'api_get_plugin_setting',
            [
                'name' => 'return_message',
                'callable' => 'Display::return_message_and_translate',
            ],
            [
                'name' => 'display_page_header',
                'callable' => 'Display::page_header_and_translate',
            ],
            [
                'name' => 'display_page_subheader',
                'callable' => 'Display::page_subheader_and_translate',
            ],
            [
                'name' => 'icon',
                'callable' => 'Display::get_icon_path',
            ],
            [
                'name' => 'img',
                'callable' => 'Display::get_image',
            ],
            [
                'name' => 'format_date',
                'callable' => 'api_format_date',
            ],
            [
                'name' => 'get_template',
                'callable' => 'api_find_template',
            ],
            [
                'name' => 'date_to_time_ago',
                'callable' => 'Display::dateToStringAgoAndLongDate',
            ],
            [
                'name' => 'remove_xss',
                'callable' => 'Security::remove_XSS',
            ],
        ];

        foreach ($filters as $filter) {
            if (is_array($filter)) {
                $this->twig->addFilter(new Twig_SimpleFilter($filter['name'], $filter['callable']));
            } else {
                $this->twig->addFilter(new Twig_SimpleFilter($filter, $filter));
            }
        }

        $functions = [
            ['name' => 'get_tutors_names', 'callable' => 'Template::returnTutorsNames'],
            ['name' => 'get_teachers_names', 'callable' => 'Template::returnTeachersNames'],
            ['name' => 'api_is_platform_admin', 'callable' => 'api_is_platform_admin'],
        ];

        foreach ($functions as $function) {
            $this->twig->addFunction(new Twig_SimpleFunction($function['name'], $function['callable']));
        }

        // Setting system variables
        $this->set_system_parameters();

        // Setting user variables
        $this->set_user_parameters();

        // Setting course variables
        $this->set_course_parameters();

        // Setting administrator variables
        $this->setAdministratorParams();
        //$this->setCSSEditor();

        // Header and footer are showed by default
        $this->set_footer($show_footer);
        $this->set_header($show_header);

        // Extra class for the main cm-content div
        global $htmlContentExtraClass;
        $this->setExtraContentClass($htmlContentExtraClass);

        $this->set_header_parameters($sendHeaders);
        $this->set_footer_parameters();

        $defaultStyle = api_get_configuration_value('default_template');
        if (!empty($defaultStyle)) {
            $this->templateFolder = $defaultStyle;
        }

        $this->assign('template', $this->templateFolder);
        $this->assign('locale', api_get_language_isocode());
        $this->assign('login_class', null);

        $allow = api_get_configuration_value('show_language_selector_in_menu');
        if ($allow) {
            $this->assign('language_form', api_display_language_form());
        }

        if (api_get_configuration_value('notification_event')) {
            $this->assign('notification_event', '1');
        }

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
     * Return the item's url key:.
     *
     *      c_id=xx&id=xx
     *
     * @param object $item
     *
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
                        'data-title' => get_lang('Help'),
                    ]
                );
                $content .= '</div>';
            }
        }
        $this->assign('help_content', $content);
    }

    /**
     * Use template system to parse the actions menu.
     *
     * @todo finish it!
     */
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
     * Shortcut to display a 1 col layout (index.php).
     * */
    public function display_one_col_template(bool $clearFlashMessages = true)
    {
        $tpl = $this->get_template('layout/layout_1_col.tpl');
        $this->display($tpl, $clearFlashMessages);
    }

    /**
     * Shortcut to display a 2 col layout (userportal.php).
     */
    public function display_two_col_template()
    {
        $tpl = $this->get_template('layout/layout_2_col.tpl');
        $this->display($tpl);
    }

    /**
     * Displays an empty template.
     */
    public function display_blank_template()
    {
        $tpl = $this->get_template('layout/blank.tpl');
        $this->display($tpl);
    }

    /**
     * Displays an empty template.
     */
    public function displayBlankTemplateNoHeader()
    {
        $tpl = $this->get_template('layout/blank_no_header.tpl');
        $this->display($tpl);
    }

    /**
     * Displays an empty template.
     */
    public function display_no_layout_template()
    {
        $tpl = $this->get_template('layout/no_layout.tpl');
        $this->display($tpl);
    }

    /**
     * Sets the footer visibility.
     *
     * @param bool true if we show the footer
     */
    public function set_footer($status)
    {
        $this->show_footer = $status;
        $this->assign('show_footer', $status);
    }

    /**
     * return true if toolbar has to be displayed for user.
     *
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
     * Sets the header visibility.
     *
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

        // Only if course is available
        $courseToolBar = '';
        $origin = api_get_origin();
        $show_course_navigation_menu = '';
        if (!empty($this->course_id) && $this->user_is_logged_in) {
            if ($origin !== 'embeddable' && api_get_setting('show_toolshortcuts') !== 'false') {
                // Course toolbar
                $courseToolBar = CourseHome::show_navigation_tool_shortcuts();
            }
            if (api_get_setting('show_navigation_menu') != 'false') {
                // Course toolbar
                $show_course_navigation_menu = CourseHome::show_navigation_menu();
            }
        }
        $this->assign('show_course_shortcut', $courseToolBar);
        $this->assign('show_course_navigation_menu', $show_course_navigation_menu);
    }

    /**
     * Sets an extra class for the main cm-content div.
     * To use, give a new row to $htmlContentExtraClass like so: `$htmlContentExtraClass[] = 'feature-item-user-skill-on';`
     * before any Display::display_header() call.
     */
    public function setExtraContentClass($htmlContentExtraClass): void
    {
        if (empty($htmlContentExtraClass)) {
            $extraClass = '';
        } else {
            if (is_array($htmlContentExtraClass)) {
                $extraClass = implode(' ', $htmlContentExtraClass);
            } else {
                $extraClass = $htmlContentExtraClass;
            }
            $extraClass = Security::remove_XSS($extraClass);
            $extraClass = trim($extraClass);
            $extraClass = ' class="'.$extraClass.'"';
        }
        $this->assign('html_content_extra_class', $extraClass);
    }

    /**
     * Returns the sub-folder and filename for the given tpl file.
     *
     * If template not found in overrides/ or custom template folder, the default template will be used.
     *
     * @param string $name
     *
     * @return string
     */
    public static function findTemplateFilePath($name)
    {
        $sysTemplatePath = api_get_path(SYS_TEMPLATE_PATH);

        // Check if the tpl file is present in the main/template/overrides/ dir
        // Overrides is a special directory meant for temporary template
        // customization. It must be taken into account before anything else
        if (is_readable($sysTemplatePath."overrides/$name")) {
            return "overrides/$name";
        }

        $defaultFolder = api_get_configuration_value('default_template');

        // If a template folder has been manually defined, search for the right
        // file, and if not found, go for the same file in the default template
        if ($defaultFolder && $defaultFolder != 'default') {
            // Avoid missing template error, use the default file.
            if (file_exists($sysTemplatePath."$defaultFolder/$name")) {
                return "$defaultFolder/$name";
            }
        }

        return "default/$name";
    }

    /**
     * Call non-static for Template::findTemplateFilePath.
     *
     * @see Template::findTemplateFilePath()
     *
     * @param string $name
     *
     * @return string
     */
    public function get_template($name)
    {
        return api_find_template($name);
    }

    /**
     * Get CSS themes sub-directory.
     *
     * @param string $theme
     *
     * @return string with a trailing slash, e.g. 'themes/chamilo_red/'
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
     * Set system parameters from api_get_configuration into _s array for use in TPLs
     * Also fills the _p array from getWebPaths().
     *
     * @uses \self::getWebPaths()
     */
    public function set_system_parameters()
    {
        // Get the interface language from global.inc.php
        global $language_interface;
        $this->theme = api_get_visual_theme();
        if (!empty($this->preview_theme)) {
            $this->theme = $this->preview_theme;
        }

        $this->themeDir = self::getThemeDir($this->theme);

        // Setting app paths/URLs
        $this->assign('_p', $this->getWebPaths());

        // Here we can add system parameters that can be use in any template
        $_s = [
            'software_name' => api_get_configuration_value('software_name'),
            'system_version' => api_get_configuration_value('system_version'),
            'site_name' => api_get_setting('siteName'),
            'institution' => api_get_setting('Institution'),
            'institution_url' => api_get_setting('InstitutionUrl'),
            'date' => api_format_date('now', DATE_FORMAT_LONG),
            'timezone' => api_get_timezone(),
            'gamification_mode' => api_get_setting('gamification_mode'),
            'language_interface' => $language_interface,
        ];
        $this->assign('_s', $_s);
    }

    /**
     * Set theme, include mainstream CSS files.
     *
     * @see setCssCustomFiles() for additional CSS sheets
     */
    public function setCssFiles()
    {
        global $disable_js_and_css_files;
        $css = [];

        $webPublicPath = api_get_path(WEB_PUBLIC_PATH);
        $webJsPath = api_get_path(WEB_LIBRARY_JS_PATH);

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
            'select2/dist/css/select2.min.css',
        ];

        $hide = api_get_configuration_value('hide_flag_language_switcher');

        if ($hide === false) {
            $bowerCSSFiles[] = 'flag-icon-css/css/flag-icon.min.css';
        }

        foreach ($bowerCSSFiles as $file) {
            $css[] = api_get_cdn_path($webPublicPath.'assets/'.$file);
        }

        $css[] = $webJsPath.'mediaelement/plugins/vrview/vrview.css';

        $features = api_get_configuration_value('video_features');
        $defaultFeatures = [
            'playpause',
            'current',
            'progress',
            'duration',
            'tracks',
            'volume',
            'fullscreen',
            'vrview',
            'markersrolls',
        ];

        if (!empty($features) && isset($features['features'])) {
            foreach ($features['features'] as $feature) {
                if ($feature === 'vrview') {
                    continue;
                }
                $css[] = $webJsPath."mediaelement/plugins/$feature/$feature.min.css";
                $defaultFeatures[] = $feature;
            }
        }

        $css[] = $webJsPath.'chosen/chosen.css';

        if (api_is_global_chat_enabled()) {
            $css[] = $webJsPath.'chat/css/chat.css';
        }
        $css_file_to_string = '';
        foreach ($css as $file) {
            $css_file_to_string .= api_get_css($file);
        }

        if (!$disable_js_and_css_files) {
            $this->assign('css_static_file_to_string', $css_file_to_string);
        }

        $defaultFeatures = implode("','", $defaultFeatures);
        $this->assign('video_features', $defaultFeatures);
    }

    /**
     * Prepare custom CSS to be added at the very end of the <head> section.
     *
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
        if (CustomPages::enabled()) {
            $cssCustomPage = api_get_path(SYS_CSS_PATH).$this->themeDir."custompage.css";
            if (is_file($cssCustomPage)) {
                $css[] = api_get_path(WEB_CSS_PATH).$this->themeDir.'custompage.css';
            } else {
                $css[] = api_get_path(WEB_CSS_PATH).'custompage.css';
            }
        }

        $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).$this->themeDir.'default.css');
        $css[] = api_get_cdn_path(ChamiloApi::getEditorBlockStylePath());

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

            $style_print = api_get_css(
                api_get_print_css(false, true),
                'print'
            );
            $this->assign('css_style_print', $style_print);
        }

        // Logo
        $logo = return_logo($this->theme);
        $logoPdf = return_logo($this->theme, false);
        $this->assign('logo', $logo);
        $this->assign('logo_pdf', $logoPdf);
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
        $js_files = [
            'chosen/chosen.jquery.min.js',
            'mediaelement/plugins/vrview/vrview.js',
            'mediaelement/plugins/markersrolls/markersrolls.min.js',
        ];

        if (api_get_setting('accessibility_font_resize') === 'true') {
            $js_files[] = 'fontresize.js';
        }

        $js_file_to_string = '';
        $bowerJsFiles = [
            'modernizr/modernizr.js',
            'jquery/dist/jquery.min.js',
            'bootstrap/dist/js/bootstrap.min.js',
            'jquery-ui/jquery-ui.min.js',
            'jqueryui-touch-punch/jquery.ui.touch-punch.min.js',
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
            "select2/dist/js/i18n/$isoCode.js",
            'js-cookie/src/js.cookie.js',
        ];

        if ($renderers = api_get_configuration_sub_value('video_player_renderers/renderers')) {
            foreach ($renderers as $renderName) {
                if ('youtube' === $renderName) {
                    continue;
                }

                $bowerJsFiles[] = "mediaelement/build/renderers/$renderName.min.js";
            }
        }

        $viewBySession = api_get_setting('my_courses_view_by_session') === 'true';

        if ($viewBySession || api_is_global_chat_enabled()) {
            // Do not include the global chat in LP
            if ($this->show_learnpath == false &&
                $this->show_footer == true &&
                $this->hide_global_chat == false
            ) {
                $js_files[] = 'chat/js/chat.js';
                $bowerJsFiles[] = 'linkifyjs/linkify.js';
                $bowerJsFiles[] = 'linkifyjs/linkify-jquery.js';
            }
        }

        $features = api_get_configuration_value('video_features');
        if (!empty($features) && isset($features['features'])) {
            foreach ($features['features'] as $feature) {
                if ($feature === 'vrview') {
                    continue;
                }
                $js_files[] = "mediaelement/plugins/$feature/$feature.min.js";
            }
        }

        if (CHAMILO_LOAD_WYSIWYG === true) {
            $bowerJsFiles[] = 'ckeditor/ckeditor.js';
        }

        if (api_get_setting('include_asciimathml_script') === 'true') {
            $bowerJsFiles[] = 'MathJax/MathJax.js?config=TeX-MML-AM_HTMLorMML';
        }

        // If not English and the language is supported by timepicker, localize
        $assetsPath = api_get_path(SYS_PUBLIC_PATH).'assets/';
        if ($isoCode != 'en') {
            if (is_file($assetsPath.'jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-'.$isoCode.'.js') && is_file($assetsPath.'jquery-ui/ui/minified/i18n/datepicker-'.$isoCode.'.min.js')) {
                $bowerJsFiles[] = 'jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-'.$isoCode.'.js';
                $bowerJsFiles[] = 'jquery-ui/ui/minified/i18n/datepicker-'.$isoCode.'.min.js';
            }
        }

        foreach ($bowerJsFiles as $file) {
            $js_file_to_string .= '<script src="'.api_get_cdn_path(api_get_path(WEB_PUBLIC_PATH).'assets/'.$file).'"></script>'."\n";
        }

        foreach ($js_files as $file) {
            $js_file_to_string .= api_get_js($file);
        }

        // Loading email_editor js
        if (api_get_setting('allow_email_editor') === 'true') {
            $link = 'email_editor.php';
            if (!api_is_anonymous()) {
                $this->assign('email_editor', $link);
                $template = $this->get_template('mail_editor/email_link.js.tpl');
                $js_file_to_string .= $this->fetch($template);
            } else {
                if (api_get_configuration_value('allow_email_editor_for_anonymous')) {
                    $link = 'email_editor_external.php';
                    $this->assign('email_editor', $link);
                    $template = $this->get_template('mail_editor/email_link.js.tpl');
                    $js_file_to_string .= $this->fetch($template);
                }
            }
        }

        if (!$disable_js_and_css_files) {
            $this->assign('js_file_to_string', $js_file_to_string);

            $extraHeaders = '<script>var _p = '.json_encode($this->getWebPaths(), JSON_PRETTY_PRINT).'</script>';
            // Adding jquery ui by default
            $extraHeaders .= api_get_jquery_ui_js();
            if (isset($htmlHeadXtra) && $htmlHeadXtra) {
                foreach ($htmlHeadXtra as &$this_html_head) {
                    $extraHeaders .= $this_html_head."\n";
                }
            }

            $ajax = api_get_path(WEB_AJAX_PATH);
            $courseId = api_get_course_id();
            if (empty($courseId)) {
                $courseLogoutCode = '
                <script>
                function courseLogout() {
                }
                </script>';
            } else {
                $courseLogoutCode = "
                <script>
                var logOutUrl = '".$ajax."course.ajax.php?a=course_logout&".api_get_cidreq()."';
                function courseLogout() {
                    $.ajax({
                        async : false,
                        url: logOutUrl,
                        success: function (data) {
                            return 1;
                        }
                    });
                }
                </script>";
            }

            $extraHeaders .= $courseLogoutCode;
            $this->assign('extra_headers', $extraHeaders);
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
        $js_files = [];
        $bower = '';
        if (api_is_global_chat_enabled()) {
            //Do not include the global chat in LP
            if ($this->show_learnpath == false && $this->show_footer == true && $this->hide_global_chat == false) {
                $js_files[] = 'chat/js/chat.js';
                $bower .= '<script src="'.api_get_path(WEB_PUBLIC_PATH).'assets/linkifyjs/linkify.js"></script>';
                $bower .= '<script src="'.api_get_path(WEB_PUBLIC_PATH).'assets/linkifyjs/linkify-jquery.js"></script>';
            }
        }
        $js_file_to_string = '';
        foreach ($js_files as $js_file) {
            $js_file_to_string .= api_get_js($js_file);
        }
        if (!$disable_js_and_css_files) {
            $this->assign('js_file_to_string_post', $js_file_to_string.$bower);
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
     * Sets the plugin content in a template variable.
     *
     * @param string $pluginRegion
     */
    public function set_plugin_region($pluginRegion)
    {
        if (!empty($pluginRegion)) {
            $regionContent = $this->plugin->load_region(
                $pluginRegion,
                $this,
                $this->force_plugin_load
            );

            $pluginList = $this->plugin->getInstalledPlugins(false);
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
     *
     * @return string
     */
    public function fetch($template = null)
    {
        $template = $this->twig->loadTemplate($template);

        return $template->render($this->params);
    }

    /**
     * @param string $variable
     * @param mixed  $value
     */
    public function assign($variable, $value = '')
    {
        $this->params[$variable] = $value;
    }

    /**
     * Render the template.
     *
     * @param string $template           The template path
     * @param bool   $clearFlashMessages Clear the $_SESSION variables for flash messages
     */
    public function display($template, $clearFlashMessages = true)
    {
        $this->assign('page_origin', api_get_origin());
        $this->assign('flash_messages', Display::getFlashToString());

        if ($clearFlashMessages) {
            Display::cleanFlashMessages();
        }

        echo $this->twig->render($template, $this->params);
    }

    /**
     * Adds a body class for login pages.
     */
    public function setLoginBodyClass()
    {
        $this->assign('login_class', 'section-login');
    }

    /**
     * The theme that will be used if the database is not working.
     *
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
                api_display_language_form(true, true)
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

                    if (api_get_setting('allow_registration') === 'confirmation') {
                        $message = get_lang('AccountNotConfirmed').PHP_EOL;
                        $message .= Display::url(
                            get_lang('ReSendConfirmationMail'),
                            api_get_path(WEB_PATH).'main/auth/resend_confirmation_mail.php',
                            ['class' => 'alert-link']
                        );
                    }
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

        return Display::return_message($message, 'error', false);
    }

    public static function displayCASLoginButton($label = null)
    {
        $form = new FormValidator(
            'form-cas-login',
            'POST',
            $_SERVER['REQUEST_URI'],
            null,
            null,
            FormValidator::LAYOUT_BOX_NO_LABEL
        );
        $form->addHidden('forceCASAuthentication', 1);
        $form->addButton(
            'casLoginButton',
            is_null($label) ? sprintf(get_lang('LoginWithYourAccount'), api_get_setting("Institution")) : $label,
            api_get_setting("casLogoURL"),
            'primary',
            null,
            'btn-block'
        );

        return $form->returnForm();
    }

    public static function displayCASLogoutButton($label = null)
    {
        $form = new FormValidator(
            'form-cas-logout',
            'GET',
            api_get_path(WEB_PATH),
            null,
            null,
            FormValidator::LAYOUT_BOX_NO_LABEL
        );
        $form->addHidden('logout', 1);
        $form->addButton(
            'casLogoutButton',
            is_null($label) ? sprintf(get_lang('LogoutWithYourAccountFromX'), api_get_setting("Institution")) : $label,
            api_get_setting("casLogoURL"),
            'primary',
            null,
            'btn-block'
        );

        return $form->returnForm();
    }

    /**
     * @throws Exception
     *
     * @return string
     */
    public static function displayLoginForm()
    {
        // Get the $cas array from app/config/auth.conf.php
        global $cas;

        if (is_array($cas) && array_key_exists('replace_login_form', $cas) && $cas['replace_login_form']) {
            return self::displayCASLoginButton();
        }

        $form = new FormValidator(
            'formLogin',
            'POST',
            null,
            null,
            null,
            FormValidator::LAYOUT_BOX_NO_LABEL
        );
        $params = [
            'id' => 'login',
            'autofocus' => 'autofocus',
            'icon' => 'user fa-fw',
            'placeholder' => get_lang('UserName'),
        ];
        $browserAutoCapitalize = false;
        // Avoid showing the autocapitalize option if the browser doesn't
        // support it: this attribute is against the HTML5 standard
        if (api_browser_support('autocapitalize')) {
            $browserAutoCapitalize = false;
            $params['autocapitalize'] = 'none';
        }
        $form->addText(
            'login',
            get_lang('UserName'),
            true,
            $params
        );
        $params = [
            'id' => 'password',
            'icon' => 'lock fa-fw',
            'placeholder' => get_lang('Pass'),
        ];
        if ($browserAutoCapitalize) {
            $params['autocapitalize'] = 'none';
        }
        $form->addElement(
            'password',
            'password',
            get_lang('Pass'),
            $params
        );
        // Captcha
        $captcha = api_get_setting('allow_captcha');
        $allowCaptcha = $captcha === 'true';

        if ($allowCaptcha) {
            $useCaptcha = isset($_SESSION['loginFailed']) ? $_SESSION['loginFailed'] : null;
            if ($useCaptcha) {
                $ajax = api_get_path(WEB_AJAX_PATH).'form.ajax.php?a=get_captcha';
                $options = [
                    'width' => 250,
                    'height' => 90,
                    'callback' => $ajax.'&var='.basename(__FILE__, '.php'),
                    'sessionVar' => basename(__FILE__, '.php'),
                    'imageOptions' => [
                        'font_size' => 20,
                        'font_path' => api_get_path(SYS_FONTS_PATH).'opensans/',
                        'font_file' => 'OpenSans-Regular.ttf',
                        //'output' => 'gif'
                    ],
                ];

                // Minimum options using all defaults (including defaults for Image_Text):
                //$options = array('callback' => 'qfcaptcha_image.php');
                $captchaQuestion = $form->addElement('CAPTCHA_Image', 'captcha_question', '', $options);
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
                    $captchaQuestion
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
            include_once api_get_path(SYS_CODE_PATH).'auth/openid/login.php';
            $html .= '<div>'.openid_form().'</div>';
        }

        $pluginKeycloak = api_get_plugin_setting('keycloak', 'tool_enable') === 'true';
        $plugin = null;
        if ($pluginKeycloak) {
            $pluginUrl = api_get_path(WEB_PLUGIN_PATH).'keycloak/start.php?sso';
            $pluginUrl = Display::url('Keycloak', $pluginUrl, ['class' => 'btn btn-block btn-primary']);
            $html .= '<div style="margin-top: 10px">'.$pluginUrl.'</div>';
        }

        $html .= '<div></div>';

        return $html;
    }

    /**
     * Returns the tutors names for the current course in session
     * Function to use in Twig templates.
     *
     * @return string
     */
    public static function returnTutorsNames()
    {
        $em = Database::getManager();
        $tutors = $em
            ->createQuery('
                SELECT u FROM ChamiloUserBundle:User u
                INNER JOIN ChamiloCoreBundle:SessionRelCourseRelUser scu WITH u.id = scu.user
                WHERE scu.status = :teacher_status AND scu.session = :session AND scu.course = :course
            ')
            ->setParameters([
                'teacher_status' => SessionRelCourseRelUser::STATUS_COURSE_COACH,
                'session' => api_get_session_id(),
                'course' => api_get_course_int_id(),
            ])
            ->getResult();

        $names = [];

        /** @var User $tutor */
        foreach ($tutors as $tutor) {
            $names[] = UserManager::formatUserFullName($tutor);
        }

        return implode(CourseManager::USER_SEPARATOR, $names);
    }

    /**
     * Returns the teachers name for the current course
     * Function to use in Twig templates.
     *
     * @return string
     */
    public static function returnTeachersNames()
    {
        $em = Database::getManager();
        $teachers = $em
            ->createQuery('
                SELECT u FROM ChamiloUserBundle:User u
                INNER JOIN ChamiloCoreBundle:CourseRelUser cu WITH u.id = cu.user
                WHERE cu.status = :teacher_status AND cu.course = :course
            ')
            ->setParameters([
                'teacher_status' => User::COURSE_MANAGER,
                'course' => api_get_course_int_id(),
            ])
            ->getResult();

        $names = [];

        /** @var User $teacher */
        foreach ($teachers as $teacher) {
            $names[] = UserManager::formatUserFullName($teacher);
        }

        return implode(CourseManager::USER_SEPARATOR, $names);
    }

    /**
     * @param int $code
     */
    public function setResponseCode($code)
    {
        $this->responseCode = $code;
    }

    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * Assign HTML code to the 'bug_notification' template variable for the side tabs to report issues.
     *
     * @return bool Always return true because there is always a string, even if empty
     */
    public function assignBugNotification()
    {
        //@todo move this in the template
        $rightFloatMenu = '';
        $iconBug = Display::return_icon(
            'bug.png',
            get_lang('ReportABug'),
            [],
            ICON_SIZE_LARGE
        );
        if (api_get_setting('show_link_bug_notification') === 'true' && $this->user_is_logged_in) {
            $rightFloatMenu = '<div class="report">
		        <a href="https://github.com/chamilo/chamilo-lms/wiki/How-to-report-issues" target="_blank">
                    '.$iconBug.'
                </a>
		        </div>';
        }

        if (api_get_setting('show_link_ticket_notification') === 'true' &&
            $this->user_is_logged_in
        ) {
            // by default is project_id = 1
            $defaultProjectId = 1;
            $iconTicket = Display::return_icon(
                'help.png',
                get_lang('Ticket'),
                [],
                ICON_SIZE_LARGE
            );
            $courseInfo = api_get_course_info();
            $courseParams = '';
            if (!empty($courseInfo)) {
                $courseParams = api_get_cidreq();
            }

            $extraParams = '';
            if (api_get_configuration_value('ticket_lp_quiz_info_add')) {
                if (isset($_GET['exerciseId']) && !empty($_GET['exerciseId'])) {
                    $extraParams = '&exerciseId='.(int) $_GET['exerciseId'];
                }

                if (isset($_GET['lp_id']) && !empty($_GET['lp_id'])) {
                    $extraParams .= '&lpId='.(int) $_GET['lp_id'];
                }
            }
            $url = api_get_path(WEB_CODE_PATH).'ticket/tickets.php?project_id='.$defaultProjectId.'&'.$courseParams.$extraParams;
            $allow = TicketManager::userIsAllowInProject(api_get_user_info(), $defaultProjectId);

            if ($allow) {
                $rightFloatMenu .= '<div class="help">
                    <a href="'.$url.'" target="_blank">
                        '.$iconTicket.'
                    </a>
                </div>';
            }
        }

        $this->assign('bug_notification', $rightFloatMenu);

        return true;
    }

    /**
     * Prepare the _c array for template files. The _c array contains
     * information about the current course.
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
        $_c = [
            'id' => $course['real_id'],
            'code' => $course['code'],
            'title' => $course['name'],
            'visibility' => $course['visibility'],
            'language' => $course['language'],
            'directory' => $course['directory'],
            'session_id' => api_get_session_id(),
            'user_is_teacher' => api_is_course_admin(),
            'student_view' => (!empty($_GET['isStudentView']) && $_GET['isStudentView'] == 'true'),
        ];
        $this->assign('course_code', $course['code']);
        $this->assign('_c', $_c);
    }

    /**
     * Prepare the _u array for template files. The _u array contains
     * information about the current user, as returned by
     * api_get_user_info().
     */
    private function set_user_parameters()
    {
        $user_info = [];
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
     * Get an array of all the web paths available (e.g. 'web' => 'https://my.chamilo.site/').
     *
     * @return array
     */
    private function getWebPaths()
    {
        $queryString = empty($_SERVER['QUERY_STRING']) ? '' : $_SERVER['QUERY_STRING'];
        $requestURI = empty($_SERVER['REQUEST_URI']) ? '' : $_SERVER['REQUEST_URI'];

        return [
            'web' => api_get_path(WEB_PATH),
            'web_url' => api_get_web_url(),
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
            'self_basename' => basename(api_get_self()),
            'web_query_vars' => api_htmlentities($queryString),
            'web_self_query_vars' => api_htmlentities($requestURI),
            'web_cid_query' => api_get_cidreq(),
            'web_rel_code' => api_get_path(REL_CODE_PATH),
        ];
    }

    /**
     * Set header parameters.
     *
     * @param bool $sendHeaders send headers
     */
    private function set_header_parameters($sendHeaders)
    {
        global $httpHeadXtra, $interbreadcrumb, $language_file, $_configuration, $this_section;
        $_course = api_get_course_info();
        $nameTools = $this->title;
        $navigation = return_navigation_array();
        $this->menu_navigation = $navigation['menu_navigation'];

        $this->assign('system_charset', api_get_system_encoding());

        if (isset($httpHeadXtra) && $httpHeadXtra) {
            foreach ($httpHeadXtra as &$thisHttpHead) {
                header($thisHttpHead);
            }
        }

        // Get language iso-code for this page - ignore errors
        $this->assign('document_language', api_get_language_isocode());

        $course_title = isset($_course['name']) ? $_course['name'] : null;

        $title_list = [];

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
        $this->setCssFiles();
        $this->set_js_files();
        $this->setCssCustomFiles();

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
        $this->assignFavIcon();
        $this->setHelp();

        $this->assignBugNotification(); //Prepare the 'bug_notification' var for the template

        $this->assignAccessibilityBlock(); //Prepare the 'accessibility' var for the template

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

        $pendingSurveyLink = '';
        $show = api_get_configuration_value('show_pending_survey_in_menu');
        if ($show) {
            $pendingSurveyLink = api_get_path(WEB_CODE_PATH).'survey/pending.php';
        }
        $this->assign('pending_survey_url', $pendingSurveyLink);

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
            self::addHTTPSecurityHeaders();

            $responseCode = $this->getResponseCode();
            if (!empty($responseCode)) {
                switch ($responseCode) {
                    case '404':
                        header("HTTP/1.0 404 Not Found");
                        break;
                }
            }
        }

        $this->assignSocialMeta();
    }

    /**
     * Set footer parameters.
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
                $adminName = get_lang('Manager').' : ';
                $adminName .= Display::encrypted_mailto_link(
                    api_get_setting('emailAdministrator'),
                    $name,
                    '',
                    true
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
                    $users = SessionManager::getCoachesByCourseSession($id_session, $courseId);
                    $links = [];
                    if (!empty($users)) {
                        $coaches = [];
                        foreach ($users as $userId) {
                            $coaches[] = api_get_user_info($userId);
                        }
                        $links = array_column($coaches, 'complete_name_with_message_link');
                    }
                    $count = count($links);
                    if ($count > 1) {
                        $tutor_data .= get_lang('Coachs').' : ';
                        $tutor_data .= array_to_string($links, CourseManager::USER_SEPARATOR);
                    } elseif ($count === 1) {
                        $tutor_data .= get_lang('Coach').' : ';
                        $tutor_data .= array_to_string($links, CourseManager::USER_SEPARATOR);
                    } elseif ($count === 0) {
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
                $teachers = CourseManager::getTeachersFromCourse($courseId);
                if (!empty($teachers)) {
                    $teachersParsed = [];
                    foreach ($teachers as $teacher) {
                        $userId = $teacher['id'];
                        $teachersParsed[] = api_get_user_info($userId);
                    }
                    $links = array_column($teachersParsed, 'complete_name_with_message_link');
                    $label = get_lang('Teacher');
                    if (count($links) > 1) {
                        $label = get_lang('Teachers');
                    }
                    $teacher_data .= $label.' : '.array_to_string($links, CourseManager::USER_SEPARATOR);
                }
                $this->assign('teachers', $teacher_data);
            }
        }
    }

    /**
     * Set administrator variables.
     */
    private function setAdministratorParams()
    {
        $_admin = [
            'email' => api_get_setting('emailAdministrator'),
            'surname' => api_get_setting('administratorSurname'),
            'name' => api_get_setting('administratorName'),
            'telephone' => api_get_setting('administratorTelephone'),
        ];

        $this->assign('_admin', $_admin);
    }

    /**
     * Manage specific HTTP headers security.
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

    /**
     * Assign favicon to the 'favico' template variable.
     *
     * @return bool Always return true because there is always at least one correct favicon.ico
     */
    private function assignFavIcon()
    {
        // Default root chamilo favicon
        $favico = '<link rel="icon" href="'.api_get_path(WEB_PATH).'favicon.png" type="image/png" />';

        //Added to verify if in the current Chamilo Theme exist a favicon
        $favicoThemeUrl = api_get_path(SYS_CSS_PATH).$this->themeDir.'images/';

        //If exist pick the current chamilo theme favicon
        if (is_file($favicoThemeUrl.'favicon.png')) {
            $favico = '<link rel="icon" href="'.api_get_path(WEB_CSS_PATH).$this->themeDir.'images/favicon.png" type="image/png" />';
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
                $homep = api_get_path(WEB_HOME_PATH).$clean_url; //homep for Home Path
                $icon_real_homep = api_get_path(SYS_HOME_PATH).$clean_url;
                //we create the new dir for the new sites
                if (is_file($icon_real_homep.'favicon.ico')) {
                    $favico = '<link rel="icon" href="'.$homep.'favicon.png" type="image/png" />';
                }
            }
        }

        $this->assign('favico', $favico);

        return true;
    }

    /**
     * Assign HTML code to the 'accessibility' template variable (usually shown above top menu).
     *
     * @return bool Always return true (even if empty string)
     */
    private function assignAccessibilityBlock()
    {
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

        return true;
    }

    /**
     * Assign HTML code to the 'social_meta' template variable (usually shown above top menu).
     *
     * @return bool Always return true (even if empty string)
     */
    private function assignSocialMeta()
    {
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
                // so print the normal or course-specific OpenGraph meta tags
                // Check for a course ID
                $courseId = api_get_course_int_id();
                // Check session ID from session/id/about (see .htaccess)
                $sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

                if ($courseId != false) {
                    // If we are inside a course (even if within a session), publish info about the course
                    $course = api_get_course_entity($courseId);
                    // @TODO: support right-to-left in title
                    $socialMeta .= '<meta property="og:title" content="'.$course->getTitle().' - '.$metaTitle.'" />'."\n";
                    $socialMeta .= '<meta property="twitter:title" content="'.$course->getTitle().' - '.$metaTitle.'" />'."\n";
                    $socialMeta .= '<meta property="og:url" content="'.api_get_course_url($course->getCode()).'" />'."\n";

                    $metaDescription = api_get_setting('meta_description');
                    if (!empty($course->getDescription())) {
                        $socialMeta .= '<meta property="og:description" content="'.strip_tags($course->getDescription()).'" />'."\n";
                        $socialMeta .= '<meta property="twitter:description" content="'.strip_tags($course->getDescription()).'" />'."\n";
                    } elseif (!empty($metaDescription)) {
                        $socialMeta .= '<meta property="og:description" content="'.$metaDescription.'" />'."\n";
                        $socialMeta .= '<meta property="twitter:description" content="'.$metaDescription.'" />'."\n";
                    }

                    $picture = CourseManager::getPicturePath($course, true);
                    if (!empty($picture)) {
                        $socialMeta .= '<meta property="og:image" content="'.$picture.'" />'."\n";
                        $socialMeta .= '<meta property="twitter:image" content="'.$picture.'" />'."\n";
                        $socialMeta .= '<meta property="twitter:image:alt" content="'.$course->getTitle().' - '.$metaTitle.'" />'."\n";
                    } else {
                        $socialMeta .= $this->getMetaPortalImagePath($metaTitle);
                    }
                } elseif ($sessionId !== 0) {
                    // If we are on a session "about" screen, publish info about the session
                    $em = Database::getManager();
                    $session = $em->find('ChamiloCoreBundle:Session', $sessionId);

                    $socialMeta .= '<meta property="og:title" content="'.$session->getName().' - '.$metaTitle.'" />'."\n";
                    $socialMeta .= '<meta property="twitter:title" content="'.$session->getName().' - '.$metaTitle.'" />'."\n";
                    $socialMeta .= '<meta property="og:url" content="'.api_get_path(WEB_PATH)."session/{$session->getId()}/about/".'" />'."\n";

                    $sessionValues = new ExtraFieldValue('session');
                    $sessionImage = $sessionValues->get_values_by_handler_and_field_variable($session->getId(), 'image')['value'];
                    $sessionImageSysPath = api_get_path(SYS_UPLOAD_PATH).$sessionImage;

                    if (!empty($sessionImage) && is_file($sessionImageSysPath)) {
                        $sessionImagePath = api_get_path(WEB_UPLOAD_PATH).$sessionImage;
                        $socialMeta .= '<meta property="og:image" content="'.$sessionImagePath.'" />'."\n";
                        $socialMeta .= '<meta property="twitter:image" content="'.$sessionImagePath.'" />'."\n";
                        $socialMeta .= '<meta property="twitter:image:alt" content="'.$session->getName().' - '.$metaTitle.'" />'."\n";
                    } else {
                        $socialMeta .= $this->getMetaPortalImagePath($metaTitle);
                    }
                } else {
                    // Otherwise (not a course nor a session, nor a user, nor a badge), publish portal info
                    $socialMeta .= '<meta property="og:title" content="'.$metaTitle.'" />'."\n";
                    $socialMeta .= '<meta property="twitter:title" content="'.$metaTitle.'" />'."\n";
                    $socialMeta .= '<meta property="og:url" content="'.api_get_path(WEB_PATH).'" />'."\n";

                    $metaDescription = api_get_setting('meta_description');
                    if (!empty($metaDescription)) {
                        $socialMeta .= '<meta property="og:description" content="'.$metaDescription.'" />'."\n";
                        $socialMeta .= '<meta property="twitter:description" content="'.$metaDescription.'" />'."\n";
                    }
                    $socialMeta .= $this->getMetaPortalImagePath($metaTitle);
                }
            }
        }

        $this->assign('social_meta', $socialMeta);

        return true;
    }

    /**
     * Get platform meta image tag (check meta_image_path setting, then use the logo).
     *
     * @param string $imageAlt The alt attribute for the image
     *
     * @return string The meta image HTML tag, or empty
     */
    private function getMetaPortalImagePath($imageAlt = '')
    {
        // Load portal meta image if defined
        $metaImage = api_get_setting('meta_image_path');
        $metaImageSysPath = api_get_path(SYS_PATH).$metaImage;
        $metaImageWebPath = api_get_path(WEB_PATH).$metaImage;
        $portalImageMeta = '';
        if (!empty($metaImage)) {
            if (is_file($metaImageSysPath)) {
                $portalImageMeta = '<meta property="og:image" content="'.$metaImageWebPath.'" />'."\n";
                $portalImageMeta .= '<meta property="twitter:image" content="'.$metaImageWebPath.'" />'."\n";
                $portalImageMeta .= '<meta property="twitter:image:alt" content="'.$imageAlt.'" />'."\n";
            }
        } else {
            if (api_get_configuration_value('mail_header_from_custom_course_logo') == true) {
                // check if current page is a course page
                $courseId = api_get_course_int_id();

                if (!empty($courseId)) {
                    $course = api_get_course_info_by_id($courseId);
                    if (!empty($course) && !empty($course['course_email_image_large'])) {
                        $portalImageMeta = '<meta property="og:image" content="'.$course['course_email_image_large'].'" />'."\n";
                        $portalImageMeta .= '<meta property="twitter:image" content="'.$course['course_email_image_large'].'" />'."\n";
                        $portalImageMeta .= '<meta property="twitter:image:alt" content="'.$imageAlt.'" />'."\n";
                    }
                }
            }
            if (empty($portalImageMeta)) {
                $logo = ChamiloApi::getPlatformLogoPath($this->theme);
                if (!empty($logo)) {
                    $portalImageMeta = '<meta property="og:image" content="'.$logo.'" />'."\n";
                    $portalImageMeta .= '<meta property="twitter:image" content="'.$logo.'" />'."\n";
                    $portalImageMeta .= '<meta property="twitter:image:alt" content="'.$imageAlt.'" />'."\n";
                }
            }
        }

        return $portalImageMeta;
    }
}
