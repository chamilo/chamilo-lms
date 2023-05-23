<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CTool;

/**
 * Class CourseHome.
 */
class CourseHome
{
    /**
     * Gets the html content to show in the 3 column view.
     *
     * @param string $cat
     * @param int    $userId
     *
     * @return string
     */
    public static function show_tool_3column($cat, $userId = null)
    {
        $_user = api_get_user_info($userId);
        $course_id = api_get_course_int_id();

        $TBL_ACCUEIL = Database::get_course_table(TABLE_TOOL_LIST);
        $TABLE_TOOLS = Database::get_main_table(TABLE_MAIN_COURSE_MODULE);

        $numcols = 3;
        $table = new HTML_Table('width="100%"');
        $all_tools = [];
        switch ($cat) {
            case 'Basic':
                $condition_display_tools = ' WHERE a.c_id = '.$course_id.' AND  a.link=t.link AND t.position="basic" ';
                if ((api_is_coach() || api_is_course_tutor() || api_is_platform_admin()) &&
                    $_SESSION['studentview'] != 'studentview'
                ) {
                    $condition_display_tools = ' WHERE
                        a.c_id = '.$course_id.' AND
                        a.link=t.link AND
                         (t.position="basic" OR a.name = "'.TOOL_TRACKING.'")
                    ';
                }

                $sql = "SELECT a.*, t.image img, t.row, t.column
                        FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
                        $condition_display_tools ORDER BY t.row, t.column";
                break;
            case 'External':
                if (api_is_allowed_to_edit()) {
                    $sql = "SELECT a.*, t.image img FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
                            WHERE
                              a.c_id = $course_id AND
                              ((a.link=t.link AND t.position='external') OR
                              (a.visibility <= 1 AND
                              (a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND
                              a.image=t.image))
                            ORDER BY a.id";
                } else {
                    $sql = "SELECT a.*, t.image img FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
                            WHERE
                              a.c_id = $course_id AND
                              (a.visibility = 1 AND ((a.link=t.link AND t.position='external') OR
                              ((a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND
                              a.image=t.image)))
                            ORDER BY a.id";
                }
                break;
            case 'courseAdmin':
                $sql = "SELECT a.*, t.image img, t.row, t.column
                        FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
                        WHERE a.c_id = $course_id AND admin=1 AND a.link=t.link
                        ORDER BY t.row, t.column";
                break;

            case 'platformAdmin':
                $sql = "SELECT *, image img FROM $TBL_ACCUEIL
                        WHERE c_id = $course_id AND visibility = 2
                        ORDER BY id";
        }
        $result = Database::query($sql);

        // Grabbing all the tools from $course_tool_table
        while ($tool = Database::fetch_array($result)) {
            $all_tools[] = $tool;
        }

        // Grabbing all the links that have the property on_homepage set to 1
        if ($cat === 'External') {
            $tbl_link = Database::get_course_table(TABLE_LINK);
            $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
            if (api_is_allowed_to_edit(null, true)) {
                $sql_links = "SELECT tl.*, tip.visibility
                              FROM $tbl_link tl
                              LEFT JOIN $tbl_item_property tip ON tip.tool='link' AND tip.ref=tl.id
                              WHERE
                                tl.c_id = $course_id AND
                                tip.c_id = $course_id AND
                                tl.on_homepage='1' AND
                                tip.visibility != 2";
            } else {
                $sql_links = "SELECT tl.*, tip.visibility
                                FROM $tbl_link tl
                                LEFT JOIN $tbl_item_property tip ON tip.tool='link' AND tip.ref=tl.id
                                WHERE
                                    tl.c_id = $course_id AND
                                    tip.c_id = $course_id AND
                                    tl.on_homepage='1' AND
                                    tip.visibility = 1";
            }
            $result_links = Database::query($sql_links);
            while ($links_row = Database::fetch_array($result_links)) {
                $properties = [];
                $properties['name'] = $links_row['title'];
                $properties['link'] = $links_row['url'];
                $properties['visibility'] = $links_row['visibility'];
                $properties['img'] = 'external.gif';
                $properties['adminlink'] = api_get_path(WEB_CODE_PATH).'link/link.php?action=editlink&amp;id='.$links_row['id'];
                $all_tools[] = $properties;
            }
        }

        $cell_number = 0;
        // Draw line between basic and external, only if there are entries in External
        if ($cat == 'External' && count($all_tools)) {
            $table->setCellContents(0, 0, '<hr noshade="noshade" size="1"/>');
            $table->updateCellAttributes(0, 0, 'colspan="3"');
            $cell_number += $numcols;
        }

        foreach ($all_tools as &$tool) {
            if (isset($tool['image']) && $tool['image'] == 'scormbuilder.gif') {
                // check if the published learnpath is visible for student
                $lpId = self::getPublishedLpIdFromLink($tool['link']);

                if (!api_is_allowed_to_edit(null, true) &&
                    !learnpath::is_lp_visible_for_student(
                        $lpId,
                        api_get_user_id(),
                        api_get_course_info(),
                        api_get_session_id()
                    )
                ) {
                    continue;
                }
            }

            if (api_get_session_id() != 0 &&
                in_array($tool['name'], ['course_maintenance', 'course_setting'])
            ) {
                continue;
            }

            $cell_content = '';
            // The name of the tool
            $tool_name = self::translate_tool_name($tool);

            $link_annex = '';
            // The url of the tool
            if ($tool['img'] != 'external.gif') {
                $tool['link'] = api_get_path(WEB_CODE_PATH).$tool['link'];
                $qm_or_amp = strpos($tool['link'], '?') === false ? '?' : '&amp;';
                $link_annex = $qm_or_amp.api_get_cidreq();
            } else {
                // If an external link ends with 'login=', add the actual login...
                $pos = strpos($tool['link'], '?login=');
                $pos2 = strpos($tool['link'], '&amp;login=');
                if ($pos !== false or $pos2 !== false) {
                    $link_annex = $_user['username'];
                }
            }

            // Setting the actual image url
            $tool['img'] = Display::returnIconPath($tool['img']);
            $target = isset($tool['target']) ? $tool['target'] : '';

            // VISIBLE
            if (($tool['visibility'] ||
                ((api_is_coach() || api_is_course_tutor() || api_is_platform_admin()) && $tool['name'] == TOOL_TRACKING)) ||
                $cat == 'courseAdmin' || $cat == 'platformAdmin'
            ) {
                if (strpos($tool['name'], 'visio_') !== false) {
                    $cell_content .= '<a  href="javascript: void(0);" onclick="javascript: window.open(\''.$tool['link'].$link_annex.'\',\'window_visio'.api_get_course_id().'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="'.$tool['target'].'"><img src="'.$tool['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                } elseif (strpos($tool['name'], 'chat') !== false &&
                    api_get_course_setting('allow_open_chat_window')
                ) {
                    // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                    $cell_content .= '<a href="javascript: void(0);" onclick="javascript: window.open(\''.$tool['link'].$link_annex.'\',\'window_chat'.api_get_course_id().'\',config=\'height=\'+600+\', width=\'+825+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="'.$tool['target'].'"><img src="'.$tool['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                } else {
                    // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                    $cell_content .= '<a href="'.$tool['link'].$link_annex.'" target="'.$target.'"><img src="'.$tool['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                }
            } else {
                // INVISIBLE
                if (api_is_allowed_to_edit(null, true)) {
                    if (strpos($tool['name'], 'visio_') !== false) {
                        $cell_content .= '<a  href="javascript: void(0);" onclick="window.open(\''.$tool['link'].$link_annex.'\',\'window_visio'.api_get_course_id().'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="'.$tool['target'].'"><img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                    } elseif (strpos($tool['name'], 'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
                        // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                        $cell_content .= '<a href="javascript: void(0);" onclick="javascript: window.open(\''.$tool['link'].$link_annex.'\',\'window_chat'.api_get_course_id().'\',config=\'height=\'+600+\', width=\'+825+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="'.$tool['target'].'" class="text-muted"><img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                    } else {
                        // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                        $cell_content .= '<a href="'.$tool['link'].$link_annex.'" target="'.$tool['target'].'" class="text-muted">
                            <img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                    }
                } else {
                    // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                    $cell_content .= '<img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">';
                    $cell_content .= '<span class="text-muted">'.$tool_name.'</span>';
                }
            }

            $lnk = [];
            if (api_is_allowed_to_edit(null, true) &&
                $cat != "courseAdmin" &&
                !strpos($tool['link'], 'learnpath_handler.php?learnpath_id') &&
                !api_is_coach()
            ) {
                if ($tool['visibility']) {
                    $link['name'] = Display::return_icon(
                        'remove.gif',
                        get_lang('Deactivate'),
                        ['style' => 'vertical-align: middle;']
                    );
                    $link['cmd'] = "hide=yes";
                    $lnk[] = $link;
                } else {
                    $link['name'] = Display::return_icon(
                        'add.gif',
                        get_lang('Activate'),
                        ['style' => 'vertical-align: middle;']
                    );
                    $link['cmd'] = "restore=yes";
                    $lnk[] = $link;
                }
                if (is_array($lnk)) {
                    foreach ($lnk as &$this_lnk) {
                        if (isset($tool['adminlink']) && $tool['adminlink']) {
                            $cell_content .= '<a href="'.$properties['adminlink'].'">'.
                                Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
                        } else {
                            $cell_content .= '<a href="'.api_get_self().'?id='.$tool['id'].'&amp;'.$this_lnk['cmd'].'">'.$this_lnk['name'].'</a>';
                        }
                    }
                }
            }
            $table->setCellContents($cell_number / $numcols, ($cell_number) % $numcols, $cell_content);
            $table->updateCellAttributes($cell_number / $numcols, ($cell_number) % $numcols, 'width="32%" height="42"');
            $cell_number++;
        }

        return $table->toHtml();
    }

    /**
     * Displays the tools of a certain category.
     *
     * @param string $course_tool_category contains the category of tools to display:
     *                                     "Public", "PublicButHide", "courseAdmin", "claroAdmin"
     *
     * @return string
     */
    public static function show_tool_2column($course_tool_category)
    {
        $html = '';
        $web_code_path = api_get_path(WEB_CODE_PATH);
        $course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);
        $course_id = api_get_course_int_id();
        switch ($course_tool_category) {
            case TOOL_PUBLIC:
                $condition_display_tools = ' WHERE c_id = '.$course_id.' AND visibility = 1 ';
                if ((api_is_coach() || api_is_course_tutor() || api_is_platform_admin()) &&
                    $_SESSION['studentview'] != 'studentview'
                ) {
                    $condition_display_tools = ' WHERE
                        c_id = '.$course_id.' AND
                        (visibility = 1 OR (visibility = 0 AND name = "'.TOOL_TRACKING.'")) ';
                }
                $result = Database::query("SELECT * FROM $course_tool_table $condition_display_tools ORDER BY id");
                $col_link = "##003399";
                break;
            case TOOL_PUBLIC_BUT_HIDDEN:
                $result = Database::query("SELECT * FROM $course_tool_table WHERE c_id = $course_id AND visibility=0 AND admin=0 ORDER BY id");
                $col_link = "##808080";
                break;
            case TOOL_COURSE_ADMIN:
                $result = Database::query("SELECT * FROM $course_tool_table WHERE c_id = $course_id AND admin=1 AND visibility != 2 ORDER BY id");
                $col_link = "##003399";
                break;
            case TOOL_PLATFORM_ADMIN:
                $result = Database::query("SELECT * FROM $course_tool_table WHERE c_id = $course_id AND visibility = 2  ORDER BY id");
                $col_link = "##003399";
        }
        $i = 0;

        // Grabbing all the tools from $course_tool_table
        while ($temp_row = Database::fetch_array($result)) {
            if ($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN && $temp_row['image'] != 'scormbuilder.gif') {
                $temp_row['image'] = str_replace('.gif', '_na.gif', $temp_row['image']);
            }
            $all_tools_list[] = $temp_row;
        }

        // Grabbing all the links that have the property on_homepage set to 1
        $course_link_table = Database::get_course_table(TABLE_LINK);
        $course_item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);

        switch ($course_tool_category) {
            case TOOL_PUBLIC:
                $sql_links = "SELECT tl.*, tip.visibility
                        FROM $course_link_table tl
                        LEFT JOIN $course_item_property_table tip
                        ON tip.tool='link' AND tl.c_id = tip.c_id AND tl.c_id = $course_id AND tip.ref=tl.id
                        WHERE tl.on_homepage='1' AND tip.visibility = 1";
                break;
            case TOOL_PUBLIC_BUT_HIDDEN:
                $sql_links = "SELECT tl.*, tip.visibility
                    FROM $course_link_table tl
                    LEFT JOIN $course_item_property_table tip
                    ON tip.tool='link' AND tl.c_id = tip.c_id AND tl.c_id = $course_id AND tip.ref=tl.id
                    WHERE tl.on_homepage='1' AND tip.visibility = 0";

                break;
            default:
                $sql_links = null;
                break;
        }
        if ($sql_links != null) {
            $properties = [];
            $result_links = Database::query($sql_links);
            while ($links_row = Database::fetch_array($result_links)) {
                unset($properties);
                $properties['name'] = $links_row['title'];
                $properties['link'] = $links_row['url'];
                $properties['visibility'] = $links_row['visibility'];
                $properties['image'] = $course_tool_category == TOOL_PUBLIC_BUT_HIDDEN ? 'external_na.gif' : 'external.gif';
                $properties['adminlink'] = api_get_path(WEB_CODE_PATH).'link/link.php?action=editlink&id='.$links_row['id'];
                $all_tools_list[] = $properties;
            }
        }
        $lnk = [];
        if (isset($all_tools_list)) {
            foreach ($all_tools_list as &$tool) {
                if ($tool['image'] == 'scormbuilder.gif') {
                    // check if the published learnpath is visible for student
                    $lpId = self::getPublishedLpIdFromLink($tool['link']);

                    if (!api_is_allowed_to_edit(null, true) &&
                        !learnpath::is_lp_visible_for_student(
                            $lpId,
                            api_get_user_id(),
                            api_get_course_info(),
                            api_get_session_id()
                        )
                    ) {
                        continue;
                    }
                }

                if (api_get_session_id() != 0 &&
                    in_array($tool['name'], ['course_maintenance', 'course_setting'])
                ) {
                    continue;
                }

                if (!($i % 2)) {
                    $html .= "<tr valign=\"top\">";
                }

                // NOTE : Table contains only the image file name, not full path
                if (stripos($tool['link'], 'http://') === false &&
                    stripos($tool['link'], 'https://') === false &&
                    stripos($tool['link'], 'ftp://') === false
                ) {
                    $tool['link'] = $web_code_path.$tool['link'];
                }
                $class = '';
                if ($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN) {
                    $class = 'class="text-muted"';
                }
                $qm_or_amp = strpos($tool['link'], '?') === false ? '?' : '&amp;';

                $tool['link'] = $tool['link'];
                $html .= '<td width="50%" height="30">';

                if (strpos($tool['name'], 'visio_') !== false) {
                    $html .= '<a  '.$class.' href="javascript: void(0);" onclick="javascript: window.open(\''.htmlspecialchars($tool['link']).(($tool['image'] == 'external.gif' || $tool['image'] == 'external_na.gif') ? '' : $qm_or_amp.api_get_cidreq()).'\',\'window_visio'.api_get_course_id().'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="'.$tool['target'].'">';
                } elseif (strpos($tool['name'], 'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
                    $html .= '<a href="javascript: void(0);" onclick="javascript: window.open(\''.htmlspecialchars($tool['link']).$qm_or_amp.api_get_cidreq().'\',\'window_chat'.api_get_course_id().'\',config=\'height=\'+600+\', width=\'+825+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="'.$tool['target'].'" '.$class.'>';
                } else {
                    $target = isset($tool['target']) ? $tool['target'] : '';
                    $html .= '<a href="'.
                        htmlspecialchars($tool['link']).(($tool['image'] == 'external.gif' || $tool['image'] == 'external_na.gif') ? '' : $qm_or_amp.api_get_cidreq()).'" target="'.$target.'" '.$class.'>';
                }

                $tool_name = self::translate_tool_name($tool);
                $html .= Display::return_icon(
                    $tool['image'],
                    $tool_name,
                    [],
                    null,
                    ICON_SIZE_MEDIUM
                ).'&nbsp;'.$tool_name.
                '</a>';

                // This part displays the links to hide or remove a tool.
                // These links are only visible by the course manager.
                $lnk = [];
                if (api_is_allowed_to_edit(null, true) && !api_is_coach()) {
                    if ($tool['visibility'] == '1' || $tool['name'] == TOOL_TRACKING) {
                        $link['name'] = Display::returnFontAwesomeIcon('minus');
                        $link['title'] = get_lang('Deactivate');
                        $link['cmd'] = 'hide=yes';
                        $lnk[] = $link;
                    }

                    if ($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN) {
                        //$link['name'] = Display::return_icon('add.gif', get_lang('Activate'));
                        $link['name'] = Display::returnFontAwesomeIcon('plus');
                        $link['title'] = get_lang('Activate');
                        $link['cmd'] = 'restore=yes';
                        $lnk[] = $link;

                        if ($tool['added_tool'] == 1) {
                            //$link['name'] = Display::return_icon('delete.gif', get_lang('Remove'));
                            $link['name'] = Display::returnFontAwesomeIcon('trash');
                            $link['title'] = get_lang('Remove');
                            $link['cmd'] = 'remove=yes';
                            $lnk[] = $link;
                        }
                    }
                    if (isset($tool['adminlink'])) {
                        $html .= '<a href="'.$tool['adminlink'].'">'.
                            Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
                    }
                }
                if (api_is_platform_admin() && !api_is_coach()) {
                    if ($tool['visibility'] == 2) {
                        $link['name'] = Display::returnFontAwesomeIcon('undo');
                        $link['title'] = get_lang('Activate');
                        $link['cmd'] = 'hide=yes';
                        $lnk[] = $link;

                        if ($tool['added_tool'] == 1) {
                            $link['name'] = get_lang('Delete');
                            $link['cmd'] = 'askDelete=yes';
                            $lnk[] = $link;
                        }
                    }
                    if ($tool['visibility'] == 0 && $tool['added_tool'] == 0) {
                        $link['name'] = Display::returnFontAwesomeIcon('trash');
                        $link['title'] = get_lang('Remove');
                        $link['cmd'] = 'remove=yes';
                        $lnk[] = $link;
                    }
                }
                if (is_array($lnk)) {
                    $html .= '<div class="pull-right">';
                    $html .= '<div class="btn-options">';
                    $html .= '<div class="btn-group btn-group-sm" role="group">';
                    foreach ($lnk as &$this_link) {
                        if (!isset($tool['adminlink'])) {
                            $html .= '<a class="btn btn-default" title='.$this_link['title'].' href="'.api_get_self().'?'.api_get_cidreq().'&amp;id='.$tool['id'].'&amp;'.$this_link['cmd'].'">'.$this_link['name'].'</a>';
                        }
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= "</td>";

                if ($i % 2) {
                    $html .= "</tr>";
                }
                $i++;
            }
        }

        if ($i % 2) {
            $html .= "<td width=\"50%\">&nbsp;</td></tr>";
        }

        return $html;
    }

    /**
     * Gets the tools of a certain category. Returns an array expected
     * by show_tools_category().
     *
     * @param string $course_tool_category contains the category of tools to
     *                                     display: "toolauthoring", "toolinteraction", "tooladmin", "tooladminplatform", "toolplugin"
     * @param int    $courseId             Optional
     * @param int    $sessionId            Optional
     *
     * @return array
     */
    public static function get_tools_category($course_tool_category, $courseId = 0, $sessionId = 0)
    {
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

        $lpTable = Database::get_course_table(TABLE_LP_MAIN);
        $tblLpCategory = Database::get_course_table(TABLE_LP_CATEGORY);
        $orderBy = ' ORDER BY id ';

        switch ($course_tool_category) {
            case TOOL_STUDENT_VIEW:
                $conditions = ' WHERE visibility = 1 AND
                                (category = "authoring" OR category = "interaction" OR category = "plugin") AND
                                t.name <> "notebookteacher" ';
                if ((api_is_coach() || api_is_course_tutor() || $is_platform_admin) &&
                    $_SESSION['studentview'] !== 'studentview'
                ) {
                    $conditions = ' WHERE (
                        visibility = 1 AND (
                            category = "authoring" OR
                            category = "interaction" OR
                            category = "plugin"
                        ) OR
                        (t.name = "'.TOOL_TRACKING.'")
                        OR (
                            image = "scormbuilder.gif"
                        )
                    )';
                }

                if ('true' === api_get_plugin_setting('positioning', 'tool_enable')) {
                    $plugin = Positioning::create();
                    $block = $plugin->get('block_course_if_initial_exercise_not_attempted');
                    if ('true' === $block) {
                        $initialData = $plugin->getInitialExercise($course_id, $sessionId);
                        if ($initialData && isset($initialData['exercise_id'])) {
                            $results = Event::getExerciseResultsByUser(
                                $userId,
                                $initialData['exercise_id'],
                                $course_id,
                                $sessionId
                            );

                            if (empty($results)) {
                                $conditions .= ' AND t.name = "positioning"';
                            }
                        }
                    }
                }

                // Add order if there are LPs
                $sql = "SELECT t.* FROM $course_tool_table t
                        LEFT JOIN $lpTable l
                        ON (t.c_id = l.c_id AND link LIKE concat('%/lp_controller.php?action=view&lp_id=', l.id, '&%'))
                        LEFT JOIN $tblLpCategory lc
                        ON (t.c_id = lc.c_id AND l.category_id = lc.iid)
                        $conditions AND
                        t.c_id = $course_id $condition_session
                        ORDER BY
                            CASE WHEN l.category_id IS NULL THEN 0 ELSE 1 END,
                            CASE WHEN l.display_order IS NULL THEN 0 ELSE 1 END,
                            lc.position,
                            l.display_order,
                            t.id";

                $orderBy = '';
                break;
            case TOOL_AUTHORING:
                $sql = "SELECT t.* FROM $course_tool_table t
                        LEFT JOIN $lpTable l
                        ON (t.c_id = l.c_id AND link LIKE concat('%/lp_controller.php?action=view&lp_id=', l.id, '&%'))
                        LEFT JOIN $tblLpCategory lc
                        ON (t.c_id = lc.c_id AND l.category_id = lc.iid)
                        WHERE
                            category = 'authoring' AND t.c_id = $course_id $condition_session
                        ORDER BY
                            CASE WHEN l.category_id IS NULL THEN 0 ELSE 1 END,
                            CASE WHEN l.display_order IS NULL THEN 0 ELSE 1 END,
                            lc.position,
                            l.display_order,
                            t.id";
                $orderBy = '';
                break;
            case TOOL_INTERACTION:
                $sql = "SELECT * FROM $course_tool_table t
                        WHERE category = 'interaction' AND c_id = $course_id $condition_session
                        ";
                break;
            case TOOL_ADMIN_VISIBLE:
                $sql = "SELECT * FROM $course_tool_table t
                        WHERE category = 'admin' AND visibility ='1' AND c_id = $course_id $condition_session
                        ";
                break;
            case TOOL_ADMIN_PLATFORM:
                $sql = "SELECT * FROM $course_tool_table t
                        WHERE category = 'admin' AND c_id = $course_id $condition_session
                        ";
                break;
            case TOOL_DRH:
                $sql = "SELECT * FROM $course_tool_table t
                        WHERE t.name IN ('tracking') AND c_id = $course_id $condition_session
                        ";
                break;
            case TOOL_COURSE_PLUGIN:
                //Other queries recover id, name, link, image, visibility, admin, address, added_tool, target, category and session_id
                // but plugins are not present in the tool table, only globally and inside the course_settings table once configured
                $sql = "SELECT * FROM $course_tool_table t
                        WHERE category = 'plugin' AND name <> 'courseblock' AND c_id = $course_id $condition_session
                        ";
                break;
        }
        $sql .= $orderBy;
        $result = Database::query($sql);
        $tools = [];
        while ($row = Database::fetch_assoc($result)) {
            $tools[] = $row;
        }

        // Get the list of hidden tools - this might imply performance slowdowns
        // if the course homepage is loaded many times, so the list of hidden
        // tools might benefit from a shared memory storage later on
        $list = api_get_settings('Tools', 'list', api_get_current_access_url_id());

        $hide_list = [];
        $check = false;
        foreach ($list as $line) {
            // Admin can see all tools even if the course_hide_tools configuration is set
            if ($is_platform_admin) {
                continue;
            }
            if ($line['variable'] === 'course_hide_tools' && $line['selected_value'] === 'true') {
                $hide_list[] = $line['subkey'];
                $check = true;
            }
        }

        $allowEditionInSession = api_get_configuration_value('allow_edit_tool_visibility_in_session');
        // If exists same tool (by name) from session in base course then avoid it. Allow them pass in other cases
        $tools = array_filter($tools, function (array $toolToFilter) use ($tools) {
            if (!empty($toolToFilter['session_id'])) {
                foreach ($tools as $originalTool) {
                    if ($toolToFilter['name'] == $originalTool['name'] && empty($originalTool['session_id'])) {
                        return false;
                    }
                }
            }

            return true;
        });

        $isAllowToEdit = api_is_allowed_to_edit(null, true);
        $showInvisibleLpsForStudents = api_get_configuration_value('show_invisible_lp_in_course_home');
        foreach ($tools as $temp_row) {
            $add = false;
            if ($check) {
                if (!in_array($temp_row['name'], $hide_list)) {
                    $add = true;
                }
            } else {
                $add = true;
            }

            if (false === $showInvisibleLpsForStudents &&
                false === $isAllowToEdit &&
                $allowEditionInSession && !empty($sessionId)
            ) {
                // Checking if exist row in session
                $criteria = [
                    'cId' => $course_id,
                    'name' => $temp_row['name'],
                    'sessionId' => $sessionId,
                ];
                /** @var CTool $toolObj */
                $toolObj = Database::getManager()->getRepository('ChamiloCourseBundle:CTool')->findOneBy($criteria);
                if ($toolObj && $toolObj->getVisibility() == false) {
                    continue;
                }
            }

            switch ($temp_row['image']) {
                case 'scormbuilder.gif':
                    $lpId = self::getPublishedLpIdFromLink($temp_row['link']);
                    $lp = new learnpath(
                        $courseInfo['code'],
                        $lpId,
                        $userId
                    );
                    $path = $lp->get_preview_image_path(ICON_SIZE_BIG);

                    if ($isAllowToEdit) {
                        $add = true;
                    } else {
                        if ($showInvisibleLpsForStudents) {
                            $add = true;
                        } else {
                            $add = learnpath::is_lp_visible_for_student(
                                $lpId,
                                $userId,
                                $courseInfo,
                                $sessionId
                            );
                            // Check if LP is visible.
                            $visibility = api_get_item_visibility($courseInfo, TOOL_LEARNPATH, $lpId, $sessionId);
                            if (1 !== $visibility) {
                                $add = false;
                            }
                        }
                    }
                    if ($path) {
                        $temp_row['custom_image'] = $path;
                    }
                    break;
                case 'lp_category.gif':
                    $lpCategory = self::getPublishedLpCategoryFromLink($temp_row['link']);
                    if ($showInvisibleLpsForStudents) {
                        $add = true;
                    } else {
                        $add = learnpath::categoryIsVisibleForStudent($lpCategory, $user);
                    }

                    break;
            }

            if ($add) {
                $all_tools_list[] = $temp_row;
            }
        }

        // Grabbing all the links that have the property on_homepage set to 1
        $course_link_table = Database::get_course_table(TABLE_LINK);
        $course_item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $condition_session = api_get_session_condition(
            $sessionId,
            true,
            true,
            'tip.session_id'
        );

        $filterVisibility = '';
        if (!($is_platform_admin || api_is_course_admin())) {
            if ($sessionId) {
                $filterVisibility = ' AND (
                    (tip.visibility = 1 AND (tip.session_id = 0 OR tip.session_id IS NULL)) OR
                    (tip.visibility = 1 AND tip.session_id <> 0)
                ) ';

                if (api_is_coach()) {
                    $filterVisibility = ' AND (
                        (tip.visibility = 1 AND (tip.session_id = 0 OR tip.session_id IS NULL)) OR
                        (tip.visibility IN (1, 2) AND tip.session_id <> 0)
                    ) ';
                }
            } else {
                $filterVisibility = ' AND tip.visibility = 1 ';
            }
        }

        switch ($course_tool_category) {
            case TOOL_ADMIN:
            case TOOL_AUTHORING:
                $sql_links = "
                    SELECT DISTINCT tl.*, tip.visibility
                    FROM $course_link_table tl
                    LEFT JOIN $course_item_property_table tip
                    ON tip.tool='link' AND tip.ref=tl.id
                    WHERE
                        tl.c_id = $course_id AND
                        tip.c_id = $course_id AND
                        tl.on_homepage='1' $condition_session
                    ORDER BY tip.visibility";
                break;
            case TOOL_INTERACTION:
                $sql_links = null;
                break;
            case TOOL_STUDENT_VIEW:
                $sql_links = "
                    SELECT DISTINCT tl.*, tip.visibility
                    FROM $course_link_table tl
                    LEFT JOIN $course_item_property_table tip
                    ON tip.tool='link' AND tip.ref=tl.id
                    WHERE
                        tl.c_id 		= $course_id AND
                        tip.c_id 		= $course_id AND
                        tl.on_homepage	='1'
                        $condition_session
                        $filterVisibility
                    ORDER BY tip.visibility";
                break;
            default:
                $sql_links = null;
                break;
        }

        // Edited by Kevin Van Den Haute (kevin@develop-it.be) for integrating Smartblogs
        if ($sql_links != null) {
            $result_links = Database::query($sql_links);
            if (Database::num_rows($result_links) > 0) {
                $linkUrl = api_get_path(WEB_CODE_PATH).'link/link.php?action=editlink';
                $added = [];
                while ($links_row = Database::fetch_array($result_links, 'ASSOC')) {
                    if (in_array($links_row['iid'], $added)) {
                        continue;
                    }
                    $properties = [];
                    $properties['id'] = $links_row['id'];
                    $properties['iid'] = $links_row['iid'];
                    $properties['name'] = $links_row['title'];
                    $properties['session_id'] = $links_row['session_id'];
                    $properties['link'] = $links_row['url'];

                    // For students, check if link is visible in the session.
                    if ($sessionId && !($is_platform_admin || api_is_course_admin())) {
                        $visibility = api_get_item_visibility($courseInfo, TOOL_LINK, $links_row['iid'], $sessionId);
                        if (1 !== $visibility) {
                            continue;
                        }
                    }

                    $properties['visibility'] = $links_row['visibility'];
                    $properties['image'] = $links_row['visibility'] == '0' ? 'file_html.png' : 'file_html.png';
                    $properties['adminlink'] = $linkUrl.'&id='.$links_row['id'].'&cidReq='.$courseInfo['code'];
                    $properties['target'] = $links_row['target'];
                    $tmp_all_tools_list[] = $properties;
                    $added[] = $links_row['iid'];
                }
            }
        }

        if (isset($tmp_all_tools_list)) {
            $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);
            foreach ($tmp_all_tools_list as $tool) {
                if ($tool['image'] === 'blog.gif') {
                    // Get blog id
                    $blog_id = substr($tool['link'], strrpos($tool['link'], '=') + 1, strlen($tool['link']));

                    // Get blog members
                    if ($is_platform_admin) {
                        $sql = "SELECT * FROM $tbl_blogs_rel_user blogs_rel_user
                                WHERE blog_id = ".$blog_id;
                    } else {
                        $sql = "SELECT * FROM $tbl_blogs_rel_user blogs_rel_user
                                WHERE blog_id = ".$blog_id." AND user_id = ".$userId;
                    }
                    $result = Database::query($sql);
                    if (Database::num_rows($result) > 0) {
                        $all_tools_list[] = $tool;
                    }
                } else {
                    $all_tools_list[] = $tool;
                }
            }
        }

        return self::filterPluginTools($all_tools_list, $course_tool_category);
    }

    /**
     * Displays the tools of a certain category.
     *
     * @param array $all_tools_list List of tools as returned by get_tools_category()
     *
     * @return array
     */
    public static function show_tools_category($all_tools_list)
    {
        $_user = api_get_user_info();
        $theme = api_get_setting('homepage_view');

        if ($theme === 'vertical_activity') {
            //ordering by get_lang name
            $order_tool_list = [];
            if (is_array($all_tools_list) && count($all_tools_list) > 0) {
                foreach ($all_tools_list as $key => $new_tool) {
                    $tool_name = self::translate_tool_name($new_tool);
                    $order_tool_list[$key] = $tool_name;
                }
                natsort($order_tool_list);
                $my_temp_tool_array = [];
                foreach ($order_tool_list as $key => $new_tool) {
                    $my_temp_tool_array[] = $all_tools_list[$key];
                }
                $all_tools_list = $my_temp_tool_array;
            } else {
                $all_tools_list = [];
            }
        }
        $web_code_path = api_get_path(WEB_CODE_PATH);
        $session_id = api_get_session_id();
        $courseId = api_get_course_int_id();
        $is_platform_admin = api_is_platform_admin();
        $courseInfo = api_get_course_info();
        $allowEditionInSession = api_get_configuration_value('allow_edit_tool_visibility_in_session');

        if ($session_id == 0) {
            $is_allowed_to_edit = api_is_allowed_to_edit(null, true) && api_is_course_admin();
        } else {
            $is_allowed_to_edit = api_is_allowed_to_edit(null, true) && !api_is_coach();
            if ($allowEditionInSession) {
                $is_allowed_to_edit = (api_is_allowed_to_edit(null, true) &&
                    api_is_coach($session_id, $courseId)) || $is_platform_admin;
            }
        }

        $disableUsers = 3 === (int) $courseInfo['visibility'] &&
            api_get_configuration_value('disable_change_user_visibility_for_public_courses');

        $items = [];
        $app_plugin = new AppPlugin();
        $toolRepo = Database::getManager()->getRepository('ChamiloCourseBundle:CTool');
        if (isset($all_tools_list)) {
            $lnk = '';
            foreach ($all_tools_list as &$tool) {
                $allowChangeVisibility = true;
                $showIcon = true;
                if ('user/user.php' === $tool['link'] && $disableUsers) {
                    $allowChangeVisibility = false;
                    $tool['visibility'] = 0;
                    $showIcon = $is_allowed_to_edit;
                }
                $item = [];
                $studentview = false;
                $tool['original_link'] = $tool['link'];

                if ($tool['image'] === 'lp_category.gif') {
                    if ($session_id) {
                        if (api_is_coach() || api_is_allowed_to_edit()) {
                            $lpCategory = self::getPublishedLpCategoryFromLink($tool['link']);
                            $itemInfo = api_get_item_property_info(
                                $courseId,
                                TOOL_LEARNPATH_CATEGORY,
                                $lpCategory->getId(),
                                $session_id
                            );

                            if ($itemInfo && 0 === (int) $itemInfo['visibility']) {
                                $tool['image'] = 'lp_category_na.gif';
                            }
                        } else {
                            $categoryInSessionName = str_replace('id_session', $session_id, $tool['name']);
                            $criteria = [
                                'cId' => $courseId,
                                'name' => $categoryInSessionName,
                                'sessionId' => $session_id,
                            ];
                            /** @var CTool $tool */
                            $toolObj = $toolRepo->findOneBy($criteria);
                            if ($toolObj) {
                                $visibility = (int) $toolObj->getVisibility();
                                if (0 === $visibility) {
                                    continue;
                                }
                            }
                        }
                    }
                }

                if ($tool['image'] === 'scormbuilder.gif') {
                    // Check if the published learnpath is visible for student
                    $lpId = self::getPublishedLpIdFromLink($tool['link']);
                    if ($session_id && api_is_coach()) {
                        $itemInfo = api_get_item_property_info(
                            $courseId,
                            TOOL_LEARNPATH,
                            $lpId,
                            $session_id
                        );

                        if ($itemInfo && 0 === (int) $itemInfo['visibility']) {
                            $tool['image'] = 'scormbuilder_na.gif';
                        }
                    }

                    if (api_is_allowed_to_edit(null, true)) {
                        $studentview = true;
                    }
                    if (!api_is_allowed_to_edit(null, true) &&
                        !learnpath::is_lp_visible_for_student(
                            $lpId,
                            api_get_user_id(),
                            $courseInfo,
                            $session_id
                        )
                    ) {
                        continue;
                    }
                }

                if ($session_id != 0 && in_array($tool['name'], ['course_setting'])) {
                    continue;
                }

                if (false === $showIcon) {
                    continue;
                }

                // This part displays the links to hide or remove a tool.
                // These links are only visible by the course manager.
                unset($lnk);

                $item['extra'] = null;
                $toolAdmin = isset($tool['admin']) ? $tool['admin'] : '';
                $extraClass = '';
                $isSessionToolVisible = false;
                if ($is_allowed_to_edit && $allowChangeVisibility) {
                    if (empty($session_id)) {
                        if (isset($tool['id'])) {
                            if ($tool['visibility'] == '1' && $toolAdmin != '1') {
                                $isSessionToolVisible = true;
                                $link['name'] = '<em
                                    id="'.'linktool_'.$tool['iid'].'"
                                    class="fa fa-eye"
                                    title="'.get_lang('Deactivate').'"></em>';
                                $link['cmd'] = 'hide=yes';
                                $lnk[] = $link;
                            }
                            if ($tool['visibility'] == '0' && $toolAdmin != '1') {
                                $isSessionToolVisible = false;
                                $link['name'] = '<em
                                    id="'.'linktool_'.$tool['iid'].'"
                                    class="fa fa-eye-slash text-muted"
                                    title="'.get_lang('Activate').'"></em>';
                                $link['cmd'] = 'restore=yes';
                                $extraClass = 'text-muted';
                                $lnk[] = $link;
                            }
                        }
                    } elseif ($allowEditionInSession) {
                        $criteria = [
                            'cId' => $courseId,
                            'name' => $tool['name'],
                            'sessionId' => $session_id,
                        ];
                        /** @var CTool $tool */
                        $toolObj = $toolRepo->findOneBy($criteria);
                        if ($toolObj) {
                            $visibility = (int) $toolObj->getVisibility();
                            switch ($visibility) {
                                case 0:
                                    $isSessionToolVisible = false;
                                    if (in_array($tool['image'], ['scormbuilder.png', 'scormbuilder.gif'])) {
                                        $info = pathinfo($tool['image']);
                                        $basename = basename($tool['image'], '.'.$info['extension']);
                                        $tool['image'] = $basename.'_na.'.$info['extension'];
                                    }
                                    $link['name'] = '<em
                                        id="'.'linktool_'.$tool['iid'].'"
                                        class="fa fa-eye-slash text-muted"
                                        title="'.get_lang('Activate').'"></em>';
                                    $link['cmd'] = 'restore=yes';
                                    $extraClass = 'text-muted';
                                    $lnk[] = $link;
                                    break;
                                case 1:
                                    $isSessionToolVisible = true;
                                    $link['name'] = '<em
                                        id="'.'linktool_'.$tool['iid'].'"
                                        class="fa fa-eye"
                                        title="'.get_lang('Deactivate').'"></em>';
                                    $link['cmd'] = 'hide=yes';
                                    $lnk[] = $link;
                                    break;
                            }
                        } else {
                            $isSessionToolVisible = true;
                            $link['name'] = '<em
                                id="'.'linktool_'.$tool['iid'].'"
                                class="fa fa-eye"
                                title="'.get_lang('Deactivate').'"></em>';
                            $link['cmd'] = 'hide=yes';
                            $lnk[] = $link;
                        }
                    }
                    if (!empty($tool['adminlink']) && $tool['session_id'] == $session_id) {
                        $item['extra'] = '<a href="'.$tool['adminlink'].'">'.
                            Display::return_icon('edit.gif', get_lang('Edit')).
                        '</a>';
                    }
                }

                // Both checks are necessary as is_platform_admin doesn't take student view into account
                if ($is_platform_admin && $is_allowed_to_edit) {
                    if ($toolAdmin != '1') {
                        $link['cmd'] = 'hide=yes';
                    }
                }

                $item['visibility'] = '';
                if (isset($lnk) && is_array($lnk)) {
                    foreach ($lnk as $this_link) {
                        if (empty($tool['adminlink'])) {
                            $item['visibility'] .= '<a
                                class="make_visible_and_invisible"
                                href="'.api_get_self().'?'.api_get_cidreq().'&id='.$tool['iid'].'&'.$this_link['cmd'].'"
                                >'.$this_link['name'].'</a>';
                        }
                    }
                }

                // NOTE : Table contains only the image file name, not full path
                if (stripos($tool['link'], 'http://') === false &&
                    stripos($tool['link'], 'https://') === false &&
                    stripos($tool['link'], 'ftp://') === false
                ) {
                    $tool['link'] = $web_code_path.$tool['link'];
                }

                $class = '';
                $info = pathinfo($tool['image']);
                $basename = basename($tool['image'], '.'.$info['extension']);

                if ($tool['visibility'] == '0' && $toolAdmin != '1') {
                    $class = 'text-muted';
                    if (!strpos($tool['image'], '_na')) {
                        $tool['image'] = $basename.'_na.'.$info['extension'];
                    }
                }

                if ($is_allowed_to_edit && $allowChangeVisibility &&
                    ($tool['image'] === 'scormbuilder.gif' || $tool['image'] === 'scormbuilder_na.gif')
                ) {
                    if (false === $isSessionToolVisible) {
                        $tool['image'] = 'scormbuilder_na.gif';
                    } else {
                        $tool['image'] = 'scormbuilder.gif';
                    }
                }

                if ($is_allowed_to_edit && $allowChangeVisibility &&
                    ($tool['image'] === 'lp_category.gif' || $tool['image'] === 'lp_category_na.gif')
                ) {
                    if (false === $isSessionToolVisible) {
                        $tool['image'] = 'lp_category_na.gif';
                    } else {
                        $tool['image'] = 'lp_category.gif';
                    }
                }

                $qm_or_amp = strpos($tool['link'], '?') === false ? '?' : '&';

                // If it's a link, we don't add the cidReq
                if ($tool['image'] === 'file_html.png' || $tool['image'] === 'file_html_na.png') {
                    $tool['link'] = $tool['link'];
                } else {
                    if (!in_array($tool['image'], ['lp_category.gif', 'lp_category_na.gif'])) {
                        $tool['link'] = $tool['link'].$qm_or_amp.api_get_cidreq(true, false).'&gidReq=0';
                    }
                }

                $toolIid = isset($tool['iid']) ? $tool['iid'] : null;

                //@todo this visio stuff should be removed
                if (strpos($tool['name'], 'visio_') !== false) {
                    $tool_link_params = [
                        'id' => 'tooldesc_'.$toolIid,
                        'href' => '"javascript: void(0);"',
                        'class' => $class,
                        'onclick' => 'javascript: window.open(\''.$tool['link'].'\',\'window_visio'.api_get_course_id().'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')',
                        'target' => $tool['target'],
                    ];
                } elseif (strpos($tool['name'], 'chat') !== false &&
                    api_get_course_setting('allow_open_chat_window')
                ) {
                    $tool_link_params = [
                        'id' => 'tooldesc_'.$toolIid,
                        'class' => $class,
                        'href' => 'javascript: void(0);',
                        'onclick' => 'javascript: window.open(\''.$tool['link'].'\',\'window_chat'.api_get_course_id().'\',config=\'height=\'+600+\', width=\'+825+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')', //Chat Open Windows
                        'target' => $tool['target'],
                    ];
                } else {
                    $tool_link_params = [
                        'id' => 'tooldesc_'.$toolIid,
                        'href' => $tool['link'],
                        'class' => "$class $extraClass ",
                        'target' => $tool['target'],
                    ];
                }

                $tool_name = self::translate_tool_name($tool);

                // Including Courses Plugins
                // Creating title and the link
                if (isset($tool['category']) && $tool['category'] === 'plugin') {
                    $plugin_info = $app_plugin->getPluginInfo($tool['name'], true);
                    if (isset($plugin_info) && isset($plugin_info['title'])) {
                        $tool_name = $plugin_info['title'];
                    }

                    if (!file_exists(api_get_path(SYS_CODE_PATH).'img/'.$tool['image']) &&
                        !file_exists(api_get_path(SYS_CODE_PATH).'img/icons/64/'.$tool['image'])) {
                        $tool['image'] = 'plugins.png';
                    }
                    $tool_link_params['href'] = api_get_path(WEB_PLUGIN_PATH)
                        .$tool['original_link'].$qm_or_amp.api_get_cidreq();
                }

                // Use in the course home
                $icon = Display::return_icon(
                    $tool['image'],
                    $tool_name,
                    ['class' => 'tool-icon', 'id' => 'toolimage_'.$toolIid],
                    ICON_SIZE_BIG,
                    false
                );

                // Used in the top bar
                $iconMedium = Display::return_icon(
                    $tool['image'],
                    $tool_name,
                    ['class' => 'tool-icon', 'id' => 'toolimage_'.$toolIid],
                    ICON_SIZE_MEDIUM,
                    false
                );

                // Used for vertical navigation
                $iconSmall = Display::return_icon(
                    $tool['image'],
                    $tool_name,
                    ['class' => 'tool-img', 'id' => 'toolimage_'.$toolIid],
                    ICON_SIZE_SMALL,
                    false
                );

                // Validation when belongs to a session
                $session_img = api_get_session_image(
                    $tool['session_id'],
                    !empty($_user['status']) ? $_user['status'] : ''
                );
                if ($studentview) {
                    $tool_link_params['href'] .= '&isStudentView=true';
                }
                $item['url_params'] = $tool_link_params;
                $item['icon'] = Display::url($icon, $tool_link_params['href'], $tool_link_params);
                $item['only_icon'] = $icon;
                $item['only_icon_medium'] = $iconMedium;
                $item['only_icon_small'] = $iconSmall;
                $item['only_href'] = $tool_link_params['href'];
                $item['tool'] = $tool;
                $item['name'] = $tool_name;
                $tool_link_params['id'] = 'is'.$tool_link_params['id'];
                $item['link'] = Display::url(
                    $tool_name.$session_img,
                    $tool_link_params['href'],
                    $tool_link_params
                );
                $items[] = $item;
            }
        }

        foreach ($items as &$item) {
            $originalImage = self::getToolIcon($item, ICON_SIZE_BIG);
            $item['tool']['only_icon_medium'] = self::getToolIcon($item, ICON_SIZE_MEDIUM, false);
            $item['tool']['only_icon_small'] = self::getToolIcon($item, ICON_SIZE_SMALL, false);
            if ($theme === 'activity_big') {
                $item['tool']['image'] = Display::url(
                    $originalImage,
                    $item['url_params']['href'],
                    $item['url_params']
                );
            }
        }

        return $items;
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
            $output .= '<tr><td>'.get_lang('SessionCategory').': '.'<b>'.$session_category.'</b></td></tr>';
        }
        $dateInfo = SessionManager::parseSessionDates($sessionInfo);

        $msgDate = $dateInfo['access'];
        $output .= '<tr>
                    <td style="width:50%">'.get_lang('SessionName').': '.'<b>'.$sessionInfo['name'].'</b></td>
                    <td>'.get_lang('GeneralCoach').': '.'<b>'.$coachInfo['complete_name'].'</b></td></tr>';
        $output .= '<tr>
                        <td>'.get_lang('SessionIdentifier').': '.
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
    public static function translate_tool_name(&$tool)
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

        $toolName = Security::remove_XSS(stripslashes(strip_tags($tool['name'])));

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

        $showOnlyText = api_get_setting('show_navigation_menu') === 'text';
        $showOnlyIcons = api_get_setting('show_navigation_menu') === 'icons';

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
        if ($origin === 'learnpath') {
            return '';
        }

        $blocks = self::getUserBlocks();
        $html = '';
        if (!empty($blocks)) {
            $styleId = 'toolshortcuts_vertical';
            if ($orientation == SHORTCUTS_HORIZONTAL) {
                $styleId = 'toolshortcuts_horizontal';
            }
            $html .= '<div id="'.$styleId.'">';

            $html .= Display::url(
                Display::return_icon('home.png', get_lang('CourseHomepageLink'), '', ICON_SIZE_MEDIUM),
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
                    if ($orientation == SHORTCUTS_VERTICAL) {
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

        if (isset($_FILES['icon']['size']) && $_FILES['icon']['size'] !== 0) {
            $dir = self::getCustomSysIconPath();

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

            if ($r === false) {
                error_log('Conversion to B&W of '.$path.' failed in '.__FILE__.' at line '.__LINE__);
            } else {
                $temp->send_image($bwPath);
                $iconName = $_FILES['icon']['name'];
                $params['custom_icon'] = $iconName;
            }
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
     * @return array
     */
    public static function getCourseAdminBlocks()
    {
        $blocks = [];
        $my_list = self::get_tools_category(TOOL_AUTHORING);

        $blocks[] = [
            'title' => get_lang('Authoring'),
            'class' => 'course-tools-author',
            'content' => self::show_tools_category($my_list),
        ];

        $list1 = self::get_tools_category(TOOL_INTERACTION);
        $list2 = self::get_tools_category(TOOL_COURSE_PLUGIN);
        $my_list = array_merge($list1, $list2);

        $blocks[] = [
            'title' => get_lang('Interaction'),
            'class' => 'course-tools-interaction',
            'content' => self::show_tools_category($my_list),
        ];

        $my_list = self::get_tools_category(TOOL_ADMIN_PLATFORM);

        $blocks[] = [
            'title' => get_lang('Administration'),
            'class' => 'course-tools-administration',
            'content' => self::show_tools_category($my_list),
        ];

        return $blocks;
    }

    /**
     * @return array
     */
    public static function getCoachBlocks()
    {
        $blocks = [];
        $blocks[] = [
            'content' => self::show_tools_category(self::get_tools_category(TOOL_STUDENT_VIEW)),
        ];

        $sessionsCopy = api_get_setting('allow_session_course_copy_for_teachers');
        if ($sessionsCopy === 'true') {
            // Adding only maintenance for coaches.
            $myList = self::get_tools_category(TOOL_ADMIN_PLATFORM);
            $onlyMaintenanceList = [];
            foreach ($myList as $item) {
                if ($item['name'] === 'course_maintenance') {
                    $item['link'] = 'course_info/maintenance_coach.php';
                    $onlyMaintenanceList[] = $item;
                }
            }

            $blocks[] = [
                'title' => get_lang('Administration'),
                'content' => self::show_tools_category($onlyMaintenanceList),
            ];
        }

        return $blocks;
    }

    /**
     * @return array
     */
    public static function getStudentBlocks()
    {
        $blocks = [];
        $tools = self::get_tools_category(TOOL_STUDENT_VIEW);
        $isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
            api_get_user_id(),
            api_get_course_info()
        );

        // Force user icon for DRH
        if ($isDrhOfCourse) {
            $addUserTool = true;
            foreach ($tools as $tool) {
                if ($tool['name'] === 'user') {
                    $addUserTool = false;
                    break;
                }
            }

            if ($addUserTool) {
                $tools[] = [
                    'c_id' => api_get_course_int_id(),
                    'name' => 'user',
                    'link' => 'user/user.php',
                    'image' => 'members.gif',
                    'visibility' => '1',
                    'admin' => '0',
                    'address' => 'squaregrey.gif',
                    'added_tool' => '0',
                    'target' => '_self',
                    'category' => 'interaction',
                    'session_id' => api_get_session_id(),
                ];
            }
        }

        if (count($tools) > 0) {
            $blocks[] = ['content' => self::show_tools_category($tools)];
        }

        if ($isDrhOfCourse) {
            $drhTool = self::get_tools_category(TOOL_DRH);
            $blocks[] = ['content' => self::show_tools_category($drhTool)];
        }

        return $blocks;
    }

    /**
     * @return array
     */
    public static function getUserBlocks()
    {
        $sessionId = api_get_session_id();
        // Start of tools for CourseAdmins (teachers/tutors)
        if ($sessionId === 0 && api_is_course_admin() && api_is_allowed_to_edit(null, true)) {
            $blocks = self::getCourseAdminBlocks();
        } elseif (api_is_coach()) {
            $blocks = self::getCoachBlocks();
        } else {
            $blocks = self::getStudentBlocks();
        }

        return $blocks;
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

        $toolLink = self::getToolLinks($toolName);

        $em = Database::getManager();
        $toolRepo = $em->getRepository('ChamiloCourseBundle:CTool');

        /** @var CTool $tool */
        $tool = $toolRepo->findOneBy(['cId' => $courseId, 'sessionId' => 0, 'link' => $toolLink]);

        if ($allowEditionInSession && $sessionId) {
            $tool = $toolRepo->findOneBy(
                ['cId' => $courseId, 'sessionId' => $sessionId, 'link' => $toolLink]
            );
        }

        if ($tool) {
            $visibility = $tool->getVisibility();
        }

        return $visibility;
    }

    /**
     * Get the tools links or one by name defined.
     *
     * @param $tool  Optional
     *
     * @return string|string[]
     */
    public static function getToolLinks($tool = null)
    {
        $toolsLinks = [
            TOOL_DOCUMENT => 'document/document.php',
            TOOL_CALENDAR_EVENT => 'calendar/agenda.php',
            TOOL_LINK => 'link/link.php',
            TOOL_COURSE_DESCRIPTION => 'course_description/index.php',
            TOOL_LEARNPATH => 'lp/lp_controller.php',
            TOOL_ANNOUNCEMENT => 'announcements/announcements.php',
            TOOL_FORUM => 'forum/index.php',
            TOOL_DROPBOX => 'dropbox/index.php',
            TOOL_QUIZ => 'exercise/exercise.php',
            TOOL_USER => 'user/user.php',
            TOOL_GROUP => 'group/group.php',
            TOOL_BLOGS => 'blog/blog_admin.php',
            TOOL_CHAT => 'chat/chat.php',
            TOOL_STUDENTPUBLICATION => 'work/work.php',
            TOOL_TRACKING => 'tracking/courseLog.php',
            TOOL_COURSE_SETTING => 'course_info/infocours.php',
            TOOL_PORTFOLIO => 'portfolio/index.php',
            TOOL_COURSE_MAINTENANCE => 'course_info/maintenance.php',
            TOOL_SURVEY => 'survey/survey_list.php',
            TOOL_WIKI => 'wiki/index.php',
            TOOL_GLOSSARY => 'glossary/index.php',
            TOOL_GRADEBOOK => 'gradebook/index.php',
            TOOL_NOTEBOOK => 'notebook/index.php',
            TOOL_ATTENDANCE => 'attendance/index.php',
            TOOL_COURSE_PROGRESS => 'course_progress/index.php',
        ];

        if (!empty($tool) && isset($toolsLinks['tool'])) {
            return $toolsLinks['tool'];
        }

        return $toolsLinks;
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

        if ($courseToolCategory == TOOL_STUDENT_VIEW) {
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

                if ($pos !== false) {
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

            if ($item['tool']['visibility'] == '0') {
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
