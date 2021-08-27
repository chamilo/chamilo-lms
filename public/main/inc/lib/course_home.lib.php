<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\Common\Collections\Criteria;

/**
 * Class CourseHome.
 */
class CourseHome
{
    /**
     * Gets the tools of a certain category. Returns an array expected
     * by show_tools_category().
     *
     * @param string $course_tool_category contains the category of tools to
     *                                     display: "toolauthoring", "toolinteraction", "tooladmin",
     *                                     "tooladminplatform", "toolplugin"
     * @param int    $courseId             Optional
     * @param int    $sessionId            Optional
     *
     * @return array
     */
    public static function get_tools_category(
        $course_tool_category,
        $courseId = 0,
        $sessionId = 0
    ) {
        $course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);
        $is_platform_admin = api_is_platform_admin();
        $all_tools_list = [];

        // Condition for the session
        $sessionId = $sessionId ?: api_get_session_id();
        $course_id = $courseId ?: api_get_course_int_id();
        $courseInfo = api_get_course_info_by_id($course_id);
        $userId = api_get_user_id();
        $user = api_get_user_entity($userId);
        $condition_session = api_get_session_condition(
            $sessionId,
            true,
            true,
            't.session_id'
        );

        $studentView = api_is_student_view_active();
        $orderBy = ' ORDER BY id ';

        $em = Database::getManager();
        $repo = $em->getRepository('ChamiloCourseBundle:CTool');
        $qb = $repo->createQueryBuilder('tool');

        $criteria = \Doctrine\Common\Collections\Criteria::create();

        switch ($course_tool_category) {
            case TOOL_STUDENT_VIEW:
                $criteria
                    ->where(Criteria::expr()->eq('visibility', 1))
                    ->andWhere(Criteria::expr()->in('category', ['authoring', 'interaction']))
                ;

                /*if ((api_is_coach() || api_is_course_tutor() || api_is_platform_admin()) && !$studentView) {
                    $conditions = ' WHERE (
                        visibility = 1 AND (
                            category = "authoring" OR
                            category = "interaction" OR
                            category = "plugin"
                        ) OR (t.name = "'.TOOL_TRACKING.'")
                    )';
                }*/
                break;
            case TOOL_AUTHORING:
                $criteria
                    ->where(Criteria::expr()->in('category', ['authoring']))
                ;
                break;
            case TOOL_INTERACTION:
                $criteria
                    ->where(Criteria::expr()->in('category', ['interaction']))
                ;
                break;
            case TOOL_ADMIN_VISIBLE:
                $criteria
                    ->where(Criteria::expr()->eq('visibility', 1))
                    ->andWhere(Criteria::expr()->in('category', ['admin']))
                ;
                break;
            case TOOL_ADMIN_PLATFORM:
                $criteria
                    ->andWhere(Criteria::expr()->in('category', ['admin']))
                ;
                break;
            case TOOL_DRH:
                $criteria
                    ->andWhere(Criteria::expr()->in('tool.tool.name', ['tracking']))
                ;
                break;
            /*case TOOL_COURSE_PLUGIN:
                //Other queries recover id, name, link, image, visibility, admin, address, added_tool, target, category and session_id
                // but plugins are not present in the tool table, only globally and inside the course_settings table once configured
                $sql = "SELECT * FROM $course_tool_table t
                        WHERE category = 'plugin' AND name <> 'courseblock' AND c_id = $course_id $condition_session
                        ";*/
                break;
        }

        $criteria
            ->andWhere(Criteria::expr()->eq('course', api_get_course_entity($courseId)))
        ;

        //$condition_session = $condition_add." ( $session_field = $session_id OR $session_field = 0 OR $session_field IS NULL) ";
        /*$criteria
            ->andWhere(Criteria::expr()->eq('session', $courseId))
        ;*/

        $qb->addCriteria($criteria);

        return $qb->getQuery()->getResult();
    }

    /**
     * Shows the general data for a particular meeting.
     *
     * @param int $id_session
     *
     * @return string session data
     */
    public static function show_session_data($id_session)
    {
        $sessionInfo = api_get_session_info($id_session);

        if (empty($sessionInfo)) {
            return '';
        }

        $table = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $sql = 'SELECT name FROM '.$table.'
                WHERE id = "'.intval($sessionInfo['session_category_id']).'"';
        $rs_category = Database::query($sql);
        $session_category = '';
        if (Database::num_rows($rs_category) > 0) {
            $rows_session_category = Database::store_result($rs_category);
            $rows_session_category = $rows_session_category[0];
            $session_category = $rows_session_category['name'];
        }

        $coachInfo = api_get_user_info($sessionInfo['id_coach']);

        $output = '';
        if (!empty($session_category)) {
            $output .= '<tr><td>'.get_lang('Sessions categories').': '.'<b>'.$session_category.'</b></td></tr>';
        }
        $dateInfo = SessionManager::parseSessionDates($sessionInfo);

        $msgDate = $dateInfo['access'];
        $output .= '<tr>
                    <td style="width:50%">'.get_lang('Session name').': '.'<b>'.$sessionInfo['name'].'</b></td>
                    <td>'.get_lang('General coach').': '.'<b>'.$coachInfo['complete_name'].'</b></td></tr>';
        $output .= '<tr>
                        <td>'.get_lang('Identifier of session').': '.
                            Display::return_icon('star.png', ' ', ['align' => 'absmiddle']).'
                        </td>
                        <td>'.get_lang('Date').': '.'<b>'.$msgDate.'</b>
                        </td>
                    </tr>';

        return $output;
    }

    /**
     * Retrieves the name-field within a tool-record and translates it on necessity.
     *
     * @param array $tool the input record
     *
     * @return string returns the name of the corresponding tool
     */
    public static function translate_tool_name(CTool $tool)
    {
        static $already_translated_icons = [
            'file_html.gif',
            'file_html_na.gif',
            'file_html.png',
            'file_html_na.png',
            'scormbuilder.gif',
            'scormbuilder_na.gif',
            'blog.gif',
            'blog_na.gif',
            'external.gif',
            'external_na.gif',
        ];

        $toolName = Security::remove_XSS(stripslashes(strip_tags($tool->getTool()->getName())));

        return $toolName;

        if (isset($tool['image']) && in_array($tool['image'], $already_translated_icons)) {
            return $toolName;
        }

        $toolName = api_underscore_to_camel_case($toolName);

        if (isset($tool['category']) && 'plugin' !== $tool['category'] &&
            isset($GLOBALS['Tool'.$toolName])
        ) {
            return get_lang('Tool'.$toolName);
        }

        return $toolName;
    }

    /**
     * Get published learning path id from link inside course home.
     *
     * @param 	string	Link to published lp
     *
     * @return int Learning path id
     */
    public static function getPublishedLpIdFromLink($link)
    {
        $lpId = 0;
        $param = strstr($link, 'lp_id=');
        if (!empty($param)) {
            $paramList = explode('=', $param);
            if (isset($paramList[1])) {
                $lpId = (int) $paramList[1];
            }
        }

        return $lpId;
    }

    /**
     * Get published learning path category from link inside course home.
     *
     * @param string $link
     *
     * @return CLpCategory
     */
    public static function getPublishedLpCategoryFromLink($link)
    {
        $query = parse_url($link, PHP_URL_QUERY);
        parse_str($query, $params);
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        $em = Database::getManager();
        /** @var CLpCategory $category */
        $category = $em->find('ChamiloCourseBundle:CLpCategory', $id);

        return $category;
    }

    /**
     * Show a navigation menu.
     */
    public static function show_navigation_menu()
    {
        $blocks = self::getUserBlocks();
        $class = null;
        $idLearn = null;
        $item = null;
        $marginLeft = 160;

        $html = '<div id="toolnav">';
        $html .= '<ul id="toolnavbox">';

        $showOnlyText = 'text' === api_get_setting('show_navigation_menu');
        $showOnlyIcons = 'icons' === api_get_setting('show_navigation_menu');

        foreach ($blocks as $block) {
            $blockItems = $block['content'];
            foreach ($blockItems as $item) {
                $html .= '<li>';
                if ($showOnlyText) {
                    $class = 'text';
                    $marginLeft = 170;
                    $show = $item['name'];
                } elseif ($showOnlyIcons) {
                    $class = 'icons';
                    $marginLeft = 25;
                    $show = $item['tool']['only_icon_small'];
                } else {
                    $class = 'icons-text';
                    $show = $item['name'].$item['tool']['only_icon_small'];
                }

                $item['url_params']['class'] = 'btn btn-default text-left '.$class;
                $html .= Display::url(
                    $show,
                    $item['only_href'],
                    $item['url_params']
                );
                $html .= '</li>';
            }
        }

        $html .= '</ul>';
        $html .= '<script>$(function() {
                $("#toolnavbox a").stop().animate({"margin-left":"-'.$marginLeft.'px"},1000);
                $("#toolnavbox > li").hover(
                    function () {
                        $("a",$(this)).stop().animate({"margin-left":"-2px"},200);
                        $("span",$(this)).css("display","block");
                    },
                    function () {
                        $("a",$(this)).stop().animate({"margin-left":"-'.$marginLeft.'px"},200);
                        $("span",$(this)).css("display","initial");
                    }
                );
            });</script>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Show a toolbar with shortcuts to the course tool.
     *
     * @param int $orientation
     *
     * @return string
     */
    public static function show_navigation_tool_shortcuts($orientation = SHORTCUTS_HORIZONTAL)
    {
        $origin = api_get_origin();
        $courseInfo = api_get_course_info();
        if ('learnpath' === $origin) {
            return '';
        }

        $blocks = self::getUserBlocks();
        $html = '';
        if (!empty($blocks)) {
            $styleId = 'toolshortcuts_vertical';
            if (SHORTCUTS_HORIZONTAL == $orientation) {
                $styleId = 'toolshortcuts_horizontal';
            }
            $html .= '<div id="'.$styleId.'">';

            $html .= Display::url(
                Display::return_icon('home.png', get_lang('Course home'), '', ICON_SIZE_MEDIUM),
                $courseInfo['course_public_url'],
                ['class' => 'items-icon']
            );

            foreach ($blocks as $block) {
                $blockItems = $block['content'];
                foreach ($blockItems as $item) {
                    $item['url_params']['id'] = '';
                    $item['url_params']['class'] = 'items-icon';
                    $html .= Display::url(
                        $item['tool']['only_icon_medium'],
                        $item['only_href'],
                        $item['url_params']
                    );
                    if (SHORTCUTS_VERTICAL == $orientation) {
                        $html .= '<br />';
                    }
                }
            }
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * List course homepage tools from authoring and interaction sections.
     *
     * @param int $courseId  The course ID (guessed from context if not provided)
     * @param int $sessionId The session ID (guessed from context if not provided)
     *
     * @return array List of all tools data from the c_tools table
     */
    public static function toolsIconsAction($courseId = null, $sessionId = null)
    {
        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        } else {
            $courseId = intval($courseId);
        }
        if (empty($sessionId)) {
            $sessionId = api_get_session_id();
        } else {
            $sessionId = intval($sessionId);
        }

        if (empty($courseId)) {
            // We shouldn't get here, but for some reason api_get_course_int_id()
            // doesn't seem to get the course from the context, sometimes
            return [];
        }

        $table = Database::get_course_table(TABLE_TOOL_LIST);
        $sql = "SELECT * FROM $table
                WHERE category in ('authoring','interaction')
                AND c_id = $courseId
                AND session_id = $sessionId
                ORDER BY id";

        $result = Database::query($sql);
        $data = Database::store_result($result, 'ASSOC');

        return $data;
    }

    /**
     * @param int $editIcon
     *
     * @return array
     */
    public static function getTool($editIcon)
    {
        $course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);
        $editIcon = intval($editIcon);

        $sql = "SELECT * FROM $course_tool_table
                WHERE iid = $editIcon";
        $result = Database::query($sql);
        $tool = Database::fetch_assoc($result, 'ASSOC');

        return $tool;
    }

    /**
     * @return string
     */
    public static function getCustomSysIconPath()
    {
        // Check if directory exists or create it if it doesn't
        $dir = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/course_home_icons/';
        if (!is_dir($dir)) {
            mkdir($dir, api_get_permissions_for_new_directories(), true);
        }

        return $dir;
    }

    /**
     * @return string
     */
    public static function getCustomWebIconPath()
    {
        // Check if directory exists or create it if it doesn't
        $dir = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/course_home_icons/';

        return $dir;
    }

    /**
     * @param string $icon
     *
     * @return string
     */
    public static function getDisableIcon($icon)
    {
        $fileInfo = pathinfo($icon);

        return $fileInfo['filename'].'_na.'.$fileInfo['extension'];
    }

    /**
     * @param int   $id
     * @param array $values
     */
    public static function updateTool($id, $values)
    {
        $table = Database::get_course_table(TABLE_TOOL_LIST);
        $params = [
            'name' => $values['name'],
            'link' => $values['link'],
            'target' => $values['target'],
            'visibility' => $values['visibility'],
            'description' => $values['description'],
        ];

        if (isset($_FILES['icon']['size']) && 0 !== $_FILES['icon']['size']) {
            /*$dir = self::getCustomSysIconPath();

            // Resize image if it is larger than 64px
            $temp = new Image($_FILES['icon']['tmp_name']);
            $picture_infos = $temp->get_image_info();
            if ($picture_infos['width'] > 64) {
                $thumbwidth = 64;
            } else {
                $thumbwidth = $picture_infos['width'];
            }
            if ($picture_infos['height'] > 64) {
                $new_height = 64;
            } else {
                $new_height = $picture_infos['height'];
            }
            $temp->resize($thumbwidth, $new_height, 0);

            //copy the image to the course upload folder
            $path = $dir.$_FILES['icon']['name'];
            $result = $temp->send_image($path);

            $temp = new Image($path);
            $r = $temp->convert2bw();
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $bwPath = substr($path, 0, -(strlen($ext) + 1)).'_na.'.$ext;

            if (false === $r) {
                error_log('Conversion to B&W of '.$path.' failed in '.__FILE__.' at line '.__LINE__);
            } else {
                $temp->send_image($bwPath);
                $iconName = $_FILES['icon']['name'];
                $params['custom_icon'] = $iconName;
            }*/
        }

        Database::update(
            $table,
            $params,
            [' iid = ?' => [$id]]
        );
    }

    /**
     * @param int $id
     */
    public static function deleteIcon($id)
    {
        $table = Database::get_course_table(TABLE_TOOL_LIST);
        $tool = self::getTool($id);

        if ($tool && !empty($tool['custom_icon'])) {
            $file = self::getCustomSysIconPath().$tool['custom_icon'];
            $fileInfo = pathinfo($file);
            $fileGray = $fileInfo['filename'].'_na.'.$fileInfo['extension'];
            $fileGray = self::getCustomSysIconPath().$fileGray;

            if (file_exists($file) && is_file($file)) {
                if (Security::check_abs_path($file, self::getCustomSysIconPath())) {
                    unlink($file);
                }
            }

            if (file_exists($fileGray) && is_file($fileGray)) {
                if (Security::check_abs_path($fileGray, self::getCustomSysIconPath())) {
                    unlink($fileGray);
                }
            }

            $params = [
                'custom_icon' => '',
            ];

            Database::update(
                $table,
                $params,
                [' iid = ?' => [$id]]
            );
        }
    }

    /**
     * @param string $toolName
     * @param int    $courseId
     * @param int    $sessionId Optional.
     *
     * @return bool
     */
    public static function getToolVisibility($toolName, $courseId, $sessionId = 0)
    {
        $allowEditionInSession = api_get_configuration_value('allow_edit_tool_visibility_in_session');

        $em = Database::getManager();
        $toolRepo = $em->getRepository('ChamiloCourseBundle:CTool');

        /** @var CTool $tool */
        $tool = $toolRepo->findOneBy(['cId' => $courseId, 'sessionId' => 0, 'name' => $toolName]);
        $visibility = $tool->getVisibility();

        if ($allowEditionInSession && $sessionId) {
            $tool = $toolRepo->findOneBy(
                ['cId' => $courseId, 'sessionId' => $sessionId, 'name' => $toolName]
            );

            if ($tool) {
                $visibility = $tool->getVisibility();
            }
        }

        return $visibility;
    }

    /**
     * Filter tool icons. Only show if $patronKey is = :teacher
     * Example dataIcons[i]['name']: parameter titleIcons1:teacher || titleIcons2 || titleIcons3:teacher.
     *
     * @param array  $dataIcons          array Reference to icons
     * @param string $courseToolCategory Current tools category
     *
     * @return array
     */
    private static function filterPluginTools($dataIcons, $courseToolCategory)
    {
        $patronKey = ':teacher';

        if (TOOL_STUDENT_VIEW == $courseToolCategory) {
            //Fix only coach can see external pages - see #8236 - icpna
            if (api_is_coach()) {
                foreach ($dataIcons as $index => $array) {
                    if (isset($array['name'])) {
                        $dataIcons[$index]['name'] = str_replace($patronKey, '', $array['name']);
                    }
                }

                return $dataIcons;
            }

            $flagOrder = false;

            foreach ($dataIcons as $index => $array) {
                if (!isset($array['name'])) {
                    continue;
                }

                $pos = strpos($array['name'], $patronKey);

                if (false !== $pos) {
                    unset($dataIcons[$index]);
                    $flagOrder = true;
                }
            }

            if ($flagOrder) {
                return array_values($dataIcons);
            }

            return $dataIcons;
        }

        // clean patronKey of name icons
        foreach ($dataIcons as $index => $array) {
            if (isset($array['name'])) {
                $dataIcons[$index]['name'] = str_replace($patronKey, '', $array['name']);
            }
        }

        return $dataIcons;
    }

    /**
     * Find the tool icon when homepage_view is activity_big.
     *
     * @param int  $iconSize
     * @param bool $generateId
     *
     * @return string
     */
    private static function getToolIcon(array $item, $iconSize, $generateId = true)
    {
        $image = str_replace('.gif', '.png', $item['tool']['image']);
        $toolIid = isset($item['tool']['iid']) ? $item['tool']['iid'] : null;

        if (isset($item['tool']['custom_image'])) {
            return Display::img(
                $item['tool']['custom_image'],
                $item['name'],
                ['id' => 'toolimage_'.$toolIid]
            );
        }

        if (isset($item['tool']['custom_icon']) && !empty($item['tool']['custom_icon'])) {
            $customIcon = $item['tool']['custom_icon'];

            if ('0' == $item['tool']['visibility']) {
                $customIcon = self::getDisableIcon($item['tool']['custom_icon']);
            }

            return Display::img(
                self::getCustomWebIconPath().$customIcon,
                $item['name'],
                ['id' => 'toolimage_'.$toolIid]
            );
        }

        $id = '';
        if ($generateId) {
            $id = 'toolimage_'.$toolIid;
        }

        return Display::return_icon(
            $image,
            $item['name'],
            ['id' => $id],
            $iconSize,
            false
        );
    }
}
