<?php
/* For licensing terms, see /license.txt */

class CourseHome {

	/**
	 * Gets the html content to show in the 3 column view
	 */
	public static function show_tool_3column($cat) {
		if (!class_exists('HTML_Table')) {
			require_once 'pear/HTML/Table.php';
		}
		global $_user;
		$charset = api_get_system_encoding();
		$TBL_ACCUEIL = Database :: get_course_table(TABLE_TOOL_LIST);
		$TABLE_TOOLS = Database :: get_main_table(TABLE_MAIN_COURSE_MODULE);

		$numcols = 3;
		$table = new HTML_Table('width="100%"');
		$all_tools = array();
		switch ($cat) {

			case 'Basic' :
				$condition_display_tools = ' WHERE a.link=t.link AND t.position="basic" ';
				if (api_is_coach()) {
					$condition_display_tools = ' WHERE a.link=t.link AND (t.position="basic" OR a.name = "'.TOOL_TRACKING.'") ';
				}

				$sql = "SELECT a.*, t.image img, t.row, t.column  FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
						$condition_display_tools ORDER BY t.row, t.column";
				break;

			case 'External' :
				if (api_is_allowed_to_edit()) {
					$sql = "SELECT a.*, t.image img FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
							WHERE (a.link=t.link AND t.position='external')
							OR (a.visibility <= 1 AND (a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND a.image=t.image)
							ORDER BY a.id";
				} else {
					$sql = "SELECT a.*, t.image img FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
							WHERE a.visibility = 1 AND ((a.link=t.link AND t.position='external')
							OR ((a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND a.image=t.image))
							ORDER BY a.id";
				}
				break;

			case 'courseAdmin' :
				$sql = "SELECT a.*, t.image img, t.row, t.column  FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
						WHERE admin=1 AND a.link=t.link ORDER BY t.row, t.column";
				break;

			case 'platformAdmin' :
				$sql = "SELECT *, image img FROM $TBL_ACCUEIL WHERE visibility = 2 ORDER BY id";
		}
		$result = Database::query($sql);

		// Grabbing all the tools from $course_tool_table
		while ($row = Database::fetch_array($result)) {
			if ($row['img'] != 'file_html.gif' && $row['img'] != 'file_html_na.gif'
					&& $row['img'] != 'scormbuilder.gif' && $row['img'] != 'scormbuilder_na.gif'
					&& $row['img'] != 'blog.gif' && $row['img'] != 'blog_na.gif'
					&& $row['img'] != 'external.gif' && $row['img'] != 'external_na.gif') {
				$row['name_translated'] = get_lang(ucfirst($row['name']));
			}
			$all_tools[] = $row;
		}

		// Grabbing all the links that have the property on_homepage set to 1
		if ($cat == 'External') {
			$tbl_link = Database :: get_course_table(TABLE_LINK);
			$tbl_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
			if (api_is_allowed_to_edit(null, true)) {
				$sql_links = "SELECT tl.*, tip.visibility
									FROM $tbl_link tl
									LEFT JOIN $tbl_item_property tip ON tip.tool='link' AND tip.ref=tl.id
									WHERE tl.on_homepage='1' AND tip.visibility != 2";
			} else {
				$sql_links = "SELECT tl.*, tip.visibility
									FROM $tbl_link tl
									LEFT JOIN $tbl_item_property tip ON tip.tool='link' AND tip.ref=tl.id
									WHERE tl.on_homepage='1' AND tip.visibility = 1";
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
			if (api_get_session_id() != 0 && in_array($tool['name'], array('course_maintenance', 'course_setting'))) {
					continue;
			}

			$cell_content = '';
			// The name of the tool
			$tool_name = !empty($tool['name_translated']) ? $tool['name_translated'] : @htmlspecialchars($tool['name'], ENT_QUOTES, $charset); // RH: added htmlspecialchars

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
			$tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img'];

			// VISIBLE
			if (($tool['visibility'] || (api_is_coach() && $tool['name'] == TOOL_TRACKING)) || $cat == 'courseAdmin' || $cat == 'platformAdmin') {
				if (strpos($tool['name'], 'visio_') !== false) {
					$cell_content .= '<a  href="javascript: void(0);" onclick="javascript: window.open(\'' . $tool['link'].$link_annex . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '"><img src="'.$tool['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
				} elseif (strpos($tool['name'], 'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
					$cell_content .= '<a href="javascript: void(0);" onclick="javascript: window.open(\'' .$tool['link'].$link_annex. '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '"><img src="'.$tool['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
				} else {
					$cell_content .= '<a href="'.$tool['link'].$link_annex.'" target="'.$tool['target'].'"><img src="'.$tool['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
				}
			}
			// INVISIBLE
			else {
				if (api_is_allowed_to_edit(null, true)) {
					if (strpos($tool['name'], 'visio_') !== false) {
						$cell_content .= '<a  href="javascript: void(0);" onclick="window.open(\'' . $tool['link'].$link_annex . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '"><img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n";
					} elseif (strpos($tool['name'],'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
						$cell_content .= '<a href="javascript: void(0);" onclick="javascript: window.open(\'' .$tool['link'].$link_annex. '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '" class="invisible"><img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
					} else {
						$cell_content .= '<a href="'.$tool['link'].$link_annex.'" target="'.$tool['target'].'" class="invisible"><img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n";// don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
					}
				} else {
					$cell_content .= '<img src="'.str_replace(".gif", "_na.gif", $tool['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'; // don't replace img with display::return_icon because $tool['img'] = api_get_path(WEB_IMG_PATH).$tool['img']
					$cell_content .= '<span class="invisible">'.$tool_name.'</span>';
				}
			}

			$lnk = array();
			if (api_is_allowed_to_edit(null, true) && $cat != "courseAdmin" && !strpos($tool['link'], 'learnpath_handler.php?learnpath_id') && !api_is_coach()) {
				if ($tool['visibility']) {
					$link['name'] = Display::return_icon('remove.gif', get_lang('Deactivate'), array('style' => 'vertical-align: middle;'));
					$link['cmd'] = "hide=yes";
					$lnk[] = $link;
				} else {
					$link['name'] = Display::return_icon('add.gif', get_lang('Activate'), array('style' => 'vertical-align: middle;'));
					$link['cmd'] = "restore=yes";
					$lnk[] = $link;

					/*if ($tool['img'] == api_get_path(WEB_IMG_PATH).'external.gif') {
						$link['name'] = get_lang('Remove');
						$link['cmd']  = 'remove=yes';
						if ($tool['visibility'] == 2 && $cat == 'platformAdmin') {
							$link['name'] = get_lang('Delete');
							$link['cmd'] = 'askDelete=yes';
							$lnk[] = $link;
						}
					}*/
				}
				//echo "<div class=courseadmin>";
				if (is_array($lnk)) {
					foreach ($lnk as & $this_lnk) {
						if ($tool['adminlink']) {
							$cell_content .= '<a href="'.$properties['adminlink'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
						} else {
							$cell_content .= '<a href="'.api_get_self().'?id='.$tool['id'].'&amp;'.$this_lnk['cmd'].'">'.$this_lnk['name'].'</a>';
						}
					}
				}

				// RH: Allow editing of invisible homepage links (modified external_module)
				if ($tool['added_tool'] == 1 && api_is_allowed_to_edit() && !$tool['visibility']
						&& $tool['image'] != 'scormbuilder.gif' && $tool['image'] != 'scormbuilder_na.gif') {
					$cell_content .= '<a class="nobold" href="'.api_get_path(WEB_CODE_PATH).'external_module/external_module.php?id='.$tool['id'].'">'.get_lang('Edit').'</a>';
				}
			}
			$table->setCellContents($cell_number / $numcols, ($cell_number) % $numcols, $cell_content);
			$table->updateCellAttributes($cell_number / $numcols, ($cell_number) % $numcols, 'width="32%" height="42"');
			$cell_number ++;
		}
		$table->display();
	} // end function showtools2($cat)

	/**
	 * Displays the tools of a certain category.
	 *
	 * @return void
	 * @param string $course_tool_category	contains the category of tools to display:
	 * "Public", "PublicButHide", "courseAdmin", "claroAdmin"
	 */
	function show_tool_2column($course_tool_category) {
		$charset = api_get_system_encoding();
		$web_code_path = api_get_path(WEB_CODE_PATH);
		$course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);

		switch ($course_tool_category) {

			case TOOL_PUBLIC:

					$condition_display_tools = ' WHERE visibility = 1 ';
					if (api_is_coach()) {
						$condition_display_tools = ' WHERE visibility = 1 OR (visibility = 0 AND name = "'.TOOL_TRACKING.'") ';
					}

					$result = Database::query("SELECT * FROM $course_tool_table $condition_display_tools ORDER BY id");
					$col_link ="##003399";
					break;

			case TOOL_PUBLIC_BUT_HIDDEN:

					$result = Database::query("SELECT * FROM $course_tool_table WHERE visibility=0 AND admin=0 ORDER BY id");
					$col_link ="##808080";
					break;

			case TOOL_COURSE_ADMIN:

					$result = Database::query("SELECT * FROM $course_tool_table WHERE admin=1 AND visibility != 2 ORDER BY id");
					$col_link ="##003399";
					break;

			case TOOL_PLATFORM_ADMIN:

					$result = Database::query("SELECT * FROM $course_tool_table WHERE visibility = 2 ORDER BY id");
					$col_link ="##003399";
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
		switch ($course_tool_category)  {
			case TOOL_PUBLIC:
				$sql_links="SELECT tl.*, tip.visibility
						FROM $course_link_table tl
						LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
						WHERE tl.on_homepage='1' AND tip.visibility = 1";
				break;
			case TOOL_PUBLIC_BUT_HIDDEN:
				$sql_links="SELECT tl.*, tip.visibility
					FROM $course_link_table tl
					LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
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
				if (api_get_session_id() != 0 && in_array($tool['name'], array('course_maintenance', 'course_setting'))) {
					continue;
				}

				if (!($i % 2)) {
					echo "<tr valign=\"top\">\n";
				}

				// NOTE : Table contains only the image file name, not full path
				if (stripos($tool['link'], 'http://') === false && stripos($tool['link'], 'https://') === false && stripos($tool['link'], 'ftp://') === false) {
					$tool['link'] = $web_code_path.$tool['link'];
				}
				if ($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN) {
				    $class = 'class="invisible"';
				}
				$qm_or_amp = strpos($tool['link'], '?') === false ? '?' : '&amp;';

				$tool['link'] = $tool['link'];
				echo '<td width="50%" height="30">';

				if (strpos($tool['name'], 'visio_') !== false) {
					echo '<a  '.$class.' href="javascript: void(0);" onclick="javascript: window.open(\'' . htmlspecialchars($tool['link']).(($tool['image']=="external.gif" || $tool['image']=="external_na.gif") ? '' : $qm_or_amp.api_get_cidreq()) . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '">';
				} elseif (strpos($tool['name'], 'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
					echo '<a href="javascript: void(0);" onclick="javascript: window.open(\'' . htmlspecialchars($tool['link']).$qm_or_amp.api_get_cidreq() . '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '"'.$class.'>';
				} else {
					echo '<a href="'. htmlspecialchars($tool['link']).(($tool['image'] == 'external.gif' || $tool['image'] == 'external_na.gif') ? '' : $qm_or_amp.api_get_cidreq()).'" target="' , $tool['target'], '" '.$class.'>';
				}

				if ($tool['image'] == 'file_html.gif' || $tool['image'] == 'file_html_na.gif'
						|| $tool['image'] == 'scormbuilder.gif' || $tool['image'] == 'scormbuilder_na.gif'
						|| $tool['image'] == 'blog.gif' || $tool['image'] == 'blog_na.gif'
						|| $tool['image'] == 'external.gif' || $tool['image'] == 'external_na.gif') {
					$tool_name = @htmlspecialchars($tool['name'], ENT_QUOTES, $charset);
				} else {
					$tool_name = get_lang(ucfirst($tool['name']));
				}
				echo Display::return_icon($tool['image'], $tool_name),'&nbsp;', $tool_name,'</a>';

				// This part displays the links to hide or remove a tool.
				// These links are only visible by the course manager.
				unset($lnk);
				if (api_is_allowed_to_edit(null, true) && !api_is_coach()) {

					if ($tool['visibility'] == '1' || $tool['name'] == TOOL_TRACKING) {
						$link['name'] = Display::return_icon('remove.gif', get_lang('Deactivate'));
						$link['cmd'] = 'hide=yes';
						$lnk[] = $link;
					}

					if ($course_tool_category == TOOL_PUBLIC_BUT_HIDDEN) {
						$link['name'] = Display::return_icon('add.gif', get_lang('Activate'));
						$link['cmd']  = 'restore=yes';
						$lnk[] = $link;

						if ($tool['added_tool'] == 1) {
							$link['name'] = Display::return_icon('delete.gif', get_lang('Remove'));
							$link['cmd']  = 'remove=yes';
							$lnk[] = $link;
						}
					}
					if ($tool['adminlink']) {
						echo '<a href="'.$tool['adminlink'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
					}

				}
				if (api_is_platform_admin() && !api_is_coach()) {
					if ($tool['visibility'] == 2) {
						$link['name'] = Display::return_icon('undelete.gif', get_lang('Activate'));

						$link['cmd']  = 'hide=yes';
						$lnk[] = $link;

						if ($tool['added_tool'] == 1) {
							$link['name'] = get_lang('Delete');
							$link['cmd'] = 'askDelete=yes';
							$lnk[] = $link;
						}
					}
					if ($tool['visibility'] == 0  && $tool['added_tool'] == 0) {
						$link['name'] = Display::return_icon('delete.gif', get_lang('Remove'));
						$link['cmd'] = 'remove=yes';
						$lnk[] = $link;
					}
				}
				if (is_array($lnk)) {
					foreach($lnk as $this_link) {
						if (!$tool['adminlink']) {
							echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;id='.$tool['id'].'&amp;'.$this_link['cmd'].'">'.$this_link['name'].'</a>';
						}
					}
				}

				// Allow editing of invisible homepage links (modified external_module)
				if ($tool['added_tool'] == 1 && api_is_allowed_to_edit(null, true) && !$tool['visibility']
						&& $tool['image'] != 'scormbuilder.gif' && $tool['image'] != 'scormbuilder_na.gif') {
					echo '<a class="nobold" href="'.api_get_path(WEB_CODE_PATH).'external_module/external_module.php?'.api_get_cidreq().'&amp;id='.$tool['id'].'">'.get_lang('Edit').'</a>';
				}
				echo "</td>\n";

				if ($i % 2) {
					echo "</tr>\n";
				}

				$i++;
			}
		}

		if ($i % 2) {
			echo "<td width=\"50%\">&nbsp;</td>\n", "</tr>\n";
		}
	}

	/**
	 * Gets the tools of a certain category. Returns an array expected
	 * by show_tools_category()
	 * @param string $course_tool_category	contains the category of tools to
	 * display: "toolauthoring", "toolinteraction", "tooladmin", "tooladminplatform"
	 * @return array
	 */

	public static function get_tools_category($course_tool_category) {
		global $_user;
		$web_code_path = api_get_path(WEB_CODE_PATH);
		$course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);
		$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
		$is_platform_admin = api_is_platform_admin();
		$all_tools_list = array();

		// Condition for the session
		$session_id = api_get_session_id();
		$condition_session = api_get_session_condition($session_id, true, true);

		switch ($course_tool_category) {
			case TOOL_STUDENT_VIEW:
					$condition_display_tools = ' WHERE visibility = 1 AND (category = "authoring" OR category = "interaction") ';
					if (api_is_coach()) {
						$condition_display_tools = ' WHERE (visibility = 1 AND (category = "authoring" OR category = "interaction")) OR (name = "'.TOOL_TRACKING.'")  ';
					}
					$sql = "SELECT * FROM $course_tool_table  $condition_display_tools $condition_session ORDER BY id";
					$result = Database::query($sql);
					$col_link ="##003399";
					break;
			case TOOL_AUTHORING:
					$sql = "SELECT * FROM $course_tool_table WHERE category = 'authoring' $condition_session ORDER BY id";
					$result = Database::query($sql);
					$col_link ="##003399";
					break;
			case TOOL_INTERACTION:
					$sql = "SELECT * FROM $course_tool_table WHERE category = 'interaction' $condition_session ORDER BY id";
					$result = Database::query($sql);
					$col_link ="##003399";
					break;
			case TOOL_ADMIN_VISIBLE:
					$sql = "SELECT * FROM $course_tool_table WHERE category = 'admin' AND visibility ='1' $condition_session ORDER BY id";
					$result = Database::query($sql);
					$col_link ="##003399";
					break;
			case TOOL_ADMIN_PLATEFORM:
					$sql = "SELECT * FROM $course_tool_table WHERE category = 'admin' $condition_session ORDER BY id";
					$result = Database::query($sql);
					$col_link ="##003399";
					break;
		}

		while ($temp_row = Database::fetch_array($result)) {
			$all_tools_list[] = $temp_row;
		}

		/*if(api_is_course_coach()) {
			$result = Database::query("SELECT * FROM $course_tool_table WHERE name='tracking'");
			$all_tools_list[]=Database :: fetch_array($result);
		}*/

		$i = 0;
		// Grabbing all the links that have the property on_homepage set to 1
		$course_link_table = Database::get_course_table(TABLE_LINK);
		$course_item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);

		switch ($course_tool_category) {
				case TOOL_AUTHORING:
					$sql_links = "SELECT tl.*, tip.visibility
						FROM $course_link_table tl
						LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
							WHERE tl.on_homepage='1' $condition_session";
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
						LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
							WHERE tl.on_homepage='1' $condition_session";
					break;

				case TOOL_ADMIN:
					$sql_links = "SELECT tl.*, tip.visibility
						FROM $course_link_table tl
						LEFT JOIN $course_item_property_table tip ON tip.tool='link' AND tip.ref=tl.id
							WHERE tl.on_homepage='1' $condition_session";
					break;

				default:
					$sql_links = null;
					break;
		}

		// Edited by Kevin Van Den Haute (kevin@develop-it.be) for integrating Smartblogs
		if ($sql_links != null) {
			$result_links = Database::query($sql_links);
			$properties = array();
			if (Database::num_rows($result_links) > 0) {
				while ($links_row = Database::fetch_array($result_links)) {
					unset($properties);
					$properties['name'] = $links_row['title'];
					$properties['session_id'] = $links_row['session_id'];
					$properties['link'] = $links_row['url'];
					$properties['visibility'] = $links_row['visibility'];
					$properties['image'] = ($links_row['visibility'] == '0') ? 'file_html.gif' : 'file_html.gif';
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
						$sql_blogs = "
							SELECT *
							FROM " . $tbl_blogs_rel_user . " blogs_rel_user
							WHERE blog_id = " . $blog_id;
					} else {
						$sql_blogs = "
							SELECT *
							FROM " . $tbl_blogs_rel_user . " blogs_rel_user
							WHERE
								blog_id = " . $blog_id . " AND
								user_id = " . api_get_user_id();
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
	 * @param array List of tools as returned by get_tools_category()
	 * @return void
	 */

	public static function show_tools_category($all_tools_list) {

		global $_user;
		$web_code_path = api_get_path(WEB_CODE_PATH);
		$course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);
		$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
		$is_platform_admin = api_is_platform_admin();
		$i = 0;
		if (isset($all_tools_list)) {
			$lnk = '';
			foreach ($all_tools_list as & $tool) {
				if (api_get_session_id() != 0 && in_array($tool['name'], array('course_maintenance', 'course_setting'))) {
					continue;
				}

				if (!($i % 2)) {
					echo "<tr valign=\"top\">\n";
	            }

				// This part displays the links to hide or remove a tool.
				// These links are only visible by the course manager.
				unset($lnk);
				echo '<td width="50%">'."\n";
				if ($is_allowed_to_edit && !api_is_coach()) {

					if ($tool['visibility'] == '1' && $tool['admin'] != '1') {
						$link['name'] = Display::return_icon('visible.gif', get_lang('Deactivate'), array('id' => 'linktool_'.$tool['id']));
						$link['cmd'] = 'hide=yes';
						$lnk[] = $link;
					}

					if ($tool['visibility'] == '0' && $tool['admin'] != '1') {
						$link['name'] = Display::return_icon('invisible.gif', get_lang('Activate'), array('id' => 'linktool_'.$tool['id']));
						$link['cmd'] = 'restore=yes';
						$lnk[] = $link;
					}

					if (!empty($tool['adminlink'])) {
						echo '<a href="'.$tool['adminlink'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
					}

				}

				// Both checks are necessary as is_platform_admin doesn't take student view into account
				if ($is_platform_admin && $is_allowed_to_edit) {
	 				if ($tool['admin'] != '1') {
						$link['cmd'] = 'hide=yes';
					}
				}

				if (isset($lnk) && is_array($lnk)) {
					foreach ($lnk as $this_link) {
						if (empty($tool['adminlink'])) {
							echo '<a class="make_visible_and_invisible"  href="'.api_get_self().'?'.api_get_cidreq().'&amp;id='.$tool['id'].'&amp;'.$this_link['cmd'].'">'.$this_link['name'].'</a>';
						}
					}
				} else {
					echo '&nbsp;&nbsp;&nbsp;&nbsp;';
				}

				// NOTE : Table contains only the image file name, not full path
				if (stripos($tool['link'], 'http://') === false && stripos($tool['link'], 'https://') === false && stripos($tool['link'], 'ftp://') === false) {
					$tool['link'] = $web_code_path.$tool['link'];
	            }
				if ($tool['visibility'] == '0' && $tool['admin'] != '1') {
				  	$class = 'class="invisible"';
				  	$info = pathinfo($tool['image']);
				  	$basename = basename($tool['image'], '.'.$info['extension']); // $file is set to "index"
					$tool['image'] = $basename.'_na.'.$info['extension'];
				} else {
					$class = '';
				}

				$qm_or_amp = strpos($tool['link'], '?') === false ? '?' : '&amp;';
				// If it's a link, we don't add the cidReq
				if ($tool['image'] == 'file_html.gif' || $tool['image'] == 'file_html_na.gif') {
					$tool['link'] = $tool['link'].$qm_or_amp;
				} else {
					$tool['link'] = $tool['link'].$qm_or_amp.api_get_cidreq();
				}

				if (strpos($tool['name'],'visio_') !== false) {
					$toollink = "\t" . '<a id="tooldesc_'.$tool["id"].'"  ' . $class . ' href="javascript: void(0);" onclick="javascript: window.open(\'' . htmlspecialchars($tool['link']) . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '">';
					$my_tool_link = "\t" . '<a id="istooldesc_'.$tool["id"].'"  ' . $class . ' href="javascript: void(0);" onclick="javascript: window.open(\'' . htmlspecialchars($tool['link']) . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '">';

				} elseif (strpos($tool['name'], 'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
					$toollink = "\t" . '<a id="tooldesc_'.$tool["id"].'" ' . $class . ' href="javascript: void(0);" onclick="javascript: window.open(\'' . htmlspecialchars($tool['link']) . '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '">';
					$my_tool_link="\t" . '<a id="istooldesc_'.$tool["id"].'" ' . $class . ' href="javascript: void(0);" onclick="javascript: window.open(\'' . htmlspecialchars($tool['link']) . '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $tool['target'] . '">';

				} else {

					if (count(explode('type=classroom',$tool['link'])) == 2 || count(explode('type=conference', $tool['link'])) == 2) {
						$toollink = "\t" . '<a id="tooldesc_'.$tool["id"].'" ' . $class . ' href="' . $tool['link'] . '" target="_blank">';
						$my_tool_link = "\t" . '<a id="istooldesc_'.$tool["id"].'" ' . $class . ' href="' . $tool['link'] . '" target="_blank">';

					} else {
						$toollink = "\t" . '<a id="tooldesc_'.$tool["id"].'" ' . $class . ' href="' . htmlspecialchars($tool['link']) . '" target="' . $tool['target'] . '">';
						$my_tool_link = "\t" . '<a id="istooldesc_'.$tool["id"].'" ' . $class . ' href="' . htmlspecialchars($tool['link']) . '" target="' . $tool['target'] . '">';

					}

				}
				echo $toollink;

				if ($tool['image'] == 'file_html.gif' || $tool['image'] == 'file_html_na.gif'
						|| $tool['image'] == 'scormbuilder.gif' || $tool['image'] == 'scormbuilder_na.gif'
						|| $tool['image'] == 'blog.gif' || $tool['image'] == 'blog_na.gif'
						|| $tool['image'] == 'external.gif' || $tool['image'] == 'external_na.gif') {
					$tool_name = stripslashes($tool['name']);
				} else {
					$list = explode('_', $tool['name']);
					foreach ($list as & $item) { $item = ucfirst($item); }
					$tool_name = get_lang('Tool'.implode($list));
				}
				Display::display_icon($tool['image'], $tool_name, array('class' => 'tool-icon', 'id' => 'toolimage_'.$tool['id']));

				// Validacion when belongs to a session
				$session_img = api_get_session_image($tool['session_id'], $_user['status']);

				echo '</a> ';
				echo $my_tool_link;
				echo "{$tool_name}$session_img";
				echo "\t".'</a></td>';

				if ($i % 2) {
					echo '</tr>';
				}

				$i++;
			}
		}

		if ($i % 2) {
			echo "<td width=\"50%\">&nbsp;</td>\n", "</tr>\n";
		}
	}

	/**
	 * Shows the general data for a particular meeting
	 *
	 * @param id	session id
	 * @return string	session data
	 */
	public static function show_session_data($id_session) {
		$session_table 			= Database::get_main_table(TABLE_MAIN_SESSION);
		$user_table 			= Database::get_main_table(TABLE_MAIN_USER);
		$session_category_table = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);

		if ($id_session != strval(intval($id_session))) {
			return '';
		} else {
			$id_session = intval($id_session);
		}

		$sql = 'SELECT name, nbr_courses, nbr_users, nbr_classes, DATE_FORMAT(date_start,"%d-%m-%Y") as date_start, DATE_FORMAT(date_end,"%d-%m-%Y") as date_end, lastname, firstname, username, session_admin_id, nb_days_access_before_beginning, nb_days_access_after_end, session_category_id, visibility
					FROM '.$session_table.'
				LEFT JOIN '.$user_table.'
					ON id_coach = user_id
				WHERE '.$session_table.'.id='.$id_session;

		$rs = Database::query($sql);
		$session = Database::store_result($rs);
		$session = $session[0];

		$sql_category = 'SELECT name FROM '.$session_category_table.' WHERE id = "'.intval($session['session_category_id']).'"';
		$rs_category = Database::query($sql_category);
		$session_category = '';
		if (Database::num_rows($rs_category) > 0) {
			$rows_session_category = Database::store_result($rs_category);
			$rows_session_category = $rows_session_category[0];
			$session_category = $rows_session_category['name'];
		}

		if ($session['date_start'] == '00-00-0000') {
			$msg_date = get_lang('NoTimeLimits');
		} else {
			$msg_date = get_lang('From').' '.$session['date_start'].' '.get_lang('To').' '.$session['date_end'];
		}

		$output  = '';
		if (!empty($session_category)) {
			$output .= '<tr><td>'. get_lang('SessionCategory') . ': ' . '<b>' . $session_category .'</b></td></tr>';
		}
		$output .= '<tr><td style="width:50%">'. get_lang('SessionName') . ': ' . '<b>' . $session['name'] .'</b></td><td>'. get_lang('GeneralCoach') . ': ' . '<b>' . $session['lastname'].' '.$session['firstname'].' ('.$session['username'].')' .'</b></td></tr>';
		$output .= '<tr><td>'. get_lang('SessionIdentifier') . ': '. Display::return_icon('star.png', ' ', array('align' => 'absmiddle')) .'</td><td>'. get_lang('Date') . ': ' . '<b>' . $msg_date .'</b></td></tr>';

		return $output;
	}

}
