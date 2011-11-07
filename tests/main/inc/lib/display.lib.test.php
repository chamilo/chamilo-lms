<?php
require_once(api_get_path(LIBRARY_PATH).'display.lib.php');

class TestDisplay extends UnitTestCase {
    public function __construct(){
        $this->UnitTestCase('Display library - main/inc/display.lib.test.php');
    }
	public function testdisplay_introduction_section() {
		$tool=api_get_tools_lists($my_tool=null);
		ob_start();
		$res = Display::display_introduction_section($tool);
		ob_end_clean();
		$this->assertTrue(is_array($tool));
		//var_dump($tool);
	}

	public function testdisplay_localised_html_file(){
		global $language_interface;
		$doc_url = str_replace('/..', '', $doc_url);
		$full_file_name=api_get_path(SYS_COURSE_PATH).'/index'.$doc_url;
		ob_start();
		$res = Display::display_localised_html_file($full_file_name);
		ob_end_clean();
		$this->assertTrue(is_string($full_file_name));
		//var_dump($full_file_name);
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
		$res= Display::display_complex_table_header($properties, $column_header);
		ob_end_clean();
		$this->assertTrue(is_string($res));
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
	/*
	public function testdisplay_table_row() {
		$bgcolor = 'red';
		$table_row = array();
		$is_alternating = true;
		ob_start();
		$res=Display::display_table_row($bgcolor, $table_row,$is_alternating);
		ob_end_clean();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}*/

	public function testdisplay_sortable_table() {
		$header='';
		$content='';
		global $origin;
		ob_start();
		$res=Display::display_sortable_table($header, $content);
		ob_end_clean();
		$this->assertNull($res);
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
		$res=Display::display_sortable_config_table($header, $content);
		ob_end_clean();
		$this->assertNull($res);
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
		$res=Display::display_normal_message($message);
		ob_end_clean();
		$this->assertNull($res);
		//var_dump($res);
	}

	/**
	 * Display the reduced page header (without banner)
	 */
	// [/var/www/chamilo/main/inc/reduced_header.inc.php line 30] - exception
/*	public function testdisplay_reduced_header() {
		global $_plugins,$lp_theme_css,$mycoursetheme,$user_theme,$platform_theme;
		global $httpHeadXtra, $htmlHeadXtra, $htmlIncHeadXtra, $_course, $_user, $text_dir, $plugins, $_user, $rootAdminWeb, $_cid, $interbreadcrumb, $charset, $language_file, $noPHP_SELF, $language_interface;
		global $menu_navigation;
		ob_start();
		$res=Display::display_reduced_header();
		ob_end_clean();
		$this->assertNull($res);
		//var_dump($res);
	}
*/
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
		$res=Display::display_confirmation_message($message);
		ob_end_clean();
		$this->assertTrue(is_string($message));
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
		$res=Display::display_error_message($message);
		ob_end_clean();
		$this->assertTrue(is_string($message));
		//var_dump($message);
	}

	/**
	 * Display the page footer
	 * @author Arthur Portugal
	 * @return string Code HTML about the footer
	 */
	public function testdisplay_footer() {
		global $_plugins;
		ob_start();
		$res=Display::display_footer();
		ob_end_clean();
		$this->assertNull($res);
		//var_dump($res);
	}

	/**
	 * Display the page header
	 * @param string The name of the page (will be showed in the page title)
	 * @param string Optional help file name
	 * @return string Display the hearders messages
	 */
	// [/var/www/chamilo/main/inc/header.inc.php line 31] - exception
/*	public function testdisplay_header() {
		global $_plugins,$lp_theme_css,$mycoursetheme,$user_theme,$platform_theme;
		global $httpHeadXtra, $htmlHeadXtra, $htmlIncHeadXtra, $_course, $_user, $text_dir, $plugins, $_user, $rootAdminWeb, $_cid, $interbreadcrumb, $charset, $language_file, $noPHP_SELF;
		global $menu_navigation;
		$tool_name = '';
		$help = NULL;
		$nameTools = $tool_name;
		ob_start();
		$res=Display::display_header($tool_name, $help);
		ob_end_clean();
		$this->assertNull($res);
		//var_dump($res);
	}
*/
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
		$res=Display::display_icon($image);
		ob_end_clean();
		$this->assertNull($res);
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
		$res=Display::display_warning_message($message);
		ob_end_clean();
		$this->assertNull($res);
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
		$clickable_text = null;
		$style_class = '';
		ob_start();
		$res=Display::encrypted_mailto_link($email, $clickable_text, $style_class);
		ob_end_clean();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	/**
	 * Print an <option>-list with all letters (A-Z).
	 * @param char $selected_letter The letter that should be selected
	 */
	public function testget_alphabet_options() {
		$selected_letter = 5;
		ob_start();
		$res=Display::get_alphabet_options();
		ob_end_clean();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testget_numeric_options() {
		$min='';
		$max='';
		ob_start();
		$res=Display::get_numeric_options($min,$max);
		ob_end_clean();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	/**
	*	Create a hyperlink to the platform homepage.
	*	@param string $name, the visible name of the hyperlink, default is sitename
	*	@return string with html code for hyperlink
	*/
	public function testget_platform_home_link_html() {
		ob_start();
		$res=Display::get_platform_home_link_html();
		ob_end_clean();
		$this->assertTrue(is_string($res));
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
		$res=Display::return_icon($image);
		ob_end_clean();
		//$res=ob_get_contents();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	/**
	* Show the so-called "left" menu for navigating
	*/
	public function testshow_course_navigation_menu() {
		global $output_string_menu;
		global $_setting;
		ob_start();
		$res=Display::show_course_navigation_menu();
		ob_end_clean();
		$this->assertNull($res);
		//var_dump($res);
	}

}
?>
