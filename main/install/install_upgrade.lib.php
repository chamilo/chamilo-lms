<?php //$id: $
/* For licensing terms, see /chamilo_license.txt */
/**
==============================================================================
* This file contains functions used by the install and upgrade scripts.
* The current functions are used to
* - fill existing tables with data;
* - write a .htaccess file in the courses folder for extra security;
* - write the system config file containing important settings like database names
* and paswords and other options.
*
* Ideas for future additions:
* - a function get_old_version_settings to retrieve the config file settings
*   of older versions before upgrading.
==============================================================================
*/
/*
==============================================================================
		CONSTANTS
==============================================================================
*/
define("DOKEOS_MAIN_DATABASE_FILE", "dokeos_main.sql");
define("COUNTRY_DATA_FILENAME", "country_data.csv");
define("COURSES_HTACCESS_FILENAME", "htaccess.dist");
define("DOKEOS_CONFIG_FILENAME", "configuration.dist.php");

require_once api_get_path(LIBRARY_PATH).'database.lib.php';

/*
==============================================================================
		DATABASE FUNCTIONS
==============================================================================
*/

/**
 * We assume this function is called from install scripts that reside inside the install folder.
 */
function set_file_folder_permissions() {
	@chmod('.', 0755); //set permissions on install dir
	@chmod('..', 0755); //set permissions on parent dir of install dir
	@chmod('country_data.csv.csv', 0755);
}

/**
 * Fills the countries table with a list of countries.
 */
function fill_track_countries_table($track_countries_table) {
	$file_path = dirname(__FILE__).'/'.COUNTRY_DATA_FILENAME;
	$add_country_sql = "LOAD DATA INFILE '".mysql_real_escape_string($file_path)."' INTO TABLE $track_countries_table FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\'';";
	@ mysql_query($add_country_sql);
}

/**
 * Add's a .htaccess file to the courses directory
 * @param string $url_append The path from your webroot to your chamilo root
 */
function write_courses_htaccess_file($url_append) {
	$file_path = dirname(__FILE__).'/'.COURSES_HTACCESS_FILENAME;
	$content = file_get_contents($file_path);
	$content = str_replace('{DOKEOS_URL_APPEND_PATH}', $url_append, $content);
	$fp = @ fopen('../../courses/.htaccess', 'w');
	if ($fp) {
		fwrite($fp, $content);
		return fclose($fp);
	}
	return false;
}

/**
 * Write the main system config file
 * @param string $path Path to the config file
 */
function write_dokeos_config_file($path) {

	global $dbHostForm;
	global $dbUsernameForm;
	global $dbPassForm;
	global $enableTrackingForm;
	global $singleDbForm;
	global $dbPrefixForm;
	global $dbNameForm;
	global $dbStatsForm;
	global $dbScormForm;
	global $dbUserForm;
	global $urlForm;
	global $pathForm;
	global $urlAppendPath;
	global $languageForm;
	global $encryptPassForm;
	global $installType;
	global $updatePath;
	global $session_lifetime;
	global $new_version;
	global $new_version_stable;

	// TODO: api_get_path() to be tested here.
    $seek = array('\\', '//');
    $destroy = array('/', '/');
	$rootSys = str_replace($seek, $destroy, realpath($pathForm).'/');
	$file_path = dirname(__FILE__).'/'.DOKEOS_CONFIG_FILENAME;

	$content = file_get_contents($file_path);
	$config['{DATE_GENERATED}'] = date('r');
	$config['{DATABASE_HOST}'] = $dbHostForm;
	$config['{DATABASE_USER}'] = $dbUsernameForm;
	$config['{DATABASE_PASSWORD}'] = $dbPassForm;
	$config['TRACKING_ENABLED'] = trueFalse($enableTrackingForm);
	$config['SINGLE_DATABASE'] = trueFalse($singleDbForm);
	$config['{COURSE_TABLE_PREFIX}'] = ($singleDbForm ? 'crs_' : '');
	$config['{DATABASE_GLUE}'] = ($singleDbForm ? '_' : '`.`');
	$config['{DATABASE_PREFIX}'] = $dbPrefixForm;
	$config['{DATABASE_MAIN}'] = $dbNameForm;
	$config['{DATABASE_STATS}'] = (($singleDbForm && empty($dbStatsForm)) ? $dbNameForm : $dbStatsForm);
	$config['{DATABASE_SCORM}'] = (($singleDbForm && empty($dbScormForm)) ? $dbNameForm : $dbScormForm);
	$config['{DATABASE_PERSONAL}'] =(($singleDbForm && empty($dbUserForm)) ?  $dbNameForm : $dbUserForm);
	$config['{ROOT_WEB}'] = $urlForm;
	$config['{ROOT_SYS}'] = $rootSys;
	$config['{URL_APPEND_PATH}'] = $urlAppendPath;
	$config['{PLATFORM_LANGUAGE}'] = $languageForm;
	$config['{SECURITY_KEY}'] = md5(uniqid(rand().time()));
	$config['{ENCRYPT_PASSWORD}'] = $encryptPassForm;

	$config['SESSION_LIFETIME'] = $session_lifetime;
	$config['{NEW_VERSION}'] = $new_version;
	$config['NEW_VERSION_STABLE'] = trueFalse($new_version_stable);

	foreach ($config as $key => $value) {
		$content = str_replace($key, $value, $content);
	}

	$fp = @ fopen($path, 'w');

	if (!$fp) {
		echo '<strong><font color="red">Your script doesn\'t have write access to the config directory</font></strong><br />
						<em>('.str_replace('\\', '/', realpath($path)).')</em><br /><br />
						You probably do not have write access on Chamilo root directory,
						i.e. you should <em>CHMOD 777</em> or <em>755</em> or <em>775</em>.<br /><br />
						Your problems can be related on two possible causes:<br />
						<ul>
						  <li>Permission problems.<br />Try initially with <em>chmod -R 777</em> and increase restrictions gradually.</li>
						  <li>PHP is running in <a href="http://www.php.net/manual/en/features.safe-mode.php" target="_blank">Safe-Mode</a>. If possible, try to switch it off.</li>
						</ul>
						<a href="http://forum.chamilo.org/" target="_blank">Read about this problem in Support Forum</a><br /><br />
						Please go back to step 5.
					    <p><input type="submit" name="step5" value="&lt; Back" /></p>
					    </td></tr></table></form></body></html>';
		exit ();
	}

	fwrite($fp, $content);
	fclose($fp);
}

/**
 * Creates the structure of the main database and fills it
 * with data. Placeholder symbols in the main database file
 * have to be replaced by the settings entered by the user during installation.
 *
 * @param array $installation_settings list of settings entered by the user
 * @param string  optional path about the script for database
 * @return void
 */
function load_main_database($installation_settings, $db_script = '') {
	if (!empty($db_script)) {
		$dokeos_main_sql_file_string = file_get_contents($db_script);
	} else {
		$dokeos_main_sql_file_string = file_get_contents(DOKEOS_MAIN_DATABASE_FILE);
	}

	//replace symbolic parameters with user-specified values
	foreach ($installation_settings as $key => $value) {
		$dokeos_main_sql_file_string = str_replace($key, mysql_real_escape_string($value), $dokeos_main_sql_file_string);
	}

	//split in array of sql strings
	$sql_instructions = array();
	$success = split_sql_file($sql_instructions, $dokeos_main_sql_file_string);

	//execute the sql instructions
	$count = count($sql_instructions);
	for ($i = 0; $i < $count; $i++) {
		$this_sql_query = $sql_instructions[$i]['query'];
		mysql_query($this_sql_query);
	}
}

/**
 * Creates the structure of the stats database
 * @param	string	Name of the file containing the SQL script inside the install directory
 */
function load_database_script($db_script) {
	$dokeos_sql_file_string = file_get_contents($db_script);

	//split in array of sql strings
	$sql_instructions = array();
	$success = split_sql_file($sql_instructions, $dokeos_sql_file_string);

	//execute the sql instructions
	$count = count($sql_instructions);
	for ($i = 0; $i < $count; $i++) {
		$this_sql_query = $sql_instructions[$i]['query'];
		mysql_query($this_sql_query);
	}
}

/**
 * Function copied and adapted from phpMyAdmin 2.6.0 PMA_splitSqlFile (also GNU GPL)
 *
 * Removes comment lines and splits up large sql files into individual queries
 *
 * Last revision: September 23, 2001 - gandon
 *
 * @param   array    the splitted sql commands
 * @param   string   the sql commands
 * @param   integer  the MySQL release number (because certains php3 versions
 *                   can't get the value of a constant from within a function)
 *
 * @return  boolean  always true
 *
 * @access  public
 */
function split_sql_file(&$ret, $sql) {
    // do not trim, see bug #1030644
    //$sql          = trim($sql);
    $sql          = rtrim($sql, "\n\r");
    $sql_len      = strlen($sql);
    $char         = '';
    $string_start = '';
    $in_string    = FALSE;
    $nothing      = TRUE;
    $time0        = time();

    for ($i = 0; $i < $sql_len; ++$i) {
        $char = $sql[$i];

        // We are in a string, check for not escaped end of strings except for
        // backquotes that can't be escaped
        if ($in_string) {
            for (;;) {
                $i         = strpos($sql, $string_start, $i);
                // No end of string found -> add the current substring to the
                // returned array
                if (!$i) {
                    $ret[] = $sql;
                    return TRUE;
                }
                // Backquotes or no backslashes before quotes: it's indeed the
                // end of the string -> exit the loop
                else if ($string_start == '`' || $sql[$i-1] != '\\') {
                    $string_start      = '';
                    $in_string         = FALSE;
                    break;
                }
                // one or more Backslashes before the presumed end of string...
                else {
                    // ... first checks for escaped backslashes
                    $j                     = 2;
                    $escaped_backslash     = FALSE;
                    while ($i-$j > 0 && $sql[$i-$j] == '\\') {
                        $escaped_backslash = !$escaped_backslash;
                        $j++;
                    }
                    // ... if escaped backslashes: it's really the end of the
                    // string -> exit the loop
                    if ($escaped_backslash) {
                        $string_start  = '';
                        $in_string     = FALSE;
                        break;
                    }
                    // ... else loop
                    else {
                        $i++;
                    }
                } // end if...elseif...else
            } // end for
        } // end if (in string)

        // lets skip comments (/*, -- and #)
        else if (($char == '-' && $sql_len > $i + 2 && $sql[$i + 1] == '-' && $sql[$i + 2] <= ' ') || $char == '#' || ($char == '/' && $sql_len > $i + 1 && $sql[$i + 1] == '*')) {
            $i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
            // didn't we hit end of string?
            if ($i === FALSE) {
                break;
            }
            if ($char == '/') $i++;
        }

        // We are not in a string, first check for delimiter...
        else if ($char == ';') {
            // if delimiter found, add the parsed part to the returned array
            $ret[]      = array('query' => substr($sql, 0, $i), 'empty' => $nothing);
            $nothing    = TRUE;
            $sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
            $sql_len    = strlen($sql);
            if ($sql_len) {
                $i      = -1;
            } else {
                // The submited statement(s) end(s) here
                return TRUE;
            }
        } // end else if (is delimiter)

        // ... then check for start of a string,...
        else if (($char == '"') || ($char == '\'') || ($char == '`')) {
            $in_string    = TRUE;
            $nothing      = FALSE;
            $string_start = $char;
        } // end else if (is start of string)

        elseif ($nothing) {
            $nothing = FALSE;
        }

        // loic1: send a fake header each 30 sec. to bypass browser timeout
        $time1     = time();
        if ($time1 >= $time0 + 30) {
            $time0 = $time1;
            header('X-pmaPing: Pong');
        } // end if
    } // end for

    // add any rest to the returned array
    if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql)) {
        $ret[] = array('query' => $sql, 'empty' => $nothing);
    }

    return TRUE;
} // end of the 'PMA_splitSqlFile()' function

/**
 * Get an SQL file's contents
 *
 * This function bases its parsing on the pre-set format of the specific SQL files in
 * the install/upgrade procedure:
 * Lines starting with "--" are comments (but need to be taken into account as they also hold sections names)
 * Other lines are considered to be one-line-per-query lines (this is checked quickly by this function)
 * @param	string	File to parse (in the current directory)
 * @param	string	Section to return
 * @param	boolean	Print (true) or hide (false) error texts when they occur
 */
function get_sql_file_contents($file, $section, $print_errors = true) {
	//check given parameters
	if (empty($file)) {
		$error = "Missing name of file to parse in get_sql_file_contents()";
		if ($print_errors) echo $error;
		return false;
	}
	if (!in_array($section, array('main', 'user', 'stats', 'scorm', 'course'))) {
		$error = "Section '$section' is not authorized in get_sql_file_contents()";
		if ($print_errors) echo $error;
		return false;
	}
	$filepath = getcwd().'/'.$file;
	if (!is_file($filepath) or !is_readable($filepath)) {
		$error = "File $filepath not found or not readable in get_sql_file_contents()";
		if ($print_errors) echo $error;
		return false;
	}
	//read the file in an array
	$file_contents = file($filepath);
	if (!is_array($file_contents) or count($file_contents) < 1) {
		$error = "File $filepath looks empty in get_sql_file_contents()";
		if ($print_errors) echo $error;
		return false;
	}

	//prepare the resulting array
	$section_contents = array();
	$record = false;
	foreach ($file_contents as $index => $line) {
		if (substr($line, 0, 2) == '--') {
			//This is a comment. Check if section name, otherwise ignore
			$result = array();
			if (preg_match('/^-- xx([A-Z]*)xx/', $line,$result)) {	//we got a section name here
				if ($result[1] == strtoupper($section)) {
					//we have the section we are looking for, start recording
					$record = true;
				} else {
					//we have another section's header. If we were recording, stop now and exit loop
					if ($record) {
						break;
					}
					$record = false;
				}
			}
		} else {
			if ($record) {
				if (!empty($line)) {
					$section_contents[] = $line;
				}
			}
		}
	}
	//now we have our section's SQL statements group ready, return
	return $section_contents;
}

// TODO: Maybe within the main API there is already a suitable function?
function my_directory_to_array($directory) {
	$array_items = array();
	if ($handle = opendir($directory)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if (is_dir($directory. "/" . $file)) {
					$array_items = array_merge($array_items, my_directory_to_array($directory. '/' . $file));
					$file = $directory . "/" . $file;
					$array_items[] = preg_replace("/\/\//si", '/', $file);
				}
			}
		}
		closedir($handle);
	}
	return $array_items;
}

/**
 * Adds a new document to the database - specific to version 1.8.0
 *
 * @param array $_course
 * @param string $path
 * @param string $filetype
 * @param int $filesize
 * @param string $title
 * @return id if inserted document
 */
function add_document_180($_course, $path, $filetype, $filesize, $title, $comment = null) {
    $table_document = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);
    $sql = "INSERT INTO $table_document
    (`path`,`filetype`,`size`,`title`, `comment`)
    VALUES ('$path','$filetype','$filesize','".
    Database::escape_string($title)."', '$comment')";
    if (Database::query($sql)) {
        //display_message("Added to database (id ".mysql_insert_id().")!");
        return mysql_insert_id();
    } else {
        //display_error("The uploaded file could not be added to the database (".mysql_error().")!");
        return false;
    }
}
