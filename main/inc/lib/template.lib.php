<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

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
        //$this->setResponseCode($responseCode);

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

        /*$template_paths = [
            api_get_path(SYS_CODE_PATH).'template/overrides', // user defined templates
            api_get_path(SYS_CODE_PATH).'template', //template folder
            api_get_path(SYS_PLUGIN_PATH), // plugin folder
            api_get_path(SYS_PATH).'src/ThemeBundle/Resources/views',
        ];*/

        $this->twig = Container::getTwig();

        // Setting system variables
        //$this->set_system_parameters();

        // Setting user variables
        //$this->set_user_parameters();

        // Setting course variables
        //$this->set_course_parameters();

        // Setting administrator variables
        //$this->setAdministratorParams();
        //$this->setCSSEditor();

        // Header and footer are showed by default
        //$this->set_footer($show_footer);
        //$this->set_header($show_header);

        //$this->set_header_parameters($sendHeaders);
        //$this->set_footer_parameters();

        $defaultStyle = api_get_setting('display.default_template');
        if (!empty($defaultStyle)) {
            $this->templateFolder = $defaultStyle;
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
                    Display::return_icon('help.png', get_lang('Help'), null, ICON_SIZE_LARGE),
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
     * Render the template.
     *
     * @param string $template           The template path
     * @param bool   $clearFlashMessages Clear the $_SESSION variables for flash messages
     */
    public function display($template)
    {
        $template = str_replace('tpl', 'html.twig', $template);
        $templateFile = api_get_path(SYS_PATH).'main/template/'.$template;

        $this->loadLegacyParams();

        if (!file_exists($templateFile)) {
            $e = new \Gaufrette\Exception\FileNotFound($templateFile);
            echo $e->getMessage();
            exit;
        }

        $this->returnResponse($this->params, $template);
    }

    /**
     * @param string $template
     *
     * @throws \Twig\Error\Error
     */
    public function displayTemplate($template)
    {
        $this->loadLegacyParams();
        $this->returnResponse($this->params, $template);
    }

    /**
     * Shortcut to display a 1 col layout (index.php).
     * */
    public function display_one_col_template()
    {
        $this->loadLegacyParams();
        $template = '@ChamiloTheme/Layout/layout_one_col.html.twig';
        $this->returnResponse($this->params, $template);
    }

    /**
     * Displays an empty template.
     */
    public function display_blank_template()
    {
        $this->loadLegacyParams();
        $template = '@ChamiloTheme/Layout/blank.html.twig';
        $this->returnResponse($this->params, $template);
    }

    /**
     * Displays an empty template.
     */
    public function displayBlankTemplateNoHeader()
    {
        $this->loadLegacyParams();
        $template = '@ChamiloTheme/Layout/blank_no_header.html.twig';
        $this->returnResponse($this->params, $template);
    }

    /**
     * Displays an empty template.
     */
    public function display_no_layout_template()
    {
        $this->loadLegacyParams();
        $template = '@ChamiloTheme/Layout/no_layout.html.twig';
        $this->returnResponse($this->params, $template);
    }

    /**
     * Displays an empty template.
     */
    public function displaySkillLayout()
    {
        $this->loadLegacyParams();
        $template = '@ChamiloTheme/Layout/skill_layout.html.twig';
        $this->returnResponse($this->params, $template);
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

        //Only if course is available
        $courseToolBar = '';
        $show_course_navigation_menu = '';
        if (!empty($this->course_id) && $this->user_is_logged_in) {
            if (api_get_setting('show_toolshortcuts') !== 'false') {
                // Course toolbar
                $courseToolBar = CourseHome::show_navigation_tool_shortcuts();
            }
            if (api_get_setting('show_navigation_menu') !== 'false') {
                //Course toolbar
                $show_course_navigation_menu = CourseHome::show_navigation_menu();
            }
        }
        $this->assign('show_course_shortcut', $courseToolBar);
        $this->assign('show_course_navigation_menu', $show_course_navigation_menu);
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
        $this->theme = api_get_visual_theme();
        if (!empty($this->preview_theme)) {
            $this->theme = $this->preview_theme;
        }

        $this->assign('theme', $this->theme);

        $this->themeDir = self::getThemeDir($this->theme);

        // Setting app paths/URLs
        //$this->assign('_p', $this->getWebPaths());

        // Here we can add system parameters that can be use in any template
        $_s = [
            'software_name' => api_get_configuration_value('software_name'),
            'system_version' => api_get_configuration_value('system_version'),
            'site_name' => api_get_setting('siteName'),
            'institu_tion' => api_get_setting('Institution'),
            'date' => api_format_date('now', DATE_FORMAT_LONG),
            'timezone' => api_get_timezone(),
            'gamification_mode' => api_get_setting('gamification_mode'),
        ];
        $this->assign('_s', $_s);
    }

    /**
     * Set legacy twig globals in order to be hook in the LegacyListener.php.
     *
     * @return array
     */
    public static function getGlobals()
    {
        $queryString = empty($_SERVER['QUERY_STRING']) ? '' : $_SERVER['QUERY_STRING'];
        $requestURI = empty($_SERVER['REQUEST_URI']) ? '' : $_SERVER['REQUEST_URI'];

        $_p = [
            'web' => api_get_path(WEB_PATH),
            'web_public' => api_get_path(WEB_PUBLIC_PATH),
            'web_url' => api_get_web_url(),
            'web_relative' => api_get_path(REL_PATH),
            'web_course' => api_get_path(WEB_COURSE_PATH),
            'web_main' => api_get_path(WEB_CODE_PATH),
            'web_css' => api_get_path(WEB_CSS_PATH),
            //'web_css_theme' => api_get_path(WEB_CSS_PATH).$this->themeDir,
            'web_ajax' => api_get_path(WEB_AJAX_PATH),
            'web_img' => api_get_path(WEB_IMG_PATH),
            'web_plugin' => api_get_path(WEB_PLUGIN_PATH),
            'web_lib' => api_get_path(WEB_LIBRARY_PATH),
            'web_upload' => api_get_path(WEB_UPLOAD_PATH),
            'web_self' => api_get_self(),
            'web_query_vars' => api_htmlentities($queryString),
            'web_self_query_vars' => api_htmlentities($requestURI),
            'web_cid_query' => api_get_cidreq(),
            'web_rel_code' => api_get_path(REL_CODE_PATH),
        ];

        $_s = [
            'software_name' => api_get_configuration_value('software_name'),
            'system_version' => api_get_configuration_value('system_version'),
            'site_name' => api_get_setting('siteName'),
            'institution' => api_get_setting('Institution'),
            //'date' => api_format_date('now', DATE_FORMAT_LONG),
            'date' => '',
            'timezone' => '',
            //'timezone' => api_get_timezone(),
            'gamification_mode' => api_get_setting('gamification_mode'),
        ];

        //$user_info = api_get_user_info();

        return [
            '_p' => $_p,
            '_s' => $_s,
            //       '_u' => $user_info,
            'template' => 'default', // @todo setup template folder in config.yml;
        ];
    }

    /**
     * Set theme, include mainstream CSS files.
     *
     * @deprecated
     * @see setCssCustomFiles() for additional CSS sheets
     */
    public function setCssFiles()
    {
        global $disable_js_and_css_files;
        $css = [];

        // Default CSS Bootstrap
        $bowerCSSFiles = [
            'fontawesome/css/font-awesome.min.css',
            'jquery-ui/themes/smoothness/theme.css',
            'jquery-ui/themes/smoothness/jquery-ui.min.css',
            'mediaelement/build/mediaelementplayer.min.css',
            'jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.css',
            'bootstrap/dist/css/bootstrap.min.css',
            'jquery.scrollbar/jquery.scrollbar.css',
            //'bootstrap-daterangepicker/daterangepicker.css',
            'bootstrap-select/dist/css/bootstrap-select.min.css',
            'select2/dist/css/select2.min.css',
            'flag-icon-css/css/flag-icon.min.css',
            'mediaelement/plugins/vrview/vrview.css',
        ];

        $features = api_get_configuration_value('video_features');
        $defaultFeatures = ['playpause', 'current', 'progress', 'duration', 'tracks', 'volume', 'fullscreen', 'vrview'];

        if (!empty($features) && isset($features['features'])) {
            foreach ($features['features'] as $feature) {
                if ($feature === 'vrview') {
                    continue;
                }
                $bowerCSSFiles[] = "mediaelement/plugins/$feature/$feature.css";
                $defaultFeatures[] = $feature;
            }
        }

        foreach ($bowerCSSFiles as $file) {
            //$css[] = api_get_path(WEB_PUBLIC_PATH).'assets/'.$file;
        }

        //$css[] = api_get_path(WEB_LIBRARY_PATH).'javascript/chosen/chosen.css';

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

        $defaultFeatures = implode("','", $defaultFeatures);
        $this->assign('video_features', $defaultFeatures);
    }

    /**
     * Sets the "styles" menu in ckEditor.
     *
     * Reads css/themes/xxx/editor.css if exists and shows it in the menu, otherwise it
     * will take the default web/editor.css file
     */
    public function setStyleMenuInCkEditor()
    {
        $cssEditor = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'editor.css');
        if (is_file(api_get_path(SYS_CSS_PATH).$this->themeDir.'editor.css')) {
            $cssEditor = api_get_path(WEB_CSS_PATH).$this->themeDir.'editor.css';
        }
        $this->assign('css_editor', $cssEditor);
    }

    /**
     * Prepare custom CSS to be added at the very end of the <head> section.
     *
     * @see setCssFiles() for the mainstream CSS files
     */
    public function setCssCustomFiles()
    {
        global $disable_js_and_css_files;
        // chamilo CSS
        //$css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'../chamilo.css');

        // Base CSS
        //$css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'base.css');
        $css = [];
        if ($this->show_learnpath) {
            $css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).'scorm.css');
            if (is_file(api_get_path(SYS_CSS_PATH).$this->themeDir.'learnpath.css')) {
                $css[] = api_get_path(WEB_CSS_PATH).$this->themeDir.'learnpath.css';
            }
        }

        //$css[] = api_get_cdn_path(api_get_path(WEB_CSS_PATH).$this->themeDir.'default.css');
        $css_file_to_string = '';
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
        ];

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
            //'bootstrap-daterangepicker/daterangepicker.js',
            'jquery-timeago/jquery.timeago.js',
            'mediaelement/build/mediaelement-and-player.min.js',
            'jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.js',
            'image-map-resizer/js/imageMapResizer.min.js',
            'jquery.scrollbar/jquery.scrollbar.min.js',
            //'readmore-js/readmore.min.js',
            'bootstrap-select/dist/js/bootstrap-select.min.js',
            $selectLink,
            'select2/dist/js/select2.min.js',
            "select2/dist/js/i18n/$isoCode.js",
            'mediaelement/plugins/vrview/vrview.js',
        ];

        $features = api_get_configuration_value('video_features');
        if (!empty($features) && isset($features['features'])) {
            foreach ($features['features'] as $feature) {
                if ($feature === 'vrview') {
                    continue;
                }
                $bowerJsFiles[] = "mediaelement/plugins/$feature/$feature.js";
            }
        }

        if (CHAMILO_LOAD_WYSIWYG === true) {
            $bowerJsFiles[] = 'ckeditor/ckeditor.js';
        }

        if (api_get_setting('include_asciimathml_script') === 'true') {
            $bowerJsFiles[] = 'MathJax/MathJax.js?config=TeX-MML-AM_HTMLorMML';
        }

        if ($isoCode != 'en') {
            $bowerJsFiles[] = 'jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-'.$isoCode.'.js';
            $bowerJsFiles[] = 'jquery-ui/ui/minified/i18n/datepicker-'.$isoCode.'.min.js';
        }

        foreach ($bowerJsFiles as $file) {
            //$js_file_to_string .= '<script type="text/javascript" src="'.api_get_path(WEB_PUBLIC_PATH).'assets/'.$file.'"></script>'."\n";
        }

        foreach ($js_files as $file) {
            //$js_file_to_string .= api_get_js($file);
        }

        // Loading email_editor js
        if (!api_is_anonymous() && api_get_setting('allow_email_editor') == 'true') {
            $template = $this->get_template('mail_editor/email_link.js.tpl');
            $js_file_to_string .= $this->fetch($template);
        }

        if (!$disable_js_and_css_files) {
            $this->assign('js_file_to_string', $js_file_to_string);
            $extraHeaders = '';
            //$extraHeaders = '<script>var _p = '.json_encode($this->getWebPaths(), JSON_PRETTY_PRINT).'</script>';
            //Adding jquery ui by default
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
     * @param array  $params
     * @param string $template
     *
     * @throws \Twig\Error\Error
     */
    public function returnResponse($params, $template)
    {
        $flash = Display::getFlashToString();
        Display::cleanFlashMessages();
        $response = new Response();
        $params['flash_messages'] = $flash;
        $content = Container::getTemplating()->render($template, $params);
        $response->setContent($content);
        $response->send();
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
        if (api_is_global_chat_enabled()) {
            //Do not include the global chat in LP
            if ($this->show_learnpath == false && $this->show_footer == true && $this->hide_global_chat == false) {
                $js_files[] = 'chat/js/chat.js';
            }
        }
        $js_file_to_string = '';
        foreach ($js_files as $js_file) {
            $js_file_to_string .= api_get_js($js_file);
        }
        if (!$disable_js_and_css_files) {
            $this->assign('js_file_to_string_post', $js_file_to_string);
        }
    }

    /**
     * @param string $theme
     *
     * @return string
     */
    public static function getPortalIcon($theme)
    {
        // Default root chamilo favicon
        $icon = '<link rel="shortcut icon" href="'.api_get_path(WEB_PUBLIC_PATH).'favicon.ico" type="image/x-icon" />';

        // Added to verify if in the current Chamilo Theme exist a favicon
        $themeUrl = api_get_path(SYS_CSS_PATH).'themes/'.$theme.'/images/';

        //If exist pick the current chamilo theme favicon
        if (is_file($themeUrl.'favicon.ico')) {
            $icon = '<link rel="shortcut icon" href="'.api_get_path(WEB_PUBLIC_PATH).'build/css/themes/'.$theme.'/images/favicon.ico" type="image/x-icon" />';
        }

        return $icon;
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
            }
        }

        return Display::return_message($message, 'error', false);
    }

    /**
     * @return string
     */
    public function displayLoginForm()
    {
        $form = new FormValidator(
            'form-login',
            'POST',
            api_get_path(WEB_PUBLIC_PATH).'login_check',
            null,
            null,
            FormValidator::LAYOUT_BOX_NO_LABEL
        );
        $params = [
            'id' => '_username',
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
            '_username',
            get_lang('UserName'),
            true,
            $params
        );
        $params = [
            'id' => '_password',
            'icon' => 'lock fa-fw',
            'placeholder' => get_lang('Pass'),
        ];
        if ($browserAutoCapitalize) {
            $params['autocapitalize'] = 'none';
        }
        $form->addElement(
            'password',
            '_password',
            get_lang('Pass'),
            $params
        );

        $token = Chamilo\CoreBundle\Framework\Container::$container->get('security.csrf.token_manager')->getToken('authenticate');
        $form->addHidden('_csrf_token', $token->getValue());

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

    /*s
     * Returns the teachers name for the current course
     * Function to use in Twig templates
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

    /**
     * @param string $code
     */
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
            $url = api_get_path(WEB_CODE_PATH).
                'ticket/tickets.php?project_id='.$defaultProjectId.'&'.$courseParams;
            $rightFloatMenu .= '<div class="help">
                <a href="'.$url.'" target="_blank">
                    '.$iconTicket.'
                </a>
            </div>';
        }

        $this->assign('bug_notification', $rightFloatMenu);

        return true;
    }

    /**
     * Load legacy params.
     */
    private function loadLegacyParams()
    {
        // Set legacy breadcrumb
        global $interbreadcrumb;
        $this->params['legacy_breadcrumb'] = $interbreadcrumb;

        global $htmlHeadXtra;
        $this->params['legacy_javascript'] = $htmlHeadXtra;
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
     * Set header parameters.
     *
     * @deprecated
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
                //header($thisHttpHead);
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
        $this->assignFavIcon(); //Set a 'favico' var for the template
        $this->setHelp();
        $this->assignBugNotification(); //Prepare the 'bug_notification' var for the template

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
        $allow = api_get_configuration_value('certificate.hide_my_certificate_link');
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
        //$menu = menuArray();
        //$this->assign('menu', $menu);

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
            /*header('Content-Type: text/html; charset='.api_get_system_encoding());
            header(
                'X-Powered-By: '.$_configuration['software_name'].' '.substr($_configuration['system_version'], 0, 1)
            );
            self::addHTTPSecurityHeaders();*/

            $responseCode = $this->getResponseCode();
            if (!empty($responseCode)) {
                switch ($responseCode) {
                    case '404':
                        header("HTTP/1.0 404 Not Found");
                        break;
                }
            }
        }

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
     * Set footer parameters.
     */
    private function set_footer_parameters()
    {
        // Loading footer extra content
        if (!api_is_platform_admin()) {
            $extra_footer = trim(api_get_setting('footer_extra_content'));
            if (!empty($extra_footer)) {
                $this->assign('footer_extra_content', $extra_footer);
            }
        }

        // Tutor name
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
            $logo = ChamiloApi::getPlatformLogoPath($this->theme);
            if (!empty($logo)) {
                $portalImageMeta = '<meta property="og:image" content="'.$logo.'" />'."\n";
                $portalImageMeta .= '<meta property="twitter:image" content="'.$logo.'" />'."\n";
                $portalImageMeta .= '<meta property="twitter:image:alt" content="'.$imageAlt.'" />'."\n";
            }
        }

        return $portalImageMeta;
    }
}
