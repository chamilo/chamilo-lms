<?php
/* For licensing terms, see /license.txt */
/**
*	This is a display library for Chamilo.
*
*	Include/require it in your code to use its public functionality.
*	There are also several display public functions in the main api library.
*
*	All public functions static public functions inside a class called Display,
*	so you use them like this: e.g.
*	Display::display_normal_message($message)
*
*	@package chamilo.library
*/

/**
*	Display class
*	contains several public functions dealing with the display of
*	table data, messages, help topics, ...
*
*	@package chamilo.library
*/

class Display {
	private function __construct() {

	}
	/**
	 * Displays the tool introduction of a tool.
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @param string $tool These are the constants that are used for indicating the tools.
	 * @param array $editor_config Optional configuration settings for the online editor.
	 * @return $tool return a string array list with the "define" in main_api.lib
	 * @return html code for adding an introduction
	 */
	public static function display_introduction_section($tool, $editor_config = null) {
		$is_allowed_to_edit = api_is_allowed_to_edit();
		$moduleId = $tool;
		if (api_get_setting('enable_tool_introduction') == 'true' || $tool == TOOL_COURSE_HOMEPAGE) {
			require api_get_path(INCLUDE_PATH).'introductionSection.inc.php';
		}
	}

	/**
	 *	Displays a localised html file
	 *	tries to show the file "$full_file_name"."_".$language_interface.".html"
	 *	and if this does not exist, shows the file "$full_file_name".".html"
	 *	warning this public function defines a global
	 *	@param $full_file_name, the (path) name of the file, without .html
	 *	@return return a string with the path
	 */
	public static function display_localised_html_file($full_file_name) {
		global $language_interface;
		$localised_file_name = $full_file_name.'_'.$language_interface.'.html';
		$default_file_name = $full_file_name.'.html';
		if (file_exists($localised_file_name)) {
			include $localised_file_name;
		} else {
			include ($default_file_name);
		}
	}

	/**
	 *	Display simple html header of table.
	 */
	public static function display_table_header() {
		$bgcolor = 'bgcolor="white"';
		echo '<table border="0" cellspacing="0" cellpadding="4" width="85%"><tbody>';
		return $bgcolor;
	}

	/**
	 *	Display html header of table with several options.
	 *
	 *	@param $properties, array with elements, all of which have defaults
	 *	"width" - the table width, e.g. "100%", default is 85%
	 *	"class"	 - the class to use for the table, e.g. "class=\"data_table\""
	 *   "cellpadding"  - the extra border in each cell, e.g. "8",default is 4
	 *   "cellspacing"  - the extra space between cells, default = 0
	 *	@param column_header, array with the header elements.
	 *	@author Roan Embrechts
	 *	@version 1.01
	 *	@return return type string, bgcolor
	 */
	public static function display_complex_table_header($properties, $column_header) {
		$width = $properties['width'];
		if (!isset($width)) {
			$width = '85%';
		}
		$class = $properties['class'];
		if (!isset($class)) {
			$class = 'class="data_table"';
		}
		$cellpadding = $properties['cellpadding'];
		if (!isset($cellpadding)) {
			$cellpadding = '4';
		}
		$cellspacing = $properties['cellspacing'];
		if (!isset ($cellspacing)) {
			$cellspacing = '0';
		}
		//... add more properties as you see fit
		//api_display_debug_info("Light grey is " . DOKEOSLIGHTGREY);
		$bgcolor = 'bgcolor="'.DOKEOSLIGHTGREY.'"';
		echo '<table '.$class.' border="0" cellspacing="$cellspacing" cellpadding="'.$cellpadding.'" width="'.$width.'">'."\n";
		echo '<thead><tr '.$bgcolor.'>';
		foreach ($column_header as & $table_element) {
			echo '<th>'.$table_element.'</th>';
		}
		echo "</tr></thead>\n";
		echo "<tbody>\n";
		$bgcolor = 'bgcolor="'.HTML_WHITE.'"';
		return $bgcolor;
	}

	/**
	 *	Displays a table row.
	 *
	 *	@param $bgcolor the background colour for the table
	 *	@param $table_row an array with the row elements
	 *	@param $is_alternating true: the row colours alternate, false otherwise
	 */
	public static function display_table_row($bgcolor, $table_row, $is_alternating = true) {
		echo '<tr '.$bgcolor.'>';
		foreach ($table_row as & $table_element) {
			echo '<td>'.$table_element.'</td>';
		}
		echo "</tr>\n";
		if ($is_alternating) {
			if ($bgcolor == 'bgcolor="'.HTML_WHITE.'"') {
				$bgcolor = 'bgcolor="'.DOKEOSLIGHTGREY.'"';
			} elseif ($bgcolor == 'bgcolor="'.DOKEOSLIGHTGREY.'"') {
				$bgcolor = 'bgcolor="'.HTML_WHITE.'"';
			}
		}
		return $bgcolor;
	}

	/**
	 *	Displays a table row.
	 *	This public function has more options and is easier to extend than display_table_row()
	 *
	 *	@param $properties, array with properties:
	 *	["bgcolor"] - the background colour for the table
	 *	["is_alternating"] - true: the row colours alternate, false otherwise
	 *	["align_row"] - an array with, per cell, left|center|right
	 *	@todo add valign property
	 */
	public static function display_complex_table_row($properties, $table_row) {
		$bgcolor = $properties['bgcolor'];
		$is_alternating = $properties['is_alternating'];
		$align_row = $properties['align_row'];
		echo '<tr '.$bgcolor.'>';
		$number_cells = count($table_row);
		for ($i = 0; $i < $number_cells; $i++) {
			$cell_data = $table_row[$i];
			$cell_align = $align_row[$i];
			echo '<td align="'.$cell_align.'">'.$cell_data.'</td>';
		}
		echo "</tr>\n";
		if ($is_alternating) {
			if ($bgcolor == 'bgcolor="'.HTML_WHITE.'"') {
				$bgcolor = 'bgcolor="'.DOKEOSLIGHTGREY.'"';
			} elseif ($bgcolor == 'bgcolor="'.DOKEOSLIGHTGREY.'"') {
				$bgcolor = 'bgcolor="'.HTML_WHITE.'"';
			}
		}
		return $bgcolor;
	}

	/**
	 *	Displays html footer of table
	 */
	public static function display_table_footer() {
		echo '</tbody></table>';
	}

	/**
	 * Displays a table
	 * @param array $header Titles for the table header
	 * 						each item in this array can contain 3 values
	 * 						- 1st element: the column title
	 * 						- 2nd element: true or false (column sortable?)
	 * 						- 3th element: additional attributes for
	 *  						th-tag (eg for column-width)
	 * 						- 4the element: additional attributes for the td-tags
	 * @param array $content 2D-array with the tables content
	 * @param array $sorting_options Keys are:
	 * 					'column' = The column to use as sort-key
	 * 					'direction' = SORT_ASC or SORT_DESC
	 * @param array $paging_options Keys are:
	 * 					'per_page_default' = items per page when switching from
	 * 										 full-	list to per-page-view
	 * 					'per_page' = number of items to show per page
	 * 					'page_nr' = The page to display
	 * @param array $query_vars Additional variables to add in the query-string
	 * @param string The style that the table will show. You can set 'table' or 'grid'
	 * @author bart.mollet@hogent.be
	 */
	public static function display_sortable_table($header, $content, $sorting_options = array(), $paging_options = array(), $query_vars = null, $form_actions = array(), $style = 'table') {
		if (!class_exists('SortableTable')) {
			require_once 'sortabletable.class.php';
		}
		global $origin;
		$column = isset($sorting_options['column']) ? $sorting_options['column'] : 0;
		$default_items_per_page = isset($paging_options['per_page']) ? $paging_options['per_page'] : 20;

		$table = new SortableTableFromArray($content, $column, $default_items_per_page);

		if (is_array($query_vars)) {
			$table->set_additional_parameters($query_vars);
		}
		if ($style == 'table') {
			if (is_array($header) && count($header) > 0) {
				foreach ($header as $index => $header_item) {
					$table->set_header($index, $header_item[0], $header_item[1], $header_item[2], $header_item[3]);
				}
			}
			$table->set_form_actions($form_actions);
			$table->display();
		} else {
			$table->display_grid();
		}
	}

	/**
	 * Shows a nice grid
	 * @param string grid name (important to create css)
	 * @param array header content
	 * @param array array with the information to show
	 * @param array $paging_options Keys are:
	 * 					'per_page_default' = items per page when switching from
	 * 										 full-	list to per-page-view
	 * 					'per_page' = number of items to show per page
	 * 					'page_nr' = The page to display
	 * 					'hide_navigation' =  true to hide the navigation
	 * @param array $query_vars Additional variables to add in the query-string
	 * @param array $form actions Additional variables to add in the query-string
	 * @param mixed An array with bool values to know which columns show. i.e: $vibility_options= array(true, false) we will only show the first column
	 * 				Can be also only a bool value. TRUE: show all columns, FALSE: show nothing
	 */

	public static function display_sortable_grid($name, $header, $content, $paging_options = array(), $query_vars = null, $form_actions = array(), $vibility_options = true) {
		if (!class_exists('SortableTable')) {
			require_once 'sortabletable.class.php';
		}
		global $origin;
		$column =  0;
		$default_items_per_page = isset($paging_options['per_page']) ? $paging_options['per_page'] : 20;
		$table = new SortableTableFromArray($content, $column, $default_items_per_page, $name);

		if (is_array($query_vars)) {
			$table->set_additional_parameters($query_vars);
		}
		echo $table->display_simple_grid($vibility_options, $paging_options['hide_navigation']);
	}

	/**
	 * Gets a nice grid in html string
	 * @param string grid name (important to create css)
	 * @param array header content
	 * @param array array with the information to show
	 * @param array $paging_options Keys are:
	 * 					'per_page_default' = items per page when switching from
	 * 										 full-	list to per-page-view
	 * 					'per_page' = number of items to show per page
	 * 					'page_nr' = The page to display
	 * 					'hide_navigation' =  true to hide the navigation
	 * @param array $query_vars Additional variables to add in the query-string
	 * @param array $form actions Additional variables to add in the query-string
	 * @param mixed An array with bool values to know which columns show. i.e: $vibility_options= array(true, false) we will only show the first column
	 * 				Can be also only a bool value. TRUE: show all columns, FALSE: show nothing
	 * @param bool  true for sorting data or false otherwise
	 * @return 	string   html grid
	 */
	public static function return_sortable_grid($name, $header, $content, $paging_options = array(), $query_vars = null, $form_actions = array(), $vibility_options = true, $sort_data = true) {
		if (!class_exists('SortableTable')) {
			require_once 'sortabletable.class.php';
		}
		global $origin;
		$column =  0;
		$default_items_per_page = isset($paging_options['per_page']) ? $paging_options['per_page'] : 20;
		$table = new SortableTableFromArray($content, $column, $default_items_per_page, $name);

		if (is_array($query_vars)) {
			$table->set_additional_parameters($query_vars);
		}
		return $table->display_simple_grid($vibility_options, $paging_options['hide_navigation'], $paging_options['per_page'], $sort_data);
	}

	/**
	 * Displays a table with a special configuration
	 * @param array $header Titles for the table header
	 * 						each item in this array can contain 3 values
	 * 						- 1st element: the column title
	 * 						- 2nd element: true or false (column sortable?)
	 * 						- 3th element: additional attributes for
	 *  						th-tag (eg for column-width)
	 * 						- 4the element: additional attributes for the td-tags
	 * @param array $content 2D-array with the tables content
	 * @param array $sorting_options Keys are:
	 * 					'column' = The column to use as sort-key
	 * 					'direction' = SORT_ASC or SORT_DESC
	 * @param array $paging_options Keys are:
	 * 					'per_page_default' = items per page when switching from
	 * 										 full-	list to per-page-view
	 * 					'per_page' = number of items to show per page
	 * 					'page_nr' = The page to display
	 * @param array $query_vars Additional variables to add in the query-string
	 * @param array $column_show Array of binaries 1= show columns 0. hide a column
	 * @param array $column_order An array of integers that let us decide how the columns are going to be sort.
	 * 						      i.e:  $column_order=array('1''4','3','4'); The 2nd column will be order like the 4th column
	 * @param array $form_actions Set optional forms actions
	 *
	 * @author Julio Montoya
	 */
	public static function display_sortable_config_table($header, $content, $sorting_options = array(), $paging_options = array(), $query_vars = null, $column_show = array(), $column_order = array(), $form_actions = array()) {
		if (!class_exists('SortableTable')) {
			require_once 'sortabletable.class.php';
		}
		global $origin;
		$column = isset($sorting_options['column']) ? $sorting_options['column'] : 0;
		$default_items_per_page = isset($paging_options['per_page']) ? $paging_options['per_page'] : 20;

		$table = new SortableTableFromArrayConfig($content, $column, $default_items_per_page, 'tablename', $column_show, $column_order);

		if (is_array($query_vars)) {
			$table->set_additional_parameters($query_vars);
		}
		// Show or hide the columns header
		if (is_array($column_show)) {
			for ($i = 0; $i < count($column_show); $i++) {
				if (!empty($column_show[$i])) {
					$val0 = isset($header[$i][0]) ? $header[$i][0] : null;
					$val1 = isset($header[$i][1]) ? $header[$i][1] : null;
					$val2 = isset($header[$i][2]) ? $header[$i][2] : null;
					$val3 = isset($header[$i][3]) ? $header[$i][3] : null;
					$table->set_header($i, $val0, $val1, $val2, $val3);
				}
			}
		}
		$table->set_form_actions($form_actions);
		$table->display();
	}

	/**
	 * Displays a normal message. It is recommended to use this public function
	 * to display any normal information messages.
	 *
	 * @author Roan Embrechts
	 * @param string $message - include any additional html
	 *                          tags if you need them
	 * @param bool	Filter (true) or not (false)
	 * @return void
	 */
	public static function display_normal_message ($message, $filter = true) {
		if ($filter) {
			// Filter message
			$message = api_htmlentities($message, ENT_QUOTES, api_is_xml_http_request() ? 'UTF-8' : api_get_system_encoding());
		}
		if (!headers_sent()) {
			echo '
						<style type="text/css" media="screen, projection">
						/*<![CDATA[*/
						@import "'.api_get_path(WEB_CODE_PATH).'css/default.css";
						/*]]>*/
						</style>'; // TODO: There is no "default.css" file in this location.
		}
		echo '<div class="normal-message">';
		//Display :: display_icon('message_normal.gif', get_lang('InfoMessage'), array ('style' => 'float:left; margin-right:10px;'));
		/*
		get_lang('InfoMessage', array ('style' => 'float:left; margin-right:10px;'));
		*/
		echo $message.'</div>';
	}

	/**
	 * Displays an warning message. Use this if you want to draw attention to something
	 * This can also be used for instance with the hint in the exercises
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @param string $message
	 * @param bool	Filter (true) or not (false)
	 * @return void
	 */
	public static function display_warning_message($message, $filter = true) {
		if ($filter){
			// Filter message
			$message = api_htmlentities($message, ENT_QUOTES, api_is_xml_http_request() ? 'UTF-8' : api_get_system_encoding());
		}
		if (!headers_sent()) {
			echo '
						<style type="text/css" media="screen, projection">
						/*<![CDATA[*/
						@import "'.api_get_path(WEB_CODE_PATH).'css/default.css";
						/*]]>*/
						</style>'; // TODO: There is no "default.css" file in this location.
		}
		echo '<div class="warning-message">';
		//Display :: display_icon('message_warning.png', get_lang('WarningMessage'), array ('style' => 'float:left; margin-right:10px;'));
		/*
		get_lang('WarningMessage', array ('style' => 'float:left; margin-right:10px;'));
		*/
		echo $message.'</div>';
	}

	/**
	 * Displays an confirmation message. Use this if something has been done successfully
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @param string $message
	 * @param bool	Filter (true) or not (false)
	 * @return void
	 */
	public static function display_confirmation_message ($message, $filter = true) {
		if ($filter){
			// Filter message
			$message = api_htmlentities($message, ENT_QUOTES, api_is_xml_http_request() ? 'UTF-8' : api_get_system_encoding());
		}
		if (!headers_sent()) {
			echo '
						<style type="text/css" media="screen, projection">
						/*<![CDATA[*/
						@import "'.api_get_path(WEB_CODE_PATH).'css/default.css";
						/*]]>*/
						</style>'; // TODO: There is no "default.css" file in this location.
		}
		echo '<div class="confirmation-message">';
		//Display :: display_icon('message_confirmation.gif', get_lang('ConfirmationMessage'), array ('style' => 'float:left; margin-right:10px;margin-left:5px;'));
		/*
		get_lang('ConfirmationMessage', array ('style' => 'float:left; margin-right:10px;margin-left:5px;'));
		*/
		echo $message.'</div>';
	}

	/**
	 * Displays an error message. It is recommended to use this public function if an error occurs
	 *
	 * @author Hugues Peeters
	 * @author Roan Embrechts
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @param string $message - include any additional html
	 *                          tags if you need them
	 * @param bool	Filter (true) or not (false)
	 * @return void
	 */
	public static function display_error_message ($message, $filter = true) {
		if($filter){
			// Filter message
			$message = api_htmlentities($message, ENT_QUOTES, api_is_xml_http_request() ? 'UTF-8' : api_get_system_encoding());
		}
		if (!headers_sent()) {
			echo '
						<style type="text/css" media="screen, projection">
						/*<![CDATA[*/
						@import "'.api_get_path(WEB_CODE_PATH).'css/default.css";
						/*]]>*/
						</style>'; // TODO: There is no "default.css" file in this location.
		}
		echo '<div class="error-message">';
		//Display :: display_icon('message_error.png', get_lang('ErrorMessage'), array ('style' => 'float:left; margin-right:10px;'));
		/*
		get_lang('ErrorMessage', array ('style' => 'float:left; margin-right:10px;'));
		*/
		echo $message.'</div>';
	}

	/**
	 * Returns an encrypted mailto hyperlink
	 *
	 * @param - $email (string) - e-mail
	 * @param - $text (string) - clickable text
	 * @param - $style_class (string) - optional, class from stylesheet
	 * @return - encrypted mailto hyperlink
	 */
	public static function encrypted_mailto_link ($email, $clickable_text = null, $style_class = '') {
		if (is_null($clickable_text)) {
			$clickable_text = $email;
		}
		// "mailto:" already present?
		if (substr($email, 0, 7) != 'mailto:') {
			$email = 'mailto:'.$email;
		}
		// Class (stylesheet) defined?
		if ($style_class != '') {
			$style_class = ' class="'.$style_class.'"';
		}
		// Encrypt email
		$hmail = '';
		for ($i = 0; $i < strlen($email); $i ++) {
			$hmail .= '&#'.ord($email {
			$i }).';';
		}
		// Encrypt clickable text if @ is present
		if (strpos($clickable_text, '@')) {
			for ($i = 0; $i < strlen($clickable_text); $i ++) {
				$hclickable_text .= '&#'.ord($clickable_text {
				$i }).';';
			}
		} else {
			$hclickable_text = @htmlspecialchars($clickable_text, ENT_QUOTES, api_get_system_encoding());
		}
		// Return encrypted mailto hyperlink
		return '<a href="'.$hmail.'"'.$style_class.' name="clickable_email_link">'.$hclickable_text.'</a>';
	}

	/**
	 *	Creates a hyperlink to the platform homepage.
	 *	@param string $name, the visible name of the hyperlink, default is sitename
	 *	@return string with html code for hyperlink
	 */
	public static function get_platform_home_link_html($name = '') {
		if ($name == '') {
			$name = api_get_setting('siteName');
		}
		return '<a href="'.api_get_path(WEB_PATH).'index.php">'.$name.'</a>';
	}

	/**
	 * Displays the page header
	 * @param string The name of the page (will be showed in the page title)
	 * @param string Optional help file name
	 */
	public static function display_header($tool_name ='', $help = null) {
		$nameTools = $tool_name;
		global $_plugins, $lp_theme_css, $mycoursetheme, $user_theme, $platform_theme;
		global $httpHeadXtra, $htmlHeadXtra, $htmlIncHeadXtra, $_course, $_user, $clarolineRepositoryWeb, $text_dir, $plugins, $_user, $rootAdminWeb, $_cid, $interbreadcrumb, $charset, $language_file, $noPHP_SELF;
		global $menu_navigation;
		require api_get_path(INCLUDE_PATH).'header.inc.php';
	}

	/**
	 * Displays the reduced page header (without banner)
	 */
	public static function display_reduced_header () {
		global $_plugins, $lp_theme_css, $mycoursetheme, $user_theme, $platform_theme;
		global $httpHeadXtra, $htmlHeadXtra, $htmlIncHeadXtra, $_course, $_user, $clarolineRepositoryWeb, $text_dir, $plugins, $_user, $rootAdminWeb, $_cid, $interbreadcrumb, $charset, $language_file, $noPHP_SELF, $language_interface;
		global $menu_navigation;
		require api_get_path(INCLUDE_PATH).'reduced_header.inc.php';
	}

	/**
	 * Display the page footer
	 */
	public static function display_footer () {
		global $_plugins;
		require api_get_path(INCLUDE_PATH).'footer.inc.php';
	}

	/**
	 * Prints an <option>-list with all letters (A-Z).
	 * @param char $selected_letter The letter that should be selected
	 * @todo This is English language specific implementation. It should be adapted for the other languages.
	 */
	public static function get_alphabet_options($selected_letter = '') {
		$result = '';
		for ($i = 65; $i <= 90; $i ++) {
			$letter = chr($i);
			$result .= '<option value="'.$letter.'"';
			if ($selected_letter == $letter) {
				$result .= ' selected="selected"';
			}
			$result .= '>'.$letter.'</option>';
		}
		return $result;
	}

	public static function get_numeric_options($min, $max, $selected_num = 0) {
		$result = '';
		for ($i = $min; $i <= $max; $i ++) {
			$result .= '<option value="'.$i.'"';
			if (is_int($selected_num))
				if ($selected_num == $i) {
					$result .= ' selected="selected"';
				}
			$result .= '>'.$i.'</option>';
		}
		return $result;
	}

	/**
	 * Shows the so-called "left" menu for navigating
	 */
	public static function show_course_navigation_menu($isHidden = false) {
		global $output_string_menu;
		global $_setting;

		// Check if the $_SERVER['REQUEST_URI'] contains already url parameters (thus a questionmark)
		if (strpos($_SERVER['REQUEST_URI'], '?') === false) {
			$sourceurl = api_get_self().'?';
		} else {
			$sourceurl = $_SERVER['REQUEST_URI'];
		}
		$output_string_menu = '';
		if ($isHidden == 'true' and $_SESSION['hideMenu']) {

			$_SESSION['hideMenu'] = 'hidden';

			$sourceurl = str_replace('&isHidden=true', '', $sourceurl);
			$sourceurl = str_replace('&isHidden=false', '', $sourceurl);

			$output_string_menu .= ' <a href="'.$sourceurl.'&isHidden=false"><img src="../../main/img/expand.gif" alt="'.'Show menu1'.'" padding:"2px"/></a>';
		} elseif ($isHidden == 'false' && $_SESSION['hideMenu']) {
			$sourceurl = str_replace('&isHidden=true', '', $sourceurl);
			$sourceurl = str_replace('&isHidden=false', '', $sourceurl);

			$_SESSION['hideMenu'] = 'shown';
			$output_string_menu .= '<div id="leftimg"><a href="'.$sourceurl.'&isHidden=true"><img src="../../main/img/collapse.gif" alt="'.'Hide menu2'.'" padding:"2px"/></a></div>';
		} elseif ($_SESSION['hideMenu']) {
			if ($_SESSION['hideMenu'] == 'shown') {
				$output_string_menu .= '<div id="leftimg"><a href="'.$sourceurl.'&isHidden=true"><img src="../../main/img/collapse.gif" alt="'.'Hide menu3'.' padding:"2px"/></a></div>';
			}
			if ($_SESSION['hideMenu'] == 'hidden') {
				$sourceurl = str_replace('&isHidden=true', '', $sourceurl);
				$output_string_menu .= '<a href="'.$sourceurl.'&isHidden=false"><img src="../../main/img/expand.gif" alt="'.'Hide menu4'.' padding:"2px"/></a>';
			}
		} elseif (!$_SESSION['hideMenu']) {
			$_SESSION['hideMenu'] = 'shown';
			if (isset($_cid)) {
				$output_string_menu .= '<div id="leftimg"><a href="'.$sourceurl.'&isHidden=true"><img src="main/img/collapse.gif" alt="'.'Hide menu5'.' padding:"2px"/></a></div>';
			}
		}
	}

	/**
	 * This public function displays an icon
	 * @param string $image the filename of the file (in the main/img/ folder
	 * @param string $alt_text the alt text (probably a language variable)
	 * @param array additional attributes (for instance height, width, onclick, ...)
	*/
	public static function display_icon($image, $alt_text = '', $additional_attributes = array()) {
		echo self::return_icon($image, $alt_text, $additional_attributes);
	}

	/**
	 * This public function returns the htmlcode for an icon
	 *
	 * @param string $image the filename of the file (in the main/img/ folder
	 * @param string $alt_text the alt text (probably a language variable)
	 * @param array additional attributes (for instance height, width, onclick, ...)
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University 2006
     * @author Julio Montoya 2010 Function improved
	 * @version October 2006
	*/
	public static function return_icon($image, $alt_text = '', $additional_attributes = array()) {		
		return self::img(api_get_path(WEB_IMG_PATH).$image, $alt_text,$additional_attributes);
	}
    
    /**
     * Returns the htmlcode for an image
     *       
     * @param string $image the filename of the file (in the main/img/ folder
     * @param string $alt_text the alt text (probably a language variable)
     * @param array additional attributes (for instance height, width, onclick, ...)
     * @author Julio Montoya 2010
     */
    public static function img($image_path, $alt_text = '', $additional_attributes = array()) {
        $attribute_list = '';
        // alt text = the image name if there is none provided (for XHTML compliance)
        if ($alt_text == '') {
            $alt_text = basename($image_path);
        } 
        $image_path = Security::remove_XSS($image_path);
        
        $additional_attributes['src']   = $image_path;
        
        if (empty($additional_attributes['alt'])) {
            $additional_attributes['alt']   = $alt_text;
        }
        if (empty($additional_attributes['title'])) {
            $additional_attributes['title'] = $alt_text;
        }        
        //return '<img src="'.$image_path.'" alt="'.$alt_text.'"  title="'.$alt_text.'" '.$attribute_list.' />';
        return self::tag('img','',$additional_attributes);        
    }
    
    
    /**
     * Returns the htmlcode for a tag (h3, h1, div, a, button), etc
     *       
     * @param string $image the filename of the file (in the main/img/ folder
     * @param string $alt_text the alt text (probably a language variable)
     * @param array additional attributes (for instance height, width, onclick, ...)
     * @author Julio Montoya 2010
     */
    public static function tag($tag, $content, $additional_attributes = array()) {
        $attribute_list = '';    
        // Managing the additional attributes
        if (!empty($additional_attributes) && is_array($additional_attributes)) {
            $attribute_list = '';
            foreach ($additional_attributes as $key => & $value) {
                $attribute_list .= $key.'="'.$value.'" ';
            }
        }   
        //some tags don't have this </XXX>
        if (in_array($tag, array('img','input','br'))) {
            $return_value = '<'.$tag.' '.$attribute_list.' />';
        } else {
            $return_value = '<'.$tag.' '.$attribute_list.' > '.$content.'</'.$tag.'>';
        }        
        return $return_value;        
    }
    
    public static function url($name, $url, $extra_attributes = array()) {
        if (!empty($url)) {
            $extra_attributes['href']= $url;
        }        
        return self::tag('a', $name, $extra_attributes);    	
    }
    
    public static function div($content, $extra_attributes = array()) {      
        return self::tag('div', $content, $extra_attributes);        
    }
    
     /**
     * Displays an HTML input tag
     * 
     */
    public static function input($type, $name,  $value, $extra_attributes = array()) {
    	 if (!empty($type)) {
            $extra_attributes['type']= $type;
         }
         if (!empty($name)) {
            $extra_attributes['name']= $name;
         }
         if (!empty($value)) {   
            $extra_attributes['value']= $value;
        }
        return self::tag('input', '',$extra_attributes);        
    }
    
    /**
     * Displays an HTML select tag
     * 
     */
    public function select($name, $values, $default = -1, $extra_attributes = array(), $show_blank_item = true) {        
        $extra = '';
        $default_id =  'id="'.$name.'" ';
        foreach($extra_attributes as $key=>$parameter) {
            if ($key == 'id') {
            	$default_id = '';
            }
            $extra .= $key.'="'.$parameter.'"';
        }
        $html .= '<select name="'.$name.'" '.$default_id.' '.$extra.'>';
    
        if ($show_blank_item) {
            $html .= self::tag('option', '-- '.get_lang('Select').' --', array('value'=>'-1'));
        }
        if($values) {
            foreach($values as $key => $value) {
                if(is_array($value) && isset($value['name'])) {
                    $value = $value['name'];
                }
                $html .= '<option value="'.$key.'"';
                if($default == $key) {
                    $html .= 'selected="selected"';
                }
                $html .= '>'.$value.'</option>';
            }
        }       
        $html .= '</select>';       
        return $html;
    }
    
    /**
     * Creates a tab menu  
     * Requirements: declare the jquery, jquery-ui libraries + the jquery-ui.css  in the $htmlHeadXtra variable before the display_header
     * Add this script
     * @example 
             * <script>
                    $(function() {
                        $( "#tabs" ).tabs();                
                    });
                </script>
     * @param   array   list of the tab titles
     * @param   array   content that will be showed
     * @param   string  the id of the container of the tab in the example "tabs"
     * @param   array   attributes for the ul 
     *   
     */
    public static function tabs($header_list, $content_list, $id = 'tabs', $ul_attributes = array()) {
        
        if (empty($header_list) || count($header_list) == 0 ) {
        	return '';
        }       
        
        $lis = '';        
        $i = 1;
        foreach ($header_list as $item) {
            
            $item =self::tag('a', $item, array('href'=>'#'.$id.'-'.$i)); 
                       
        	$lis .=self::tag('li', $item, $ul_attributes);
            $i++;
        }
        $ul = self::tag('ul',$lis);
        
        $i = 1;
        $divs = '';
        foreach ($content_list as $content) {     
            $content = self::tag('p',$content);       
        	$divs .=self::tag('div', $content, array('id'=>$id.'-'.$i));
            $i++;
        }        
        $main_div = self::tag('div',$ul.$divs, array('id'=>$id));
        return $main_div ;    	
    }
    
    /**
     * Displays the html needed by the grid_js function
     */
    public static function grid_html($div_id){
    	$table  = self::tag('table','',array('id'=>$div_id));
        $table .= self::tag('div','',array('id'=>$div_id.'_pager'));
        return $table; 
    }
    
    /** 
     * This is just a wrapper to use the jqgrid For the other options go here http://www.trirand.com/jqgridwiki/doku.php?id=wiki:options 
     * This function need to be in the ready jquery function example --> $(function() { <?php echo Display::grid_js('grid' ...); ?> }
     * In order to work this function needs the Display::grid_html function with the same div id
     * @param   string  div id
     * @param   string  url where the jqgrid will ask for data
     * @param   array   Visible columns (you should use get_lang). An array in which we place the names of the columns. This is the text that appears in the head of the grid (Header layer). Example: colname   {name:'date',     index:'date',   width:120, align:'right'}, 
     * @param   array   the column model :  Array which describes the parameters of the columns.This is the most important part of the grid. For a full description of all valid values see colModel API. See the url above.
     * @param   array   extra parameters
     * @param   array   data that will be loaded
     * @return  string  the js code 
     * 
     */
    public static function grid_js($div_id, $url, $column_names, $column_model, $extra_params, $data = array(), $formatter = '') {
        $obj = new stdClass();
              
        if (!empty($url))
            $obj->url       = $url;        
        $obj->colNames      = $column_names;        
        $obj->colModel      = $column_model;
        $obj->pager         = $div_id.'_pager';
        
        $obj->datatype  = 'json';
        if (!empty($extra_params['datatype'])) {
            $obj->datatype  = $extra_params['datatype'];
        }
        
        if (!empty($extra_params['sortname'])) {
            $obj->sortname      = $extra_params['sortname'];
        }
        //$obj->sortorder     = 'desc';
        if (!empty($extra_params['sortorder'])) {
            $obj->sortorder     = $extra_params['sortorder'];
        }
        
        if (!empty($extra_params['rowList'])) {
            $obj->rowList     = $extra_params['rowList'];
        }
        $obj->rowNum = 10;
        if (!empty($extra_params['rowNum'])) {
            $obj->rowNum     = $extra_params['rowNum'];
        }         
        
        //height: 'auto',     
        
        $obj->viewrecords = 'true';
         
        if (!empty($extra_params['viewrecords']))
            $obj->viewrecords   = $extra_params['viewrecords'];
            
        if (!empty($extra_params)) {
            foreach ($extra_params as $key=>$element){           	
                $obj->$key = $element;            	
            }
        }
        
        //Adding static data 
        if (!empty($data)) {
            $data_var = $div_id.'_data';
            $json.=' var '.$data_var.' = '.json_encode($data).';';
          /*  $json.='for(var i=0;i<='.$data_var.'.length;i++)
                    jQuery("#'.$div_id.'").jqGrid(\'addRowData\',i+1,'.$data_var.'[i]);';*/
            $obj->data = $data_var;
            $obj->datatype = 'local';
            $json.="\n";
        }        
        
        $json_encode = json_encode($obj);
        if (!empty($data)) {
            //Converts the "data":"js_variable" to "data":js_variable othersiwe it will not work
            $json_encode = str_replace('"data":"'.$data_var.'"','"data":'.$data_var.'',$json_encode);            
        }
        
        //Fixing true/false js values that doesn't need the ""
        $json_encode = str_replace(':"true"',':true',$json_encode);
        $json_encode = str_replace(':"false"',':false',$json_encode);
        
        $json_encode = str_replace('"formatter":"action_formatter"','formatter:action_formatter',$json_encode);
              
        //Creating the jqgrid element         
        $json .= '$("#'.$div_id.'").jqGrid(';
        $json .= $json_encode;
        $json .= ');';
    
        $json.="\n";
        
        //Adding edit/delete icons        
        $json.=$formatter;
        
        return $json;
        
        /*
        Real Example
        $("#list_week").jqGrid({
            url:'<?php echo api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_by_week&session_id='.$session_id; ?>',
            datatype: 'json',    
            colNames:['Week','Date','Course', 'LP'],
            colModel :[
              {name:'week',     index:'week',   width:120, align:'right'},       
              {name:'date',     index:'date',   width:120, align:'right'},
              {name:'course',   index:'course', width:150},  
              {name:'lp',       index:'lp',     width:250} 
            ],
            pager: '#pager3',
            rowNum:100,
             rowList:[10,20,30],    
            sortname: 'date',
            sortorder: 'desc',
            viewrecords: true,
            grouping:true, 
            groupingView : { 
                groupField : ['week'],
                groupColumnShow : [false],
                groupText : ['<b>Week {0} - {1} Item(s)</b>']
            } 
        });  */    	
    }
    /**
     * Display create course link
     *
     */
    function display_create_course_link() {
        echo '<li><a href="main/create_course/add_course.php">'.(api_get_setting('course_validation') == 'true' ? get_lang('CreateCourseRequest') : get_lang('CourseCreate')).'</a></li>';
    }
    
    
    /**
     * Display dashboard link
     *
     */
    function display_dashboard_link() {
        echo '<li><a href="main/dashboard/index.php">'.get_lang('Dashboard').'</a></li>';
    }
    
    /**
     * Display edit course list links
     *
     */
    function display_edit_course_list_links() {
        echo '<li><a href="main/auth/courses.php">'.get_lang('CourseManagement').'</a></li>';
    }
    
    /**
     * Show history sessions
     *
     */
    function display_history_course_session() {
        if (api_get_setting('use_session_mode') == 'true') {
            if (isset($_GET['history']) && intval($_GET['history']) == 1) {
                echo '<li><a href="user_portal.php">'.get_lang('DisplayTrainingList').'</a></li>';
            } else {
                echo '<li><a href="user_portal.php?history=1">'.get_lang('HistoryTrainingSessions').'</a></li>';
            }
        }
    }
} //end class Display