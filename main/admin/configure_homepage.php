<?php
/* For licensing terms, see /license.txt */

$language_file = array('index','admin', 'accessibility');
$cidReset = true;
require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
$_SESSION['this_section'] = $this_section;
$this_page = '';

api_protect_admin_script();

require_once api_get_path(LIBRARY_PATH).'WCAG/WCAG_rendering.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'security.lib.php';

$action = Security::remove_XSS($_GET['action']);
$tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
$tool_name = get_lang('ConfigureHomePage');

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

if (!empty($action)) {
	$interbreadcrumb[] = array('url' => 'configure_homepage.php', 'name' => get_lang('ConfigureHomePage'));
	switch ($action) {
		case 'edit_top':
			$tool_name = get_lang('EditHomePage');
			break;
		case 'edit_news':
			$tool_name = get_lang('EditNews');
			break;
		case 'edit_notice':
			$tool_name = get_lang('EditNotice');
			break;
		case 'insert_link':
			$tool_name = get_lang('InsertLink');
			break;
		case 'edit_link':
			$tool_name = get_lang('EditLink');
			break;
		case 'insert_tabs':
			$tool_name = get_lang('InsertTabs');
			break;
		case 'edit_tabs':
			$tool_name = get_lang('EditTabs');
			break;
	}
}

// The global logic for language priorities should be:
// - take language selected when connecting ($_SESSION['user_language_choice'])
//   or last language selected (taken from select box into SESSION by global.inc.php)
//   or, if unavailable;
// - take default user language ($_SESSION['_user']['language']) - which is taken from
//   the database in local.inc.php or, if unavailable;
// - take platform language (taken from the database campus setting 'platformLanguage')
// Then if a language file doesn't exist, it should be created.
// The default language for the homepage should use the default platform language
// (if nothing else is selected), which means the 'no-language' file should be taken
// to fill a new 'language-specified' language file, and then only the latter should be
// modified. The original 'no-language' files should never be modified.

// ----- Language selection -----
// The final language selected and used everywhere in this script follows the rules
// described above and is put into "$lang". Because this script includes
// global.inc.php, the variables used for language purposes below are considered safe.

$lang = ''; //el for "Edit Language"
if (!empty($_SESSION['user_language_choice'])) {
	$lang = $_SESSION['user_language_choice'];
} elseif (!empty($_SESSION['_user']['language'])) {
	$lang = $_SESSION['_user']['language'];
} else {
	$lang = api_get_setting('platformLanguage');
}

// Ensuring availability of main files in the corresponding language

if ($_configuration['multiple_access_urls']) {
	$access_url_id = api_get_current_access_url_id();
	if ($access_url_id != -1) {
		$url_info = api_get_access_url($access_url_id);
		$url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
		$clean_url = replace_dangerous_char($url);
		$clean_url = str_replace('/', '-', $clean_url);
		$clean_url .= '/';

		$homep = api_get_path(SYS_PATH).'home/'; //homep for Home Path
		$homep_new = api_get_path(SYS_PATH).'home/'.$clean_url; //homep for Home Path added the url
		$new_url_dir = api_get_path(SYS_PATH).'home/'.$clean_url;
		//we create the new dir for the new sites
		if (!is_dir($new_url_dir)) {
			mkdir($new_url_dir, api_get_permissions_for_new_directories());
		}
	}
} else {
	$homep_new = '';
	$homep = api_get_path(SYS_PATH).'home/'; //homep for Home Path
}

$menuf	 = 'home_menu'; //menuf for Menu File
$newsf	 = 'home_news'; //newsf for News File
$topf 	 = 'home_top'; //topf for Top File
$noticef = 'home_notice'; //noticef for Notice File
$menutabs= 'home_tabs'; //menutabs for tabs Menu
$ext 	 = '.html'; //ext for HTML Extension - when used frequently, variables are
				// faster than hardcoded strings
$homef = array($menuf, $newsf, $topf, $noticef, $menutabs);

// If language-specific file does not exist, create it by copying default file
foreach ($homef as $my_file) {
	if ($_configuration['multiple_access_urls']) {
		if (!file_exists($homep_new.$my_file.'_'.$lang.$ext)) {
			copy($homep.$my_file.$ext, $homep_new.$my_file.'_'.$lang.$ext);
		}
	} else {
		if (!file_exists($homep.$my_file.'_'.$lang.$ext)) {
			copy($homep.$my_file.$ext, $homep.$my_file.'_'.$lang.$ext);
		}
	}
}
if ($_configuration['multiple_access_urls']) {
	$homep = $homep_new;
}

// Check WCAG settings and prepare edition using WCAG
$errorMsg = '';
if (api_get_setting('wcag_anysurfer_public_pages') == 'true') {
	$errorMsg = WCAG_Rendering::request_validation();
}

// Filter link param
$link = '';
if (!empty($_GET['link'])) {
	$link = $_GET['link'];
	// If the link parameter is suspicious, empty it
	if (strstr($link, '/') || !strstr($link, '.html') || strstr($link, '\\')) {
		$link = '';
		$action = '';
	}
}

global $_configuration;

// Start analysing requested actions
if (!empty($action)) {
	if ($_POST['formSent']) {
		// Variables used are $homep for home path, $menuf for menu file, $newsf
		// for news file, $topf for top file, $noticef for noticefile,
		// $ext for '.html'
		switch ($action) {
			case 'edit_top':
				// Filter
				$home_top = '';
				if (api_get_setting('wcag_anysurfer_public_pages') == 'true') {
					$home_top = WCAG_Rendering::prepareXHTML();
				} else {
					$home_top = trim(stripslashes($_POST['home_top']));
				}

				// Write
				if (file_exists($homep.$topf.'_'.$lang.$ext)) {
					if (is_writable($homep.$topf.'_'.$lang.$ext)) {
						$fp = fopen($homep.$topf.'_'.$lang.$ext, 'w');
						fputs($fp, $home_top);
						fclose($fp);
					} else {
						$errorMsg = get_lang('HomePageFilesNotWritable');
					}
				} else {
					//File does not exist
					$fp = fopen($homep.$topf.'_'.$lang.$ext, 'w');
					fputs($fp, $home_top);
					fclose($fp);
				}

				break;
			case 'edit_notice':
				// Filter
				$notice_title = trim(strip_tags(stripslashes($_POST['notice_title'])));
				$notice_text = trim(str_replace(array("\r", "\n"), array('', '<br />'), strip_tags(stripslashes($_POST['notice_text']), '<a>')));
				if (empty($notice_title) || empty($notice_text)) {
					$errorMsg = get_lang('NoticeWillBeNotDisplayed');
				}
				// Write
				if (file_exists($homep.$noticef.'_'.$lang.$ext)) {
					if (is_writable($homep.$noticef.'_'.$lang.$ext)) {
						$fp = fopen($homep.$noticef.'_'.$lang.$ext, 'w');
						if ($errorMsg == '') {
							fputs($fp, "<b>$notice_title</b><br />\n$notice_text");
						} else {
							fputs($fp, '');
						}
						fclose($fp);
					} else {
						$errorMsg .= "<br/>\n".get_lang('HomePageFilesNotWritable');
					}
				} else {
					//File does not exist
					$fp = fopen($homep.$noticef.'_'.$lang.$ext, 'w');
					fputs($fp, "<b>$notice_title</b><br />\n$notice_text");
					fclose($fp);
				}
				break;
			case 'edit_news':
				//Filter
				//$s_languages_news=$_POST["news_languages"]; // TODO: Why this line has been disabled?
				if (api_get_setting('wcag_anysurfer_public_pages') == 'true') {
					$home_news = WCAG_rendering::prepareXHTML();
				} else {
					$home_news = trim(stripslashes($_POST['home_news']));
				}
				//Write
				if ($s_languages_news != 'all') {
					if (file_exists($homep.$newsf.'_'.$s_languages_news.$ext)) {
						if (is_writable($homep.$newsf.'_'.$s_languages_news.$ext)) {
							$fp = fopen($homep.$newsf.'_'.$s_languages_news.$ext, 'w');
							fputs($fp, $home_news);
							fclose($fp);
						} else {
							$errorMsg = get_lang('HomePageFilesNotWritable');
						}
					} else {
						// File does not exist
						$fp = fopen($homep.$newsf.'_'.$s_languages_news.$ext, 'w');
						fputs($fp, $home_news);
						fclose($fp);
					}
				} else {
					// We update all the news file
					$_languages = api_get_languages();
					foreach ($_languages['name'] as $key => $value) {
						$english_name = $_languages['folder'][$key];
						if (file_exists($homep.$newsf.'_'.$english_name.$ext)) {
							if (is_writable($homep.$newsf.'_'.$english_name.$ext)) {
								$fp = fopen($homep.$newsf.'_'.$english_name.$ext, 'w');
								fputs($fp, $home_news);
								fclose($fp);
							} else {
								$errorMsg = get_lang('HomePageFilesNotWritable');
							}
						} else {
							// File does not exist
							$fp = fopen($homep.$newsf.'_'.$english_name.$ext, 'w');
							fputs($fp, $home_news);
							fclose($fp);
						}
					}
				}
				break;
			case 'insert_tabs':
			case 'edit_tabs':            
			case 'insert_link':
			case 'edit_link':
				$link_index = intval($_POST['link_index']);

				$insert_where = intval($_POST['insert_where']);
				$link_name = trim(stripslashes($_POST['link_name']));
				$link_url = trim(stripslashes($_POST['link_url']));
				// WCAG
				if (api_get_setting('wcag_anysurfer_public_pages') == 'true') {
					$link_html = WCAG_Rendering::prepareXHTML();
				} else {
					$link_html = trim(stripslashes($_POST['link_html']));
				}
				$filename = trim(stripslashes($_POST['filename']));
				$target_blank = $_POST['target_blank'] ? true : false;

				if ($link_url == 'http://') {
					$link_url = '';
				} elseif (!empty($link_url) && !strstr($link_url, '://')) {
					$link_url='http://'.$link_url;
				}
				$menuf = ($action == 'insert_tabs' || $action == 'edit_tabs')? $menutabs : $menuf;
				if (!is_writable($homep.$menuf.'_'.$lang.$ext)) {
					$errorMsg = get_lang('HomePageFilesNotWritable');
				} elseif (empty($link_name)) {
					$errorMsg = get_lang('PleaseEnterLinkName');
				} else {
					// New links are added as new files in the home/ directory
					if ($action == 'insert_link' || $action == 'insert_tabs' || empty($filename) || strstr($filename, '/') || !strstr($filename, '.html')) {
						$filename = replace_dangerous_char($link_name, 'strict').'.html';
					}
					// "home_" prefix for links are renamed to "user_" prefix (to avoid name clash with existing home page files)
					if (!empty($filename)) {
						$filename = str_replace('home_', 'user_', $filename);
					}
					// If the typical language suffix is not found in the file name,
					// replace the ".html" suffix by "_en.html" or the active menu language
					if (!strstr($filename,'_'.$lang.$ext)) {
						$filename = str_replace($ext, '_'.$lang.$ext, $filename);
					}
					// Get the contents of home_menu_en.html (or active menu language
					// version) into $home_menu as an array of one entry per line
					$home_menu = file($homep.$menuf.'_'.$lang.$ext);
					$home_menu = implode("\n", $home_menu);
					$home_menu = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
					$home_menu = explode("\n", $home_menu);
					$home_menu = array_values(array_filter(array_map('trim', $home_menu), 'strlen'));
					// Prepare place to insert the new link into (default is end of file)
					if ($insert_where < -1 || $insert_where > (sizeof($home_menu) - 1)) {
						$insert_where = sizeof($home_menu) - 1;
					}
					//
					// For each line of the file, remove trailing spaces and special chars
					//foreach ($home_menu as $key => $enreg) {
					//	$home_menu[$key] = trim($enreg);
					//}
					//
					// If the given link url is empty, then replace the link url by a link to the link file created
					if (empty($link_url) || $link_url == 'http://') {
						$link_url = api_get_path(WEB_PATH).'index.php?include='.urlencode($filename);
						// If the file doesn't exist, then create it and
						// fill it with default text
                        
                        $fp = @fopen($homep.$filename, 'w');                       
                        if ($fp) {
                            if (empty($link_html)) {
                                fputs($fp, get_lang('MyTextHere'));
                            } else {
                            	fputs($fp, $link_html);
                            }
                            fclose($fp);
                        } 
					}
					// If the requested action is to edit a link, open the file and
					// write to it (if the file doesn't exist, create it)
					if (in_array($action, array('edit_link'))  && !empty($link_html)) {                     
						  $fp = @fopen($homep.$filename, 'w');
						  if ($fp) {
							 fputs($fp, $link_html);
							 fclose($fp);
						  }                       
					}
					// If the requested action is to create a link, make some room
					// for the new link in the home_menu array at the requested place
					// and insert the new link there
					if ($action == 'insert_link' || $action == 'insert_tabs') {
						for ($i = sizeof($home_menu); $i; $i--) {
							if ($i > $insert_where) {
								$home_menu[$i] = $home_menu[$i - 1];
							} else {
								break;
							}
						}
						$home_menu[$insert_where + 1] = '<li><a href="'.$link_url.'" target="'.($target_blank ? '_blank' : '_self').'"><span>'.$link_name.'</span></a></li>';
					} else {
						// If the request is about a link edition, change the link
						$home_menu[$link_index]='<li><a href="'.$link_url.'" target="'.($target_blank?'_blank':'_self').'"><span>'.$link_name.'</span></a></li>';
					}
					// Re-build the file from the home_menu array
					$home_menu = implode("\n", $home_menu);
					// Write                    
					if (file_exists($homep.$menuf.'_'.$lang.$ext)) {
						if (is_writable($homep.$menuf.'_'.$lang.$ext)) {
							$fp = fopen($homep.$menuf.'_'.$lang.$ext, 'w');                          
							fputs($fp, $home_menu);
							fclose($fp);
							if (file_exists($homep.$menuf.$ext)) {
								if (is_writable($homep.$menuf.$ext)) {
									$fpo = fopen($homep.$menuf.$ext, 'w');
									fputs($fpo, $home_menu);
									fclose($fpo);
								}
							}
						} else {
							$errorMsg = get_lang('HomePageFilesNotWritable');
						}
					} else {
						//File does not exist
						$fp = fopen($homep.$menuf.'_'.$lang.$ext, 'w');
						fputs($fp, $home_menu);
						fclose($fp);
					}
				}
				break;
		} //end of switch($action)

		if (empty($errorMsg)) {
			header('Location: '.api_get_self());
			exit();
		}
	} else {
		//if POST[formSent] is not set
		switch ($action) {
			case 'open_link':
				// Previously, filtering of GET['link'] was done here but it left
				// a security threat. Filtering has now been moved outside conditions
				break;
			case 'delete_tabs':
			case 'delete_link':
				// A link is deleted by getting the file into an array, removing the
				// link and re-writing the array to the file
				$link_index = intval($_GET['link_index']);
				$menuf = ($action == 'delete_tabs')? $menutabs : $menuf;
				$home_menu = @file($homep.$menuf.'_'.$lang.$ext);
				if (empty($home_menu)) {
					$home_menu = array();
				}
				foreach ($home_menu as $key => $enreg) {
					if ($key == $link_index) {
						unset($home_menu[$key]);
					} else {
						$home_menu[$key] = trim($enreg);
					}
				}
				$home_menu = implode("\n", $home_menu);
				$home_menu = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
                
				$fp = fopen($homep.$menuf.'_'.$lang.$ext, 'w');
				fputs($fp, $home_menu);
				fclose($fp);
				if (file_exists($homep.$menuf.$ext)) {
					if (is_writable($homep.$menuf.$ext)) {
						$fpo = fopen($homep.$menuf.$ext,'w');
						fputs($fpo, $home_menu);
						fclose($fpo);
					}
				}
				header('Location: '.api_get_self());
				exit();
				break;
			case 'edit_top':
				// This request is only the preparation for the update of the home_top
				$home_top = '';
				if (is_file($homep.$topf.'_'.$lang.$ext) && is_readable($homep.$topf.'_'.$lang.$ext)) {
					$home_top = @(string)file_get_contents($homep.$topf.'_'.$lang.$ext);
				} elseif (is_file($homep.$topf.$lang.$ext) && is_readable($homep.$topf.$lang.$ext)) {
					$home_top = @(string)file_get_contents($homep.$topf.$lang.$ext);
				} else {
					$errorMsg = get_lang('HomePageFilesNotReadable');
				}
				$home_top = api_to_system_encoding($home_top, api_detect_encoding(strip_tags($home_top)));
				break;
			case 'edit_notice':
				// This request is only the preparation for the update of the home_notice
				$home_notice = '';
				if (is_file($homep.$noticef.'_'.$lang.$ext) && is_readable($homep.$noticef.'_'.$lang.$ext)) {
					$home_notice = @file($homep.$noticef.'_'.$lang.$ext);
				} elseif (is_file($homep.$noticef.$lang.$ext) && is_readable($homep.$noticef.$lang.$ext)) {
					$home_notice = @file($homep.$noticef.$lang.$ext);
				} else {
					$errorMsg = get_lang('HomePageFilesNotReadable');
				}
				if (empty($home_notice)) {
					$home_notice = array();
				}
				$notice_title = strip_tags($home_notice[0]);
				$notice_title = api_to_system_encoding($notice_title, api_detect_encoding($notice_title));
				$notice_text = strip_tags(str_replace('<br />', "\n", $home_notice[1]), '<a>');
				$notice_text = api_to_system_encoding($notice_text, api_detect_encoding(strip_tags($notice_text)));
				break;
			case 'edit_news':
				// This request is the preparation for the update of the home_news page
				$home_news = '';
				if (is_file($homep.$newsf.'_'.$lang.$ext) && is_readable($homep.$newsf.'_'.$lang.$ext)) {
					$home_news = @(string)file_get_contents($homep.$newsf.'_'.$lang.$ext);
				} elseif (is_file($homep.$newsf.$lang.$ext) && is_readable($homep.$newsf.$lang.$ext)) {
					$home_news = @(string)file_get_contents($homep.$newsf.$lang.$ext);
				} else {
					$errorMsg = get_lang('HomePageFilesNotReadable');
				}
				$home_news = api_to_system_encoding($home_news, api_detect_encoding(strip_tags($home_news)));
				break;
			case 'insert_link':
				// This request is the preparation for the addition of an item in home_menu
				$home_menu = '';
				$menuf = ($action == 'edit_tabs')? $menutabs : $menuf;
				if (is_file($homep.$menuf.'_'.$lang.$ext) && is_readable($homep.$menuf.'_'.$lang.$ext)) {
					$home_menu = @file($homep.$menuf.'_'.$lang.$ext);
				} elseif(is_file($homep.$menuf.$lang.$ext) && is_readable($homep.$menuf.$lang.$ext)) {
					$home_menu = @file($homep.$menuf.$lang.$ext);
				} else {
					$errorMsg = get_lang('HomePageFilesNotReadable');
				}
				if (empty($home_menu)) {
					$home_menu = array();
				}
				if (!empty($home_menu)) {
					$home_menu = implode("\n", $home_menu);
					$home_menu = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
					$home_menu = explode("\n", $home_menu);
				}
				$home_menu = array_values(array_filter(array_map('trim', $home_menu), 'strlen'));
				break;
			case 'insert_tabs':
				// This request is the preparation for the addition of an item in home_menu
				$home_menu = '';
				if (is_file($homep.$menutabs.'_'.$lang.$ext) && is_readable($homep.$menutabs.'_'.$lang.$ext)) {
					$home_menu = @file($homep.$menutabs.'_'.$lang.$ext);
				} elseif (is_file($homep.$menutabs.$lang.$ext) && is_readable($homep.$menutabs.$lang.$ext)) {
					$home_menu = @file($homep.$menutabs.$lang.$ext);
				} else {
					$errorMsg = get_lang('HomePageFilesNotReadable');
				}
				if (empty($home_menu)) {
					$home_menu = array();
				}
				if (!empty($home_menu)) {
					$home_menu = implode("\n", $home_menu);
					$home_menu = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
					$home_menu = explode("\n", $home_menu);
				}
				$home_menu = array_values(array_filter(array_map('trim', $home_menu), 'strlen'));
				break;
			case 'edit_tabs':
			case 'edit_link':
				// This request is the preparation for the edition of the links array
				$home_menu = '';
				$menuf = ($action == 'edit_tabs')? $menutabs : $menuf;
				if (is_file($homep.$menuf.'_'.$lang.$ext) && is_readable($homep.$menuf.'_'.$lang.$ext)) {
					$home_menu = @file($homep.$menuf.'_'.$lang.$ext);
				} elseif(is_file($homep.$menuf.$lang.$ext) && is_readable($homep.$menuf.$lang.$ext)) {
					$home_menu = @file($homep.$menuf.$lang.$ext);
				} else {
					$errorMsg = get_lang('HomePageFilesNotReadable');
				}
				if (empty($home_menu)) {
					$home_menu = array();
				}
				if (!empty($home_menu)) {
					$home_menu = implode("\n", $home_menu);
					$home_menu = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
					$home_menu = explode("\n", $home_menu);
				}

				$link_index = intval($_GET['link_index']);

				$target_blank = false;
				$link_name = '';
				$link_url = '';

				//$home_menu_new = array();
				//
				//Cleaning array
				//foreach ($home_menu as $item) {
				//	if(!empty($item)) {
				//		$home_menu_new[] = $item;
				//	}
				//}
				//$home_menu = $home_menu_new;

				// Cleaning the array
				$home_menu = array_values(array_filter(array_map('trim', $home_menu), 'strlen'));

				// For each line of the home_menu file
				foreach ($home_menu as $key => $enreg) {
					// Check if the current item is the one we want to update
					if ($key == $link_index) {
						// This is the link we want to update
						// Check if the target should be "_blank"
						if (strstr($enreg, 'target="_blank"')) {
							$target_blank = true;
						}
						// Remove dangerous HTML tags from the link itself (this is an
						// additional measure in case a link previously contained
						// unsecure tags)
						$link_name = strip_tags($enreg);

						// Get the contents of "href" attribute in $link_url
						$enreg = explode('href="',$enreg);
						list($link_url) = explode('"', $enreg[sizeof($enreg) - 1]);

						// If the link contains the web root of this portal, then strip
						// it off and keep only the name of the file that needs edition
						if (strstr($link_url, $_configuration['root_web']) && strstr($link_url, '?include=')) {
							$link_url = explode('?include=', $link_url);

							$filename = $link_url[sizeof($link_url) - 1];

							if (!strstr($filename, '/') && strstr($filename, '.html')) {
								// Get oonly the contents of the link file
								$link_html = @file($homep.$filename);
								$link_html = implode('', $link_html);
								$link_url = '';
							} else {
								$filename = '';
							}
						}
						break;
					}
				}
				break;
		}//end of second switch($action) (when POST['formSent'] was not set, yet)
	}// end of "else" in if($_POST['formSent']) condition
} else {
	//if $action is empty, then prepare a list of the course categories to display (?)
	$Categories = Database::store_result(Database::query("SELECT name FROM $tbl_category WHERE parent_id IS NULL ORDER BY tree_pos"));
}

// Display section

Display::display_header($tool_name);

//api_display_tool_title($tool_name);

switch ($action) {
	case 'open_link':
		if (!empty($link)) {
			// $link is only set in case of action=open_link and is filtered
			$open = @(string)file_get_contents($homep.$link);
			$open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
			echo $open;
		}
		break;
	case 'edit_notice':
		// Display for edit_notice case
		?>
		<form action="<?php echo api_get_self(); ?>?action=<?php echo $action; ?>" method="post" style="margin:0px;">
		<div class="row"><div class="form_header"><?php echo $tool_name; ?></div></div>
		<input type="hidden" name="formSent" value="1"/>

		<?php
		if (!empty($errorMsg)) {
			//echo '<tr><td colspan="2">';
			Display::display_normal_message($errorMsg);
			//echo '</td></tr>';
		}
		?>

		<table border="0" cellpadding="5" cellspacing="0">
		<tr><td colspan="2"><?php echo '<span style="font-style: italic;">'.get_lang('LetThoseFieldsEmptyToHideTheNotice').'</span>'; ?></tr>
		<tr>
		  <td nowrap="nowrap"><?php echo get_lang('NoticeTitle'); ?> :</td>
		  <td><input type="text" name="notice_title" size="30" maxlength="50" value="<?php echo $notice_title; ?>" style="width: 350px;"/></td>
		</tr>
		<tr>
		  <td nowrap="nowrap" valign="top"><?php echo get_lang('NoticeText'); ?> :</td>
		  <td><textarea name="notice_text" cols="30" rows="5" wrap="virtual" style="width: 350px;"><?php echo $notice_text; ?></textarea></td>
		</tr>
		<tr>
		  <td>&nbsp;</td>
		  <td><button class="save" type="submit" value="<?php echo get_lang('Ok'); ?>"/><?php echo get_lang('Ok'); ?></button></td>
		</tr>
		</table>
		</form>
		<?php
		break;
	case 'insert_tabs':
	case 'edit_tabs':
	case 'insert_link':
	case 'edit_link':

		if (!empty($errorMsg)) {
			Display::display_normal_message($errorMsg);
		}

		$default = array();
		$form = new FormValidator('configure_homepage_'.$action, 'post', api_get_self().'?action='.$action, '', array('style' => 'margin: 0px;'));
		$renderer =& $form->defaultRenderer();
		$renderer->setFormTemplate('<form{attributes}><table border="0" cellpadding="5" cellspacing="0" width="100%">{content}</table></form>');
		$renderer->setElementTemplate('{element}');
		$renderer->setRequiredNoteTemplate('');
		$form->addElement('header', '', $tool_name);
		$form->addElement('hidden', 'formSent', '1');
		$form->addElement('hidden', 'link_index', ($action == 'edit_link' || $action == 'edit_tabs') ? $link_index : '0');
		$form->addElement('hidden', 'filename', ($action == 'edit_link' || $action == 'edit_tabs') ? $filename : '');

		$form->addElement('html', '<tr><td nowrap="nowrap" style="width: 15%;">'.get_lang('LinkName').' :</td><td>');
		$default['link_name'] = $link_name;
		$form->addElement('text', 'link_name', get_lang('LinkName'), array('size' => '30', 'maxlength' => '50'));
		$form->addElement('html', '</td></tr>');

		$form->addElement('html', '<tr><td nowrap="nowrap">'.get_lang('LinkURL').' ('.get_lang('Optional').') :</td><td>');
		$default['link_url'] = empty($link_url) ? 'http://' : api_htmlentities($link_url, ENT_QUOTES);
		$form->addElement('text', 'link_url', get_lang('LinkName'), array('size' => '30', 'maxlength' => '100', 'style' => 'width: 350px;'));
		$form->addElement('html', '</td></tr>');

		if ($action == 'insert_link' || $action == 'insert_tabs') {
			$form->addElement('html', '<tr><td nowrap="nowrap">'.get_lang('InsertThisLink').' :</td>');
			$form->addElement('html', '<td><select name="insert_where"><option value="-1">'.get_lang('FirstPlace').'</option>');
			if (is_array($home_menu)){
				foreach ($home_menu as $key => $enreg) {
					if (strlen($enreg = trim(strip_tags($enreg))) > 0) {
						$form->addElement('html', '<option value="'.$key.'" '.($formSent && $insert_where == $key ? 'selected="selected"' : '').' >'.get_lang('After').' &quot;'.$enreg.'&quot;</option>');
					}
				}
			}
			$form->addElement('html', '</select></td></tr>');
		}

		$form->addElement('html', '<tr><td nowrap="nowrap">'.get_lang('OpenInNewWindow').'</td><td>');
		$target_blank_checkbox = & $form->addElement('checkbox', 'target_blank', '', '&nbsp;'.get_lang('Yes'), 1);
		if ($target_blank) $target_blank_checkbox->setChecked(true);
		$form->addElement('html', '</td></tr>');

		//if($action == 'edit_link' && empty($link_url))
		if ($action == 'edit_link' && (empty($link_url) || $link_url == 'http://')) {
			$form->addElement('html', '</table><table border="0" cellpadding="5" cellspacing="0" width="100%"><tr><td>');
			$form->addElement('html', '</td></tr><tr><td>');
			if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
				$form->addElement('html', WCAG_Rendering::create_xhtml(isset($_POST['link_html'])?$_POST['link_html']:$link_html));
			} else {
				$default['link_html'] = isset($_POST['link_html']) ? $_POST['link_html'] : $link_html;
				$form->add_html_editor('link_html', '', true, false, array('ToolbarSet' => 'PortalHomePage', 'Width' => '100%', 'Height' => '400'));
			}
			$form->addElement('html', '</td></tr><tr><td>');
			$form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');
			$form->addElement('html', '</td></tr>');
		} else {
            if (in_array($action, array('edit_tabs','insert_tabs'))) {
                $form->addElement('html', '<tr><td valign="top">'.get_lang('Content').' ('.get_lang('Optional').')</td><td>');
                if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
                    $form->addElement('html', WCAG_Rendering::create_xhtml(isset($_POST['link_html'])?$_POST['link_html']:$link_html));
                } else {
                    $default['link_html'] = isset($_POST['link_html']) ? $_POST['link_html'] : $link_html;
                    $form->add_html_editor('link_html', '', true, false, array('ToolbarSet' => 'PortalHomePage', 'Width' => '100%', 'Height' => '400'));
                }
            } else {
            	$form->addElement('html', '<tr><td valign="top"></td><td>');
            }            
			$form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');
			$form->addElement('html', '</td></tr>');
		}

		$form->setDefaults($default);
		$form->display();

		break;
	case 'edit_top':
	case 'edit_news':
		if ($action == 'edit_top') {
			$name = $topf;
			$open = $home_top;
		} else {
			$name = $newsf;
			$open = @(string)file_get_contents($homep.$newsf.'_'.$lang.$ext);
		}
		$open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));

		if (!empty($errorMsg)) {
			Display::display_normal_message($errorMsg); //main API
		}

		$default = array();
		$form = new FormValidator('configure_homepage_'.$action, 'post', api_get_self().'?action='.$action, '', array('style' => 'margin: 0px;'));
		$renderer =& $form->defaultRenderer();
		$renderer->setHeaderTemplate('');
		$renderer->setFormTemplate('<form{attributes}><table border="0" cellpadding="5" cellspacing="0" width="100%">{content}</table></form>');
		$renderer->setElementTemplate('<tr><td>{element}</td></tr>');
		$renderer->setRequiredNoteTemplate('');
		$form->addElement('hidden', 'formSent', '1');

		if ($action == 'edit_news'){
			$_languages = api_get_languages();
			$html = '<tr><td>'.get_lang('ChooseNewsLanguage').' : ';
			$html .= '<select name="news_languages">';
			$html .= '<option value="all">'.get_lang('AllLanguages').'</option>';
			foreach ($_languages['name'] as $key => $value) {
				$english_name = $_languages['folder'][$key];
				if ($language == $english_name) {
					$html .= '<option value="'.$english_name.'" selected="selected">'.$value.'</option>';
				} else {
					$html .= '<option value="'.$english_name.'">'.$value.'</option>';
				}
			}
			$html .= '</select></td></tr>';
			$form->addElement('html', $html);
		}
		if (api_get_setting('wcag_anysurfer_public_pages') == 'true') {
			//TODO: review these lines
			// Print WCAG-specific HTML editor
			$html = '<tr><td>';
			$html .= WCAG_Rendering::create_xhtml($open);
			$html .= '</td></tr>';
			$form->addElement('html', $html);
		} else {
			$default[$name] = str_replace('{rel_path}', api_get_path(REL_PATH), $open);
			$form->add_html_editor($name, '', true, false, array('ToolbarSet' => 'PortalHomePage', 'Width' => '100%', 'Height' => '400'));
		}
		$form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');
		$form->setDefaults($default);
		$form->display();

		break;
	default: // When no action applies, default page to update campus homepage
		?>
		<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr>
		  <td width="70%" valign="top">
		  	<div class="actions">
			<a href="<?php echo api_get_self(); ?>?action=edit_top"><?php Display::display_icon('edit.gif', get_lang('EditHomePage')); ?></a>
			<a href="<?php echo api_get_self(); ?>?action=edit_top"><?php echo get_lang('EditHomePage'); ?></a>
		  	</div>

			<table border="0" cellpadding="5" cellspacing="0" width="100%">
			<tr>
			  <td colspan="2">
				<?php
					//print home_top contents
					if (file_exists($homep.$topf.'_'.$lang.$ext)) {
						$home_top_temp = @(string)file_get_contents($homep.$topf.'_'.$lang.$ext);
					} else {
						$home_top_temp = @(string)file_get_contents($homep.$topf.$ext);
					}
					$open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
					$open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
					echo $open;
				?>
			  </td>
			</tr>
			<tr>
			<?php
			$access_url_id = 1;
			// we only show the category options for the main chamilo installation
			if ($_configuration['multiple_access_urls']) {
				$access_url_id = api_get_current_access_url_id();
			}
			echo '<td width="50%">';
			if ($access_url_id == 1) {
				echo '<div class="actions">';
				echo '<a href="course_category.php">'.Display::display_icon('edit.gif', get_lang('Edit')).'</a>
					  <a href="course_category.php">'.get_lang('EditCategories').'</a>';
				echo '</div>';
			}
			echo '</td>
				  <td width="50%">
				  <br />';
			/* <!--<a href="<?php echo api_get_self(); ?>?action=edit_news"><?php Display::display_icon('edit.gif', get_lang('Edit')); ?></a> <a href="<?php echo api_get_self(); ?>?action=edit_news"><?php echo get_lang('EditNews'); ?></a>--> */
			echo '</td></tr>
				<tr>
				<td width="50%" valign="top">
				<table border="0" cellpadding="5" cellspacing="0" width="100%">';
				if ($access_url_id == 1) {
					if (sizeof($Categories)) {
						foreach ($Categories as $enreg) {
							echo '<tr><td>'.Display::return_icon('folder_document.gif', $enreg['name']).'&nbsp;'.$enreg['name'].'</td></tr>';
						}
						unset($Categories);
					} else {
						echo get_lang('NoCategories');
					}
				}

				echo '</table>';
				?>
			  </td>
			  <!--<td width="50%" valign="top">
				<?php
				if (file_exists($homep.$newsf.'_'.$lang.$ext)) {
					$open = @(string)file_get_contents($homep.$newsf.'_'.$lang.$ext);
					$open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
					echo $open;
				} else {
					$open = @(string)file_get_contents($homep.$newsf.$ext);
					$open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
					echo $open;
				}

			?>
			  </td>-->
			</tr>
			</table>
			<?php 
			
			// Add new page
			
			$home_menu = '';
            if (file_exists($homep.$menutabs.'_'.$lang.$ext)) {
                $home_menu = @file($homep.$menutabs.'_'.$lang.$ext);
            } else {
                $home_menu = @file($homep.$menutabs.$ext);
            }
            if (empty($home_menu)) {
                $home_menu = array();
            }
            if (!empty($home_menu)) {
                $home_menu = implode("\n", $home_menu);
                $home_menu = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
                $home_menu = explode("\n", $home_menu);
            }
            $lis = '';
            $tab_counter = 0;            
            foreach ($home_menu as $enreg) {
                $enreg = trim($enreg);
                if (!empty($enreg)) {
                    $edit_link   = ' <a href="'.api_get_self().'?action=edit_tabs&amp;link_index='.$tab_counter.'" ><span>'.Display::return_icon('edit.gif', get_lang('Edit')).'</span></a>';
                    $delete_link = ' <a href="'.api_get_self().'?action=delete_tabs&amp;link_index='.$tab_counter.'"  onclick="javascript: if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES)).'\')) return false;"><span>'.Display::return_icon('delete.gif', get_lang('Delete')).'</span></a>';
                    $tab_string = str_replace(array('href="'.api_get_path(WEB_PATH).'index.php?include=', '</li>'), array('href="'.api_get_path(WEB_CODE_PATH).'admin/'.basename(api_get_self()).'?action=open_link&link=', $edit_link.$delete_link.'</li>'), $enreg);
                    $tab_string = str_replace(array('<li>', '</li>'), '', $tab_string );                
                    $lis .= Display::tag('tr', Display::tag('td', $tab_string));
                    $tab_counter++;
                }
            }            
            ?>
            <div class="actions">
                <a href="<?php echo api_get_self(); ?>?action=insert_tabs"><?php Display::display_icon('addd.gif', get_lang('InsertLink')); echo get_lang('InsertLink'); ?></a>
            </div>
            <?php 
            
            echo '<table class="data_table">';            
            echo $lis;			
            echo '</table>';
            
			?>
		  </td>
		  <td width="10%" valign="top"></td>
		  <td width="20%" rowspan="3" valign="top">
		    <div id="menu-wrapper">
			<div id="menu" class="menu">
			<?php
			api_display_language_form();
			?>
			<form id="formLogin">
				<div><label><?php echo get_lang('LoginName'); ?></label></div>
				<div><input type="text" id="login" size="15" value="" disabled="disabled" /></div>
			    <div><label><?php echo get_lang('UserPassword'); ?></label></div>
				<div><input type="password" id="password" size="15" value="" disabled="disabled" /></div>
				<div><button class="login" type="button" name="submitAuth" value="<?php echo get_lang('Ok'); ?>" disabled="disabled"><?php echo get_lang('Ok'); ?></button></div>
			</form>
			<div class="clear"> &nbsp; </div>
			
			
			<div class="menusection">
				<span class="menusectioncaption"><?php echo get_lang('MenuUser'); ?></span>
				<ul class="menulist">
				<li><span style="color: #9D9DA1; font-weight: bold;"><?php echo api_ucfirst(get_lang('Registration')); ?></span></li>
				<li><span style="color: #9D9DA1; font-weight: bold;"><?php echo api_ucfirst(get_lang('LostPassword')); ?></span></li>
				</ul>
			</div>
			
			</div>
			
			<div id="menu" class="menu">
            <br />                    
            <a href="<?php echo api_get_self(); ?>?action=edit_notice"><?php Display::display_icon('edit.gif', get_lang('Edit')); ?></a> <a href="<?php echo api_get_self(); ?>?action=edit_notice"><?php echo get_lang('EditNotice'); ?></a>
            
            <div class="menusection note">
                    
            <?php
            $home_notice = '';
            if (file_exists($homep.$noticef.'_'.$lang.$ext)) {
                $home_notice = @(string)file_get_contents($homep.$noticef.'_'.$lang.$ext);
            } else {
                $home_notice = @(string)file_get_contents($homep.$noticef.$ext);
            }
            $home_notice = api_to_system_encoding($home_notice, api_detect_encoding(strip_tags($home_notice)));
            echo $home_notice;          
            ?>
            </div>
            <br />
            </div>
            
			
            <div id="menu" class="menu">
                <br />
                <a href="<?php echo api_get_self(); ?>?action=insert_link"><?php Display::display_icon('addd.gif', get_lang('InsertLink')); ?></a>
                <a href="<?php echo api_get_self(); ?>?action=insert_link"/><?php echo get_lang('InsertLink'); ?></a>
                
    			<div class="menusection">
    				<span class="menusectioncaption"><?php echo api_ucfirst(get_lang('General')); ?></span>
    				<ul class="menulist">
    				<?php
    					$home_menu = '';
    					if (file_exists($homep.$menuf.'_'.$lang.$ext)) {
    						$home_menu = @file($homep.$menuf.'_'.$lang.$ext);
    					} else {
    						$home_menu = @file($homep.$menuf.$ext);
    					}
    					if (empty($home_menu)) {
    						$home_menu = array();
    					}
    					if (!empty($home_menu)) {
    						$home_menu = implode("\n", $home_menu);
    						$home_menu = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
    						$home_menu = explode("\n", $home_menu);
    					}
    					$i = 0;
    					foreach ($home_menu as $enreg) {
    						$enreg = trim($enreg);
    						if (!empty($enreg)) {
    							$edit_link = '<a href="'.api_get_self().'?action=edit_link&amp;link_index='.$i.'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
    							$delete_link = '<a href="'.api_get_self().'?action=delete_link&amp;link_index='.$i.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES)).'\')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
    							echo str_replace(array('href="'.api_get_path(WEB_PATH).'index.php?include=', '</li>'), array('href="'.api_get_path(WEB_CODE_PATH).'admin/'.basename(api_get_self()).'?action=open_link&link=', '<br />'.$edit_link.' '.$delete_link.'</li>'), $enreg);
    							$i++;
    						}
    					}
    				?>
    				</ul>
			     </div>
			</div>


			
			</div> <!-- menu wrapper -->
		  </td>
		</tr>
		</table>
		<?php
		break;
}
Display::display_footer();