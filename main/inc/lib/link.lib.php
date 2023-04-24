<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLink;
use GuzzleHttp\Client;

/**
 * Function library for the links tool.
 *
 * This is a complete remake of the original link tool.
 * New features:
 * - Organize links into categories;
 * - favorites/bookmarks interface;
 * - move links up/down within a category;
 * - move categories up/down;
 * - expand/collapse all categories;
 * - add link to 'root' category => category-less link is always visible.
 *
 * @author Patrick Cool, complete remake (December 2003 - January 2004)
 * @author René Haentjens, CSV file import (October 2004)
 */
class Link extends Model
{
    public $table;
    public $is_course_model = true;
    public $columns = [
        'id',
        'c_id',
        'url',
        'title',
        'description',
        'category_id',
        'display_order',
        'on_homepage',
        'target',
        'session_id',
    ];
    public $required = ['url', 'title'];
    private $course;

    /**
     * Link constructor.
     */
    public function __construct()
    {
        $this->table = Database::get_course_table(TABLE_LINK);
    }

    /**
     * @param array $course
     */
    public function setCourse($course)
    {
        $this->course = $course;
    }

    /**
     * @return array
     */
    public function getCourse()
    {
        return !empty($this->course) ? $this->course : api_get_course_info();
    }

    /**
     * Organize the saving of a link, using the parent's save method and
     * updating the item_property table.
     *
     * @param array $params
     * @param bool  $show_query Whether to show the query in logs when
     *                          calling parent's save method
     *
     * @return bool True if link could be saved, false otherwise
     */
    public function save($params, $show_query = null)
    {
        $course_info = $this->getCourse();
        $courseId = $course_info['real_id'];

        $params['session_id'] = api_get_session_id();
        $params['category_id'] = isset($params['category_id']) ? $params['category_id'] : 0;

        $sql = "SELECT MAX(display_order)
                FROM  ".$this->table."
                WHERE
                    c_id = $courseId AND
                    category_id = '".intval($params['category_id'])."'";
        $result = Database::query($sql);
        list($orderMax) = Database::fetch_row($result);
        $order = $orderMax + 1;
        $params['display_order'] = $order;

        $id = parent::save($params, $show_query);

        if (!empty($id)) {
            // iid
            $sql = "UPDATE ".$this->table." SET id = iid WHERE iid = $id";
            Database::query($sql);

            api_item_property_update(
                $course_info,
                TOOL_LINK,
                $id,
                'LinkAdded',
                api_get_user_id()
            );

            api_set_default_visibility($id, TOOL_LINK, 0, $course_info);
        }

        return $id;
    }

    /**
     * Update a link in the database.
     *
     * @param int    $linkId    The ID of the link to update
     * @param string $linkUrl   The new URL to be saved
     * @param int    $courseId
     * @param int    $sessionId
     *
     * @return bool
     */
    public function updateLink(
        $linkId,
        $linkUrl,
        $courseId = null,
        $sessionId = null
    ) {
        $tblLink = Database::get_course_table(TABLE_LINK);
        $linkUrl = Database::escape_string($linkUrl);
        $linkId = intval($linkId);
        if (is_null($courseId)) {
            $courseId = api_get_course_int_id();
        }
        $courseId = intval($courseId);
        if (is_null($sessionId)) {
            $sessionId = api_get_session_id();
        }
        $sessionId = intval($sessionId);
        if ($linkUrl != '') {
            $sql = "UPDATE $tblLink SET
                    url = '$linkUrl'
                    WHERE id = $linkId AND c_id = $courseId AND session_id = $sessionId";
            $resLink = Database::query($sql);

            return $resLink;
        }

        return false;
    }

    /**
     * Used to add a link or a category.
     *
     * @param string $type , "link" or "category"
     *
     * @todo replace strings by constants
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @return bool True on success, false on failure
     */
    public static function addlinkcategory($type)
    {
        $ok = true;
        $_course = api_get_course_info();
        $course_id = $_course['real_id'];
        $session_id = api_get_session_id();

        if ($type === 'link') {
            $title = $_POST['title'];
            $urllink = $_POST['url'];
            $description = $_POST['description'];
            $selectcategory = $_POST['category_id'];

            $onhomepage = 0;
            if (isset($_POST['on_homepage'])) {
                $onhomepage = $_POST['on_homepage'];
            }

            $target = '_self'; // Default target.
            if (!empty($_POST['target'])) {
                $target = $_POST['target'];
            }

            $urllink = trim($urllink);
            $title = trim($title);
            $description = trim($description);

            // We ensure URL to be absolute.
            if (strpos($urllink, '://') === false) {
                $urllink = 'http://'.$urllink;
            }

            // If the title is empty, we use the URL as title.
            if ($title == '') {
                $title = $urllink;
            }

            // If the URL is invalid, an error occurs.
            if (!api_valid_url($urllink, true)) {
                // A check against an absolute URL
                Display::addFlash(Display::return_message(get_lang('GiveURL'), 'error'));

                return false;
            } else {
                // Looking for the largest order number for this category.
                $link = new Link();
                $params = [
                    'c_id' => $course_id,
                    'url' => $urllink,
                    'title' => $title,
                    'description' => $description,
                    'category_id' => $selectcategory,
                    'on_homepage' => $onhomepage,
                    'target' => $target,
                    'session_id' => $session_id,
                ];
                $link_id = $link->save($params);

                if ((api_get_setting('search_enabled') === 'true') &&
                    $link_id && extension_loaded('xapian')
                ) {
                    require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';

                    $course_int_id = $_course['real_id'];
                    $courseCode = $_course['code'];
                    $specific_fields = get_specific_field_list();
                    $ic_slide = new IndexableChunk();

                    // Add all terms to db.
                    $all_specific_terms = '';
                    foreach ($specific_fields as $specific_field) {
                        if (isset($_REQUEST[$specific_field['code']])) {
                            $sterms = trim($_REQUEST[$specific_field['code']]);
                            if (!empty($sterms)) {
                                $all_specific_terms .= ' '.$sterms;
                                $sterms = explode(',', $sterms);
                                foreach ($sterms as $sterm) {
                                    $ic_slide->addTerm(
                                        trim($sterm),
                                        $specific_field['code']
                                    );
                                    add_specific_field_value(
                                        $specific_field['id'],
                                        $courseCode,
                                        TOOL_LINK,
                                        $link_id,
                                        $sterm
                                    );
                                }
                            }
                        }
                    }

                    // Build the chunk to index.
                    $ic_slide->addValue('title', $title);
                    $ic_slide->addCourseId($courseCode);
                    $ic_slide->addToolId(TOOL_LINK);
                    $xapian_data = [
                        SE_COURSE_ID => $courseCode,
                        SE_TOOL_ID => TOOL_LINK,
                        SE_DATA => [
                            'link_id' => (int) $link_id,
                        ],
                        SE_USER => (int) api_get_user_id(),
                    ];
                    $ic_slide->xapian_data = serialize($xapian_data);
                    $description = $all_specific_terms.' '.$description;
                    $ic_slide->addValue('content', $description);

                    // Add category name if set.
                    if (isset($selectcategory) && $selectcategory > 0) {
                        $table_link_category = Database::get_course_table(
                            TABLE_LINK_CATEGORY
                        );
                        $sql_cat = 'SELECT * FROM %s WHERE id=%d AND c_id = %d LIMIT 1';
                        $sql_cat = sprintf(
                            $sql_cat,
                            $table_link_category,
                            (int) $selectcategory,
                            $course_int_id
                        );
                        $result = Database::query($sql_cat);
                        if (Database::num_rows($result) == 1) {
                            $row = Database::fetch_array($result);
                            $ic_slide->addValue(
                                'category',
                                $row['category_title']
                            );
                        }
                    }

                    $di = new ChamiloIndexer();
                    isset($_POST['language']) ? $lang = Database::escape_string(
                        $_POST['language']
                    ) : $lang = 'english';
                    $di->connectDb(null, null, $lang);
                    $di->addChunk($ic_slide);

                    // Index and return search engine document id.
                    $did = $di->index();
                    if ($did) {
                        // Save it to db.
                        $tbl_se_ref = Database::get_main_table(
                            TABLE_MAIN_SEARCH_ENGINE_REF
                        );
                        $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, search_did)
                                VALUES (NULL , \'%s\', \'%s\', %s, %s)';
                        $sql = sprintf(
                            $sql,
                            $tbl_se_ref,
                            $course_int_id,
                            $courseCode,
                            TOOL_LINK,
                            $link_id,
                            $did
                        );
                        Database::query($sql);
                    }
                }
                Display::addFlash(Display::return_message(get_lang('LinkAdded')));

                return $link_id;
            }
        } elseif ($type === 'category') {
            $tbl_categories = Database::get_course_table(TABLE_LINK_CATEGORY);

            $category_title = trim($_POST['category_title']);
            $description = trim($_POST['description']);

            if (empty($category_title)) {
                echo Display::return_message(get_lang('GiveCategoryName'), 'error');
                $ok = false;
            } else {
                // Looking for the largest order number for this category.
                $result = Database::query(
                    "SELECT MAX(display_order) FROM  $tbl_categories
                    WHERE c_id = $course_id "
                );
                list($orderMax) = Database::fetch_row($result);
                $order = $orderMax + 1;
                $order = intval($order);
                $session_id = api_get_session_id();

                $params = [
                    'c_id' => $course_id,
                    'category_title' => $category_title,
                    'description' => $description,
                    'display_order' => $order,
                    'session_id' => $session_id,
                ];
                $linkId = Database::insert($tbl_categories, $params);

                if ($linkId) {
                    // iid
                    $sql = "UPDATE $tbl_categories SET id = iid WHERE iid = $linkId";
                    Database::query($sql);

                    // add link_category visibility
                    // course ID is taken from context in api_set_default_visibility
                    //api_set_default_visibility($linkId, TOOL_LINK_CATEGORY);
                    api_item_property_update(
                        $_course,
                        TOOL_LINK_CATEGORY,
                        $linkId,
                        'LinkCategoryAdded',
                        api_get_user_id()
                    );
                    api_set_default_visibility($linkId, TOOL_LINK_CATEGORY);
                }

                Display::addFlash(Display::return_message(get_lang('CategoryAdded')));

                return $linkId;
            }
        }

        return $ok;
    }

    /**
     * Used to delete a link or a category.
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @param int    $id
     * @param string $type The type of item to delete
     *
     * @return bool
     */
    public static function deletelinkcategory($id, $type)
    {
        $courseInfo = api_get_course_info();
        $tbl_link = Database::get_course_table(TABLE_LINK);
        $tbl_categories = Database::get_course_table(TABLE_LINK_CATEGORY);

        $course_id = $courseInfo['real_id'];
        $id = intval($id);

        if (empty($id)) {
            return false;
        }

        $result = false;
        switch ($type) {
            case 'link':
                // -> Items are no longer physically deleted,
                // but the visibility is set to 2 (in item_property).
                // This will make a restore function possible for the platform administrator.
                $sql = "UPDATE $tbl_link SET on_homepage='0'
                        WHERE c_id = $course_id AND id='".$id."'";
                Database::query($sql);

                api_item_property_update(
                    $courseInfo,
                    TOOL_LINK,
                    $id,
                    'delete',
                    api_get_user_id()
                );
                self::delete_link_from_search_engine(api_get_course_id(), $id);
                Skill::deleteSkillsFromItem($id, ITEM_TYPE_LINK);
                Display::addFlash(Display::return_message(get_lang('LinkDeleted')));
                $result = true;
                break;
            case 'category':
                // First we delete the category itself and afterwards all the links of this category.
                $sql = "DELETE FROM ".$tbl_categories."
                        WHERE c_id = $course_id AND id='".$id."'";
                Database::query($sql);

                $sql = "DELETE FROM ".$tbl_link."
                        WHERE c_id = $course_id AND category_id='".$id."'";
                Database::query($sql);

                api_item_property_update(
                    $courseInfo,
                    TOOL_LINK_CATEGORY,
                    $id,
                    'delete',
                    api_get_user_id()
                );

                Display::addFlash(Display::return_message(get_lang('CategoryDeleted')));
                $result = true;
                break;
        }

        return $result;
    }

    /**
     * Removes a link from search engine database.
     *
     * @param string $course_id Course code
     * @param int    $link_id   Document id to delete
     */
    public static function delete_link_from_search_engine($course_id, $link_id)
    {
        // Remove from search engine if enabled.
        if (api_get_setting('search_enabled') === 'true') {
            $tbl_se_ref = Database::get_main_table(
                TABLE_MAIN_SEARCH_ENGINE_REF
            );
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_LINK, $link_id);
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                $di = new ChamiloIndexer();
                $di->remove_document($row['search_did']);
            }
            $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_LINK, $link_id);
            Database::query($sql);

            // Remove terms from db.
            require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
            delete_all_values_for_item($course_id, TOOL_DOCUMENT, $link_id);
        }
    }

    /**
     * Get link info.
     *
     * @param int $id
     *
     * @return array link info
     */
    public static function getLinkInfo($id)
    {
        $tbl_link = Database::get_course_table(TABLE_LINK);
        $course_id = api_get_course_int_id();

        if (empty($id) || empty($course_id)) {
            return [];
        }

        $sql = "SELECT * FROM $tbl_link
                WHERE c_id = $course_id AND id='".(int) $id."' ";
        $result = Database::query($sql);
        $data = [];
        if (Database::num_rows($result)) {
            $data = Database::fetch_array($result);
        }

        return $data;
    }

    /**
     * @param int   $id
     * @param array $values
     */
    public static function editLink($id, $values = [])
    {
        $tbl_link = Database::get_course_table(TABLE_LINK);
        $_course = api_get_course_info();
        $course_id = $_course['real_id'];
        $id = (int) $id;

        $values['url'] = trim($values['url']);
        $values['title'] = trim($values['title']);
        $values['description'] = trim($values['description']);
        $values['target'] = empty($values['target']) ? '_self' : $values['target'];
        $values['on_homepage'] = isset($values['on_homepage']) ? $values['on_homepage'] : '';

        $categoryId = intval($values['category_id']);

        // We ensure URL to be absolute.
        if (strpos($values['url'], '://') === false) {
            $values['url'] = 'http://'.$_POST['url'];
        }

        // If the title is empty, we use the URL as title.
        if ($values['title'] == '') {
            $values['title'] = $values['url'];
        }

        // If the URL is invalid, an error occurs.
        if (!api_valid_url($values['url'], true)) {
            Display::addFlash(
                Display::return_message(get_lang('GiveURL'), 'error')
            );

            return false;
        }

        if (empty($id) || empty($course_id)) {
            return false;
        }

        // Finding the old category_id.
        $sql = "SELECT * FROM $tbl_link
                WHERE c_id = $course_id AND id='".$id."'";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $category_id = $row['category_id'];

        if ($category_id != $values['category_id']) {
            $sql = "SELECT MAX(display_order)
                    FROM $tbl_link
                    WHERE
                        c_id = $course_id AND
                        category_id='".intval($values['category_id'])."'";
            $result = Database::query($sql);
            list($max_display_order) = Database::fetch_row($result);
            $max_display_order++;
        } else {
            $max_display_order = $row['display_order'];
        }
        $params = [
            'url' => $values['url'],
            'title' => $values['title'],
            'description' => $values['description'],
            'category_id' => $values['category_id'],
            'display_order' => $max_display_order,
            'on_homepage' => $values['on_homepage'],
            'target' => $values['target'],
        ];

        Database::update(
            $tbl_link,
            $params,
            ['c_id = ? AND id = ?' => [$course_id, $id]]
        );

        // Update search enchine and its values table if enabled.
        if (api_get_setting('search_enabled') === 'true') {
            $course_int_id = api_get_course_int_id();
            $course_id = api_get_course_id();
            $link_title = Database::escape_string($values['title']);
            $link_description = Database::escape_string($values['description']);

            // Actually, it consists on delete terms from db,
            // insert new ones, create a new search engine document, and remove the old one.
            // Get search_did.
            $tbl_se_ref = Database::get_main_table(
                TABLE_MAIN_SEARCH_ENGINE_REF
            );
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
            $sql = sprintf(
                $sql,
                $tbl_se_ref,
                $course_id,
                TOOL_LINK,
                $id
            );
            $res = Database::query($sql);

            if (Database::num_rows($res) > 0) {
                require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';

                $se_ref = Database::fetch_array($res);
                $specific_fields = get_specific_field_list();
                $ic_slide = new IndexableChunk();

                $all_specific_terms = '';
                foreach ($specific_fields as $specific_field) {
                    delete_all_specific_field_value(
                        $course_id,
                        $specific_field['id'],
                        TOOL_LINK,
                        $id
                    );
                    if (isset($_REQUEST[$specific_field['code']])) {
                        $sterms = trim(
                            $_REQUEST[$specific_field['code']]
                        );
                        if (!empty($sterms)) {
                            $all_specific_terms .= ' '.$sterms;
                            $sterms = explode(',', $sterms);
                            foreach ($sterms as $sterm) {
                                $ic_slide->addTerm(
                                    trim($sterm),
                                    $specific_field['code']
                                );
                                add_specific_field_value(
                                    $specific_field['id'],
                                    $course_id,
                                    TOOL_LINK,
                                    $id,
                                    $sterm
                                );
                            }
                        }
                    }
                }

                // Build the chunk to index.
                $ic_slide->addValue("title", $link_title);
                $ic_slide->addCourseId($course_id);
                $ic_slide->addToolId(TOOL_LINK);
                $xapian_data = [
                    SE_COURSE_ID => $course_id,
                    SE_TOOL_ID => TOOL_LINK,
                    SE_DATA => [
                        'link_id' => (int) $id,
                    ],
                    SE_USER => (int) api_get_user_id(),
                ];
                $ic_slide->xapian_data = serialize($xapian_data);
                $link_description = $all_specific_terms.' '.$link_description;
                $ic_slide->addValue('content', $link_description);

                // Add category name if set.
                if (isset($categoryId) && $categoryId > 0) {
                    $table_link_category = Database::get_course_table(
                        TABLE_LINK_CATEGORY
                    );
                    $sql_cat = 'SELECT * FROM %s WHERE id=%d and c_id = %d LIMIT 1';
                    $sql_cat = sprintf(
                        $sql_cat,
                        $table_link_category,
                        $categoryId,
                        $course_int_id
                    );
                    $result = Database::query($sql_cat);
                    if (Database::num_rows($result) == 1) {
                        $row = Database::fetch_array($result);
                        $ic_slide->addValue(
                            'category',
                            $row['category_title']
                        );
                    }
                }

                $di = new ChamiloIndexer();
                isset($_POST['language']) ? $lang = Database::escape_string($_POST['language']) : $lang = 'english';
                $di->connectDb(null, null, $lang);
                $di->remove_document($se_ref['search_did']);
                $di->addChunk($ic_slide);

                // Index and return search engine document id.
                $did = $di->index();
                if ($did) {
                    // Save it to db.
                    $sql = 'DELETE FROM %s
                            WHERE course_code=\'%s\'
                            AND tool_id=\'%s\'
                            AND ref_id_high_level=\'%s\'';
                    $sql = sprintf(
                        $sql,
                        $tbl_se_ref,
                        $course_id,
                        TOOL_LINK,
                        $id
                    );
                    Database::query($sql);
                    $sql = 'INSERT INTO %s (c_id, id, course_code, tool_id, ref_id_high_level, search_did)
                            VALUES (NULL , \'%s\', \'%s\', %s, %s)';
                    $sql = sprintf(
                        $sql,
                        $tbl_se_ref,
                        $course_int_id,
                        $course_id,
                        TOOL_LINK,
                        $id,
                        $did
                    );
                    Database::query($sql);
                }
            }
        }

        // "WHAT'S NEW" notification: update table last_toolEdit.
        api_item_property_update(
            $_course,
            TOOL_LINK,
            $id,
            'LinkUpdated',
            api_get_user_id()
        );
        Display::addFlash(Display::return_message(get_lang('LinkModded')));
    }

    /**
     * @param int   $id
     * @param array $values
     *
     * @return bool
     */
    public static function editCategory($id, $values)
    {
        $table = Database::get_course_table(TABLE_LINK_CATEGORY);
        $course_id = api_get_course_int_id();
        $id = intval($id);

        // This is used to put the modified info of the category-form into the database.
        $params = [
            'category_title' => $values['category_title'],
            'description' => $values['description'],
        ];
        Database::update(
            $table,
            $params,
            ['c_id = ? AND id = ?' => [$course_id, $id]]
        );
        Display::addFlash(Display::return_message(get_lang('CategoryModded')));

        return true;
    }

    /**
     * Changes the visibility of a link.
     *
     * @todo add the changing of the visibility of a course
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     */
    public static function change_visibility_link($id, $scope)
    {
        $_course = api_get_course_info();
        $_user = api_get_user_info();
        if ($scope == TOOL_LINK) {
            api_item_property_update(
                $_course,
                TOOL_LINK,
                $id,
                $_GET['action'],
                $_user['user_id']
            );
            Display::addFlash(Display::return_message(get_lang('VisibilityChanged')));
        } elseif ($scope == TOOL_LINK_CATEGORY) {
            api_item_property_update(
                $_course,
                TOOL_LINK_CATEGORY,
                $id,
                $_GET['action'],
                $_user['user_id']
            );
            Display::addFlash(Display::return_message(get_lang('VisibilityChanged')));
        }
    }

    /**
     * Generate SQL to select all the links categories in the current course and
     * session.
     *
     * @param int  $courseId
     * @param int  $sessionId
     * @param bool $withBaseContent
     *
     * @return array
     */
    public static function getLinkCategories($courseId, $sessionId, $withBaseContent = true)
    {
        $tblLinkCategory = Database::get_course_table(TABLE_LINK_CATEGORY);
        $tblItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $courseId = intval($courseId);
        $courseInfo = api_get_course_info_by_id($courseId);

        // Condition for the session.
        $sessionCondition = api_get_session_condition(
            $sessionId,
            true,
            $withBaseContent,
            'linkcat.session_id'
        );

        // Getting links
        $sql = "SELECT *, linkcat.id
                FROM $tblLinkCategory linkcat
                WHERE
                    linkcat.c_id = $courseId
                    $sessionCondition
                ORDER BY linkcat.display_order DESC";

        $result = Database::query($sql);
        $categories = Database::store_result($result);

        $sql = "SELECT *, linkcat.id
                FROM $tblLinkCategory linkcat
                INNER JOIN $tblItemProperty ip
                ON (linkcat.id = ip.ref AND linkcat.c_id = ip.c_id)
                WHERE
                    ip.tool = '".TOOL_LINK_CATEGORY."' AND
                    (ip.visibility = '0' OR ip.visibility = '1')
                    $sessionCondition AND
                    linkcat.c_id = ".$courseId."
                ORDER BY linkcat.display_order DESC";

        $result = Database::query($sql);

        $categoryInItemProperty = [];
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $categoryInItemProperty[$row['id']] = $row;
            }
        }

        foreach ($categories as &$category) {
            if (!isset($categoryInItemProperty[$category['id']])) {
                api_item_property_update(
                    $courseInfo,
                    TOOL_LINK_CATEGORY,
                    $category['id'],
                    'LinkCategoryAdded',
                    api_get_user_id()
                );
            }
        }

        $sql = "SELECT DISTINCT linkcat.*, visibility
                FROM $tblLinkCategory linkcat
                INNER JOIN $tblItemProperty ip
                ON (linkcat.id = ip.ref AND linkcat.c_id = ip.c_id)
                WHERE
                    ip.tool = '".TOOL_LINK_CATEGORY."' AND
                    (ip.visibility = '0' OR ip.visibility = '1')
                    $sessionCondition AND
                    linkcat.c_id = ".$courseId."
                ORDER BY linkcat.display_order DESC
                ";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param int $categoryId
     * @param $courseId
     * @param $sessionId
     * @param bool $withBaseContent
     *
     * @return array
     */
    public static function getLinksPerCategory(
        $categoryId,
        $courseId,
        $sessionId,
        $withBaseContent = true
    ) {
        $tbl_link = Database::get_course_table(TABLE_LINK);
        $TABLE_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;
        $categoryId = (int) $categoryId;

        // Condition for the session.
        $condition_session = api_get_session_condition(
            $sessionId,
            true,
            false,
            'ip.session_id'
        );

        if (!empty($sessionId)) {
            $conditionBaseSession = api_get_session_condition(
                0,
                true,
                $withBaseContent,
                'ip.session_id'
            );
            $condition = " AND
                (
                    (ip.visibility = '1' $conditionBaseSession) OR

                    (
                        (ip.visibility = '0' OR ip.visibility = '1')
                        $condition_session
                    )
                )
            ";
        } else {
            $condition = api_get_session_condition(
                0,
                true,
                false,
                'ip.session_id'
            );
            $condition .= " AND (ip.visibility = '0' OR ip.visibility = '1') $condition ";
        }

        $sql = "SELECT
                    link.id,
                    ip.session_id,
                    link.session_id link_session_id,
                    url,
                    category_id,
                    visibility,
                    description,
                    title,
                    target,
                    on_homepage
                FROM $tbl_link link
                INNER JOIN $TABLE_ITEM_PROPERTY ip
                ON (link.id = ip.ref AND link.c_id = ip.c_id)
                WHERE
                    ip.tool = '".TOOL_LINK."' AND
                    link.category_id = '".$categoryId."' AND
                    link.c_id = $courseId AND
                    ip.c_id = $courseId
                    $condition
                ORDER BY link.display_order ASC, ip.session_id DESC";

        $result = Database::query($sql);

        return Database::store_result($result);
    }

    /**
     * Displays all the links of a given category.
     *
     * @param int  $catid
     * @param int  $courseId
     * @param int  $session_id
     * @param bool $showActionLinks
     *
     * @return string
     *
     * @author Julio Montoya
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     */
    public static function showLinksPerCategory($catid, $courseId, $session_id, $showActionLinks = true)
    {
        global $token;
        $_user = api_get_user_info();
        $catid = (int) $catid;

        $links = self::getLinksPerCategory($catid, $courseId, $session_id);
        $content = '';
        $numberOfLinks = count($links);

        if (!empty($links)) {
            $content .= '<div class="link list-group">';
            $i = 1;
            $linksAdded = [];
            foreach ($links as $myrow) {
                $linkId = $myrow['id'];
                $linkUrl = Security::remove_XSS($myrow['url']);

                if (in_array($linkId, $linksAdded)) {
                    continue;
                }

                $linksAdded[] = $linkId;
                $categoryId = $myrow['category_id'];

                // Validation when belongs to a session.
                $session_img = api_get_session_image($myrow['link_session_id'], $_user['status']);

                $toolbar = '';
                $link_validator = '';
                if (api_is_allowed_to_edit(null, true)) {
                    $toolbar .= Display::toolbarButton(
                        '',
                        'javascript:void(0);',
                        'check-circle-o',
                        'default btn-sm',
                        [
                            'onclick' => "check_url('".$linkId."', '".addslashes($linkUrl)."');",
                            'title' => get_lang('CheckURL'),
                        ]
                    );

                    $link_validator .= Display::span(
                        '',
                        [
                        'id' => 'url_id_'.$linkId,
                        'class' => 'check-link',
                        ]
                    );

                    if ($session_id == $myrow['link_session_id']) {
                        $url = api_get_self().'?'.api_get_cidreq().'&action=editlink&id='.$linkId;
                        $title = get_lang('Edit');
                        $toolbar .= Display::toolbarButton(
                            '',
                            $url,
                            'pencil',
                            'default btn-sm',
                            [
                                'title' => $title,
                            ]
                        );
                    }

                    $urlVisibility = api_get_self().'?'.api_get_cidreq().
                            '&sec_token='.$token.
                            '&id='.$linkId.
                            '&scope=link&category_id='.$categoryId;

                    switch ($myrow['visibility']) {
                        case '1':
                            $urlVisibility .= '&action=invisible';
                            $title = get_lang('MakeInvisible');
                            $toolbar .= Display::toolbarButton(
                                '',
                                $urlVisibility,
                                'eye',
                                'default btn-sm',
                                [
                                    'title' => $title,
                                ]
                            );
                            break;
                        case '0':
                            $urlVisibility .= '&action=visible';
                            $title = get_lang('MakeVisible');
                            $toolbar .= Display::toolbarButton(
                                '',
                                $urlVisibility,
                                'eye-slash',
                                'primary btn-sm',
                                [
                                    'title' => $title,
                                ]
                            );
                            break;
                    }

                    if ($session_id == $myrow['link_session_id']) {
                        $moveLinkParams = [
                            'id' => $linkId,
                            'scope' => 'category',
                            'category_id' => $categoryId,
                            'action' => 'move_link_up',
                        ];

                        $toolbar .= Display::toolbarButton(
                            get_lang('MoveUp'),
                            api_get_self().'?'.api_get_cidreq().'&'.http_build_query($moveLinkParams),
                            'level-up',
                            'default',
                            ['class' => 'btn-sm '.($i === 1 ? 'disabled' : '')],
                            false
                        );

                        $moveLinkParams['action'] = 'move_link_down';
                        $toolbar .= Display::toolbarButton(
                            get_lang('MoveDown'),
                            api_get_self().'?'.api_get_cidreq().'&'.http_build_query($moveLinkParams),
                            'level-down',
                            'default',
                            ['class' => 'btn-sm '.($i === $numberOfLinks ? 'disabled' : '')],
                            false
                        );

                        $url = api_get_self().'?'.api_get_cidreq().'&sec_token='.$token.'&action=deletelink&id='.$linkId.'&category_id='.$categoryId;
                        $event = "javascript: if(!confirm('".get_lang('LinkDelconfirm')."'))return false;";
                        $title = get_lang('Delete');

                        $toolbar .= Display::toolbarButton(
                            '',
                            $url,
                            'trash',
                            'default btn-sm',
                            [
                                'onclick' => $event,
                                'title' => $title,
                            ]
                        );
                    }
                }

                $showLink = false;
                $titleClass = '';
                if ($myrow['visibility'] == '1') {
                    $showLink = true;
                } else {
                    if (api_is_allowed_to_edit(null, true)) {
                        $showLink = true;
                        $titleClass = 'text-muted';
                    }
                }

                if ($showLink) {
                    $iconLink = Display::return_icon(
                        'url.png',
                        get_lang('Link'),
                        null,
                        ICON_SIZE_SMALL
                    );
                    $url = api_get_path(WEB_CODE_PATH).'link/link_goto.php?'.api_get_cidreq().'&link_id='.$linkId;
                    $content .= '<div class="list-group-item">';
                    if ($showActionLinks) {
                        $content .= '<div class="pull-right"><div class="btn-group">'.$toolbar.'</div></div>';
                    }
                    $content .= '<h4 class="list-group-item-heading">';
                    $content .= $iconLink;
                    $content .= Display::tag(
                        'a',
                        Security::remove_XSS($myrow['title']),
                        [
                            'href' => $url,
                            'target' => Security::remove_XSS($myrow['target']),
                            'class' => $titleClass,
                        ]
                    );
                    $content .= $link_validator;
                    $content .= $session_img;
                    $content .= '</h4>';
                    $content .= '<p class="list-group-item-text">'.Security::remove_XSS($myrow['description']).'</p>';
                    $content .= '</div>';
                }
                $i++;
            }
            $content .= '</div>';
        }

        return $content;
    }

    /**
     * Displays the edit, delete and move icons.
     *
     * @param int   Category ID
     * @param int $currentCategory
     * @param int $countCategories
     *
     * @return string
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     */
    public static function showCategoryAdminTools($category, $currentCategory, $countCategories)
    {
        $categoryId = $category['id'];
        $token = null;
        $tools = '<a href="'.api_get_self().'?'.api_get_cidreq().'&sec_token='.$token.'&action=editcategory&id='.$categoryId.'&category_id='.$categoryId.'" title='.get_lang('Modify').'">'.
            Display::return_icon(
                'edit.png',
                get_lang('Modify'),
                [],
                ICON_SIZE_SMALL
            ).'</a>';

        // DISPLAY MOVE UP COMMAND only if it is not the top link.
        if ($currentCategory != 0) {
            $tools .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&sec_token='.$token.'&action=up&up='.$categoryId.'&category_id='.$categoryId.'" title="'.get_lang('Up').'">'.
                Display::return_icon(
                    'up.png',
                    get_lang('Up'),
                    [],
                    ICON_SIZE_SMALL
                ).'</a>';
        } else {
            $tools .= Display::return_icon(
                'up_na.png',
                get_lang('Up'),
                [],
                ICON_SIZE_SMALL
            ).'</a>';
        }

        // DISPLAY MOVE DOWN COMMAND only if it is not the bottom link.
        if ($currentCategory < $countCategories - 1) {
            $tools .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&sec_token='.$token.'&action=down&down='.$categoryId.'&category_id='.$categoryId.'">'.
                Display::return_icon(
                    'down.png',
                    get_lang('Down'),
                    [],
                    ICON_SIZE_SMALL
                ).'</a>';
        } else {
            $tools .= Display::return_icon(
                'down_na.png',
                get_lang('Down'),
                [],
                ICON_SIZE_SMALL
            ).'</a>';
        }

        $tools .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&sec_token='.$token.'&action=deletecategory&id='.$categoryId."&category_id=$categoryId\"
            onclick=\"javascript: if(!confirm('".get_lang('CategoryDelconfirm')."')) return false;\">".
            Display::return_icon(
                'delete.png',
                get_lang('Delete'),
                [],
                ICON_SIZE_SMALL
            ).'</a>';

        return $tools;
    }

    /**
     * move a link or a linkcategory up or down.
     *
     * @param   int Category ID
     * @param   int Course ID
     * @param   int Session ID
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @todo support sessions
     */
    public static function movecatlink($action, $catlinkid, $courseId = null, $sessionId = null)
    {
        $tbl_categories = Database::get_course_table(TABLE_LINK_CATEGORY);

        if (is_null($courseId)) {
            $courseId = api_get_course_int_id();
        }
        $courseId = intval($courseId);
        if (is_null($sessionId)) {
            $sessionId = api_get_session_id();
        }
        $sessionId = intval($sessionId);
        $thiscatlinkId = intval($catlinkid);

        if ($action === 'down') {
            $sortDirection = 'DESC';
        }

        if ($action === 'up') {
            $sortDirection = 'ASC';
        }

        $movetable = $tbl_categories;

        if (!empty($sortDirection)) {
            if (!in_array(trim(strtoupper($sortDirection)), ['ASC', 'DESC'])) {
                $sortDirection = 'ASC';
            }

            $sql = "SELECT id, display_order FROM $movetable
                    WHERE c_id = $courseId
                    ORDER BY display_order $sortDirection";
            $linkresult = Database::query($sql);
            $thislinkOrder = 1;
            while ($sortrow = Database::fetch_array($linkresult)) {
                // STEP 2 : FOUND THE NEXT LINK ID AND ORDER, COMMIT SWAP
                // This part seems unlogic, but it isn't . We first look for the current link with the querystring ID
                // and we know the next iteration of the while loop is the next one. These should be swapped.
                if (isset($thislinkFound) && $thislinkFound) {
                    $nextlinkId = $sortrow['id'];
                    $nextlinkOrder = $sortrow['display_order'];

                    Database::query(
                        "UPDATE ".$movetable."
                        SET display_order = '$nextlinkOrder'
                        WHERE c_id = $courseId  AND id =  '$thiscatlinkId'"
                    );
                    Database::query(
                        "UPDATE ".$movetable."
                        SET display_order = '$thislinkOrder'
                        WHERE c_id = $courseId  AND id =  '$nextlinkId'"
                    );

                    break;
                }
                if ($sortrow['id'] == $thiscatlinkId) {
                    $thislinkOrder = $sortrow['display_order'];
                    $thislinkFound = true;
                }
            }
        }

        Display::addFlash(Display::return_message(get_lang('LinkMoved')));
    }

    /**
     * This function checks if the url is a vimeo link.
     *
     * @author Julio Montoya
     *
     * @version 1.0
     */
    public static function isVimeoLink($url)
    {
        $isLink = strrpos($url, 'vimeo.com');

        return $isLink;
    }

    /**
     * Get vimeo id from URL.
     *
     * @param string $url
     *
     * @return bool|mixed
     */
    public static function getVimeoLinkId($url)
    {
        $possibleUrls = [
            'http://www.vimeo.com/',
            'http://vimeo.com/',
            'https://www.vimeo.com/',
            'https://vimeo.com/',
        ];
        $url = str_replace($possibleUrls, '', $url);

        if (is_numeric($url)) {
            return $url;
        }

        return false;
    }

    /**
     * This function checks if the url is a youtube link.
     *
     * @author Jorge Frisancho
     * @author Julio Montoya - Fixing code
     *
     * @version 1.0
     */
    public static function is_youtube_link($url)
    {
        $is_youtube_link = strrpos($url, 'youtube') || strrpos(
            $url,
            'youtu.be'
        );

        return $is_youtube_link;
    }

    /**
     * This function checks if the url is a PDF File link.
     *
     * @author Jorge Frisancho
     * @author Alex Aragón - Fixing code
     *
     * @version 1.0
     */
    public static function isPdfLink($url)
    {
        $isPdfLink = strrpos(strtolower($url), '.pdf');

        return $isPdfLink;
    }

    /**
     * Get youtube id from an URL.
     *
     * @param string $url
     *
     * @return string
     */
    public static function get_youtube_video_id($url)
    {
        // This is the length of YouTube's video IDs
        $len = 11;

        // The ID string starts after "v=", which is usually right after
        // "youtube.com/watch?" in the URL
        $pos = strpos($url, "v=");
        $id = '';

        //If false try other options
        if ($pos === false) {
            $url_parsed = parse_url($url);

            //Youtube shortener
            //http://youtu.be/ID
            $pos = strpos($url, "youtu.be");

            if ($pos == false) {
                $id = '';
            } else {
                return substr($url_parsed['path'], 1);
            }

            //if empty try the youtube.com/embed/ID
            if (empty($id)) {
                $pos = strpos($url, "embed");
                if ($pos === false) {
                    return '';
                } else {
                    return substr($url_parsed['path'], 7);
                }
            }
        } else {
            // Offset the start location to match the beginning of the ID string
            $pos += 2;
            // Get the ID string and return it
            $id = substr($url, $pos, $len);

            return $id;
        }
    }

    /**
     * @param int    $course_id
     * @param int    $session_id
     * @param int    $categoryId
     * @param string $show
     * @param null   $token
     * @param bool   $showActionLinks
     * @param bool   $forceOpenCategories
     *
     * @return string
     */
    public static function listLinksAndCategories(
        $course_id,
        $session_id,
        $categoryId,
        $show = 'none',
        $token = null,
        $showActionLinks = true,
        $forceOpenCategories = false
    ) {
        $categoryId = (int) $categoryId;
        $content = '';
        $categories = self::getLinkCategories($course_id, $session_id);
        $countCategories = count($categories);
        $linksPerCategory = self::showLinksPerCategory(0, $course_id, $session_id, $showActionLinks);

        if ($showActionLinks) {
            /*	Action Links */
            $content = '<div class="actions">';
            if (api_is_allowed_to_edit(null, true)) {
                $content .= '<a href="'.api_get_self().'?'.api_get_cidreq(
                    ).'&action=addlink&category_id='.$categoryId.'">'.
                    Display::return_icon('new_link.png', get_lang('LinkAdd'), '', ICON_SIZE_MEDIUM).'</a>';
                $content .= '<a href="'.api_get_self().'?'.api_get_cidreq(
                    ).'&action=addcategory&category_id='.$categoryId.'">'.
                    Display::return_icon('new_folder.png', get_lang('CategoryAdd'), '', ICON_SIZE_MEDIUM).'</a>';
            }

            if (!empty($countCategories)) {
                $content .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=list&show=none">';
                $content .= Display::return_icon(
                        'forum_listview.png',
                        get_lang('FlatView'),
                        '',
                        ICON_SIZE_MEDIUM
                    ).' </a>';

                $content .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=list&show=all">';
                $content .= Display::return_icon(
                        'forum_nestedview.png',
                        get_lang('NestedView'),
                        '',
                        ICON_SIZE_MEDIUM
                    ).'</a>';
            }

            $content .= Display::url(
                Display::return_icon('pdf.png', get_lang('ExportToPdf'), '', ICON_SIZE_MEDIUM),
                api_get_self().'?'.api_get_cidreq().'&action=export'
            );
            $content .= '</div>';
        }

        if (empty($countCategories)) {
            $content .= $linksPerCategory;
        } else {
            if (!empty($linksPerCategory)) {
                $content .= Display::panel($linksPerCategory, get_lang('NoCategory'));
            }
        }

        $counter = 0;
        foreach ($categories as $myrow) {
            // Student don't see invisible categories.
            if (!api_is_allowed_to_edit(null, true)) {
                if ($myrow['visibility'] == 0) {
                    continue;
                }
            }

            // Validation when belongs to a session
            $showChildren = $categoryId == $myrow['id'] || $show === 'all';
            if ($forceOpenCategories) {
                $showChildren = true;
            }

            $strVisibility = '';
            $visibilityClass = null;
            if ($myrow['visibility'] == '1') {
                $strVisibility = '<a href="link.php?'.api_get_cidreq().'&sec_token='.$token.'&action=invisible&id='.$myrow['id'].'&scope='.TOOL_LINK_CATEGORY.'" title="'.get_lang('Hide').'">'.
                    Display::return_icon('visible.png', get_lang('Hide'), [], ICON_SIZE_SMALL).'</a>';
            } elseif ($myrow['visibility'] == '0') {
                $visibilityClass = 'text-muted';
                $strVisibility = ' <a href="link.php?'.api_get_cidreq().'&sec_token='.$token.'&action=visible&id='.$myrow['id'].'&scope='.TOOL_LINK_CATEGORY.'" title="'.get_lang('Show').'">'.
                    Display::return_icon('invisible.png', get_lang('Show'), [], ICON_SIZE_SMALL).'</a>';
            }

            $header = '';
            if ($showChildren) {
                $header .= '<a class="'.$visibilityClass.'" href="'.api_get_self().'?'.api_get_cidreq().'&category_id=">';
                $header .= Display::return_icon('forum_nestedview.png');
            } else {
                $header .= '<a class="'.$visibilityClass.'" href="'.api_get_self().'?'.api_get_cidreq().'&category_id='.$myrow['id'].'">';
                $header .= Display::return_icon('forum_listview.png');
            }
            $header .= Security::remove_XSS($myrow['category_title']).'</a>';

            if ($showActionLinks) {
                if (api_is_allowed_to_edit(null, true)) {
                    if ($session_id == $myrow['session_id']) {
                        $header .= $strVisibility;
                        $header .= self::showCategoryAdminTools($myrow, $counter, count($categories));
                    } else {
                        $header .= get_lang('EditionNotAvailableFromSession');
                    }
                }
            }

            $childrenContent = '';
            if ($showChildren) {
                $childrenContent = self::showLinksPerCategory(
                    $myrow['id'],
                    $course_id,
                    $session_id,
                    $showActionLinks
                );
            }

            $content .= Display::panel(Security::remove_XSS($myrow['description']).$childrenContent, $header);
            $counter++;
        }

        return $content;
    }

    /**
     * @param int    $linkId
     * @param string $action
     * @param null   $token
     *
     * @return FormValidator
     */
    public static function getLinkForm($linkId, $action, $token = null)
    {
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();
        $linkInfo = self::getLinkInfo($linkId);
        $categoryId = isset($linkInfo['category_id']) ? $linkInfo['category_id'] : '';
        $lpId = isset($_GET['lp_id']) ? Security::remove_XSS($_GET['lp_id']) : null;

        $form = new FormValidator(
            'link',
            'post',
            api_get_self().'?action='.$action.
            '&category_id='.$categoryId.
            '&'.api_get_cidreq().
            '&id='.$linkId.
            '&sec_token='.$token
        );

        if ($action === 'addlink') {
            $form->addHeader(get_lang('LinkAdd'));
        } else {
            $form->addHeader(get_lang('LinkMod'));
        }

        $target_link = '_blank';
        $title = '';
        $category = '';
        $onhomepage = '';
        $description = '';
        if (!empty($linkInfo)) {
            $urllink = $linkInfo['url'];
            $title = $linkInfo['title'];
            $description = $linkInfo['description'];
            $category = $linkInfo['category_id'];
            if ($linkInfo['on_homepage'] != 0) {
                $onhomepage = 1;
            }
            $target_link = $linkInfo['target'];
        }

        $form->addHidden('id', $linkId);
        $form->addText('url', 'URL');
        $form->addRule('url', get_lang('GiveURL'), 'url');
        $form->addText('title', get_lang('LinkName'));
        $form->addTextarea('description', get_lang('Description'));

        $resultcategories = self::getLinkCategories($course_id, $session_id);
        $options = ['0' => '--'];
        if (!empty($resultcategories)) {
            foreach ($resultcategories as $myrow) {
                $options[$myrow['id']] = $myrow['category_title'];
            }
        }

        $form->addSelect('category_id', get_lang('Category'), $options);
        $form->addCheckBox('on_homepage', null, get_lang('OnHomepage'));

        $targets = [
            '_self' => get_lang('LinkOpenSelf'),
            '_blank' => get_lang('LinkOpenBlank'),
            '_parent' => get_lang('LinkOpenParent'),
            '_top' => get_lang('LinkOpenTop'),
        ];

        $form->addSelect(
            'target',
            [
                get_lang('LinkTarget'),
                get_lang('AddTargetOfLinkOnHomepage'),
            ],
            $targets
        );

        $defaults = [
            'url' => empty($urllink) ? 'http://' : str_replace('&amp;', '&', Security::remove_XSS($urllink)),
            'title' => Security::remove_XSS($title),
            'category_id' => $category,
            'on_homepage' => $onhomepage,
            'description' => Security::remove_XSS($description),
            'target' => $target_link,
        ];

        if (api_get_setting('search_enabled') === 'true') {
            require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
            $specific_fields = get_specific_field_list();
            $form->addCheckBox('index_document', get_lang('SearchFeatureDoIndexLink'), get_lang('Yes'));

            foreach ($specific_fields as $specific_field) {
                $default_values = '';
                if ($action === 'editlink') {
                    $filter = [
                        'field_id' => $specific_field['id'],
                        'ref_id' => intval($_GET['id']),
                        'tool_id' => '\''.TOOL_LINK.'\'',
                    ];
                    $values = get_specific_field_values_list($filter, ['value']);
                    if (!empty($values)) {
                        $arr_str_values = [];
                        foreach ($values as $value) {
                            $arr_str_values[] = $value['value'];
                        }
                        $default_values = implode(', ', $arr_str_values);
                    }
                }
                $form->addText($specific_field['name'], $specific_field['code']);
                $defaults[$specific_field['name']] = $default_values;
            }
        }

        Skill::addSkillsToForm($form, api_get_course_int_id(), api_get_session_id(), ITEM_TYPE_LINK, $linkId);
        $form->addHidden('lp_id', $lpId);
        $form->addButtonSave(get_lang('SaveLink'), 'submitLink');
        $form->setDefaults($defaults);

        return $form;
    }

    /**
     * @param int    $id
     * @param string $action
     *
     * @return FormValidator
     */
    public static function getCategoryForm($id, $action)
    {
        $id = (int) $id;
        $action = Security::remove_XSS($action);

        $form = new FormValidator(
            'category',
            'post',
            api_get_self().'?action='.$action.'&'.api_get_cidreq()
        );

        $defaults = [];
        if ($action == 'addcategory') {
            $form->addHeader(get_lang('CategoryAdd'));
            $my_cat_title = get_lang('CategoryAdd');
        } else {
            $form->addHeader(get_lang('CategoryMod'));
            $my_cat_title = get_lang('CategoryMod');
            $defaults = self::getCategory($id);
        }
        $form->addHidden('id', $id);
        $form->addText('category_title', get_lang('CategoryName'));
        $form->addTextarea('description', get_lang('Description'));
        $form->addButtonSave($my_cat_title, 'submitCategory');
        $form->setDefaults($defaults);

        return $form;
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public static function getCategory($id)
    {
        $table = Database::get_course_table(TABLE_LINK_CATEGORY);
        $id = (int) $id;
        $courseId = api_get_course_int_id();

        if (empty($id) || empty($courseId)) {
            return [];
        }
        $sql = "SELECT * FROM $table
                WHERE id = $id AND c_id = $courseId";
        $result = Database::query($sql);
        $category = Database::fetch_array($result, 'ASSOC');

        return $category;
    }

    /**
     * It gets the category by a specific name.
     *
     * @param string $name
     *
     * @return array
     */
    public static function getCategoryByName($name)
    {
        $table = Database::get_course_table(TABLE_LINK_CATEGORY);
        $courseId = api_get_course_int_id();
        $name = Database::escape_string($name);

        $sql = "SELECT * FROM $table
                WHERE category_title = '$name' AND c_id = $courseId";
        $result = Database::query($sql);
        $category = Database::fetch_array($result, 'ASSOC');

        return $category;
    }

    /**
     * Move a link up in its category.
     *
     * @param int $id
     *
     * @return bool
     */
    public static function moveLinkUp($id)
    {
        return self::moveLinkDisplayOrder($id, 'ASC');
    }

    /**
     * Move a link down in its category.
     *
     * @param int $id
     *
     * @return bool
     */
    public static function moveLinkDown($id)
    {
        return self::moveLinkDisplayOrder($id, 'DESC');
    }

    public static function checkUrl(string $url): bool
    {
        $defaults = [
            'allow_redirects' => [
                'max' => 5, // max number of redirects allowed
                'strict' => true, // whether to use strict redirects or not
                'referer' => true, // whether to add the Referer header when redirecting
                'protocols' => ['http', 'https'], // protocols allowed to be redirected to
                'track_redirects' => true, // whether to keep track of the number of redirects
            ],
            'connect_timeout' => 4,
            'timeout' => 4,
        ];

        $proxySettings = api_get_configuration_value('proxy_settings');

        if (!empty($proxySettings) &&
            isset($proxySettings['curl_setopt_array'])
        ) {
            $defaults['proxy'] = sprintf(
                '%s:%s',
                $proxySettings['curl_setopt_array']['CURLOPT_PROXY'],
                $proxySettings['curl_setopt_array']['CURLOPT_PROXYPORT']
            );
        }

        $client = new Client(['defaults' => $defaults]);

        try {
            $response = $client->get($url);

            if (200 !== $response->getStatusCode()) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Move a link inside its category (display_order field).
     *
     * @param int    $id        The link ID
     * @param string $direction The direction to sort the links
     *
     * @return bool
     */
    private static function moveLinkDisplayOrder($id, $direction)
    {
        $em = Database::getManager();
        /** @var CLink $link */
        $link = $em->find('ChamiloCourseBundle:CLink', $id);

        if (!$link) {
            return false;
        }

        $compareLinks = $em
            ->getRepository('ChamiloCourseBundle:CLink')
            ->findBy(
                [
                    'cId' => $link->getCId(),
                    'categoryId' => $link->getCategoryId(),
                ],
                ['displayOrder' => $direction]
            );

        /** @var CLink $prevLink */
        $prevLink = null;

        /** @var CLink $compareLink */
        foreach ($compareLinks as $compareLink) {
            if ($compareLink->getId() !== $link->getId()) {
                $prevLink = $compareLink;

                continue;
            }

            if (!$prevLink) {
                return false;
            }

            $newPrevLinkDisplayOrder = $link->getDisplayOrder();
            $newLinkDisplayOrder = $prevLink->getDisplayOrder();

            $link->setDisplayOrder($newLinkDisplayOrder);
            $prevLink->setDisplayOrder($newPrevLinkDisplayOrder);

            $em->merge($prevLink);
            $em->merge($link);
            break;
        }

        $em->flush();

        return true;
    }
}
