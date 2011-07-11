<?php

/* For licensing terms, see /license.txt */

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
 * @package chamilo.link
 */

/* FUNCTIONS */

/**
 * Used to add a link or a category
 * @param string $type, "link" or "category"
 * @todo replace strings by constants
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function addlinkcategory($type) {
	global $catlinkstatus;
	global $msgErr;

	$ok = true;

	if ($type == 'link') {
		$tbl_link = Database :: get_course_table(TABLE_LINK);

		$title            = Security :: remove_XSS(stripslashes($_POST['title']));
		$urllink          = Security :: remove_XSS($_POST['urllink']);
		$description      = Security :: remove_XSS($_POST['description']);
		$selectcategory   = Security :: remove_XSS($_POST['selectcategory']);

		if ($_POST['onhomepage'] == '') {
			$onhomepage = 0;
			$target = '_self'; // Default target.
		} else {
			$onhomepage  = Security :: remove_XSS($_POST['onhomepage']);			
			$target      = Security :: remove_XSS($_POST['target_link']);
		}

		$urllink      = trim($urllink);
		$title        = trim($title);
		$description  = trim($description);

		// We ensure URL to be absolute.
		if (strpos($urllink, '://') === false) {
			$urllink = 'http://' . $urllink;
		}

		// If the title is empty, we use the URL as title.
		if ($title == '') {
			$title = $urllink;
		}

		// If the URL is invalid, an error occurs.
		// Ivan, 13-OCT-2010, Chamilo 1.8.8: Let us still tolerate PHP 5.1.x and avoid a specific bug in filter_var(), see http://bugs.php.net/51192
		//if (!filter_var($urllink, FILTER_VALIDATE_URL)) {
		if (!api_valid_url($urllink, true)) { // A check against an absolute URL.
			//
			$msgErr = get_lang('GiveURL');
			Display :: display_error_message(get_lang('GiveURL'));
			$ok = false;
		} else {
			// Looking for the largest order number for this category.
			$result = Database :: query("SELECT MAX(display_order) FROM  " . $tbl_link . " WHERE category_id = '" . intval($_POST['selectcategory']) . "'");
			list ($orderMax) = Database :: fetch_row($result);
			$order = $orderMax +1;

			$session_id = api_get_session_id();

			$sql = "INSERT INTO " . $tbl_link . " (url, title, description, category_id, display_order, on_homepage, target, session_id)
			        VALUES ('" . Database :: escape_string($urllink) . "','" . Database :: escape_string($title) . "','" . Database :: escape_string($description) . "','" .
			Database :: escape_string($selectcategory) . "','" . Database :: escape_string($order) . "', '" . Database :: escape_string($onhomepage) . "','" .
			Database :: escape_string($target) . "','" . Database :: escape_string($session_id) . "')";
			$catlinkstatus = get_lang('LinkAdded');
			Database :: query($sql);
			$link_id = Database :: insert_id();

			if ((api_get_setting('search_enabled') == 'true') && $link_id && extension_loaded('xapian')) {
				require_once api_get_path(LIBRARY_PATH) . 'search/DokeosIndexer.class.php';
				require_once api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php';
				require_once api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php';

				$courseid = api_get_course_id();
				$specific_fields = get_specific_field_list();
				$ic_slide = new IndexableChunk();

				// Add all terms to db.
				$all_specific_terms = '';
				foreach ($specific_fields as $specific_field) {
					if (isset ($_REQUEST[$specific_field['code']])) {
						$sterms = trim($_REQUEST[$specific_field['code']]);
						if (!empty ($sterms)) {
							$all_specific_terms .= ' ' . $sterms;
							$sterms = explode(',', $sterms);
							foreach ($sterms as $sterm) {
								$ic_slide->addTerm(trim($sterm), $specific_field['code']);
								add_specific_field_value($specific_field['id'], $courseid, TOOL_LINK, $link_id, $sterm);
							}
						}
					}
				}

				// Build the chunk to index.
				$ic_slide->addValue('title', $title);
				$ic_slide->addCourseId($courseid);
				$ic_slide->addToolId(TOOL_LINK);
				$xapian_data = array (
					SE_COURSE_ID => $courseid,
					SE_TOOL_ID => TOOL_LINK,
					SE_DATA => array (
						'link_id' => (int) $link_id
					),
					SE_USER => (int) api_get_user_id(),
					
				);
				$ic_slide->xapian_data = serialize($xapian_data);
				$description = $all_specific_terms . ' ' . $description;
				$ic_slide->addValue('content', $description);

				// Add category name if set.
				if (isset ($_POST['selectcategory']) && $selectcategory > 0) {
					$table_link_category = Database :: get_course_table(TABLE_LINK_CATEGORY);
					$sql_cat = 'SELECT * FROM %s WHERE id=%d LIMIT 1';
					$sql_cat = sprintf($sql_cat, $table_link_category, (int) $selectcategory);
					$result = Database :: query($sql_cat);
					if (Database :: num_rows($result) == 1) {
						$row = Database :: fetch_array($result);
						$ic_slide->addValue('category', $row['category_title']);
					}
				}

				$di = new DokeosIndexer();
				isset ($_POST['language']) ? $lang = Database :: escape_string($_POST['language']) : $lang = 'english';
				$di->connectDb(NULL, NULL, $lang);
				$di->addChunk($ic_slide);

				// Index and return search engine document id.
				$did = $di->index();
				if ($did) {
					// Save it to db.
					$tbl_se_ref = Database :: get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
					$sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, search_did)
					                        VALUES (NULL , \'%s\', \'%s\', %s, %s)';
					$sql = sprintf($sql, $tbl_se_ref, $courseid, TOOL_LINK, $link_id, $did);
					Database :: query($sql);
				}
			}
			unset ($urllink, $title, $description, $selectcategory);
			Display :: display_confirmation_message(get_lang('LinkAdded'));
		}
	} elseif ($type == 'category') {
		$tbl_categories = Database :: get_course_table(TABLE_LINK_CATEGORY);

		$category_title = trim($_POST['category_title']);
		$description    = trim($_POST['description']);

		if (empty($category_title)) {
			$msgErr = get_lang('GiveCategoryName');
			Display :: display_error_message(get_lang('GiveCategoryName'));
			$ok = false;
		} else {
			// Looking for the largest order number for this category.
			$result = Database :: query("SELECT MAX(display_order) FROM  " . $tbl_categories);
			list ($orderMax) = Database :: fetch_row($result);
			$order = $orderMax +1;
			$order = intval($order);
			$session_id = api_get_session_id();
			$sql = "INSERT INTO ".$tbl_categories." (category_title, description, display_order, session_id) 
			        VALUES ('" .Database::escape_string($category_title) . "', '" . Database::escape_string($description) . "', '$order', '$session_id')";
			Database :: query($sql);

			$catlinkstatus = get_lang('CategoryAdded');
			unset ($category_title, $description);
			Display :: display_confirmation_message(get_lang('CategoryAdded'));
		}
	}

	// "WHAT'S NEW" notification : update last tool Edit.
	if ($type == 'link') {
		global $_user;
		global $_course;
		global $nameTools;
		api_item_property_update($_course, TOOL_LINK, $link_id, 'LinkAdded', $_user['user_id']);
	}
	return $ok;
}

/**
 * Used to delete a link or a category
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function deletelinkcategory($type) {
	global $catlinkstatus;
	global $_course;	
	$tbl_link = Database :: get_course_table(TABLE_LINK);
	$tbl_categories = Database :: get_course_table(TABLE_LINK_CATEGORY);
	$TABLE_ITEM_PROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);

	if ($type == 'link') {
		global $id;
		// -> Items are no longer fysically deleted, but the visibility is set to 2 (in item_property).
		// This will make a restore function possible for the platform administrator.
		if (isset ($_GET['id']) && $_GET['id'] == strval(intval($_GET['id']))) {
			$sql = "UPDATE $tbl_link SET on_homepage='0' WHERE id='" . intval($_GET['id']) . "'";
			Database :: query($sql);
		}

		api_item_property_update($_course, TOOL_LINK, $id, 'delete', api_get_user_id());
		delete_link_from_search_engine(api_get_course_id(), $id);
		$catlinkstatus = get_lang('LinkDeleted');
		unset ($id);

		Display :: display_confirmation_message(get_lang('LinkDeleted'));
	}

	if ($type == 'category') {
		global $id;
		if (isset ($_GET['id']) && !empty ($_GET['id'])) {
			// First we delete the category itself and afterwards all the links of this category.
			$sql = "DELETE FROM " . $tbl_categories . " WHERE id='" . intval($_GET['id']) . "'";
			Database :: query($sql);
			$sql = "DELETE FROM " . $tbl_link . " WHERE category_id='" . intval($_GET['id']) . "'";
			$catlinkstatus = get_lang('CategoryDeleted');
			unset ($id);
			Database :: query($sql);
			Display :: display_confirmation_message(get_lang('CategoryDeleted'));
		}
	}
}

/**
 * Removes a link from search engine database
 *
 * @param string $course_id Course code
 * @param int $document_id Document id to delete
 */
function delete_link_from_search_engine($course_id, $link_id) {
	// Remove from search engine if enabled.
	if (api_get_setting('search_enabled') == 'true') {
		$tbl_se_ref = Database :: get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
		$sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
		$sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_LINK, $link_id);
		$res = Database :: query($sql);
		if (Database :: num_rows($res) > 0) {
			$row = Database :: fetch_array($res);
			require_once api_get_path(LIBRARY_PATH) . 'search/DokeosIndexer.class.php';
			$di = new DokeosIndexer();
			$di->remove_document((int) $row['search_did']);
		}
		$sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
		$sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_LINK, $link_id);
		Database :: query($sql);

		// Remove terms from db.
		require_once (api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');
		delete_all_values_for_item($course_id, TOOL_DOCUMENT, $link_id);
	}
}

/**
 * Used to edit a link or a category
 * @todo Rewrite the whole links tool because it is becoming completely cluttered,
 *       code does not follow the coding conventions, does not use html_quickform, ...
 *       Some features were patched in.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @todo replace the globals with the appropriate $_POST or $_GET values
 */
function editlinkcategory($type) {

	global $catlinkstatus;
	global $id;
	global $submit_link;
	global $submit_category;
	global $_user;
	global $_course;
	global $nameTools;
	global $urllink;
	global $title;
	global $description;
	global $category;
	global $selectcategory;
	global $description;
	global $category_title;
	global $onhomepage;
	global $target_link;

	$tbl_link = Database :: get_course_table(TABLE_LINK);
	$tbl_categories = Database :: get_course_table(TABLE_LINK_CATEGORY);

	if ($type == 'link') {

		// This is used to populate the link-form with the info found in the database.
		if (!empty ($_GET['id'])) {
			$sql = "SELECT * FROM " . $tbl_link . " WHERE id='" . intval($_GET['id']) . "'";
			$result = Database :: query($sql);
			if ($myrow = Database :: fetch_array($result)) {
				$urllink = $myrow['url'];
				$title = $myrow['title'];
				$description = $myrow['description'];
				$category = $myrow['category_id'];
				if ($myrow['on_homepage'] != 0) {
					$onhomepage = 'checked';
				}
				$target_link = $myrow['target'];
			}
		}

		// This is used to put the modified info of the link-form into the database.
		if ($_POST['submitLink']) {

			// Ivan, 13-OCT-2010: It is a litle bit messy code below, just in case I added some extra-security checks here.
			$_POST['urllink']        = trim($_POST['urllink']);
			$_POST['title']          = trim(Security :: remove_XSS($_POST['title']));
			$_POST['description']    = trim(Security :: remove_XSS($_POST['description']));
			$_POST['selectcategory'] = intval($_POST['selectcategory']);
			$_POST['id']             = intval($_POST['id']);

			// We ensure URL to be absolute.
			if (strpos($_POST['urllink'], '://') === false) {
				$_POST['urllink'] = 'http://' . $_POST['urllink'];
			}

			// If the title is empty, we use the URL as title.
			if ($_POST['title'] == '') {
				$_POST['title'] = $_POST['urllink'];
			}

			// If the URL is invalid, an error occurs.
			// Ivan, 13-OCT-2010, Chamilo 1.8.8: Let us still tolerate PHP 5.1.x and avoid a specific bug in filter_var(), see http://bugs.php.net/51192
			//if (!filter_var($urllink, FILTER_VALIDATE_URL)) {
			if (!api_valid_url($urllink, true)) { // A check against an absolute URL.
				$msgErr = get_lang('GiveURL');
				Display :: display_error_message(get_lang('GiveURL'));
				return false;
			}

			$onhomepage  = Security :: remove_XSS($_POST['onhomepage']);
			$target      = Database::escape_string($_POST['target_link']);
			if (empty ($mytarget)) {
				$mytarget = '_self';
			}
			$mytarget = ",target='" . $target . "'";

			// Finding the old category_id.
			$sql = "SELECT * FROM " . $tbl_link . " WHERE id='" . intval($_POST['id']) . "'";
			$result = Database :: query($sql);
			$row = Database :: fetch_array($result);
			$category_id = $row['category_id'];

			if ($category_id != $_POST['selectcategory']) {
				$sql = "SELECT MAX(display_order) FROM " . $tbl_link . " WHERE category_id='" . intval($_POST['selectcategory']) . "'";
				$result = Database :: query($sql);
				list ($max_display_order) = Database :: fetch_row($result);
				$max_display_order++;
			} else {
				$max_display_order = $row['display_order'];
			}

			$sql = "UPDATE " . $tbl_link . " SET " .
			"url='" . Database :: escape_string($_POST['urllink']) . "', " .
			"title='" . Database :: escape_string($_POST['title']) . "', " .
			"description='" . Database :: escape_string($_POST['description']) . "', " .
			"category_id='" . Database :: escape_string($_POST['selectcategory']) . "', " .
			"display_order='" . $max_display_order . "', " .
			"on_homepage='" . Database :: escape_string($onhomepage) . " ' $mytarget " .
			" WHERE id='" . intval($_POST['id']) . "'";
			Database :: query($sql);

			// Update search enchine and its values table if enabled.
			if (api_get_setting('search_enabled') == 'true') {
				$link_id = intval($_POST['id']);
				$course_id = api_get_course_id();
				$link_url = Database :: escape_string($_POST['urllink']);
				$link_title = Database :: escape_string($_POST['title']);
				$link_description = Database :: escape_string($_POST['description']);

				// Actually, it consists on delete terms from db, insert new ones, create a new search engine document, and remove the old one.
				// Get search_did.
				$tbl_se_ref = Database :: get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
				$sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
				$sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_LINK, $link_id);
				$res = Database :: query($sql);

				if (Database :: num_rows($res) > 0) {
					require_once api_get_path(LIBRARY_PATH) . 'search/DokeosIndexer.class.php';
					require_once api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php';
					require_once api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php';

					$se_ref = Database :: fetch_array($res);
					$specific_fields = get_specific_field_list();
					$ic_slide = new IndexableChunk();

					$all_specific_terms = '';
					foreach ($specific_fields as $specific_field) {
						delete_all_specific_field_value($course_id, $specific_field['id'], TOOL_LINK, $link_id);
						if (isset ($_REQUEST[$specific_field['code']])) {
							$sterms = trim($_REQUEST[$specific_field['code']]);
							if (!empty ($sterms)) {
								$all_specific_terms .= ' ' . $sterms;
								$sterms = explode(',', $sterms);
								foreach ($sterms as $sterm) {
									$ic_slide->addTerm(trim($sterm), $specific_field['code']);
									add_specific_field_value($specific_field['id'], $course_id, TOOL_LINK, $link_id, $sterm);
								}
							}
						}
					}

					// Build the chunk to index.
					$ic_slide->addValue("title", $link_title);
					$ic_slide->addCourseId($course_id);
					$ic_slide->addToolId(TOOL_LINK);
					$xapian_data = array (
						SE_COURSE_ID => $course_id,
						SE_TOOL_ID => TOOL_LINK,
						SE_DATA => array (
							'link_id' => (int) $link_id
						),
						SE_USER => (int) api_get_user_id(),
						
					);
					$ic_slide->xapian_data = serialize($xapian_data);
					$link_description = $all_specific_terms . ' ' . $link_description;
					$ic_slide->addValue('content', $link_description);

					// Add category name if set.
					if (isset ($_POST['selectcategory']) && $selectcategory > 0) {
						$table_link_category = Database :: get_course_table(TABLE_LINK_CATEGORY);
						$sql_cat = 'SELECT * FROM %s WHERE id=%d LIMIT 1';
						$sql_cat = sprintf($sql_cat, $table_link_category, (int) $selectcategory);
						$result = Database :: query($sql_cat);
						if (Database :: num_rows($result) == 1) {
							$row = Database :: fetch_array($result);
							$ic_slide->addValue('category', $row['category_title']);
						}
					}

					$di = new DokeosIndexer();
					isset ($_POST['language']) ? $lang = Database :: escape_string($_POST['language']) : $lang = 'english';
					$di->connectDb(NULL, NULL, $lang);
					$di->remove_document((int) $se_ref['search_did']);
					$di->addChunk($ic_slide);

					// Index and return search engine document id.
					$did = $di->index();
					if ($did) {
						// Save it to db.
						$sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=\'%s\'';
						$sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_LINK, $link_id);
						Database :: query($sql);
						$sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, search_did)
						        VALUES (NULL , \'%s\', \'%s\', %s, %s)';
						$sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_LINK, $link_id, $did);
						Database :: query($sql);
					}
				}
			}
			// "WHAT'S NEW" notification: update table last_toolEdit.
			api_item_property_update($_course, TOOL_LINK, $_POST['id'], 'LinkUpdated', $_user['user_id']);
			Display :: display_confirmation_message(get_lang('LinkModded'));
		}
	}

	if ($type == 'category') {

		// This is used to populate the category-form with the info found in the database.
		if (!$submit_category) {
			$sql = "SELECT * FROM " . $tbl_categories . " WHERE id='" . intval($_GET['id']) . "'";
			$result = Database :: query($sql);
			if ($myrow = Database :: fetch_array($result)) {
				$category_title = $myrow['category_title'];
				$description = $myrow['description'];
			}
		}

		// This is used to put the modified info of the category-form into the database.
		if ($submit_category) {
			$sql = "UPDATE " . $tbl_categories . " set category_title='" . Database :: escape_string($_POST['category_title']) . "', description='" . Database :: escape_string($_POST['description']) . "' WHERE id='" . Database :: escape_string($_POST['id']) . "'";
			Database :: query($sql);
			Display :: display_confirmation_message(get_lang('CategoryModded'));
		}

	}

	return true; // On errors before this statement, exit from this function by returning false value.
}

/**
 * Creates a correct $view for in the URL
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function makedefaultviewcode($locatie) {
	global $aantalcategories, $view;
	for ($j = 0; $j <= $aantalcategories -1; $j++) {
		$view[$j] = 0;
	}
	$view[intval($locatie)] = '1';
}

/**
 * Changes the visibility of a link
 * @todo add the changing of the visibility of a course
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function change_visibility($id, $scope) {
	global $_course, $_user;
	if ($scope == 'link') {
		api_item_property_update($_course, TOOL_LINK, $id, $_GET['action'], $_user['user_id']);
		Display :: display_confirmation_message(get_lang('VisibilityChanged'));
	}
}

/**
 * Displays all the links of a given category.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function showlinksofcategory($catid) {
	global $is_allowed, $charset, $urlview, $up, $down, $_user;

	$tbl_link = Database :: get_course_table(TABLE_LINK);
	$TABLE_ITEM_PROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);

	// Condition for the session.
	$session_id = api_get_session_id();
	$condition_session = api_get_session_condition($session_id, true, true);
	$catid = intval($catid);

	$sqlLinks = "SELECT * FROM " . $tbl_link . " link, " . $TABLE_ITEM_PROPERTY . " itemproperties WHERE itemproperties.tool='" . TOOL_LINK . "' AND link.id=itemproperties.ref AND link.category_id='" . $catid . "' AND (itemproperties.visibility='0' OR itemproperties.visibility='1') $condition_session ORDER BY link.display_order DESC";
	$result = Database :: query($sqlLinks);
	$numberoflinks = Database :: num_rows($result);
	if ($numberoflinks > 0) {
    	echo '<table class="data_table" width="100%">';
    	$i = 1;
    	while ($myrow = Database :: fetch_array($result)) {
    
    		// Validacion when belongs to a session.
    		$session_img = api_get_session_image($myrow['session_id'], $_user['status']);
    
    		$css_class = $i % 2 == 0 ? $css_class = 'row_odd' : $css_class = 'row_even';
    		
    		$link_validator = '';
			if (api_is_allowed_to_edit(null, true)) {
			    $link_validator  = ''.Display::url(Display::return_icon('preview_view.png', get_lang('CheckURL'), array(), 16), '#', array('onclick'=>"check_url('".$myrow['id']."', '".addslashes($myrow['url'])."');"));    			
			    $link_validator .= Display::span('', array('id'=>'url_id_'.$myrow['id']));
			}
    
    		if ($myrow['visibility'] == '1') {
    			echo '<tr class="'.$css_class.'">';
    			echo '<td align="center" valign="middle" width="15">';
    			echo '<a href="link_goto.php?', api_get_cidreq(), '&amp;link_id=', $myrow[0], '&amp;link_url=', urlencode($myrow[1]), '" target="_blank"><img src="../../main/img/link.gif" border="0" alt="', get_lang('Link'), '"/></a></td><td width="80%" valign="top"><a href="link_goto.php?', api_get_cidreq(), '&amp;link_id=', $myrow[0], '&amp;link_url=', urlencode($myrow[1]), '" target="', $myrow['target'], '">';
    			echo Security :: remove_XSS($myrow[2]);
    			echo '</a>';    			
    			echo $link_validator;   			
    		
    			echo $session_img;
    			echo '<br />', $myrow[3];
    		} else {
    			if (api_is_allowed_to_edit(null, true)) {
    				echo '<tr class="'.$css_class.'">';
    				echo '<td align="center" valign="middle" width="15"><a href="link_goto.php?', api_get_cidreq(), '&amp;link_id=', $myrow[0], "&amp;link_url=", urlencode($myrow[1]), '" target="_blank" class="invisible">';
    				echo Display :: return_icon('link_na.gif', get_lang('Link')), '</a>';
    				echo '</td><td width="80%" valign="top"><a href="link_goto.php?', api_get_cidreq(), '&amp;link_id=', $myrow[0], '&amp;link_url=', urlencode($myrow[1]),'" target="', $myrow['target'], '"  class="invisible">';
    				echo Security :: remove_XSS($myrow[2]);
    				echo "</a>";
    			    echo $link_validator;	
    				echo $session_img, '<br />', $myrow[3];
    			}
    		}
    
    		echo '<td style="text-align:center;">';
    		if (api_is_allowed_to_edit(null, true)) {
    			if ($session_id == $myrow['session_id']) {
    
    				echo '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;action=editlink&amp;category=' . (!empty ($category) ? $category : '') . '&amp;id=' . $myrow[0] . '&amp;urlview=' . $urlview . '" title="' . get_lang('Modify') . '">' . Display :: return_icon('edit.png', get_lang('Modify'), array (), 22) . '</a>';
    				// DISPLAY MOVE UP COMMAND only if it is not the top link.
    				if ($i != 1) {
    					echo '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;urlview=' . $urlview . '&amp;up=', $myrow[0], '" title="' . get_lang('Up') . '">' . Display :: return_icon('up.png', get_lang('Up'), array (), 22) . '', "</a>\n";
    				} else {
    					echo Display :: return_icon('up.png', get_lang('Up'), array (), 22) . '</a>';
    				}
    
    				// DISPLAY MOVE DOWN COMMAND only if it is not the bottom link.
    				if ($i < $numberoflinks) {
    					echo '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;urlview=' . $urlview . '&amp;down=' . $myrow[0] . '" title="' . get_lang('Down') . '">' . Display :: return_icon('down.png', get_lang('Down'), array (), 22) . '', "</a>\n";
    				} else {
    					echo Display :: return_icon('down_na.png', get_lang('Down'), array (), 22) . '', "</a>\n";
    				}
    
    				if ($myrow['visibility'] == '1') {
    					echo '<a href="link.php?' . api_get_cidreq() . '&amp;action=invisible&amp;id=' . $myrow[0] . '&amp;scope=link&amp;urlview=' . $urlview . '" title="' . get_lang('Hide') . '">' . Display :: return_icon('visible.png', get_lang('Hide'), array (), 22) . '</a>';
    				}
    				if ($myrow['visibility'] == '0') {
    					echo ' <a href="link.php?' . api_get_cidreq() . '&amp;action=visible&amp;id=' . $myrow[0] . '&amp;scope=link&amp;urlview=' . $urlview . '" title="' . get_lang('Show') . '">' . Display :: return_icon('invisible.png', get_lang('Show'), array (), 22) . '</a>';
    				}
    				echo ' <a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;action=deletelink&amp;id=', $myrow[0], '&amp;urlview=', $urlview, "\" onclick=\"javascript: if(!confirm('" . get_lang('LinkDelconfirm') . "')) return false;\" title=\"" . get_lang('Delete') . '">' . Display :: return_icon('delete.png', get_lang('Delete'), array (), 22) . '</a>';
    
    			} else {
    				echo get_lang('EditionNotAvailableFromSession');
    			}
    		}
    		echo '</td></tr>';
    		$i++;
    	}
    	echo '</table>';
	}
}

/**
 * Displays the edit, delete and move icons
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function showcategoryadmintools($categoryid) {

	global $urlview;
	global $aantalcategories;
	global $catcounter;
	echo '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;action=editcategory&amp;id=' . $categoryid . '&amp;urlview=' . $urlview . '" title=' . get_lang('Modify') . '">' . Display :: return_icon('edit.png', get_lang('Modify'), array (), 22) . '</a>';
	echo '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;action=deletecategory&amp;id=', $categoryid, "&amp;urlview=$urlview\" onclick=\"javascript: if(!confirm('" . get_lang('CategoryDelconfirm') . "')) return false;\">", Display :: return_icon('delete.png', get_lang('Delete'), array (), 22) . '</a>';

	// DISPLAY MOVE UP COMMAND only if it is not the top link.
	if ($catcounter != 1) {
		echo '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;catmove=true&amp;up=', $categoryid, '&amp;urlview=' . $urlview . '" title="' . get_lang('Up') . '">' . Display :: return_icon('up.png', get_lang('Up'), array (), 22) . '</a>';
	} else {
		echo Display :: return_icon('up_na.png', get_lang('Up'), array (), 22) . '</a>';
	}

	// DISPLAY MOVE DOWN COMMAND only if it is not the bottom link.
	if ($catcounter < $aantalcategories) {
		echo '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&amp;catmove=true&amp;down=' . $categoryid . '&amp;urlview=' . $urlview . '">
				' . Display :: return_icon('down.png', get_lang('Down'), array (), 22) . '</a>';
	} else {
		echo Display :: return_icon('down_na.png', get_lang('Down'), array (), 22) . '</a>';
	}
	$catcounter++;
}

/**
 * move a link or a linkcategory up or down
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function movecatlink($catlinkid) {

	global $catmove;
	global $up;
	global $down;
	$tbl_link = Database :: get_course_table(TABLE_LINK);
	$tbl_categories = Database :: get_course_table(TABLE_LINK_CATEGORY);

	if (!empty ($down)) {
		$thiscatlinkId = intval($down);
		$sortDirection = 'DESC';
	}
	if (!empty ($up)) {
		$thiscatlinkId = intval($up);
		$sortDirection = 'ASC';
	}

	// We check if it is a category we are moving or a link. If it is a category, a querystring catmove = true is present in the url.
	if ($catmove == 'true') {
		$movetable = $tbl_categories;
		$catid = $catlinkid;
	} else {
		$movetable = $tbl_link;
		// Getting the category of the link.
		if (!empty ($thiscatlinkId)) {
			$sql = "SELECT category_id from " . $movetable . " WHERE id='$thiscatlinkId'";
			$result = Database :: query($sql);
			$catid = Database :: fetch_array($result);
		}
	}

	// This code is copied and modified from announcements.php.
	if (!empty($sortDirection)) {
		if (!in_array(trim(strtoupper($sortDirection)), array (
				'ASC',
				'DESC'
			)))
			die('Bad sort direction used.'); // Sanity check of sortDirection var.
		if ($catmove == 'true') {
			$sqlcatlinks = "SELECT id, display_order FROM " . $movetable . " ORDER BY display_order $sortDirection";
		} else {
			$sqlcatlinks = "SELECT id, display_order FROM " . $movetable . " WHERE category_id='" . $catid[0] . "' ORDER BY display_order $sortDirection";
		}
		$linkresult = Database :: query($sqlcatlinks);
		while ($sortrow = Database :: fetch_array($linkresult)) {
			// STEP 2 : FOUND THE NEXT ANNOUNCEMENT ID AND ORDER, COMMIT SWAP
			// This part seems unlogic, but it isn't . We first look for the current link with the querystring ID
			// and we know the next iteration of the while loop is the next one. These should be swapped.
			if (isset ($thislinkFound) && $thislinkFound) {
				$nextlinkId = $sortrow['id'];
				$nextlinkOrdre = $sortrow['display_order'];

				Database :: query("UPDATE " . $movetable . "
				                                         SET display_order = '$nextlinkOrdre'
				                                         WHERE id =  '$thiscatlinkId'");

				Database :: query("UPDATE " . $movetable . "
				                                         SET display_order = '$thislinkOrdre'
				                                         WHERE id =  '$nextlinkId'");

				break;
			}
			if ($sortrow['id'] == $thiscatlinkId) {
				$thislinkOrdre = $sortrow['display_order'];
				$thislinkFound = true;
			}
		}
	}

	Display :: display_confirmation_message(get_lang('LinkMoved'));
}

/**
 * CSV file import functions
 * @author René Haentjens , Ghent University
 */
function get_cat($catname) {
	// Get category id (existing or make new).
	$tbl_categories = Database :: get_course_table(TABLE_LINK_CATEGORY);
	$result = Database :: query("SELECT id FROM " . $tbl_categories . " WHERE category_title='" . Database::escape_string($catname) . "'");
	if (Database :: num_rows($result) >= 1 && ($row = Database :: fetch_array($result))) {
		return $row['id']; // Several categories with same name: take the first.
	}

	$result = Database :: query("SELECT MAX(display_order) FROM " . $tbl_categories);
	list ($max_order) = Database :: fetch_row($result);
	Database :: query("INSERT INTO " . $tbl_categories . " (category_title, description, display_order) VALUES ('" . Database::escape_string($catname) . "','','" . ($max_order +1) . "')");
	return Database :: insert_id();
}

/**
 * CSV file import functions
 * @author René Haentjens , Ghent University
 */
function put_link($url, $cat, $title, $description, $on_homepage, $hidden) {
	$tbl_link = Database :: get_course_table(TABLE_LINK);

	$urleq = "url='" . Database :: escape_string($url) . "'";
	$cateq = "category_id=" . intval($cat);

	$result = Database :: query("SELECT id FROM $tbl_link WHERE " . $urleq . ' AND ' . $cateq);

	if (Database :: num_rows($result) >= 1 && ($row = Database :: fetch_array($result))) {
		Database :: query("UPDATE $tbl_link set title='" . Database :: escape_string($title) . "', description='" . Database :: escape_string($description) . "' WHERE id='" . Database :: escape_string($row['id']) . "'");

		$ipu = 'LinkUpdated';
		$rv = 1; // 1 = upd
	} else {
		// Add new link
		$result = Database :: query("SELECT MAX(display_order) FROM  $tbl_link WHERE category_id='" . intval($cat) . "'");
		list ($max_order) = Database :: fetch_row($result);

		Database :: query("INSERT INTO $tbl_link (url, title, description, category_id, display_order, on_homepage) VALUES ('" . Database :: escape_string($url) . "','" . Database :: escape_string($title) . "','" . Database :: escape_string($description) . "','" . intval($cat) . "','" . (intval($max_order) + 1) . "','" . intval($on_homepage) . "')");

		$id = Database :: insert_id();
		$ipu = 'LinkAdded';
		$rv = 2; // 2 = new
	}

	global $_course, $nameTools, $_user;
	api_item_property_update($_course, TOOL_LINK, $id, $ipu, $_user['user_id']);

	if ($hidden && $ipu == 'LinkAdded') {
		api_item_property_update($_course, TOOL_LINK, $id, 'invisible', $_user['user_id']);
	}
	return $rv;
}

/**
 * CSV file import functions
 * @author René Haentjens , Ghent University
 */
function import_link($linkdata) {
	// url, category_id, title, description, ...

	// Field names used in the uploaded file
	$known_fields = array (
		'url',
		'category',
		'title',
		'description',
		'on_homepage',
		'hidden'
	);
	$hide_fields = array (
		'kw',
		'kwd',
		'kwds',
		'keyword',
		'keywords'
	);

	// All other fields are added to description, as "name:value".

	// Only one hide_field is assumed to be present, <> is removed from value.

	if (!($url = trim($linkdata['url'])) || !($title = trim($linkdata['title']))) {
		return 0; // 0 = fail
	}

	$cat = ($catname = trim($linkdata['category'])) ? get_cat($catname) : 0;

	$regs = array (); // Will be passed to ereg()
	foreach ($linkdata as $key => $value)
		if (!in_array($key, $known_fields))
			if (in_array($key, $hide_fields) && ereg('^<?([^>]*)>?$', $value, $regs)) // possibly in <...>
				if (($kwlist = trim($regs[1])) != '')
					$kw = '<i kw="' . htmlspecialchars($kwlist) . '">';
				else
					$kw = '';
	// i.e. assume only one of the $hide_fields will be present
	// and if found, hide the value as expando property of an <i> tag
	elseif (trim($value)) {
		$d .= ', ' . $key . ':' . $value;
	}
	if ($d) {
		$d = substr($d, 2) . ' - ';
	}

	return put_link($url, $cat, $title, $kw . ereg_replace('\[((/?(b|big|i|small|sub|sup|u))|br/)\]', '<\\1>', htmlspecialchars($d . $linkdata['description'])) . ($kw ? '</i>' : ''), $linkdata['on_homepage'] ? '1' : '0', $linkdata['hidden'] ? '1' : '0');
	// i.e. allow some BBcode tags, e.g. [b]...[/b]
}

/**
 * CSV file import functions
 * @author René Haentjens , Ghent University
 */
function import_csvfile() {

	global $catlinkstatus; // Feedback message to user.

	if (is_uploaded_file($filespec = $_FILES['import_file']['tmp_name']) && filesize($filespec) && ($myFile = @ fopen($filespec, 'r'))) {
		// read first line of file (column names) and find ',' or ';'
		$listsep = strpos($colnames = trim(fgets($myFile)), ',') !== false ? ',' : (strpos($colnames, ';') !== false ? ';' : '');

		if ($listsep) {
			$columns = array_map('strtolower', explode($listsep, $colnames));

			if (in_array('url', $columns) && in_array('title', $columns)) {
				$stats = array (
					0,
					0,
					0
				); // fails, updates, inserts

				// Modified by Ivan Tcholakov, 01-FEB-2010.
				//while (($data = fgetcsv($myFile, 32768, $listsep))) {
				while (($data = api_fgetcsv($myFile, null, $listsep))) {
					//
					foreach ($data as $i => & $text) {
						$linkdata[$columns[$i]] = $text;
					}

					$stats[import_link($linkdata)]++;
					unset ($linkdata);
				}

				$catlinkstatus = '';

				if ($stats[0]) {
					$catlinkstatus .= $stats[0] . ' ' . get_lang('CsvLinesFailed');
				}
				if ($stats[1]) {
					$catlinkstatus .= $stats[1] . ' ' . get_lang('CsvLinesOld');
				}
				if ($stats[2]) {
					$catlinkstatus .= $stats[2] . ' ' . get_lang('CsvLinesNew');
				}
			} else {
				$catlinkstatus = get_lang('CsvFileNoURL') . ($colnames ? get_lang('CsvFileLine1') . htmlspecialchars(substr($colnames, 0, 200)) . '...' : '');
			}
		} else {
			$catlinkstatus = get_lang('CsvFileNoSeps') . ($colnames ? get_lang('CsvFileLine1') . htmlspecialchars(substr($colnames, 0, 200)) . '...' : '');
		}
		fclose($myFile);
	} else {
		$catlinkstatus = get_lang('CsvFileNotFound');
	}
}

/**
 * This function checks if the url is a youtube link
 * @author Jorge Frisancho
 * @author Julio Montoya - Fixing code 
 * @version 1.0
 */
function is_youtube_link($url) {
	return strrpos($url, "youtube");
}

function get_youtube_video_id($url) {
	// This is the length of YouTube's video IDs
	$len = 11;

	// The ID string starts after "v=", which is usually right after
	// "youtube.com/watch?" in the URL    
	$pos = strpos($url, "?v=");

	// In case the "v=" is NOT right after the "?" (not likely, but I like to keep my
	// bases covered), it will be after an "&":
	if ($pos === false) {
		$pos = strpos($url, "&v=");
	}
	// If still FALSE, URL doesn't have a vid ID
	if ($pos === false) {
		//die("YouTube video ID not found. Please double-check your URL.");
		api_not_allowed();
	}
	// Offset the start location to match the beginning of the ID string
	$pos += 3;
	// Get the ID string and return it
	$ytvID = substr($url, $pos, $len);
	return $ytvID;
}