<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;

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
 * @author Patrick Cool (December 2003 - January 2004)
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
    public array $required = ['url', 'title'];
    private $course;

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
     * @param bool  $showQuery Whether to show the query in logs when
     *                         calling parent's save method
     *
     * @return bool True if link could be saved, false otherwise
     */
    public function save($params, $showQuery = null, $showFlash = true)
    {
        $course_info = $this->getCourse();
        $course_id = $course_info['real_id'];
        $session_id = api_get_session_id();

        $title = stripslashes($params['title']);
        $urllink = $params['url'];
        $description = $params['description'];
        $categoryId = (int) $params['category_id'];

        $onhomepage = 0;
        if (isset($params['on_homepage'])) {
            $onhomepage = Security::remove_XSS($params['on_homepage']);
        }

        $target = '_self'; // Default target.
        if (!empty($params['target'])) {
            $target = Security::remove_XSS($params['target']);
        }

        $urllink = trim($urllink);
        $title = trim($title);
        $description = trim($description);

        // We ensure URL to be absolute.
        if (false === strpos($urllink, '://')) {
            $urllink = 'http://'.$urllink;
        }

        // If the title is empty, we use the URL as title.
        if ('' == $title) {
            $title = $urllink;
        }

        // If the URL is invalid, an error occurs.
        if (!api_valid_url($urllink, true)) {
            // A check against an absolute URL
            Display::addFlash(
                Display::return_message(get_lang('Please give the link URL, it should be valid.'), 'error')
            );

            return false;
        } else {
            $category = null;
            $repoCategory = Container::getLinkCategoryRepository();
            if (!empty($categoryId)) {
                /** @var CLinkCategory $category */
                $category = $repoCategory->find($categoryId);
            }

            // Looking for the largest order number for this category.
            $link = new CLink();
            $link
                ->setUrl($urllink)
                ->setTitle($title)
                ->setDescription($description)
                ->setTarget($target)
                ->setCategory($category)
            ;

            $repo = Container::getLinkRepository();
            $courseEntity = api_get_course_entity($course_id);
            if (empty($category)) {
                $link
                    ->setParent($courseEntity)
                    ->addCourseLink($courseEntity, api_get_session_entity($session_id))
                ;
            } else {
                $link
                    ->setParent($category)
                    ->addCourseLink($courseEntity, api_get_session_entity($session_id))
                ;
            }

            $repo->create($link);
            $link_id = $link->getIid();

            if (('true' === api_get_setting('search_enabled')) &&
                $link_id && extension_loaded('xapian')
            ) {
                $courseCode = $course_info['code'];
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
                        'link_id' => $link_id,
                    ],
                    SE_USER => (int) api_get_user_id(),
                ];
                $ic_slide->xapian_data = serialize($xapian_data);
                $description = $all_specific_terms.' '.$description;
                $ic_slide->addValue('content', $description);

                // Add category name if set.
                if (isset($categoryId) && $categoryId > 0) {
                    $table_link_category = Database::get_course_table(
                        TABLE_LINK_CATEGORY
                    );
                    $sql_cat = 'SELECT * FROM %s WHERE id=%d AND c_id = %d LIMIT 1';
                    $sql_cat = sprintf(
                        $sql_cat,
                        $table_link_category,
                        $categoryId,
                        $course_id
                    );
                    $result = Database:: query($sql_cat);
                    if (1 == Database:: num_rows($result)) {
                        $row = Database:: fetch_array($result);
                        $ic_slide->addValue(
                            'category',
                            $row['category_title']
                        );
                    }
                }

                $di = new ChamiloIndexer();
                isset($params['language']) ? $lang = Database:: escape_string(
                    $params['language']
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
                        $course_id,
                        $courseCode,
                        TOOL_LINK,
                        $link_id,
                        $did
                    );
                    Database:: query($sql);
                }
            }
            if ($showFlash) {
                Display::addFlash(Display::return_message(get_lang('The link has been added.')));
            }

            return $link_id;
        }
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
        $linkId = (int) $linkId;
        if ('' != $linkUrl) {
            $sql = "UPDATE $tblLink SET
                    url = '$linkUrl'
                    WHERE iid = $linkId ";
            $resLink = Database::query($sql);

            return $resLink;
        }

        return false;
    }

    public static function addCategory()
    {
        $_course = api_get_course_info();
        $course_id = $_course['real_id'];
        $category_title = trim($_POST['category_title']);
        $description = trim($_POST['description']);

        if (empty($category_title)) {
            echo Display::return_message(get_lang('Please give the category name'), 'error');

            return false;
        }

        // Looking for the largest order number for this category.
        /*$result = Database:: query(
            "SELECT MAX(display_order) FROM  $tbl_categories
            WHERE c_id = $course_id "
        );
        [$orderMax] = Database:: fetch_row($result);
        $order = $orderMax + 1;
        $order = (int) $order;*/
        $session_id = api_get_session_id();

        $repo = Container::getLinkCategoryRepository();
        $courseEntity = api_get_course_entity($course_id);
        $sessionEntity = api_get_session_entity($session_id);

        $category = (new CLinkCategory())
            ->setCategoryTitle($category_title)
            ->setDescription($description)
         //   ->setDisplayOrder($order)
            ->setParent($courseEntity)
            ->addCourseLink($courseEntity, $sessionEntity)
        ;

        $repo->create($category);
        $linkId = $category->getIid();

        Display::addFlash(Display::return_message(get_lang('Category added')));

        return $linkId;
    }

    public static function deleteCategory($id)
    {
        $repo = Container::getLinkCategoryRepository();
        /** @var CLinkCategory $category */
        $category = $repo->find($id);
        if ($category) {
            $repo->delete($category);
            Display::addFlash(Display::return_message(get_lang('The category has been deleted.')));

            return true;
        }

        return false;
    }

    /**
     * Used to delete a link.
     *
     * @param int $id
     *
     * @return bool
     */
    public static function deleteLink($id)
    {
        $repo = Container::getLinkRepository();
        $link = $repo->find($id);
        if ($link) {
            $repo->delete($link);
            self::delete_link_from_search_engine(api_get_course_id(), $id);
            SkillModel::deleteSkillsFromItem($id, ITEM_TYPE_LINK);
            Display::addFlash(Display::return_message(get_lang('The link has been deleted')));

            return true;
        }

        return false;
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
        if ('true' === api_get_setting('search_enabled')) {
            $tbl_se_ref = Database::get_main_table(
                TABLE_MAIN_SEARCH_ENGINE_REF
            );
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_LINK, $link_id);
            $res = Database:: query($sql);
            if (Database:: num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                $di = new ChamiloIndexer();
                $di->remove_document($row['search_did']);
            }
            $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_LINK, $link_id);
            Database:: query($sql);

            // Remove terms from db.
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
        $id = (int) $id;

        if (empty($id) || empty($course_id)) {
            return [];
        }

        $sql = "SELECT * FROM $tbl_link
                WHERE iid= $id ";
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
        $_course = api_get_course_info();
        $course_id = $_course['real_id'];
        $id = (int) $id;

        $values['url'] = trim($values['url']);
        $values['title'] = trim($values['title']);
        $values['description'] = trim($values['description']);
        $values['target'] = empty($values['target']) ? '_self' : $values['target'];
        $values['on_homepage'] = isset($values['on_homepage']) ? $values['on_homepage'] : '';

        $categoryId = (int) $values['category_id'];

        // We ensure URL to be absolute.
        if (false === strpos($values['url'], '://')) {
            $values['url'] = 'http://'.$_POST['url'];
        }

        // If the title is empty, we use the URL as title.
        if ('' == $values['title']) {
            $values['title'] = $values['url'];
        }

        // If the URL is invalid, an error occurs.
        if (!api_valid_url($values['url'], true)) {
            Display::addFlash(
                Display::return_message(get_lang('Please give the link URL, it should be valid.'), 'error')
            );

            return false;
        }

        if (empty($id) || empty($course_id)) {
            return false;
        }

        $repo = Container::getLinkRepository();
        /** @var CLink $link */
        $link = $repo->find($id);

        if (null === $link) {
            return false;
        }

        /*if ($link->getCategory() != $values['category_id']) {
            $sql = "SELECT MAX(display_order)
                    FROM $tbl_link
                    WHERE
                        category_id='".intval($values['category_id'])."'";
            $result = Database:: query($sql);
            [$max_display_order] = Database:: fetch_row($result);
            $max_display_order++;
        } else {
            $max_display_order = $row['display_order'];
        }*/

        $link
            ->setUrl($values['url'])
            ->setTitle($values['title'])
            ->setDescription($values['description'])
            ->setTarget($values['target'])
        ;

        if (!empty($values['category_id'])) {
            $repoCategory = Container::getLinkCategoryRepository();
            /** @var CLinkCategory $category */
            $category = $repoCategory->find($categoryId);
            $link->setCategory($category);
        }

        $repo->update($link);

        // Update search enchine and its values table if enabled.
        if ('true' === api_get_setting('search_enabled')) {
            $course_int_id = api_get_course_int_id();
            $course_id = api_get_course_id();
            $link_title = Database:: escape_string($values['title']);
            $link_description = Database:: escape_string($values['description']);

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
            $res = Database:: query($sql);

            if (Database:: num_rows($res) > 0) {
                $se_ref = Database:: fetch_array($res);
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
                        'link_id' => $id,
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
                    $result = Database:: query($sql_cat);
                    if (1 == Database:: num_rows($result)) {
                        $row = Database:: fetch_array($result);
                        $ic_slide->addValue(
                            'category',
                            $row['category_title']
                        );
                    }
                }

                $di = new ChamiloIndexer();
                isset($_POST['language']) ? $lang = Database:: escape_string($_POST['language']) : $lang = 'english';
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
                    Database:: query($sql);
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
                    Database:: query($sql);
                }
            }
        }

        Display::addFlash(Display::return_message(get_lang('The link has been modified.')));
    }

    /**
     * @param int   $id
     * @param array $values
     *
     * @return bool
     */
    public static function editCategory($id, $values)
    {
        $repo = Container::getLinkCategoryRepository();
        /** @var CLinkCategory $category */
        $category = $repo->find($id);
        $category
            ->setCategoryTitle($values['category_title'])
            ->setDescription($values['description'])
        ;

        $repo->update($category);

        Display::addFlash(Display::return_message(get_lang('The category has been modified.')));

        return true;
    }

    /**
     * Changes the visibility of a link.
     */
    public static function setVisible($id, $scope)
    {
        if (TOOL_LINK == $scope) {
            /*api_item_property_update(
                $_course,
                TOOL_LINK,
                $id,
                $_GET['action'],
                $_user['user_id']
            );*/
            $repo = Container::getLinkRepository();
            /** @var CLink $link */
            $link = $repo->find($id);
            if ($link) {
                $repo->setVisibilityPublished($link);
            }
        } elseif (TOOL_LINK_CATEGORY == $scope) {
            $repo = Container::getLinkCategoryRepository();
            /** @var CLink $link */
            $link = $repo->find($id);
            if ($link) {
                $repo->setVisibilityPublished($link);
            }
            /*api_item_property_update(
                $_course,
                TOOL_LINK_CATEGORY,
                $id,
                $_GET['action'],
                $_user['user_id']
            );*/
        }
        Display::addFlash(Display::return_message(get_lang('The visibility has been changed.')));
    }

    public static function setInvisible($id, $scope)
    {
        if (TOOL_LINK == $scope) {
            $repo = Container::getLinkRepository();
            /** @var CLink $link */
            $link = $repo->find($id);
            if ($link) {
                $repo->setVisibilityDraft($link);
            }
        } elseif (TOOL_LINK_CATEGORY == $scope) {
            $repo = Container::getLinkCategoryRepository();
            /** @var CLinkCategory $link */
            $link = $repo->find($id);
            if ($link) {
                $repo->setVisibilityDraft($link);
            }
        }
        Display::addFlash(Display::return_message(get_lang('The visibility has been changed.')));
    }

    /**
     * Generate SQL to select all the links categories in the current course and
     * session.
     *
     * @param int  $courseId
     * @param int  $sessionId
     * @param bool $withBaseContent
     *
     * @return CLinkCategory[]
     */
    public static function getLinkCategories($courseId, $sessionId, $withBaseContent = true)
    {
        $repo = Container::getLinkCategoryRepository();

        $courseEntity = api_get_course_entity($courseId);
        $sessionEntity = api_get_session_entity($sessionId);

        $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);

        return $qb->getQuery()->getResult();
        /*
        $tblLinkCategory = Database::get_course_table(TABLE_LINK_CATEGORY);
        $tblItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $courseId = (int) $courseId;
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

        return Database::store_result($result, 'ASSOC');*/
    }

    /**
     * @param int $categoryId
     * @param $courseId
     * @param $sessionId
     * @param bool $withBaseContent
     *
     * @return CLink[]|null
     */
    public static function getLinksPerCategory(
        $categoryId,
        $courseId,
        $sessionId,
        $withBaseContent = true
    ) {
        $courseEntity = api_get_course_entity($courseId);
        $sessionEntity = api_get_session_entity($sessionId);

        if (empty($categoryId)) {
            $repo = Container::getLinkRepository();
            $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity, null, $courseEntity->getResourceNode());

            return $qb->getQuery()->getResult();
        }

        $repo = Container::getLinkCategoryRepository();
        /** @var CLinkCategory $category */
        $category = $repo->find($categoryId);
        if ($category) {
            $repo = Container::getLinkRepository();
            $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity, null, $category->getResourceNode());

            return $qb->getQuery()->getResult();
        }

        return null;

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

        $result = Database:: query($sql);

        return Database::store_result($result);
    }

    /**
     * Displays all the links of a given category.
     *
     * @param int  $categoryId
     * @param int  $courseId
     * @param int  $sessionId
     * @param bool $showActionLinks
     *
     * @return string
     */
    public static function showLinksPerCategory($categoryId, $courseId, $sessionId, $showActionLinks = true)
    {
        global $token;
        $links = self::getLinksPerCategory($categoryId, $courseId, $sessionId);
        $content = '';
        $numberOfLinks = count($links);
        if (!empty($links)) {
            $courseEntity = api_get_course_entity($courseId);
            $sessionEntity = api_get_session_entity($sessionId);
            $user = api_get_user_entity();
            $i = 1;
            $linksAdded = [];
            $iconLink = Display::return_icon(
                'url.png',
                get_lang('Link'),
                null,
                ICON_SIZE_SMALL
            );
            foreach ($links as $link) {
                $linkId = $link->getIid();
                $resourceLink = $link->getFirstResourceLink();

                if (in_array($linkId, $linksAdded)) {
                    continue;
                }

                $visibility = (int) $link->isVisible($courseEntity, $sessionEntity);

                $linksAdded[] = $linkId;
                $categoryId = 0;
                if ($link->getCategory()) {
                    $categoryId = $link->getCategory()->getIid();
                }

                // Validation when belongs to a session.
                $session_img = '';
                $session = $resourceLink->getSession();
                if ($session) {
                    $session_img = api_get_session_image($session->getId(), $user);
                }

                $toolbar = '';
                $link_validator = '';
                if (api_is_allowed_to_edit(null, true)) {
                    $toolbar .= Display::toolbarButton(
                        '',
                        'javascript:void(0);',
                        'check-circle',
                        'secondary btn-sm',
                        [
                            'onclick' => "check_url('".$linkId."', '".addslashes($link->getUrl())."');",
                            'title' => get_lang('Check link'),
                        ]
                    );

                    $link_validator .= Display::span(
                        '',
                        [
                        'id' => 'url_id_'.$linkId,
                        'class' => 'check-link',
                        ]
                    );

                    if ($session === $sessionEntity) {
                        $url = api_get_self().'?'.api_get_cidreq().'&action=editlink&id='.$linkId;
                        $title = get_lang('Edit');
                        $toolbar .= Display::toolbarButton(
                            '',
                            $url,
                            'pencil',
                            'secondary btn-sm',
                            [
                                'title' => $title,
                            ]
                        );
                    }

                    $urlVisibility = api_get_self().'?'.api_get_cidreq().
                            '&sec_token='.$token.
                            '&id='.$linkId.
                            '&scope=link&category_id='.$categoryId;

                    switch ($visibility) {
                        case 1:
                            $urlVisibility .= '&action=invisible';
                            $title = get_lang('Make invisible');
                            $toolbar .= Display::toolbarButton(
                                '',
                                $urlVisibility,
                                'eye',
                                'secondary btn-sm',
                                [
                                    'title' => $title,
                                ]
                            );
                            break;
                        case 0:
                            $urlVisibility .= '&action=visible';
                            $title = get_lang('Make Visible');
                            $toolbar .= Display::toolbarButton(
                                '',
                                $urlVisibility,
                                'eye-off',
                                'secondary btn-sm',
                                [
                                    'title' => $title,
                                ]
                            );
                            break;
                    }

                    if ($session === $sessionEntity) {
                        $moveLinkParams = [
                            'id' => $linkId,
                            'scope' => 'category',
                            'category_id' => $categoryId,
                            'action' => 'move_link_up',
                        ];

                        $toolbar .= Display::toolbarButton(
                            get_lang('Move up'),
                            api_get_self().'?'.api_get_cidreq().'&'.http_build_query($moveLinkParams),
                            'level-up-alt',
                            'secondary',
                            ['class' => 'btn-sm '.(1 === $i ? 'disabled' : '')],
                            false
                        );

                        $moveLinkParams['action'] = 'move_link_down';
                        $toolbar .= Display::toolbarButton(
                            get_lang('Move down'),
                            api_get_self().'?'.api_get_cidreq().'&'.http_build_query($moveLinkParams),
                            'level-down-alt',
                            'secondary',
                            ['class' => 'btn-sm '.($i === $numberOfLinks ? 'disabled' : '')],
                            false
                        );

                        $url = api_get_self().'?'.api_get_cidreq().'&sec_token='.$token.'&action=deletelink&id='.$linkId.'&category_id='.$categoryId;
                        $event = "javascript: if(!confirm('".get_lang('Do you want to delete this link?')."'))return false;";
                        $title = get_lang('Delete');

                        $toolbar .= Display::toolbarButton(
                            '',
                            $url,
                            'delete',
                            'secondary btn-sm',
                            [
                                'onclick' => $event,
                                'title' => $title,
                            ]
                        );
                    }
                }

                $showLink = false;
                $titleClass = '';
                if ($visibility) {
                    $showLink = true;
                } else {
                    if (api_is_allowed_to_edit(null, true)) {
                        $showLink = true;
                        $titleClass = 'text-muted';
                    }
                }

                if ($showLink) {
                    $url = api_get_path(WEB_CODE_PATH).'link/link_goto.php?'.api_get_cidreq().'&link_id='.$linkId.'&link_url='.urlencode($link->getUrl());
                    $actions = '';
                    if ($showActionLinks) {
                        $actions .= $toolbar;
                    }

                    $title = $iconLink;
                    $title .= Display::tag(
                        'a',
                        Security::remove_XSS($link->getTitle()),
                        [
                            'href' => $url,
                            'target' => $link->getTarget(),
                            'class' => $titleClass,
                        ]
                    );
                    $title .= $link_validator;
                    $title .= $session_img;
                    $content .= Display::panel(null, $title, null, null, null, null, null, $actions);
                }
                $i++;
            }
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
    public static function showCategoryAdminTools(CLinkCategory $category, $currentCategory, $countCategories)
    {
        $categoryId = $category->getIid();
        $token = null;
        $tools = '<a href="'.api_get_self().'?'.api_get_cidreq().'&sec_token='.$token.'&action=editcategory&id='.$categoryId.'&category_id='.$categoryId.'" title='.get_lang('Edit').'">'.
            Display:: return_icon(
                'edit.png',
                get_lang('Edit'),
                [],
                ICON_SIZE_SMALL
            ).'</a>';

        // DISPLAY MOVE UP COMMAND only if it is not the top link.
        if (0 != $currentCategory) {
            $tools .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&sec_token='.$token.'&action=up&up='.$categoryId.'&category_id='.$categoryId.'" title="'.get_lang('Up').'">'.
                Display:: return_icon(
                    'up.png',
                    get_lang('Up'),
                    [],
                    ICON_SIZE_SMALL
                ).'</a>';
        } else {
            $tools .= Display:: return_icon(
                'up_na.png',
                get_lang('Up'),
                [],
                ICON_SIZE_SMALL
            ).'</a>';
        }

        // DISPLAY MOVE DOWN COMMAND only if it is not the bottom link.
        if ($currentCategory < $countCategories - 1) {
            $tools .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&sec_token='.$token.'&action=down&down='.$categoryId.'&category_id='.$categoryId.'">'.
                Display:: return_icon(
                    'down.png',
                    get_lang('down'),
                    [],
                    ICON_SIZE_SMALL
                ).'</a>';
        } else {
            $tools .= Display:: return_icon(
                'down_na.png',
                get_lang('down'),
                [],
                ICON_SIZE_SMALL
            ).'</a>';
        }

        $tools .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&sec_token='.$token.'&action=deletecategory&id='.$categoryId."&category_id=$categoryId\"
            onclick=\"javascript: if(!confirm('".get_lang('When deleting a category, all links of this category are also deleted.
Do you really want to delete this category and its links ?')."')) return false;\">".
            Display:: return_icon(
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

        if ('down' == $action) {
            $sortDirection = 'DESC';
        }

        if ('up' == $action) {
            $sortDirection = 'ASC';
        }

        $movetable = $tbl_categories;

        if (!empty($sortDirection)) {
            if (!in_array(trim(strtoupper($sortDirection)), ['ASC', 'DESC'])) {
                $sortDirection = 'ASC';
            }

            $sql = "SELECT id, display_order FROM $tbl_categories
                    WHERE c_id = $courseId
                    ORDER BY display_order $sortDirection";
            $linkresult = Database:: query($sql);
            $thislinkOrder = 1;
            while ($sortrow = Database:: fetch_array($linkresult)) {
                // STEP 2 : FOUND THE NEXT LINK ID AND ORDER, COMMIT SWAP
                // This part seems unlogic, but it isn't . We first look for the current link with the querystring ID
                // and we know the next iteration of the while loop is the next one. These should be swapped.
                if (isset($thislinkFound) && $thislinkFound) {
                    $nextlinkId = $sortrow['id'];
                    $nextlinkOrder = $sortrow['display_order'];

                    Database:: query(
                        "UPDATE ".$movetable."
                        SET display_order = '$nextlinkOrder'
                        WHERE c_id = $courseId  AND id =  '$thiscatlinkId'"
                    );
                    Database:: query(
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

        Display::addFlash(Display::return_message(get_lang('LinksMoved')));
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
        if (false === $pos) {
            $url_parsed = parse_url($url);

            //Youtube shortener
            //http://youtu.be/ID
            $pos = strpos($url, "youtu.be");

            if (false == $pos) {
                $id = '';
            } else {
                return substr($url_parsed['path'], 1);
            }

            //if empty try the youtube.com/embed/ID
            if (empty($id)) {
                $pos = strpos($url, "embed");
                if (false === $pos) {
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
     * @param int    $courseId
     * @param int    $sessionId
     * @param int    $categoryId
     * @param string $show
     * @param null   $token
     * @param bool   $showActionLinks
     * @param bool   $forceOpenCategories
     *
     * @return string
     */
    public static function listLinksAndCategories(
        $courseId,
        $sessionId,
        $categoryId,
        $show = 'none',
        $token = null,
        $showActionLinks = true,
        $forceOpenCategories = false
    ) {
        $categoryId = (int) $categoryId;
        $content = '';
        $toolbar = '';
        $categories = self::getLinkCategories($courseId, $sessionId);
        $countCategories = count($categories);
        $linksPerCategory = self::showLinksPerCategory(0, $courseId, $sessionId, $showActionLinks);

        if ($showActionLinks) {
            /*	Action Links */
            $actions = '';
            if (api_is_allowed_to_edit(null, true)) {
                $actions .= '<a
                    href="'.api_get_self().'?'.api_get_cidreq().'&action=addlink&category_id='.$categoryId.'">'.
                    Display::return_icon('new_link.png', get_lang('Add a link'), '', ICON_SIZE_MEDIUM).'</a>';
                $actions .= '<a
                    href="'.api_get_self().'?'.api_get_cidreq().'&action=addcategory&category_id='.$categoryId.'">'.
                    Display::return_icon('new_folder.png', get_lang('Add a category'), '', ICON_SIZE_MEDIUM).'</a>';
            }

            if (!empty($countCategories)) {
                $actions .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=list&show=none">';
                $actions .= Display::return_icon(
                        'forum_listview.png',
                        get_lang('List View'),
                        '',
                        ICON_SIZE_MEDIUM
                    ).' </a>';

                $actions .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=list&show=all">';
                $actions .= Display::return_icon(
                        'forum_nestedview.png',
                        get_lang('Nested View'),
                        '',
                        ICON_SIZE_MEDIUM
                    ).'</a>';
            }
            $actions .= Display::url(
                Display::return_icon('pdf.png', get_lang('Export to PDF'), '', ICON_SIZE_MEDIUM),
                api_get_self().'?'.api_get_cidreq().'&action=export'
            );
            $toolbar = Display::toolbarAction('toolbar', [$actions]);
        }

        if (empty($countCategories)) {
            $content .= $linksPerCategory;
        } else {
            if (!empty($linksPerCategory)) {
                $content .= Display::panel($linksPerCategory, get_lang('General'));
            }
        }

        $counter = 0;
        $courseEntity = api_get_course_entity($courseId);
        $sessionEntity = api_get_session_entity($sessionId);
        $allowToEdit = api_is_allowed_to_edit(null, true);

        foreach ($categories as $category) {
            $categoryItemId = $category->getIid();
            $isVisible = $category->isVisible($courseEntity, $sessionEntity);
            // Student don't see invisible categories.
            if (!$allowToEdit) {
                if (!$isVisible) {
                    continue;
                }
            }

            // Validation when belongs to a session
            $showChildren = $categoryId === $categoryItemId || 'all' === $show;
            if ($forceOpenCategories) {
                $showChildren = true;
            }

            $strVisibility = '';
            $visibilityClass = null;
            if ($isVisible) {
                $strVisibility = '<a
                    href="link.php?'.api_get_cidreq().'&sec_token='.$token.'&action=invisible&id='.$categoryItemId.'&scope='.TOOL_LINK_CATEGORY.'"
                    title="'.get_lang('Hide').'">'.
                    Display::return_icon('visible.png', get_lang('Hide'), [], ICON_SIZE_SMALL).'</a>';
            } elseif (!$isVisible) {
                $visibilityClass = 'text-muted';
                $strVisibility = ' <a
                    href="link.php?'.api_get_cidreq().'&sec_token='.$token.'&action=visible&id='.$categoryItemId.'&scope='.TOOL_LINK_CATEGORY.'"
                    title="'.get_lang('Show').'">'.
                    Display::return_icon('invisible.png', get_lang('Show'), [], ICON_SIZE_SMALL).'</a>';
            }

            $header = '';
            if ($showChildren) {
                $header .= '<a
                    class="'.$visibilityClass.'" href="'.api_get_self().'?'.api_get_cidreq().'&category_id=">';
                $header .= Display::return_icon('forum_nestedview.png');
            } else {
                $header .= '<a
                    class="'.$visibilityClass.'"
                    href="'.api_get_self().'?'.api_get_cidreq().'&category_id='.$categoryItemId.'">';
                $header .= Display::return_icon('forum_listview.png');
            }
            $header .= Security::remove_XSS($category->getCategoryTitle()).'</a>';

            if ($showActionLinks) {
                if ($allowToEdit) {
                    if ($category->getFirstResourceLink() &&
                        $sessionEntity === $category->getFirstResourceLink()->getSession()
                    ) {
                        $header .= $strVisibility;
                        $header .= self::showCategoryAdminTools($category, $counter, count($categories));
                    } else {
                        $header .= get_lang('Edition not available from the session, please edit from the basic course.');
                    }
                }
            }

            $childrenContent = '';
            if ($showChildren) {
                $childrenContent = self::showLinksPerCategory(
                    $categoryItemId,
                    api_get_course_int_id(),
                    api_get_session_id()
                );
            }

            $content .= Display::panel($category->getDescription().$childrenContent, $header);
            $counter++;
        }

        if (empty($content) && api_is_allowed_to_edit()) {
            $content .= Display::noDataView(
                get_lang('Links'),
                Display::return_icon('links.png', '', [], 64),
                get_lang('Add links'),
                api_get_self().'?'.api_get_cidreq().'&'.http_build_query(['action' => 'addlink'])
            );
        }

        return $toolbar.$content;
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
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
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

        if ('addlink' === $action) {
            $form->addHeader(get_lang('Add a link'));
        } else {
            $form->addHeader(get_lang('Edit link'));
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
            if (0 != $linkInfo['on_homepage']) {
                $onhomepage = 1;
            }
            $target_link = $linkInfo['target'];
        }

        $form->addHidden('id', $linkId);
        $form->addText('url', 'URL');
        $form->addRule('url', get_lang('Please give the link URL, it should be valid.'), 'url');
        $form->addText('title', get_lang('Link name'));
        $form->addHtmlEditor(
            'description',
            get_lang('Description'),
            false,
            false,
            ['ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130']
        );

        $resultcategories = self::getLinkCategories($courseId, $sessionId);
        $options = ['0' => '--'];
        if (!empty($resultcategories)) {
            foreach ($resultcategories as $myrow) {
                $options[$myrow->getIid()] = $myrow->getCategoryTitle();
            }
        }

        $form->addSelect('category_id', get_lang('Category'), $options);
        $form->addCheckBox('on_homepage', null, get_lang('Show link on course homepage'));

        $targets = [
            '_self' => get_lang('Open self'),
            '_blank' => get_lang('Open blank'),
            '_parent' => get_lang('Open parent'),
            '_top' => get_lang('Open top'),
        ];

        $form->addSelect(
            'target',
            [
                get_lang('Link\'s target'),
                get_lang('Select the target which shows the link on the homepage of the course'),
            ],
            $targets
        );

        $defaults = [
            'url' => empty($urllink) ? 'http://' : Security::remove_XSS($urllink),
            'title' => Security::remove_XSS($title),
            'category_id' => $category,
            'on_homepage' => $onhomepage,
            'description' => $description,
            'target' => $target_link,
        ];

        if ('true' === api_get_setting('search_enabled')) {
            $specific_fields = get_specific_field_list();
            $form->addCheckBox('index_document', get_lang('Index link title and description?s'), get_lang('Yes'));

            foreach ($specific_fields as $specific_field) {
                $default_values = '';
                if ('editlink' === $action) {
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

        $skillList = SkillModel::addSkillsToForm($form, ITEM_TYPE_LINK, $linkId);
        $form->addHidden('lp_id', $lpId);
        $form->addButtonSave(get_lang('Save links'), 'submitLink');
        $defaults['skills'] = array_keys($skillList);
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
        if ('addcategory' === $action) {
            $form->addHeader(get_lang('Add a category'));
            $my_cat_title = get_lang('Add a category');
        } else {
            $form->addHeader(get_lang('Edit Category'));
            $my_cat_title = get_lang('Edit Category');
            $defaults = self::getCategory($id);
        }
        $form->addHidden('id', $id);
        $form->addText('category_title', get_lang('Category name'));
        $form->addHtmlEditor(
            'description',
            get_lang('Description'),
            false,
            false,
            ['ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130']
        );
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
                WHERE iid = $id";
        $result = Database::query($sql);

        return Database::fetch_array($result, 'ASSOC');
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

    /**
     * @param string $url
     *
     * @return bool
     */
    public static function checkUrl($url)
    {
        // Check if curl is available.
        if (!in_array('curl', get_loaded_extensions())) {
            return false;
        }

        // set URL and other appropriate options
        $defaults = [
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true, // follow redirects accept youtube.com
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
        ];

        $proxySettings = api_get_configuration_value('proxy_settings');

        if (!empty($proxySettings) &&
            isset($proxySettings['curl_setopt_array'])
        ) {
            $defaults[CURLOPT_PROXY] = $proxySettings['curl_setopt_array']['CURLOPT_PROXY'];
            $defaults[CURLOPT_PROXYPORT] = $proxySettings['curl_setopt_array']['CURLOPT_PROXYPORT'];
        }

        // Create a new cURL resource
        $ch = curl_init();
        curl_setopt_array($ch, $defaults);

        // grab URL and pass it to the browser
        ob_start();
        $result = curl_exec($ch);
        ob_get_clean();

        // close cURL resource, and free up system resources
        curl_close($ch);

        return $result;
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
        $repo = Container::getLinkRepository();

        /** @var CLink $link */
        $link = $repo->find($id);

        if (!$link) {
            return false;
        }

        $compareLinks = $repo
            ->findBy(
                [
                    'categoryId' => $link->getCategory() ? $link->getCategory()->getIid() : 0,
                ],
                ['displayOrder' => $direction]
            );

        /** @var CLink $prevLink */
        $prevLink = null;

        /** @var CLink $compareLink */
        foreach ($compareLinks as $compareLink) {
            if ($compareLink->getIid() !== $link->getIid()) {
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

            $em->persist($prevLink);
            $em->persist($link);
            break;
        }

        $em->flush();

        return true;
    }
}
