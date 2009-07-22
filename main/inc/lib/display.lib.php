<?php

/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos S.A.
	Copyright (c) Roan Embrechts, Vrije Universiteit Brussel
	Copyright (c) Wolfgang Schneider
	Copyright (c) Bert Vanderkimpen, Ghent University
	Copyright (c) Bart Mollet, Hogeschool Gent
	Copyright (c) Rene Haentjens, Ghent University
	Copyright (c) Yannick Warnier, Dokeos S.A.
	Copyright (c) Sandra Matthys, Hogeschool Gent
	Copyright (c) Denes Nagy, Dokeos S.A.

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This is a display library for Dokeos.
*
*	Include/require it in your code to use its public functionality.
*	There are also several display public functions in the main api library.
*
*	All public functions static public functions inside a class called Display,
*	so you use them like this: e.g.
*	Display::display_normal_message($message)
*
*	@package dokeos.library
==============================================================================
*/
/*
==============================================================================
	   CONSTANTS
==============================================================================
*/
/*
==============================================================================
	   LIBRARIES
==============================================================================
*/
//no other libraries needed at the moment
/*
==============================================================================
		public functionS
==============================================================================
*/
//all public functions are stored inside the Display class
/*
==============================================================================
		CLASS Display

		public functions inside
		----------------
		Display::display_localised_html_file($full_file_name)
		Display::display_table_header()
		Display::display_complex_table_header($properties, $column_header)
		Display::display_table_row($bgcolor, $table_row, $is_alternating=true)
		Display::display_table_footer()
		Display::display_normal_message($message)
		Display::display_error_message($message)
		Display::encrypted_mailto_link($email, $clickable_text, $style_class='')
		Display::get_platform_home_link_html($name = '')
==============================================================================
*/
/**
*	Display class
*	contains several public functions dealing with the display of
*	table data, messages, help topics, ...
*
*	@version 1.0.4
*	@package dokeos.library
*/
require_once 'sortabletable.class.php';
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
	public static function display_introduction_section ($tool, $editor_config = null) {
		$is_allowed_to_edit = api_is_allowed_to_edit();
		$moduleId = $tool;
		if (api_get_setting('enable_tool_introduction') == 'true' || $tool==TOOL_COURSE_HOMEPAGE)
		{
			include (api_get_path(INCLUDE_PATH)."introductionSection.inc.php");
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
	public static function display_localised_html_file ($full_file_name) {
		global $language_interface;
		$localised_file_name = $full_file_name."_".$language_interface.".html";
		$default_file_name = $full_file_name.".html";
		if (file_exists($localised_file_name))
		{
			include ($localised_file_name);
		}
		else
		{
			include ($default_file_name); //default
		}
	}

	/**
	*	Display simple html header of table.
	*/
	public static function display_table_header () {
		$bgcolor = "bgcolor='white'";
		echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"4\" width=\"85%\"><tbody>";
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
	public static function display_complex_table_header ($properties, $column_header) {
		$width = $properties["width"];
		if (!isset ($width))
			$width = "85%";
		$class = $properties["class"];
		if (!isset ($class))
			$class = "class=\"data_table\"";
		$cellpadding = $properties["cellpadding"];
		if (!isset ($cellpadding))
			$cellpadding = "4";
		$cellspacing = $properties["cellspacing"];
		if (!isset ($cellspacing))
			$cellspacing = "0";
		//... add more properties as you see fit
		//api_display_debug_info("Dokeos light grey is " . DOKEOSLIGHTGREY);
		$bgcolor = "bgcolor='".DOKEOSLIGHTGREY."'";
		echo "<table $class border=\"0\" cellspacing=\"$cellspacing\" cellpadding=\"$cellpadding\" width=\"$width\">\n";
		echo "<thead><tr $bgcolor>";
		foreach ($column_header as $table_element)
		{
			echo "<th>".$table_element."</th>";
		}
		echo "</tr></thead>\n";
		echo "<tbody>\n";
		$bgcolor = "bgcolor='".HTML_WHITE."'";
		return $bgcolor;
	}

	/**
	*	Displays a table row.
	*
	*	@param $bgcolor the background colour for the table
	*	@param $table_row an array with the row elements
	*	@param $is_alternating true: the row colours alternate, false otherwise
	*/
	public static function display_table_row ($bgcolor, $table_row, $is_alternating = true) {
		echo "<tr $bgcolor>";
		foreach ($table_row as $table_element)
		{
			echo "<td>".$table_element."</td>";
		}
		echo "</tr>\n";
		if ($is_alternating)
		{
			if ($bgcolor == "bgcolor='".HTML_WHITE."'")
			{
				$bgcolor = "bgcolor='".DOKEOSLIGHTGREY."'";
			}
			else
			{
				if ($bgcolor == "bgcolor='".DOKEOSLIGHTGREY."'")
				{
					$bgcolor = "bgcolor='".HTML_WHITE."'";
				}
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
	public static function display_complex_table_row ($properties, $table_row) {
		$bgcolor = $properties["bgcolor"];
		$is_alternating = $properties["is_alternating"];
		$align_row = $properties["align_row"];
		echo "<tr $bgcolor>";
		$number_cells = count($table_row);
		for ($i = 0; $i < $number_cells; $i ++)
		{
			$cell_data = $table_row[$i];
			$cell_align = $align_row[$i];
			echo "<td align=\"$cell_align\">".$cell_data."</td>";
		}
		echo "</tr>\n";
		if ($is_alternating)
		{
			if ($bgcolor == "bgcolor='".HTML_WHITE."'")
				$bgcolor = "bgcolor='".DOKEOSLIGHTGREY."'";
			else
				if ($bgcolor == "bgcolor='".DOKEOSLIGHTGREY."'")
					$bgcolor = "bgcolor='".HTML_WHITE."'";
		}
		return $bgcolor;
	}

	/**
	*	display html footer of table
	*/
	public static function display_table_footer() {
		echo "</tbody></table>";
	}

	/**
	 * Display a table
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
	 * @author bart.mollet@hogent.be
	 */
	public static function display_sortable_table ($header, $content, $sorting_options = array (), $paging_options = array (), $query_vars = null, $form_actions=array()) {
		global $origin;
		$column = isset ($sorting_options['column']) ? $sorting_options['column'] : 0;
		$default_items_per_page = isset ($paging_options['per_page']) ? $paging_options['per_page'] : 20;
			
		$table = new SortableTableFromArray($content, $column, $default_items_per_page);

		if (is_array($query_vars)) {
			$table->set_additional_parameters($query_vars);
		}
		foreach ($header as $index => $header_item)
		{			
			$table->set_header($index, $header_item[0], $header_item[1], $header_item[2], $header_item[3]);
		}
		$table->set_form_actions($form_actions);
		$table->display();
	}
	
	
	/**
	 * Display a table with a special configuration
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
	 
	public static function display_sortable_config_table ($header, $content, $sorting_options = array (), $paging_options = array (), $query_vars = null, $column_show=array(),$column_order=array(),$form_actions=array()) {
		global $origin;
		$column = isset ($sorting_options['column']) ? $sorting_options['column'] : 0;
		$default_items_per_page = isset ($paging_options['per_page']) ? $paging_options['per_page'] : 20;
		
		$table = new SortableTableFromArrayConfig($content, $column, $default_items_per_page,'tablename',$column_show,$column_order);

		if (is_array($query_vars)) {
			$table->set_additional_parameters($query_vars);
		}
		// show or hide the columns header
		if (is_array($column_show) ) 
		{
			for ($i=0;$i<count($column_show);$i++)
			{
				if (!empty($column_show[$i]))
				{					
					isset($header[$i][0])?$val0=$header[$i][0]:$val0=null;
					isset($header[$i][1])?$val1=$header[$i][1]:$val1=null;
					isset($header[$i][2])?$val2=$header[$i][2]:$val2=null;
					isset($header[$i][3])?$val3=$header[$i][3]:$val3=null;	
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
	public static function display_normal_message ($message,$filter=true) {
		global $charset;
		if($filter) {
			//filter message
			$message = api_htmlentities($message, ENT_QUOTES, api_is_xml_http_request() ? 'UTF-8' : $charset);
		}
		if (!headers_sent())
		{
			echo '
						<style type="text/css" media="screen, projection">
						/*<![CDATA[*/
						@import "'.api_get_path(WEB_CODE_PATH).'css/default.css";
						/*]]>*/
						</style>';
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
	public static function display_warning_message ($message,$filter=true) {
		global $charset;
		if($filter){
			//filter message
			$message = api_htmlentities($message, ENT_QUOTES, api_is_xml_http_request() ? 'UTF-8' : $charset);
		}
		if (!headers_sent())
		{
			echo '
						<style type="text/css" media="screen, projection">
						/*<![CDATA[*/
						@import "'.api_get_path(WEB_CODE_PATH).'css/default.css";
						/*]]>*/
						</style>';
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
	public static function display_confirmation_message ($message,$filter=true) {
		global $charset;
		if($filter){
			//filter message
			$message = api_htmlentities($message, ENT_QUOTES, api_is_xml_http_request() ? 'UTF-8' : $charset);
		}
		if (!headers_sent())
		{
			echo '
						<style type="text/css" media="screen, projection">
						/*<![CDATA[*/
						@import "'.api_get_path(WEB_CODE_PATH).'css/default.css";
						/*]]>*/
						</style>';
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
	public static function display_error_message ($message,$filter=true) {
		global $charset;
		if($filter){
			//filter message
			$message = api_htmlentities($message, ENT_QUOTES, api_is_xml_http_request() ? 'UTF-8' : $charset);
		}

		if (!headers_sent())
		{
			echo '
						<style type="text/css" media="screen, projection">
						/*<![CDATA[*/
						@import "'.api_get_path(WEB_CODE_PATH).'css/default.css";
						/*]]>*/
						</style>';
		}
		echo '<div class="error-message">';
		//Display :: display_icon('message_error.png', get_lang('ErrorMessage'), array ('style' => 'float:left; margin-right:10px;'));
		/*
		get_lang('ErrorMessage', array ('style' => 'float:left; margin-right:10px;'));
		*/
		echo $message.'</div>';
	}
	/**
	 * Return an encrypted mailto hyperlink
	 *
	 * @param - $email (string) - e-mail
	 * @param - $text (string) - clickable text
	 * @param - $style_class (string) - optional, class from stylesheet
	 * @return - encrypted mailto hyperlink
	 */
	public static function encrypted_mailto_link ($email, $clickable_text = null, $style_class = '') {
		global $charset;		
		if (is_null($clickable_text))
		{
			$clickable_text = $email;
		}
		//mailto already present?
		if (substr($email, 0, 7) != 'mailto:') {
			$email = 'mailto:'.$email;
		}
		//class (stylesheet) defined?
		if ($style_class != '') {
			$style_class = ' class="'.$style_class.'"';
		}
		//encrypt email
		$hmail = '';
		for ($i = 0; $i < strlen($email); $i ++) {
			$hmail .= '&#'.ord($email {
			$i }).';';
		}
		//encrypt clickable text if @ is present
		if (strpos($clickable_text, '@')) {
			
			for ($i = 0; $i < strlen($clickable_text); $i ++) {
				$hclickable_text .= '&#'.ord($clickable_text {
				$i }).';';
			}
		} else {
			$hclickable_text = htmlspecialchars($clickable_text,ENT_QUOTES,$charset);
		}

		//return encrypted mailto hyperlink
		return '<a href="'.$hmail.'"'.$style_class.' name="clickable_email_link">'.$hclickable_text.'</a>';
	}

	/**
	*	Create a hyperlink to the platform homepage.
	*	@param string $name, the visible name of the hyperlink, default is sitename
	*	@return string with html code for hyperlink
	*/
	public static function get_platform_home_link_html ($name = '') {
		if ($name == '')
		{
			$name = api_get_setting('siteName');
		}
		return "<a href=\"".api_get_path(WEB_PATH)."index.php\">$name</a>";
	}
	/**
	 * Display the page header
	 * @param string The name of the page (will be showed in the page title)
	 * @param string Optional help file name
	 */
	public static function display_header ($tool_name, $help = NULL) {
		$nameTools = $tool_name;
		global $_plugins,$lp_theme_css,$mycoursetheme,$user_theme,$platform_theme;
		global $httpHeadXtra, $htmlHeadXtra, $htmlIncHeadXtra, $_course, $_user, $clarolineRepositoryWeb, $text_dir, $plugins, $_user, $rootAdminWeb, $_cid, $interbreadcrumb, $charset, $language_file, $noPHP_SELF;
		global $menu_navigation;
		include (api_get_path(INCLUDE_PATH)."header.inc.php");
	}
	/**
	 * Display the reduced page header (without banner)
	 */
	public static function display_reduced_header () {
		global $_plugins,$lp_theme_css,$mycoursetheme,$user_theme,$platform_theme;
		global $httpHeadXtra, $htmlHeadXtra, $htmlIncHeadXtra, $_course, $_user, $clarolineRepositoryWeb, $text_dir, $plugins, $_user, $rootAdminWeb, $_cid, $interbreadcrumb, $charset, $language_file, $noPHP_SELF, $language_interface;
		global $menu_navigation;
		include (api_get_path(INCLUDE_PATH)."reduced_header.inc.php");
	}
	/**
	 * Display the page footer
	 */
	public static function display_footer () {
		global $dokeos_version; //necessary to have the value accessible in the footer
		global $_plugins;
		include (api_get_path(INCLUDE_PATH)."footer.inc.php");
	}

	/**
	 * Print an <option>-list with all letters (A-Z).
	 * @param char $selected_letter The letter that should be selected
	 */
	public static function get_alphabet_options ($selected_letter = '') {
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
	
	public static function get_numeric_options ($min,$max, $selected_num = 0) {
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
	* Show the so-called "left" menu for navigating
	*/
	public static function show_course_navigation_menu ($isHidden = false) {
		global $output_string_menu;
		global $_setting;

		// check if the $_SERVER['REQUEST_URI'] contains already url parameters (thus a questionmark)
		if (!strstr($_SERVER['REQUEST_URI'], "?"))
		{
			$sourceurl = api_get_self()."?";
		}
		else
		{
			$sourceurl = $_SERVER['REQUEST_URI'];
		}
		$output_string_menu = "";
		if ($isHidden == "true" and $_SESSION["hideMenu"]) {

			$_SESSION["hideMenu"] = "hidden";

			$sourceurl = str_replace("&isHidden=true", "", $sourceurl);
			$sourceurl = str_replace("&isHidden=false", "", $sourceurl);

			$output_string_menu .= " <a href='".$sourceurl."&isHidden=false'>"."<img src=../../main/img/expand.gif alt='Show menu1' padding:'2px'/>"."</a>";
		}
		elseif ($isHidden == "false" and $_SESSION["hideMenu"])
		{
			$sourceurl = str_replace("&isHidden=true", "", $sourceurl);
			$sourceurl = str_replace("&isHidden=false", "", $sourceurl);

			$_SESSION["hideMenu"] = "shown";
			$output_string_menu .= "<div id='leftimg'><a href='".$sourceurl."&isHidden=true'>"."<img src=../../main/img/collapse.gif alt='Hide menu2' padding:'2px'/>"."</a></div>";
		}
		elseif ($_SESSION["hideMenu"])
		{
			if ($_SESSION["hideMenu"] == "shown") {
				$output_string_menu .= "<div id='leftimg'><a href='".$sourceurl."&isHidden=true'>"."<img src='../../main/img/collapse.gif' alt='Hide menu3' padding:'2px'/>"."</a></div>";
			}
			if ($_SESSION["hideMenu"] == "hidden") {
				$sourceurl = str_replace("&isHidden=true", "", $sourceurl);
				$output_string_menu .= "<a href='".$sourceurl."&isHidden=false'>"."<img src='../../main/img/expand.gif' alt='Hide menu4' padding:'2px'/>"."</a>";

			}
		}
		elseif (!$_SESSION["hideMenu"])
		{
			$_SESSION["hideMenu"] = "shown";
			if (isset ($_cid))
			{
				$output_string_menu .= "<div id='leftimg'><a href='".$sourceurl."&isHidden=true'>"."<img src='main/img/collapse.gif' alt='Hide menu5' padding:'2px'/>"."</a></div>";
			}
		}
	}

	/**
	 * This public function displays an icon
	 * @param string $image the filename of the file (in the main/img/ folder
	 * @param string $alt_text the alt text (probably a language variable)
	 * @param array additional attributes (for instance height, width, onclick, ...)
	*/
	public static function display_icon ($image, $alt_text = '', $additional_attributes = array ()) {
		echo Display::return_icon($image,$alt_text,$additional_attributes);
	}

	/**
	 * This public function returns the htmlcode for an icon
	 *
	 * @param string $image the filename of the file (in the main/img/ folder
	 * @param string $alt_text the alt text (probably a language variable)
	 * @param array additional attributes (for instance height, width, onclick, ...)
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version October 2006
	*/
	public static function return_icon ($image,$alt_text='',$additional_attributes=array()) {
		$attribute_list = '';
		// alt text = the image if there is none provided (for XHTML compliance)
		if ($alt_text=='')
		{
			$alt_text=$image;
		}

		// managing the additional attributes
		if (!empty($additional_attributes) and is_array($additional_attributes))
		{
			$attribute_list='';
			foreach ($additional_attributes as $key=>$value)
			{
				$attribute_list.=$key.'="'.$value.'" ';
			}
		}
		return '<img src="'.api_get_path(WEB_IMG_PATH).$image.'" alt="'.$alt_text.'"  title="'.$alt_text.'" '.$attribute_list.'  />';
	}
	
	/**
	 * Display name and lastname in a specific order
	 * @param string Firstname 
	 * @param string Lastname
	 * @param string Title in the destination language (Dr, Mr, Miss, Sr, Sra, etc)
	 * @param string Optional format string (e.g. '%t. %l, %f')
	 * @author Carlos Vargas <carlos.vargas@dokeos.com>
	 */
	 public static function user_name($fname,$lname,$title='',$format=null) {
		 if (empty($format)){	 	
			 	if (empty($fname) or empty($lname)) {
			 		$user_name = $fname.$lname;
			 	} else {
				 	$user_name= $fname.' '.$lname; 		
			 	}
		 } 	else {
		 	$find = array('%t','%f','%l');
			$repl = array($title,$fname,$lname);
			$user_name = str_replace($find,$repl,$format);
		 }
	 	 return $user_name;
	 }

} //end class Display
?>
