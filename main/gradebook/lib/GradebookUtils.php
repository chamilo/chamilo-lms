<?php

/* For licensing terms, see /license.txt */

/**
 * Class GradebookUtils.
 */
class GradebookUtils
{
    /**
     * Adds a resource to the unique gradebook of a given course.
     *
     * @param   int
     * @param   string  Course code
     * @param   int     Resource type (use constants defined in linkfactory.class.php)
     * @param   int     Resource ID in the corresponding tool
     * @param   string  Resource name to show in the gradebook
     * @param   int     Resource weight to set in the gradebook
     * @param   int     Resource max
     * @param   string  Resource description
     * @param   int     Visibility (0 hidden, 1 shown)
     * @param   int     Session ID (optional or 0 if not defined)
     * @param   int
     * @param int $resource_type
     *
     * @return bool True on success, false on failure
     */
    public static function add_resource_to_course_gradebook(
        $category_id,
        $course_code,
        $resource_type,
        $resource_id,
        $resource_name = '',
        $weight = 0,
        $max = 0,
        $resource_description = '',
        $visible = 0,
        $session_id = 0,
        $link_id = null
    ) {
        $link = LinkFactory::create($resource_type);
        $link->set_user_id(api_get_user_id());
        $link->set_course_code($course_code);

        if (empty($category_id)) {
            return false;
        }
        $link->set_category_id($category_id);
        if ($link->needs_name_and_description()) {
            $link->set_name($resource_name);
        } else {
            $link->set_ref_id($resource_id);
        }
        $link->set_weight($weight);

        if ($link->needs_max()) {
            $link->set_max($max);
        }
        if ($link->needs_name_and_description()) {
            $link->set_description($resource_description);
        }

        $link->set_visible(empty($visible) ? 0 : 1);

        if (!empty($session_id)) {
            $link->set_session_id($session_id);
        }
        $link->add();

        return true;
    }

    /**
     * Update a resource weight.
     *
     * @param    int     Link/Resource ID
     * @param   string
     * @param float
     *
     * @return bool false on error, true on success
     */
    public static function updateResourceFromCourseGradebook(
        $link_id,
        $course_code,
        $weight
    ) {
        $link_id = (int) $link_id;
        if (!empty($link_id)) {
            $course_code = Database::escape_string($course_code);
            $sql = 'UPDATE '.Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK).'
                    SET weight = '."'".api_float_val($weight)."'".'
                    WHERE course_code = "'.$course_code.'" AND id = '.$link_id;
            Database::query($sql);
        }

        return true;
    }

    /**
     * Remove a resource from the unique gradebook of a given course.
     *
     * @param    int     Link/Resource ID
     *
     * @return bool false on error, true on success
     */
    public static function remove_resource_from_course_gradebook($link_id)
    {
        if (empty($link_id)) {
            return false;
        }

        // TODO find the corresponding category (the first one for this course, ordered by ID)
        $l = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        $sql = "DELETE FROM $l WHERE id = ".(int) $link_id;
        Database::query($sql);

        return true;
    }

    /**
     * Block students.
     */
    public static function block_students()
    {
        $sessionId = api_get_session_id();
        if (empty($sessionId)) {
            if (!api_is_allowed_to_edit()) {
                api_not_allowed();
            }
        } else {
            $isCoach = api_is_coach(api_get_session_id(), api_get_course_int_id());
            if (false === $isCoach) {
                if (!api_is_allowed_to_edit()) {
                    api_not_allowed();
                }
            }
        }
    }

    /**
     * Builds an img tag for a gradebook item.
     */
    public static function build_type_icon_tag($kind, $attributes = [])
    {
        return Display::return_icon(
            self::get_icon_file_name($kind),
            ' ',
            $attributes,
            ICON_SIZE_SMALL
        );
    }

    /**
     * Returns the icon filename for a gradebook item.
     *
     * @param string $type value returned by a gradebookitem's get_icon_name()
     *
     * @return string
     */
    public static function get_icon_file_name($type)
    {
        switch ($type) {
            case 'cat':
                $icon = 'gradebook.png';
                break;
            case 'evalempty':
                $icon = 'empty_evaluation.png';
                break;
            case 'evalnotempty':
                $icon = 'no_empty_evaluation.png';
                break;
            case 'exercise':
            case LINK_EXERCISE:
                $icon = 'quiz.png';
                break;
            case 'learnpath':
            case LINK_LEARNPATH:
                $icon = 'learnpath.png';
                break;
            case 'studentpublication':
            case LINK_STUDENTPUBLICATION:
                $icon = 'works.gif';
                break;
            case 'link':
                $icon = 'link.gif';
                break;
            case 'forum':
            case LINK_FORUM_THREAD:
                $icon = 'forum.gif';
                break;
            case 'attendance':
            case LINK_ATTENDANCE:
                $icon = 'attendance.gif';
                break;
            case 'survey':
            case LINK_SURVEY:
                $icon = 'survey.gif';
                break;
            case 'dropbox':
            case LINK_DROPBOX:
                $icon = 'dropbox.gif';
                break;
            case 'portfolio':
                $icon = 'wiki_task.png';
                break;
            default:
                $icon = 'link.gif';
                break;
        }

        return $icon;
    }

    /**
     * Builds the course or platform admin icons to edit a category.
     *
     * @param Category $cat       category
     * @param Category $selectcat id of selected category
     *
     * @return string
     */
    public static function build_edit_icons_cat($cat, $selectcat)
    {
        $show_message = $cat->show_message_resource_delete($cat->get_course_code());
        $grade_model_id = $selectcat->get_grade_model_id();
        $selectcat = $selectcat->get_id();
        $modify_icons = null;

        if ($show_message === false) {
            $visibility_icon = ($cat->is_visible() == 0) ? 'invisible' : 'visible';
            $visibility_command = ($cat->is_visible() == 0) ? 'set_visible' : 'set_invisible';

            $modify_icons .= '<a class="view_children" data-cat-id="'.$cat->get_id().'" href="javascript:void(0);">'.
                Display::return_icon(
                    'view_more_stats.gif',
                    get_lang('Show'),
                    '',
                    ICON_SIZE_SMALL
                ).
                '</a>';

            if (!api_is_allowed_to_edit(null, true)) {
                $modify_icons .= Display::url(
                    Display::return_icon(
                        'statistics.png',
                        get_lang('FlatView'),
                        '',
                        ICON_SIZE_SMALL
                    ),
                    'personal_stats.php?'.http_build_query([
                        'selectcat' => $cat->get_id(),
                    ]).'&'.api_get_cidreq(),
                    [
                        'class' => 'ajax',
                        'data-title' => get_lang('FlatView'),
                    ]
                );
            }

            $courseParams = api_get_cidreq_params(
                $cat->get_course_code(),
                $cat->get_session_id()
            );

            if (api_is_allowed_to_edit(null, true)) {
                // Locking button
                if (api_get_setting('gradebook_locking_enabled') === 'true') {
                    if ($cat->is_locked()) {
                        if (api_is_platform_admin()) {
                            $modify_icons .= '&nbsp;<a onclick="javascript:if (!confirm(\''.addslashes(get_lang('ConfirmToUnlockElement')).'\')) return false;" href="'.api_get_self().'?'.api_get_cidreq().'&category_id='.$cat->get_id().'&action=unlock">'.
                                Display::return_icon('lock.png', get_lang('UnLockEvaluation'), '', ICON_SIZE_SMALL).'</a>';
                        } else {
                            $modify_icons .= '&nbsp;<a href="#">'.
                                Display::return_icon('lock_na.png', get_lang('GradebookLockedAlert'), '', ICON_SIZE_SMALL).'</a>';
                        }
                        $modify_icons .= '&nbsp;<a href="gradebook_flatview.php?export_pdf=category&selectcat='.$cat->get_id().'" >'.Display::return_icon('pdf.png', get_lang('ExportToPDF'), '', ICON_SIZE_SMALL).'</a>';
                    } else {
                        $modify_icons .= '&nbsp;<a onclick="javascript:if (!confirm(\''.addslashes(get_lang('ConfirmToLockElement')).'\')) return false;" href="'.api_get_self().'?'.api_get_cidreq().'&category_id='.$cat->get_id().'&action=lock">'.
                            Display::return_icon('unlock.png', get_lang('LockEvaluation'), '', ICON_SIZE_SMALL).'</a>';
                        $modify_icons .= '&nbsp;<a href="#" >'.
                            Display::return_icon('pdf_na.png', get_lang('ExportToPDF'), '', ICON_SIZE_SMALL).'</a>';
                    }
                }

                if (empty($grade_model_id) || $grade_model_id == -1) {
                    if ($cat->is_locked() && !api_is_platform_admin()) {
                        $modify_icons .= Display::return_icon(
                            'edit_na.png',
                            get_lang('Modify'),
                            '',
                            ICON_SIZE_SMALL
                        );
                    } else {
                        $modify_icons .= '<a href="gradebook_edit_cat.php?editcat='.$cat->get_id().'&'.$courseParams.'">'.
                            Display::return_icon(
                                'edit.png',
                                get_lang('Modify'),
                                '',
                                ICON_SIZE_SMALL
                            ).'</a>';
                    }
                }

                $modify_icons .= '<a href="gradebook_edit_all.php?selectcat='.$cat->get_id().'&'.$courseParams.'">'.
                    Display::return_icon(
                        'percentage.png',
                        get_lang('EditAllWeights'),
                        '',
                        ICON_SIZE_SMALL
                    ).'</a>';

                $modify_icons .= '<a href="gradebook_flatview.php?selectcat='.$cat->get_id().'&'.$courseParams.'">'.
                    Display::return_icon(
                        'statistics.png',
                        get_lang('FlatView'),
                        '',
                        ICON_SIZE_SMALL
                    ).'</a>';
                $modify_icons .= '&nbsp;<a href="'.api_get_self().'?visiblecat='.$cat->get_id().'&'.$visibility_command.'=&selectcat='.$selectcat.'&'.$courseParams.'">'.
                    Display::return_icon(
                        $visibility_icon.'.png',
                        get_lang('Visible'),
                        '',
                        ICON_SIZE_SMALL
                    ).'</a>';

                if ($cat->is_locked() && !api_is_platform_admin()) {
                    $modify_icons .= Display::return_icon(
                        'delete_na.png',
                        get_lang('DeleteAll'),
                        '',
                        ICON_SIZE_SMALL
                    );
                } else {
                    $modify_icons .= '&nbsp;<a href="'.api_get_self().'?deletecat='.$cat->get_id().'&selectcat='.$selectcat.'&'.$courseParams.'" onclick="return confirmation();">'.
                        Display::return_icon(
                            'delete.png',
                            get_lang('DeleteAll'),
                            '',
                            ICON_SIZE_SMALL
                        ).
                        '</a>';
                }
            }

            return $modify_icons;
        }
    }

    /**
     * Builds the course or platform admin icons to edit an evaluation.
     *
     * @param Evaluation $eval      evaluation object
     * @param int        $selectcat id of selected category
     *
     * @return string
     */
    public static function build_edit_icons_eval($eval, $selectcat)
    {
        $is_locked = $eval->is_locked();
        $eval->get_course_code();
        $cat = new Category();
        $message_eval = $cat->show_message_resource_delete($eval->get_course_code());
        $courseParams = api_get_cidreq_params($eval->get_course_code(), $eval->getSessionId());

        if ($message_eval === false && api_is_allowed_to_edit(null, true)) {
            $visibility_icon = $eval->is_visible() == 0 ? 'invisible' : 'visible';
            $visibility_command = $eval->is_visible() == 0 ? 'set_visible' : 'set_invisible';
            if ($is_locked && !api_is_platform_admin()) {
                $modify_icons = Display::return_icon(
                    'edit_na.png',
                    get_lang('Modify'),
                    '',
                    ICON_SIZE_SMALL
                );
            } else {
                $modify_icons = '<a href="gradebook_edit_eval.php?editeval='.$eval->get_id().'&'.$courseParams.'">'.
                    Display::return_icon(
                        'edit.png',
                        get_lang('Modify'),
                        '',
                        ICON_SIZE_SMALL
                    ).
                    '</a>';
            }

            $modify_icons .= '&nbsp;<a href="'.api_get_self().'?visibleeval='.$eval->get_id().'&'.$visibility_command.'=&selectcat='.$selectcat.'&'.$courseParams.' ">'.
                Display::return_icon(
                    $visibility_icon.'.png',
                    get_lang('Visible'),
                    '',
                    ICON_SIZE_SMALL
                ).
                '</a>';

            if (api_is_allowed_to_edit(null, true)) {
                $modify_icons .= '&nbsp;<a href="gradebook_showlog_eval.php?visiblelog='.$eval->get_id().'&selectcat='.$selectcat.' &'.$courseParams.'">'.
                    Display::return_icon(
                        'history.png',
                        get_lang('GradebookQualifyLog'),
                        '',
                        ICON_SIZE_SMALL
                    ).
                    '</a>';

                $allowStats = api_get_configuration_value('allow_gradebook_stats');
                if ($allowStats) {
                    $modify_icons .= Display::url(
                        Display::return_icon('reload.png', get_lang('GenerateStats')),
                        api_get_self().'?itemId='.$eval->get_id().'&action=generate_eval_stats&selectcat='.$selectcat.'&'.$courseParams
                    );
                }
            }

            if ($is_locked && !api_is_platform_admin()) {
                $modify_icons .= '&nbsp;'.
                    Display::return_icon(
                        'delete_na.png',
                        get_lang('Delete'),
                        '',
                        ICON_SIZE_SMALL
                    );
            } else {
                $modify_icons .= '&nbsp;<a href="'.api_get_self().'?deleteeval='.$eval->get_id().'&selectcat='.$selectcat.' &'.$courseParams.'" onclick="return confirmation();">'.
                    Display::return_icon(
                        'delete.png',
                        get_lang('Delete'),
                        '',
                        ICON_SIZE_SMALL
                    ).
                    '</a>';
            }

            return $modify_icons;
        }
    }

    /**
     * Builds the course or platform admin icons to edit a link.
     *
     * @param AbstractLink $link
     * @param int          $selectcat id of selected category
     *
     * @return string
     */
    public static function build_edit_icons_link($link, $selectcat)
    {
        $cat = new Category();
        $message_link = $cat->show_message_resource_delete($link->get_course_code());
        $is_locked = $link->is_locked();
        $modify_icons = null;

        if (!api_is_allowed_to_edit(null, true)) {
            return null;
        }

        $courseParams = api_get_cidreq_params(
            $link->get_course_code(),
            $link->get_session_id()
        );

        if ($message_link === false) {
            $visibility_icon = $link->is_visible() == 0 ? 'invisible' : 'visible';
            $visibility_command = $link->is_visible() == 0 ? 'set_visible' : 'set_invisible';

            if ($is_locked && !api_is_platform_admin()) {
                $modify_icons = Display::return_icon(
                    'edit_na.png',
                    get_lang('Modify'),
                    '',
                    ICON_SIZE_SMALL
                );
            } else {
                $modify_icons = '<a href="gradebook_edit_link.php?editlink='.$link->get_id().'&'.$courseParams.'">'.
                    Display::return_icon(
                        'edit.png',
                        get_lang('Modify'),
                        '',
                        ICON_SIZE_SMALL
                    ).
                    '</a>';
            }
            $modify_icons .= '&nbsp;<a href="'.api_get_self().'?visiblelink='.$link->get_id().'&'.$visibility_command.'=&selectcat='.$selectcat.'&'.$courseParams.' ">'.
                Display::return_icon(
                    $visibility_icon.'.png',
                    get_lang('Visible'),
                    '',
                    ICON_SIZE_SMALL
                ).
                '</a>';

            $modify_icons .= '&nbsp;<a href="gradebook_showlog_link.php?visiblelink='.$link->get_id().'&selectcat='.$selectcat.'&'.$courseParams.'">'.
                Display::return_icon(
                    'history.png',
                    get_lang('GradebookQualifyLog'),
                    '',
                    ICON_SIZE_SMALL
                ).
                '</a>';

            $allowStats = api_get_configuration_value('allow_gradebook_stats');
            if ($allowStats && $link->get_type() == LINK_EXERCISE) {
                $modify_icons .= Display::url(
                    Display::return_icon('reload.png', get_lang('GenerateStats')),
                    api_get_self().'?itemId='.$link->get_id().'&action=generate_link_stats&selectcat='.$selectcat.'&'.$courseParams
                );
            }

            //If a work is added in a gradebook you can only delete the link in the work tool
            if ($is_locked && !api_is_platform_admin()) {
                $modify_icons .= '&nbsp;'.
                    Display::return_icon(
                        'delete_na.png',
                        get_lang('Delete'),
                        '',
                        ICON_SIZE_SMALL
                    );
            } else {
                $modify_icons .= '&nbsp;
                <a
                    href="'.api_get_self().'?deletelink='.$link->get_id().'&selectcat='.$selectcat.' &'.$courseParams.'"
                    onclick="return confirmation();">'.
                    Display::return_icon(
                        'delete.png',
                        get_lang('Delete'),
                        '',
                        ICON_SIZE_SMALL
                    ).
                    '</a>';
            }

            return $modify_icons;
        }
    }

    /**
     * Checks if a resource is in the unique gradebook of a given course.
     *
     * @param string $course_code   Course code
     * @param int    $resource_type Resource type (use constants defined in linkfactory.class.php)
     * @param int    $resource_id   Resource ID in the corresponding tool
     * @param int    $session_id    Session ID (optional -  0 if not defined)
     *
     * @return array false on error or array of resource
     */
    public static function isResourceInCourseGradebook(
        $course_code,
        $resource_type,
        $resource_id,
        $session_id = 0
    ) {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        $course_code = Database::escape_string($course_code);
        $sql = "SELECT * FROM $table l
                WHERE
                    course_code = '$course_code' AND
                    type = ".(int) $resource_type." AND
                    ref_id = ".(int) $resource_id;
        $res = Database::query($sql);

        if (Database::num_rows($res) < 1) {
            return false;
        }

        return Database::fetch_array($res, 'ASSOC');
    }

    /**
     * Return the course id.
     *
     * @param    int
     *
     * @return string
     */
    public static function get_course_id_by_link_id($id_link)
    {
        $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_grade_links = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        $id_link = (int) $id_link;

        $sql = 'SELECT c.id FROM '.$course_table.' c
                INNER JOIN '.$tbl_grade_links.' l
                ON c.code = l.course_code
                WHERE l.id='.$id_link.' OR l.category_id='.$id_link;
        $res = Database::query($sql);
        $array = Database::fetch_array($res, 'ASSOC');

        return $array['id'];
    }

    /**
     * @param $type
     *
     * @return string
     */
    public static function get_table_type_course($type)
    {
        global $table_evaluated;

        return Database::get_course_table($table_evaluated[$type][0]);
    }

    /**
     * @param Category $cat
     * @param $users
     * @param $alleval
     * @param $alllinks
     * @param $params
     * @param null $mainCourseCategory
     * @param bool $onlyScore
     *
     * @return array
     */
    public static function get_printable_data(
        $cat,
        $users,
        $alleval,
        $alllinks,
        $params,
        $mainCourseCategory = null,
        $onlyScore = false
    ) {
        $datagen = new FlatViewDataGenerator(
            $users,
            $alleval,
            $alllinks,
            $params,
            $mainCourseCategory
        );

        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        // step 2: generate rows: students
        $datagen->category = $cat;
        $totalItems = $datagen->get_total_items_count();
        $count = (($offset + 10) > $totalItems) ? ($totalItems - $offset) : GRADEBOOK_ITEM_LIMIT;
        $headers = $datagen->get_header_names($offset, $count, true);
        $list = $datagen->get_data(
            FlatViewDataGenerator::FVDG_SORT_LASTNAME,
            0,
            null,
            $offset,
            $count,
            $onlyScore,
            true,
            $onlyScore
        );

        $result = [];
        foreach ($list as $data) {
            $result[] = array_slice($data, 1);
        }

        return [$headers, $result];
    }

    /**
     * XML-parser: handle character data.
     */
    public static function character_data($parser, $data)
    {
        global $current_value;
        $current_value = $data;
    }

    public static function overwritescore($resid, $importscore, $eval_max)
    {
        $result = Result::load($resid);
        if ($importscore > $eval_max) {
            header('Location: gradebook_view_result.php?selecteval='.Security::remove_XSS($_GET['selecteval']).'&overwritemax=');
            exit;
        }
        $result[0]->set_score($importscore);
        $result[0]->save();
        unset($result);
    }

    /**
     * register user info about certificate.
     *
     * @param int    $cat_id            The category id
     * @param int    $user_id           The user id
     * @param float  $score_certificate The score obtained for certified
     * @param string $date_certificate  The date when you obtained the certificate
     */
    public static function registerUserInfoAboutCertificate(
        $cat_id,
        $user_id,
        $score_certificate,
        $date_certificate
    ) {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $cat_id = (int) $cat_id;
        $user_id = (int) $user_id;

        $sql = "SELECT COUNT(id) as count
                FROM $table gc
                WHERE gc.cat_id = $cat_id AND user_id = $user_id ";
        $rs_exist = Database::query($sql);
        $row = Database::fetch_array($rs_exist);
        if (0 == $row['count']) {
            $params = [
                'cat_id' => $cat_id,
                'user_id' => $user_id,
                'score_certificate' => $score_certificate,
                'created_at' => $date_certificate,
            ];
            Database::insert($table, $params);
        }
    }

    /**
     * Get date of user certificate.
     *
     * @param int $cat_id  The category id
     * @param int $user_id The user id
     *
     * @return Datetime The date when you obtained the certificate
     */
    public static function get_certificate_by_user_id($cat_id, $user_id)
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $cat_id = (int) $cat_id;
        $user_id = (int) $user_id;

        $sql = "SELECT * FROM $table
                WHERE cat_id = $cat_id AND user_id = $user_id ";

        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');

        return $row;
    }

    /**
     * Get list of users certificates.
     *
     * @param int   $cat_id   The category id
     * @param array $userList Only users in this list
     *
     * @return array
     */
    public static function get_list_users_certificates($cat_id = null, $userList = [])
    {
        $table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = 'SELECT DISTINCT u.user_id, u.lastname, u.firstname, u.username, gc.created_at
                FROM '.$table_user.' u
                INNER JOIN '.$table_certificate.' gc
                ON u.user_id=gc.user_id ';
        if (!is_null($cat_id) && $cat_id > 0) {
            $sql .= ' WHERE cat_id='.intval($cat_id);
        }
        if (!empty($userList)) {
            $userList = array_map('intval', $userList);
            $userListCondition = implode("','", $userList);
            $sql .= " AND u.user_id IN ('$userListCondition')";
        }
        $sql .= ' ORDER BY '.(api_sort_by_first_name() ? 'u.firstname' : 'u.lastname');
        $rs = Database::query($sql);

        $list_users = [];
        while ($row = Database::fetch_array($rs)) {
            $list_users[] = $row;
        }

        return $list_users;
    }

    /**
     * Gets the certificate list by user id.
     *
     * @param int $user_id The user id
     * @param int $cat_id  The category id
     *
     * @return array
     */
    public static function get_list_gradebook_certificates_by_user_id(
        $user_id,
        $cat_id = null
    ) {
        $user_id = (int) $user_id;
        $table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $sql = 'SELECT
                    gc.score_certificate,
                    gc.created_at,
                    gc.path_certificate,
                    gc.cat_id,
                    gc.user_id,
                    gc.id
                FROM  '.$table_certificate.' gc
                WHERE gc.user_id = "'.$user_id.'" ';
        if (!is_null($cat_id) && $cat_id > 0) {
            $sql .= ' AND cat_id='.intval($cat_id);
        }

        $rs = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($rs)) {
            $list[] = $row;
        }

        return $list;
    }

    /**
     * @param int    $user_id
     * @param string $course_code
     * @param int    $sessionId
     * @param bool   $is_preview
     * @param bool   $hide_print_button
     *
     * @return array
     */
    public static function get_user_certificate_content(
        $user_id,
        $course_code,
        $sessionId,
        $is_preview = false,
        $hide_print_button = false
    ) {
        // Generate document HTML
        $content_html = DocumentManager::replace_user_info_into_html(
            $user_id,
            $course_code,
            $sessionId,
            $is_preview
        );

        $new_content_html = isset($content_html['content']) ? $content_html['content'] : null;
        $variables = isset($content_html['variables']) ? $content_html['variables'] : null;
        $path_image = api_get_path(WEB_COURSE_PATH).api_get_course_path($course_code).'/document/images/gallery';
        $new_content_html = str_replace('../images/gallery', $path_image, $new_content_html);

        $path_image_in_default_course = api_get_path(WEB_CODE_PATH).'default_course_document';
        $new_content_html = str_replace('/main/default_course_document', $path_image_in_default_course, $new_content_html);
        $new_content_html = str_replace(SYS_CODE_PATH.'img/', api_get_path(WEB_IMG_PATH), $new_content_html);

        //add print header
        if (!$hide_print_button) {
            $print = '<style>#print_div {
                padding:4px;border: 0 none;position: absolute;top: 0px;right: 0px;
            }
            @media print {
                #print_div  {
                    display: none !important;
                }
            }
            </style>';

            $print .= Display::div(
                Display::url(
                    Display::return_icon('printmgr.gif', get_lang('Print')),
                    'javascript:void()',
                    ['onclick' => 'window.print();']
                ),
                ['id' => 'print_div']
            );
            $print .= '</html>';
            $new_content_html = str_replace('</html>', $print, $new_content_html);
        }

        return [
            'content' => $new_content_html,
            'variables' => $variables,
        ];
    }

    /**
     * @param null $course_code
     * @param int  $gradebook_model_id
     *
     * @return mixed
     */
    public static function create_default_course_gradebook(
        $course_code = null,
        $gradebook_model_id = 0
    ) {
        if (api_is_allowed_to_edit(true, true)) {
            if (!isset($course_code) || empty($course_code)) {
                $course_code = api_get_course_id();
            }
            $session_id = api_get_session_id();

            $t = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
            $sql = "SELECT * FROM $t
                    WHERE course_code = '".Database::escape_string($course_code)."' ";
            if (!empty($session_id)) {
                $sql .= " AND session_id = ".$session_id;
            } else {
                $sql .= ' AND (session_id IS NULL OR session_id = 0) ';
            }
            $sql .= ' ORDER BY id ';
            $res = Database::query($sql);
            if (Database::num_rows($res) < 1) {
                //there is no unique category for this course+session combination,
                $cat = new Category();
                if (!empty($session_id)) {
                    $my_session_id = api_get_session_id();
                    $s_name = api_get_session_name($my_session_id);
                    $cat->set_name($course_code.' - '.get_lang('Session').' '.$s_name);
                    $cat->set_session_id($session_id);
                } else {
                    $cat->set_name($course_code);
                }
                $cat->set_course_code($course_code);
                $cat->set_description(null);
                $cat->set_user_id(api_get_user_id());
                $cat->set_parent_id(0);
                $default_weight_setting = api_get_setting('gradebook_default_weight');
                $default_weight = isset($default_weight_setting) && !empty($default_weight_setting) ? $default_weight_setting : 100;
                $cat->set_weight($default_weight);
                $cat->set_grade_model_id($gradebook_model_id);
                $cat->set_certificate_min_score(75);
                $cat->set_visible(0);
                $cat->add();
                $category_id = $cat->get_id();
                unset($cat);
            } else {
                $row = Database::fetch_array($res);
                $category_id = $row['id'];
            }

            return $category_id;
        }

        return false;
    }

    /**
     * @param FormValidator $form
     */
    public static function load_gradebook_select_in_tool($form)
    {
        $course_code = api_get_course_id();
        $session_id = api_get_session_id();

        self::create_default_course_gradebook();

        // Cat list
        $all_categories = Category::load(
            null,
            null,
            $course_code,
            null,
            null,
            $session_id,
            false
        );
        $select_gradebook = $form->addElement(
            'select',
            'category_id',
            get_lang('SelectGradebook')
        );

        if (!empty($all_categories)) {
            foreach ($all_categories as $my_cat) {
                if ($my_cat->get_course_code() == api_get_course_id()) {
                    $grade_model_id = $my_cat->get_grade_model_id();
                    if (empty($grade_model_id)) {
                        if ($my_cat->get_parent_id() == 0) {
                            $select_gradebook->addOption(get_lang('Default'), $my_cat->get_id());
                            $cats_added[] = $my_cat->get_id();
                        } else {
                            $select_gradebook->addOption(Security::remove_XSS($my_cat->get_name()), $my_cat->get_id());
                            $cats_added[] = $my_cat->get_id();
                        }
                    } else {
                        $select_gradebook->addOption(get_lang('Select'), 0);
                    }
                }
            }
        }
    }

    /**
     * @param FlatViewTable $flatviewtable
     * @param Category      $cat
     * @param $users
     * @param $alleval
     * @param $alllinks
     * @param array $params
     * @param null  $mainCourseCategory
     */
    public static function export_pdf_flatview(
        $flatviewtable,
        $cat,
        $users,
        $alleval,
        $alllinks,
        $params = [],
        $mainCourseCategory = null
    ) {
        // Getting data
        $printable_data = self::get_printable_data(
            $cat[0],
            $users,
            $alleval,
            $alllinks,
            $params,
            $mainCourseCategory
        );

        // HTML report creation first
        $course_code = trim($cat[0]->get_course_code());

        $displayscore = ScoreDisplay::instance();
        $customDisplays = $displayscore->get_custom_score_display_settings();

        $total = [];
        if (is_array($customDisplays) && count(($customDisplays))) {
            foreach ($customDisplays as $custom) {
                $total[$custom['display']] = 0;
            }
            $user_results = $flatviewtable->datagen->get_data_to_graph2(false);
            foreach ($user_results as $user_result) {
                $item = $user_result[count($user_result) - 1];
                $customTag = isset($item[1]) ? strip_tags($item[1]) : '';
                $total[$customTag]++;
            }
        }

        $parent_id = $cat[0]->get_parent_id();
        if (isset($cat[0]) && isset($parent_id)) {
            if ($parent_id == 0) {
                $grade_model_id = $cat[0]->get_grade_model_id();
            } else {
                $parent_cat = Category::load($parent_id);
                $grade_model_id = $parent_cat[0]->get_grade_model_id();
            }
        }

        $use_grade_model = true;
        if (empty($grade_model_id) || $grade_model_id == -1) {
            $use_grade_model = false;
        }

        if ($use_grade_model) {
            if ($parent_id == 0) {
                $title = api_strtoupper(get_lang('Average')).
                    '<br />'.get_lang('Detailed');
            } else {
                $title = api_strtoupper(get_lang('Average')).
                    '<br />'.$cat[0]->get_description().' - ('.$cat[0]->get_name().')';
            }
        } else {
            if ($parent_id == 0) {
                $title = api_strtoupper(get_lang('Average')).'<br />'.get_lang('Detailed');
            } else {
                $title = api_strtoupper(get_lang('Average'));
            }
        }

        $columns = count($printable_data[0]);
        $has_data = is_array($printable_data[1]) && count($printable_data[1]) > 0;

        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $row = 0;
        $column = 0;
        $table->setHeaderContents($row, $column, get_lang('NumberAbbreviation'));
        $column++;
        foreach ($printable_data[0] as $printable_data_cell) {
            if (!is_array($printable_data_cell)) {
                $printable_data_cell = strip_tags($printable_data_cell);
            }
            $table->setHeaderContents($row, $column, $printable_data_cell);
            $column++;
        }
        $row++;

        if ($has_data) {
            $counter = 1;
            foreach ($printable_data[1] as &$printable_data_row) {
                $column = 0;
                $table->setCellContents($row, $column, $counter);
                $table->updateCellAttributes($row, $column, 'align="center"');
                $column++;
                $counter++;

                foreach ($printable_data_row as $key => &$printable_data_cell) {
                    $attributes = [];
                    $attributes['align'] = 'center';
                    $attributes['style'] = null;

                    if ($key === 'name') {
                        $attributes['align'] = 'left';
                    }
                    if ($key === 'total') {
                        $attributes['style'] = 'font-weight:bold';
                    }
                    $table->setCellContents($row, $column, $printable_data_cell);
                    $table->updateCellAttributes($row, $column, $attributes);
                    $column++;
                }
                $table->updateRowAttributes($row, $row % 2 ? 'class="row_even"' : 'class="row_odd"', true);
                $row++;
            }
        } else {
            $column = 0;
            $table->setCellContents($row, $column, get_lang('NoResults'));
            $table->updateCellAttributes($row, $column, 'colspan="'.$columns.'" align="center" class="row_odd"');
        }

        $pdfParams = [
            'filename' => get_lang('FlatView').'_'.api_get_local_time(),
            'pdf_title' => $title,
            'course_code' => $course_code,
            'add_signatures' => ['Drh', 'Teacher', 'Date'],
        ];

        $page_format = $params['orientation'] === 'landscape' ? 'A4-L' : 'A4';
        ob_start();
        $pdf = new PDF($page_format, $page_format, $pdfParams);
        $pdf->html_to_pdf_with_template($flatviewtable->return_table(), false, false, true);
        $content = ob_get_contents();
        ob_end_clean();
        echo $content;
        exit;
    }

    /**
     * @param string[] $list_values
     *
     * @return string
     */
    public static function score_badges($list_values)
    {
        $counter = 1;
        $badges = [];
        foreach ($list_values as $value) {
            $class = 'warning';
            if ($counter == 1) {
                $class = 'success';
            }
            $counter++;
            $badges[] = Display::badge($value, $class);
        }

        return Display::badge_group($badges);
    }

    /**
     * returns users within a course given by param.
     *
     * @param string $courseCode
     *
     * @deprecated use CourseManager
     *
     * @return array
     */
    public static function get_users_in_course($courseCode)
    {
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname ASC' : ' ORDER BY lastname, firstname ASC';

        $current_session = api_get_session_id();
        $courseCode = Database::escape_string($courseCode);
        $courseInfo = api_get_course_info($courseCode);
        $courseId = $courseInfo['real_id'];

        if (!empty($current_session)) {
            $sql = "SELECT user.user_id, user.username, lastname, firstname, official_code
                    FROM $tbl_session_course_user as scru
                    INNER JOIN $tbl_user as user
                    ON (scru.user_id = user.user_id)
                    WHERE
                        scru.status = 0 AND
                        scru.c_id='$courseId' AND
                        session_id ='$current_session'
                    $order_clause
                    ";
        } else {
            $sql = 'SELECT user.user_id, user.username, lastname, firstname, official_code
                    FROM '.$tbl_course_user.' as course_rel_user
                    INNER JOIN '.$tbl_user.' as user
                    ON (course_rel_user.user_id = user.id)
                    WHERE
                        course_rel_user.status = '.STUDENT.' AND
                        course_rel_user.c_id = "'.$courseId.'" '.
                    $order_clause;
        }

        $result = Database::query($sql);

        return self::get_user_array_from_sql_result($result);
    }

    /**
     * @param Doctrine\DBAL\Driver\Statement|null $result
     *
     * @return array
     */
    public static function get_user_array_from_sql_result($result)
    {
        $a_students = [];
        while ($user = Database::fetch_array($result)) {
            if (!array_key_exists($user['user_id'], $a_students)) {
                $a_current_student = [];
                $a_current_student[] = $user['user_id'];
                $a_current_student[] = $user['username'];
                $a_current_student[] = $user['lastname'];
                $a_current_student[] = $user['firstname'];
                $a_current_student[] = $user['official_code'];
                $a_students['STUD'.$user['user_id']] = $a_current_student;
            }
        }

        return $a_students;
    }

    /**
     * @param array $evals
     * @param array $links
     *
     * @return array
     */
    public static function get_all_users($evals = [], $links = [])
    {
        $coursecodes = [];
        // By default add all user in course
        $coursecodes[api_get_course_id()] = '1';
        $users = self::get_users_in_course(api_get_course_id());

        foreach ($evals as $eval) {
            $coursecode = $eval->get_course_code();
            // evaluation in course
            if (isset($coursecode) && !empty($coursecode)) {
                if (!array_key_exists($coursecode, $coursecodes)) {
                    $coursecodes[$coursecode] = '1';
                    $users = array_merge($users, self::get_users_in_course($coursecode));
                }
            } else {
                // course independent evaluation
                $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
                $tbl_res = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);

                $sql = 'SELECT user.user_id, lastname, firstname, user.official_code
                        FROM '.$tbl_res.' as res, '.$tbl_user.' as user
                        WHERE
                            res.evaluation_id = '.intval($eval->get_id()).' AND
                            res.user_id = user.user_id
                        ';
                $sql .= ' ORDER BY lastname, firstname';
                if (api_is_western_name_order()) {
                    $sql .= ' ORDER BY firstname, lastname';
                }

                $result = Database::query($sql);
                $users = array_merge(
                    $users,
                    self::get_user_array_from_sql_result($result)
                );
            }
        }

        foreach ($links as $link) {
            // links are always in a course
            $coursecode = $link->get_course_code();
            if (!array_key_exists($coursecode, $coursecodes)) {
                $coursecodes[$coursecode] = '1';
                $users = array_merge(
                    $users,
                    self::get_users_in_course($coursecode)
                );
            }
        }

        return $users;
    }

    /**
     * Search students matching a given last name and/or first name.
     *
     * @author Bert Steppé
     */
    public static function find_students($mask = '')
    {
        // students shouldn't be here // don't search if mask empty
        if (!api_is_allowed_to_edit() || empty($mask)) {
            return null;
        }
        $mask = Database::escape_string($mask);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_cru = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $sql = 'SELECT DISTINCT user.user_id, user.lastname, user.firstname, user.email, user.official_code
                FROM '.$tbl_user.' user';
        if (!api_is_platform_admin()) {
            $sql .= ', '.$tbl_cru.' cru';
        }

        $sql .= ' WHERE user.status = '.STUDENT;
        $sql .= ' AND (user.lastname LIKE '."'%".$mask."%'";
        $sql .= ' OR user.firstname LIKE '."'%".$mask."%')";

        if (!api_is_platform_admin()) {
            $sql .= ' AND user.user_id = cru.user_id AND
                      cru.relation_type <> '.COURSE_RELATION_TYPE_RRHH.' AND
                      cru.c_id in (
                            SELECT c_id FROM '.$tbl_cru.'
                            WHERE
                                user_id = '.api_get_user_id().' AND
                                status = '.COURSEMANAGER.'
                        )
                    ';
        }

        $sql .= ' ORDER BY lastname, firstname';
        if (api_is_western_name_order()) {
            $sql .= ' ORDER BY firstname, lastname';
        }

        $result = Database::query($sql);

        return Database::store_result($result);
    }

    /**
     * @param int   $linkId
     * @param float $weight
     */
    public static function updateLinkWeight($linkId, $name, $weight)
    {
        $linkId = (int) $linkId;
        $weight = api_float_val($weight);
        $course_id = api_get_course_int_id();

        AbstractLink::add_link_log($linkId, $name);
        $table_link = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

        $em = Database::getManager();
        $tbl_forum_thread = Database::get_course_table(TABLE_FORUM_THREAD);
        $tbl_attendance = Database::get_course_table(TABLE_ATTENDANCE);

        $sql = 'UPDATE '.$table_link.'
                SET weight = '."'".Database::escape_string($weight)."'".'
                WHERE id = '.$linkId;

        Database::query($sql);

        // Update weight for attendance
        $sql = 'SELECT ref_id FROM '.$table_link.'
                WHERE id = '.$linkId.' AND type='.LINK_ATTENDANCE;

        $rs_attendance = Database::query($sql);
        if (Database::num_rows($rs_attendance) > 0) {
            $row_attendance = Database::fetch_array($rs_attendance);
            $sql = 'UPDATE '.$tbl_attendance.' SET
                    attendance_weight ='.api_float_val($weight).'
                    WHERE c_id = '.$course_id.' AND  id = '.intval($row_attendance['ref_id']);
            Database::query($sql);
        }
        // Update weight into forum thread
        $sql = 'UPDATE '.$tbl_forum_thread.' SET
                thread_weight = '.api_float_val($weight).'
                WHERE
                    c_id = '.$course_id.' AND
                    thread_id = (
                        SELECT ref_id FROM '.$table_link.'
                        WHERE id='.$linkId.' AND type='.LINK_FORUM_THREAD.'
                    )
                ';
        Database::query($sql);
        //Update weight into student publication(work)
        $em
            ->createQuery('
                UPDATE ChamiloCourseBundle:CStudentPublication w
                SET w.weight = :final_weight
                WHERE w.cId = :course
                    AND w.id = (
                        SELECT l.refId FROM ChamiloCoreBundle:GradebookLink l
                        WHERE l.id = :link AND l.type = :type
                    )
            ')
            ->execute([
                'final_weight' => $weight,
                'course' => $course_id,
                'link' => $linkId,
                'type' => LINK_STUDENTPUBLICATION,
            ]);
    }

    /**
     * @param int   $id
     * @param float $weight
     */
    public static function updateEvaluationWeight($id, $weight)
    {
        $table_evaluation = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
        $id = (int) $id;
        $evaluation = new Evaluation();
        $evaluation->addEvaluationLog($id);
        $sql = 'UPDATE '.$table_evaluation.'
               SET weight = '."'".Database::escape_string($weight)."'".'
               WHERE id = '.$id;
        Database::query($sql);
    }

    /**
     * Get the achieved certificates for a user in courses.
     *
     * @param int  $userId                       The user id
     * @param bool $includeNonPublicCertificates Whether include the non-plublic certificates
     *
     * @return array
     */
    public static function getUserCertificatesInCourses(
        $userId,
        $includeNonPublicCertificates = true
    ) {
        $userId = (int) $userId;
        $courseList = [];
        $courses = CourseManager::get_courses_list_by_user_id($userId);

        foreach ($courses as $course) {
            if (!$includeNonPublicCertificates) {
                $allowPublicCertificates = api_get_course_setting('allow_public_certificates', $course);

                if (empty($allowPublicCertificates)) {
                    continue;
                }
            }

            $category = Category::load(null, null, $course['code']);

            if (empty($category)) {
                continue;
            }

            if (!isset($category[0])) {
                continue;
            }
            /** @var Category $category */
            $category = $category[0];

            if (empty($category->getGenerateCertificates())) {
                continue;
            }

            $categoryId = $category->get_id();
            $certificateInfo = self::get_certificate_by_user_id($categoryId, $userId);

            if (empty($certificateInfo)) {
                continue;
            }

            $courseInfo = api_get_course_info_by_id($course['real_id']);
            if (empty($courseInfo)) {
                continue;
            }

            $courseList[] = [
                'course' => $courseInfo['title'],
                'score' => $certificateInfo['score_certificate'],
                'date' => api_format_date($certificateInfo['created_at'], DATE_FORMAT_SHORT),
                'link' => api_get_path(WEB_PATH)."certificates/index.php?id={$certificateInfo['id']}",
                'pdf' => api_get_path(WEB_PATH)."certificates/index.php?id={$certificateInfo['id']}&user_id={$userId}&action=export",
            ];
        }

        return $courseList;
    }

    /**
     * Get the achieved certificates for a user in course sessions.
     *
     * @param int  $userId                       The user id
     * @param bool $includeNonPublicCertificates Whether include the non-public certificates
     *
     * @return array
     */
    public static function getUserCertificatesInSessions($userId, $includeNonPublicCertificates = true)
    {
        $userId = (int) $userId;
        $sessionList = [];
        $sessions = SessionManager::get_sessions_by_user($userId, true, true);

        foreach ($sessions as $session) {
            if (empty($session['courses'])) {
                continue;
            }
            $sessionCourses = SessionManager::get_course_list_by_session_id($session['session_id']);

            if (empty($sessionCourses)) {
                continue;
            }

            foreach ($sessionCourses as $course) {
                if (!$includeNonPublicCertificates) {
                    $allowPublicCertificates = api_get_course_setting('allow_public_certificates');

                    if (empty($allowPublicCertificates)) {
                        continue;
                    }
                }

                $category = Category::load(
                    null,
                    null,
                    $course['code'],
                    null,
                    null,
                    $session['session_id']
                );

                if (empty($category)) {
                    continue;
                }

                if (!isset($category[0])) {
                    continue;
                }

                /** @var Category $category */
                $category = $category[0];

                // Don't allow generate of certifications
                if (empty($category->getGenerateCertificates())) {
                    continue;
                }

                $categoryId = $category->get_id();
                $certificateInfo = self::get_certificate_by_user_id(
                    $categoryId,
                    $userId
                );

                if (empty($certificateInfo)) {
                    continue;
                }

                $sessionList[] = [
                    'session' => $session['session_name'],
                    'course' => $course['title'],
                    'score' => $certificateInfo['score_certificate'],
                    'date' => api_format_date($certificateInfo['created_at'], DATE_FORMAT_SHORT),
                    'link' => api_get_path(WEB_PATH)."certificates/index.php?id={$certificateInfo['id']}",
                    'pdf' => api_get_path(WEB_PATH)."certificates/index.php?id={$certificateInfo['id']}&user_id={$userId}&action=export",
                ];
            }
        }

        return $sessionList;
    }

    /**
     * @param array $courseInfo
     * @param int   $userId
     * @param array $cats
     * @param bool  $saveToFile
     * @param bool  $saveToHtmlFile
     * @param array $studentList
     * @param PDF   $pdf
     *
     * @return string
     */
    public static function generateTable(
        $courseInfo,
        $userId,
        $cats,
        $saveToFile = false,
        $saveToHtmlFile = false,
        $studentList = [],
        $pdf = null
    ) {
        $userInfo = api_get_user_info($userId);
        $model = ExerciseLib::getCourseScoreModel();
        /** @var Category $cat */
        $cat = $cats[0];
        $allcat = $cats[0]->get_subcategories(
            $userId,
            api_get_course_id(),
            api_get_session_id()
        );
        $alleval = $cats[0]->get_evaluations($userId);
        $alllink = $cats[0]->get_links($userId);

        $loadStats = GradebookTable::getExtraStatsColumnsToDisplay();

        $gradebooktable = new GradebookTable(
            $cat,
            $allcat,
            $alleval,
            $alllink,
            null,
            true,
            false,
            $userId,
            $studentList,
            $loadStats
        );
        $gradebooktable->hideNavigation = true;
        $gradebooktable->userId = $userId;

        $table = $gradebooktable->return_table();

        $graph = '';
        if (empty($model)) {
            $graph = $gradebooktable->getGraph();
        }
        $params = [
            'pdf_title' => sprintf(get_lang('GradeFromX'), $courseInfo['name']),
            'session_info' => '',
            'course_info' => '',
            'pdf_date' => '',
            'course_code' => api_get_course_id(),
            'student_info' => $userInfo,
            'show_grade_generated_date' => true,
            'show_real_course_teachers' => false,
            'show_teacher_as_myself' => false,
            'orientation' => 'P',
        ];

        if (empty($pdf)) {
            $pdf = new PDF('A4', $params['orientation'], $params);
        }

        $pdf->params['student_info'] = $userInfo;
        $extraRows = [];
        if (api_get_configuration_value('allow_gradebook_comments')) {
            $commentInfo = self::getComment($cat->get_id(), $userId);
            if ($commentInfo) {
                $extraRows[] = [
                    'label' => get_lang('Comment'),
                    'content' => $commentInfo['comment'],
                ];
            }
        }

        $file = api_get_path(SYS_ARCHIVE_PATH).uniqid().'.html';

        $settings = api_get_configuration_value('gradebook_pdf_export_settings');
        $showFeedBack = true;
        if (isset($settings['hide_feedback_textarea']) && $settings['hide_feedback_textarea']) {
            $showFeedBack = false;
        }

        $feedback = '';
        if ($showFeedBack) {
            $feedback = '<br />'.get_lang('Feedback').'<br />
            <textarea class="form-control" rows="5" cols="100">&nbsp;</textarea>';
        }
        $content = $table.$graph.$feedback;
        $result = $pdf->html_to_pdf_with_template(
            $content,
            $saveToFile,
            $saveToHtmlFile,
            true,
            $extraRows
        );

        if ($saveToHtmlFile) {
            return $result;
        }

        return $file;
    }

    public static function getComment($gradeBookId, $userId)
    {
        $gradeBookId = (int) $gradeBookId;
        $userId = (int) $userId;

        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_COMMENT);
        $sql = "SELECT * FROM $table
                WHERE user_id = $userId AND gradebook_id = $gradeBookId";
        $result = Database::query($sql);

        return Database::fetch_array($result);
    }

    public static function saveComment($gradeBookId, $userId, $comment)
    {
        $commentInfo = self::getComment($gradeBookId, $userId);
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_COMMENT);
        if (empty($commentInfo)) {
            $params = [
                'gradebook_id' => $gradeBookId,
                'user_id' => $userId,
                'comment' => $comment,
                'created_at' => api_get_utc_datetime(),
                'updated_at' => api_get_utc_datetime(),
            ];
            Database::insert($table, $params);
        } else {
            $params = [
                'comment' => $comment,
                'updated_at' => api_get_utc_datetime(),
            ];
            Database::update($table, $params, ['id = ?' => $commentInfo['id']]);
        }
    }

    public static function returnJsExportAllCertificates(
        $buttonSelector,
        $categoryId,
        $courseCode,
        $sessionId = 0,
        $filterOfficialCodeGet = null
    ) {
        $params = [
            'a' => 'export_all_certificates',
            'cat_id' => $categoryId,
            'cidReq' => $courseCode,
            'id_session' => $sessionId,
            'filter' => $filterOfficialCodeGet,
        ];
        $urlExportAll = 'gradebook.ajax.php?'.http_build_query($params);

        $params['a'] = 'verify_export_all_certificates';
        $urlVerifyExportAll = 'gradebook.ajax.php?'.http_build_query($params);

        $imgSrcLoading = api_get_path(WEB_LIBRARY_JS_PATH).'loading.gif';
        $imgSrcPdf = Display::return_icon('pdf.png', '', [], ICON_SIZE_MEDIUM, false, true);

        $urlDownload = api_get_path(WEB_CODE_PATH).'gradebook/gradebook_display_certificate.php?'.api_get_cidreq().'&action=download_all_certificates&catId='.$categoryId;

        return "<script>
            $(function () {
                var \$btnExport = $('$buttonSelector'),
                    interval = 0;

                function verifyExportSuccess (response) {
                    if (response.length > 0) {
                        \$btnExport.find('img').prop('src', '$imgSrcPdf');
                        window.clearInterval(interval);
                        window.removeEventListener('beforeunload', onbeforeunloadListener);
                        window.location.href = '".$urlDownload."';
                    }
                }

                function exportAllSuccess () {
                    interval = window.setInterval(
                        function () {
                            $.ajax(_p.web_ajax + '$urlVerifyExportAll').then(verifyExportSuccess);
                        },
                        15000
                    );
                }

                function onbeforeunloadListener (e) {
                    e.preventDefault();
                    e.returnValue = '';
                }

                \$btnExport.on('click', function (e) {
                    e.preventDefault();
                    \$btnExport.find('img').prop({src: '$imgSrcLoading', width: 40, height: 40});
                    window.addEventListener('beforeunload', onbeforeunloadListener);
                    $.ajax(_p.web_ajax + '$urlExportAll').then(exportAllSuccess);
                });
            });
            </script>";
    }
}
