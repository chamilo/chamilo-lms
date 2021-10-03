<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Display
 * Contains several public functions dealing with the display of
 * table data, messages, help topics, ...
 *
 * Include/require it in your code to use its public functionality.
 * There are also several display public functions in the main api library.
 *
 * All public functions static public functions inside a class called Display,
 * so you use them like this: e.g.
 * Display::return_message($message)
 */
class Display
{
    /** @var Template */
    public static $global_template;
    public static $preview_style = null;
    public static $legacyTemplate;

    public function __construct()
    {
    }

    /**
     * @return array
     */
    public static function toolList()
    {
        return [
            'group',
            'work',
            'glossary',
            'forum',
            'course_description',
            'gradebook',
            'attendance',
            'course_progress',
            'notebook',
        ];
    }

    /**
     * Displays the page header.
     *
     * @param string The name of the page (will be showed in the page title)
     * @param string Optional help file name
     * @param string $page_header
     */
    public static function display_header(
        $tool_name = '',
        $help = null,
        $page_header = null
    ) {
        global $interbreadcrumb;
        $interbreadcrumb[] = ['url' => '#', 'name' => $tool_name];

        ob_start();

        return true;
    }

    /**
     * Displays the reduced page header (without banner).
     */
    public static function display_reduced_header()
    {
        ob_start();
        self::$legacyTemplate = '@ChamiloCore/Layout/no_layout.html.twig';

        return true;
    }

    /**
     * Display no header.
     */
    public static function display_no_header()
    {
        global $tool_name, $show_learnpath;
        self::$global_template = new Template(
            $tool_name,
            false,
            false,
            $show_learnpath
        );
    }

    /**
     * Display the page footer.
     */
    public static function display_footer()
    {
        $contents = ob_get_contents();
        if (ob_get_length()) {
            ob_end_clean();
        }
        $tpl = '@ChamiloCore/Layout/layout_one_col.html.twig';
        if (!empty(self::$legacyTemplate)) {
            $tpl = self::$legacyTemplate;
        }
        $response = new Response();
        $params['content'] = $contents;
        global $interbreadcrumb, $htmlHeadXtra;

        $courseInfo = api_get_course_info();
        if (!empty($courseInfo)) {
            $url = $courseInfo['course_public_url'];
            $sessionId = api_get_session_id();
            if (!empty($sessionId)) {
                $url .= '?sid='.$sessionId;
            }

            if (!empty($interbreadcrumb)) {
                array_unshift(
                    $interbreadcrumb,
                    ['name' => $courseInfo['title'], 'url' => $url]
                );
            }
        }

        if (empty($interbreadcrumb)) {
            $interbreadcrumb = [];
        } else {
            $interbreadcrumb = array_filter(
                $interbreadcrumb,
                function ($item) {
                    return isset($item['name']) && !empty($item['name']);
                }
            );
        }

        $params['legacy_javascript'] = $htmlHeadXtra;
        $params['legacy_breadcrumb'] = json_encode($interbreadcrumb);

        Template::setVueParams($params);
        $content = Container::getTwig()->render($tpl, $params);
        $response->setContent($content);
        $response->send();
        exit;
    }

    /**
     * Display the page footer.
     */
    public static function display_reduced_footer()
    {
        return self::display_footer();
    }

    /**
     * Displays the tool introduction of a tool.
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @param string $tool          these are the constants that are used for indicating the tools
     * @param array  $editor_config Optional configuration settings for the online editor.
     *                              return: $tool return a string array list with the "define" in main_api.lib
     *
     * @return string html code for adding an introduction
     */
    public static function display_introduction_section(
        $tool,
        $editor_config = null
    ) {
        // @todo replace introduction section with a vue page.
        return;
    }

    /**
     * @param string $tool
     * @param array  $editor_config
     */
    public static function return_introduction_section(
        $tool,
        $editor_config = null
    ) {
    }

    /**
     * Displays a table.
     *
     * @param array  $header          Titles for the table header
     *                                each item in this array can contain 3 values
     *                                - 1st element: the column title
     *                                - 2nd element: true or false (column sortable?)
     *                                - 3th element: additional attributes for
     *                                th-tag (eg for column-width)
     *                                - 4the element: additional attributes for the td-tags
     * @param array  $content         2D-array with the tables content
     * @param array  $sorting_options Keys are:
     *                                'column' = The column to use as sort-key
     *                                'direction' = SORT_ASC or SORT_DESC
     * @param array  $paging_options  Keys are:
     *                                'per_page_default' = items per page when switching from
     *                                full-    list to per-page-view
     *                                'per_page' = number of items to show per page
     *                                'page_nr' = The page to display
     * @param array  $query_vars      Additional variables to add in the query-string
     * @param array  $form_actions
     * @param string $style           The style that the table will show. You can set 'table' or 'grid'
     * @param string $tableName
     * @param string $tableId
     *
     * @author bart.mollet@hogent.be
     */
    public static function display_sortable_table(
        $header,
        $content,
        $sorting_options = [],
        $paging_options = [],
        $query_vars = null,
        $form_actions = [],
        $style = 'table',
        $tableName = 'tablename',
        $tableId = ''
    ) {
        $column = $sorting_options['column'] ?? 0;
        $default_items_per_page = $paging_options['per_page'] ?? 20;
        $table = new SortableTableFromArray($content, $column, $default_items_per_page, $tableName, null, $tableId);
        if (is_array($query_vars)) {
            $table->set_additional_parameters($query_vars);
        }
        if ('table' === $style) {
            if (is_array($header) && count($header) > 0) {
                foreach ($header as $index => $header_item) {
                    $table->set_header(
                        $index,
                        isset($header_item[0]) ? $header_item[0] : null,
                        isset($header_item[1]) ? $header_item[1] : null,
                        isset($header_item[2]) ? $header_item[2] : null,
                        isset($header_item[3]) ? $header_item[3] : null
                    );
                }
            }
            $table->set_form_actions($form_actions);
            $table->display();
        } else {
            $table->display_grid();
        }
    }

    /**
     * Returns an HTML table with sortable column (through complete page refresh).
     *
     * @param array  $header
     * @param array  $content         Array of row arrays
     * @param array  $sorting_options
     * @param array  $paging_options
     * @param array  $query_vars
     * @param array  $form_actions
     * @param string $style
     *
     * @return string HTML string for array
     */
    public static function return_sortable_table(
        $header,
        $content,
        $sorting_options = [],
        $paging_options = [],
        $query_vars = null,
        $form_actions = [],
        $style = 'table'
    ) {
        ob_start();
        self::display_sortable_table(
            $header,
            $content,
            $sorting_options,
            $paging_options,
            $query_vars,
            $form_actions,
            $style
        );
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Shows a nice grid.
     *
     * @param string grid name (important to create css)
     * @param array header content
     * @param array array with the information to show
     * @param array $paging_options Keys are:
     *                              'per_page_default' = items per page when switching from
     *                              full-    list to per-page-view
     *                              'per_page' = number of items to show per page
     *                              'page_nr' = The page to display
     *                              'hide_navigation' =  true to hide the navigation
     * @param array $query_vars     Additional variables to add in the query-string
     * @param mixed An array with bool values to know which columns show.
     * i.e: $visibility_options= array(true, false) we will only show the first column
     *                Can be also only a bool value. TRUE: show all columns, FALSE: show nothing
     */
    public static function display_sortable_grid(
        $name,
        $header,
        $content,
        $paging_options = [],
        $query_vars = null,
        $form_actions = [],
        $visibility_options = true,
        $sort_data = true,
        $grid_class = []
    ) {
        echo self::return_sortable_grid(
            $name,
            $header,
            $content,
            $paging_options,
            $query_vars,
            $form_actions,
            $visibility_options,
            $sort_data,
            $grid_class
        );
    }

    /**
     * Gets a nice grid in html string.
     *
     * @param string grid name (important to create css)
     * @param array header content
     * @param array array with the information to show
     * @param array $paging_options Keys are:
     *                              'per_page_default' = items per page when switching from
     *                              full-    list to per-page-view
     *                              'per_page' = number of items to show per page
     *                              'page_nr' = The page to display
     *                              'hide_navigation' =  true to hide the navigation
     * @param array $query_vars     Additional variables to add in the query-string
     * @param mixed An array with bool values to know which columns show. i.e:
     *  $visibility_options= array(true, false) we will only show the first column
     *    Can be also only a bool value. TRUE: show all columns, FALSE: show nothing
     * @param bool  true for sorting data or false otherwise
     * @param array grid classes
     *
     * @return string html grid
     */
    public static function return_sortable_grid(
        $name,
        $header,
        $content,
        $paging_options = [],
        $query_vars = null,
        $form_actions = [],
        $visibility_options = true,
        $sort_data = true,
        $grid_class = [],
        $elementCount = 0
    ) {
        $column = 0;
        $default_items_per_page = $paging_options['per_page'] ?? 20;
        $table = new SortableTableFromArray($content, $column, $default_items_per_page, $name);
        $table->total_number_of_items = (int) $elementCount;
        if (is_array($query_vars)) {
            $table->set_additional_parameters($query_vars);
        }

        return $table->display_simple_grid(
            $visibility_options,
            $paging_options['hide_navigation'],
            $default_items_per_page,
            $sort_data,
            $grid_class
        );
    }

    /**
     * Displays a table with a special configuration.
     *
     * @param array $header          Titles for the table header
     *                               each item in this array can contain 3 values
     *                               - 1st element: the column title
     *                               - 2nd element: true or false (column sortable?)
     *                               - 3th element: additional attributes for th-tag (eg for column-width)
     *                               - 4the element: additional attributes for the td-tags
     * @param array $content         2D-array with the tables content
     * @param array $sorting_options Keys are:
     *                               'column' = The column to use as sort-key
     *                               'direction' = SORT_ASC or SORT_DESC
     * @param array $paging_options  Keys are:
     *                               'per_page_default' = items per page when switching from full list to per-page-view
     *                               'per_page' = number of items to show per page
     *                               'page_nr' = The page to display
     * @param array $query_vars      Additional variables to add in the query-string
     * @param array $column_show     Array of binaries 1= show columns 0. hide a column
     * @param array $column_order    An array of integers that let us decide how the columns are going to be sort.
     *                               i.e:  $column_order=array('1''4','3','4'); The 2nd column will be order like the 4th column
     * @param array $form_actions    Set optional forms actions
     *
     * @author Julio Montoya
     */
    public static function display_sortable_config_table(
        $table_name,
        $header,
        $content,
        $sorting_options = [],
        $paging_options = [],
        $query_vars = null,
        $column_show = [],
        $column_order = [],
        $form_actions = []
    ) {
        $column = isset($sorting_options['column']) ? $sorting_options['column'] : 0;
        $default_items_per_page = isset($paging_options['per_page']) ? $paging_options['per_page'] : 20;

        $table = new SortableTableFromArrayConfig(
            $content,
            $column,
            $default_items_per_page,
            $table_name,
            $column_show,
            $column_order
        );

        if (is_array($query_vars)) {
            $table->set_additional_parameters($query_vars);
        }
        // Show or hide the columns header
        if (is_array($column_show)) {
            for ($i = 0; $i < count($column_show); $i++) {
                if (!empty($column_show[$i])) {
                    $val0 = isset($header[$i][0]) ? $header[$i][0] : null;
                    $val1 = isset($header[$i][1]) ? $header[$i][1] : null;
                    $val2 = isset($header[$i][2]) ? $header[$i][2] : null;
                    $val3 = isset($header[$i][3]) ? $header[$i][3] : null;
                    $table->set_header($i, $val0, $val1, $val2, $val3);
                }
            }
        }
        $table->set_form_actions($form_actions);
        $table->display();
    }

    /**
     * Returns a div html string with.
     *
     * @param string $message
     * @param string $type    Example: confirm, normal, warning, error
     * @param bool   $filter  Whether to XSS-filter or not
     *
     * @return string Message wrapped into an HTML div
     */
    public static function return_message($message, $type = 'normal', $filter = true)
    {
        if (empty($message)) {
            return '';
        }

        if ($filter) {
            $message = api_htmlentities(
                $message,
                ENT_QUOTES,
                api_is_xml_http_request() ? 'UTF-8' : api_get_system_encoding()
            );
        }

        $class = '';
        switch ($type) {
            case 'warning':
                $class .= 'alert alert-warning';
                break;
            case 'error':
                $class .= 'alert alert-danger';
                break;
            case 'confirmation':
            case 'confirm':
            case 'success':
                $class .= 'alert alert-success';
                break;
            case 'normal':
            case 'info':
            default:
                $class .= 'alert alert-info';
        }

        return self::div($message, ['class' => $class]);
    }

    /**
     * Returns an encrypted mailto hyperlink.
     *
     * @param string  e-mail
     * @param string  clickable text
     * @param string  optional, class from stylesheet
     * @param bool $addExtraContent
     *
     * @return string encrypted mailto hyperlink
     */
    public static function encrypted_mailto_link(
        $email,
        $clickable_text = null,
        $style_class = '',
        $addExtraContent = false
    ) {
        if (is_null($clickable_text)) {
            $clickable_text = $email;
        }

        // "mailto:" already present?
        if ('mailto:' !== substr($email, 0, 7)) {
            $email = 'mailto:'.$email;
        }

        // Class (stylesheet) defined?
        if ('' !== $style_class) {
            $style_class = ' class="'.$style_class.'"';
        }

        // Encrypt email
        $hmail = '';
        for ($i = 0; $i < strlen($email); $i++) {
            $hmail .= '&#'.ord($email[$i]).';';
        }

        $value = api_get_configuration_value('add_user_course_information_in_mailto');

        if ($value) {
            if ('false' === api_get_setting('allow_email_editor')) {
                $hmail .= '?';
            }

            if (!api_is_anonymous()) {
                $hmail .= '&subject='.Security::remove_XSS(api_get_setting('siteName'));
            }
            if ($addExtraContent) {
                $content = '';
                if (!api_is_anonymous()) {
                    $userInfo = api_get_user_info();
                    $content .= get_lang('User').': '.$userInfo['complete_name']."\n";

                    $courseInfo = api_get_course_info();
                    if (!empty($courseInfo)) {
                        $content .= get_lang('Course').': ';
                        $content .= $courseInfo['name'];
                        $sessionInfo = api_get_session_info(api_get_session_id());
                        if (!empty($sessionInfo)) {
                            $content .= ' '.$sessionInfo['name'].' <br />';
                        }
                    }
                }
                $hmail .= '&body='.rawurlencode($content);
            }
        }

        $hclickable_text = '';
        // Encrypt clickable text if @ is present
        if (strpos($clickable_text, '@')) {
            for ($i = 0; $i < strlen($clickable_text); $i++) {
                $hclickable_text .= '&#'.ord($clickable_text[$i]).';';
            }
        } else {
            $hclickable_text = @htmlspecialchars(
                $clickable_text,
                ENT_QUOTES,
                api_get_system_encoding()
            );
        }
        // Return encrypted mailto hyperlink
        return '<a href="'.$hmail.'"'.$style_class.' class="clickable_email_link">'.$hclickable_text.'</a>';
    }

    /**
     * Prints an <option>-list with all letters (A-Z).
     *
     * @todo This is English language specific implementation.
     * It should be adapted for the other languages.
     *
     * @return string
     */
    public static function get_alphabet_options($selectedLetter = '')
    {
        $result = '';
        for ($i = 65; $i <= 90; $i++) {
            $letter = chr($i);
            $result .= '<option value="'.$letter.'"';
            if ($selectedLetter == $letter) {
                $result .= ' selected="selected"';
            }
            $result .= '>'.$letter.'</option>';
        }

        return $result;
    }

    /**
     * Get the options withing a select box within the given values.
     *
     * @param int   Min value
     * @param int   Max value
     * @param int   Default value
     *
     * @return string HTML select options
     */
    public static function get_numeric_options($min, $max, $selected_num = 0)
    {
        $result = '';
        for ($i = $min; $i <= $max; $i++) {
            $result .= '<option value="'.$i.'"';
            if (is_int($selected_num)) {
                if ($selected_num == $i) {
                    $result .= ' selected="selected"';
                }
            }
            $result .= '>'.$i.'</option>';
        }

        return $result;
    }

    /**
     * Gets the path of an icon.
     *
     * @param string $icon
     * @param int    $size
     *
     * @return string
     */
    public static function returnIconPath($icon, $size = ICON_SIZE_SMALL)
    {
        return self::return_icon($icon, null, null, $size, null, true, false);
    }

    /**
     * This public function returns the htmlcode for an icon.
     *
     * @param string   The filename of the file (in the main/img/ folder
     * @param string   The alt text (probably a language variable)
     * @param array    Additional attributes (for instance height, width, onclick, ...)
     * @param int  The wanted width of the icon (to be looked for in the corresponding img/icons/ folder)
     *
     * @return string An HTML string of the right <img> tag
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University 2006
     * @author Julio Montoya 2010 Function improved, adding image constants
     * @author Yannick Warnier 2011 Added size handler
     *
     * @version Feb 2011
     */
    public static function return_icon(
        $image,
        $alt_text = '',
        $additional_attributes = [],
        $size = ICON_SIZE_SMALL,
        $show_text = true,
        $return_only_path = false,
        $loadThemeIcon = true
    ) {
        $code_path = api_get_path(SYS_PUBLIC_PATH);
        $w_code_path = api_get_path(WEB_PUBLIC_PATH);
        // The following path is checked to see if the file exist. It's
        // important to use the public path (i.e. web/css/) rather than the
        // internal path (/app/Resource/public/css/) because the path used
        // in the end must be the public path
        $alternateCssPath = api_get_path(SYS_PUBLIC_PATH).'css/';
        $alternateWebCssPath = api_get_path(WEB_PUBLIC_PATH).'css/';

        // Avoid issues with illegal string offset for legacy calls to this
        // method with an empty string rather than null or an empty array
        if (empty($additional_attributes)) {
            $additional_attributes = [];
        }

        $image = trim($image);

        if (isset($size)) {
            $size = (int) $size;
        } else {
            $size = ICON_SIZE_SMALL;
        }

        $size_extra = $size.'/';
        $icon = $w_code_path.'img/'.$image;
        $theme = 'themes/chamilo/icons/';

        if ($loadThemeIcon) {
            // @todo with chamilo 2 code
            $theme = 'themes/'.api_get_visual_theme().'/icons/';
            if (is_file($alternateCssPath.$theme.$image)) {
                $icon = $alternateWebCssPath.$theme.$image;
            }
            // Checking the theme icons folder example: app/Resources/public/css/themes/chamilo/icons/XXX
            if (is_file($alternateCssPath.$theme.$size_extra.$image)) {
                $icon = $alternateWebCssPath.$theme.$size_extra.$image;
            } elseif (is_file($code_path.'img/icons/'.$size_extra.$image)) {
                //Checking the main/img/icons/XXX/ folder
                $icon = $w_code_path.'img/icons/'.$size_extra.$image;
            }
        } else {
            if (is_file($code_path.'img/icons/'.$size_extra.$image)) {
                // Checking the main/img/icons/XXX/ folder
                $icon = $w_code_path.'img/icons/'.$size_extra.$image;
            }
        }

        // Special code to enable SVG - refs #7359 - Needs more work
        // The code below does something else to "test out" SVG: for each icon,
        // it checks if there is an SVG version. If so, it uses it.
        // When moving this to production, the return_icon() calls should
        // ask for the SVG version directly
        $svgIcons = api_get_setting('icons_mode_svg');
        if ('true' == $svgIcons && false == $return_only_path) {
            $svgImage = substr($image, 0, -3).'svg';
            if (is_file($code_path.$theme.'svg/'.$svgImage)) {
                $icon = $w_code_path.$theme.'svg/'.$svgImage;
            } elseif (is_file($code_path.'img/icons/svg/'.$svgImage)) {
                $icon = $w_code_path.'img/icons/svg/'.$svgImage;
            }

            if (empty($additional_attributes['height'])) {
                $additional_attributes['height'] = $size;
            }
            if (empty($additional_attributes['width'])) {
                $additional_attributes['width'] = $size;
            }
        }

        if ($return_only_path) {
            return $icon;
        }

        $img = self::img($icon, $alt_text, $additional_attributes);
        if (SHOW_TEXT_NEAR_ICONS == true && !empty($alt_text)) {
            if ($show_text) {
                $img = "$img $alt_text";
            }
        }

        return $img;
    }

    /**
     * Returns the htmlcode for an image.
     *
     * @param string $image_path            the filename of the file (in the main/img/ folder
     * @param string $alt_text              the alt text (probably a language variable)
     * @param array  $additional_attributes (for instance height, width, onclick, ...)
     * @param bool   $filterPath            Optional. Whether filter the image path. Default is true
     *
     * @return string
     *
     * @author Julio Montoya 2010
     */
    public static function img(
        $image_path,
        $alt_text = '',
        $additional_attributes = null,
        $filterPath = true
    ) {
        if (empty($image_path)) {
            return '';
        }
        // Sanitizing the parameter $image_path
        if ($filterPath) {
            $image_path = Security::filter_img_path($image_path);
        }

        // alt text = the image name if there is none provided (for XHTML compliance)
        if ('' == $alt_text) {
            $alt_text = basename($image_path);
        }

        if (empty($additional_attributes)) {
            $additional_attributes = [];
        }

        $additional_attributes['src'] = $image_path;

        if (empty($additional_attributes['alt'])) {
            $additional_attributes['alt'] = $alt_text;
        }
        if (empty($additional_attributes['title'])) {
            $additional_attributes['title'] = $alt_text;
        }

        return self::tag('img', '', $additional_attributes);
    }

    /**
     * Returns the htmlcode for a tag (h3, h1, div, a, button), etc.
     *
     * @param string $tag                   the tag name
     * @param string $content               the tag's content
     * @param array  $additional_attributes (for instance height, width, onclick, ...)
     *
     * @return string
     *
     * @author Julio Montoya 2010
     */
    public static function tag($tag, $content, $additional_attributes = [])
    {
        $attribute_list = '';
        // Managing the additional attributes
        if (!empty($additional_attributes) && is_array($additional_attributes)) {
            $attribute_list = '';
            foreach ($additional_attributes as $key => &$value) {
                $attribute_list .= $key.'="'.$value.'" ';
            }
        }
        //some tags don't have this </XXX>
        if (in_array($tag, ['img', 'input', 'br'])) {
            $return_value = '<'.$tag.' '.$attribute_list.' />';
        } else {
            $return_value = '<'.$tag.' '.$attribute_list.' >'.$content.'</'.$tag.'>';
        }

        return $return_value;
    }

    /**
     * Creates a URL anchor.
     *
     * @param string $name
     * @param string $url
     * @param array  $attributes
     *
     * @return string
     */
    public static function url($name, $url, $attributes = [])
    {
        if (!empty($url)) {
            $url = preg_replace('#&amp;#', '&', $url);
            $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $attributes['href'] = $url;
        }

        return self::tag('a', $name, $attributes);
    }

    /**
     * Creates a div tag.
     *
     * @param string $content
     * @param array  $attributes
     *
     * @return string
     */
    public static function div($content, $attributes = [])
    {
        return self::tag('div', $content, $attributes);
    }

    /**
     * Creates a span tag.
     */
    public static function span($content, $attributes = [])
    {
        return self::tag('span', $content, $attributes);
    }

    /**
     * Displays an HTML input tag.
     */
    public static function input($type, $name, $value, $attributes = [])
    {
        if (isset($type)) {
            $attributes['type'] = $type;
        }
        if (isset($name)) {
            $attributes['name'] = $name;
        }
        if (isset($value)) {
            $attributes['value'] = $value;
        }

        return self::tag('input', '', $attributes);
    }

    /**
     * @param $name
     * @param $value
     * @param array $attributes
     *
     * @return string
     */
    public static function button($name, $value, $attributes = [])
    {
        if (!empty($name)) {
            $attributes['name'] = $name;
        }

        return self::tag('button', $value, $attributes);
    }

    /**
     * Displays an HTML select tag.
     *
     * @param string $name
     * @param array  $values
     * @param int    $default
     * @param array  $extra_attributes
     * @param bool   $show_blank_item
     * @param string $blank_item_text
     *
     * @return string
     */
    public static function select(
        $name,
        $values,
        $default = -1,
        $extra_attributes = [],
        $show_blank_item = true,
        $blank_item_text = ''
    ) {
        $html = '';
        $extra = '';
        $default_id = 'id="'.$name.'" ';
        $extra_attributes = array_merge(['class' => 'form-control'], $extra_attributes);
        foreach ($extra_attributes as $key => $parameter) {
            if ('id' == $key) {
                $default_id = '';
            }
            $extra .= $key.'="'.$parameter.'" ';
        }
        $html .= '<select name="'.$name.'" '.$default_id.' '.$extra.'>';

        if ($show_blank_item) {
            if (empty($blank_item_text)) {
                $blank_item_text = get_lang('Select');
            } else {
                $blank_item_text = Security::remove_XSS($blank_item_text);
            }
            $html .= self::tag(
                'option',
                '-- '.$blank_item_text.' --',
                ['value' => '-1']
            );
        }
        if ($values) {
            foreach ($values as $key => $value) {
                if (is_array($value) && isset($value['name'])) {
                    $value = $value['name'];
                }
                $html .= '<option value="'.$key.'"';

                if (is_array($default)) {
                    foreach ($default as $item) {
                        if ($item == $key) {
                            $html .= ' selected="selected"';
                            break;
                        }
                    }
                } else {
                    if ($default == $key) {
                        $html .= ' selected="selected"';
                    }
                }

                $html .= '>'.$value.'</option>';
            }
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * Creates a tab menu
     * Requirements: declare the jquery, jquery-ui libraries + the jquery-ui.css
     * in the $htmlHeadXtra variable before the display_header
     * Add this script.
     *
     * @param array  $headers       list of the tab titles
     * @param array  $items
     * @param string $id            id of the container of the tab in the example "tabs"
     * @param array  $attributes    for the ul
     * @param array  $ul_attributes
     * @param string $selected
     *
     * @return string
     */
    public static function tabs(
        $headers,
        $items,
        $id = 'tabs',
        $attributes = [],
        $ul_attributes = [],
        $selected = ''
    ) {
        if (empty($headers) || 0 === count($headers)) {
            return '';
        }

        $lis = '';
        $i = 1;
        foreach ($headers as $item) {
            $active = '';
            if (1 == $i) {
                $active = ' active';
            }

            if (!empty($selected)) {
                $active = '';
                if ($selected == $i) {
                    $active = ' active';
                }
            }

            $item = self::tag(
                'a',
                $item,
                [
                    //'href' => '#'.$id.'-'.$i,
                    'href' => 'javascript:void(0)',
                    'class' => 'nav-item nav-link '.$active,
                    '@click' => "openTab =  $i",
                    'id' => $id.$i.'-tab',
                    'data-toggle' => 'tab',
                    'role' => 'tab',
                    'aria-controls' => $id.'-'.$i,
                    'aria-selected' => $selected,
                ]
            );
            $lis .= $item;
            $i++;
        }

        $ul = self::tag(
            'nav',
            $lis,
            [
                'id' => 'nav_'.$id,
                'class' => 'nav nav-tabs',
                'role' => 'tablist',
            ]
        );

        $i = 1;
        $divs = '';
        foreach ($items as $content) {
            $active = '';
            if (1 == $i) {
                $active = ' show active';
            }

            if (!empty($selected)) {
                $active = '';
                if ($selected == $i) {
                    $active = ' show active';
                }
            }

            $divs .= self::tag(
                'div',
                $content,
                [
                    'id' => $id.'-'.$i,
                    'x-show' => "openTab === $i",
                    //'class' => 'tab-pane fade '.$active,
                    //'role' => 'tabpanel',
                    //'aria-labelledby' => $id.$i.'-tab',
                ]
            );
            $i++;
        }

        $attributes['id'] = ''.$id;
        if (empty($attributes['class'])) {
            $attributes['class'] = '';
        }
        $attributes['class'] .= ' tab_wrapper ';
        $attributes['x-data'] = ' { openTab: 1 } ';

        return self::tag(
            'div',
            $ul.
            $divs,
            $attributes
        );
    }

    /**
     * @param $headers
     * @param null $selected
     *
     * @return string
     */
    public static function tabsOnlyLink($headers, $selected = null)
    {
        $id = uniqid('tabs_');
        $i = 1;
        $list = '';
        foreach ($headers as $item) {
            $class = null;
            if ($i == $selected) {
                $class = 'active';
            }
            $item = self::tag(
                'a',
                $item['content'],
                [
                    'id' => $id.'-'.$i,
                    'href' => $item['url'],
                    'class' => 'btn '.$class,
                ]
            );
            $list .= $item;
            $i++;
        }

        return self::toolbarAction($id, [$list]);
    }

    /**
     * In order to display a grid using jqgrid you have to:.
     *
     * @example
     * After your Display::display_header function you have to add the nex javascript code:
     * <script>
     *   echo Display::grid_js('my_grid_name', $url,$columns, $column_model, $extra_params,[]);
     *   // for more information of this function check the grid_js() function
     * </script>
     * //Then you have to call the grid_html
     * echo Display::grid_html('my_grid_name');
     * As you can see both function use the same "my_grid_name" this is very important otherwise nothing will work
     *
     * @param   string  the div id, this value must be the same with the first parameter of Display::grid_js()
     *
     * @return string html
     */
    public static function grid_html($div_id)
    {
        $table = self::tag('table', '', ['id' => $div_id]);
        $table .= self::tag('div', '', ['id' => $div_id.'_pager']);

        return $table;
    }

    /**
     * This is a wrapper to use the jqgrid in Chamilo.
     * For the other jqgrid options visit http://www.trirand.com/jqgridwiki/doku.php?id=wiki:options
     * This function need to be in the ready jquery function
     * example --> $(function() { <?php echo Display::grid_js('grid' ...); ?> }
     * In order to work this function needs the Display::grid_html function with the same div id.
     *
     * @param string $div_id       div id
     * @param string $url          url where the jqgrid will ask for data (if datatype = json)
     * @param array  $column_names Visible columns (you should use get_lang).
     *                             An array in which we place the names of the columns.
     *                             This is the text that appears in the head of the grid (Header layer).
     *                             Example: colname   {name:'date',     index:'date',   width:120, align:'right'},
     * @param array  $column_model the column model :  Array which describes the parameters of the columns.
     *                             This is the most important part of the grid.
     *                             For a full description of all valid values see colModel API. See the url above.
     * @param array  $extra_params extra parameters
     * @param array  $data         data that will be loaded
     * @param string $formatter    A string that will be appended to the JSON returned
     * @param bool   $fixed_width  not implemented yet
     *
     * @return string the js code
     */
    public static function grid_js(
        $div_id,
        $url,
        $column_names,
        $column_model,
        $extra_params,
        $data = [],
        $formatter = '',
        $fixed_width = false
    ) {
        $obj = new stdClass();
        $obj->first = 'first';

        if (!empty($url)) {
            $obj->url = $url;
        }

        // Needed it in order to render the links/html in the grid
        foreach ($column_model as &$columnModel) {
            if (!isset($columnModel['formatter'])) {
                $columnModel['formatter'] = '';
            }
        }

        //This line should only be used/modified in case of having characters
        // encoding problems - see #6159
        //$column_names = array_map("utf8_encode", $column_names);
        $obj->colNames = $column_names;
        $obj->colModel = $column_model;
        $obj->pager = '#'.$div_id.'_pager';
        $obj->datatype = 'json';
        $obj->viewrecords = 'true';
        $obj->guiStyle = 'bootstrap4';
        $obj->iconSet = 'fontAwesomeSolid';
        $all_value = 10000000;

        // Sets how many records we want to view in the grid
        $obj->rowNum = 20;

        // Default row quantity
        if (!isset($extra_params['rowList'])) {
            $extra_params['rowList'] = [20, 50, 100, 500, 1000, $all_value];
            $rowList = api_get_configuration_value('table_row_list');
            if (!empty($rowList) && isset($rowList['options'])) {
                $rowList = $rowList['options'];
                $rowList[] = $all_value;
            }
            $extra_params['rowList'] = $rowList;
        }

        $defaultRow = api_get_configuration_value('table_default_row');
        if (!empty($defaultRow)) {
            $obj->rowNum = (int) $defaultRow;
        }

        $json = '';
        if (!empty($extra_params['datatype'])) {
            $obj->datatype = $extra_params['datatype'];
        }

        // Row even odd style.
        $obj->altRows = true;
        if (!empty($extra_params['altRows'])) {
            $obj->altRows = $extra_params['altRows'];
        }

        if (!empty($extra_params['sortname'])) {
            $obj->sortname = $extra_params['sortname'];
        }

        if (!empty($extra_params['sortorder'])) {
            $obj->sortorder = $extra_params['sortorder'];
        }

        if (!empty($extra_params['rowList'])) {
            $obj->rowList = $extra_params['rowList'];
        }

        if (!empty($extra_params['rowNum'])) {
            $obj->rowNum = $extra_params['rowNum'];
        } else {
            // Try to load max rows from Session
            $urlInfo = parse_url($url);
            if (isset($urlInfo['query'])) {
                parse_str($urlInfo['query'], $query);
                if (isset($query['a'])) {
                    $action = $query['a'];
                    // This value is set in model.ajax.php
                    $savedRows = Session::read('max_rows_'.$action);
                    if (!empty($savedRows)) {
                        $obj->rowNum = $savedRows;
                    }
                }
            }
        }

        if (!empty($extra_params['viewrecords'])) {
            $obj->viewrecords = $extra_params['viewrecords'];
        }

        $beforeSelectRow = null;
        if (isset($extra_params['beforeSelectRow'])) {
            $beforeSelectRow = 'beforeSelectRow: '.$extra_params['beforeSelectRow'].', ';
            unset($extra_params['beforeSelectRow']);
        }

        $beforeProcessing = '';
        if (isset($extra_params['beforeProcessing'])) {
            $beforeProcessing = 'beforeProcessing : function() { '.$extra_params['beforeProcessing'].' },';
            unset($extra_params['beforeProcessing']);
        }

        $beforeRequest = '';
        if (isset($extra_params['beforeRequest'])) {
            $beforeRequest = 'beforeRequest : function() { '.$extra_params['beforeRequest'].' },';
            unset($extra_params['beforeRequest']);
        }

        $gridComplete = '';
        if (isset($extra_params['gridComplete'])) {
            $gridComplete = 'gridComplete : function() { '.$extra_params['gridComplete'].' },';
            unset($extra_params['gridComplete']);
        }

        // Adding extra params
        if (!empty($extra_params)) {
            foreach ($extra_params as $key => $element) {
                // the groupHeaders key gets a special treatment
                if ('groupHeaders' != $key) {
                    $obj->$key = $element;
                }
            }
        }

        // Adding static data.
        if (!empty($data)) {
            $data_var = $div_id.'_data';
            $json .= ' var '.$data_var.' = '.json_encode($data).';';
            $obj->data = $data_var;
            $obj->datatype = 'local';
            $json .= "\n";
        }

        $obj->end = 'end';

        $json_encode = json_encode($obj);

        if (!empty($data)) {
            //Converts the "data":"js_variable" to "data":js_variable,
            // otherwise it will not work
            $json_encode = str_replace('"data":"'.$data_var.'"', '"data":'.$data_var.'', $json_encode);
        }

        // Fixing true/false js values that doesn't need the ""
        $json_encode = str_replace(':"true"', ':true', $json_encode);
        // wrap_cell is not a valid jqgrid attributes is a hack to wrap a text
        $json_encode = str_replace('"wrap_cell":true', 'cellattr : function(rowId, value, rowObject, colModel, arrData) { return \'class = "jqgrid_whitespace"\'; }', $json_encode);
        $json_encode = str_replace(':"false"', ':false', $json_encode);
        $json_encode = str_replace('"formatter":"action_formatter"', 'formatter:action_formatter', $json_encode);
        $json_encode = str_replace('"formatter":"extra_formatter"', 'formatter:extra_formatter', $json_encode);
        $json_encode = str_replace(['{"first":"first",', '"end":"end"}'], '', $json_encode);

        if (api_get_configuration_value('allow_compilatio_tool') &&
            (false !== strpos($_SERVER['REQUEST_URI'], 'work/work.php') ||
             false != strpos($_SERVER['REQUEST_URI'], 'work/work_list_all.php')
            )
        ) {
            $json_encode = str_replace('"function () { compilatioInit() }"',
                'function () { compilatioInit() }',
                $json_encode
            );
        }
        // Creating the jqgrid element.
        $json .= '$("#'.$div_id.'").jqGrid({';
        //$json .= $beforeSelectRow;
        $json .= $gridComplete;
        $json .= $beforeProcessing;
        $json .= $beforeRequest;
        $json .= $json_encode;
        $json .= '});';

        // Grouping headers option
        if (isset($extra_params['groupHeaders'])) {
            $groups = '';
            foreach ($extra_params['groupHeaders'] as $group) {
                //{ "startColumnName" : "courses", "numberOfColumns" : 1, "titleText" : "Order Info" },
                $groups .= '{ "startColumnName" : "'.$group['startColumnName'].'", "numberOfColumns" : '.$group['numberOfColumns'].', "titleText" : "'.$group['titleText'].'" },';
            }
            $json .= '$("#'.$div_id.'").jqGrid("setGroupHeaders", {
                "useColSpanStyle" : false,
                "groupHeaders"    : [
                    '.$groups.'
                ]
            });';
        }

        $all_text = addslashes(get_lang('All'));
        $json .= '$("'.$obj->pager.' option[value='.$all_value.']").text("'.$all_text.'");';
        $json .= "\n";
        // Adding edit/delete icons.
        $json .= $formatter;

        return $json;
    }

    /**
     * @param array $headers
     * @param array $rows
     * @param array $attributes
     *
     * @return string
     */
    public static function table($headers, $rows, $attributes = [])
    {
        if (empty($attributes)) {
            $attributes['class'] = 'data_table';
        }
        $table = new HTML_Table($attributes);
        $row = 0;
        $column = 0;

        // Course headers
        if (!empty($headers)) {
            foreach ($headers as $item) {
                $table->setHeaderContents($row, $column, $item);
                $column++;
            }
            $row = 1;
            $column = 0;
        }

        if (!empty($rows)) {
            foreach ($rows as $content) {
                $table->setCellContents($row, $column, $content);
                $row++;
            }
        }

        return $table->toHtml();
    }

    /**
     * Get the session box details as an array.
     *
     * @todo check session visibility.
     *
     * @param int $session_id
     *
     * @return array Empty array or session array
     *               ['title'=>'...','category'=>'','dates'=>'...','coach'=>'...','active'=>true/false,'session_category_id'=>int]
     */
    public static function getSessionTitleBox($session_id)
    {
        $session_info = api_get_session_info($session_id);
        $generalCoachesNames = implode(
            ' - ',
            SessionManager::getGeneralCoachesNamesForSession($session_id)
        );

        $session = [];
        $session['category_id'] = $session_info['session_category_id'];
        $session['title'] = $session_info['name'];
        $session['dates'] = '';
        $session['coach'] = '';
        if ('true' === api_get_setting('show_session_coach') && $generalCoachesNames) {
            $session['coach'] = get_lang('General coach').': '.$generalCoachesNames;
        }
        $active = false;
        if (('0000-00-00 00:00:00' === $session_info['access_end_date'] &&
            '0000-00-00 00:00:00' === $session_info['access_start_date']) ||
            (empty($session_info['access_end_date']) && empty($session_info['access_start_date']))
        ) {
            if (isset($session_info['duration']) && !empty($session_info['duration'])) {
                $daysLeft = SessionManager::getDayLeftInSession($session_info, api_get_user_id());
                $session['duration'] = $daysLeft >= 0
                    ? sprintf(get_lang('This session has a maximum duration. Only %s days to go.'), $daysLeft)
                    : get_lang('You are already registered but your allowed access time has expired.');
            }
            $active = true;
        } else {
            $dates = SessionManager::parseSessionDates($session_info, true);
            $session['dates'] = $dates['access'];
            //$active = $date_start <= $now && $date_end >= $now;
        }
        $session['active'] = $active;
        $session['session_category_id'] = $session_info['session_category_id'];
        $session['visibility'] = $session_info['visibility'];
        $session['num_users'] = $session_info['nbr_users'];
        $session['num_courses'] = $session_info['nbr_courses'];
        $session['description'] = $session_info['description'];
        $session['show_description'] = $session_info['show_description'];
        //$session['image'] = SessionManager::getSessionImage($session_info['id']);
        $session['url'] = api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_info['id'];

        $entityManager = Database::getManager();
        $fieldValuesRepo = $entityManager->getRepository(ExtraFieldValues::class);
        $extraFieldValues = $fieldValuesRepo->getVisibleValues(
            ExtraField::SESSION_FIELD_TYPE,
            $session_id
        );

        $session['extra_fields'] = [];
        /** @var ExtraFieldValues $value */
        foreach ($extraFieldValues as $value) {
            if (empty($value)) {
                continue;
            }
            $session['extra_fields'][] = [
                'field' => [
                    'variable' => $value->getField()->getVariable(),
                    'display_text' => $value->getField()->getDisplayText(),
                ],
                'value' => $value->getValue(),
            ];
        }

        return $session;
    }

    /**
     * Return the five star HTML.
     *
     * @param string $id              of the rating ul element
     * @param string $url             that will be added (for jquery see hot_courses.tpl)
     * @param array  $point_info      point info array see function CourseManager::get_course_ranking()
     * @param bool   $add_div_wrapper add a div wrapper
     *
     * @return string
     */
    public static function return_rating_system(
        $id,
        $url,
        $point_info = [],
        $add_div_wrapper = true
    ) {
        $number_of_users_who_voted = isset($point_info['users_who_voted']) ? $point_info['users_who_voted'] : null;
        $percentage = isset($point_info['point_average']) ? $point_info['point_average'] : 0;

        if (!empty($percentage)) {
            $percentage = $percentage * 125 / 100;
        }
        $accesses = isset($point_info['accesses']) ? $point_info['accesses'] : 0;
        $star_label = sprintf(get_lang('%s stars out of 5'), $point_info['point_average_star']);

        $html = '<section class="rating-widget">';
        $html .= '<div class="rating-stars"><ul id="stars">';
        $html .= '<li class="star" data-link="'.$url.'&amp;star=1" title="Poor" data-value="1"><i class="fa fa-star fa-fw"></i></li>
                 <li class="star" data-link="'.$url.'&amp;star=2" title="Fair" data-value="2"><i class="fa fa-star fa-fw"></i></li>
                 <li class="star" data-link="'.$url.'&amp;star=3" title="Good" data-value="3"><i class="fa fa-star fa-fw"></i></li>
                 <li class="star" data-link="'.$url.'&amp;star=4" title="Excellent" data-value="4"><i class="fa fa-star fa-fw"></i></li>
                 <li class="star" data-link="'.$url.'&amp;star=5" title="WOW!!!" data-value="5"><i class="fa fa-star fa-fw"></i></li>
        ';
        $html .= '</ul></div>';
        $html .= '</section>';
        $labels = [];

        $labels[] = 1 == $number_of_users_who_voted ? $number_of_users_who_voted.' '.get_lang('Vote') : $number_of_users_who_voted.' '.get_lang('Votes');
        $labels[] = 1 == $accesses ? $accesses.' '.get_lang('Visit') : $accesses.' '.get_lang('Visits');
        $labels[] = $point_info['user_vote'] ? get_lang('Your vote').' ['.$point_info['user_vote'].']' : get_lang('Your vote').' [?] ';

        if (!$add_div_wrapper && api_is_anonymous()) {
            $labels[] = self::tag('span', get_lang('Login to vote'), ['class' => 'error']);
        }

        $html .= self::div(implode(' | ', $labels), ['id' => 'vote_label_'.$id, 'class' => 'vote_label_info']);
        $html .= ' '.self::span(' ', ['id' => 'vote_label2_'.$id]);

        if ($add_div_wrapper) {
            $html = self::div($html, ['id' => 'rating_wrapper_'.$id]);
        }

        return $html;
    }

    /**
     * @param string $title
     * @param string $second_title
     * @param string $size
     * @param bool   $filter
     *
     * @return string
     */
    public static function page_header($title, $second_title = null, $size = 'h2', $filter = true)
    {
        if ($filter) {
            $title = Security::remove_XSS($title);
        }

        if (!empty($second_title)) {
            if ($filter) {
                $second_title = Security::remove_XSS($second_title);
            }
            $title .= "<small> $second_title</small>";
        }

        return '<'.$size.' class="page-header">'.$title.'</'.$size.'>';
    }

    public static function page_header_and_translate($title, $second_title = null)
    {
        $title = get_lang($title);

        return self::page_header($title, $second_title);
    }

    public static function page_subheader($title, $second_title = null, $size = 'h3', $attributes = [])
    {
        if (!empty($second_title)) {
            $second_title = Security::remove_XSS($second_title);
            $title .= "<small> $second_title<small>";
        }
        $subTitle = self::tag($size, Security::remove_XSS($title), $attributes);

        return $subTitle;
    }

    public static function page_subheader2($title, $second_title = null)
    {
        return self::page_header($title, $second_title, 'h4');
    }

    public static function page_subheader3($title, $second_title = null)
    {
        return self::page_header($title, $second_title, 'h5');
    }

    public static function description(array $list): string
    {
        $html = '';
        if (!empty($list)) {
            $html = '<dl class="dl-horizontal">';
            foreach ($list as $item) {
                $html .= '<dt>'.$item['title'].'</dt>';
                $html .= '<dd>'.$item['content'].'</dd>';
            }
            $html .= '</dl>';
        }

        return $html;
    }

    /**
     * @param int    $percentage      int value between 0 and 100
     * @param bool   $show_percentage
     * @param string $extra_info
     * @param string $class           danger/success/infowarning
     *
     * @return string
     */
    public static function bar_progress($percentage, $show_percentage = true, $extra_info = '', $class = '')
    {
        $percentage = (int) $percentage;
        $class = empty($class) ? '' : "progress-bar-$class";

        $div = '<div class="progress">
                <div
                    class="progress-bar progress-bar-striped '.$class.'"
                    role="progressbar"
                    aria-valuenow="'.$percentage.'"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    style="width: '.$percentage.'%;"
                >';
        if ($show_percentage) {
            $div .= $percentage.'%';
        } else {
            if (!empty($extra_info)) {
                $div .= $extra_info;
            }
        }
        $div .= '</div></div>';

        return $div;
    }

    /**
     * @param string $count
     * @param string $type
     *
     * @return string|null
     */
    public static function badge($count, $type = 'warning')
    {
        $class = '';

        switch ($type) {
            case 'success':
                $class = 'bg-success';
                break;
            case 'warning':
                $class = 'bg-warning text-dark';
                break;
            case 'important':
                $class = 'bg-important';
                break;
            case 'info':
                $class = 'bg-info';
                break;
            case 'inverse':
                $class = 'bg-inverse';
                break;
        }

        if (!empty($count)) {
            return ' <span class="badge '.$class.'">'.$count.'</span>';
        }

        return null;
    }

    /**
     * @param array $badge_list
     *
     * @return string
     */
    public static function badgeGroup($list)
    {
        $html = '<div class="badge-group">';
        foreach ($list as $badge) {
            $html .= $badge;
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Return an HTML span element with the badge class and an additional bg-$type class
     */
    public static function label(string $content, string $type = 'default'): string
    {
        $html = '';
        if (!empty($content)) {
            $class = match ($type) {
                'success' => 'success',
                'warning' => 'warning',
                'important', 'danger' => 'danger',
                'info' => 'info',
                'primary' => 'primary',
                default => 'secondary',
            };

            $html = '<span class="badge bg-ch-'.$class.'">';
            $html .= $content;
            $html .= '</span>';
        }

        return $html;
    }

    public static function actions(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $links = '';
        foreach ($items as $value) {
            $attributes = $value['url_attributes'] ?? [];
            $links .= self::url($value['content'], $value['url'], $attributes);
        }

        return self::toolbarAction(uniqid('toolbar', false), [$links]);
    }

    /**
     * Prints a tooltip.
     *
     * @param string $text
     * @param string $tip
     *
     * @return string
     */
    public static function tip($text, $tip)
    {
        if (empty($tip)) {
            return $text;
        }

        return self::span(
            $text,
            ['class' => 'boot-tooltip', 'title' => strip_tags($tip)]
        );
    }

    /**
     * @param array $buttons
     *
     * @return string
     */
    public static function groupButton($buttons)
    {
        $html = '<div class="btn-group" role="group">';
        foreach ($buttons as $button) {
            $html .= $button;
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @todo use twig
     *
     * @param string $title
     * @param array  $elements
     * @param bool   $alignToRight
     *
     * @return string
     */
    public static function groupButtonWithDropDown($title, $elements, $alignToRight = false)
    {
        $id = uniqid('dropdown', false);
        $html = '
        <div class="dropdown inline-block relative">
            <button
                id="'.$id.'"
                type="button"
                class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-indigo-500"
                aria-expanded="false"
                aria-haspopup="true"
            >
              '.$title.'
              <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
            <div
                id="'.$id.'_menu"
                class=" dropdown-menu hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none"
                role="menu"
                aria-orientation="vertical"
                aria-labelledby="menu-button"
                tabindex="-1"
            >
            <div class="py-1" role="none">';
        foreach ($elements as $item) {
            $html .= self::url(
                    $item['title'],
                    $item['href'],
                    [
                        'class' => 'text-gray-700 block px-4 py-2 text-sm',
                        'role' => 'menuitem',
                        'onclick' => $item['onclick'] ?? '',
                        'data-action' => $item['data-action'] ?? '',
                    ]
                );
        }
        $html .= '
            </div>
            </div>
            </div>
            <script>
             document.addEventListener("DOMContentLoaded", function() {
                const button = document.querySelector("#'.$id.'");
                    button.addEventListener("click", (e) => {
                    let menu = document.querySelector("#'.$id.'_menu");
                    if (menu.classList.contains("hidden")) {
                        menu.classList.remove("hidden");
                    } else {
                        menu.classList.add("hidden");
                    }
                });
            });
            </script>';

        return $html;
    }

    /**
     * @param string $file
     * @param array  $params
     *
     * @return string|null
     */
    public static function getMediaPlayer($file, $params = [])
    {
        $fileInfo = pathinfo($file);

        $autoplay = isset($params['autoplay']) && 'true' === $params['autoplay'] ? 'autoplay' : '';
        $id = isset($params['id']) ? $params['id'] : $fileInfo['basename'];
        $width = isset($params['width']) ? 'width="'.$params['width'].'"' : null;
        $class = isset($params['class']) ? ' class="'.$params['class'].'"' : null;

        switch ($fileInfo['extension']) {
            case 'mp3':
            case 'webm':
                $html = '<audio id="'.$id.'" '.$class.' controls '.$autoplay.' '.$width.' src="'.$params['url'].'" >';
                $html .= '<object width="'.$width.'" height="50" type="application/x-shockwave-flash" data="'.api_get_path(WEB_LIBRARY_PATH).'javascript/mediaelement/flashmediaelement.swf">
                            <param name="movie" value="'.api_get_path(WEB_LIBRARY_PATH).'javascript/mediaelement/flashmediaelement.swf" />
                            <param name="flashvars" value="controls=true&file='.$params['url'].'" />
                          </object>';
                $html .= '</audio>';

                return $html;
                break;
            case 'wav':
            case 'ogg':
                $html = '<audio width="300px" controls id="'.$id.'" '.$autoplay.' src="'.$params['url'].'" >';

                return $html;
                break;
        }

        return null;
    }

    /**
     * @param int    $nextValue
     * @param array  $list
     * @param int    $current
     * @param int    $fixedValue
     * @param array  $conditions
     * @param string $link
     * @param bool   $isMedia
     * @param bool   $addHeaders
     * @param array  $linkAttributes
     *
     * @return string
     */
    public static function progressPaginationBar(
        $nextValue,
        $list,
        $current,
        $fixedValue = null,
        $conditions = [],
        $link = null,
        $isMedia = false,
        $addHeaders = true,
        $linkAttributes = []
    ) {
        if ($addHeaders) {
            $pagination_size = 'pagination-mini';
            $html = '<div class="exercise_pagination pagination '.$pagination_size.'"><ul>';
        } else {
            $html = null;
        }
        $affectAllItems = false;
        if ($isMedia && isset($fixedValue) && ($nextValue + 1 == $current)) {
            $affectAllItems = true;
        }
        $localCounter = 0;
        foreach ($list as $itemId) {
            $isCurrent = false;
            if ($affectAllItems) {
                $isCurrent = true;
            } else {
                if (!$isMedia) {
                    $isCurrent = $current == ($localCounter + $nextValue + 1) ? true : false;
                }
            }
            $html .= self::parsePaginationItem(
                $itemId,
                $isCurrent,
                $conditions,
                $link,
                $nextValue,
                $isMedia,
                $localCounter,
                $fixedValue,
                $linkAttributes
            );
            $localCounter++;
        }
        if ($addHeaders) {
            $html .= '</ul></div>';
        }

        return $html;
    }

    /**
     * @param int    $itemId
     * @param bool   $isCurrent
     * @param array  $conditions
     * @param string $link
     * @param int    $nextValue
     * @param bool   $isMedia
     * @param int    $localCounter
     * @param int    $fixedValue
     * @param array  $linkAttributes
     *
     * @return string
     */
    public static function parsePaginationItem(
        $itemId,
        $isCurrent,
        $conditions,
        $link,
        $nextValue = 0,
        $isMedia = false,
        $localCounter = null,
        $fixedValue = null,
        $linkAttributes = []
    ) {
        $defaultClass = 'before';
        $class = $defaultClass;
        foreach ($conditions as $condition) {
            $array = isset($condition['items']) ? $condition['items'] : [];
            $class_to_applied = $condition['class'];
            $type = isset($condition['type']) ? $condition['type'] : 'positive';
            $mode = isset($condition['mode']) ? $condition['mode'] : 'add';
            switch ($type) {
                case 'positive':
                    if (in_array($itemId, $array)) {
                        if ('overwrite' == $mode) {
                            $class = " $defaultClass $class_to_applied";
                        } else {
                            $class .= " $class_to_applied";
                        }
                    }
                    break;
                case 'negative':
                    if (!in_array($itemId, $array)) {
                        if ('overwrite' == $mode) {
                            $class = " $defaultClass $class_to_applied";
                        } else {
                            $class .= " $class_to_applied";
                        }
                    }
                    break;
            }
        }
        if ($isCurrent) {
            $class = 'before current';
        }
        if ($isMedia && $isCurrent) {
            $class = 'before current';
        }
        if (empty($link)) {
            $link_to_show = '#';
        } else {
            $link_to_show = $link.($nextValue + $localCounter);
        }
        $label = $nextValue + $localCounter + 1;
        if ($isMedia) {
            $label = ($fixedValue + 1).' '.chr(97 + $localCounter);
            $link_to_show = $link.$fixedValue.'#questionanchor'.$itemId;
        }
        $link = self::url($label.' ', $link_to_show, $linkAttributes);

        return '<li class = "'.$class.'">'.$link.'</li>';
    }

    /**
     * @param int $current
     * @param int $total
     *
     * @return string
     */
    public static function paginationIndicator($current, $total)
    {
        $html = null;
        if (!empty($current) && !empty($total)) {
            $label = sprintf(get_lang('%s of %s'), $current, $total);
            $html = self::url($label, '#', ['class' => 'btn disabled']);
        }

        return $html;
    }

    /**
     * @param $url
     * @param $currentPage
     * @param $pagesCount
     * @param $totalItems
     *
     * @return string
     */
    public static function getPagination($url, $currentPage, $pagesCount, $totalItems)
    {
        $pagination = '';
        if ($totalItems > 1 && $pagesCount > 1) {
            $pagination .= '<ul class="pagination">';
            for ($i = 0; $i < $pagesCount; $i++) {
                $newPage = $i + 1;
                if ($currentPage == $newPage) {
                    $pagination .= '<li class="active"><a href="'.$url.'&page='.$newPage.'">'.$newPage.'</a></li>';
                } else {
                    $pagination .= '<li><a href="'.$url.'&page='.$newPage.'">'.$newPage.'</a></li>';
                }
            }
            $pagination .= '</ul>';
        }

        return $pagination;
    }

    /**
     * Adds a legacy message in the queue.
     *
     * @param string $message
     */
    public static function addFlash($message)
    {
        // Detect type of message.
        $parts = preg_match('/alert-([a-z]*)/', $message, $matches);
        $type = 'primary';
        if ($parts && isset($matches[1]) && $matches[1]) {
            $type = $matches[1];
        }
        // Detect legacy content of message.
        $result = preg_match('/<div(.*?)\>(.*?)\<\/div>/s', $message, $matches);
        if ($result && isset($matches[2])) {
            Container::getSession()->getFlashBag()->add($type, $matches[2]);
        }
    }

    /**
     * Get the profile edition link for a user.
     *
     * @param int  $userId  The user id
     * @param bool $asAdmin Optional. Whether get the URL for the platform admin
     *
     * @return string The link
     */
    public static function getProfileEditionLink($userId, $asAdmin = false)
    {
        $editProfileUrl = api_get_path(WEB_CODE_PATH).'auth/profile.php';
        if ($asAdmin) {
            $editProfileUrl = api_get_path(WEB_CODE_PATH)."admin/user_edit.php?user_id=".intval($userId);
        }

        return $editProfileUrl;
    }

    /**
     * Get the vCard for a user.
     *
     * @param int $userId The user id
     *
     * @return string *.*vcf file
     */
    public static function getVCardUserLink($userId)
    {
        return api_get_path(WEB_PATH).'main/social/vcard_export.php?userId='.intval($userId);
    }

    /**
     * @param string $content
     * @param string $title
     * @param string $footer
     * @param string $type        primary|success|info|warning|danger
     * @param string $extra
     * @param string $id
     * @param string $customColor
     * @param string $rightAction
     *
     * @return string
     */
    public static function panel(
        $content,
        $title = '',
        $footer = '',
        $type = 'default',
        $extra = '',
        $id = '',
        $customColor = '',
        $rightAction = ''
    ) {
        $headerStyle = '';
        if (!empty($customColor)) {
            $headerStyle = 'style = "color: white; background-color: '.$customColor.'" ';
        }

        $footer = !empty($footer) ? '<p class="card-text"><small class="text-muted">'.$footer.'</small></p>' : '';
        $typeList = ['primary', 'success', 'info', 'warning', 'danger'];
        $style = !in_array($type, $typeList) ? 'default' : $type;

        if (!empty($id)) {
            $id = " id='$id'";
        }
        $cardBody = $title.' '.self::contentPanel($content).' '.$footer;

        return "
            <div $id class=card>
                <div class='flex justify-between items-center py-2'>
                    <div class='relative mt-1 flex'>
                        $title
                    </div>
                    <div>
                        $rightAction
                    </div>
                </div>

                $content
                $footer
            </div>"
        ;
    }

    /**
     * @param string $content
     */
    public static function contentPanel($content): string
    {
        if (empty($content)) {
            return '';
        }

        return '<div class="card-text">'.$content.'</div>';
    }

    /**
     * Get the button HTML with an Awesome Font icon.
     *
     * @param string $text        The button content
     * @param string $url         The url to button
     * @param string $icon        The Awesome Font class for icon
     * @param string $type        Optional. The button Bootstrap class. Default 'default' class
     * @param array  $attributes  The additional attributes
     * @param bool   $includeText
     *
     * @return string The button HTML
     */
    public static function toolbarButton(
        $text,
        $url,
        $icon = 'check',
        $type = null,
        array $attributes = [],
        $includeText = true
    ) {
        $buttonClass = "btn btn-outline-secondary";
        if (!empty($type)) {
            $buttonClass = "btn btn-$type";
        }
        //$icon = self::tag('i', null, ['class' => "fa fa-$icon fa-fw", 'aria-hidden' => 'true']);
        $icon = self::getMdiIcon($icon);
        $attributes['class'] = isset($attributes['class']) ? "$buttonClass {$attributes['class']}" : $buttonClass;
        $attributes['title'] = $attributes['title'] ?? $text;

        if (!$includeText) {
            $text = '<span class="sr-only">'.$text.'</span>';
        }

        return self::url("$icon $text", $url, $attributes);
    }

    public static function toolbarAction(string $id, array $contentList): string
    {
        $contentList = array_filter($contentList);

        if (empty($contentList)) {
            return '';
        }

        $col = count($contentList);
        $html = ' <div id="'.$id.'" class="q-card p-2 mb-4">';
        $html .= ' <div class="flex justify-between '.$col.'">';
        foreach ($contentList as $item) {
            $html .= '<div class="flex p-2 gap-2 ">'.$item.'</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public static function getMdiIcon(string $name, string $additionalClass = null, string $style = null, int $pixelSize = null, string $title = null): string
    {
        $sizeString = '';
        if (!empty($pixelSize)) {
            $sizeString = 'font-size: '.$pixelSize.'px; width: '.$pixelSize.'px; height: '.$pixelSize.'px; ';
        }
        if (empty($style)) {
            $style = '';
        }
        $additionalAttributes = [
            'class' => "mdi-$name mdi v-icon notranslate v-icon--size-default $additionalClass",
            'style' => $sizeString.$style,
            'medium' => '',
            'aria-hidden' => 'true',
        ];
        if (!empty($title)) {
            $additionalAttributes['title'] = htmlentities($title);
        }

        return self::tag(
            'i',
            '',
            $additionalAttributes
        );
    }

    /**
     * Get a HTML code for a icon by Font Awesome.
     *
     * @param string     $name            The icon name. Example: "mail-reply"
     * @param int|string $size            Optional. The size for the icon. (Example: lg, 2, 3, 4, 5)
     * @param bool       $fixWidth        Optional. Whether add the fw class
     * @param string     $additionalClass Optional. Additional class
     *
     * @return string
     */
    public static function returnFontAwesomeIcon(
        $name,
        $size = '',
        $fixWidth = false,
        $additionalClass = ''
    ) {
        $className = "mdi mdi-$name";

        if ($fixWidth) {
            $className .= ' fa-fw';
        }

        switch ($size) {
            case 'xs':
            case 'sm':
            case 'lg':
                $className .= " fa-{$size}";
                break;
            case 2:
            case 3:
            case 4:
            case 5:
                $className .= " fa-{$size}x";
                break;
        }

        if (!empty($additionalClass)) {
            $className .= " $additionalClass";
        }

        $icon = self::tag('em', null, ['class' => $className]);

        return "$icon ";
    }

    /**
     * @param string     $title
     * @param string     $content
     * @param null       $id
     * @param array      $params
     * @param null       $idAccordion
     * @param null       $idCollapse
     * @param bool|true  $open
     * @param bool|false $fullClickable
     *
     * @return string
     *
     * @todo rework function to easy use
     */
    public static function panelCollapse(
        $title,
        $content,
        $id = null,
        $params = [],
        $idAccordion = null,
        $idCollapse = null,
        $open = true,
        $fullClickable = false
    ) {
        if (!empty($idAccordion)) {
            $headerClass = $fullClickable ? 'center-block ' : '';
            $headerClass .= $open ? '' : 'collapsed';
            $contentClass = 'panel-collapse collapse ';
            $contentClass .= $open ? 'in' : '';
            $ariaExpanded = $open ? 'true' : 'false';

            $html = <<<HTML
                <div class="v-card bg-white mx-2" id="$id">
                    <div class="v-card-header text-xl my-2">
                        $title
                    </div>
                    <div class="v-card-text">$content</div>
                </div>
HTML;
        } else {
            if (!empty($id)) {
                $params['id'] = $id;
            }
            $params['class'] = 'v-card bg-white mx-2';
            $html = '';
            if (!empty($title)) {
                $html .= '<div class="v-card-header text-xl my-2">'.$title.'</div>'.PHP_EOL;
            }
            $html .= '<div class="v-card-text">'.$content.'</div>'.PHP_EOL;
            $html = self::div($html, $params);
        }

        return $html;
    }

    /**
     * Returns the string "1 day ago" with a link showing the exact date time.
     *
     * @param string $dateTime in UTC or a DateTime in UTC
     *
     * @return string
     */
    public static function dateToStringAgoAndLongDate($dateTime)
    {
        if (empty($dateTime) || '0000-00-00 00:00:00' === $dateTime) {
            return '';
        }

        if ($dateTime instanceof \DateTime) {
            $dateTime = $dateTime->format('Y-m-d H:i:s');
        }

        return self::tip(
            date_to_str_ago($dateTime),
            api_convert_and_format_date($dateTime, DATE_TIME_FORMAT_LONG)
            //api_get_local_time($dateTime)
        );
    }

    /**
     * @param array  $userInfo
     * @param string $status
     * @param string $toolbar
     *
     * @return string
     */
    public static function getUserCard($userInfo, $status = '', $toolbar = '')
    {
        if (empty($userInfo)) {
            return '';
        }

        if (!empty($status)) {
            $status = '<div class="items-user-status">'.$status.'</div>';
        }

        if (!empty($toolbar)) {
            $toolbar = '<div class="btn-group pull-right">'.$toolbar.'</div>';
        }

        return '<div id="user_card_'.$userInfo['id'].'" class="card d-flex flex-row">
                    <img src="'.$userInfo['avatar'].'" class="rounded" />
                    <h3 class="card-title">'.$userInfo['complete_name'].'</h3>
                    <div class="card-body">
                       <div class="card-title">
                       '.$status.'
                       '.$toolbar.'
                       </div>
                    </div>
                    <hr />
              </div>';
    }

    /**
     * @param string $fileName
     * @param string $fileUrl
     *
     * @return string
     */
    public static function fileHtmlGuesser($fileName, $fileUrl)
    {
        $data = pathinfo($fileName);

        //$content = self::url($data['basename'], $fileUrl);
        $content = '';
        switch ($data['extension']) {
            case 'webm':
            case 'mp4':
            case 'ogg':
                $content = '<video style="width: 400px; height:100%;" src="'.$fileUrl.'"></video>';
                // Allows video to play when loading during an ajax call
                $content .= "<script>jQuery('video:not(.skip), audio:not(.skip)').mediaelementplayer();</script>";
                break;
            case 'jpg':
            case 'jpeg':
            case 'gif':
            case 'png':
                $content = '<img class="img-responsive" src="'.$fileUrl.'" />';
                break;
            default:
                //$html = self::url($data['basename'], $fileUrl);
                break;
        }
        //$html = self::url($content, $fileUrl, ['ajax']);

        return $content;
    }

    /**
     * @param string $image
     * @param int    $size
     *
     * @return string
     */
    public static function get_icon_path($image, $size = ICON_SIZE_SMALL)
    {
        return self::return_icon($image, '', [], $size, false, true);
    }

    /**
     * @param $id
     *
     * @return array|mixed
     */
    public static function randomColor($id)
    {
        static $colors = [];

        if (!empty($colors[$id])) {
            return $colors[$id];
        } else {
            $color = substr(md5(time() * $id), 0, 6);
            $c1 = hexdec(substr($color, 0, 2));
            $c2 = hexdec(substr($color, 2, 2));
            $c3 = hexdec(substr($color, 4, 2));
            $luminosity = $c1 + $c2 + $c3;

            $type = '#000000';
            if ($luminosity < (255 + 255 + 255) / 2) {
                $type = '#FFFFFF';
            }

            $result = [
                'color' => '#'.$color,
                'luminosity' => $type,
            ];
            $colors[$id] = $result;

            return $result; // example: #fc443a
        }
    }

    public static function noDataView(string $title, string $icon, string $buttonTitle, string $url): string
    {
        $content = '<div id="no-data-view">';
        $content .= '<h3>'.$title.'</h3>';
        $content .= $icon;
        $content .= '<div class="controls">';
        $content .= self::url(
            '<em class="fa fa-plus"></em> '.$buttonTitle,
            $url,
            ['class' => 'btn btn-primary']
        );
        $content .= '</div>';
        $content .= '</div>';

        return $content;
    }

    public static function prose(string $contents): string
    {
        return "
            <div class=''>
                <div class='prose prose-blue'>
                $contents
                </div>
            </div>
            ";
    }
}
