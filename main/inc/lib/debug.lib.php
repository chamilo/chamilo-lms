<?php
/* For licensing terms, see /license.txt */
/**
* This is the debug library for Chamilo.
* Include/require it in your code to use its functionality.
* @package chamilo.debug
*/
/**
* This is the debug library for Chamilo.
* Include/require it in your code to use its functionality.
* @package chamilo.debug
*/
class Debug {
	/**
	 * This function displays the contend of a variable, array or object in a nicely formatted way.
	 * @param	Mixed	A variable, array or object
	 * @return 	void	Prints <pre> HTML block to output
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version November 2006
	 */
	public function printr($variable) {
		echo '<pre>';
		print_r($variable);
		echo '</pre>';
	}
	
	/**
	 * This function displays all the information of the dokeos $_course array
	 * This array stores all the information of the current course if the user is in a course.
	 * This is why this array is used to check weither the user is currently is in the course.
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version November 2006
	 */
	public function course() {
		global $_course;
		self::printr($_course);
	}
	
	/**
	 * This function displays all the information of the dokeos $_user array
	 * This array stores all the information of the current user.
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version November 2006
	 */
	public function user() {
		global $_user;
		self::printr($_user);
	}
	
	/**
	 * This function displays an overview of the different path constants that can be used with the api_get_path function
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version November 2006
	 * @return	void
	 */
	public function debug_paths() {
		echo 'WEB_PATH :'.api_get_path(WEB_PATH).'<br />';
		echo 'SYS_PATH :'.api_get_path(SYS_PATH).'<br />';
		echo 'REL_PATH :'.api_get_path(REL_PATH).'<br />';
		echo 'WEB_COURSE_PATH :'.api_get_path(WEB_COURSE_PATH).'<br />';
		echo 'SYS_COURSE_PATH :'.api_get_path(SYS_COURSE_PATH).'<br />';
		echo 'REL_COURSE_PATH :'.api_get_path(REL_COURSE_PATH).'<br />';
		echo 'REL_CLARO_PATH :'.api_get_path(REL_CODE_PATH).'<br />';
		echo 'WEB_CODE_PATH :'.api_get_path(WEB_CODE_PATH).'<br />';
		echo 'SYS_CODE_PATH :'.api_get_path(SYS_CODE_PATH).'<br />';
		echo 'SYS_LANG_PATH :'.api_get_path(SYS_LANG_PATH).'<br />';
		echo 'WEB_IMG_PATH :'.api_get_path(WEB_IMG_PATH).'<br />';
		echo 'PLUGIN_PATH :'.api_get_path(PLUGIN_PATH).'<br />';
		echo 'SYS_ARCHIVE_PATH :'.api_get_path(SYS_ARCHIVE_PATH).'<br />';
		echo 'INCLUDE_PATH :'.api_get_path(INCLUDE_PATH).'<br />';
		echo 'LIBRARY_PATH :'.api_get_path(LIBRARY_PATH).'<br />';
		echo 'CONFIGURATION_PATH :'.api_get_path(CONFIGURATION_PATH).'<br />';
	
	}
	
	/**
	 * Dump variable contents on screen in a nice format
	 * @param	mixed	Variable to dump
	 * @param	string	Variable name to print
	 * @return void
	 */
	public function print_var($var, $varName = "@") {
		GLOBAL $DEBUG;
		if ($DEBUG)
		{
			echo "<blockquote>\n";
			echo "<b>[$varName]</b>";
			echo "<hr noshade size=\"1\" style=\"color:blue\">";
			echo "<pre style=\"color:red\">\n";
			var_dump($var);
			echo "</pre>\n";
			echo "<hr noshade size=\"1\" style=\"color:blue\">";
			echo "</blockquote>\n";
		}
		else
		{
			echo "<!-- DEBUG is OFF -->";
			echo "DEBUG is OFF";
		}
	}
	
	/**
	 * Log the given string into the default log if mode confirms it
	 * @param	string	String to be logged
	 * @param	bool	Whether to force the log even in production mode or not
	 * @return	bool	True on success, false on failure
	 */
	public function log_s($msg, $force_log = false) {
		$server_type = api_get_setting('server_type');
		if ($server_type == 'production' && !$force_log) {
			//not logging in production mode
			return false;
		}
		$backtrace = debug_backtrace(); // Retrieving information about the caller statement.
		$backtrace_string = self::_get_backtrace_raw_string($backtrace);
		return error_log($msg.$backtrace_string);
	}
	/**
	 * Log the given variables' dump into the default log if mode confirms it
	 * @param	string	String to be logged
	 * @param	bool	Whether to force the log even in production mode or not
	 * @return	bool	True on success, false on failure
	 */
	public function log_v($variable, $force_log = false) {
		$server_type = api_get_setting('server_type');
		if ($server_type == 'production' && !$force_log) {
			//not logging in production mode
			return null;
		}
		$backtrace = debug_backtrace(); // Retrieving information about the caller statement.
		$backtrace_string = self::_get_backtrace_raw_string($backtrace);
		return error_log(print_r($variable,1).$backtrace_string);
	}
	/**
	 * Get a string formatted with all backtrace info
	 * @param	array	Backtrace data
	 * @return	string	Backtrace formatted string
	 */
	private function _get_backtrace_raw_string($backtrace=array()) {
		$file = $line = $type = $function = $class = '';
		if (isset($backtrace[0])) {
			$caller = & $backtrace[0];
		} else {
			$caller = array();
		}
		if (isset($backtrace[1])) {
			$owner = & $backtrace[1];
		} else {
			$owner = array();
		}
		$file = $caller['file'];
		$line = $caller['line'];
		$type = $owner['type'];
		$function = $owner['function'];
		$class = $owner['class'];
		$info = ' CHAMILO LOG INFO :: FILE: ' . (empty($file) ? ' unknown ' : $file) . ' LINE: ' . (empty($line) ? ' unknown ' : $line).' ' ;
		if (empty($type)) {
			if (!empty($function)) {
				$info .= 'FUNCTION: ' . $function;
			}
		} else {
			if (!empty($class) && !empty($function)) {
				$info .= 'CLASS: ' . $class;
				$info .= 'METHOD: ' . $function;
			}
		}
		return $info;
	}
}
