<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;

/**
 * Class GradebookUtils.
 */
class GradebookUtils
{
    /**
     * Adds a resource to the unique gradebook of a given course.
     *
     * @param int
     * @param int $courseId Course ID
     * @param int     Resource type (use constants defined in linkfactory.class.php)
     * @param int     Resource ID in the corresponding tool
     * @param string  Resource name to show in the gradebook
     * @param int     Resource weight to set in the gradebook
     * @param int     Resource max
     * @param string  Resource description
     * @param int     Visibility (0 hidden, 1 shown)
     * @param int     Session ID (optional or 0 if not defined)
     * @param int
     * @param int $resource_type
     *
     * @return bool True on success, false on failure
     * @throws Exception
     */
    public static function add_resource_to_course_gradebook(
        int $category_id,
        int $courseId,
        string $resource_type,
        int $resource_id,
        ?string $resource_name = '',
        ?int $weight = 0,
        ?int $max = 0,
        ?string $resource_description = '',
        ?int $visible = 0,
        ?int $session_id = 0,
        ?int $link_id = null
    ): bool
    {
        $link = LinkFactory::create($resource_type);
        $link->set_user_id(api_get_user_id());
        $link->setCourseId($courseId);

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
     * @param int   $link_id Link/Resource ID
     * @param int   $courseId
     * @param float $weight
     *
     * @return bool false on error, true on success
     * @throws Exception
     */
    public static function updateResourceFromCourseGradebook(
        int $link_id,
        int $courseId,
        float $weight
    ): bool
    {
        if (!empty($link_id) && !empty($courseId)) {
            $sql = 'UPDATE '.Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK).'
                    SET weight = '.$weight.'
                    WHERE c_id = '.$courseId.' AND id = '.$link_id;
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
    public static function build_type_icon_tag($kind, $style = null)
    {
        return Display::getMdiIcon(
            self::get_icon_file_name($kind),
            'ch-tool-icon',
            $style,
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
                $icon = ToolIcon::GRADEBOOK;
                break;
            case 'evalempty':
                $icon = 'table';
                break;
            case 'evalnotempty':
                $icon = 'table-check';
                break;
            case 'exercise':
            case LINK_EXERCISE:
                $icon = ObjectIcon::TEST;
                break;
            case 'learnpath':
            case LINK_LEARNPATH:
                $icon = ObjectIcon::LP;
                break;
            case 'studentpublication':
            case LINK_STUDENTPUBLICATION:
                $icon = ObjectIcon::ASSIGNMENT;
                break;
            case 'link':
                $icon = ObjectIcon::LINK;
                break;
            case 'forum':
            case LINK_FORUM_THREAD:
                $icon = ObjectIcon::FORUM_THREAD;
                break;
            case 'attendance':
            case LINK_ATTENDANCE:
                $icon = ObjectIcon::ATTENDANCE;
                break;
            case 'survey':
            case LINK_SURVEY:
                $icon = ObjectIcon::SURVEY;
                break;
            case 'dropbox':
            case LINK_DROPBOX:
                $icon = ToolIcon::DROPBOX;
                break;
            default:
                $icon = ObjectIcon::LINK;
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
        $show_message = Category::show_message_resource_delete($cat->getCourseId());
        $grade_model_id = $selectcat->get_grade_model_id();
        $selectcat = $selectcat->get_id();
        $modify_icons = null;

        if ('' === $show_message) {
            $visibility_icon = (0 == $cat->is_visible()) ? ActionIcon::INVISIBLE : ActionIcon::VISIBLE;
            $visibility_command = (0 == $cat->is_visible()) ? 'set_visible' : 'set_invisible';

            $modify_icons .= '<a class="view_children" data-cat-id="'.$cat->get_id().'" href="javascript:void(0);">'.
                Display::getMdiIcon(
                    ActionIcon::VIEW_MORE,
                    'ch-tool-icon',
                    '',
                    ICON_SIZE_SMALL,
                    get_lang('Show')
                ).
                '</a>';

            if (!api_is_allowed_to_edit(null, true)) {
                $modify_icons .= Display::url(
                    Display::getMdiIcon(
                        StateIcon::LIST_VIEW,
                        'ch-tool-icon',
                        null,
                        ICON_SIZE_SMALL,
                        get_lang('List View')
                    ),
                    'personal_stats.php?'.http_build_query([
                        'selectcat' => $cat->get_id(),
                    ]).'&'.api_get_cidreq(),
                    [
                        'class' => 'ajax',
                        'data-title' => get_lang('List View'),
                    ]
                );
            }

            $courseParams = api_get_cidreq_params(
                $cat->getCourseId(),
                $cat->get_session_id()
            );

            if (api_is_allowed_to_edit(null, true)) {
                // Locking button
                if ('true' == api_get_setting('gradebook_locking_enabled')) {
                    if ($cat->is_locked()) {
                        if (api_is_platform_admin()) {
                            $modify_icons .= '&nbsp;<a onclick="javascript:if (!confirm(\''.addslashes(get_lang('Are you sure you want to unlock this element?')).'\')) return false;" href="'.api_get_self().'?'.api_get_cidreq().'&category_id='.$cat->get_id().'&action=unlock">'.
                                Display::getMdiIcon(ActionIcon::LOCK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Unlock evaluation.')).'</a>';
                        } else {
                            $modify_icons .= '&nbsp;<a href="#">'.
                                Display::getMdiIcon(ActionIcon::LOCK, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, get_lang('This assessment has been locked. You cannot unlock it. If you really need to unlock it, please contact the platform administrator, explaining the reason why you would need to do that (it might otherwise be considered as fraud attempt).')).'</a>';
                        }
                        $modify_icons .= '&nbsp;<a href="gradebook_flatview.php?export_pdf=category&selectcat='.$cat->get_id().'" >'.Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Export to PDF')).'</a>';
                    } else {
                        $modify_icons .= '&nbsp;<a onclick="javascript:if (!confirm(\''.addslashes(get_lang('Are you sure you want to lock this item? After locking this item you can\'t edit the user results. To unlock it, you need to contact the platform administrator.')).'\')) return false;" href="'.api_get_self().'?'.api_get_cidreq().'&category_id='.$cat->get_id().'&action=lock">'.
                            Display::getMdiIcon(ActionIcon::UNLOCK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Lock evaluation')).'</a>';
                        $modify_icons .= '&nbsp;<a href="#" >'.
                            Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, get_lang('Export to PDF')).'</a>';
                    }
                }

                if (empty($grade_model_id) || -1 == $grade_model_id) {
                    if ($cat->is_locked() && !api_is_platform_admin()) {
                        $modify_icons .= Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, get_lang('Edit'));
                    } else {
                        $modify_icons .= '<a href="gradebook_edit_cat.php?editcat='.$cat->get_id().'&'.$courseParams.'">'.
                            Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a>';
                    }
                }

                $modify_icons .= '<a href="gradebook_edit_all.php?selectcat='.$cat->get_id().'&'.$courseParams.'">'.
                    Display::getMdiIcon('percent-box', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Weight in Report')).'</a>';

                $modify_icons .= '<a href="gradebook_flatview.php?selectcat='.$cat->get_id().'&'.$courseParams.'">'.
                    Display::getMdiIcon('format-list-text', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('List View')).'</a>';
                $modify_icons .= '&nbsp;<a href="'.api_get_self().'?visiblecat='.$cat->get_id().'&'.$visibility_command.'=&selectcat='.$selectcat.'&'.$courseParams.'">'.
                    Display::getMdiIcon(
                        $visibility_icon,
                        'ch-tool-icon',
                        null,
                        ICON_SIZE_SMALL,
                        get_lang('Visible')
                    ).'</a>';

                if ($cat->is_locked() && !api_is_platform_admin()) {
                    $modify_icons .= Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, get_lang('Delete all'));
                } else {
                    $modify_icons .= '&nbsp;<a href="'.api_get_self().'?deletecat='.$cat->get_id().'&selectcat='.$selectcat.'&'.$courseParams.'" onclick="return confirmation();">'.
                        Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete all')).
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
        $message_eval = Category::show_message_resource_delete($eval->getCourseId());
        $courseParams = api_get_cidreq_params($eval->getCourseId(), $eval->getSessionId());

        if ('' === $message_eval && api_is_allowed_to_edit(null, true)) {
            $visibility_icon = 0 == $eval->is_visible() ? ActionIcon::INVISIBLE : ActionIcon::VISIBLE;
            $visibility_command = 0 == $eval->is_visible() ? 'set_visible' : 'set_invisible';
            if ($is_locked && !api_is_platform_admin()) {
                $modify_icons = Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, get_lang('Edit'));
            } else {
                $modify_icons = '<a href="gradebook_edit_eval.php?editeval='.$eval->get_id().'&'.$courseParams.'">'.
                    Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit weight')).
                    '</a>';
            }

            $modify_icons .= '&nbsp;<a href="'.api_get_self().'?visibleeval='.$eval->get_id().'&'.$visibility_command.'=&selectcat='.$selectcat.'&'.$courseParams.' ">'.
                Display::getMdiIcon(
                    $visibility_icon,
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_SMALL,
                    get_lang('Visible')
                ).
                '</a>';

            if (api_is_allowed_to_edit(null, true)) {
                $modify_icons .= '&nbsp;<a href="gradebook_showlog_eval.php?visiblelog='.$eval->get_id().'&selectcat='.$selectcat.' &'.$courseParams.'">'.
                    Display::getMdiIcon(ActionIcon::VIEW_DETAILS, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Assessment history')).
                    '</a>';

                $allowStats = ('true' === api_get_setting('gradebook.allow_gradebook_stats'));
                if ($allowStats) {
                    $modify_icons .= Display::url(
                        Display::getMdiIcon(ActionIcon::REFRESH, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Generate statistics')),
                        api_get_self().'?itemId='.$eval->get_id().'&action=generate_eval_stats&selectcat='.$selectcat.'&'.$courseParams
                    );
                }
            }

            if ($is_locked && !api_is_platform_admin()) {
                $modify_icons .= '&nbsp;'.
                    Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, get_lang('Delete'));
            } else {
                $modify_icons .= '&nbsp;<a href="'.api_get_self().'?deleteeval='.$eval->get_id().'&selectcat='.$selectcat.' &'.$courseParams.'" onclick="return confirmation();">'.
                    Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).
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
        $message_link = Category::show_message_resource_delete($link->getCourseId());
        $is_locked = $link->is_locked();
        $modify_icons = null;

        if (!api_is_allowed_to_edit(null, true)) {
            return null;
        }

        $courseParams = api_get_cidreq_params($link->getCourseId(), $link->get_session_id());

        if ('' === $message_link) {
            $visibility_icon = 0 == $link->is_visible() ? ActionIcon::INVISIBLE : ActionIcon::VISIBLE;
            $visibility_command = 0 == $link->is_visible() ? 'set_visible' : 'set_invisible';

            if ($is_locked && !api_is_platform_admin()) {
                $modify_icons = Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, get_lang('Edit'));
            } else {
                $modify_icons = '<a href="gradebook_edit_link.php?editlink='.$link->get_id().'&'.$courseParams.'">'.
                    Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit weight')).
                    '</a>';
            }
            $modify_icons .= '&nbsp;<a href="'.api_get_self().'?visiblelink='.$link->get_id().'&'.$visibility_command.'=&selectcat='.$selectcat.'&'.$courseParams.' ">'.
                Display::getMdiIcon(
                    $visibility_icon,
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_SMALL,
                    get_lang('Visible'),
                ).
                '</a>';

            $modify_icons .= '&nbsp;<a href="gradebook_showlog_link.php?visiblelink='.$link->get_id().'&selectcat='.$selectcat.'&'.$courseParams.'">'.
                Display::getMdiIcon(
                    ActionIcon::VIEW_DETAILS,
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_SMALL,
                    get_lang('Assessment history')
                ).
                '</a>';

            $allowStats = ('true' === api_get_setting('gradebook.allow_gradebook_stats'));
            if ($allowStats && LINK_EXERCISE == $link->get_type()) {
                $modify_icons .= Display::url(
                    Display::getMdiIcon(ActionIcon::REFRESH, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Generate statistics')),
                    api_get_self().'?itemId='.$link->get_id().'&action=generate_link_stats&selectcat='.$selectcat.'&'.$courseParams
                );
            }

            //If a work is added in a gradebook you can only delete the link in the work tool
            if ($is_locked && !api_is_platform_admin()) {
                $modify_icons .= '&nbsp;'.
                    Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, get_lang('Delete'));
            } else {
                $modify_icons .= '&nbsp;
                <a
                    href="'.api_get_self().'?deletelink='.$link->get_id().'&selectcat='.$selectcat.' &'.$courseParams.'"
                    onclick="return confirmation();">'.
                    Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).
                    '</a>';
            }

            return $modify_icons;
        }
    }

    /**
     * Checks if a resource is in the unique gradebook of a given course.
     *
     * @param int    $courseId Course ID
     * @param int    $resource_type Resource type (use constants defined in linkfactory.class.php)
     * @param int    $resource_id Resource ID in the corresponding tool
     * @param ?int    $session_id Session ID (optional -  0 if not defined) (WARNING: not yet implemented)
     *
     * @return array false on error or array of resource
     * @throws Exception
     */
    public static function isResourceInCourseGradebook(
        int $courseId,
        int $resource_type,
        int $resource_id,
        ?int $session_id
    ): array
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        if (empty($courseId) or empty($resource_type) or empty($resource_id)) {
            return [];
        }

        $sql = "SELECT * FROM $table l
                WHERE
                    c_id = $courseId AND
                    type = $resource_type AND
                    ref_id = $resource_id";
        $res = Database::query($sql);

        if (Database::num_rows($res) < 1) {
            return [];
        }

        return Database::fetch_assoc($res);
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
                ON c.id = l.c_id
                WHERE l.id='.intval($id_link).' OR l.category_id='.intval($id_link);
        $res = Database::query($sql);
        $array = Database::fetch_assoc($res);

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

        if (!isset($table_evaluated[$type][0])) {
            throw new \InvalidArgumentException('Unknown evaluated type: '.$type);
        }

        return Database::get_course_table($table_evaluated[$type][0]);
    }

    /**
     * @param Category $cat
     * @param $users
     * @param $alleval
     * @param $alllinks
     * @param $params
     * @param null $mainCourseCategory
     *
     * @return array
     */
    public static function get_printable_data(
        $cat,
        $users,
        $alleval,
        $alllinks,
        $params,
        $mainCourseCategory = null
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

        $count = (($offset + 10) > $datagen->get_total_items_count()) ? ($datagen->get_total_items_count() - $offset) : GRADEBOOK_ITEM_LIMIT;
        $header_names = $datagen->get_header_names($offset, $count, true);
        $data_array = $datagen->get_data(
            FlatViewDataGenerator::FVDG_SORT_LASTNAME,
            0,
            null,
            $offset,
            $count,
            true,
            true
        );

        $result = [];
        foreach ($data_array as $data) {
            $result[] = array_slice($data, 1);
        }
        $return = [$header_names, $result];

        return $return;
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
        $repository = Container::getGradeBookCertificateRepository();

        $repository->registerUserInfoAboutCertificate(
            (int) $cat_id,
            (int) $user_id,
            (float) api_float_val($score_certificate),
            ''
        );
    }

    /**
     * Get date of user certificate.
     *
     * @param int $cat_id  The category id
     * @param int $user_id The user id
     *
     * @return array
     */
    public static function get_certificate_by_user_id($cat_id, $user_id)
    {
        $repository = Container::getGradeBookCertificateRepository();
        $certificate = $repository->getCertificateByUserId($cat_id, $user_id, true);

        return $certificate;
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

        $sql = 'SELECT DISTINCT u.id AS user_id, u.lastname, u.firstname, u.username, gc.created_at
            FROM '.$table_user.' u
            INNER JOIN '.$table_certificate.' gc ON u.id = gc.user_id';

        $where = [];

        if (!is_null($cat_id) && $cat_id > 0) {
            $where[] = 'gc.cat_id = '.(int) $cat_id;
        }
        if (!empty($userList)) {
            $ids = array_map('intval', $userList);
            $where[] = 'u.id IN ('.implode(',', $ids).')';
        }
        if ($where) {
            $sql .= ' WHERE '.implode(' AND ', $where);
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
                    gc.id,
                    gc.publish
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
     * Gets the content of an HTML document with placeholders replaced
     * @param int    $user_id
     * @param int    $courseId
     * @param int    $sessionId
     * @param bool   $is_preview
     * @param bool   $hide_print_button
     *
     * @return array
     */
    public static function get_user_certificate_content(
        int $user_id,
        int $courseId,
        int $sessionId,
        ?bool $is_preview = false,
        ?bool $hide_print_button = false
    ): array
    {
        // Generate document HTML
        $content_html = DocumentManager::replace_user_info_into_html(
            $user_id,
            api_get_course_info_by_id($courseId),
            $sessionId,
            $is_preview
        );

        $new_content_html = isset($content_html['content']) ? $content_html['content'] : null;
        $variables = isset($content_html['variables']) ? $content_html['variables'] : null;
        $path_image = api_get_path(WEB_PUBLIC_PATH).'/img/gallery';
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
                    Display::getMdiIcon(ActionIcon::PRINT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Print')),
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
     * Create a gradebook in the given course if no gradebook exists yet
     * @param ?int $courseId
     * @param ?int $gradebook_model_id
     *
     * @return int 0 on failure, gradebook ID otherwise
     * @throws Exception
     */
    public static function create_default_course_gradebook(
        ?int $courseId = null,
        ?int $gradebook_model_id = 0
    ): int
    {
        if (api_is_allowed_to_edit(true, true)) {
            if (empty($courseId)) {
                $courseId = api_get_course_int_id();
            }
            $session_id = api_get_session_id();

            $t = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
            $sql = "SELECT * FROM $t
                    WHERE c_id = $courseId ";
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
                    $cat->set_name($courseId.' - '.get_lang('Session').' '.$s_name);
                    $cat->set_session_id($session_id);
                } else {
                    $cat->set_name(strval($courseId));
                }
                $cat->setCourseId($courseId);
                $cat->set_description('');
                $cat->set_user_id(api_get_user_id());
                $cat->set_parent_id(0);
                $default_weight_setting = api_get_setting('gradebook_default_weight');
                $default_weight = !empty($default_weight_setting) ? $default_weight_setting : 100;
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

        return 0;
    }

    /**
     * @param FormValidator $form
     */
    public static function load_gradebook_select_in_tool($form)
    {
        $session_id = api_get_session_id();

        self::create_default_course_gradebook();

        // Cat list
        $all_categories = Category::load(
            null,
            null,
            api_get_course_int_id(),
            null,
            null,
            $session_id,
            null
        );
        $select_gradebook = $form->addSelect(
            'category_id',
            get_lang('Select assessment')
        );

        if (!empty($all_categories)) {
            foreach ($all_categories as $my_cat) {
                if ($my_cat->getCourseId() == api_get_course_int_id()) {
                    $grade_model_id = $my_cat->get_grade_model_id();
                    if (empty($grade_model_id)) {
                        if (0 == $my_cat->get_parent_id()) {
                            $select_gradebook->addOption(get_lang('Default'), $my_cat->get_id());
                            $cats_added[] = $my_cat->get_id();
                        } else {
                            $select_gradebook->addOption($my_cat->get_name(), $my_cat->get_id());
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
     * @param array         $cat
     * @param               $users
     * @param               $alleval
     * @param               $alllinks
     * @param array         $params
     * @param null          $mainCourseCategory
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
        $cat = $cat[0] ?? null;

        if (!$cat instanceof Category) {
            return;
        }

        // Getting data
        $printable_data = self::get_printable_data(
            $cat,
            $users,
            $alleval,
            $alllinks,
            $params,
            $mainCourseCategory
        );

        // HTML report creation first

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

        $parent_id = $cat->get_parent_id();
        if (isset($cat) && isset($parent_id)) {
            if (0 == $parent_id) {
                $grade_model_id = $cat->get_grade_model_id();
            } else {
                $parent_cat = Category::load($parent_id);
                $grade_model_id = $parent_cat[0]->get_grade_model_id();
            }
        }

        $use_grade_model = true;
        if (empty($grade_model_id) || -1 == $grade_model_id) {
            $use_grade_model = false;
        }

        if ($use_grade_model) {
            if (0 == $parent_id) {
                $title = api_strtoupper(get_lang('Average')).'<br />'.get_lang('Detailed');
            } else {
                $title = api_strtoupper(get_lang('Average')).'<br />'.$cat[0]->get_description().' - ('.$cat[0]->get_name().')';
            }
        } else {
            if (0 == $parent_id) {
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
        $table->setHeaderContents($row, $column, get_lang('N°'));
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

                    if ('name' === $key) {
                        $attributes['align'] = 'left';
                    }
                    if ('total' === $key) {
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
            $table->setCellContents($row, $column, get_lang('No results found'));
            $table->updateCellAttributes($row, $column, 'colspan="'.$columns.'" align="center" class="row_odd"');
        }

        $course_code = trim($cat->get_course_code());
        $pdfParams = [
            'filename' => get_lang('List View').'_'.api_get_local_time(),
            'pdf_title' => $title,
            'course_code' => $course_code,
            'add_signatures' => ['Drh', 'Teacher', 'Date'],
        ];

        $page_format = 'landscape' === $params['orientation'] ? 'A4-L' : 'A4';
        ob_start();
        $pdf = new PDF($page_format, $page_format, $pdfParams);
        $pdf->html_to_pdf_with_template($flatviewtable->return_table(), false, false, true);
        $content = ob_get_contents();
        ob_end_clean();
        echo $content;
        exit;
    }

    /**
     * @param string[] $listValues
     *
     * @return string
     */
    public static function scoreBadges($listValues)
    {
        $counter = 1;
        $badges = [];
        foreach ($listValues as $value) {
            $class = 'warning';
            if (1 == $counter) {
                $class = 'success';
            }
            $counter++;
            $badges[] = Display::label($value, $class);
        }

        return Display::badgeGroup($badges);
    }

    /**
     * returns users within a course given by param.
     *
     * @param ?int $courseId
     *
     * @return array
     * @throws Exception
     * @todo use CourseManager
     *
     */
    public static function get_users_in_course(?int $courseId = 0)
    {
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname ASC' : ' ORDER BY lastname, firstname ASC';
        $current_session = api_get_session_id();

        if (!empty($current_session)) {
            $sql = "SELECT user.id as user_id, user.username, lastname, firstname, official_code
                    FROM $tbl_session_course_user as scru
                    INNER JOIN $tbl_user as user
                    ON (scru.user_id = user.id)
                    WHERE
                        scru.status = ".Session::STUDENT." AND
                        scru.c_id = $courseId AND
                        session_id = '$current_session'
                    $order_clause
                    ";
        } else {
            $sql = 'SELECT user.id as user_id, user.username, lastname, firstname, official_code
                    FROM '.$tbl_course_user.' as course_rel_user
                    INNER JOIN '.$tbl_user.' as user
                    ON (course_rel_user.user_id = user.id)
                    WHERE
                        course_rel_user.status = '.STUDENT.' AND
                        course_rel_user.c_id = '.$courseId.' '.
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
     * @throws Exception
     */
    public static function get_all_users($evals = [], $links = []): array
    {
        $courseId = api_get_course_int_id();
        // By default, add all user in course
        $courseIds[$courseId] = '1';
        $users = self::get_users_in_course($courseId);

        foreach ($evals as $eval) {
            /* @var Evaluation $eval */
            $loopCourseId = $eval->getCourseId();
            // evaluation in course
            if (!empty($loopCourseId)) {
                if (!array_key_exists($loopCourseId, $courseIds)) {
                    $courseIds[$loopCourseId] = '1';
                    $users = array_merge($users, self::get_users_in_course($loopCourseId));
                }
            } else {
                // course independent evaluation
                $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
                $tbl_res = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);

                $sql = 'SELECT user.id as user_id, lastname, firstname, user.official_code
                        FROM '.$tbl_res.' as res, '.$tbl_user.' as user
                        WHERE
                            res.evaluation_id = '.$eval->get_id().' AND
                            res.user_id = user.id
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
            /** @var EvalLink $link */
            $loopCourseId = $link->getCourseId();
            if (!array_key_exists($loopCourseId, $courseIds)) {
                $courseIds[$loopCourseId] = '1';
                $users = array_merge(
                    $users,
                    self::get_users_in_course($loopCourseId)
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
        if (!api_is_allowed_to_edit() || $mask === '') {
            return null;
        }

        $mask = Database::escape_string($mask);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_cru  = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $sql = 'SELECT DISTINCT user.id AS user_id, user.lastname, user.firstname, user.email, user.official_code
            FROM '.$tbl_user.' user';

        if (!api_is_platform_admin()) {
            $sql .= ' INNER JOIN '.$tbl_cru.' cru ON (cru.user_id = user.id)
                  AND cru.relation_type <> '.COURSE_RELATION_TYPE_RRHH.'
                  AND cru.c_id IN (
                        SELECT c_id FROM '.$tbl_cru.'
                         WHERE user_id = '.api_get_user_id().' AND status = '.COURSEMANAGER.'
                  )';
        }

        $sql .= ' WHERE user.status = '.STUDENT.'
              AND (user.lastname LIKE \'%'.$mask.'%\' OR user.firstname LIKE \'%'.$mask.'%\')';

        $orderBy = api_is_western_name_order() ? 'firstname, lastname' : 'lastname, firstname';
        $sql .= ' ORDER BY '.$orderBy;

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
                    WHERE id = '.intval($row_attendance['ref_id']);
            Database::query($sql);
        }
        // Update weight into forum thread
        $sql = 'UPDATE '.$tbl_forum_thread.' SET
                thread_weight = '.api_float_val($weight).'
                WHERE
                    iid = (
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
                WHERE
                    w.iid = (
                        SELECT l.refId FROM ChamiloCoreBundle:GradebookLink l
                        WHERE l.id = :link AND l.type = :type
                    )
            ')
            ->execute([
                'final_weight' => $weight,
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

            $category = Category::load(null, null, $course['real_id']);

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

            $path = $certificateInfo['path_certificate'] ?? '';
            $publish = $certificateInfo['publish'] ?? 0;
            $hash = pathinfo($path, PATHINFO_FILENAME);

            $link = '';
            $pdf = '';
            if (api_is_platform_admin()) {
                $publish = true;
            }
            if (!empty($hash) && $publish) {
                $link = api_get_path(WEB_PATH) . "certificates/{$hash}.html";
                $pdf = api_get_path(WEB_PATH)."certificates/{$hash}.pdf";
            }

            $courseList[] = [
                'course' => $courseInfo['title'],
                'score' => $certificateInfo['score_certificate'],
                'date' => api_format_date($certificateInfo['created_at'], DATE_FORMAT_SHORT),
                'link' => $link,
                'pdf' => $pdf,
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
                    $course['real_id'],
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
                $hash = pathinfo($certificateInfo['path_certificate'], PATHINFO_FILENAME);
                $sessionList[] = [
                    'session' => $session['session_name'],
                    'course' => $course['title'],
                    'score' => $certificateInfo['score_certificate'],
                    'date' => api_format_date($certificateInfo['created_at'], DATE_FORMAT_SHORT),
                    'link' => api_get_path(WEB_PATH)."certificates/{$hash}.html",
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
            api_get_course_int_id(),
            api_get_session_id()
        );
        $alleval = $cats[0]->get_evaluations($userId);
        $alllink = $cats[0]->get_links($userId);

        $loadStats = [];
        if ('true' === api_get_setting('gradebook_detailed_admin_view')) {
            $loadStats = [1, 2, 3];
        }

        $gradebooktable = new GradebookTable(
            $cat,
            $allcat,
            $alleval,
            $alllink,
            [],
            true,
            false,
            $userId,
            $studentList,
            $loadStats
        );
        $gradebooktable->hideNavigation = true;
        $gradebooktable->userId = $userId;

        if (api_is_allowed_to_edit(null, true)) {
        } else {
            if (empty($model)) {
                $gradebooktable->td_attributes = [
                    3 => 'class=centered',
                    4 => 'class=centered',
                    5 => 'class=centered',
                    6 => 'class=centered',
                    7 => 'class=centered',
                ];
            }
        }
        $table = $gradebooktable->return_table();

        $graph = '';
        if (empty($model)) {
            $graph = $gradebooktable->getGraph();
        }
        $params = [
            'pdf_title' => sprintf(get_lang('Grades from course: %s'), $courseInfo['name']),
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
        if ('true' === api_get_setting('gradebook.allow_gradebook_comments')) {
            $commentInfo = self::getComment($cat->get_id(), $userId);
            if ($commentInfo) {
                $extraRows[] = [
                    'label' => get_lang('Comment'),
                    'content' => $commentInfo['comment'],
                ];
            }
        }

        $file = api_get_path(SYS_ARCHIVE_PATH).uniqid().'.html';

        $settings = api_get_setting('gradebook.gradebook_pdf_export_settings', true);
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
}
