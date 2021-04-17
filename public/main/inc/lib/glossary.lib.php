<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CGlossary;
use ChamiloSession as Session;
use Doctrine\ORM\NoResultException;

/**
 * Class GlossaryManager
 * This library provides functions for the glossary tool.
 * Include/require it in your code to use its functionality.
 *
 * @author Julio Montoya
 * @author Christian Fasanando
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium januari 2009, dokeos 1.8.6
 */
class GlossaryManager
{
    /**
     * Get all glossary terms.
     *
     * @author Isaac Flores <isaac.flores@dokeos.com>
     *
     * @return array Contain glossary terms
     */
    public static function get_glossary_terms()
    {
        $glossaryData = [];
        $repo = Container::getGlossaryRepository();

        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($sessionId);

        $glossaries = $repo->getResourcesByCourse($course, $session);
        /** @var CGlossary $item */
        foreach ($glossaries as $item) {
            $glossaryData[] = [
                'id' => $item->getIid(),
                'name' => $item->getName(),
                'description' => $item->getDescription(),
            ];
        }

        return $glossaryData;

        /*
        $table = Database::get_course_table(TABLE_GLOSSARY);
        $session_id = api_get_session_id();
        $sql_filter = api_get_session_condition($session_id);
        $course_id = api_get_course_int_id();

        $sql = "SELECT glossary_id as id, name, description
                FROM $table
                WHERE c_id = $course_id $sql_filter";
        $rs = Database::query($sql);
        while ($row = Database::fetch_array($rs)) {
            $glossary_data[] = $row;
        }

        return $glossary_data;
        */
    }

    /**
     * Get glossary description by glossary id.
     *
     * @author Isaac Flores <florespaz@bidsoftperu.com>
     *
     * @param int $glossary_id
     *
     * @return string The glossary description
     */
    public static function get_glossary_term_by_glossary_id($glossary_id)
    {
        $repo = Container::getGlossaryRepository();
        /** @var CGlossary $glossary */
        $glossary = $repo->find($glossary_id);
        $description = '';
        if (null !== $glossary) {
            $description = $glossary->getDescription();
        }

        return $description;

        /*
        $table = Database::get_course_table(TABLE_GLOSSARY);
        $course_id = api_get_course_int_id();
        $glossary_id = (int) $glossary_id;

        $sql = "SELECT description
                FROM $table
                WHERE c_id = $course_id  AND glossary_id =".$glossary_id;
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_array($rs);

            return $row['description'];
        }

        return '';
        */
    }

    /**
     * Get glossary term by glossary id.
     *
     * @author Isaac Flores <florespaz_isaac@hotmail.com>
     *
     * @param string $name The glossary term name
     *
     * @return array The glossary info
     */
    public static function get_glossary_term_by_glossary_name($name)
    {
        // @todo Filter by like on ORM
        $table = Database::get_course_table(TABLE_GLOSSARY);
        $sessionId = api_get_session_id();
        $course_id = api_get_course_int_id();
        $sessionCondition = api_get_session_condition($sessionId);

        $glossaryName = Security::remove_XSS($name);
        $glossaryName = api_convert_encoding($glossaryName, 'UTF-8', 'UTF-8');
        $glossaryName = trim($glossaryName);
        $parsed = $glossaryName;

        if (api_get_configuration_value('save_titles_as_html')) {
            $parsed = api_htmlentities($parsed);
            $parsed = "%$parsed%";
        }

        $sql = "SELECT * FROM $table
		        WHERE
		            c_id = $course_id AND
		            (
		                name LIKE '".Database::escape_string($glossaryName)."'
		                OR
		                name LIKE '".Database::escape_string($parsed)."'
                    )
                    $sessionCondition
                LIMIT 1
                ";
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0) {
            return Database::fetch_array($rs, 'ASSOC');
        }

        return [];
    }

    /**
     * This functions stores the glossary in the database.
     *
     * @param array $values Array of title + description (name => $title, description => $comment)
     *
     * @return mixed Term id on success, false on failure
     */
    public static function save_glossary($values, $showMessage = true)
    {
        if (!is_array($values) || !isset($values['name'])) {
            return false;
        }

        // get the maximum display order of all the glossary items
        $max_glossary_item = self::get_max_glossary_item();

        // check if the glossary term already exists
        if (self::glossary_exists($values['name'])) {
            // display the feedback message
            if ($showMessage) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('This glossary term already exists. Please change the term name.'),
                        'error'
                    )
                );
            }

            return false;
        } else {
            $glossary = new CGlossary();

            $courseId = api_get_course_int_id();
            $sessionId = api_get_session_id();

            $glossary
                ->setName($values['name'])
                ->setDescription($values['description'])
                ->setDisplayOrder($max_glossary_item + 1)
            ;

            $course = api_get_course_entity($courseId);
            $session = api_get_session_entity($sessionId);
            $glossary->setParent($course);
            $glossary->addCourseLink($course, $session);

            $repo = Container::getGlossaryRepository();
            $repo->create($glossary);

            // display the feedback message
            if ($showMessage) {
                Display::addFlash(
                    Display::return_message(get_lang('Term added'))
                );
            }

            return $glossary;
        }
    }

    /**
     * update the information of a glossary term in the database.
     *
     * @param array $values an array containing all the form elements
     *
     * @return bool True on success, false on failure
     */
    public static function update_glossary($values, $showMessage = true)
    {
        /*
        // Database table definition
        $table = Database::get_course_table(TABLE_GLOSSARY);
        $course_id = api_get_course_int_id();

        */
        // check if the glossary term already exists
        if (self::glossary_exists($values['name'], $values['glossary_id'])) {
            // display the feedback message
            if ($showMessage) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('This glossary term already exists. Please change the term name.'),
                        'error'
                    )
                );
            }

            return false;
        } else {
            $repo = Container::getGlossaryRepository();

            /** @var CGlossary $glossary */
            $glossary = $repo->find($values['glossary_id']);
            if (null !== $glossary) {
                $glossary
                    ->setName($values['name'])
                    ->setDescription($values['description']);
                $repo->update($glossary);
            }
            /*

            $sql = "UPDATE $table SET
                        name = '".Database::escape_string($values['name'])."',
                        description	= '".Database::escape_string($values['description'])."'
                    WHERE
                        c_id = $course_id AND
                        glossary_id = ".intval($values['glossary_id']);
            $result = Database::query($sql);
            if (false === $result) {
                return false;
            }

            //update glossary into item_property
            /*api_item_property_update(
                api_get_course_info(),
                TOOL_GLOSSARY,
                intval($values['glossary_id']),
                'GlossaryUpdated',
                api_get_user_id()
            );* /

            */
            if ($showMessage) {
                // display the feedback message
                Display::addFlash(
                    Display::return_message(get_lang('Term updated'))
                );
            }
        }

        return true;
    }

    /**
     * Get the maximum display order of the glossary item.
     *
     * @return int Maximum glossary display order
     */
    public static function get_max_glossary_item()
    {
        // @todo get max by orm
        /*
        $repo = Container::getGlossaryRepository();

        $findArray  = [
            'cId' => api_get_course_int_id(),
            'sessionId' => api_get_session_id(),
            'name'=>$term,
        ];
        $glossary = $repo->findBy($findArray);
        */
        // Database table definition

        $repo = Container::getGlossaryRepository();

        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($sessionId);
        $qb = $repo->getResourcesByCourse($course, $session);

        try {
            $count = $qb->select('COUNT(resource)')->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            $count = 0;
        }

        return $count;

        $table = Database::get_course_table(TABLE_GLOSSARY);
        $course_id = api_get_course_int_id();
        $get_max = "SELECT MAX(display_order) FROM $table
                    WHERE c_id = $course_id ";
        $res_max = Database::query($get_max);
        if (0 == Database::num_rows($res_max)) {
            return 0;
        }
        $row = Database::fetch_array($res_max);
        if (!empty($row[0])) {
            return $row[0];
        }

        return 0;
    }

    /**
     * check if the glossary term exists or not.
     *
     * @param string $term   Term to look for
     * @param int    $not_id ID to counter-check if the term exists with this ID as well (optional)
     *
     * @return bool True if term exists
     */
    public static function glossary_exists($term, $not_id = 0)
    {
        $repo = Container::getGlossaryRepository();

        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($sessionId);

        $qb = $repo->getResourcesByCourse($course, $session);
        $glossaries = $qb->getQuery()->getResult();

        if (0 === count($glossaries)) {
            return false;
        }

        /** @var CGlossary $item */
        foreach ($glossaries as $item) {
            if ($term == $item->getName() && $not_id != $item->getIid()) {
                return true;
            }
        }

        return false;
        /*

        // Database table definition
        $table = Database::get_course_table(TABLE_GLOSSARY);
        $course_id = api_get_course_int_id();

        $sql = "SELECT name FROM $table
                WHERE
                    c_id = $course_id AND
                    name = '".Database::escape_string($term)."'";
        if ('' != $not_id) {
            $sql .= " AND glossary_id <> '".intval($not_id)."'";
        }
        $result = Database::query($sql);
        $count = Database::num_rows($result);
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
        */
    }

    /**
     * Delete a glossary term (and re-order all the others).
     *
     * @param int  $glossary_id
     * @param bool $showMessage
     *
     * @return bool True on success, false on failure
     */
    public static function delete_glossary($glossary_id, $showMessage = true)
    {
        $repo = Container::getGlossaryRepository();
        /** @var CGlossary $glossary */
        $glossary = $repo->find($glossary_id);
        if (null !== $glossary) {
            $repo->delete($glossary);
        } else {
            $showMessage = false;
        }

        /*
         // update item_property (delete)
        api_item_property_update(
            api_get_course_info(),
            TOOL_GLOSSARY,
            $glossary_id,
            'delete',
            api_get_user_id()
        );
        */
        // reorder the remaining terms
        self::reorder_glossary();

        if ($showMessage) {
            Display::addFlash(
                Display::return_message(
                    get_lang('Term removed').': '.Security::remove_XSS($glossary->getName()),
                    'normal',
                    false
                )
            );
        }

        return true;
    }

    /**
     * @return string
     */
    public static function getGlossaryView()
    {
        $view = Session::read('glossary_view');
        if (empty($view)) {
            $defaultView = api_get_configuration_value('default_glossary_view');
            if (empty($defaultView)) {
                $defaultView = 'table';
            }

            return $defaultView;
        }

        return $view;
    }

    /**
     * This is the main function that displays the list or the table with all
     * the glossary terms
     * Defaults to 'table' and prefers glossary_view from the session by default.
     *
     * @return string
     */
    public static function display_glossary()
    {
        // This function should always be called with the corresponding
        // parameter for view type. Meanwhile, use this cheap trick.
        $view = self::getGlossaryView();
        // action links
        $actionsLeft = '';
        $url = api_get_path(WEB_CODE_PATH).'glossary/index.php?'.api_get_cidreq();
        if (api_is_allowed_to_edit(null, true)) {
            $addIcon = Display::return_icon(
                'new_glossary_term.png',
                get_lang('Add new glossary term'),
                '',
                ICON_SIZE_MEDIUM
            );
            $actionsLeft .= '<a
                href="'.$url.'&action=addglossary">'.$addIcon.'</a>';
        }

        if (api_is_allowed_to_edit(null, true)) {
            $actionsLeft .= '<a href="'.$url.'&action=import">'.
                Display::return_icon('import.png', get_lang('Import glossary'), '', ICON_SIZE_MEDIUM).'</a>';
        }

        if (!api_is_anonymous()) {
            $actionsLeft .= '<a id="export_opener" href="'.$url.'&action=export">'.
                Display::return_icon('save.png', get_lang('Export'), '', ICON_SIZE_MEDIUM).'</a>';
        }

        if ('table' === $view || !isset($view)) {
            $actionsLeft .= '<a href="'.$url.'&action=changeview&view=list">'.
                Display::return_icon('view_detailed.png', get_lang('List view'), '', ICON_SIZE_MEDIUM).'</a>';
        } else {
            $actionsLeft .= '<a href="'.$url.'&action=changeview&view=table">'.
                Display::return_icon('view_text.png', get_lang('Table view'), '', ICON_SIZE_MEDIUM).'</a>';
        }

        if (api_is_allowed_to_edit(true, true, true)) {
            $exportIcon = Display::return_icon(
                'export_to_documents.png',
                get_lang('Export latest version of this page to Documents'),
                [],
                ICON_SIZE_MEDIUM
            );
            $actionsLeft .= Display::url(
                $exportIcon,
                $url.'&'.http_build_query(['action' => 'export_documents'])
            );
        }

        $orderList = isset($_GET['order']) ? Database::escape_string($_GET['order']) : '';
        if (empty($orderList)) {
            $orderList = 'ASC';
        }
        if (!api_is_allowed_to_edit(true, true, true)) {
            if ('ASC' === $orderList) {
                $actionsLeft .= Display::url(
                    Display::return_icon('falling.png', get_lang('Sort Descending'), [], ICON_SIZE_MEDIUM),
                    $url.'&'.http_build_query(['order' => 'DESC'])
                );
            } else {
                $actionsLeft .= Display::url(
                    Display::return_icon('upward.png', get_lang('Sort Ascending'), [], ICON_SIZE_MEDIUM),
                    $url.'&'.http_build_query(['order' => 'ASC'])
                );
            }
        }

        /* BUILD SEARCH FORM */
        $form = new FormValidator(
            'search',
            'get',
            $url,
            '',
            [],
            FormValidator::LAYOUT_INLINE
        );
        $form->addText('keyword', '', false, ['class' => '']);
        $form->addElement('hidden', 'cid', api_get_course_int_id());
        $form->addElement('hidden', 'sid', api_get_session_id());
        $form->addButtonSearch(get_lang('Search'));
        $actionsRight = $form->returnForm();

        $toolbar = Display::toolbarAction('toolbar-document', [$actionsLeft, $actionsRight]);

        $content = $toolbar;

        $items = self::get_number_glossary_terms();
        if (0 != $items && (!$view || 'table' === $view)) {
            // @todo Table haven't paggination
            $table = new SortableTable(
                'glossary',
                ['GlossaryManager', 'get_number_glossary_terms'],
                ['GlossaryManager', 'get_glossary_data'],
                0
            );
            $table->set_header(0, get_lang('Term'), true);
            $table->set_header(1, get_lang('Term definition'), true);
            if (api_is_allowed_to_edit(null, true)) {
                $table->set_header(2, get_lang('Detail'), false, 'width=90px', ['class' => 'td_actions']);
                $table->set_column_filter(2, ['GlossaryManager', 'actions_filter']);
            }
            $content .= $table->return_table();
        }

        if ('list' === $view) {
            $content .= self::displayGlossaryList();
        }

        return $content;
    }

    /**
     * Display the glossary terms in a list.
     *
     * @return bool true
     */
    public static function displayGlossaryList()
    {
        $glossaryList = self::get_glossary_data(0, 1000, 0, 'ASC');
        $content = '';
        foreach ($glossaryList as $key => $glossary_item) {
            $actions = '';
            if (api_is_allowed_to_edit(null, true)) {
                $actions = '<div class="pull-right">'.
                        self::actions_filter($glossary_item[2], '', $glossary_item).'</div>';
            }
            $content .= Display::panel($glossary_item[1], $glossary_item[0].' '.$actions);
        }

        return $content;
    }

    /**
     * Get the number of glossary terms in the course (or course+session).
     *
     * @param  int     Session ID filter (optional)
     *
     * @return int Count of glossary terms
     */
    public static function get_number_glossary_terms($sessionId = 0)
    {
        // @todo Filter by keywork dont work
        $repo = Container::getGlossaryRepository();

        $courseId = api_get_course_int_id();
        $sessionId = !empty($sessionId) ? $sessionId : api_get_session_id();

        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($sessionId);

        $qb = $repo->getResourcesByCourse($course, $session);
        /*
        $keyword = isset($_GET['keyword']) ? Database::escape_string($_GET['keyword']) : '';
        if(!empty($keyword)){
            $qb->andWhere(
                $qb->expr()->like('resource.name',':keyword')
            )->andWhere(
                $qb->expr()->like('resource.description',':keyword')
            )->setParameter('keyword', '%'.$keyword.'%');
        }
        */

        try {
            $count = $qb->select('COUNT(resource)')->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            $count = 0;
        }

        return $count;
        /*

        // Database table definition
        $t_glossary = Database::get_course_table(TABLE_GLOSSARY);
        $course_id = api_get_course_int_id();
        $session_id = (int) $session_id;
        $sql_filter = api_get_session_condition($session_id, true, true);

        $keyword = isset($_GET['keyword']) ? Database::escape_string($_GET['keyword']) : '';
        $keywordCondition = '';
        if (!empty($keyword)) {
            $keywordCondition = "AND (name LIKE '%$keyword%' OR description LIKE '%$keyword%')";
        }

        $sql = "SELECT count(glossary_id) as total
                FROM $t_glossary
                WHERE c_id = $course_id $sql_filter
                $keywordCondition ";
        $res = Database::query($sql);
        if (false === $res) {
            return 0;
        }
        $obj = Database::fetch_object($res);

        return $obj->total;
        */
    }

    /**
     * Get all the data of a glossary.
     *
     * @param int    $from            From which item
     * @param int    $number_of_items Number of items to collect
     * @param string $column          Name of column on which to order
     * @param string $direction       Whether to sort in ascending (ASC) or descending (DESC)
     *
     * @return array
     */
    public static function get_glossary_data(
        $from,
        $number_of_items,
        $column,
        $direction
    ) {
        // @todo pagination.
        $repo = Container::getGlossaryRepository();
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($sessionId);

        $keyword = $_GET['keyword'] ?? null;

        if (!empty($keyword)) {
            $glossaries = $repo->findResourcesByTitle($keyword, $course->getResourceNode(), $course, $session);
        } else {
            $qb = $repo->getResourcesByCourse($course, $session);
            $glossaries = $qb->getQuery()->getResult();
        }

        $return = [];
        $array = [];
        $view = self::getGlossaryView();

        /** @var CGlossary $glossary */
        foreach ($glossaries as $glossary) {
            $decoration = $repo->addTitleDecoration($glossary, $course, $session);
            $array[0] = $glossary->getName().$decoration;
            if (!$view || 'table' === $view) {
                $array[1] = str_replace(['<p>', '</p>'], ['', '<br />'], $glossary->getDescription());
            } else {
                $array[1] = $glossary->getDescription();
            }

            if (isset($_GET['action']) && 'export' === $_GET['action']) {
                $array[1] = api_html_entity_decode($glossary->getDescription());
            }
            if (api_is_allowed_to_edit(null, true)) {
                $array[2] = $glossary->getIid();
            }
            $return[] = $array;
        }

        return $return;
        /*
        $_user = api_get_user_info();
        $view = self::getGlossaryView();

        // Database table definition
        $t_glossary = Database::get_course_table(TABLE_GLOSSARY);
        $t_item_propery = Database::get_course_table(TABLE_ITEM_PROPERTY);

        if (api_is_allowed_to_edit(null, true)) {
            $col2 = ' glossary.glossary_id	as col2, ';
        } else {
            $col2 = ' ';
        }

        // Condition for the session
        $session_id = api_get_session_id();
        $condition_session = api_get_session_condition(
            $session_id,
            true,
            true,
            'glossary.session_id'
        );

        $column = (int) $column;
        $from = (int) $from;
        $number_of_items = (int) $number_of_items;

        if (!in_array($direction, ['DESC', 'ASC'])) {
            $direction = 'ASC';
        }

        $keyword = isset($_GET['keyword']) ? Database::escape_string($_GET['keyword']) : '';
        $keywordCondition = '';
        if (!empty($keyword)) {
            $keywordCondition = "AND (glossary.name LIKE '%$keyword%' OR glossary.description LIKE '%$keyword%')";
        }
        $sql = "SELECT
                    glossary.name as col0,
                    glossary.description as col1,
                    $col2
                    glossary.session_id
                FROM $t_glossary glossary
                INNER JOIN $t_item_propery ip
                ON (glossary.glossary_id = ip.ref AND glossary.c_id = ip.c_id)
                WHERE
                    tool = '".TOOL_GLOSSARY."'
                    $condition_session AND
                    glossary.c_id = ".api_get_course_int_id()." AND
                    ip.c_id = ".api_get_course_int_id()."
                    $keywordCondition
                ORDER BY col$column $direction
                LIMIT $from, $number_of_items";
        $res = Database::query($sql);

        $return = [];
        $array = [];
        while ($data = Database::fetch_array($res)) {
            // Validation when belongs to a session
            $session_img = api_get_session_image($data['session_id'], $_user['status']);
            $array[0] = $data[0].$session_img;

            if (!$view || 'table' === $view) {
                $array[1] = str_replace(['<p>', '</p>'], ['', '<br />'], $data[1]);
            } else {
                $array[1] = $data[1];
            }

            if (isset($_GET['action']) && 'export' === $_GET['action']) {
                $array[1] = api_html_entity_decode($data[1]);
            }

            if (api_is_allowed_to_edit(null, true)) {
                $array[2] = $data[2];
            }
            $return[] = $array;
        }

        return $return;
        */
    }

    /**
     * Update action icons column.
     *
     * @param int   $glossary_id
     * @param array $url_params  Parameters to use to affect links
     * @param array $row         The line of results from a query on the glossary table
     *
     * @return string HTML string for the action icons columns
     */
    public static function actions_filter($glossary_id, $url_params, $row)
    {
        $glossary_id = $row[2];
        $return = '<a href="'.api_get_self().'?action=edit_glossary&glossary_id='.$glossary_id.'&'.api_get_cidreq().'&msg=edit">'.
            Display::return_icon('edit.png', get_lang('Edit'), '', 22).'</a>';
        $repo = Container::getGlossaryRepository();
        /** @var CGlossary $glossaryData */
        $glossaryData = $repo->find($glossary_id);
        $glossaryTerm = Security::remove_XSS(strip_tags($glossaryData->getName()));
        if (api_is_allowed_to_edit(null, true)) {
            $return .= '<a
                href="'.api_get_self().'?action=delete_glossary&glossary_id='.$glossary_id.'&'.api_get_cidreq().'"
                onclick="return confirmation(\''.$glossaryTerm.'\');">'.
                Display::return_icon('delete.png', get_lang('Delete'), '', 22).'</a>';
            /*
             * if ($glossaryData->getSessionId() == api_get_session_id()) {
            } else {
                $return = get_lang('Edition not available from the session, please edit from the basic course.');
            }
            */
        }

        return $return;
    }

    /**
     * a little bit of javascript to display a prettier warning when deleting a term.
     *
     * @return string HTML string including JavaScript
     */
    public static function javascript_glossary()
    {
        return "<script>
            function confirmation (name) {
                if (confirm(\" ".get_lang("Do you really want to delete this term")." \"+ name + \" ?\")) {
                    return true;
                } else {
                    return false;
                }
            }
        </script>";
    }

    /**
     * Re-order glossary.
     */
    public static function reorder_glossary()
    {
        $repo = Container::getGlossaryRepository();

        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($sessionId);

        $glossaries = $repo->getResourcesByCourse($course, $session);
        $i = 1;
        /** @var CGlossary $item */
        foreach ($glossaries as $item) {
            $item->setDisplayOrder($i);
            $repo->update($item);
            $i++;
        }
        /*
        // Database table definition
        $table = Database::get_course_table(TABLE_GLOSSARY);
        $course_id = api_get_course_int_id();
        $sql = "SELECT * FROM $table
                WHERE c_id = $course_id
                ORDER by display_order ASC";
        $res = Database::query($sql);

        $i = 1;
        while ($data = Database::fetch_array($res)) {
            $sql = "UPDATE $table SET display_order = $i
                    WHERE c_id = $course_id AND glossary_id = '".intval($data['glossary_id'])."'";
            Database::query($sql);
            $i++;
        }
        */
    }

    /**
     * Move a glossary term.
     *
     * @param string $direction
     * @param string $glossary_id
     */
    public static function move_glossary($direction, $glossary_id)
    {
        // Database table definition
        $table = Database::get_course_table(TABLE_GLOSSARY);

        // sort direction
        if ('up' === $direction) {
            $sortorder = 'DESC';
        } else {
            $sortorder = 'ASC';
        }
        $course_id = api_get_course_int_id();

        $sql = "SELECT * FROM $table
                WHERE c_id = $course_id
                ORDER BY display_order $sortorder";
        $res = Database::query($sql);
        $found = false;
        while ($row = Database::fetch_array($res)) {
            if ($found && empty($next_id)) {
                $next_id = $row['glossary_id'];
                $next_display_order = $row['display_order'];
            }

            if ($row['glossary_id'] == $glossary_id) {
                $current_id = $glossary_id;
                $current_display_order = $row['display_order'];
                $found = true;
            }
        }

        $sql1 = "UPDATE $table SET display_order = '".Database::escape_string($next_display_order)."'
                 WHERE c_id = $course_id  AND glossary_id = '".Database::escape_string($current_id)."'";
        $sql2 = "UPDATE $table SET display_order = '".Database::escape_string($current_display_order)."'
                 WHERE c_id = $course_id  AND glossary_id = '".Database::escape_string($next_id)."'";
        Database::query($sql1);
        Database::query($sql2);

        Display::addFlash(Display::return_message(get_lang('The term has moved')));
    }

    /**
     * Export to pdf.
     */
    public static function export_to_pdf()
    {
        $data = self::get_glossary_data(
            0,
            self::get_number_glossary_terms(api_get_session_id()),
            0,
            'ASC'
        );
        $template = new Template('', false, false, false, true, false, false);
        $layout = $template->get_template('glossary/export_pdf.tpl');
        $template->assign('items', $data);

        $html = $template->fetch($layout);
        $courseCode = api_get_course_id();
        $pdf = new PDF();
        $pdf->content_to_pdf($html, '', get_lang('Glossary').'_'.$courseCode, $courseCode);
    }

    /**
     * Generate a PDF with all glossary terms and move file to documents.
     *
     * @return bool false if there's nothing in the glossary
     */
    public static function movePdfToDocuments()
    {
        $sessionId = api_get_session_id();
        $courseId = api_get_course_int_id();
        $data = self::get_glossary_data(
            0,
            self::get_number_glossary_terms($sessionId),
            0,
            'ASC'
        );

        if (!empty($data)) {
            $template = new Template('', false, false, false, true, false, false);
            $layout = $template->get_template('glossary/export_pdf.tpl');
            $template->assign('items', $data);
            $fileName = get_lang('Glossary').'-'.api_get_local_time();
            $signatures = ['Drh', 'Teacher', 'Date'];

            $pdf = new PDF(
                'A4-P',
                'P',
                [
                    'filename' => $fileName,
                    'pdf_title' => $fileName,
                    'add_signatures' => $signatures,
                ]
            );
            $pdf->exportFromHtmlToDocumentsArea(
                $template->fetch($layout),
                $fileName,
                $courseId
            );

            return true;
        } else {
            Display::addFlash(Display::return_message(get_lang('Nothing to add')));
        }

        return false;
    }

    /**
     * @param string $format
     */
    public static function exportToFormat($format)
    {
        if ('pdf' == $format) {
            self::export_to_pdf();

            return;
        }

        $data = self::get_glossary_data(
            0,
            self::get_number_glossary_terms(api_get_session_id()),
            0,
            'ASC'
        );
        usort($data, 'self::sorter');
        //usort($data, 'sorter');
        $list = [];
        $list[] = ['term', 'definition'];
        $allowStrip = api_get_configuration_value('allow_remove_tags_in_glossary_export');
        foreach ($data as $line) {
            $definition = $line[1];
            if ($allowStrip) {
                // htmlspecialchars_decode replace &#39 to '
                // strip_tags remove HTML tags
                $definition = htmlspecialchars_decode(strip_tags($definition), ENT_QUOTES);
            }
            $list[] = [$line[0], $definition];
        }
        $filename = 'glossary_course_'.api_get_course_id();

        switch ($format) {
            case 'csv':
                Export::arrayToCsv($list, $filename);
                break;
            case 'xls':
                Export::arrayToXls($list, $filename);
                break;
        }
    }

    public static function sorter($item1, $item2)
    {
        if ($item1[2] == $item2[2]) {
            return 0;
        }

        return $item1[2] < $item2[2] ? -1 : 1;
    }
}
