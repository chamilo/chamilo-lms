<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Entity\CLpCategory;

/**
 * Class CourseHome
 */
class CourseHome
{
    /**
     * Gets the html content to show in the 3 column view
     * @param string $cat
     * @param int $userId
     * @return string
     */
    public static function show_tool_3column($cat, $userId = null)
    {
        $_user = api_get_user_info($userId);

        $TBL_ACCUEIL = Database::get_course_table(TABLE_TOOL_LIST);
        $TABLE_TOOLS = Database::get_main_table(TABLE_MAIN_COURSE_MODULE);

        $numcols = 3;
        $table = new HTML_Table('width="100%"');
        $all_tools = array();

        $course_id = api_get_course_int_id();

        switch ($cat) {
            case 'Basic':
                $condition_display_tools = ' WHERE a.c_id = '.$course_id.' AND  a.link=t.link AND t.position="basic" ';
                if ((api_is_coach() || api_is_course_tutor()) && $_SESSION['studentview'] != 'studentview') {
                    $condition_display_tools = ' WHERE a.c_id = '.$course_id.' AND a.link=t.link AND (t.position="basic" OR a.name = "'.TOOL_TRACKING.'") ';
                }

                $sql = "SELECT a.*, t.image img, t.row, t.column  FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
                        $condition_display_tools ORDER BY t.row, t.column";
                break;
            case 'External':
                if (api_is_allowed_to_edit()) {
                    $sql = "SELECT a.*, t.image img FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
                            WHERE a.c_id = $course_id AND ((a.link=t.link AND t.position='external')
                            OR (a.visibility <= 1 AND (a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND a.image=t.image))
                            ORDER BY a.id";
                } else {
                    $sql = "SELECT a.*, t.image img FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
                            WHERE a.c_id = $course_id AND (a.visibility = 1 AND ((a.link=t.link AND t.position='external')
                            OR ((a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND a.image=t.image)))
                            ORDER BY a.id";
                }
                break;
            case 'courseAdmin':
                $sql = "SELECT a.*, t.image img, t.row, t.column  FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
                        WHERE a.c_id = $course_id AND admin=1 AND a.link=t.link ORDER BY t.row, t.column";
                break;

            case 'platformAdmin':
                $sql = "SELECT *, image img FROM $TBL_ACCUEIL WHERE c_id = $course_id AND visibility = 2 ORDER BY id";
        }
        $result = Database::query($sql);

        // Grabbing all the tools from $course_tool_table
        while ($tool = Database::fetch_array($result)) {
            $all_tools[] = $tool;
        }

        $course_id = api_get_course_int_id();

        // Grabbing all the links that have the property on_homepage set to 1
        if ($cat == 'External') {
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
                $properties = array();
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

        foreach ($all_tools as & $tool) {
            if ($tool['image'] == 'scormbuilder.gif') {
                // check if the published learnpath is visible for student
                $published_lp_id = self::get_published_lp_id_from_link($tool['link']);
                if (!api_is_allowed_to_edit(null, true) &&
                    !learnpath::is_lp_visible_for_student(
                        $published_lp_id,
                        api_get_user_id(),
                        api_get_course_id(),
                        api_get_session_id()
                    )
                ) {
                    continue;
                }
            }

            if (api_get_session_id() != 0 &&
                in_array($tool['name'], array('course_maintenance', 'course_setting'))
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

            // VISIBLE
            if (($tool['visibility'] ||
                ((api_is_coach() || api_is_course_tutor()) && $tool['name'] == TOOL_TRACKING)) ||
                $cat == 'courseAdmin' || $cat == 'platformAdmin'
            ) {
                if (strpos($tool['name'], 'visio_') !== false) {
                    $cell_content .= '<a  href="javascript: void(0);" onclick="javascript: window.open(\''.$tool['link'].$link_annex.'\',\'window_visio'.api_get_course_id().'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="'.$tool['target'].'"><img src="'.$tool['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                } elseif (strpos($tool['name'], 'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
                    $cell_content .= '<a href="javascript: void(0);" onclick="javascript: window.open(\''.$tool['link'].$link_annex.'\',\'window_chat'.api_get_course_id().'\',config=\'height=\'+600+\', width=\'+825+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="'.$tool['target'].'"><img src="'.$tool['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                    // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                } else {
                    $cell_content .= '<a href="'.$tool['link'].$link_annex.'" target="'.$tool['target'].'"><img src="'.$tool['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                    // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                }
            } else {
                // INVISIBLE
                if (api_is_allowed_to_edit(null, true)) {
                    if (strpos($tool['name'], 'visio_') !== false) {
                        $cell_content .= '<a  href="javascript: void(0);" onclick="window.open(\''.$tool['link'].$link_annex.'\',\'window_visio'.api_get_course_id().'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="'.$tool['target'].'"><img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                    } elseif (strpos($tool['name'], 'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
                        $cell_content .= '<a href="javascript: void(0);" onclick="javascript: window.open(\''.$tool['link'].$link_annex.'\',\'window_chat'.api_get_course_id().'\',config=\'height=\'+600+\', width=\'+825+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="'.$tool['target'].'" class="text-muted"><img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                        // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                    } else {
                        $cell_content .= '<a href="'.$tool['link'].$link_annex.'" target="'.$tool['target'].'" class="text-muted">
                                            <img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
                        // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                    }
                } else {
                    $cell_content .= '<img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">';
                    // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
                    $cell_content .= '<span class="text-muted">'.$tool_name.'</span>';
                }
            }

            $lnk = array();
            if (api_is_allowed_to_edit(null, true) &&
                $cat != "courseAdmin" &&
                !strpos($tool['link'], 'learnpath_handler.php?learnpath_id') &&
                !api_is_coach()
            ) {
                if ($tool['visibility']) {
                    $link['name'] = Display::return_icon(
                        'remove.gif',
                        get_lang('Deactivate'),
                        array('style' => 'vertical-align: middle;')
                    );
                    $link['cmd'] = "hide=yes";
                    $lnk[] = $link;
                } else {
                    $link['name'] = Display::return_icon(
                        'add.gif',
                        get_lang('Activate'),
                        array('style' => 'vertical-align: middle;')
                    );
                    $link['cmd'] = "restore=yes";
                    $lnk[] = $link;
                }
                if (is_array($lnk)) {
                    foreach ($lnk as & $this_lnk) {
                        if ($tool['adminlink']) {
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
     * @return void
     * @param string $course_tool_category	contains the category of tools to display:
     * "Public", "PublicButHide", "courseAdmin", "claroAdmin"
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
                if ((api_is_coach() || api_is_course_tutor()) && $_SESSION['studentview'] != 'studentview') {
                    $condition_display_tools = ' WHERE c_id = '.$course_id.' AND (visibility = 1 OR (visibility = 0 AND name = "'.TOOL_TRACKING.'")) ';
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
                        LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tl.c_id = tip.c_id AND tl.c_id = $course_id AND tip.ref=tl.id
                        WHERE tl.on_homepage='1' AND tip.visibility = 1";
                break;
            case TOOL_PUBLIC_BUT_HIDDEN:
                $sql_links = "SELECT tl.*, tip.visibility
                    FROM $course_link_table tl
                    LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tl.c_id = tip.c_id AND tl.c_id = $course_id AND tip.ref=tl.id
                    WHERE tl.on_homepage='1' AND tip.visibility = 0";

                break;
            default:
                $sql_links = null;
                break;
        }
        if ($sql_links != null) {
            $properties = array();
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
        if (isset($all_tools_list)) {
            $lnk = array();
            foreach ($all_tools_list as & $tool) {
                if ($tool['image'] == 'scormbuilder.gif') {
                    // check if the published learnpath is visible for student
                    $published_lp_id = self::get_published_lp_id_from_link($tool['link']);

                    if (!api_is_allowed_to_edit(null, true) &&
                        !learnpath::is_lp_visible_for_student(
                            $published_lp_id,
                            api_get_user_id(),
                            api_get_course_id(),
                            api_get_session_id()
                        )
                    ) {
                        continue;
                    }
                }

                if (api_get_session_id() != 0 &&
                    in_array($tool['name'], array('course_maintenance', 'course_setting'))
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
                    $html .= '<a href="'.htmlspecialchars($tool['link']).(($tool['image'] == 'external.gif' || $tool['image'] == 'external_na.gif') ? '' : $qm_or_amp.api_get_cidreq()).'" target="'.$tool['target'].'" '.$class.'>';
                }

                $tool_name = self::translate_tool_name($tool);
                $html .= Display::return_icon(
                        $tool['image'],
                        $tool_name,
                        array(),
                        null,
                        ICON_SIZE_MEDIUM
                    ).'&nbsp;'.$tool_name.'</a>';

                // This part displays the links to hide or remove a tool.
                // These links are only visible by the course manager.
                unset($lnk);
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
                        $html .= '<a href="'.$tool['adminlink'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
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
                    foreach ($lnk as & $this_link) {
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
     * by show_tools_category()
     * @param string $course_tool_category contains the category of tools to
     * display: "toolauthoring", "toolinteraction", "tooladmin", "tooladminplatform", "toolplugin"
     * @param int $courseId Optional
     * @param int $sessionId Optional
     * @return array
     */
    public static function get_tools_category($course_tool_category, $courseId = 0, $sessionId = 0)
    {
        $course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);
        $is_platform_admin = api_is_platform_admin();
        $all_tools_list = array();

        // Condition for the session
        $session_id = $sessionId ?: api_get_session_id();
        $course_id = $courseId ?: api_get_course_int_id();
        $userId = api_get_user_id();
        $user = api_get_user_entity($userId);
        $condition_session = api_get_session_condition(
            $session_id,
            true,
            true,
            't.session_id'
        );

        switch ($course_tool_category) {
            case TOOL_STUDENT_VIEW:
                $conditions = ' WHERE visibility = 1 AND (category = "authoring" OR category = "interaction" OR category = "plugin") ';
                if ((api_is_coach() || api_is_course_tutor()) && $_SESSION['studentview'] != 'studentview') {
                    $conditions = ' WHERE (visibility = 1 AND (category = "authoring" OR category = "interaction" OR category = "plugin") OR (name = "'.TOOL_TRACKING.'") )   ';
                }
                $sql = "SELECT *
                        FROM $course_tool_table t
                        $conditions AND
                        c_id = $course_id $condition_session
                        ORDER BY id";
                $result = Database::query($sql);
                break;
            case TOOL_AUTHORING:
                $sql = "SELECT * FROM $course_tool_table t
                        WHERE category = 'authoring' AND c_id = $course_id $condition_session
                        ORDER BY id";
                $result = Database::query($sql);
                break;
            case TOOL_INTERACTION:
                $sql = "SELECT * FROM $course_tool_table t
                        WHERE category = 'interaction' AND c_id = $course_id $condition_session
                        ORDER BY id";
                $result = Database::query($sql);
                break;
            case TOOL_ADMIN_VISIBLE:
                $sql = "SELECT * FROM $course_tool_table t
                        WHERE category = 'admin' AND visibility ='1' AND c_id = $course_id $condition_session
                        ORDER BY id";
                $result = Database::query($sql);
                break;
            case TOOL_ADMIN_PLATFORM:
                $sql = "SELECT * FROM $course_tool_table t
                        WHERE category = 'admin' AND c_id = $course_id $condition_session
                        ORDER BY id";
                $result = Database::query($sql);
                break;
            case TOOL_DRH:
                $sql = "SELECT * FROM $course_tool_table t
                        WHERE name IN ('tracking') AND c_id = $course_id $condition_session
                        ORDER BY id";
                $result = Database::query($sql);
                break;
            case TOOL_COURSE_PLUGIN:
                //Other queries recover id, name, link, image, visibility, admin, address, added_tool, target, category and session_id
                // but plugins are not present in the tool table, only globally and inside the course_settings table once configured
                $sql = "SELECT * FROM $course_tool_table t
                        WHERE category = 'plugin' AND name <> 'courseblock' AND c_id = $course_id $condition_session
                        ORDER BY id";
                $result = Database::query($sql);
                break;
        }

        //Get the list of hidden tools - this might imply performance slowdowns
        // if the course homepage is loaded many times, so the list of hidden
        // tools might benefit from a shared memory storage later on
        $list = api_get_settings('Tools', 'list', api_get_current_access_url_id());
        $hide_list = array();
        $check = false;

        foreach ($list as $line) {
            // Admin can see all tools even if the course_hide_tools configuration is set
            if ($is_platform_admin) {
                continue;
            }
            if ($line['variable'] == 'course_hide_tools' and $line['selected_value'] == 'true') {
                $hide_list[] = $line['subkey'];
                $check = true;
            }
        }

        $allowEditionInSession = api_get_configuration_value('allow_edit_tool_visibility_in_session');
        while ($temp_row = Database::fetch_assoc($result)) {
            $add = false;
            if ($check) {
                if (!in_array($temp_row['name'], $hide_list)) {
                    $add = true;
                }
            } else {
                $add = true;
            }

            if ($allowEditionInSession && !empty($session_id)) {
                // Checking if exist row in session
                $criteria = [
                    'cId' => $course_id,
                    'name' => $temp_row['name'],
                    'sessionId' => $session_id,
                ];
                /** @var CTool $tool */
                $toolObj = Database::getManager()->getRepository('ChamiloCourseBundle:CTool')->findOneBy($criteria);
                if ($toolObj) {
                    if ($toolObj->getVisibility() == 0) {
                        continue;
                    }
                }
            }

            if ($temp_row['image'] == 'scormbuilder.gif') {
                $lp_id = self::get_published_lp_id_from_link($temp_row['link']);
                $lp = new learnpath(
                    api_get_course_id(),
                    $lp_id,
                    $userId
                );
                $path = $lp->get_preview_image_path(ICON_SIZE_BIG);
                $add = learnpath::is_lp_visible_for_student(
                    $lp_id,
                    $userId,
                    api_get_course_id(),
                    api_get_session_id()
                );
                if ($path) {
                    $temp_row['custom_image'] = $path;
                }
            }

            if ($temp_row['image'] === 'lp_category.gif') {
                $lpCategory = self::getPublishedLpCategoryFromLink(
                    $temp_row['link']
                );
                $add = learnpath::categoryIsVisibleForStudent(
                    $lpCategory,
                    $user
                );
            }

            if ($add) {
                $all_tools_list[] = $temp_row;
            }
        }

        // Grabbing all the links that have the property on_homepage set to 1
        $course_link_table = Database::get_course_table(TABLE_LINK);
        $course_item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $condition_session = api_get_session_condition(
            $session_id,
            true,
            true,
            'tip.session_id'
        );

        switch ($course_tool_category) {
            case TOOL_AUTHORING:
                $sql_links = "SELECT tl.*, tip.visibility
                    FROM $course_link_table tl
                    LEFT JOIN $course_item_property_table tip
                    ON tip.tool='link' AND tip.ref=tl.id
                    WHERE
                        tl.c_id = $course_id AND
                        tip.c_id = $course_id AND
                        tl.on_homepage='1' $condition_session";
                break;
            case TOOL_INTERACTION:
                $sql_links = null;
                /*
                  $sql_links = "SELECT tl.*, tip.visibility
                  FROM $course_link_table tl
                  LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
                  WHERE tl.on_homepage='1' ";
                 */
                break;
            case TOOL_STUDENT_VIEW:
                $sql_links = "SELECT tl.*, tip.visibility
                    FROM $course_link_table tl
                    LEFT JOIN $course_item_property_table tip
                    ON tip.tool='link' AND tip.ref=tl.id
                    WHERE
                        tl.c_id 		= $course_id AND
                        tip.c_id 		= $course_id AND
                        tl.on_homepage	='1' $condition_session";
                break;
            case TOOL_ADMIN:
                $sql_links = "SELECT tl.*, tip.visibility
                    FROM $course_link_table tl
                    LEFT JOIN $course_item_property_table tip
                    ON tip.tool='link' AND tip.ref=tl.id
                    WHERE
                        tl.c_id = $course_id AND
                        tip.c_id = $course_id AND
                        tl.on_homepage='1' $condition_session";
                break;
            default:
                $sql_links = null;
                break;
        }

        // Edited by Kevin Van Den Haute (kevin@develop-it.be) for integrating Smartblogs
        if ($sql_links != null) {
            $result_links = Database::query($sql_links);

            if (Database::num_rows($result_links) > 0) {
                while ($links_row = Database::fetch_array($result_links, 'ASSOC')) {
                    $properties = array();
                    $properties['name'] = $links_row['title'];
                    $properties['session_id'] = $links_row['session_id'];
                    $properties['link'] = $links_row['url'];
                    $properties['visibility'] = $links_row['visibility'];
                    $properties['image'] = $links_row['visibility'] == '0' ? 'file_html.png' : 'file_html.png';
                    $properties['adminlink'] = api_get_path(WEB_CODE_PATH).'link/link.php?action=editlink&id='.$links_row['id'];
                    $properties['target'] = $links_row['target'];
                    $tmp_all_tools_list[] = $properties;
                }
            }
        }

        if (isset($tmp_all_tools_list)) {
            foreach ($tmp_all_tools_list as $tool) {
                if ($tool['image'] == 'blog.gif') {
                    // Init
                    $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);

                    // Get blog id
                    $blog_id = substr($tool['link'], strrpos($tool['link'], '=') + 1, strlen($tool['link']));

                    // Get blog members
                    if ($is_platform_admin) {
                        $sql_blogs = "SELECT * FROM $tbl_blogs_rel_user blogs_rel_user
                                      WHERE blog_id =".$blog_id;
                    } else {
                        $sql_blogs = "SELECT * FROM $tbl_blogs_rel_user blogs_rel_user
                                      WHERE blog_id =".$blog_id." AND user_id = ".$userId;
                    }
                    $result_blogs = Database::query($sql_blogs);

                    if (Database::num_rows($result_blogs) > 0) {
                        $all_tools_list[] = $tool;
                    }
                } else {
                    $all_tools_list[] = $tool;
                }
            }
        }

        return $all_tools_list;
    }

    /**
     * Displays the tools of a certain category.
     * @param array $all_tools_list List of tools as returned by get_tools_category()
     * @param bool  $rows
     *
     * @return string
     */
    public static function show_tools_category($all_tools_list, $rows = false)
    {
        $_user = api_get_user_info();
        $theme = api_get_setting('homepage_view');
        if ($theme === 'vertical_activity') {
            //ordering by get_lang name
            $order_tool_list = array();
            if (is_array($all_tools_list) && count($all_tools_list) > 0) {
                foreach ($all_tools_list as $key => $new_tool) {
                    $tool_name = self::translate_tool_name($new_tool);
                    $order_tool_list [$key] = $tool_name;
                }
                natsort($order_tool_list);
                $my_temp_tool_array = array();
                foreach ($order_tool_list as $key => $new_tool) {
                    $my_temp_tool_array[] = $all_tools_list[$key];
                }
                $all_tools_list = $my_temp_tool_array;
            } else {
                $all_tools_list = array();
            }
        }
        $web_code_path = api_get_path(WEB_CODE_PATH);
        $session_id = api_get_session_id();
        $is_platform_admin = api_is_platform_admin();
        $allowEditionInSession = api_get_configuration_value('allow_edit_tool_visibility_in_session');

        if ($session_id == 0) {
            $is_allowed_to_edit = api_is_allowed_to_edit(null, true) && api_is_course_admin();
        } else {
            $is_allowed_to_edit = api_is_allowed_to_edit(null, true) && !api_is_coach();
            if ($allowEditionInSession) {
                $is_allowed_to_edit = api_is_allowed_to_edit(null, true) && api_is_coach($session_id, api_get_course_int_id());
            }
        }

        $i = 0;
        $items = array();
        $app_plugin = new AppPlugin();

        if (isset($all_tools_list)) {
            $lnk = '';
            foreach ($all_tools_list as & $tool) {
                $item = array();
                $studentview = false;
                $tool['original_link'] = $tool['link'];
                if ($tool['image'] == 'scormbuilder.gif') {
                    // check if the published learnpath is visible for student
                    $published_lp_id = self::get_published_lp_id_from_link($tool['link']);
                    if (api_is_allowed_to_edit(null, true)) {
                        $studentview = true;
                    }
                    if (!api_is_allowed_to_edit(null, true) &&
                        !learnpath::is_lp_visible_for_student(
                            $published_lp_id,
                            api_get_user_id(),
                            api_get_course_id(),
                            api_get_session_id()
                        )
                    ) {
                        continue;
                    }
                }

                if ($session_id != 0 && in_array($tool['name'], array('course_setting'))) {
                    continue;
                }

                // This part displays the links to hide or remove a tool.
                // These links are only visible by the course manager.
                unset($lnk);

                $item['extra'] = null;
                $toolAdmin = isset($tool['admin']) ? $tool['admin'] : '';

                if ($is_allowed_to_edit) {
                    if (empty($session_id)) {
                        if (isset($tool['id'])) {
                            if ($tool['visibility'] == '1' && $toolAdmin != '1') {
                                $link['name'] = Display::return_icon(
                                    'visible.png',
                                    get_lang('Deactivate'),
                                    array('id' => 'linktool_'.$tool['iid']),
                                    ICON_SIZE_SMALL,
                                    false
                                );
                                $link['cmd'] = 'hide=yes';
                                $lnk[] = $link;
                            }
                            if ($tool['visibility'] == '0' && $toolAdmin != '1') {
                                $link['name'] = Display::return_icon(
                                    'invisible.png',
                                    get_lang('Activate'),
                                    array('id' => 'linktool_'.$tool['iid']),
                                    ICON_SIZE_SMALL,
                                    false
                                );
                                $link['cmd'] = 'restore=yes';
                                $lnk[] = $link;
                            }
                        }
                    } elseif ($allowEditionInSession) {
                        $criteria = [
                            'cId' => api_get_course_int_id(),
                            'name' => $tool['name'],
                            'sessionId' => $session_id
                        ];
                        /** @var CTool $tool */
                        $toolObj = Database::getManager()->getRepository('ChamiloCourseBundle:CTool')->findOneBy($criteria);
                        if ($toolObj) {
                            $visibility = $toolObj->getVisibility();
                            switch ($visibility) {
                                case '0':
                                    $link['name'] = Display::return_icon(
                                        'invisible.png',
                                        get_lang('Activate'),
                                        array('id' => 'linktool_'.$tool['iid']),
                                        ICON_SIZE_SMALL,
                                        false
                                    );
                                    $link['cmd'] = 'restore=yes';
                                    $lnk[] = $link;
                                    break;
                                case '1':
                                    $link['name'] = Display::return_icon(
                                        'visible.png',
                                        get_lang('Deactivate'),
                                        array('id' => 'linktool_'.$tool['iid']),
                                        ICON_SIZE_SMALL,
                                        false
                                    );
                                    $link['cmd'] = 'hide=yes';
                                    $lnk[] = $link;
                                    break;
                            }
                        } else {
                            $link['name'] = Display::return_icon(
                                'visible.png',
                                get_lang('Deactivate'),
                                array('id' => 'linktool_'.$tool['iid']),
                                ICON_SIZE_SMALL,
                                false
                            );
                            $link['cmd'] = 'hide=yes';
                            $lnk[] = $link;
                        }
                    }
                    if (!empty($tool['adminlink'])) {
                        $item['extra'] = '<a href="'.$tool['adminlink'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
                    }
                }

                // Both checks are necessary as is_platform_admin doesn't take student view into account
                if ($is_platform_admin && $is_allowed_to_edit) {
                    if ($toolAdmin != '1') {
                        $link['cmd'] = 'hide=yes';
                    }
                }

                $item['visibility'] = null;
                if (isset($lnk) && is_array($lnk)) {
                    foreach ($lnk as $this_link) {
                        if (empty($tool['adminlink'])) {
                            $item['visibility'] .= '<a class="make_visible_and_invisible" href="'.api_get_self().'?'.api_get_cidreq().'&id='.$tool['iid'].'&'.$this_link['cmd'].'">'.
                                $this_link['name'].'</a>';
                        }
                    }
                } else {
                    $item['visibility'] .= '';
                }

                // NOTE : Table contains only the image file name, not full path
                if (stripos($tool['link'], 'http://') === false &&
                    stripos($tool['link'], 'https://') === false &&
                    stripos($tool['link'], 'ftp://') === false
                ) {
                    $tool['link'] = $web_code_path.$tool['link'];
                }

                if ($tool['visibility'] == '0' && $toolAdmin != '1') {
                    $class = 'text-muted';
                    $info = pathinfo($tool['image']);
                    $basename = basename($tool['image'], '.'.$info['extension']); // $file is set to "index"
                    $tool['image'] = $basename.'_na.'.$info['extension'];
                } else {
                    $class = '';
                }

                $qm_or_amp = strpos($tool['link'], '?') === false ? '?' : '&';
                // If it's a link, we don't add the cidReq

                if ($tool['image'] == 'file_html.png' || $tool['image'] == 'file_html_na.png') {
                    $tool['link'] = $tool['link'].$qm_or_amp;
                } else {
                    $tool['link'] = $tool['link'].$qm_or_amp.api_get_cidreq();
                }

                $tool_link_params = array();
                $toolIid = isset($tool["iid"]) ? $tool["iid"] : null;

                //@todo this visio stuff should be removed
                if (strpos($tool['name'], 'visio_') !== false) {
                    $tool_link_params = array(
                        'id' => 'tooldesc_'.$toolIid,
                        'href' => '"javascript: void(0);"',
                        'class' => $class,
                        'onclick' => 'javascript: window.open(\''.$tool['link'].'\',\'window_visio'.api_get_course_id().'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')',
                        'target' => $tool['target']
                    );
                } elseif (strpos($tool['name'], 'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
                    $tool_link_params = array(
                        'id' => 'tooldesc_'.$toolIid,
                        'class' => $class,
                        'href' => 'javascript: void(0);',
                        'onclick' => 'javascript: window.open(\''.$tool['link'].'\',\'window_chat'.api_get_course_id().'\',config=\'height=\'+600+\', width=\'+825+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')', //Chat Open Windows
                        'target' => $tool['target']
                    );
                } else {
                    $tool_link_params = array(
                        'id' => 'tooldesc_'.$toolIid,
                        'href' => $tool['link'],
                        'class' => $class,
                        'target' => $tool['target']
                    );
                }

                $tool_name = self::translate_tool_name($tool);

                // Including Courses Plugins
                // Creating title and the link
                if (isset($tool['category']) && $tool['category'] == 'plugin') {
                    $plugin_info = $app_plugin->getPluginInfo($tool['name']);
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

                $icon = Display::return_icon(
                    $tool['image'],
                    $tool_name,
                    array('class' => 'tool-icon', 'id' => 'toolimage_'.$toolIid),
                    ICON_SIZE_BIG,
                    false
                );

                /*if (!empty($tool['custom_icon'])) {
                    $image = self::getCustomWebIconPath().$tool['custom_icon'];
                    $icon = Display::img(
                        $image,
                        $tool['description'],
                        array(
                            'class' => 'tool-icon',
                            'id' => 'toolimage_'.$tool['id']
                        )
                    );
                }*/

                // Validation when belongs to a session
                $session_img = api_get_session_image($tool['session_id'], (!empty($_user['status']) ? $_user['status'] : ''));
                if ($studentview) {
                    $tool_link_params['href'] .= '&isStudentView=true';
                }
                $item['url_params'] = $tool_link_params;
                $item['icon'] = Display::url($icon, $tool_link_params['href'], $tool_link_params);
                $item['tool'] = $tool;
                $item['name'] = $tool_name;
                $tool_link_params['id'] = 'is'.$tool_link_params['id'];
                $item['link'] = Display::url(
                    $tool_name.$session_img,
                    $tool_link_params['href'],
                    $tool_link_params
                );

                $items[] = $item;

                $i++;
            } // end of foreach
        }

        if (api_get_setting('homepage_view') != 'activity_big') {
            return $items;
        }

        foreach ($items as &$item) {
            $originalImage = self::getToolIcon($item);
            $item['tool']['image'] = Display::url(
                $originalImage,
                $item['url_params']['href'],
                $item['url_params']
            );
        }

        return $items;
    }

    /**
     * Find the tool icon when homepage_view is activity_big
     * @param array $item
     * @return string
     */
    private static function getToolIcon(array $item)
    {
        $image = str_replace('.gif', '.png', $item['tool']['image']);
        $toolIid = isset($item['tool']['iid']) ? $item['tool']['iid'] : null;

        if (isset($item['tool']['custom_image'])) {
            return Display::img(
                $item['tool']['custom_image'],
                $item['name'],
                array('id' => 'toolimage_'.$toolIid)
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
                array('id' => 'toolimage_'.$toolIid)
            );
        }

        return Display::return_icon(
            $image,
            $item['name'],
            array('id' => 'toolimage_'.$toolIid),
            ICON_SIZE_BIG,
            false
        );
    }

    /**
     * Shows the general data for a particular meeting
     *
     * @param id	session id
     * @return string	session data
     */
    public static function show_session_data($id_session)
    {
        $session_category_table = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);

        $sessionInfo = api_get_session_info($id_session);

        if (empty($sessionInfo)) {
            return '';
        }

        $sql = 'SELECT name FROM '.$session_category_table.'
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
                            Display::return_icon('star.png', ' ', array('align' => 'absmiddle')).'
                        </td>
                        <td>'.get_lang('Date').': '.'<b>'.$msgDate.'</b>
                        </td>
                    </tr>';

        return $output;
    }

    /**
     * Retrieves the name-field within a tool-record and translates it on necessity.
     * @param array $tool		The input record.
     * @return string			Returns the name of the corresponding tool.
     */
    public static function translate_tool_name(& $tool)
    {
        static $already_translated_icons = array(
            'file_html.gif',
            'file_html_na.gif',
            'file_html.png',
            'file_html_na.png',
            'scormbuilder.gif',
            'scormbuilder_na.gif',
            'blog.gif',
            'blog_na.gif',
            'external.gif',
            'external_na.gif'
        );

        $toolName = Security::remove_XSS(stripslashes($tool['name']));

        if (in_array($tool['image'], $already_translated_icons)) {
            return $toolName;
        }

        $toolName = api_underscore_to_camel_case($toolName);

        if (isset($GLOBALS['Tool'.$toolName])) {
            return get_lang('Tool'.$toolName);
        }

        return $toolName;
    }

    /**
     * Get published learning path id from link inside course home
     * @param 	string	Link to published lp
     * @return	int		Learning path id
     */
    public static function get_published_lp_id_from_link($published_lp_link)
    {
        $lp_id = 0;
        $param_lp_id = strstr($published_lp_link, 'lp_id=');
        if (!empty($param_lp_id)) {
            $a_param_lp_id = explode('=', $param_lp_id);
            if (isset($a_param_lp_id[1])) {
                $lp_id = intval($a_param_lp_id[1]);
            }
        }

        return $lp_id;
    }

    /**
     * Get published learning path category from link inside course home
     * @param string $link
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
     * @param bool $include_admin_tools
     * @return array
     */
    public static function get_navigation_items($include_admin_tools = false)
    {
        $navigation_items = array();
        $course_id = api_get_course_int_id();
        $courseInfo = api_get_course_info();
        $sessionId = api_get_session_id();

        if (!empty($course_id)) {

            $course_tools_table = Database::get_course_table(TABLE_TOOL_LIST);

            /* 	Link to the Course homepage */
            $navigation_items['home']['image'] = 'home.gif';
            $navigation_items['home']['link'] = $courseInfo['course_public_url'];
            $navigation_items['home']['name'] = get_lang('CourseHomepageLink');

            $sql = "SELECT * FROM $course_tools_table
                    WHERE c_id = $course_id AND visibility='1' and admin='0'
                    ORDER BY id ASC";
            $sql_result = Database::query($sql);
            while ($row = Database::fetch_array($sql_result)) {
                $navigation_items[$row['id']] = $row;
                if (stripos($row['link'], 'http://') === false && stripos($row['link'], 'https://') === false) {
                    $navigation_items[$row['id']]['link'] = api_get_path(WEB_CODE_PATH);

                    if ($row['category'] == 'plugin') {
                        $plugin = new AppPlugin();
                        $pluginInfo = $plugin->getPluginInfo($row['name']);
                        $navigation_items[$row['id']]['link'] = api_get_path(WEB_PLUGIN_PATH);
                        $navigation_items[$row['id']]['name'] = $pluginInfo['title'];
                    } else {
                        $navigation_items[$row['id']]['name'] = self::translate_tool_name($row);
                    }

                    $navigation_items[$row['id']]['link'] .= $row['link'];
                }
            }

            /* 	Admin (edit rights) only links
              - Course settings (course admin only)
              - Course rights (roles & rights overview) */
            if ($include_admin_tools) {
                $sql = "SELECT name, image FROM $course_tools_table
                        WHERE c_id = $course_id  AND link='course_info/infocours.php'";
                $sql_result = Database::query($sql);
                $course_setting_info = Database::fetch_array($sql_result);
                $course_setting_visual_name = self::translate_tool_name($course_setting_info);
                if ($sessionId == 0) {
                    // course settings item
                    $navigation_items['course_settings']['image'] = $course_setting_info['image'];
                    $navigation_items['course_settings']['link'] = api_get_path(WEB_CODE_PATH).'course_info/infocours.php';
                    $navigation_items['course_settings']['name'] = $course_setting_visual_name;
                }
            }
        }

        foreach ($navigation_items as $key => $navigation_item) {
            if (strstr($navigation_item['link'], '?')) {
                //link already contains a parameter, add course id parameter with &
                $parameter_separator = '&amp;';
            } else {
                //link doesn't contain a parameter yet, add course id parameter with ?
                $parameter_separator = '?';
            }
            //$navigation_items[$key]['link'] .= $parameter_separator.api_get_cidreq();
            $navigation_items[$key]['link'] .= $parameter_separator.'cidReq='.api_get_course_id().'&gidReq=0&id_session='.$sessionId;
        }

        return $navigation_items;
    }

    /**
     * Show a navigation menu
     */
    public static function show_navigation_menu()
    {
        $navigation_items = self::get_navigation_items(true);
        $course_id = api_get_course_id();

        $class = null;
        $idLearn = null;
        $item = null;
        $marginLeft = 160;

        $html = '<div id="toolnav">';
        $html .= '<ul id="toolnavbox">';
        $count = 0;
        foreach ($navigation_items as $key => $navigation_item) {
            //students can't see the course settings option
            $count++;
            if (!api_is_allowed_to_edit() && $key == 'course_settings') {
                continue;
            }
            $html .= '<li>';
            $url_item = parse_url($navigation_item['link']);
            $url_current = parse_url($_SERVER['REQUEST_URI']);

            if (api_get_setting('show_navigation_menu') == 'text') {
                $class = 'text';
                $marginLeft = 170;
                $item = $navigation_item['name'];
            } else if (api_get_setting('show_navigation_menu') == 'icons') {
                $class = 'icons';
                $marginLeft = 25;
                $item = Display::return_icon(
                    substr($navigation_item['image'], 0, -3)."png",
                    $navigation_item['name'],
                    array('class' => 'tool-img'),
                    ICON_SIZE_SMALL
                );
            } else {
                $class = 'icons-text';
                $item = $navigation_item['name'].Display::return_icon(substr($navigation_item['image'], 0, -3)."png", $navigation_item['name'], array('class'=>'tool-img'), ICON_SIZE_SMALL);
            }

            if (stristr($url_item['path'], $url_current['path'])) {
                if (!isset($_GET['learnpath_id']) || strpos($url_item['query'], 'learnpath_id='.intval($_GET['learnpath_id'])) === 0) {
                    $idLearn = ' id="here"';
                }
            }

            if (strpos($navigation_item['link'], 'chat') !== false &&
                api_get_course_setting('allow_open_chat_window', $course_id)
            ) {
                $html .= '<a '.$idLearn.' class="btn btn-default text-left '.$class.' " href="javascript: void(0);" onclick="javascript: window.open(\''.$navigation_item['link'].'\',\'window_chat'.api_get_course_id().'\',config=\'height=\'+600+\', width=\'+825+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="'.$navigation_item['target'].'"';
                $html .= ' title="'.$navigation_item['name'].'">';
                $html .= $item;
                $html .= '</a>';
            } else {
                $html .= '<a '.$idLearn.' class="btn btn-default text-left '.$class.'" href="'.$navigation_item['link'].'" target="_top" title="'.$navigation_item['name'].'">';
                $html .= $item;
                $html .= '</a>';
            }

            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '<script>$(function() {
                $("#toolnavbox a").stop().animate({"margin-left":"-' . $marginLeft.'px"},1000);
                $("#toolnavbox > li").hover(
                    function () {
                        $("a",$(this)).stop().animate({"margin-left":"-2px"},200);
                        $("span",$(this)).css("display","block");
                    },
                    function () {
                        $("a",$(this)).stop().animate({"margin-left":"-' . $marginLeft.'px"},200);
                        $("span",$(this)).css("display","initial");
                    }
                );
            });</script>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Show a toolbar with shortcuts to the course tool
     * @param int $orientation
     *
     * @return string
     */
    public static function show_navigation_tool_shortcuts($orientation = SHORTCUTS_HORIZONTAL)
    {
        $origin = api_get_origin();
        if ($origin === 'learnpath') {
            return '';
        }

        $navigation_items = self::get_navigation_items(false);
        $html = '';
        if (!empty($navigation_items)) {
            if ($orientation == SHORTCUTS_HORIZONTAL) {
                $style_id = "toolshortcuts_horizontal";
            } else {
                $style_id = "toolshortcuts_vertical";
            }
            $html .= '<div id="'.$style_id.'">';
            foreach ($navigation_items as $key => $navigation_item) {
                if (strpos($navigation_item['link'], 'chat') !== false &&
                    api_get_course_setting('allow_open_chat_window')
                ) {
                    $html .= '<a class="items-icon" href="javascript: void(0);" onclick="javascript: window.open(\''.$navigation_item['link'].'\',\'window_chat'.api_get_course_id().'\',config=\'height=\'+600+\', width=\'+825+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="'.$navigation_item['target'].'"';
                } else {
                    $html .= '<a class="items-icon" href="'.$navigation_item['link'].'"';
                }
                if (strpos(api_get_self(), $navigation_item['link']) !== false) {
                    $html .= ' id="here"';
                }
                $html .= ' target="_top" title="'.$navigation_item['name'].'">';

                if (isset($navigation_item['category']) && $navigation_item['category'] == 'plugin') {
                    /*$plugin_info = $app_plugin->getPluginInfo($navigation_item['name']);
                    if (isset($plugin_info) && isset($plugin_info['title'])) {
                        $tool_name = $plugin_info['title'];
                    }*/

                    if (!file_exists(api_get_path(SYS_CODE_PATH).'img/'.$navigation_item['image']) &&
                        !file_exists(api_get_path(SYS_CODE_PATH).'img/icons/'.ICON_SIZE_MEDIUM.'/'.$navigation_item['image'])
                    ) {
                        $navigation_item['image'] = 'plugins.png';
                    }
                    //$tool_link_params['href'] = api_get_path(WEB_PLUGIN_PATH).$navigation_item['link'].'?'.api_get_cidreq();
                }

                $html .= Display::return_icon(
                    substr($navigation_item['image'], 0, -3).'png',
                    $navigation_item['name'],
                    [],
                    ICON_SIZE_MEDIUM
                );
                $html .= '</a> ';
                if ($orientation == SHORTCUTS_VERTICAL) {
                    $html .= '<br />';
                }
            }
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * List course homepage tools from authoring and interaction sections
     * @param   int $courseId The course ID (guessed from context if not provided)
     * @param   int $sessionId The session ID (guessed from context if not provided)
     * @return  array List of all tools data from the c_tools table
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
            return array();
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
     * @return string
     */
    public static function getDisableIcon($icon)
    {
        $fileInfo = pathinfo($icon);

        return $fileInfo['filename'].'_na.'.$fileInfo['extension'];
    }

    /**
     * @param int $id
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
                'custom_icon' => ''
            ];

            Database::update(
                $table,
                $params,
                [' iid = ?' => [$id]]
            );
        }
    }
}
