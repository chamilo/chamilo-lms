<?php
/* For licensing terms, see /license.txt */
/**
 *	Navigation menu display code
 *
 *	@package chamilo.include
 */
/**
 * Code
 */
define('SHORTCUTS_HORIZONTAL', 0);
define('SHORTCUTS_VERTICAL', 1);

require_once api_get_path(LIBRARY_PATH).'course_home.lib.php'; // For using the method CourseHome::translate_tool_name();

/**
 * Build the navigation items to show in a course menu
 * @param boolean $include_admin_tools
 */
function get_navigation_items($include_admin_tools = false) {

	
	global $is_courseMember;
	global $_user;
	global $_course;

	if (!empty($_course['db_name'])) {
		$database = $_course['db_name'];
	}

	$navigation_items = array ();
	$course_id = api_get_course_id();

	if (!empty($course_id) && $course_id != -1) {

		$user_id = api_get_user_id();

		$course_tools_table = Database :: get_course_table(TABLE_TOOL_LIST, $database);

		/*	Link to the Course homepage */

		$navigation_items['home']['image'] = 'home.gif';
		$navigation_items['home']['link'] = api_get_path(REL_COURSE_PATH).Security::remove_XSS($_SESSION['_course']['path']).'/index.php';
		$navigation_items['home']['name'] = get_lang('CourseHomepageLink');

		/*	Link to the different tools */

		$sql_menu_query = "SELECT * FROM $course_tools_table WHERE visibility='1' and admin='0' ORDER BY id ASC";
		$sql_result = Database::query($sql_menu_query);
		while ($row = Database::fetch_array($sql_result)) {
			$navigation_items[$row['id']] = $row;
			if (stripos($row['link'], 'http://') === false && stripos($row['link'], 'https://') === false) {
				$navigation_items[$row['id']]['link'] = api_get_path(REL_CODE_PATH).$row['link'];
				$navigation_items[$row['id']]['name'] = CourseHome::translate_tool_name($row);
			}
		}

		/*	Admin (edit rights) only links
			- Course settings (course admin only)
			- Course rights (roles & rights overview) */

		if ($include_admin_tools) {
			$course_settings_sql = "SELECT name,image FROM $course_tools_table
									WHERE link='course_info/infocours.php'";
			$sql_result = Database::query($course_settings_sql);
			$course_setting_info = Database::fetch_array($sql_result);
			$course_setting_visual_name = CourseHome::translate_tool_name($course_setting_info);
			if (api_get_session_id() == 0) {
				// course settings item
				$navigation_items['course_settings']['image'] = $course_setting_info['image'];
				$navigation_items['course_settings']['link'] = api_get_path(REL_CODE_PATH).'course_info/infocours.php';
				$navigation_items['course_settings']['name'] = $course_setting_visual_name;
			}
		}
	}
	foreach ($navigation_items as $key => $navigation_item) {
		if (strstr($navigation_item['link'], '?')) {
			//link already contains a parameter, add course id parameter with &
			$parameter_separator = '&amp;';
		} else {
			//link doesn't contain a parameter yet, add course id parameter with ?
			$parameter_separator = '?';
		}
		$navigation_items[$key]['link'] .= $parameter_separator.api_get_cidreq();
	}
	return $navigation_items;
}

/**
 * Show a navigation menu
 */
function show_navigation_menu() {
	$navigation_items = get_navigation_items(true);
	$course_id = api_get_course_id();
	if (api_get_setting('show_navigation_menu') == 'icons') {
		echo '<div style="float:right;width: 40px;position:absolute;right:10px;top:10px;">';
		show_navigation_tool_shortcuts($orientation = SHORTCUTS_VERTICAL);
		echo '</div>';
	} else {
	    echo '<div id="toolnav"> <!-- start of #toolnav -->';
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
		function createCookie(name, value, days)
		{
			if (days)
			{
				var date = new Date();
				date.setTime(date.getTime()+(days*24*60*60*1000));
				var expires = "; expires="+date.toGMTString();
			}
			else var expires = "";
			document.cookie = name+"="+value+expires+"; path=/";
		}
		function readCookie(name)
		{
			var nameEQ = name + "=";
			var ca = document.cookie.split(';');
			for (var i = 0; i < ca.length; i++)
			{
				var c = ca[i];
				while (c.charAt(0)==' ') c = c.substring(1,c.length);
				if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
			}
			return null;
		}
		function swap_menu()
		{
			toolnavlist_el = document.getElementById('toolnavlist');
			center_el = document.getElementById('center');
			swap_menu_link_el = document.getElementById('swap_menu_link');

			if (toolnavlist_el.style.display == 'none')
			{
				toolnavlist_el.style.display = '';
				if (center_el)
				{
					center_el.style.margin = '0 190px 0 0';
				}
				swap_menu_link_el.innerHTML = '<?php echo get_lang('Hide'); ?> &raquo;&raquo;';
				createCookie('dokeos_menu_state',1,10);
			}
			else
			{
				toolnavlist_el.style.display = 'none';
				if (center_el)
				{
					center_el.style.margin = '0 0 0 0';
				}
				swap_menu_link_el.innerHTML = '&laquo;&laquo; <?php echo get_lang('Show'); ?>';
				createCookie('dokeos_menu_state',0,10);
			}
		}
		document.write('<a href="javascript: void(0);" id="swap_menu_link" onclick="javascript: swap_menu();"><?php echo get_lang('Hide'); ?> &raquo;&raquo;<\/a>');
		/* ]]> */
		</script>
		<?php
		echo '<div id="toolnavbox">';
		echo '<div id="toolnavlist"><dl>';
		foreach ($navigation_items as $key => $navigation_item) {
			//students can't see the course settings option
			if (!api_is_allowed_to_edit() && $key == 'course_settings') {
				continue;
			}
			echo '<dd>';
			$url_item = parse_url($navigation_item['link']);
			$url_current = parse_url($_SERVER['REQUEST_URI']);

			if (strpos($navigation_item['link'], 'chat') !== false && api_get_course_setting('allow_open_chat_window', $course_id)) {
				echo '<a href="javascript: void(0);" onclick="javascript: window.open(\''.$navigation_item['link'].'\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $navigation_item['target'] . '"';
			} else {
				echo '<a href="'.$navigation_item['link'].'" target="_top" ';
			}

			if (stristr($url_item['path'], $url_current['path'])) {
				if (!isset($_GET['learnpath_id']) || strpos($url_item['query'],'learnpath_id='.$_GET['learnpath_id']) === 0) {
					echo ' id="here"';
				}
			}
			echo ' title="'.$navigation_item['name'].'">';
			if (api_get_setting('show_navigation_menu') != 'text') {
				echo '<div align="left"><img src="'.api_get_path(WEB_IMG_PATH).$navigation_item['image'].'" alt="'.$navigation_item['name'].'"/></div>';
			}
			if (api_get_setting('show_navigation_menu') != 'icons') {
				echo $navigation_item['name'];
			}
			echo '</a>';
			echo '</dd>';
			echo "\n";
		}
		echo '</dl></div></div>';
	    echo '</div> <!-- end "#toolnav" -->';
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
		if (readCookie('dokeos_menu_state') == 0) {
			swap_menu();
		}
		/* ]]> */
		</script>
		<?php
	}
}

/**
 * Show a toolbar with shortcuts to the course tool
 */
function show_navigation_tool_shortcuts($orientation = SHORTCUTS_HORIZONTAL) {
	$navigation_items = get_navigation_items(false);	
    if (!empty($navigation_items)) {
        if ($orientation == SHORTCUTS_HORIZONTAL)
            $style_id = "toolshortcuts_horizontal";
        else {
            $style_id = "toolshortcuts_vertical";
        }
        echo '<div id="'.$style_id.'">';
    
    	foreach ($navigation_items as $key => $navigation_item) {
    		if (strpos($navigation_item['link'],'chat') !== false && api_get_course_setting('allow_open_chat_window')) {
    		  	echo '<a href="javascript: void(0);" onclick="javascript: window.open(\''.$navigation_item['link'].'\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $navigation_item['target'] . '"';
    	    } else {
    		  	echo '<a href="'.$navigation_item['link'].'"';
    	    }    
    		if (strpos(api_get_self(), $navigation_item['link']) !== false) {
    			echo ' id="here"';
    		}
    		echo ' target="_top" title="'.$navigation_item['name'].'">';
    		echo '<img src="'.api_get_path(WEB_IMG_PATH).$navigation_item['image'].'" alt="'.$navigation_item['name'].'"/>';
    		echo '</a> ';
    		if ($orientation == SHORTCUTS_VERTICAL) {
    			echo '<br />';
    		}
    	}
    	echo '</div>';
    }
}
