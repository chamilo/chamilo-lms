<?php
require_once(api_get_path(LIBRARY_PATH).'display.lib.php');


class TestDisplay extends UnitTestCase {

	public function testdisplay_introduction_section() {
		$tool=api_get_tools_lists($my_tool=null);
		ob_start();
		Display::display_introduction_section($tool);
		$res= ob_get_contents();
		$this->assertTrue(is_array($tool));
		ob_end_clean();
		//var_dump($tool);
	}

	public function testdisplay_localised_html_file(){
		global $language_interface;
		$doc_url = str_replace('/..', '', $doc_url);
		$full_file_name=api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/blog/'.$doc_url;
		ob_start();
		Display::display_localised_html_file($full_file_name);
		$res=ob_get_contents();
		$this->assertTrue(is_string($full_file_name));
		ob_end_clean();
		//var_dump($full_file_name);
	}

	public function testdisplay_table_header() {
		ob_start();
		Display::display_table_header();
		$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

		/**
	*	Display html header of table with several options.
	*
	*	@param $properties, array with elements
	*	@param column_header, array with the header elements.
	*	@author Arthur Portugal
	*	@return return type string, bgcolor
	*/
	public function testdisplay_complex_table_header() {
		$properties='HTML_WHITE';
		$column_header=array();
		ob_start();
		Display::display_complex_table_header($properties, $column_header);
		$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	*	Displays a table row.
	*
	*	@param $bgcolor the background colour for the table
	*	@param $table_row an array with the row elements
	*	@param $is_alternating true: the row colours alternate, false otherwise
	*	@return string color
	*/
	public function testdisplay_table_row() {
		$bgcolor='';
		$table_row='';
		ob_start();
		Display::display_table_row($bgcolor, $table_row);
		$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	public function testdisplay_complex_table_row() {
		$properties='';
		$table_row='';
		ob_start();
		Display::display_complex_table_row($properties, $table_row);
		$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	*	display html footer of table
	*/
	public function testdisplay_table_footer() {
		ob_start();
		Display::display_table_footer();
		$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	public function testdisplay_sortable_table() {
		$header='';
		$content='';
		global $origin;
		ob_start();
		Display::display_sortable_table($header, $content);
		$res= ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
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
	 * @return void String about header
	 */
	public function testdisplay_sortable_config_table() {
		$header='';
		$content='';
		global $origin;
		ob_start();
		Display::display_sortable_config_table($header, $content);
		$res= ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

		/**
	* Displays a normal message. It is recommended to use this public function
	* to display any normal information messages.
	*
	* @param string $message - include any additional html
	*                          tags if you need them
	* @param bool	Filter (true) or not (false)
	* @return void String message
	*/
	public function testdisplay_normal_message() {
		global $charset;
		$message=api_htmlentities($message, ENT_QUOTES, api_is_xml_http_request() ? 'UTF-8' : $charset);
		ob_start();
		Display::display_normal_message($message);
		$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	 * Display the reduced page header (without banner)
	 */
	public function testdisplay_reduced_header() {
		global $_plugins,$lp_theme_css,$mycoursetheme,$user_theme,$platform_theme;
		global $httpHeadXtra, $htmlHeadXtra, $htmlIncHeadXtra, $_course, $_user, $clarolineRepositoryWeb, $text_dir, $plugins, $_user, $rootAdminWeb, $_cid, $interbreadcrumb, $charset, $language_file, $noPHP_SELF, $language_interface;
		global $menu_navigation;
		ob_start();
		Display::display_reduced_header();
		$res= ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	* Displays an confirmation message. Use this if something has been done successfully
	*
	* @param string $message
	* @param bool	Filter (true) or not (false)
	* @return void String message
	*/
	public function testdisplay_confirmation_message() {
		global $charset;
		$message=api_htmlentities($message, ENT_QUOTES, api_is_xml_http_request() ? 'UTF-8' : $charset);
		ob_start();
		Display::display_confirmation_message($message);
		$res=ob_get_contents();
		$this->assertTrue(is_string($message));
		ob_end_clean();
		//var_dump($message);
	}

	/**
	* Displays an error message.
	* @author Arthur Portugal
	* @param string $message - include any additional html tags if you need them
	* @param bool	Filter (true) or not (false)
	* @param object Not display the object in the test browser
	* @return string Code HTML
	*/
	public function testdisplay_error_message() {
		global $charset;
		$message = "error message";
		ob_start();
		Display::display_error_message($message);
		$res=ob_get_contents();
		$this->assertTrue(is_string($message));
		ob_end_clean();
		//var_dump($message);
	}

	/**
	 * Display the page footer
	 * @author Arthur Portugal
	 * @return string Code HTML about the footer
	 */
	public function testdisplay_footer() {
		global $dokeos_version; //necessary to have the value accessible in the footer
		global $_plugins;
		ob_start();
		Display::display_footer();
		$res= ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	 * Display the page header
	 * @param string The name of the page (will be showed in the page title)
	 * @param string Optional help file name
	 * @return string Display the hearders messages
	 */

	public function testdisplay_header() {
		global $_plugins,$lp_theme_css,$mycoursetheme,$user_theme,$platform_theme;
		global $httpHeadXtra, $htmlHeadXtra, $htmlIncHeadXtra, $_course, $_user, $clarolineRepositoryWeb, $text_dir, $plugins, $_user, $rootAdminWeb, $_cid, $interbreadcrumb, $charset, $language_file, $noPHP_SELF;
		global $menu_navigation;
		$tool_name='';
		$nameTools = $tool_name;
		ob_start();
		Display::display_header($tool_name);
		$res= ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	 * This public function displays an icon
	 * @param string $image the filename of the file (in the main/img/ folder
	 * @param string $alt_text the alt text (probably a language variable)
	 * @param array additional attributes (for instance height, width, onclick, ...)
	 * @return return icon like string in this test (path)
	*/
	public function testdisplay_icon() {
		$image='file';
		ob_start();
		Display::display_icon($image);
		$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	* Displays an warning message. Use this if you want to draw attention to something
	*
	* @author Arthur Portugal
	* @param string $message
	* @param bool	Filter (true) or not (false)
	* @return string with the message (also void)
	*/
	public function testdisplay_warning_message() {
		$message="warning-message";
		ob_start();
		Display::display_warning_message($message);
		$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	 * Return an encrypted mailto hyperlink
	 * @author Arthur Portugal
	 * @param - $email (string) - e-mail
	 * @return - encrypted mailto hyperlink
	 */
	public function testencrypted_mailto_link() {
		$email='';
		ob_start();
		Display::encrypted_mailto_link();
		$res= ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	 * Print an <option>-list with all letters (A-Z).
	 * @param char $selected_letter The letter that should be selected
	 */
	public function testget_alphabet_options() {
		$selected_letter = 5;
		ob_start();
		Display::get_alphabet_options();
		$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	public function testget_numeric_options() {
		$min='';
		$max='';
		ob_start();
		Display::get_numeric_options($min,$max);
		$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	*	Create a hyperlink to the platform homepage.
	*	@param string $name, the visible name of the hyperlink, default is sitename
	*	@return string with html code for hyperlink
	*/
	public function testget_platform_home_link_html() {
		ob_start();
		Display::get_platform_home_link_html();
		$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	 * This public function returns the htmlcode for an icon
	 *
	 * @param string $image the filename of the file (in the main/img/ folder
	 * @param string $alt_text the alt text (probably a language variable)
	 * @param array additional attributes (for instance height, width, onclick, ...)
	 *
     */
	public function testreturn_icon() {
		$image='';
		ob_start();
		Display::return_icon($image);
		$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	* Show the so-called "left" menu for navigating
	*/
	public function testshow_course_navigation_menu() {
		global $output_string_menu;
		global $_setting;
		ob_start();
		Display::show_course_navigation_menu();
		$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	 * Display name and lastname in a specific order
	 * @param string Firstname
	 * @param string Lastname
	 */
	public function testuser_name() {
		$fname='';
		$lname='';
		ob_start();
		Display::user_name($fname,$lname);
		$res= ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}
}
?>