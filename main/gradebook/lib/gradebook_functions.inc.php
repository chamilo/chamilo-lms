<?php
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
* These are functions used in gradebook
*
* @author Stijn Konings <konings.stijn@skynet.be>, Hogeschool Ghent
* @author Julio Montoya <gugli100@gmail.com> adding security functions
* @version april 2007
*/
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be.inc.php';
require_once 'gradebook_functions_users.inc.php';
require_once api_get_path(LIBRARY_PATH).'grade_model.lib.php';

/**
 * Adds a resource to the unique gradebook of a given course
 * @param   string  Course code
 * @param   int     Resource type (use constants defined in linkfactory.class.php)
 * @param   int     Resource ID in the corresponding tool
 * @param   string  Resource name to show in the gradebook
 * @param   int     Resource weight to set in the gradebook
 * @param   int     Resource max
 * @param   string  Resource description
 * @param   string  Date
 * @param   int     Visibility (0 hidden, 1 shown)
 * @param   int     Session ID (optional or 0 if not defined)
 * @return  boolean True on success, false on failure
 */
function add_resource_to_course_gradebook($category_id, $course_code, $resource_type, $resource_id, $resource_name='', $weight=0, $max=0, $resource_description='',  $visible=0, $session_id = 0) {
    $link = LinkFactory :: create($resource_type);
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

    $link->set_visible(empty ($visible) ? 0 : 1);

    if (!empty($session_id)) {
    	$link->set_session_id($session_id);
    }
    $link->add();
    return true;
}

function block_students() {    
    if (!api_is_allowed_to_edit()) {		
		api_not_allowed();
	}
}

/**
 * Returns the course name from a given code
 * @param string $code
 */
function get_course_name_from_code($code) {
	$tbl_main_categories= Database :: get_main_table(TABLE_MAIN_COURSE);
	$sql= 'SELECT title, code FROM ' . $tbl_main_categories . 'WHERE code = "' . Database::escape_string($code) . '"';
	$result= Database::query($sql);
	if ($col= Database::fetch_array($result)) {
		return $col['title'];
	}
}

/**
 * Builds an img tag for a gradebook item
 * @param string $type value returned by a gradebookitem's get_icon_name()
 */
function build_type_icon_tag($kind) {
	return '<img src="' . get_icon_file_name ($kind) . '" border="0" hspace="5" align="middle" alt="" />';
}


/**
 * Returns the icon filename for a gradebook item
 * @param string $type value returned by a gradebookitem's get_icon_name()
 */
function get_icon_file_name ($type) {
	switch ($type) {
		case 'cat':
			$icon = 'icons/22/gradebook.png';
			break;
		case 'evalempty':
			$icon = 'icons/22/empty_evaluation.png';
			break;
		case 'evalnotempty':
			$icon = 'icons/22/no_empty_evaluation.png';
			break;
		case 'exercise':
		case LINK_EXERCISE:
			$icon = 'quiz.gif';
			break;
		case 'learnpath':
		case LINK_LEARNPATH:
			$icon = 'icons/22/learnpath.png';
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
		default:
			$icon = 'link.gif';
			break;
	}
	return api_get_path(WEB_IMG_PATH).$icon;
}

/**
 * Builds the course or platform admin icons to edit a category
 * @param object $cat category object
 * @param int $selectcat id of selected category
 */
function build_edit_icons_cat($cat, $selectcat) {
	$show_message = $cat->show_message_resource_delete($cat->get_course_code());
    $grade_model_id = $selectcat->get_grade_model_id();
    
    $selectcat = $selectcat->get_id();
    
	if ($show_message===false) {
		$visibility_icon= ($cat->is_visible() == 0) ? 'invisible' : 'visible';
		$visibility_command= ($cat->is_visible() == 0) ? 'set_visible' : 'set_invisible';
        
        $modify_icons .= '<a class="view_children" data-cat-id="'.$cat->get_id().'" href="javascript:void(0);">'.Display::return_icon('view_more_stats.gif', get_lang('Show'),'',ICON_SIZE_SMALL).'</a>';
        
        if (api_is_allowed_to_edit(null, true)) {
            
            //Locking button
            if (api_get_setting('gradebook_locking_enabled') == 'true') {                
                if ($cat->is_locked()) {
                    if (api_is_platform_admin()) {
                        $modify_icons .= '&nbsp;<a onclick="javascrip:unlock_confirmation()" href="' . api_get_self() . '?'.  api_get_cidreq().'&category_id=' . $cat->get_id() . '&action=unlock">'.
                                         Display::return_icon('lock.png', get_lang('Unlock'),'',ICON_SIZE_SMALL).'</a>';                                        
                    } else {
                        $modify_icons .= '&nbsp;<a href="#">'.Display::return_icon('lock_na.png', get_lang('GradebookLockedAlert'),'',ICON_SIZE_SMALL).'</a>';                
                    }
                    $modify_icons .= '&nbsp;<a href="gradebook_flatview.php?export_pdf=category&selectcat=' . $cat->get_id() . '" >'.Display::return_icon('pdf.png', get_lang('ExportToPDF'),'',ICON_SIZE_SMALL).'</a>';
                } else {
                    $modify_icons .= '&nbsp;<a onclick="javascrip:lock_confirmation()" href="' . api_get_self() . '?'.  api_get_cidreq().'&category_id=' . $cat->get_id() . '&action=lock">'.
                            Display::return_icon('unlock.png', get_lang('Lock'),'',ICON_SIZE_SMALL).'</a>';                
                    //$modify_icons .= '&nbsp;<a href="gradebook_flatview.php?export_pdf=category&selectcat=' . $cat->get_id() . '" >'.Display::return_icon('pdf.png', get_lang('ExportToPDF'),'',ICON_SIZE_SMALL).'</a>';
                }                
            }          
            
            if (empty($grade_model_id) || $grade_model_id == -1) {
                if ($cat->is_locked() && !api_is_platform_admin()) {
                    $modify_icons .= Display::return_icon('edit_na.png', get_lang('Modify'),'',ICON_SIZE_SMALL);
                } else {
                    $modify_icons .= '<a href="gradebook_edit_cat.php?editcat='.$cat->get_id().'&amp;cidReq='.$cat->get_course_code().'">'.Display::return_icon('edit.png', get_lang('Modify'),'',ICON_SIZE_SMALL).'</a>';
                }
            }

            $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?visiblecat=' . $cat->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat . ' ">'.Display::return_icon($visibility_icon.'.png', get_lang('Visible'),'',ICON_SIZE_SMALL).'</a>';

            //no move ability for root categories
            if ($cat->is_movable()) {
                /*$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?movecat=' . $cat->get_id() . '&amp;selectcat=' . $selectcat . ' &amp;cidReq='.$cat->get_course_code().'">
                                    <img src="../img/icons/22/move.png" border="0" title="' . get_lang('Move') . '" alt="" /></a>';*/
            } else {
                //$modify_icons .= '&nbsp;<img src="../img/deplacer_fichier_na.gif" border="0" title="' . get_lang('Move') . '" alt="" />';
            }
            if ($cat->is_locked() && !api_is_platform_admin()) {
                $modify_icons .= Display::return_icon('delete_na.png', get_lang('DeleteAll'),'',ICON_SIZE_SMALL);
            } else {
                $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?deletecat=' . $cat->get_id() . '&amp;selectcat=' . $selectcat . '&amp;cidReq='.$cat->get_course_code().'" onclick="return confirmation();">'.Display::return_icon('delete.png', get_lang('DeleteAll'),'',ICON_SIZE_SMALL).'</a>';
            }            
        }
		return $modify_icons;
	}
}
/**
 * Builds the course or platform admin icons to edit an evaluation
 * @param object $eval evaluation object
 * @param int $selectcat id of selected category
 */
function build_edit_icons_eval($eval, $selectcat) {
	$status = CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
	$is_locked = $eval->is_locked();
	$eval->get_course_code();
	$cat=new Category();
	$message_eval=$cat->show_message_resource_delete($eval->get_course_code());
    
	if ($message_eval===false && api_is_allowed_to_edit(null, true)) {
		$visibility_icon= ($eval->is_visible() == 0) ? 'invisible' : 'visible';
		$visibility_command= ($eval->is_visible() == 0) ? 'set_visible' : 'set_invisible';
        if ($is_locked && !api_is_platform_admin()) {
            $modify_icons= Display::return_icon('edit_na.png', get_lang('Modify'),'',ICON_SIZE_SMALL);
        } else {
            $modify_icons= '<a href="gradebook_edit_eval.php?editeval=' . $eval->get_id() . ' &amp;cidReq='.$eval->get_course_code().'">'.Display::return_icon('edit.png', get_lang('Modify'),'',ICON_SIZE_SMALL).'</a>';
        }

		$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?visibleeval=' . $eval->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat . ' ">'.Display::return_icon($visibility_icon.'.png', get_lang('Visible'),'',ICON_SIZE_SMALL).'</a>';
		if (api_is_allowed_to_edit(null, true)){
			$modify_icons .= '&nbsp;<a href="gradebook_showlog_eval.php?visiblelog=' . $eval->get_id() . '&amp;selectcat=' . $selectcat . ' &amp;cidReq='.$eval->get_course_code().'">'.Display::return_icon('history.png', get_lang('GradebookQualifyLog'),'',ICON_SIZE_SMALL).'</a>';			
		}
        
        /*
		if ($locked_status == 0){
			$modify_icons .= "&nbsp;<a href=\"javascript:if (confirm('".addslashes(get_lang('AreYouSureToLockedTheEvaluation'))."')) { location.href='".api_get_self().'?lockedeval=' . $eval->get_id() . '&amp;selectcat=' . $selectcat . ' &amp;cidReq='.$eval->get_course_code()."'; }\">".Display::return_icon('unlock.png',get_lang('LockEvaluation'), array(), ICON_SIZE_SMALL)."</a>";
		} else {
			if (api_is_platform_admin()){
				$modify_icons .= "&nbsp;<a href=\"javascript:if (confirm('".addslashes(get_lang('AreYouSureToUnLockedTheEvaluation'))."')) { location.href='".api_get_self().'?lockedeval=' . $eval->get_id() . '&amp;typelocked=&amp;selectcat=' . $selectcat . ' &amp;cidReq='.$eval->get_course_code()."';\">".Display::return_icon('lock.png',get_lang('UnLockEvaluation'), array(), ICON_SIZE_SMALL)."</a>";
			} else {
				$modify_icons .= '&nbsp;<img src="../img/locked_na.png" border="0" title="' . get_lang('TheEvaluationIsLocked') . '" alt="" />';
			}
		}*/        
        if ($is_locked && !api_is_platform_admin()) {
            $modify_icons .= '&nbsp;'.Display::return_icon('delete_na.png', get_lang('Delete'),'',ICON_SIZE_SMALL);
        } else {
            $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?deleteeval=' . $eval->get_id() . '&selectcat=' . $selectcat . ' &amp;cidReq='.$eval->get_course_code().'" onclick="return confirmation();">'.Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>';
        }
		return $modify_icons;
	}
}
/**
 * Builds the course or platform admin icons to edit a link
 * @param object $linkobject
 * @param int $selectcat id of selected category
 */
function build_edit_icons_link($link, $selectcat) {	
	$cat = new Category();
	$message_link = $cat->show_message_resource_delete($link->get_course_code());    
    $is_locked = $link->is_locked();
        
	if ($message_link===false) {
		$visibility_icon= ($link->is_visible() == 0) ? 'invisible' : 'visible';
		$visibility_command= ($link->is_visible() == 0) ? 'set_visible' : 'set_invisible';
        
        if ($is_locked && !api_is_platform_admin()) {
            $modify_icons = Display::return_icon('edit_na.png', get_lang('Modify'),'',ICON_SIZE_SMALL);
        } else {
            $modify_icons = '<a href="gradebook_edit_link.php?editlink='.$link->get_id().'&amp;cidReq='.$link->get_course_code().'">'.Display::return_icon('edit.png', get_lang('Modify'),'',ICON_SIZE_SMALL).'</a>';
        }

		//$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?movelink=' . $link->get_id() . '&selectcat=' . $selectcat . '"><img src="../img/deplacer_fichier.gif" border="0" title="' . get_lang('Move') . '" alt="" /></a>';
		$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?visiblelink=' . $link->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat . ' ">'.Display::return_icon($visibility_icon.'.png', get_lang('Visible'),'',ICON_SIZE_SMALL).'</a>';
		$modify_icons .= '&nbsp;<a href="gradebook_showlog_link.php?visiblelink=' . $link->get_id() . '&amp;selectcat=' . $selectcat . '&amp;cidReq='.$link->get_course_code().'">'.Display::return_icon('history.png', get_lang('GradebookQualifyLog'),'',ICON_SIZE_SMALL).'</a>';
		
		//If a work is added in a gradebook you can only delete the link in the work tool 
		$show_delete = true;
		if ($link->get_type() == 3) {
			$show_delete = false;
		}
		if ($show_delete) {
            if ($is_locked && !api_is_platform_admin()) {
                $modify_icons .= '&nbsp;'.Display::return_icon('delete_na.png', get_lang('Delete'),'',ICON_SIZE_SMALL);                
            } else {
                $modify_icons .= '&nbsp;<a href="' . api_get_self() . '?deletelink=' . $link->get_id() . '&selectcat=' . $selectcat . ' &amp;cidReq='.$link->get_course_code().'" onclick="return confirmation();">'.Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>';
            }
		} else {
			$modify_icons .= '&nbsp;'.Display::return_icon('delete_na.png', get_lang('Delete'),'',ICON_SIZE_SMALL);
		}
		return $modify_icons;
	}
}

/**
 * Checks if a resource is in the unique gradebook of a given course
 * @param    string  Course code
 * @param    int     Resource type (use constants defined in linkfactory.class.php)
 * @param    int     Resource ID in the corresponding tool
 * @param    int     Session ID (optional -  0 if not defined)
 * @return   int     false on error or link ID
 */
function is_resource_in_course_gradebook($course_code, $resource_type, $resource_id, $session_id = 0) {
    // TODO find the corresponding category (the first one for this course, ordered by ID)
    $t = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
    $l = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
    /*$sql = "SELECT * FROM $t WHERE course_code = '".Database::escape_string($course_code)."' ";
    if (!empty($session_id)) {
        $sql .= " AND session_id = ".(int)$session_id;
    } else {
        $sql .= " AND (session_id IS NULL OR session_id = 0) ";
    }
    $sql .= " ORDER BY id";
    $res = Database::query($sql);
    if (Database::num_rows($res)<1) {
    	return false;
    }
    $row = Database::fetch_array($res,'ASSOC');    
    $category = $row['id'];*/
    
    $course_code = Database::escape_string($course_code);
    $sql = "SELECT * FROM $l l WHERE course_code = '$course_code' AND type = ".(int) $resource_type." and ref_id = ".(int) $resource_id;
    $res = Database::query($sql);
    if (Database::num_rows($res)<1) {
    	return false;
    }
    $row = Database::fetch_array($res, 'ASSOC');
    return $row;
}

/**
 * Remove a resource from the unique gradebook of a given course
 * @param    int     Link/Resource ID
 * @return   bool    false on error, true on success
 */
function get_resource_from_course_gradebook($link_id) {
    if ( empty($link_id) ) { return false; }    
    // TODO find the corresponding category (the first one for this course, ordered by ID)
    $l = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
    $sql = "SELECT * FROM $l WHERE id = ".(int)$link_id;    
    $res = Database::query($sql);
    $row = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_array($res, 'ASSOC');
    }
    return $row;
}

/**
 * Remove a resource from the unique gradebook of a given course
 * @param    int     Link/Resource ID
 * @return   bool    false on error, true on success
 */
function remove_resource_from_course_gradebook($link_id) {
    if ( empty($link_id) ) { return false; }    
    // TODO find the corresponding category (the first one for this course, ordered by ID)
    $l = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
    $sql = "DELETE FROM $l WHERE id = ".(int)$link_id;
    $res = Database::query($sql);
    return true;
}
/**
 * Return the database name
 * @param    int
 * @return   String
 */
function get_database_name_by_link_id($id_link) {
	$course_table 		= Database::get_main_table(TABLE_MAIN_COURSE);
	$tbl_grade_links 	= Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
	$sql = 'SELECT db_name FROM '.$course_table.' c INNER JOIN '.$tbl_grade_links.' l
			ON c.code=l.course_code WHERE l.id='.intval($id_link).' OR l.category_id='.intval($id_link);	
	$res=Database::query($sql);
	$my_db_name=Database::fetch_array($res,'ASSOC');
	return $my_db_name['db_name'];
}

/**
* Return the course id
* @param    int
* @return   String
*/
function get_course_id_by_link_id($id_link) {
	$course_table 		= Database::get_main_table(TABLE_MAIN_COURSE);
	$tbl_grade_links 	= Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
	$sql = 'SELECT c.id FROM '.$course_table.' c INNER JOIN '.$tbl_grade_links.' l
			ON c.code = l.course_code WHERE l.id='.intval($id_link).' OR l.category_id='.intval($id_link);	
	$res = Database::query($sql);
	$array = Database::fetch_array($res,'ASSOC');
	return $array['id'];
}

function get_table_type_course($type) {	
	global $table_evaluated;
	return Database::get_course_table($table_evaluated[$type][0]);
}

function get_printable_data($cat, $users, $alleval, $alllinks, $params) {
	$datagen = new FlatViewDataGenerator ($users, $alleval, $alllinks, $params);
    
	$offset = isset($_GET['offset']) ? $_GET['offset'] : '0';
	$offset = intval($offset);	
    
    // step 2: generate rows: students
    $datagen->category = $cat;
        
	$count = (($offset + 10) > $datagen->get_total_items_count()) ? ($datagen->get_total_items_count() - $offset) : LIMIT;	
	$header_names = $datagen->get_header_names($offset, $count, true);	
	$data_array   = $datagen->get_data(FlatViewDataGenerator :: FVDG_SORT_LASTNAME, 0, null, $offset, $count, true, true);

	$newarray = array();
	foreach ($data_array as $data) {
		$newarray[] = array_slice($data, 1);
	}
    $return = array($header_names, $newarray);    
	return $return;
}


/**
 * XML-parser: handle character data
 */

function character_data($parser, $data) {
	global $current_value;
	$current_value= $data;
}

/**
 * XML-parser: handle end of element
 */

function element_end($parser, $data) {
	global $user;
	global $users;
	global $current_value;
	switch ($data) {
	case 'Result' :
		$users[]= $user;
		break;
	default :
		$user[$data]= $current_value;
		break;
	}
}

/**
 * XML-parser: handle start of element
 */

function element_start($parser, $data) {
	global $user;
	global $current_tag;
	switch ($data) {
	case 'Result' :
		$user= array ();
		break;
	default :
		$current_tag= $data;
	}
}

function overwritescore($resid, $importscore, $eval_max) {
	$result= Result :: load($resid);
	if ($importscore > $eval_max) {
		header('Location: gradebook_view_result.php?selecteval=' .Security::remove_XSS($_GET['selecteval']) . '&overwritemax=');
		exit;
	}
	$result[0]->set_score($importscore);
	$result[0]->save();
	unset ($result);
}

/**
 * Read the XML-file
 * @param string $file Path to the XML-file
 * @return array All userinformation read from the file
 */

function parse_xml_data($file) {
	global $current_tag;
	global $current_value;
	global $user;
	global $users;
	$users= array ();
	$parser= xml_parser_create();
	xml_set_element_handler($parser, 'element_start', 'element_end');
	xml_set_character_data_handler($parser, "character_data");
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
	xml_parse($parser, file_get_contents($file));
	xml_parser_free($parser);
	return $users;
}



/**
* register user info about certificate
* @param int The category id
* @param int The user id
* @param float The score obtained for certified
* @param Datetime The date when you obtained the certificate
* @return void()
*/
function register_user_info_about_certificate ($cat_id, $user_id, $score_certificate, $date_certificate) {
    $table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
    $sql_exist='SELECT COUNT(*) as count FROM '.$table_certificate.' gc
    			WHERE gc.cat_id="'.intval($cat_id).'" AND user_id="'.intval($user_id).'" ';
    $rs_exist=Database::query($sql_exist);
    $row=Database::fetch_array($rs_exist);
    if ($row['count']==0) {
    	$sql='INSERT INTO '.$table_certificate.' (cat_id,user_id,score_certificate,created_at)
    		  VALUES("'.intval($cat_id).'","'.intval($user_id).'","'.Database::escape_string($score_certificate).'","'.Database::escape_string($date_certificate).'")';
    	$rs = Database::query($sql);
    }
}

/**
* Get date of user certificate
* @param int The category id
* @param int The user id
* @return Datetime The date when you obtained the certificate
*/
function get_certificate_by_user_id ($cat_id,$user_id) {
	$table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
	$sql_get_date='SELECT * FROM '.$table_certificate.' WHERE cat_id="'.intval($cat_id).'" AND user_id="'.intval($user_id).'"';
	$rs_get_date=Database::query($sql_get_date);
	$row =Database::fetch_array($rs_get_date,'ASSOC');
	return $row;
}

/**
* Get list of users certificates
* @param int The category id
* @return array
*/
function get_list_users_certificates ($cat_id=null) {
    $table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
    $table_user = Database::get_main_table(TABLE_MAIN_USER);
    $sql = 'SELECT DISTINCT u.user_id, u.lastname, u.firstname, u.username 
    		FROM '.$table_user.' u INNER JOIN '.$table_certificate.' gc ON u.user_id=gc.user_id ';
    if (!is_null($cat_id) && $cat_id>0) {
    	$sql.=' WHERE cat_id='.Database::escape_string($cat_id);
    }
    $sql.=' ORDER BY u.firstname';
    $rs = Database::query($sql);
    $list_users = array();
    while ($row=Database::fetch_array($rs)) {
    	$list_users[]=$row;
    }
    return $list_users;
}

/**
*Gets the certificate list by user id
*@param int The user id
*@param int The category id
*@return array
*/
function get_list_gradebook_certificates_by_user_id ($user_id,$cat_id=null) {
    $table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
    $sql='SELECT gc.score_certificate, gc.created_at, gc.path_certificate, gc.cat_id, gc.user_id, gc.id FROM  '.$table_certificate.' gc
    	  WHERE gc.user_id="'.Database::escape_string($user_id).'" ';
    if (!is_null($cat_id) && $cat_id>0) {
    	$sql.=' AND cat_id='.Database::escape_string($cat_id);
    }
    
    $rs = Database::query($sql);
    $list_certificate=array();
    while ($row=Database::fetch_array($rs)) {
    	$list_certificate[]=$row;
    }
    return $list_certificate;
}

function get_user_certificate_content($user_id, $course_code, $is_preview = false) {
    //generate document HTML    
    $content_html       = DocumentManager::replace_user_info_into_html($user_id, $course_code, $is_preview);
            
    $new_content        = explode('</head>', $content_html['content']);    
    $new_content_html   = $new_content[1];                    
    $path_image         = api_get_path(WEB_COURSE_PATH).api_get_course_path($course_code).'/document/images/gallery';
    $new_content_html   = str_replace('../images/gallery',$path_image,$new_content_html);
    
    $path_image_in_default_course = api_get_path(WEB_CODE_PATH).'default_course_document';
    $new_content_html   = str_replace('/main/default_course_document',$path_image_in_default_course,$new_content_html);    
    $new_content_html   = str_replace('/main/img/', api_get_path(WEB_IMG_PATH), $new_content_html);
    
    //add print header
    $print  = '<style media="print" type="text/css">#print_div {visibility:hidden;}</style>';
    $print .= '<a href="javascript:window.print();" style="float:right; padding:4px;" id="print_div"><img src="'.api_get_path(WEB_CODE_PATH).'img/printmgr.gif" alt="' . get_lang('Print') . '" /> ' . get_lang('Print') . '</a>';
    
    //add header
    $new_content_html = $new_content[0].$print.'</head>'.$new_content_html;
    return array('content' => $new_content_html, 'variables'=>$content_html['variables']);
}

function create_default_course_gradebook( $course_code = null, $gradebook_model_id = 0) {    
    if (api_is_allowed_to_edit(true, true)) {
        if (!isset($course_code) || empty($course_code)) {
            $course_code = api_get_course_id();    
        }        
        $session_id = api_get_session_id();
        
        $t = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = "SELECT * FROM $t WHERE course_code = '".Database::escape_string($course_code)."' ";
        if (!empty($session_id)) {
            $sql .= " AND session_id = ".(int)$session_id;
        } else {
            $sql .= " AND (session_id IS NULL OR session_id = 0) ";
        }
        $sql .= " ORDER BY id";
        $res = Database::query($sql);
        if (Database::num_rows($res)<1){
            //there is no unique category for this course+session combination,
            $cat = new Category();
            if (!empty($session_id)) {
                $my_session_id=api_get_session_id();
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
            
            
            $cat->set_visible(0);            
            $cat->add();            
            $category_id = $cat->get_id();
            unset ($cat);
        } else {
            $row = Database::fetch_array($res);
            $category_id = $row['id'];
        }
    }
    return $category_id;
}
function load_gradebook_select_in_tool($form) {     
    
    $course_code = api_get_course_id();
    $session_id = api_get_session_id();
    
    create_default_course_gradebook();
                
    //Cat list
    $all_categories = Category :: load(null, null, $course_code, null, null, $session_id, false);    
    $select_gradebook = $form->addElement('select', 'category_id', get_lang('SelectGradebook'));
      
    if (!empty($all_categories)) {
        foreach ($all_categories as $my_cat) {
            if ($my_cat->get_course_code() == api_get_course_id()) {         
                $grade_model_id = $my_cat->get_grade_model_id();                     
                if (empty($grade_model_id)) {
                    if ($my_cat->get_parent_id() == 0) {
                        //$default_weight = $my_cat->get_weight();
                        $select_gradebook->addoption(get_lang('Default'), $my_cat->get_id());
                        $cats_added[] = $my_cat->get_id();
                    } else {
                        $select_gradebook->addoption($my_cat->get_name(), $my_cat->get_id());
                        $cats_added[] = $my_cat->get_id();
                    }
                } else {
                    $select_gradebook->addoption(get_lang('Select'), 0);
                }
                /*if ($this->evaluation_object->get_category_id() == $my_cat->get_id()) {
                    $default_weight = $my_cat->get_weight();                        
                } */                                       
            }           
        }
    }
}

/**
 * PDF report creation
 */
function export_pdf_flatview($cat, $users, $alleval, $alllinks, $params = array()) {    
    //Getting data
    $printable_data = get_printable_data($cat[0], $users, $alleval, $alllinks, $params);    

    // Reading report's CSS
    $css_file = api_get_path(SYS_CODE_PATH).'gradebook/print.css';
    $css = file_exists($css_file) ? @file_get_contents($css_file) : '';

    // HTML report creation first    
    $course_code = trim($cat[0]->get_course_code());
    $organization = api_get_setting('Institution');

    $displayscore = ScoreDisplay :: instance();
    $customdisplays = $displayscore->get_custom_score_display_settings();
    
    $total = array();
    if (is_array($customdisplays) && count(($customdisplays))) {        
        foreach($customdisplays  as $custom) {
            $total[$custom['display']]  = 0; 
        }			
        $user_results = $flatviewtable->datagen->get_data_to_graph2();
        foreach($user_results  as $user_result) {
            $total[$user_result[count($user_result)-1][1]]++;
        }
    }

    $html = '';

    $img = api_get_path(SYS_CODE_PATH).'css/'.api_get_visual_theme().'/images/header-logo.png';    
    if (file_exists($img)) {
        $img = api_get_path(WEB_CODE_PATH).'css/'.api_get_visual_theme().'/images/header-logo.png';
        $organization = "<img src='$img'>";			
    } else {
        if (!empty($organization)) {			  
            $organization = '<h2 align="left">'.$organization.'</h2>';
        }
    }
    
    Display::$global_template->assign('organization', $organization);
    
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
            $title = '<h2 align="center">'.api_strtoupper(get_lang('Average')).'<br />'.get_lang('Detailed').'</h2>';
        } else {
            $title = '<h2 align="center"> '.api_strtoupper(get_lang('Average')).'<br />'.$cat[0]->get_description().' - ('.$cat[0]->get_name().')</h2>';
        }
    } else {
        if ($parent_id == 0) {
            $title = '<h2 align="center">'.api_strtoupper(get_lang('Average')).'<br />'.get_lang('Detailed').'</h2>';
        } else {
            $title = '<h2 align="center">'.api_strtoupper(get_lang('Average')).'</h2>';
        }
    }
    
    Display::$global_template->assign('pdf_title', $title);
    //Showing only the current teacher/admin instead the all teacherlist name see BT#4080
    //$teacher_list = CourseManager::get_teacher_list_from_course_code_to_string($course_code);
    $user_info = api_get_user_info();    
    $teacher_list = $user_info['complete_name'];
    $session_name = api_get_session_name(api_get_session_id());    
    if (!empty($session_name)) {        
        Display::$global_template->assign('pdf_session', $session_name);
    }	
    
    Display::$global_template->assign('pdf_course', $course_code);
    Display::$global_template->assign('pdf_date', api_format_date(api_get_utc_datetime(), DATE_TIME_FORMAT_LONG));
    Display::$global_template->assign('pdf_teachers', $teacher_list);
 
    $columns  = count($printable_data[0]);
    $has_data = is_array($printable_data[1]) && count($printable_data[1]) > 0;
        
    $table = new HTML_Table(array('class' => 'data_table'));
    $row = 0;
    $column = 0;    
    
    $table->setHeaderContents($row, $column, get_lang('NumberAbbreviation'));
    $column++;
    foreach ($printable_data[0] as $printable_data_cell) {
        $printable_data_cell = strip_tags($printable_data_cell);
        $table->setHeaderContents($row, $column, $printable_data_cell);
        $column++;
    }
    $row++;
    
    if ($has_data) {
        $counter = 1;
        //var_dump($printable_data);exit;
        foreach ($printable_data[1] as &$printable_data_row) {
            $column = 0;
            $table->setCellContents($row, $column, $counter); 
            $table->updateCellAttributes($row, $column, 'align="center"');
            $column++; 
            $counter++;            
            
            foreach ($printable_data_row as $key => &$printable_data_cell) {
                $attributes = array();
                $attributes['align'] = 'center';                
                $attributes['style'] = null;
                
                if ($key === 'name') {
                    $attributes['align'] = 'left';
                }
                if ($key === 'total') {
                    $attributes['style'] = 'font-weight:bold';                 
                }
                //var_dump($key, $printable_data_cell, $attributes);
                $table->setCellContents($row, $column, $printable_data_cell);
                $table->updateCellAttributes($row, $column, $attributes);
                $column++;
            }
            $table->updateRowAttributes($row, $row % 2 ? 'class="row_even"' : 'class="row_odd"', true);
            $row++;
        }   
        //exit;
    } else {
        $column = 0;
        $table->setCellContents($row, $column, get_lang('NoResults'));
        $table->updateCellAttributes($row, $column, 'colspan="'.$columns.'" align="center" class="row_odd"');
    }    
    
    Display::$global_template->assign('pdf_table', $table->toHtml());

    unset($printable_data);
    unset($table);

    // Conversion of the created HTML report to a PDF report    
    $gradebook_tpl = Display::$global_template->get_template('gradebook/flatview.pdf.tpl');     
    $gradebook_flatview = Display::$global_template->fetch($gradebook_tpl);
    
    //Header
    $html = $gradebook_flatview;  
    
    $html = api_utf8_encode($html);
    $page_format = $params['orientation'] == 'landscape' ? 'A4-L' : 'A4';
    $pdf = new PDF($page_format, $params['orientation']);

    // Sending the created PDF report to the client    
    $file_name = null;
    if (!empty($course_code)) {
        $file_name .= $course_code;
    }
    $file_name = api_get_utc_datetime();
    $file_name = get_lang('FlatView').'_'.$file_name;
    $pdf->content_to_pdf($html, $css, $file_name, api_get_course_id());
    exit;	
}

function score_badges($list_values) {
    $counter = 1;
    $badges = array();
    foreach ($list_values as $value) {
        $class = 'info';
        if ($counter == 1) {
            $class = 'success';    
        }        
        $counter++;
        $badges[] = Display::badge($value, $class);        
        
    }
    return Display::badge_group($badges);
}