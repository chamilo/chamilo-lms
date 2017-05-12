<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class GlossaryManager
 * This library provides functions for the glossary tool.
 * Include/require it in your code to use its functionality.
 *
 * @author Julio Montoya
 * @author Christian Fasanando
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium januari 2009, dokeos 1.8.6
 *
 * @package chamilo.library
 */
class GlossaryManager
{
    /**
     * Get all glossary terms
     * @author Isaac Flores <isaac.flores@dokeos.com>
     * @return array Contain glossary terms
     */
    public static function get_glossary_terms()
    {
        $glossary_data  = array();
        $glossary_table = Database::get_course_table(TABLE_GLOSSARY);
        $session_id = api_get_session_id();
        $sql_filter = api_get_session_condition($session_id);
        $course_id = api_get_course_int_id();

        $sql = "SELECT glossary_id as id, name, description
		        FROM $glossary_table
		        WHERE c_id = $course_id $sql_filter";
        $rs = Database::query($sql);
        while ($row = Database::fetch_array($rs)) {
            $glossary_data[] = $row;
        }

        return $glossary_data;
    }

    /**
     * Get glossary term by glossary id
     * @author Isaac Flores <florespaz@bidsoftperu.com>
     * @param int $glossary_id
     *
     * @return string The glossary description
     */
    public static function get_glossary_term_by_glossary_id($glossary_id)
    {
        $glossary_table = Database::get_course_table(TABLE_GLOSSARY);
        $course_id = api_get_course_int_id();
        $sql = "SELECT description FROM $glossary_table
                WHERE c_id = $course_id  AND glossary_id =".intval($glossary_id);
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_array($rs);

            return $row['description'];
        } else {
            return '';
        }
    }

    /**
     * Get glossary term by glossary id
     * @author Isaac Flores <florespaz_isaac@hotmail.com>
     * @param string $glossary_name The glossary term name
     *
     * @return array The glossary info
     */
    public static function get_glossary_term_by_glossary_name($glossary_name)
    {
        $glossary_table = Database::get_course_table(TABLE_GLOSSARY);
        $session_id = api_get_session_id();
        $course_id = api_get_course_int_id();
        $sql_filter = api_get_session_condition($session_id);
        $sql = 'SELECT * FROM '.$glossary_table.'
		        WHERE
		            c_id = '.$course_id.' AND
		            name LIKE trim("'.Database::escape_string($glossary_name).'")'.$sql_filter;
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_array($rs, 'ASSOC');

            return $row;
        }

        return [];
    }

    /**
     * This functions stores the glossary in the database
     *
     * @param array  $values  Array of title + description (name => $title, description => $comment)
     *
     * @return mixed   Term id on success, false on failure
     *
     */
    public static function save_glossary($values, $showMessage = true)
    {
        if (!is_array($values) || !isset($values['name'])) {
            return false;
        }

        // Database table definition
        $t_glossary = Database::get_course_table(TABLE_GLOSSARY);

        // get the maximum display order of all the glossary items
        $max_glossary_item = self::get_max_glossary_item();

        // session_id
        $session_id = api_get_session_id();

        // check if the glossary term already exists
        if (self::glossary_exists($values['name'])) {
            // display the feedback message
            if ($showMessage) {
                Display::addFlash(
                    Display::return_message(get_lang('GlossaryTermAlreadyExistsYouShouldEditIt'), 'error')
                );
            }
            return false;
        } else {
            $params = [
                'glossary_id' => 0,
                'c_id' => api_get_course_int_id(),
                'name' => $values['name'],
                'description' => $values['description'],
                'display_order' => $max_glossary_item + 1,
                'session_id' => $session_id,
            ];
            $id = Database::insert($t_glossary, $params);

            if ($id) {
                $sql = "UPDATE $t_glossary SET glossary_id = $id WHERE iid = $id";
                Database::query($sql);

                //insert into item_property
                api_item_property_update(
                    api_get_course_info(),
                    TOOL_GLOSSARY,
                    $id,
                    'GlossaryAdded',
                    api_get_user_id()
                );
            }
            // display the feedback message
            if ($showMessage) {
                Display::addFlash(
                    Display::return_message(get_lang('TermAdded'))
                );
            }

            return $id;
        }
    }

    /**
     * update the information of a glossary term in the database
     *
     * @param array $values an array containing all the form elements
     * @return boolean True on success, false on failure
     */
    public static function update_glossary($values, $showMessage = true)
    {
        // Database table definition
        $t_glossary = Database::get_course_table(TABLE_GLOSSARY);
        $course_id = api_get_course_int_id();

        // check if the glossary term already exists
        if (self::glossary_exists($values['name'], $values['glossary_id'])) {
            // display the feedback message
            if ($showMessage) {
                Display::addFlash(
                    Display::return_message(get_lang('GlossaryTermAlreadyExistsYouShouldEditIt'), 'error')
                );
            }

            return false;
        } else {
            $sql = "UPDATE $t_glossary SET
                        name = '".Database::escape_string($values['name'])."',
                        description	= '".Database::escape_string($values['description'])."'
					WHERE
					    c_id = $course_id AND
					    glossary_id = ".intval($values['glossary_id']);
            $result = Database::query($sql);
            if ($result === false) {
                return false;
            }

            //update glossary into item_property
            api_item_property_update(
                api_get_course_info(),
                TOOL_GLOSSARY,
                intval($values['glossary_id']),
                'GlossaryUpdated',
                api_get_user_id()
            );

            if ($showMessage) {
                // display the feedback message
                Display::addFlash(
                    Display::return_message(get_lang('TermUpdated'))
                );
            }
        }

        return true;
    }

    /**
     * Get the maximum display order of the glossary item
     * @return integer Maximum glossary display order
     */
    public static function get_max_glossary_item()
    {
        // Database table definition
        $t_glossary = Database::get_course_table(TABLE_GLOSSARY);
        $course_id = api_get_course_int_id();
        $get_max = "SELECT MAX(display_order) FROM $t_glossary
                    WHERE c_id = $course_id ";
        $res_max = Database::query($get_max);
        if (Database::num_rows($res_max) == 0) {
            return 0;
        }
        $row = Database::fetch_array($res_max);
        if (!empty($row[0])) {
            return $row[0];
        }

        return 0;
    }

    /**
     * check if the glossary term exists or not
     *
     * @param string  $term Term to look for
     * @param integer  $not_id ID to counter-check if the term exists with this ID as well (optional)
     * @return bool    True if term exists
     *
     */
    public static function glossary_exists($term, $not_id = '')
    {
        // Database table definition
        $t_glossary = Database::get_course_table(TABLE_GLOSSARY);
        $course_id = api_get_course_int_id();

        $sql = "SELECT name FROM $t_glossary
                WHERE
                    c_id = $course_id AND
                    name = '".Database::escape_string($term)."'";
        if ($not_id <> '') {
            $sql .= " AND glossary_id <> '".intval($not_id)."'";
        }
        $result = Database::query($sql);
        $count = Database::num_rows($result);
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get one specific glossary term data
     *
     * @param integer $glossary_id ID of the flossary term
     * @return mixed   Array(glossary_id,name,description,glossary_display_order) or false on error
     *
     */
    public static function get_glossary_information($glossary_id)
    {
        // Database table definition
        $t_glossary = Database::get_course_table(TABLE_GLOSSARY);
        $t_item_propery = Database::get_course_table(TABLE_ITEM_PROPERTY);
        if (empty($glossary_id)) {
            return false;
        }
        $sql = "SELECT
                    g.glossary_id 		as glossary_id,
                    g.name 				as name,
                    g.description 		as description,
                    g.display_order		as glossary_display_order,
                    ip.insert_date      as insert_date,
                    ip.lastedit_date    as update_date,
                    g.session_id
                FROM $t_glossary g, $t_item_propery ip
                WHERE
                    g.glossary_id = ip.ref AND
                    tool = '".TOOL_GLOSSARY."' AND
                    g.glossary_id = '".intval($glossary_id)."' AND
                    g.c_id = ".api_get_course_int_id()." AND
                    ip.c_id = ".api_get_course_int_id();

        $result = Database::query($sql);
        if ($result === false || Database::num_rows($result) != 1) {
            return false;
        }

        return Database::fetch_array($result);
    }

    /**
     * Delete a glossary term (and re-order all the others)
     *
     * @param integer $glossary_id
     * @param bool $showMessage
     *
     * @return bool    True on success, false on failure
     */
    public static function delete_glossary($glossary_id, $showMessage = true)
    {
        // Database table definition
        $t_glossary = Database::get_course_table(TABLE_GLOSSARY);
        $course_id = api_get_course_int_id();
        $glossaryInfo = self::get_glossary_information($glossary_id);

        if (empty($glossaryInfo)) {
            return false;
        }

        $glossary_id = (int) $glossary_id;

        $sql = "DELETE FROM $t_glossary 
                WHERE 
                    c_id = $course_id AND 
                    glossary_id='".$glossary_id."'";
        $result = Database::query($sql);
        if ($result === false || Database::affected_rows($result) < 1) {
            return false;
        }

        // update item_property (delete)
        api_item_property_update(
            api_get_course_info(),
            TOOL_GLOSSARY,
            $glossary_id,
            'delete',
            api_get_user_id()
        );

        // reorder the remaining terms
        self::reorder_glossary();

        if ($showMessage) {
            Display::addFlash(
                Display::return_message(get_lang('TermDeleted').': '.$glossaryInfo['name'])
            );
        }

        return true;
    }

    /**
     * This is the main function that displays the list or the table with all
     * the glossary terms
     * @param  string  View ('table' or 'list'). Optional parameter.
     * Defaults to 'table' and prefers glossary_view from the session by default.
     *
     * @return string
     */
    public static function display_glossary($view = 'table')
    {
        // This function should always be called with the corresponding
        // parameter for view type. Meanwhile, use this cheap trick.
        $view = Session::read('glossary_view');
        if (empty($view)) {
            Session::write('glossary_view', $view);
        }
        // action links
        //echo '<div class="actions">';
        $actionsLeft = '';
        if (api_is_allowed_to_edit(null, true)) {
            $actionsLeft .= '<a href="index.php?'.api_get_cidreq().'&action=addglossary&msg=add?'.api_get_cidreq().'">'.
                Display::return_icon('new_glossary_term.png', get_lang('TermAddNew'), '', ICON_SIZE_MEDIUM).'</a>';
        }

        $actionsLeft .= '<a href="index.php?'.api_get_cidreq().'&action=export">'.
            Display::return_icon('export_csv.png', get_lang('ExportGlossaryAsCSV'), '', ICON_SIZE_MEDIUM).'</a>';
        if (api_is_allowed_to_edit(null, true)) {
            $actionsLeft .= '<a href="index.php?'.api_get_cidreq().'&action=import">'.
                Display::return_icon('import_csv.png', get_lang('ImportGlossary'), '', ICON_SIZE_MEDIUM).'</a>';
        }

        $actionsLeft .= '<a href="index.php?'.api_get_cidreq().'&action=export_to_pdf">'.
            Display::return_icon('pdf.png', get_lang('ExportToPDF'), '', ICON_SIZE_MEDIUM).'</a>';

        if (($view == 'table') || (!isset($view))) {
            $actionsLeft .= '<a href="index.php?'.api_get_cidreq().'&action=changeview&view=list">'.
                Display::return_icon('view_detailed.png', get_lang('ListView'), '', ICON_SIZE_MEDIUM).'</a>';
        } else {
            $actionsLeft .= '<a href="index.php?'.api_get_cidreq().'&action=changeview&view=table">'.
                Display::return_icon('view_text.png', get_lang('TableView'), '', ICON_SIZE_MEDIUM).'</a>';
        }

        /* BUILD SEARCH FORM */
        $form = new FormValidator(
            'search',
            'get',
            api_get_self().'?'.api_get_cidreq(),
            '',
            array(),
            FormValidator::LAYOUT_INLINE
        );
        $form->addText('keyword', '', false, array('class' => 'col-md-2'));
        $form->addElement('hidden', 'cidReq', api_get_course_id());
        $form->addElement('hidden', 'id_session', api_get_session_id());
        $form->addButtonSearch(get_lang('Search'));
        $actionsRight = $form->returnForm();

        $toolbar = Display::toolbarAction(
            'toolbar-document',
            array($actionsLeft, $actionsRight)
        );

        $content = $toolbar;

        if (!$view || $view === 'table') {
            $table = new SortableTable(
                'glossary',
                array('GlossaryManager', 'get_number_glossary_terms'),
                array('GlossaryManager', 'get_glossary_data'),
                0
            );
            //$table->set_header(0, '', false);
            $table->set_header(0, get_lang('TermName'), true);
            $table->set_header(1, get_lang('TermDefinition'), true);
            if (api_is_allowed_to_edit(null, true)) {
                $table->set_header(2, get_lang('Actions'), false, 'width=90px', array('class' => 'td_actions'));
                $table->set_column_filter(2, array('GlossaryManager', 'actions_filter'));
            }
            $content .= $table->return_table();
        }

        if ($view === 'list') {
            $content .= self::displayGlossaryList();
        }

        return $content;
    }

    /**
     * Display the glossary terms in a list
     * @return bool true
     */
    public static function displayGlossaryList()
    {
        $glossary_data = self::get_glossary_data(0, 1000, 0, 'ASC');
        $content = '';
        foreach ($glossary_data as $key => $glossary_item) {
            $actions = '';
            if (api_is_allowed_to_edit(null, true)) {
                $actions = '<div class="pull-right">'.self::actions_filter($glossary_item[2], '', $glossary_item).'</div>';
            }
            $content .= Display::panel($glossary_item[1], $glossary_item[0].' '.$actions);
        }
        return $content;
    }

    /**
     * Get the number of glossary terms in the course (or course+session)
     * @param  int     Session ID filter (optional)
     * @return integer Count of glossary terms
     *
     */
    public static function get_number_glossary_terms($session_id = 0)
    {
        // Database table definition
        $t_glossary = Database::get_course_table(TABLE_GLOSSARY);
        $course_id = api_get_course_int_id();
        $session_id = intval($session_id);
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
        if ($res === false) {
            return 0;
        }
        $obj = Database::fetch_object($res);

        return $obj->total;
    }

    /**
     * Get all the data of a glossary
     *
     * @param int $from From which item
     * @param int $number_of_items Number of items to collect
     * @param string  $column Name of column on which to order
     * @param string $direction  Whether to sort in ascending (ASC) or descending (DESC)
     *
     * @return array
     */
    public static function get_glossary_data($from, $number_of_items, $column, $direction)
    {
        $_user = api_get_user_info();
        $view = Session::read('glossary_view');

        // Database table definition
        $t_glossary = Database::get_course_table(TABLE_GLOSSARY);
        $t_item_propery = Database::get_course_table(TABLE_ITEM_PROPERTY);

        if (api_is_allowed_to_edit(null, true)) {
            $col2 = " glossary.glossary_id	as col2, ";
        } else {
            $col2 = ' ';
        }

        //condition for the session
        $session_id = api_get_session_id();
        $condition_session = api_get_session_condition(
            $session_id,
            true,
            true,
            'glossary.session_id'
        );

        $column = intval($column);
        if (!in_array($direction, array('DESC', 'ASC'))) {
            $direction = 'ASC';
        }
        $from = intval($from);
        $number_of_items = intval($number_of_items);

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
				FROM $t_glossary glossary, $t_item_propery ip
				WHERE
				    glossary.glossary_id = ip.ref AND
					tool = '".TOOL_GLOSSARY."' $condition_session AND
					glossary.c_id = ".api_get_course_int_id()." AND
					ip.c_id = ".api_get_course_int_id()."
					$keywordCondition
		        ORDER BY col$column $direction
		        LIMIT $from,$number_of_items";
        $res = Database::query($sql);

        $return = array();
        $array = array();
        while ($data = Database::fetch_array($res)) {
            // Validation when belongs to a session
            $session_img = api_get_session_image($data['session_id'], $_user['status']);
            $array[0] = $data[0].$session_img;

            if (!$view || $view === 'table') {
                $array[1] = str_replace(array('<p>', '</p>'), array('', '<br />'), $data[1]);
            } else {
                $array[1] = $data[1];
            }

            if (api_is_allowed_to_edit(null, true)) {
                $array[2] = $data[2];
            }
            $return[] = $array;
        }

        return $return;
    }

    /**
     * Update action icons column
     *
     * @param integer $glossary_id
     * @param array   $url_params Parameters to use to affect links
     * @param array   $row The line of results from a query on the glossary table
     *
     * @return string HTML string for the action icons columns
     */
    public static function actions_filter($glossary_id, $url_params, $row)
    {
        $glossary_id = $row[2];
        $return = '<a href="'.api_get_self().'?action=edit_glossary&glossary_id='.$glossary_id.'&'.api_get_cidreq().'&msg=edit">'.
            Display::return_icon('edit.png', get_lang('Edit'), '', 22).'</a>';
        $glossary_data = self::get_glossary_information($glossary_id);
        $glossary_term = $glossary_data['name'];
        if (api_is_allowed_to_edit(null, true)) {
            if ($glossary_data['session_id'] == api_get_session_id()) {
                $return .= '<a href="'.api_get_self().'?action=delete_glossary&glossary_id='.$glossary_id.'&'.api_get_cidreq().'" onclick="return confirmation(\''.$glossary_term.'\');">'.
                    Display::return_icon('delete.png', get_lang('Delete'), '', 22).'</a>';
            } else {
                $return = get_lang('EditionNotAvailableFromSession');
            }
        }

        return $return;
    }

    /**
     * a little bit of javascript to display a prettier warning when deleting a term
     *
     * @return string  HTML string including JavaScript
     *
     */
    public static function javascript_glossary()
    {
        return "<script>
            function confirmation (name) {
                if (confirm(\" ".get_lang("TermConfirmDelete")." \"+ name + \" ?\")) {
                    return true;
                } else {
                    return false;
                }
            }
        </script>";
    }

    /**
     * Re-order glossary
     */
    public static function reorder_glossary()
    {
        // Database table definition
        $t_glossary = Database::get_course_table(TABLE_GLOSSARY);
        $course_id = api_get_course_int_id();
        $sql = "SELECT * FROM $t_glossary
                WHERE c_id = $course_id
                ORDER by display_order ASC";
        $res = Database::query($sql);

        $i = 1;
        while ($data = Database::fetch_array($res)) {
            $sql = "UPDATE $t_glossary SET display_order = $i
                    WHERE c_id = $course_id AND glossary_id = '".intval($data['glossary_id'])."'";
            Database::query($sql);
            $i++;
        }
    }

    /**
     * Move a glossary term
     *
     * @param string $direction
     * @param string $glossary_id
     */
    public static function move_glossary($direction, $glossary_id)
    {
        // Database table definition
        $t_glossary = Database::get_course_table(TABLE_GLOSSARY);

        // sort direction
        if ($direction === 'up') {
            $sortorder = 'DESC';
        } else {
            $sortorder = 'ASC';
        }
        $course_id = api_get_course_int_id();

        $sql = "SELECT * FROM $t_glossary
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
        $sql1 = "UPDATE $t_glossary SET display_order = '".Database::escape_string($next_display_order)."'
                 WHERE c_id = $course_id  AND glossary_id = '".Database::escape_string($current_id)."'";
        $sql2 = "UPDATE $t_glossary SET display_order = '".Database::escape_string($current_display_order)."'
                 WHERE c_id = $course_id  AND glossary_id = '".Database::escape_string($next_id)."'";
        Database::query($sql1);
        Database::query($sql2);

        Display::addFlash(Display::return_message(get_lang('TermMoved')));
    }

    /**
     * Export to pdf
     */
    public static function export_to_pdf()
    {
        $data = self::get_glossary_data(
            0,
            self::get_number_glossary_terms(api_get_session_id()),
            0,
            'ASC'
        );
        $html = '<html><body>';
        $html .= '<h2>'.get_lang('Glossary').'</h2><hr><br><br>';
        foreach ($data as $item) {
            $term = $item[0];
            $description = $item[1];
            $html .= '<h4>'.$term.'</h4><p>'.$description.'<p><hr>';
        }
        $html .= '</body></html>';
        $courseCode = api_get_course_id();
        $pdf = new PDF();
        $pdf->content_to_pdf($html, '', get_lang('Glossary').'_'.$courseCode, $courseCode);
    }
}
