<?php
/* For licensing terms, see /license.txt */

/**
 * This is a function library for the learning path.
 *
 * Due to the face that the learning path has been built upon the resoucelinker,
 * naming conventions have changed at least 2 times. You can see here in order the :
 * 1. name used in the first version of the resourcelinker
 * 2. name used in the first version of the LP
 * 3. name used in the second (current) version of the LP
 *
 *       1.       2.        3.
 *   Category = Chapter = Module
 *   Item (?) = Item    = Step
 *
 * @author  Denes Nagy <darkden@evk.bke.hu>, main author
 * @author  Roan Embrechts, some code cleaning
 * @author  Yannick Warnier <yannick.warnier@beeznest.com>, multi-level learnpath behaviour + new SCORM tool
 * @access  public
 * @package chamilo.learnpath
 * @todo rename functions to coding conventions: not deleteitem but delete_item, etc
 * @todo rewrite functions to comply with phpDocumentor
 * @todo remove code duplication
 */

/**
 * This function deletes an item
 * @param integer 	$id: the item we want to delete
 * @return boolean	True if item was deleted, false if not found or error
 */
function deleteitem($id) {
    
    $course_id = api_get_course_int_id();
    
    $tbl_learnpath_item     = Database :: get_course_table(TABLE_LEARNPATH_ITEM);
    $tbl_learnpath_chapter  = Database :: get_course_table(TABLE_LEARNPATH_CHAPTER);
    // Get the display order for this item before it is deleted.
    $sql = "SELECT display_order, parent_item_id FROM $tbl_lp_item WHERE c_id = $course_id AND id=$id";
    $result = Database::query($sql);
    if (Database::num_rows($result) == 0) {
        return false;
    }
    $row = Database::fetch_row($result);
    $display_order = $row[0];
    $parent_item_id = $row[1];
    // Delete the item.
    $sql = "DELETE FROM $tbl_learnpath_item WHERE c_id = $course_id AND id='$id'";
    $result = Database::query($sql);
    if ($result === false) {
        return false;
    }
    // Update the other items and chapters.
    $sql = "UPDATE $tbl_learnpath_item SET display_order = display_order-1 WHERE c_id = $course_id AND display_order > $display_order AND parent_item_id = $parent_item_id";
    $result = Database::query($sql);
    $sql = "UPDATE $tbl_learnpath_chapter SET display_order = display_order-1 WHERE c_id = $course_id AND display_order > $display_order AND parent_item_id = $parent_item_id";
    $result = Database::query($sql);

    return true;
}

/**
 * This function deletes a module(chapter) and all its steps(items).
 *
 * @param integer id of the chapter we want to delete
 * @return boolean	True on success and false if not found or error
 */
function deletemodule($parent_item_id) {
    global $learnpath_id;
    $course_id = api_get_course_int_id();
    $tbl_learnpath_item 	= Database :: get_course_table(TABLE_LEARNPATH_ITEM);
    $tbl_learnpath_chapter 	= Database :: get_course_table(TABLE_LEARNPATH_CHAPTER);

    // Added for multi-level behaviour - slightly recursive.
    $sql = "SELECT * FROM $tbl_learnpath_chapter WHERE c_id = $course_id AND lp_id=$learnpath_id";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        if ($row['parent_item_id'] == $parent_item_id) {
            // Delete every subchapter.
            if (deletemodule($row['id']) === false) {
                return false;
            }
        }
    }

    // Get this chapter's display order.
    $sql = "SELECT display_order, parent_item_id FROM $tbl_learnpath_chapter 
    		WHERE c_id = $course_id AND id=$parent_item_id and lp_id=$learnpath_id";
    $result = Database::query($sql);
    if (Database::num_rows($result) == 0) {
        return false;
    }
    $row = Database::fetch_row($result);

    $display_order = $row[0];
    $parent_id = $row[1];

    // Delete the chapter itself.
    $sql = "DELETE FROM $tbl_learnpath_chapter WHERE c_id = $course_id AND (id=$parent_item_id and lp_id=$learnpath_id)";
    $result = Database::query($sql);
    // Delete items from that chapter.
    $sql2 = "DELETE FROM $tbl_learnpath_item WHERE c_id = $course_id AND parent_item_id=$parent_item_id";
    $result = Database::query($sql2);

    // Update all other chapters accordingly.
    $sql = "UPDATE $tbl_learnpath_item SET display_order = display_order-1 WHERE c_id = $course_id AND display_order > $display_order AND parent_item_id = $parent_id";
    $result = Database::query($sql);
    $sql = "UPDATE $tbl_learnpath_chapter SET display_order = display_order-1 WHERE c_id = $course_id AND display_order > $display_order AND parent_item_id = $parent_id";
    $result = Database::query($sql);

    return true;
}

/**
 * This function deletes an entire path.
 *
 * @param integer 	$id: the path we want to delete
 * @return	void
 */
function deletepath($path_id) {
    $tbl_learnpath_main = Database :: get_course_table(TABLE_LEARNPATH_MAIN);
    $tbl_learnpath_item = Database :: get_course_table(TABLE_LEARNPATH_ITEM);
    $tbl_learnpath_chapter = Database :: get_course_table(TABLE_LEARNPATH_CHAPTER);
    
    $course_id = api_get_course_int_id();

    $sql = "DELETE FROM $tbl_learnpath_main WHERE c_id = $course_id AND lp_id='$path_id'";
    $result = Database::query($sql);

    //@TODO check how this function is used before uncommenting the following
    //also delete all elements inside that path
    $sql = "SELECT * FROM $tbl_learnpath_chapter WHERE c_id = $course_id AND lp_id = $path_id";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        deletemodule($row['id']);
    }
}

/**
 * This function moves an item.
 *
 * @param string    $direction: move the given chapter up or down
 * @param integer   Item ID
 * @param integer   $moduleid: the id of the chapter the element resides in
 * @return	boolean	Returns false on error
 * @note    With this new version, the moveitem deals with items AND directories (not the base-level modules). This is a lot more complicated but is a temporary step towards new database structure as 'everything is an item'
 */
function moveitem($direction, $id, $moduleid, $type = 'item') {
    global $learnpath_id;
    $course_id = api_get_course_int_id();
    
    $tbl_learnpath_item     = Database::get_course_table(TABLE_LEARNPATH_ITEM);
    $tbl_learnpath_chapter  = Database::get_course_table(TABLE_LEARNPATH_CHAPTER);

    $tree = get_learnpath_tree($learnpath_id);
    $orig_order = 0;
    $orig_type = '';
    $orig_id = $id;
    foreach ($tree[$moduleid] as $row) {
        // If this is the element we want (be it a chapter or an item), get its data.
        if (($row['id'] == $id) && ($row['type'] == $type)) {
            $orig_order = $row['display_order'];
            $orig_type = $row['type'];
            break;
        }
    }

    $dest_order = 0;
    $dest_type = '';
    $dest_id = 0;
    if ($direction == 'up') {
        if (!empty ($tree[$moduleid][$orig_order - 1])) {
            $dest_order = $orig_order - 1;
            $dest_type = $tree[$moduleid][$orig_order - 1]['type'];
            $dest_id = $tree[$moduleid][$orig_order - 1]['id'];
        } else {
            return false;
        }
    } else {
        // Move down.
        if (!empty ($tree[$moduleid][$orig_order + 1])) {
            $dest_order = $orig_order + 1;
            $dest_type = $tree[$moduleid][$orig_order + 1]['type'];
            $dest_id = $tree[$moduleid][$orig_order + 1]['id'];
        } else {
            return false;
        }
    }

    $sql1 = '';
    $sql2 = '';
    if ($orig_type == 'chapter') {
        $sql1 = "UPDATE $tbl_learnpath_chapter SET display_order = ".$dest_order." WHERE c_id = $course_id AND (id=$orig_id and parent_item_id=$moduleid)";
    } elseif ($orig_type == 'item') {
        $sql1 = "UPDATE $tbl_learnpath_item SET display_order = ".$dest_order." WHERE c_id = $course_id AND (id=$orig_id and parent_item_id=$moduleid)";
    } else {
        return false;
    }

    if ($dest_type == 'chapter') {
        $sql2 = "UPDATE $tbl_learnpath_chapter SET display_order = ".$orig_order." WHERE c_id = $course_id AND (id='$dest_id' and parent_item_id=$moduleid)";
    } elseif ($dest_type == 'item') {
        $sql2 = "UPDATE $tbl_learnpath_item SET display_order = ".$orig_order." WHERE c_id = $course_id AND (id='$dest_id' and parent_item_id=$moduleid)";
    } else {
        return false;
    }
    Database::query($sql1);
    Database::query($sql2);
}

/**
 * This function moves a module (also called chapter or category).
 *
 * @param   string $direction: move the given chapter up or down
 * @param   integer $id: the id of the chapter we want to move
 * @return	void
 */
function movemodule($direction, $id) {
    global $learnpath_id;
    $course_id = api_get_course_int_id();
    
    $tbl_learnpath_chapter  = Database::get_course_table(TABLE_LEARNPATH_CHAPTER);
    if ($direction == 'up') {
        $sortDirection = 'DESC';
    } else {
        $sortDirection = 'ASC';
    }

    // Select all chapters of first level (parent_item_id = 0).
    $sql = "SELECT * FROM $tbl_learnpath_chapter WHERE c_id = $course_id AND (lp_id=$learnpath_id AND parent_item_id = 0) ORDER BY display_order $sortDirection";
    $result = Database::query($sql);
    $previousrow = '';

    // See similar comment in moveitem() function.
    // @TODO: this only works for chapters in multi-level mode. Why not gather
    // this function and moveitem to keep only one multi-uses function?
    while ($row = Database::fetch_array($result)) {
        // Step 2: Performing the move (only happens when passed trhough step 1 at least once).
        if (!empty ($this_cat_order)) {
            $next_cat_order = $row['display_order'];
            $next_cat_id = $row['id'];

            $sql1 = "UPDATE $tbl_learnpath_chapter SET display_order = '$next_cat_order' WHERE c_id = $course_id AND (id='$this_cat_id' and lp_id=$learnpath_id)";
            $sql2 = "UPDATE $tbl_learnpath_chapter SET display_order = '$this_cat_order' WHERE c_id = $course_id AND (id='$next_cat_id' and lp_id=$learnpath_id)";
            Database::query($sql1);
            Database::query($sql2);
            unset ($this_cat_order);
            unset ($next_cat_order);
            unset ($next_cat_id);
            break;
        }

        // Step 1: Looking for the order of the row we want to move.
        if ($row['id'] == $id) {
            $this_cat_order = $row['display_order'];
            $this_cat_id = $id;
        }
    }
}

/**
 * Inserts a new element in a learnpath table (item or chapter)
 * @param		string	Element type ('chapter' or 'item')
 * @param		string	Chapter name
 * @param		string	Chapter description (optional)
 * @param		integer	Parent chapter ID (default: 0)
 * @param		integer Learnpath ID
 * @param		mixed		If type 'item', then array(prereq_id=>value, prereq_..)
 * @return	integer	The new chapter ID, or false on failure
 * @TODO	Finish this function before it is used. Currently only chapters can be added using it.
 * @note This function is currently never used!
 */
function insert_item($type = 'item', $name, $chapter_description = '', $parent_id = 0, $learnpath_id = 0, $params = null) {
    $tbl_learnpath_chapter	= Database :: get_course_table(TABLE_LEARNPATH_CHAPTER);
    $tbl_learnpath_item 	= Database :: get_course_table(TABLE_LEARNPATH_ITEM);
    
    $course_id = api_get_course_int_id();

    // Getting the last order number from the chapters table, in this learnpath, for the parent chapter given.
    $sql = "SELECT * FROM $tbl_learnpath_chapter
            WHERE c_id = $course_id AND lp_id=$learnpath_id  AND parent_item_id = $parent_id
            ORDER BY display_order DESC";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    $last_chapter_order = $row['display_order'];

    // Getting the last order number of the items.
    $sql = "SELECT * FROM $tbl_learnpath_item
            WHERE c_id = $course_id AND parent_item_id = $parent_id
            ORDER BY display_order DESC";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    $last_item_order = $row['display_order'];
    $new_order = max($last_chapter_order, $last_item_order) + 1;
	
    if ($type === 'chapter') {
        $sql = "INSERT INTO $tbl_learnpath_chapter (c_id, lp_id, chapter_name, chapter_description, display_order)
				VALUES ( $course_id, 
						'".domesticate($learnpath_id)."',
                        '".domesticate(htmlspecialchars($name))."',
                        '".domesticate(htmlspecialchars($chapter_description))."',
                        $new_order )";
        $result = Database::query($sql);
        if ($result === false) {
            return false;
        }
        $id = Database :: insert_id();
    } elseif ($type === 'item') {
        $sql = "INSERT INTO $tbl_learnpath_item (c_id, parent_item_id, item_type, display_order) VALUES 
        		($course_id, '".domesticate($parent_id)."','".domesticate(htmlspecialchars($type))."', $new_order )";
        $result = Database::query($sql);
        if ($result === false) {
            return false;
        }
        $id = Database :: insert_id();
    }
    return $id;
}

/**
 * This function returns an array with all the learnpath categories/chapters
 * @return array List of learnpath chapter titles
 */
function array_learnpath_categories() {
    $course_id = api_get_course_int_id();    
    global $learnpath_id;
    $tbl_learnpath_chapter = Database :: get_course_table(TABLE_LEARNPATH_CHAPTER);

    $sql = "SELECT * FROM  $tbl_learnpath_chapter  WHERE c_id = $course_id AND (lp_id=$learnpath_id) ORDER BY display_order ASC";
    $result = Database::query($sql);

    while ($row = Database::fetch_array($result)) {
        $array_learnpath_categories[] = array($row['id'], $row['chapter_name']);
    }
    //$array_learnpath_categories = array($array_learnpath_categories_name, $array_learnpath_categories_id);
    return $array_learnpath_categories;
}

/**
* Displays the learnpath chapters(=modules,categories) and their contents.
* @param    integer     Chapter ID to display now (enables recursive behaviour)
* @param    array       The array as returned by get_learnpath_tree, with all the elements of a learnpath compiled and structured into the array, by chapter id
* @param    integer     Level (the depth of the call - helps in display)
* @todo eliminate all global $lang declarations, use get_lang, improve structure.
* @author   Denes Nagy
* @author   Roan Embrechts
* @author   Yannick Warnier <yannick.warnier@beeznest.com> - complete redesign for multi-level learnpath chapters
*/
function display_learnpath_chapters($parent_item_id = 0, $tree = array (), $level = 0) {
    //error_log('New LP - In learnpath_functions::display_learnpath_chapters', 0);
    global $color2;
    global $xml_output;
    global $learnpath_id;
    $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);

    // @todo: coding standards: Language variables are CaMMelCaSe, all other variables should use the underscoring method.
    $lang_move_down = get_lang('_move_down');
    $lang_move_up = get_lang('lang_move_up');
    $lang_edit_learnpath_module = get_lang('lang_edit_learnpath_module');
    $lang_delete_learnpath_module = get_lang('lang_delete_learnpath_module');
    $lang_nochapters = get_lang('lang_nochapters');
    $lang_prerequisites = get_lang('lang_prerequisites');
    $lang_prerequisites_limit = get_lang('lang_prerequisites_limit');
    $lang_add_learnpath_item = get_lang('lang_add_learnpath_item');
    $lang_add_item = get_lang('lang_add_item');
    $lang_title_and_desc = get_lang('lang_title_and_desc');
    $lang_change_order = get_lang('lang_change_order');
    $lang_add_prereqi = get_lang('lang_add_prereqi');
    $lang_add_title_and_desc = get_lang('lang_add_title_and_desc');
    $lang_delete = get_lang('Delete');

    if ($parent_item_id === 0) {

        // This is the first time we use the function, define the tree and display learnpath name.
        $tree = get_learnpath_tree($learnpath_id);

        $num_modules = count($tree);
        //$num_modules = Database::num_rows($result);
        if ($num_modules == 0) {
            // do not diplay useless information
            //echo "<tr><td>&nbsp;$lang_nochapters</td></tr>";
        } else {
            echo "  <tr align='center' valign='top'><td><b>&nbsp;$lang_title_and_desc </b></td>\n"."    <td><b>&nbsp;$lang_add_item </b></td>\n";
            if (is_prereq($learnpath_id)) {
                echo "    <td bgcolor='#ddddee'><b>&nbsp;$lang_prerequisites_limit </b></td>\n";
            } else {
                echo "    <td><b>&nbsp;$lang_prerequisites </b></td>\n";
            }

            echo "    <td colspan='2'><b>&nbsp;$lang_change_order </b></td><td><b>&nbsp;$lang_add_prereqi </b></td>\n"."    <td><b>&nbsp;$lang_add_title_and_desc </b></td><td><b>&nbsp;$lang_delete </b></td>\n"."  </tr>\n";
        }
    }

    $i = 1;
    $counter = 0;
    $num_modules = count($tree[$parent_item_id]);

    //while ($row = Database::fetch_array($result))
    if (isset ($tree[$parent_item_id])) {
        foreach ($tree[$parent_item_id] as $row) {
            if ($row['item_type'] === 'dokeos_chapter') {
                $xml_output .= "<chapter>";
                $xml_output .= "<chaptertitle>".$row['title']."</chaptertitle>";
                $xml_output .= "<chapterdescription>".$row['description']."</chapterdescription>";

                $counter ++;
                if (($counter % 2) == 0) {
                    $oddclass = 'row_odd';
                } else {
                    $oddclass = 'row_even';
                }

                //echo '<tr class="'.$oddclass.'">'."\n".'  <td>'.str_repeat("&nbsp;&gt;", $level)."<img src='../img/documents.gif' alt='folder'/><a href='".api_get_self()."?lp_id=$learnpath_id&item_id={$row['id']}&action=add&type=learnpathitem&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9'><b>&nbsp;".$row['title']."</b></a>"."<br /><i><div align='justify'>&nbsp;".str_repeat("&nbsp;&nbsp;&nbsp;", $level)."</i></td>\n".'  <td  align="center"><a href="'.api_get_self()."?lp_id=$learnpath_id&item_id={$row['id']}&action=add&type=learnpathitem&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9"><img src='../img/0.gif' width='13' height='13' border='0' title='$lang_add_learnpath_item'></a></td>\n"."  <td";
                echo '<tr class="'.$oddclass.'">'."\n".'  <td>'.str_repeat("&nbsp;&gt;", $level)."<img src='../img/documents.gif' alt='folder'/><a href='".api_get_self()."?lp_id=$learnpath_id&parent_item_id=".$row['id']."&action=add_sub_item'><b>&nbsp;".$row['title']."</b></a>"."<br /><i><div align='justify'>&nbsp;".str_repeat("&nbsp;&nbsp;&nbsp;", $level)."</i></td>\n".'  <td  align="center"><a href="'.api_get_self()."?lp_id=$learnpath_id&parent_item_id=".$row['id']."&action=add_sub_item\"><img src='../img/0.gif' width='13' height='13' border='0' title='$lang_add_learnpath_item'></a></td>\n"."  <td";
                if (is_prereq($learnpath_id)) {
                    echo " bgcolor='#ddddee'";
                }
                echo ">".$row['prerequisite']."</td>\n";

                // Showing the edit, delete and move icons.
                if (api_is_allowed_to_edit()) {
                    $myaction = 'move_item';
                    if ($i < $num_modules) {
                        // If we are still under the number of chapters in this section, show "move down".
                        //echo "  <td align=center>"."<a href='".api_get_self()."?lp_id=$learnpath_id&amp;action=".$myaction."&amp;direction=down&amp;moduleid=".$parent_item_id."&amp;id=".$row['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9>"."<img src=\"../img/down.gif\" border=\"0\" title=\"$lang_move_down\">"."</a></td>\n";
                        echo "  <td align=center>"."<a href='".api_get_self()."?lp_id=$learnpath_id&action=".$myaction."&direction=down&moduleid=".$parent_item_id."&id=".$row['id']."'>"."<img src=\"../img/down.gif\" border=\"0\" title=\"$lang_move_down\">"."</a></td>\n";
                    } else {
                        echo '  <td align="center">&nbsp;</td>'."\n";
                    }

                    if ($i > 1) {
                        //echo '  <td align="center">'."<a href='".api_get_self()."?lp_id=$learnpath_id&amp;action=".$myaction."&amp;direction=up&amp;moduleid=".$parent_item_id."&amp;id=".$row['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9>"."<img src=\"../img/up.gif\" border=\"0\" title=\"$lang_move_up\">"."</a>"."</td>\n";
                        echo '  <td align="center">'."<a href='".api_get_self()."?lp_id=$learnpath_id&action=".$myaction."&direction=up&moduleid=".$parent_item_id."&id=".$row['id']."'>"."<img src=\"../img/up.gif\" border=\"0\" title=\"$lang_move_up\">"."</a>"."</td>\n";
                    } else {
                        echo '  <td align="center">&nbsp;</td>'."\n";
                    }

                    echo "  <td align='center'>&nbsp;</td>\n";
                    //echo "  <td align='center'>"."<a href='".api_get_self()."?lp_id=$learnpath_id&amp;action=editmodule&amp;id=".$row['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9>"."<img src=\"../img/edit.gif\" border=\"0\" title=\"$lang_edit_learnpath_module\">"."</a>"."</td>\n";
                    echo "  <td align='center'>"."<a href='".api_get_self()."?lp_id=$learnpath_id&action=edititem&id=".$row['id']."'>"."<img src=\"../img/edit.gif\" border=\"0\" title=\"$lang_edit_learnpath_module\">"."</a>"."</td>\n";

                    //echo "  <td align='center'>"."<a href='".api_get_self()."?lp_id=$learnpath_id&amp;action=deletemodule&amp;id=".$row['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9 onclick=\"javascript: return confirmation('".$row['chapter_name']."');\">"."<img src=\"../img/delete.gif\" border=\"0\" title=\"$lang_delete_learnpath_module\">"."</a>"."</td>\n";
                    echo "  <td align='center'>"."<a href='".api_get_self()."?lp_id=$learnpath_id&action=delete_item&id=".$row['id']."' onclick=\"javascript: return confirmation('".$row['title']."');\">"."<img src=\"../img/delete.gif\" border=\"0\" title=\"$lang_delete_learnpath_module\">"."</a>"."</td>\n";
                }

                echo "</tr>\n";
                $i ++;

                $xml_output .= "<items>";

                //display_learnpath_items($row['id']);
                display_learnpath_chapters($row['id'], $tree, $level + 1);

                $xml_output .= "</items>";
                $xml_output .= "</chapter>";

            } else //if //($row['item_type'] === 'item')
            {
                $row_items = $row;
                echo "<tr>\n  <td colspan='2' valign='top'>";
                //require 'resourcelinker.inc.php';
                display_addedresource_link_in_learnpath($row_items['item_type'], $row_items['ref'], '', $row_items['id'], 'builder', 'icon', $level);

                if ($row_items['description']) {
                    echo "<div align='justify'>&nbsp;&nbsp;&nbsp;{$row_items['description']}";
                }

                echo "</td>";

                if (is_prereq($learnpath_id)) {
                    echo '<td bgcolor="#EEEEFF">';
                } else {
                    echo "<td>";
                }

                if (api_is_allowed_to_edit()) {

                    if ($row_items['prerequisite'] != '') {
                        $prereq = $row_items['prerequisite'];

                        //if ($row_items['prereq_type'] == 'i') {
                            // item
                            $sql_items2 = "SELECT * FROM $tbl_lp_item WHERE id='$prereq'"; // Check if prereq has been deleted.
                            $result_items2 = Database::query($sql_items2);
                            $number_items2 = Database::num_rows($result_items2);
                            if ($number_items2 == 0) {
                                echo get_lang('PrerequisiteDeletedError');
                            }
                            $row_items2 = Database::fetch_array($result_items2);
                            display_addedresource_link_in_learnpath($row_items2['item_type'], $row_items2['ref'], '', $row_items2['id'], 'builder', '', 0);
                            if ((($row_items2['item_type'] == TOOL_QUIZ) or ($row_items2['item_type'] == 'HotPotatoes')) and ($row_items['prerequisite'])) {
                                //echo "&nbsp;({$row_items2['title']})";
                            }
                        //}
                        /*
                        if ($row_items['prereq_type'] == 'c') {
                            // chapter
                            $sql_items2 = "SELECT * FROM $tbl_lp_item WHERE id='$prereq' AND item_type='dokeos_chapter'"; // Check if prereq has been deleted.
                            $result_items2 = Database::query($sql_items2);
                            $number_items2 = Database::num_rows($result_items2);
                            if ($number_items2 == 0) {
                                echo "<font color='red'>$lang_prereq_deleted_error</font>";
                            }
                            $row_items2 = Database::fetch_array($result_items2);
                            echo " {$row_items2['title']}";
                        }*/
                    }
                    echo "</font></td>";
                    $xml_output .= "<element_type>".$row_items['item_type']."</element_type>";
                    $xml_output .= "<element_id>".$row_items['item_id']."</element_id>";

                    // Move
                    if ($i < $num_modules) {
                        echo "<td align='center'>"."<a href='".api_get_self()."?lp_id=$learnpath_id&amp;action=moveitem&amp;type=item&amp;direction=down&amp;moduleid=".$parent_item_id."&amp;id=".$row_items['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9>"."<img src=\"../img/down.gif\" border=\"0\" title=\"$lang_move_down\">"."</a>"."</td>";
                    } else {
                        echo "<td width='30' align='center'>&nbsp;</td>";
                    }

                    if ($i > 1) {
                        echo "<td align='center'>"."<a href='".api_get_self()."?lp_id=$learnpath_id&amp;action=moveitem&amp;type=item&amp;direction=up&amp;moduleid=".$parent_item_id."&amp;id=".$row_items['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9>"."<img src=\"../img/up.gif\" border=\"0\" title=\"$lang_move_up\">"."</a>";
                    } else {
                        echo "<td width='30' align='center'>&nbsp;</td>";
                    }
                    echo "</td>"."<td align='center'>";

                    // Edit prereq
                    echo "<a href='".api_get_self()."?lp_id=$learnpath_id&amp;action=edititemprereq&amp;id=".$row_items['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9>"."<img src=\"../img/scormpre.gif\" border=\"0\" title=\"$lang_add_prereq\">"."</a>"."</td>";

                    // Edit
                    echo "<td align='center'>"."<a href='".api_get_self()."?lp_id=$learnpath_id&amp;action=edititem&amp;id=".$row_items['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9>"."<img src=\"../img/edit.gif\" border=\"0\" title=\"$lang_edit_learnpath_item\">"."</a>"."</td>";

                    // Delete
                    echo "<td align='center'>"."<a href='".api_get_self()."?lp_id=$learnpath_id&action=deleteitem&id=".$row_items['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9>"."<img src=\"../img/delete.gif\" border=\"0\" title=\"$lang_delete_learnpath_item\" onclick=\"javascript: return confirmation('".$row_items['item_type']."');\">"."</a>";
                }
                $i ++;
                echo "</td></tr>";
            }
        }
    }
}


/**
 * Displays the learning path items/steps.
 * @param		integer		Category ID
 * @return	void
 * @todo eliminate all global $lang declarations, use get_lang, improve structure.
 */
function display_learnpath_items($categoryid) {
    global $xml_output;
    global $lang_prerequisites, $lang_move_down, $lang_move_up, $lang_edit_learnpath_item, $lang_delete_learnpath_item, $learnpath_id, $lang_add_prereq, $lang_prereq_deleted_error, $lang_pre_short, $langThisItem;
    $course_id = api_get_course_int_id();
    $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);

    $sql_items = "SELECT * FROM $tbl_lp_item WHERE c_id = $course_id AND parent_item_id='$categoryid' ORDER BY display_order ASC";
    $result_items = Database::query($sql_items);
    $number_items = Database::num_rows($result_items);
    $i = 1;

    while ($row_items = Database::fetch_array($result_items)) {
        echo "<tr><td colspan='2' valign='top'>";
        display_addedresource_link_in_learnpath($row_items['item_type'], $row_items['ref'], '', $row_items['id'], 'builder', 'icon');
        if ($row_items['description']) {
            echo "<div align='justify'><font color='#999999'>&nbsp;&nbsp;&nbsp;{$row_items['description']}</font>";
        }
        echo "</td>";
        if (is_prereq($learnpath_id)) {
            echo '<td bgcolor="#EEEEFF">';
        } else {
            echo "<td>";
        }

        if (api_is_allowed_to_edit()) {
            //error_log('Is allowed to edit item'.$row_items['id'], 0);
            // TODO: Fix by adding true prerequisites parsing (and cycle through).
            // Over simplification here, we transform prereq_id field into prerequisite field.
            if ($row_items['prerequisite'] != '') {
                $prereq = $row_items['prerequisite'];

                //if ($row_items['prereq_type'] == 'i') {
                    // item
                    $sql_items2 = "SELECT * FROM $tbl_lp_item WHERE c_id = $course_id AND id='$prereq'"; // Check if prereq has been deleted.
                    $result_items2 = Database::query($sql_items2);
                    $number_items2 = Database::num_rows($result_items2);
                    if ($number_items2 == 0) {
                        echo "<font color=red>$lang_prereq_deleted_error</font>";
                    }
                    $row_items2 = Database::fetch_array($result_items2);
                    display_addedresource_link_in_learnpath($row_items2['item_type'], $row_items2['ref'], '', $row_items2['id'], 'builder', '');
                    if ((($row_items2['item_type'] == 'Exercise') or ($row_items2['item_type'] == 'HotPotatoes')) and ($row_items['prerequisites'])) {
                        echo "&nbsp;({$row_items2['title']})";
                    }
                //}
                /*if ($row_items['prereq_type'] == 'c') {
                    // chapter
                    $sql_items2 = "SELECT * FROM $tbl_learnpath_chapter WHERE id='$prereq'"; //check if prereq has been deleted
                    $result_items2 = Database::query($sql_items2);
                    $number_items2 = Database::num_rows($result_items2);
                    if ($number_items2 == 0) {
                        echo "<font color=red>$lang_prereq_deleted_error</font>";
                    }
                    $row_items2 = Database::fetch_array($result_items2);
                    echo " {$row_items2['chapter_name']}";
                }*/
            }
            echo "</font></td>";
            $xml_output .= "<element_type>".$row_items['item_type']."</element_type>";
            $xml_output .= "<element_id>".$row_items['id']."</element_id>";

            // Move
            if ($i < $number_items) {
                echo "<td align='center'><a href='".api_get_self()."?lp_id=$learnpath_id&action=moveitem&direction=down&moduleid=".$categoryid."&id=".$row_items['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9><img src=\"../img/down.gif\" border=\"0\" title=\"$lang_move_down\"></a></td>";
            } else {
                echo "<td width='30' align='center'>&nbsp;</td>";
            }

            if ($i > 1) {
                echo "<td align='center'><a href='".api_get_self()."?lp_id=$learnpath_id&action=moveitem&direction=up&moduleid=".$categoryid."&id=".$row_items['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9><img src=\"../img/up.gif\" border=\"0\" title=\"$lang_move_up\"></a>";
            } else {
                echo "<td width='30' align='center'>&nbsp;</td>";
            }
            echo "</td><td align='center'>";

            // Edit prereq
            echo "<a href='".api_get_self()."?lp_id=$learnpath_id&action=edititemprereq&id=".$row_items['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9><img src=\"../img/scormpre.gif\" border=\"0\" title=\"$lang_add_prereq\"></a></td>";

            // Edit
            echo "<td align='center'><a href='".api_get_self()."?lp_id=$learnpath_id&action=edititem&id=".$row_items['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9><img src=\"../img/edit.gif\" border=\"0\" title=\"$lang_edit_learnpath_item\"></a></td>";

            // Delete
            echo "<td align='center'>";
            echo "<a href='".api_get_self()."?lp_id=$learnpath_id&action=deleteitem&id=".$row_items['id']."'&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9><img src=\"../img/delete.gif\" border=\"0\" title=\"$lang_delete_learnpath_item\" onclick=\"javascript: return confirmation('".$langThisItem."');\"></a>";
        }
        $i ++;
        echo "</td></tr>";
    }
}

/**
 * This function returns the items belonging to the chapter that contains the given item (brother items)
 * @param	integer	Item id
 * @return	array		Table containing the items
 */
function learnpath_items($itemid) {
    global $xml_output;
    $tbl_learnpath_item = Database :: get_course_table(TABLE_LEARNPATH_ITEM);
    $course_id = api_get_course_int_id();
    

    $sql_items = "SELECT parent_item_id FROM $tbl_lp_item WHERE c_id = $course_id AND id='$itemid'";
    $moduleid_sql = Database::query($sql_items);
    $moduleid_array = Database::fetch_array($moduleid_sql); // First row of the results.
    $moduleid = $moduleid_array['parent_item_id'];

    $sql_items = "SELECT * FROM $tbl_lp_item WHERE c_id = $course_id AND parent_item_id='$moduleid' ORDER BY display_order ASC";
    $result_items = Database::query($sql_items);
    $ar = Database::fetch_array($result_items);
    while ($ar != '') {
        $result[] = $ar;
        $ar = Database::fetch_array($result_items);
    }
    return $result;
}

/**
 * This function returns the chapters belonging to the path that contais the given chapter (brother chapters)
 * @param	integer	Learnpath id
 * @return array		Table containing the chapters
 */
function learnpath_chapters($learnpath_id) {
    global $xml_output, $learnpath_id;    
    $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
    $course_id = api_get_course_int_id();
    

    $sql_items = "SELECT * FROM $tbl_lp_item WHERE c_id = $course_id AND lp_id='$learnpath_id' AND item_type='dokeos_chapter' ORDER BY display_order ASC";
    //$sql_items = "SELECT * FROM $tbl_learnpath_chapter WHERE lp_id='$learnpath_id' ORDER BY display_order ASC";
    $result_items = Database::query($sql_items);
    $ar = Database::fetch_array($result_items);
    while ($ar != '') {
        $result[] = $ar;
        $ar = Database::fetch_array($result_items);
    }
    return $result;
}

/**
 * This function tells if a learnpath contains items which are prerequisite to other items
 * @param	integer	Learnpath id
 * @return	boolean	True if this learnpath contains an item which is a prerequisite to something
 */
function is_prereq($learnpath_id) {
    global $xml_output;
    $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
    $course_id = api_get_course_int_id();

    $prereq = false;

    $sql_items = "SELECT * FROM $tbl_lp_item WHERE c_id = $course_id AND lp_id='$learnpath_id' AND parent_item_id=0 ORDER BY display_order ASC";
    $result_items = Database::query($sql_items);
    while ($ar = Database::fetch_array($result_items)) {
        $c = $ar['id'];
        $sql_items2 = "SELECT * FROM $tbl_lp_item WHERE c_id = $course_id AND lp_id = $learnpath_id AND parent_item_id='$c' ORDER BY display_order ASC";
        $result_items2 = Database::query($sql_items2);
        while ($ar2 = Database::fetch_array($result_items2)) {
            if ($ar2['prerequisite'] != '') {
                $prereq = true;
            }
        }
    }
    return ($prereq);
}

/**
 * This function returns the prerequisite sentence
 * @param	integer	Item ID
 * @return	string 	Prerequisite warning text
 */
function prereqcheck($id_in_path) {
    // 1. Initialise and import working vars.
    global $learnpath_id, $_user;
    global $langPrereqToEnter, $langPrereqTestLimit1, $langPrereqTestLimit2, $langPrereqTestLimitNow, $langPrereqFirstNeedTo, $langPrereqModuleMinimum1, $langPrereqModuleMinimum2;
    $tbl_learnpath_user = Database :: get_course_table(TABLE_LEARNPATH_USER);
    $tbl_learnpath_item = Database :: get_course_table(TABLE_LEARNPATH_ITEM);
    $tbl_learnpath_chapter = Database :: get_course_table(TABLE_LEARNPATH_CHAPTER);
    
    $course_id = api_get_course_int_id();

    // 2. Initialise return value.
    $prereq = false;

    // 3. Get item data from the database.
    $sql_items = "SELECT * FROM $tbl_learnpath_item WHERE c_id = $course_id AND id='$id_in_path'";
    $result_items = Database::query($sql_items);
    $row = Database::fetch_array($result_items);
    // 4. Check prerequisite's type.
    if ($row['prereq_type'] == 'i') {
        // 4.a If prerequisite is of type 'i' (item):
        // 4.a.1 Get data ready for use.
        $id_in_path3 = $row['prereq_id'];
        $prereq_limit = $row['prereq_completion_limit'];

        // 4.a.2 Get data from the user-item relation.
        if ($_user['user_id'] == '') {
            $user_id = '0';
        } else {
            $user_id = $_user['user_id'];
        }
        $sql_items3 = "SELECT * FROM $tbl_learnpath_user WHERE c_id = $course_id AND (learnpath_item_id='$id_in_path3' and user_id=$user_id)";
        $result_items3 = Database::query($sql_items3);
        $row3 = Database::fetch_array($result_items3);

        // 4.a.3 Get the link that needs to be shown for the current item (not the prereq)
        $stepname = display_addedresource_link_in_learnpath($row['item_type'], $row['ref'], '', $id_in_path, 'builder', 'nolink');
        // This is the step we want to open.
        $stepname = trim($stepname); // Removing occasional line breaks and white spaces

        // 4.a.4 Get the prerequisite item.
        $sql6 = "SELECT * FROM $tbl_learnpath_item WHERE (id='$id_in_path3')";
        $result6 = Database::query($sql6);
        $row6 = Database::fetch_array($result6);
        // 4.a.5 Get a link to the prerequisite item.
        $prereqname = display_addedresource_link_in_learnpath($row6['item_type'], $row6['ref'], '', $id_in_path3, 'builder', 'nolink'); //this is the prereq of the step we want to open

        // 4.a.5 Initialise limit value.
        $limitok = true;
        // 4.a.6 Get prerequisite limit.
        if ($prereq_limit) {
            // 4.a.6.a If the completion limit exists.
            if ($row3['score'] < $prereq_limit) {
                // 4.a.6.a.a If the completion limit hasn't been reached, then display the corresponding message.
                $prereq = $langPrereqToEnter.$stepname.$langPrereqTestLimit1."$prereq_limit".$langPrereqTestLimit2.$prereqname.". (".$langPrereqTestLimitNow.$row3['score'].")";
            } else {
                // 4.a.6.a.b The completion limit has been reached. Prepare to return false (no prereq hanging).
                $prereq = false;
            }
        } else {
            // 4.a.6.b If the completion limit doesn't exist.
            if ($row3['status'] == 'completed' or $row3['status'] == 'passed') {
                // 4.a.6.b.a If the prerequisite status is 'completed'.
                $prereq = false;
            } else {
                // 4.a.6.b.b The prerequisite status is not 'completed', return corresponding message.
                $prereq = $langPrereqToEnter.$stepname.$langPrereqFirstNeedTo.$prereqname.'.';
            }
        }
    } elseif ($row['prereq_type'] == 'c') {
        // 4.b If prerequisite is of type 'c' (chapter).
        // 4.b.1 Get data ready to use.
        $id_in_path2 = $row['prereq_id'];
        // 4.b.2 Get all items in the prerequisite chapter.
        $sql_items3 = "SELECT * FROM $tbl_lp_item WHERE c_id = $course_id AND parent_item_id='$id_in_path2'";
        $result_items3 = Database::query($sql_items3);
        $allcompleted = true;
        while ($row3 = Database::fetch_array($result_items3)) {
            // 4.b.3 Cycle through items in the prerequisite chapter.
            // 4.b.3.1 Get data ready to use.
            $id_in_path4 = $row3['id'];
            if ($_user['user_id'] == '') {
                $user_id = '0';
            } else {
                $user_id = $_user['user_id'];
            }
            // 4.b.3.2 Get user-item relation.
            $sql_items4 = "SELECT * FROM $tbl_learnpath_user WHERE c_id = $course_id AND (learnpath_item_id='$id_in_path4' and user_id=$user_id)";
            $result_items4 = Database::query($sql_items4);
            $row4 = Database::fetch_array($result_items4);
            // 4.b.3.3 If any of these elements is not 'completed', the overall completion status is false.
            if ($row4['status'] != 'completed' and $row4['status'] != 'passed') {
                $allcompleted = false;
            }
        }
        if ($allcompleted) {
            // 4.b.4.a All items were completed, prepare to return that there is no prerequisite blocking the way.
            $prereq = false;
        } else {
            // 4.b.4.b Something was not completed. Return corresponding message.
            $sql5 = "SELECT * FROM $tbl_learnpath_chapter WHERE c_id = $course_id AND (lp_id='$learnpath_id' and id='$id_in_path2')";
            $result5 = Database::query($sql5);
            $row5 = Database::fetch_array($result5);
            $prereqmodulename = trim($row5['chapter_name']);
            $prereq = $langPrereqModuleMinimum1.$prereqmodulename.$langPrereqModuleMinimum2;
        }
    } else {
        // 5. If prerequisite type undefined, no prereq.
        $prereq = false;
    }
    // 6. Return the message (or false if no prerequisite waiting).
    return ($prereq);
}

/**
 * Constructs the tree that will be used to build the learnpath structure
 * @params  integer     Learnpath_id
 * @return  array       Tree of the learnpath structure
 * @author  Yannick Warnier <yannick.warnier@beeznest.com>
 * @comment This is a temporary function, which exists while the chapters and items
 *          are still in separate tables in the database. This function gathers the data in a unique tree.
 */
function get_learnpath_tree($learnpath_id) {
    //error_log('New LP - In learnpath_functions::get_learnpath_tree', 0);
    // Init elems
    //global $tbl_learnpath_item, $tbl_learnpath_chapter;
    /*
    $tbl_learnpath_item = Database :: get_course_table(TABLE_LEARNPATH_ITEM);
    $tbl_learnpath_chapter = Database :: get_course_table(TABLE_LEARNPATH_CHAPTER);
    */
    $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);

    $tree = array();
    $chapters = array();    
    $all_items_by_chapter = array();
    $course_id = api_get_course_int_id();
    
    $sql = "SELECT * FROM $tbl_lp_item WHERE c_id = $course_id AND lp_id = ".$learnpath_id." AND item_type='dokeos_chapter' ORDER BY display_order";
    //error_log('New LP - learnpath_functions - get_learnpath_tree: '.$sql,0);
    $res = Database::query($sql);
    // Format the $chapters_by_parent array so we have a suitable structure to work with.
    while ($row = Database::fetch_array($res)) {
        $chapters[] = $row;
        // Shouldn't be necessary (check no null value).
        if (empty ($row['parent_item_id'])) {
            $row['parent_item_id'] = 0;
        }
        //$chapters_by_parent[$row['parent_item_id']][$row['previous_item_id']] = $row;
        $all_items_by_chapter[$row['parent_item_id']][$row['display_order']] = $row;
        $all_items_by_chapter[$row['parent_item_id']][$row['display_order']]['type'] = 'dokeos_chapter';
    }

    // Now for every item in each chapter, get a suitable structure too.
    foreach ($chapters as $row) {
        // Select items from this chapter.
        $sql = "SELECT * FROM $tbl_lp_item WHERE c_id = $course_id AND lp_id = $learnpath_id AND parent_item_id = ".$row['id']." ORDER BY display_order";
        //error_log('New LP - learnpath_functions - get_learnpath_tree: '.$sql, 0);
        $res = Database::query($sql);
        //error_log('New LP - learnpath_functions - get_learnpath_tree: Found '.Database::num_rows($res).' results', 0);
        while ($myrow = Database::fetch_array($res, 'ASSOC')) {
            //$items[] = $myrow;
            //$items_by_chapter[$myrow['parent_item_id']][$myrow['display_order']] = $myrow;
            $all_items_by_chapter[$row['id']][$myrow['display_order']] = $myrow;
            $all_items_by_chapter[$row['id']][$myrow['display_order']]['type'] = 'item';
        }
    }
    //array_multisort($all_items_by_chapter[0], SORT_ASC, SORT_NUMERIC);
    foreach ($all_items_by_chapter as $key => $subrow) {
        ksort($all_items_by_chapter[$key]);
    }

    //all items should now be well-ordered
    //error_log('New LP - In get_learnpath_tree, returning '.print_r($all_items_by_chapter,true), 0);
    return $all_items_by_chapter;
}

/**
 * Gives a list of sequencial elements IDs for next/previous actions
 * @param   array   The elements tree as returned by get_learnpath_tree()
 * @param   integer The chapter id to start from
 * @param   boolean Whether to include chapters or not
 * @return  array   List of elements in the first to last order
 * @author  Yannick Warnier <yannick.warnier@beeznest.com>
 */
function get_ordered_items_list($tree, $chapter = 0, $include_chapters = false) {
    $list = array ();
    foreach ($tree[$chapter] as $order => $elem) {
        if ($elem['type'] == 'chapter') {
            if ($include_chapters === true) {
                $list[] = array ('id' => $elem['id'], 'type' => $elem['type']);
            }
            $res = get_ordered_items_list($tree, $elem['id'], $include_chapters);
            foreach ($res as $elem) {
                $list[] = $elem;
            }
        } elseif ($elem['type'] == 'item') {
            $list[] = array ('id' => $elem['id'], 'type' => $elem['type'], 'item_type' => $elem['item_type'], 'parent_item_id' => $elem['parent_item_id'], 'item_id' => $elem['item_id']);
        }
    }
    return $list;
}

/**
 * Displays the structure of a chapter recursively. Takes the result of get_learnpath_tree as argument
 * @param	array		Chapter structure
 * @param	integer	Chapter ID (start point in the tree)
 * @param	integer	Learnpath ID
 * @param	integer	User ID
 * @param	boolean	Indicates if the style is wrapped (true) or extended (false)
 * @param	integer	Level reached so far in the tree depth (enables recursive behaviour)
 * @return	array		Number of items, Number of items completed
 * @author	Many changes by Yannick Warnier <yannick.warnier@beeznest.com>
 */
function display_toc_chapter_contents($tree, $parent_item_id = 0, $learnpath_id, $uid, $wrap, $level = 0) {
    //global $tbl_learnpath_user;
    $tbl_learnpath_user = Database :: get_course_table(TABLE_LEARNPATH_USER);
    $num = 0;
    $num_completed = 0;
    foreach ($tree[$parent_item_id] as $order => $elem) {

        $bold = false;
        if (!empty ($_SESSION['cur_open']) && ($elem['id'] == $_SESSION['cur_open'])) {
            $bold = true;
        }
        if ($elem['type'] === 'chapter') {
            if ($wrap) {
                echo str_repeat("&nbsp;&nbsp;", $level).shorten(strip_tags($elem['chapter_name']), (35 - 3 * $level))."<br />\n";
            } else {
                echo "<tr><td colspan='3'>".str_repeat("&nbsp;&nbsp;", $level).shorten($elem['chapter_name'], (35 - 3 * $level))."</td></tr>\n";
            }

            if ($wrap) {
                if ($elem['chapter_description'] != '') {
                    echo "<div class='description'>".str_repeat("&nbsp;&nbsp;", $level)."&nbsp;".shorten($elem['chapter_description'], (35 - 3 * $level))."</div>\n";
                }
            } else {
                if ($elem['chapter_description'] != '') {
                    echo "<tr><td colspan='3'><div class='description'>".str_repeat("&nbsp;&nbsp;", $level)."&nbsp;".shorten($elem['chapter_description'], (35 - 3 * $level))."</div></td></tr>\n";
                }
            }
            list ($a, $b) = display_toc_chapter_contents($tree, $elem['id'], $learnpath_id, $uid, $wrap, $level + 1);
            $num += $a;
            $num_completed += $b;

        } elseif ($elem['type'] === 'item') {
            // If this element is an item (understand: not a directory/module).
            $sql0 = "SELECT * FROM $tbl_learnpath_user WHERE (user_id='".$uid."' and learnpath_item_id='".$elem['id']."' and lp_id='".$learnpath_id."')";
            $result0 = Database::query($sql0);
            $row0 = Database::fetch_array($result0);

            $completed = '';
            if (($row0['status'] == 'completed') or ($row0['status'] == 'passed')) {
                $completed = 'completed';
                $num_completed ++;
            }

            if ($wrap) {
                echo str_repeat("&nbsp;&nbsp;", $level)."<a name='{$elem['id']}' />\n";
            } else {
                echo "<tr><td>".str_repeat("&nbsp;&nbsp;", $level-1)."<a name='{$elem['id']}' />\n";
            }

            if ($wrap) {
                $icon = 'wrap';
            }

            if ($bold) {
                echo "<b>";
            }
            display_addedresource_link_in_learnpath($elem['item_type'], $elem['ref'], $completed, $elem['id'], 'player', $icon);
            if ($bold) {
                echo "</b>";
            }
            if ($wrap) {
                echo "<br />\n";
            } else {
                echo "</td></tr>\n";
            }

            $num ++;
        }
    }
    return array ($num, $num_completed);
}

/**
 * Returns a string to display in the tracking frame within the contents.php page (for example)
 * @param   integer     Learnpath id
 * @param   integer     Current user id
 * @param   integer     Starting chapter id
 * @param   array       Tree of elements as returned by get_learnpath_tree()
 * @param   integer     Level of recursivity we have reached
 * @param   integer     Counter of elements already displayed
 * @author  Yannick Warnier <yannick.warnier@beeznest.com>
 * @deprecated this function seems to be unused
 * @note : forced display because of display_addedresource_link_in_learnpath behaviour (outputing a string would be better)
 */
function get_tracking_table($learnpath_id, $user_id, $parent_item_id = 0, $tree = false, $level = 0, $counter = 0) {
    $tbl_learnpath_chapter = Database :: get_course_learnpath_chapter_table();
    $tbl_learnpath_item = Database :: get_course_learnpath_item_table();
    $tbl_learnpath_user = Database :: get_course_learnpath_user_table();
    //$mytable = '';
    $include_chapters = true;

    if (!is_array($tree)) {
        // Get a tree of the current learnpath elements.
        $tree = get_learnpath_tree($learnpath_id);
    }
    foreach ($tree[$parent_item_id] as $order => $elem) {
        if (($counter % 2) == 0) {
            $oddclass = 'row_odd';
        } else {
            $oddclass = 'row_even';
        }

        if ($elem['type'] == 'chapter') {
            if ($include_chapters === true) {
                //$mytable .= "<tr class='$oddclass'><td colspan = '3'>".str_repeat('&nbsp;',$level*2+2).$elem['chapter_name']."</td></tr>\n";
                echo "<tr class='$oddclass'><td colspan = '3'>".str_repeat('&nbsp;', $level * 2 + 2).$elem['chapter_name']."</td></tr>\n";
            }
            $counter ++;
            //$mytable .= get_tracking_table($learnpath_id, $user_id, $elem['id'], $tree, $level + 1, $counter );
            get_tracking_table($learnpath_id, $user_id, $elem['id'], $tree, $level + 1, $counter);

        } elseif ($elem['type'] == 'item') {

            $sql = "SELECT * FROM $tbl_learnpath_user "."WHERE user_id = $user_id "."AND lp_id = $learnpath_id "."AND learnpath_item_id = ".$elem['id'];
            $res = Database::query($sql);
            $myrow = Database::fetch_array($res);

            if (($myrow['status'] == 'completed') || ($myrow['status'] == 'passed')) {
                $color = 'blue';
                $statusmessage = get_lang('Complete');
            } else {
                $color = 'black';
                $statusmessage = get_lang('Incomplete');
            }

            $link = get_addedresource_link_in_learnpath($elem['item_type'], $elem['id'], $elem['item_id']);
            //$link = display_addedresource_link_in_learnpath($elem['item_type'], $elem['id'], $row['status'], $elem['item_id'], 'player', 'none');

            //$mytable .= "<tr class='$oddclass'>"
            echo "<tr class='$oddclass'>"."<td class='mystatus'>".str_repeat("&nbsp;", $level * 2 + 2);
            //."<a href='$link?SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9' target='toc'>hop</a>"
            display_addedresource_link_in_learnpath($elem['item_type'], $elem['ref'], $myrow['status'], $elem['id'], 'player', 'wrap');
            //we should also add the total score here
            echo "<td>"."<font color='$color'><div class='mystatus'>".$statusmessage."</div></font>"."</td>"."<td>"."<div class='mystatus' align='center'>". ($myrow['score'] == 0 ? '-' : $myrow['score'])."</div>"."</td>"."</tr>\n";
            $counter ++;
        }
    }
    //return $mytable;
    return true;
}

/**
 * This function returns false if there is at least one item in the path
 * @param	Learnpath ID
 * @return	boolean	True if nothing was found, false otherwise
 */
function is_empty($id) {
    $tbl_learnpath_item = Database :: get_course_table(TABLE_LEARNPATH_ITEM);
    $tbl_learnpath_chapter = Database :: get_course_table(TABLE_LEARNPATH_CHAPTER);
    $course_id = api_get_course_int_id();

    $sql = "SELECT * FROM $tbl_learnpath_chapter WHERE c_id = $course_id AND lp_id=$id ORDER BY display_order ASC";
    $result = Database::query($sql);
    $num_modules = Database::num_rows($result);
    $empty = true;

    if ($num_modules != 0) {
        while ($row = Database::fetch_array($result)) {

            $num_items = 0;
            $parent_item_id = $row['id'];
            $sql2 = "SELECT * FROM $tbl_learnpath_item WHERE c_id = $course_id AND (parent_item_id=$parent_item_id) ORDER BY display_order ASC";
            $result2 = Database::query($sql2);
            $num_items = Database::num_rows($result2);
            if ($num_items > 0) {
                $empty = false;
            }
        }
    }

    return ($empty);
}

/**
 * This function writes $content to $filename
 * @param	string	Destination filename
 * @param	string	Learnpath name
 * @param	integer	Learnpath ID
 * @param	string	Content to write
 * @return	void
 */
function exporttofile($filename, $LPname, $LPid, $content) {

    global $circle1_files; // This keeps all the files which are exported [0]:filename [1]:LP name.
    // The $circle1_files variable is going to be used to a deep extent in the imsmanifest.xml.
    global $expdir;

    if (!$handle = fopen($expdir.'/'.$filename, 'w')) {
        echo "Cannot open file ($filename)";
    }
    if (fwrite($handle, $content) === false) {
        echo "Cannot write to file ($filename)";
        exit;
    }
    fclose($handle);

    $circle1_files[0][] = $filename;
    $circle1_files[1][] = $LPname;
    $circle1_files[2][] = $LPid;
}

/**
 * This function exports the given Chamilo test
 * @param	integer	Test ID
 * @return string 	The test itself as an HTML string
 */
function export_exercise($item_id) {

    global $expdir, $_course, $_configuration, $_SESSION, $_SERVER, $language_interface, $langExerciseNotFound, $langQuestion, $langOk, $origin, $questionNum;

    $exerciseId = $item_id;

    require_once '../exercice/exercise.class.php';
    require_once '../exercice/question.class.php';
    require_once '../exercice/answer.class.php';
    require_once '../exercice/exercise.lib.php';

    // Answer types
    define('UNIQUE_ANSWER', 1);
    define('MULTIPLE_ANSWER', 2);
    define('FILL_IN_BLANKS', 3);
    define('MATCHING', 4);
    define('FREE_ANSWER', 5);

    $TBL_EXERCISES = Database :: get_course_table(TABLE_QUIZ_TEST);

    /* Clears the exercise session */
    if (isset ($_SESSION['objExercise'])) {
        api_session_unregister('objExercise');
    }
    if (isset ($_SESSION['objQuestion'])) {
        api_session_unregister('objQuestion');
    }
    if (isset ($_SESSION['objAnswer'])) {
        api_session_unregister('objAnswer');
    }
    if (isset ($_SESSION['questionList'])) {
        api_session_unregister('questionList');
    }
    if (isset ($_SESSION['exerciseResult'])) {
        api_session_unregister('exerciseResult');
    }

    // If the object is not in the session:
    if (!isset ($_SESSION['objExercise'])) {
        // Construction of Exercise.
        $objExercise = new Exercise();

        $sql = "SELECT title,description,sound,type,random,active FROM $TBL_EXERCISES WHERE id='$exerciseId'";
        // If the specified exercise doesn't exist or is disabled:
        if (!$objExercise->read($exerciseId) || (!$objExercise->selectStatus() && !api_is_allowed_to_edit() && ($origin != 'learnpath'))) {
            die($langExerciseNotFound);
        }

        // Saves the object into the session.
        api_session_register('objExercise');
    }

    $exerciseTitle = $objExercise->selectTitle();
    $exerciseDescription = $objExercise->selectDescription();
    $exerciseSound = $objExercise->selectSound();
    $randomQuestions = $objExercise->isRandom();
    $exerciseType = $objExercise->selectType();

    if (!isset ($_SESSION['questionList'])) {
        // Selects the list of question ID.
        $questionList = $randomQuestions ? $objExercise->selectRandomList() : $objExercise->selectQuestionList();

        // Saves the question list into the session.
        api_session_register('questionList');
    }

    $nbrQuestions = sizeof($questionList);

    // If questionNum comes from POST and not from GET:
    if (!$questionNum || $_POST['questionNum']) {
        // Only used for sequential exercises (see $exerciseType).
        if (!$questionNum) {
            $questionNum = 1;
        } else {
            $questionNum ++;
        }
    }

    $exerciseTitle = text_filter($exerciseTitle);

    $test .= "<h3>".$exerciseTitle."</h3>";

    if (!empty ($exerciseSound)) {
        $test .= "<a href=\"../document/download.php?doc_url=%2Faudio%2F".$exerciseSound."\"&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9 target=\"_blank\"><img src=\"../img/sound.gif\" border=\"0\" align=\"absmiddle\" alt=".get_lang("Sound")."\" /></a>";
    }

    $exerciseDescription = text_filter($exerciseDescription);

    // Writing the .js file with to check the correct answers begin.
    $scriptfilename = "Exercice".$item_id.".js";
    $s = "<script type=\"text/javascript\" src='../js/".$scriptfilename."'></script>";
    $test .= $s;

    $content = "function evaluate() {
        alert('Test evaluated.');
        }
        ";

    if (!$handle = fopen($expdir.'/js/'.$scriptfilename, 'w')) {
        echo "Cannot open file ($scriptfilename)";
    }
    if (fwrite($handle, $content) === false) {
        echo "Cannot write to file ($filename)";
        exit;
    }
    fclose($handle);

    // Writing the .js file with to check the correct answers end.
    $s = "
        <p>$exerciseDescription</p>
        <table width='100%' border='0' cellpadding='1' cellspacing='0'>
         <form method='post' action=''><input type=\"hidden\" name=\"SQMSESSID\" value=\"36812c2dea7d8d6e708d5e6a2f09b0b9\" />
         <input type='hidden' name='formSent' value='1' />
         <input type='hidden' name='exerciseType' value='".$exerciseType."' />
         <input type='hidden' name='questionNum' value='".$questionNum."' />
         <input type='hidden' name='nbrQuestions' value='".$nbrQuestions."' />
         <tr>
          <td>
          <table width='100%' cellpadding='4' cellspacing='2' border='0'>";

    $exerciseType = 1; // So to list all questions in one page.
    $test .= $s;

    $i = 0;

    foreach ($questionList as $questionId) {
        $i ++;

        // For sequential exercises.
        if ($exerciseType == 2) {
            // If it is not the right question, goes to the next loop iteration.
            if ($questionNum != $i) {
                continue;
            } else {
                // if the user has already answered this question:
                if (isset ($exerciseResult[$questionId])) {
                    // Construction of the Question object.
                    $objQuestionTmp = new Question();

                    // Reads question informations.
                    $objQuestionTmp->read($questionId);

                    $questionName = $objQuestionTmp->selectTitle();

                    // Destruction of the Question object.
                    unset ($objQuestionTmp);

                    $test .= '<tr><td>'.get_lang('AlreadyAnswered').' &quot;'.$questionName.'&quot;</td></tr>';

                    break;
                }
            }
        }

        echo $s = "<tr bgcolor='#e6e6e6'><td valign='top' colspan='2'>".get_lang('Question')." ";
        // Call the showQuestion() function from exercise.lib.php. This basically displays the question in a table.
        $test .= showQuestion($questionId, false, 'export', $i);

    } // end foreach()

    $s = "</table></td></tr><tr><td><br/><input type='button' value='".$langOk."' onclick=\"javascript: evaluate(); alert('Evaluated.');\">";
    $s .= "</td></tr></form></table>";
    $s .= "<script type='text/javascript'> loadPage(); </script>";
    $b = 2;
    $test .= $s;
    return ($test);
}

/**
 * This function exports the given item
 * @param	integer	Id from learnpath_items table
 * @param	integer	Item id
 * @param	string	Itm type
 * @param	boolean	Shall the SCORM communications features be added? (true). Default: false.
 * @return	void (outputs a zip file)
 * @todo	Try using the SCORM communications addition (adding a button and several javascript calls to the SCORM API) elsewhere than just in the export feature, so it doesn't look like an incoherent feature
 */

function exportitem($id, $item_id, $item_type, $add_scorm_communications = false) {
    $course_id = api_get_course_int_id();

    global $circle1_files, $expdir, $_course, $_SESSION, $GLOBALS;
    global $timeNoSecFormat, $dateFormatLong, $language_interface, $langPubl, $langDone, $langThisCourseDescriptionIsEmpty, $lang_course_description, $lang_introduction_text, $_cid, $langHotPotatoesFinished, $lang_author, $lang_date, $lang_groups, $lang_users, $lang_ass, $lang_dropbox, $test, $langQuestion;

    $libp = api_get_path(SYS_CODE_PAH);
    include_once $libp.'exercice/exercise.class.php';
    include_once $libp.'question.class.php';
    include_once $libp.'answer.class.php';
    include_once $libp.'exercise.lib.php';

    $langLasting = '';//avoid code parser warning
    include_once $libp.'lang/english/announcements.inc.php'; //this line is here only for $langPubl in announcements
    include_once $libp.'lang/'.$language_interface.'/announcements.inc.php'; //this line is here only for $langPubl in announcements
    include_once $libp.'lang/english/agenda.inc.php'; //this line is here only for $langLasting
    include_once $libp.'lang/'.$language_interface.'/agenda.inc.php'; //this line is here only for $langLasting
    include_once $libp.'lang/english/course_description.inc.php'; //this line is here only for $langThisCourseDescriptionIsEmpty
    include_once $libp.'lang/'.$language_interface.'/course_description.inc.php'; //				 -||-
    include_once $libp.'lang/english/resourcelinker.inc.php';
    include_once $libp.'lang/'.$language_interface.'/resourcelinker.inc.php';
    include_once $libp.'lang/english/learnpath.inc.php';
    include_once $libp.'lang/'.$language_interface.'/learnpath.inc.php';
    include_once $libp.'lang/english/exercice.inc.php';
    include_once $libp.'lang/'.$language_interface.'/exercice.inc.php';

    include_once '../resourcelinker/resourcelinker.inc.php';

    $LPname = display_addedresource_link_in_learnpath($item_type, $item_id, '', $id, 'builder', 'nolink');

    $expcontent = "<!--
        This is an exported file from Chamilo Learning Path belonging to a Scorm compliant content package.
        Do not modify or replace individually.

        Export module author : Denes Nagy <darkden@evk.bke.hu>

        -->

        ";
    // Files needed for communicating with the scos.
    $scocomfiles = "<script type='text/javascript' src='../js/APIWrapper.js'></script>"."<script type='text/javascript' src='../js/SCOFunctions.js'></script>";
    $expcontent .= '<html><head><link rel="stylesheet" href="../css/default.css" type="text/css" media="screen,projection" />'.$scocomfiles.'</head><body>';

    $donebutton .= "<script type='text/javascript'>
            /* <![CDATA[ */
            loadPage();
            var   studentName = '!';
            var   lmsStudentName = doLMSGetValue(  'cmi.core.student_name' );
            if ( lmsStudentName  != '' )
            {
               studentName = ' ' + lmsStudentName +   '!';
            }
            /* ]]> */
            </script>
            <br /><br />
            <form><input type=\"hidden\" name=\"SQMSESSID\" value=\"36812c2dea7d8d6e708d5e6a2f09b0b9\" />
                <table cols='3'	width='100%' align='center'>
                    <tr>
                    <td	align='middle'><input type = 'button' value	= '  ".$langDone."  ' onclick = \"javascript: doQuit('completed');\" id='button2' name='button2'></td>
                    </tr>
                </table>
            </form>";

    /**
     * Switch between the different element types, namely:
     * - Agenda
     * - Ad_Valvas
     * - Course_description
     * - Document
     * - Introduction_text
     * - HotPotatoes
     * - Exercise
     * - Post
     * - Forum          ]
     * - Thread         ]
     * - Dropbox        ]
     * - Assignments    ] These elements are all replaced by a simple message in the exported document.
     * - Groups         ]
     * - Users          ]
     * - Link _self
     * - Link _blank
     */
    switch ($item_type) {

        // AGENDA BEGIN
        case 'Agenda':
            // 1. Get agenda event data from the database table.
            $TABLEAGENDA = Database :: get_course_table(TABLE_AGENDA);
            $sql = "SELECT * FROM ".$TABLEAGENDA." where c_id = $course_id AND (id=$item_id)";
            $result = Database::query($sql);

            // 2. Prepare table output.
            $expcontent .= "<table class=\"data_table\" >";
            $barreMois = '';

            // 3. For each event corresponding to this agenda, do the following:
            while ($myrow = Database::fetch_array($result)) {
                $start_date_local = api_get_local_time($myrow['start_date'], null, date_default_timezone_get());
                //3.1 Make the blue month bar appear only once.
                if ($barreMois != api_format_date($start_date_local, "%m")) {
                    // 3.1.1 Update the check value for the month bar.
                    $barreMois = api_format_date($start_date_local, "%m");
                    // 3.1.2 Display the month bar.
                    $expcontent .= "<tr><td id=\"title\" colspan=\"2\" class=\"month\" valign=\"top\">".api_format_date($start_date_local, "%B %Y")."</td></tr>";
                }

                // 3.2 Display the agenda items (of this month): the date, hour and title.
                $db_date = (int) api_format_date($start_date_local, "%d");
                if ($_GET['day'] != $db_date) {
                    // 3.2.1.a If the day given in the URL (might not be set) is different from this element's day, use style 'data'.
                    $expcontent .= "<tr><td class=\"data\" colspan='2'>";
                } else {
                    // 3.2.1.b Else (same day) use style 'datanow'.
                    $expcontent .= "<tr><td class=\"datanow\" colspan='2'>";
                }
                // 3.2.2 Mark an anchor for this date.
                $expcontent .= "<a name=\"".$db_date."\"></a>"; // anchoring
                // 3.2.3 Write the date and time of this event to the export string.
                $expcontent .= api_format_date($start_date_local);
                // 3.2.4 If a duration is set, write it, otherwise ignore.
                if ($myrow['duration'] == '') {
                    $expcontent .= "<br />";
                } else {
                    $expcontent .= " / ".$langLasting." ".$myrow['duration']."<br />"; //langLasting comes from lang/x/agenda.inc.php
                }
                // 3.2.5 Write the title.
                $expcontent .= $myrow['title'];
                $expcontent .= "</td></tr>";
                // 3.2.6 Prepare the content of the agenda item.
                $content = $myrow['content'];
                // 3.2.7 Make clickable???
                $content = make_clickable($content);
                $content = text_filter($content);
                // 3.2.8 Write the prepared content to the export string.
                $expcontent .= "<tr><td class=\"text\" colspan='2'>";
                $expcontent .= $content;
                $expcontent .= "</td></tr>";

                // Displaying the agenda item of this month: the added resources.
                // This part is not included into LP export.
                /*if (check_added_resources("Agenda", $myrow['id'])) {
                    $content.= "<tr><td colspan='2'>";
                    $content.= "<i>".get_lang('AddedResources')."</i><br />";
                    display_added_resources("Agenda", $myrow['id']);
                    $content.= "</td></tr>";
                }*/

            }
            // 4. Finish the export string.
            $expcontent .= "<tr></table>";

            break;

        // ANNOUNCEMENT BEGIN
        case 'Ad_Valvas':
            // 1. Get the announcement data from the database
            $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
            $sql = "SELECT * FROM $tbl_announcement WHERE c_id = $course_id AND id='$item_id'";
            $result = Database::query($sql);

            // 2. Initialise export string
            $expcontent .= "<table class=\"data_table\">";

            // 3. For each announcement matching the query
            while ($myrow = Database::fetch_array($result)) {
                // 3.1 Get the __ field data.
                $content = $myrow[1];
                //$content = nl2br($content);
                // 3.2 Prepare the data for export.
                $content = make_clickable($content);
                $content = text_filter($content);

                // 3.3 Get a UNIX(?<-mktime) Timestamp of the end_date for this announcement.
                $last_post_datetime = $myrow['end_date']; // post time format  datetime of database layer (MySQL is assumed)
                list ($last_post_date, $last_post_time) = split(' ', $last_post_datetime);
                list ($year, $month, $day) = explode('-', $last_post_date);
                list ($hour, $min) = explode(':', $last_post_time);
                $announceDate = mktime($hour, $min, 0, $month, $day, $year);

                // 3.4 Compare the end date to the last login date of the user (mark it in red if he has not already read it).
                if ($announceDate > $_SESSION['user_last_login_datetime']) {
                    $colorBecauseNew = " color=\"red\" ";
                } else {
                    $colorBecauseNew = '  ';
                }

                // 3.5 Write this content to the export string (formatted HTML array).
                $expcontent .= "<tr>\n"."<td class=\"cell_header\">\n"."<font ".$colorBecauseNew.">".$langPubl." : ".api_convert_and_format_date($last_post_datetime, null, date_default_timezone_get())."</font>\n"."</td>\n"."</tr>\n"."<tr>\n"."<td>\n".$content."</td>\n"."</tr>\n";

            } // while loop

            // 4 Finish the export string.
            $expcontent .= "</table>";

            break;

        // Course_description BEGIN
        case 'Course_description':
            // 1. Get course description data from database.
            $tbl_course_description = Database :: get_course_table(TABLE_COURSE_DESCRIPTION);
            $result = Database::query("SELECT id, title, content FROM ".$tbl_course_description." WHERE c_id = $course_id  ORDER BY id");

            // 2. Check this element.
            if (Database::num_rows($result)) {
                // 2.a This course has one (or more) description in the database.
                $expcontent .= "<hr noshade=\"noshade\" size=\"1\" />";
                // 2.a.1 For each description available for this course.
                while ($row = Database::fetch_array($result)) {
                    // 2.a.1.1 Write title to export string.
                    $expcontent .= "<h4>".$row['title']."</h4>";
                    // 2.a.1.2 Prepare content.
                    $content = make_clickable(nl2br($row['content']));
                    $content = text_filter($content);
                    // 2.a.1.3 Write content to the export string.
                    $expcontent .= $content;
                }
            } else {
                // 2.b This course has no description available.
                $expcontent .= "<br /><h4>$langThisCourseDescriptionIsEmpty</h4>";
            }

            break;

        // DOCUMENT BEGIN
        case 'Document':
            // 1. Get the document data from the database.
            $tbl_document = Database::get_course_table(TABLE_DOCUMENT);
            $sql_query = "SELECT * FROM $tbl_document WHERE c_id = $course_id AND id=$item_id";
            $sql_result = Database::query($sql_query);
            $myrow = Database::fetch_array($sql_result);
            // 2. Get the origin path of the document to treat it internally.
            $orig = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$myrow['path'];
            // 3. Make some kind of strange transformation to get the destination filepath ???
            $pathname = explode('/', $myrow['path']);
            $last = count($pathname) - 1;
            $filename = 'data/'.$filename.$pathname[$last];
            $copyneeded = true;

            // Html files do not need to be copied as the ok button is inserted into them,
            // so don't copy directly.
            $extension = explode('.', $pathname[$last]);
            // This old condition was WRONG for names like design.html.old. Instead, we now get the extension
            // by using preg_match to match case-insensitive (probably faster than 4 conditions).
            //if (($extension[1]=='htm') or ($extension[1]=='html') or ($extension[1]=='HTM') or ($extension[1]=='HTML')) {
            // 4. Check the file extension.
            if (preg_match('/.*(\.htm(l)?)$/i', $pathname[$last])) {
                // 4.a If this file ends with ".htm(l)", we consider it's an HTML file.
                // src tag check begin
                // We now check if there is any src attribute in htm(l) files, if yes, we have to also export
                // the target file (swf, mp3, video,...) of that src tag.
                // In case of absolute links (http://) this is not neccessary, but makes no error.
                // However still missing : relative links case with subdirs -> the necessary dirs are not created in the exported package.

                // 4.a.1 Get the file contents into $file.
                $file = file_get_contents($orig);

                // 4.a.2 Get all the src links in this file.
                //preg_match_all("|((?i)src=\".*\" )|U",$file,$match);
                $match = GetSRCTags($orig);

                // 4.a.3 For each src tag found, do the following:
                for ($i = 0; $i < count($match); $i ++) {
                    // 4.a.3.1 Get the tag (split from the key).
                    list ($key, $srctag) = each($match);
                    $src = $srctag;

                    // 4.a.3.2 Check the link kind (web or absolute/relative).
                    if (stristr($src, 'http') === false) {
                        // 4.a.3.2.a Do something only if relative (otherwise the user will be able to see it too anyway).
                        // 4.a.3.2.a.1 Get a proper URL and remove all './'
                        $src = urldecode($src); //mp3
                        //$src=str_replace('./','',$src);
                        $src = preg_replace('/^\.\//', '', $src);
                        // 4.a.3.2.a.2 Remove the player link from the URL (only use the mp3 file).
                        $src = str_replace('mp3player.swf?son=', '', $src); //mp3
                        // 4.a.3.2.a.3 Remove funny link parts.
                        $src = str_replace('?0', '', $src); //mp3
                        // The previous lines are used when creating docs with Chamilo Document tool's htmlarea
                        // Rows marked by 'mp3' are needed because the mp3 plugin inserts the swf-mp3 links in a very strange way
                        // and we can decode them with those 3 lines, hoping this will not cause errors in case of other htmls,
                        // created by any other software.
                        // 4.a.3.2.a.4 Prepare source and destination paths.
                        $source = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.dirname($myrow['path']).'/'.$src;
                        $dest = $expdir.'/data/'.$src;
                        //CopyNCreate($source,$dest);
                        rcopy($source, $dest);
                    } //else...?
                }

                // src tag check end

                // sco communication insertion begin
                // 4.a.4 If we want to add SCORM actions and a "Done" button, do the following:
                if ($add_scorm_communications === true) {
                    if ($bodyclose = strpos($file, '</body>')) {
                        $file = substr_replace($file, $scocomfiles.$donebutton, $bodyclose, 7);
                    } elseif ($htmlclose = strpos($file, '</html>')) {
                        $file = substr_replace($file, $scocomfiles.$donebutton, $htmlclose, 7);
                        $file .= '</html>';
                    } else {
                        $file .= $scocomfiles.$donebutton;
                    }
                } //sco communication insertion end

                // 4.a.5 Replace the file's name by adding the element's ID before htm.
                // This will not work with uppercase HTML though. Maybe use the preg_replace syntax proposed...
                $filename = str_replace('.htm', $id.'.htm', $filename);
                //$filename=preg_replace('/.*(\.htm(l)?)$/i',$id.$1,$filename);
                // 4.a.6 Export these contents to a file and set the circle1_files array for later reuse.
                exporttofile($filename, $LPname, $id, $file);

                // The file has been copied, so ask not to copy it again.
                $copyneeded = false;

            } //if (htm(l) files) end

            // 5. If we still need to copy the file (e.g. it was not an HTML file), then copy and set circle1_files for later reuse.
            if ($copyneeded) {
                copy($orig, $expdir.'/'.$filename);
                $circle1_files[0][] = $filename;
                $circle1_files[1][] = $LPname;
                $circle1_files[2][] = $id;
            }

            //echo $orig;
            return;

        // Introduction_text BEGIN
        case 'Introduction_text':
            // 1 Get the introduction text data from the database.
            $TBL_INTRO = Database :: get_course_tool_intro_table();
            // Modified by Ivan Tcholakov, 15-SEP-2008.
            //$result = Database::query("SELECT * FROM ".$TBL_INTRO." WHERE id=1");
            $result = Database::query("SELECT * FROM ".$TBL_INTRO." WHERE c_id = $course_id AND id='course_homepage'");
            //
            $myrow = Database::fetch_array($result);
            $intro = $myrow['intro_text'];
            // 2 Write introduction text to the export string.
            $expcontent .= '<br />'.$intro;
            break;

        // HotPotatoes BEGIN
        case 'HotPotatoes':
            // 1. Get HotPotatoes data from the document table.
            $tbl_document = Database::get_course_table(TABLE_DOCUMENT);
            $result = Database::query("SELECT * FROM $tbl_document WHERE c_id = $course_id AND id=$item_id");
            $myrow = Database::fetch_array($result);
            // 2. Get the document path.
            $testfile = api_get_path(SYS_COURSE_PATH).$_course['path']."/document".urldecode($myrow['path']);
            // 3. Get the document contents into a string.
            $content = file_get_contents($testfile);
            // 4. Get the document filename (just the file, no path) - would probably be better to use PHP native function.
            $pathname = explode('/', $myrow['path']);
            $last = count($pathname) - 1;
            $filename = 'data/'.$filename.$pathname[$last];

            // 4beta - get all linked files and copy them (procedure copied from documents type).
            // Get all the src links in this file.
            $match = GetSRCTags($testfile);
            // For each src tag found, do the following:
            foreach ($match as $src) {
                //Check the link kind (web or absolute/relative)
                if (stristr($src, 'http') === false) {
                    // Do something only if relative (otherwise the user will be able to see it too anyway).
                    // Get a proper URL and remove all './'
                    $src = urldecode($src); //mp3
                    $src = str_replace('./', '', $src);
                    // Remove the player link from the URL (only use the mp3 file).
                    $src = str_replace('mp3player.swf?son=', '', $src); //mp3
                    // Remove funny link parts.
                    $src = str_replace('?0', '', $src); //mp3
                    // The previous lines are used when creating docs with Chamilo Document tool's htmlarea.
                    // Rows marked by 'mp3' are needed because the mp3 plugin inserts the swf-mp3 links in a very strange way
                    // and we can decode them with those 3 lines, hoping this will not cause errors in case of other htmls,
                    // created by any other software.
                    // Prepare source and destination paths.
                    $source = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.dirname($myrow['path']).'/'.$src;
                    $dest = $expdir.'/data/'.$src;
                    //CopyNCreate($source,$dest);
                    rcopy($source, $dest);
                } //else...?
            }

            // 5. Prepare the special "close window" for this test.
            $closewindow = "<html><head><link rel='stylesheet' type='text/css' href='../css/default.css'></head><body>"."<br /><div class='message'>$langHotPotatoesFinished</div></body></html>";

            // Finish is the function of HP to save scores, we insert our scorm function calls to its beginning
            // 'Score' is the variable that tracks the score in HP tests.
            // 6.
            $mit = "function Finish(){";

            $js_content = "var SaveScoreVariable = 0; // This variable is included by Chamilo LP export\n"."function mySaveScore() // This function is included by Chamilo LP export\n"."{\n"."   if (SaveScoreVariable==0)\n"."		{\n"."	   SaveScoreVariable = 1;\n".
                //the following function are implemented in SCOFunctions.js
    "      exitPageStatus = true;\n"."      computeTime();\n"."      doLMSSetValue( 'cmi.core.score.raw', Score );\n"."      doLMSSetValue( 'cmi.core.lesson_status', 'completed' );\n"."      doLMSCommit();\n"."      doLMSFinish();\n".
                //				"      document.write('".$closewindow."');\n".
        //if you insert the previous row, the test does not appear correctly !!!!
    "		}\n"."}\n"."function Finish(){\n"." mySaveScore();";

            $start = "<script type='text/javascript'> loadPage(); </script>";
            // 7. Replace the current MIT function call by our set of functions. In clear, transform HP to SCORM.
            $content = str_replace($mit, $js_content, $content);
            // 8. Finally, add the API loading calls (although that might have been done first).
            $content = str_replace("</script>", "</script>".$scocomfiles.$start, $content);

            // 9. Change the filename to add the database ID and export to a new file,
            // setting the circle1_files array for later reuse.
            $filename = str_replace('.htm', $id.'.htm', $filename);
            exporttofile($filename, $LPname, $id, $content);

            return;

        // Chamilo test BEGIN
        case 'Exercise':
            //1 Use the export_exercise() function to do the job of constructing the question's HTML table
            $expcontent .= export_exercise($item_id);
            break;

        // POST BEGIN
        case 'Post':
            // 1. Get the forum post data from the database.
            $tbl_posts =Database::get_course_table(TABLE_FORUM_POST);
            $tbl_posts_text =Database::get_course_table(TOOL_FORUM_POST_TEXT_TABLE);
            $result = Database::query("SELECT * FROM $tbl_posts where c_id = $course_id AND post_id=$item_id");
            $myrow = Database::fetch_array($result);
            // Grabbing the title of the post.
            $sql_titel = "SELECT * FROM $tbl_posts_text WHERE c_id = $course_id AND post_id=".$myrow['post_id'];
            $result_titel = Database::query($sql_titel);
            $myrow_titel = Database::fetch_array($result_titel);

            $posternom = $myrow['nom'];
            $posterprenom = $myrow['prenom'];
            $posttime = $myrow['post_time'];
            $posttext = $myrow_titel['post_text'];
            $posttitle = $myrow_titel['post_title'];
            $posttext = str_replace('"', "'", $posttext);

            //2 Export contents as an HTML table
            $expcontent .= "<table border='0' cellpadding='3' cellspacing='1' width='100%'>
                            <tr>
                                <td colspan='2' bgcolor='#e6e6e6'><b>$posttitle</b><br />$posttext</td>
                            </tr>
                            <tr>
                                <td colspan='2'></td>
                            </tr>
                            <tr>
                                <td bgcolor='#cccccc' align='left'>$lang_author : $posterprenom $posternom</td>
                                <td align='right' bgcolor='#cccccc'>$lang_date : $posttime</td>
                            </tr>
                            <tr><td colspan='2' height='10'></td></tr>
                        </table>";
            break;

        // NOT IMPLEMENTED ITEMS BEGIN
        case 'Forum':
        case 'Thread':
        case 'Dropbox':
        case 'Assignments':
        case 'Groups':
        case 'Users':
            // 1. Instead of building something, put an info message.
            $langItemMissing1 = "There was a ";
            $langItemMissing2 = "page (step) here in the original Chamilo Learning Path.";
            $expcontent .= "<div class='message'>$langItemMissing1 $item_type $langItemMissing2</div>";
            break;

        // Link BEGIN
        case 'Link _self':
        case 'Link _blank':
            // 1. Get the link data from the database.
            $TABLETOOLLINK = Database :: get_course_link_table();
            $result = Database::query("SELECT * FROM $TABLETOOLLINK WHERE c_id = $course_id AND id=$item_id");
            $myrow = Database::fetch_array($result);
            $thelink = $myrow['url'];
            // 2. Check the link type (open in blank page or in current page).
            if ($item_type == 'Link _blank')
            {
                $target = '_blank';
            }
            // 3. Write the link to the export string.
            $expcontent .= "<a href='$thelink?SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9' target='".$target."'>$LPname</a>";
            // 4. Change the element type for later changes (this is lost, however, so useless here).
            $item_type = 'Link'; // To put this to the filename.
            //$LPname="<a href='$thelink?SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9' target=".$target.">$LPname</a>";
            // I am still not sure about Link export : to export them as files or they can appear in the TOC at once ?
            // To enable the second possibility, unrem the row $LPname=...
            break;
    }

    // Now we add the Done button and the initialize function : loadpage()
    // not in the case of Documents, HotP
    if ($item_type != 'Exercise' and ($add_scorm_communications === true)) {
        $expcontent .= $donebutton;
    }
    // End the export string with valid HTML tags.
    $expcontent .= "</body></html>";

    // Prepare new file name.
    $filename = $item_type.$id.".htm";
    // Write the export content to the new file.
    exporttofile('data/'.$filename, $LPname, $id, $expcontent);
}

/**
 * This function exports the given item's description into a separate file
 * @param	integer	Item id
 * @param	string	Item type
 * @param	string	Description
 * @return void
 */
function exportdescription($id, $item_type, $description) {
    global $expdir;
    $filename = $item_type.$id.'.desc';
    $expcontent = $description;
    exporttofile($expdir.$filename, 'description_of_'.$item_type.$id, 'description_of_item_'.$id, $expcontent);
}

/**
 * This function deletes an entire directory
 * @param	string	The directory path
 * @return boolean	True on success, false on failure
 */
function deldir($dir) {
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if ($file != '.' && $file != '..') {
            $fullpath = $dir.'/'.$file;
            if (!is_dir($fullpath)) {
                unlink($fullpath);
            } else {
                deldir($fullpath);
            }
        }
    }

    closedir($dh);

    if (rmdir($dir)) {
        return true;
    }
    return false;
}

/**
 * This functions exports the given path. This is the opener function, which is called first
 * @deprecated this function is only called in the newscorm/scorm_admin.php which is deprecated
 * @param	integer 	The path id
 * @return	resource	A zip file, containing a hopefully Scorm compliant course made from the LP. This might happen when we don't actually exit the function first :-)
 */
function exportpath($learnpath_id) {
    // 1. Initialise variables.
    global $_course, $circle1_files, $LPnamesafe, $LPname, $expdir;
    $course_id = api_get_course_int_id();
    
    
    //$tbl_learnpath_main, $tbl_learnpath_chapter, $tbl_learnpath_item,
    $tbl_learnpath_main = Database :: get_course_table(TABLE_LEARNPATH_MAIN);
    $tbl_learnpath_item = Database :: get_course_table(TABLE_LEARNPATH_ITEM);
    $tbl_learnpath_chapter = Database :: get_course_table(TABLE_LEARNPATH_CHAPTER);

    // Where applicable, add a scorm "Done" button at the end of all contents.
    $add_scorm_button = true;

    // 2. Get the name of the LP.
    include_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
    $sql = "SELECT * FROM $tbl_learnpath_main WHERE c_id = $course_id AND (lp_id=$learnpath_id)";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    $LPname = $row['learnpath_name'];
    $LPnamesafe = replace_dangerous_char($LPname, 'strict');

    // 3. Get a temporary dir for creating the zip file.
    $expdir = api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/".$LPnamesafe;
    $fromdir = '../scorm/export/'; //this dir contains some standard files

    deldir($expdir); // Make sure the temp dir is cleared.
    mkdir($expdir, api_get_permissions_for_new_directories());
    mkdir($expdir.'/css', api_get_permissions_for_new_directories());
    mkdir($expdir.'/data', api_get_permissions_for_new_directories());
    mkdir($expdir.'/js', api_get_permissions_for_new_directories());
    mkdir($expdir.'/data/images', api_get_permissions_for_new_directories());
    mkdir($expdir.'/data/audio', api_get_permissions_for_new_directories());
    mkdir($expdir.'/data/videos', api_get_permissions_for_new_directories());

    $circle1 = array( // This array contains the types of elements we want to export.
        'Chapter', 'Agenda', 'Ad_Valvas', 'Course_description', 'Document', 'Introduction_text', 'Link _self', 'Link _blank', 'Forum', 'Thread', 'Post', 'Exercise', 'HotPotatoes', 'Assignments', 'Dropbox', 'Users', 'Groups');
    //$circle2 = array('');

    // 4. Get the first level chapters - YW added parent_item_id condition for multi-level paths.
    $sql = "SELECT * FROM $tbl_learnpath_chapter
            WHERE c_id = $course_id AND (lp_id=$learnpath_id and parent_item_id=0)
            ORDER BY display_order ASC";
    // To get all the elements, we should use the function that builds the table of content get_learnpath_tree.
    //WHERE (lp_id=$learnpath_id)
    //ORDER BY parent_item_id, display_order ASC";
    $result = Database::query($sql);

    // 5. export the items listed in Circle I one by one.
    while ($row = Database::fetch_array($result)) {
        // 5.1. Get items data from the database for this chapter.
        $parent_item_id = $row['id'];
        //$sql2a = "SELECT * FROM $tbl_learnpath_chapter WHERE (lp_id=$learnpath_id and parent_item_id=$parent_item_id) ORDER BY display_order ASC";
        //$result2a = Database::query($sql);
        $sql2b = "SELECT * FROM $tbl_learnpath_item WHERE c_id = $course_id AND (parent_item_id=$parent_item_id) ORDER BY display_order ASC";
        $result2b = Database::query($sql2b);

        while ($row2 = Database::fetch_array($result2b)) {
            // 5.1.1 Check if the element is in the circle1 array.
            $tobeexported = false;
            for ($i = 0; $i < count($circle1) && !$tobeexported; $i ++) {
                // If the type is found in the circle1 array, ask for export.
                if ($circle1[$i] == $row2['item_type']) {
                    $tobeexported = true;
                }
            }
            // 5.1.2 If applicable, export the item to an HTML file (see exportitem function for more details).
            if ($tobeexported) {
                exportitem($row2['id'], $row2['item_id'], $row2['item_type'], $add_scorm_button);
                /*if ($row2['description']) { // Put the description of items to a separate file (.desc).
                    exportdescription($row2['id'], $row2['item_type'], $row2['description']);
                }*/
            }
        } //end of items loop
    } //end of first-level chapters loop

    // 6. Export the other necceassary files.
    $filename = 'default.css';
    copy('../css/'.$filename, $expdir.'/css/'.$filename);
    $filename = 'ims_xml.xsd';
    copy($fromdir.$filename, $expdir.'/'.$filename);
    $filename = 'imscp_v1p1.xsd';
    copy($fromdir.$filename, $expdir.'/'.$filename);
    $filename = 'imsmd_v1p2.xsd';
    copy($fromdir.$filename, $expdir.'/'.$filename);
    $filename = 'APIWrapper.js';
    copy($fromdir.$filename, $expdir.'/js/'.$filename);
    $filename = 'SCOFunctions.js';
    copy($fromdir.$filename, $expdir.'/js/'.$filename);

    // In case circle1_files is not defined, build it
    //$circle1_files
    // 7. Create imsmanifest.xml.
    createimsmanifest($circle1_files, $learnpath_id);

    // 8. Put the files in the exportdir into a zip and force download.
    include_once api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php';
    
    // Create zipfile of given directory.
    $zip_folder = new PclZip(api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/".$LPnamesafe.".zip");

    $zip_folder->create(api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/".$LPnamesafe."/", PCLZIP_OPT_REMOVE_PATH, api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/");
    //api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/".$LPnamesafe); // whitout folder

    // Modified by imandak80

    /*	copy(api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/".$LPnamesafe.".zip",
             api_get_path(SYS_COURSE_PATH).$_course['path']."/document/".$LPnamesafe.".zip");
    */

    $zipfoldername = api_get_path(SYS_COURSE_PATH).$_course['path']."/temp/".$LPnamesafe;
    $zipfilename = $zipfoldername.".zip";
    DocumentManager :: file_send_for_download($zipfilename, false, basename($LPnamesafe.".zip"));

    // 9. Delete the temporary zip file and directory.
    include_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
    // in fileManage.lib.php
    my_delete($zipfilename);
    my_delete($zipfoldername);

    //exit;

    // 10. Return the circle_files hash (array).
    return ($circle1_files); //has been used before...
}

/**
 * Export SCORM content into a zip file
 *
 * Basically, all this function does is put the scorm directory back into a zip file (like the one
 * that was most probably used to import the course at first)
 * @deprecated this function is only called in the newscorm/scorm_admin.php which is deprecated
 * 
 * @param	string	Name of the SCORM path (or the directory under which it resides)
 * @param	array		Not used right now. Should replace the use of global $_course
 * @return	void
 * @author	imandak80
 */
function exportSCORM($scormname, $course) {
    global $_course;

    // Initialize.
    $tmpname = api_get_path(SYS_COURSE_PATH).$_course['path'].'/scorm';
    $zipfoldername = $tmpname.$scormname;
    $zipfilename = $zipfoldername.'.zip';

    // Create zipfile of given directory.
    include_once api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php';
    $zip_folder = new PclZip($zipfilename);
    $list = 1;
    //$list = $zip_folder->create($zipfoldername.'/',PCLZIP_OPT_REMOVE_PATH,$tmpname.$scormname."/"); // whitout folder
    $list = $zip_folder->create($zipfoldername.'/', PCLZIP_OPT_REMOVE_PATH, $tmpname);
    if ($list == 0) {
        //echo "Error  : ".$zip_folder->errorInfo(true);
    }

    // Send to client.
    DocumentManager :: file_send_for_download($zipfilename, false, basename($scormname.'.zip'));

    // Clear.
    include_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
    my_delete($zipfilename);
}

/**
 * This function returns an xml tag
 * $data behaves as the content in case of full tags
 * $data is an array of attributes in case of returning an opening tag
 * @param	string
 * @param	string
 * @param	array
 * @param	string
 * @return string
 */
function xmltagwrite($tagname, $which, $data, $linebreak = 'yes') {
    switch ($which) {
        case 'open':
            $tag = '<'.$tagname;
            $i = 0;
            while ($data[0][$i]) {
                $tag .= ' '.$data[0][$i]."=\"".$data[1][$i]."\"";
                $i ++;
            }
            if ($tagname == 'file') {
                $closing = '/';
            }
            $tag .= $closing.'>';
            if ($linebreak != 'no_linebreak') {
                $tag .= "\n";
            }
            break;
        case 'close':
            $tag = '</'.$tagname.'>';
            if ($linebreak != 'no_linebreak') {
                $tag .= "\n";
            }
            break;
        case 'full':
            $tag = '<'.$tagname;
            $tag .= '>'.$data.'</'.$tagname.'>';
            if ($linebreak != 'no_linebreak') {
                $tag .= "\n";
            }
            break;
    }
    return $tag;
}

/**
 * This function writes the imsmanifest.xml and exports the chapter names
 * @param	array		Array containing filenames
 * @param	integer	Learnpath_id
 * @return	void
 */
function createimsmanifest($circle1_files, $learnpath_id) {
    global $_course, $LPname, $expdir, $LPnamesafe;
    //$tbl_learnpath_main, $tbl_learnpath_chapter, $tbl_learnpath_item,
    $tbl_learnpath_main = Database :: get_course_table(TABLE_LEARNPATH_MAIN);
    $tbl_learnpath_item = Database :: get_course_table(TABLE_LEARNPATH_ITEM);
    $tbl_learnpath_chapter = Database :: get_course_table(TABLE_LEARNPATH_CHAPTER);

    include_once '../metadata/md_funcs.php'; // RH: export metadata

    // Header
    // Charset should be dependent on content.
    $header = '<?xml version="1.0" encoding="'.api_get_system_encoding().'"?>'."\n<manifest identifier='".$LPnamesafe."' version='1.1'\n xmlns='http://www.imsproject.org/xsd/imscp_rootv1p1p2'\n xmlns:adlcp='http://www.adlnet.org/xsd/adlcp_rootv1p2'\n xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n xsi:schemaLocation='http://www.imsproject.org/xsd/imscp_rootv1p1p2 imscp_rootv1p1p2.xsd\n http://www.imsglobal.org/xsd/imsmd_rootv1p2p1 imsmd_rootv1p2p1.xsd\n http://www.adlnet.org/xsd/adlcp_rootv1p2 adlcp_rootv1p2.xsd'>\n";

    $org .= xmltagwrite('metadata', 'open');
    $org .= '  '.xmltagwrite('schema', 'full', 'ADL SCORM');
    $org .= '  '.xmltagwrite('schemaversion', 'full', '1.2');
    $org .= xmltagwrite('metadata', 'close');

    $defaultorgname = 'default_org';

    $attributes[0][0] = 'default';
    $attributes[1][0] = $defaultorgname;
    $org .= xmltagwrite('organizations', 'open', $attributes);

    $attributes[0][0] = 'identifier';
    $attributes[1][0] = $defaultorgname;
    $org .= '  '.xmltagwrite('organization', 'open', $attributes);

    $org .= '    '.xmltagwrite('title', 'full', $LPname);

    // Items list.
    $i = 0;
    $course_id = api_get_course_int_id();
    
    $previous_item_id = '00';
    while ($circle1_files[0][$i]) {
        // Check whether we are in the border of two chapters.
        //if (!$desc=strpos($circle1_files[2][$i],'scription')) {  // This is needed if the descriptions are exported to file.

        $sql = "SELECT * FROM $tbl_learnpath_item WHERE c_id = $course_id AND (id=".$circle1_files[2][$i].")";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $parent_item_id = $row['parent_item_id'];

        if ($parent_item_id != $previous_item_id) {
            // We create the item tag for the chapter (without indifierref).
            $sql2 = "SELECT * FROM $tbl_learnpath_chapter WHERE c_id = $course_id AND (id=".$parent_item_id.")";
            $result2 = Database::query($sql2);
            $row2 = Database::fetch_array($result2);
            $chapter_name = $row2['chapter_name'];

            $attributes = '';
            $attributes[0][] = 'identifier';
            $attributes[1][] = 'chapter_'.$row2['id'];
            $attributes[0][] = 'isvisible';
            $attributes[1][] = '1';
            if ($previous_item_id != '00') {
                $org .= '    '.xmltagwrite('item', 'close');
            }

            $org .= '    '.xmltagwrite('item', 'open', $attributes);
            $org .= '      '.xmltagwrite('title', 'full', $chapter_name);

            if ($row2['chapter_description'] != '') {
                // Chapter description.
                $attributes = '';
                $attributes[0][] = 'identifier';
                $attributes[1][] = 'chapter_'.$row2['id'].'_desc';
                $attributes[0][] = 'isvisible';
                $attributes[1][] = '1';
                $org .= '    '.xmltagwrite('item', 'open', $attributes);
                $org .= '      '.xmltagwrite('title', 'full', ' '.$row2['chapter_description']);
                $org .= '    '.xmltagwrite('item', 'close');
            }
        }
        $previous_item_id = $parent_item_id;
        //}

        $attributes = ''; // Item output.
        $attributes[0][] = 'identifier';
        $attributes[1][] = 'item_'.$circle1_files[2][$i];
        $attributes[0][] = 'identifierref';
        $attributes[1][] = 'item_ref_'.$circle1_files[2][$i];
        $attributes[0][] = 'isvisible';
        $attributes[1][] = '1';
        $org .= '    '.xmltagwrite('item', 'open', $attributes);
        $org .= '      '.xmltagwrite('title', 'full', $circle1_files[1][$i]);

        if ($row['prereq_id'] != '') {
            // Item prerequisites.
            $attributes = '';
            $attributes[0][] = 'type';
            $attributes[1][] = 'aicc_script';
            $org .= '      '.xmltagwrite('adlcp:prerequisites', 'open', $attributes, 'no_linebreak');
            if ($row['prereq_type'] == 'i') {
                $org .= 'item_'.$row['prereq_id'];
            }
            if ($row['prereq_type'] == 'c') {
                $org .= 'chapter_'.$row['prereq_id'];
            }
            $org .= xmltagwrite('adlcp:prerequisites', 'close', $attributes);
        }

        if ($row['description'] != '') {
            // Item description.
            $attributes = '';
            $attributes[0][] = 'identifier';
            $attributes[1][] = 'item_'.$circle1_files[2][$i].'_desc';
            $attributes[0][] = 'isvisible';
            $attributes[1][] = '1';
            $org .= '    '.xmltagwrite('item', 'open', $attributes);
            $org .= '      '.xmltagwrite('title', 'full', ' '.$row['description']);
            $org .= '    '.xmltagwrite('item', 'close');
        }

        $mds = new mdstore(true); // RH: export metadata; if no table, create it
        if (($mdt = $mds->mds_get($row['item_type'].'.'.$row['item_id'])))
            if (($mdo = api_strpos($mdt, '<metadata>')) && ($mdc = api_strpos($mdt, '</metadata>')))
                $org .= '    '.api_substr($mdt, $mdo, $mdc - $mdo + 11)."\n";

        $org .= '    '.xmltagwrite('item', 'close');
        $i ++;
    }

    if ($circle1_files) {
        $org .= '    '.xmltagwrite('item', 'close');
    } // Not needed in case of a blank path.
    $org .= '  '.xmltagwrite('organization', 'close');
    $org .= xmltagwrite('organizations', 'close');
    $org .= xmltagwrite('resources', 'open');

    // Resources list.
    $i = 0;
    while ($circle1_files[0][$i]) {
        $attributes = '';
        $attributes[0][] = 'identifier';
        $attributes[1][] = 'item_ref_'.$circle1_files[2][$i];
        $attributes[0][] = 'type';
        $attributes[1][] = 'webcontent';
        $attributes[0][] = 'adlcp:scormtype';
        $attributes[1][] = 'sco';
        $attributes[0][] = 'href';
        $attributes[1][] = $circle1_files[0][$i];
        $org .= '  '.xmltagwrite('resource', 'open', $attributes);

        $org .= '    '.xmltagwrite('metadata', 'open');
        $org .= '      '.xmltagwrite('schema', 'full', 'ADL SCORM');
        $org .= '      '.xmltagwrite('schemaversion', 'full', '1.2');
        $org .= '    '.xmltagwrite('metadata', 'close');

        $attributes = '';
        $attributes[0][] = 'href';
        $attributes[1][] = $circle1_files[0][$i];
        $org .= '    '.xmltagwrite('file', 'open', $attributes);

        $org .= '  '.xmltagwrite('resource', 'close');
        $i ++;
    }

    $org .= xmltagwrite('resources', 'close');
    $org .= xmltagwrite('manifest', 'close');
    $manifest = $header.$org;

    exporttofile('imsmanifest.xml', 'Manifest file', '0', $manifest);
}

/**
 * Gets the tags of the file given as parameter
 *
 * if $filename is not found, GetSRCTags(filename) will return FALSE
 * @param string		File path
 * @return mixed		Array of strings on success, false on failure
 * @author unknown
 * @author Included by imandak80
 */
function GetSRCTags($fileName) {
    if (!($fp = fopen($fileName, 'r'))) {
        // Iif file can't be opened, return false.
        return false;
    }
    // Read file contents.
    $contents = fread($fp, filesize($fileName));
    fclose($fp);

    $matches = array();
    $srcList = array();
    // Get all src tags contents in this file. Use multi-line search.
    preg_match_all('/src(\s)*=(\s)*[\'"]([^\'"]*)[\'"]/mi', $contents, $matches); // Get the img src as contained between " or '

    foreach ($matches[3] as $match) {
        if (!in_array($match, $srcList)) {
            $srcList[] = $match;
        }
    }
    if (count($srcList) == 0) {
        return false;
    }
    return $srcList;
}

/**
 * Copy file and create directories in the path if needed.
 *
 * @param	string	$source Source path
 * @param	string	$dest Destination path
 * @return boolean 	true on success, false on failure
 */
function CopyNCreate($source, $dest) {
    if (strcmp($source, $dest) == 0)
        return false;

    $dir = '';
    $tdest = explode('/', $dest);
    for ($i = 0; $i < sizeof($tdest) - 1; $i ++) {
        $dir = $dir.$tdest[$i].'/';
        if (!is_dir($dir))
            if (!mkdir($dir, api_get_permissions_for_new_directories()))
                return false;
    }

    if (!copy($source, $dest))
        return false;

    return true;
}

function rcopy($source, $dest) {
    //error_log($source." -> ".$dest, 0);
    if (!file_exists($source)) {
        //error_log($source." does not exist", 0);
        return false;
    }

    if (is_dir($source)) {
        //error_log($source." is a dir", 0);
        // This is a directory.
        // Remove trailing '/'
        if (strrpos($source, '/') == sizeof($source) - 1) {
            $source = substr($source, 0, size_of($source) - 1);
        }
        if (strrpos($dest, '/') == sizeof($dest) - 1) {
            $dest = substr($dest, 0, size_of($dest) - 1);
        }

        if (!is_dir($dest)) {
            $res = @mkdir($dest, api_get_permissions_for_new_directories());
            if ($res !== false) {
                return true;
            } else {
                // Remove latest part of path and try creating that.
                if (rcopy(substr($source, 0, strrpos($source, '/')), substr($dest, 0, strrpos($dest, '/')))) {
                    return @mkdir($dest, api_get_permissions_for_new_directories());
                } else {
                    return false;
                }
            }
        }
        return true;
    } else {
        // This is presumably a file.
        //error_log($source." is a file", 0);
        if (!@ copy($source, $dest)) {
            //error_log("Could not simple-copy $source", 0);
            $res = rcopy(dirname($source), dirname($dest));
            if ($res === true) {
                //error_log("Welcome dir created", 0);
                return @ copy($source, $dest);
            } else {
                return false;
                //error_log("Error creating path", 0);
            }
        } else {
            //error_log("Could well simple-copy $source", 0);
            return true;
        }
    }
}
