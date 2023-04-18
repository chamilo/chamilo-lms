<?php
/* For licensing terms, see /license.txt */

/**
 * Configure the portal homepage (manages multi-urls and languages).
 *
 * @package chamilo.admin
 */

use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Creates menu tabs for logged and anonymous users.
 *
 * This function copies the file containing private a public tabs (home_tabs_logged_in_$language.html)
 * in to the public tab template (home_tabs_$language.html) but without the private tabs.
 * Private tabs are the ones including "?private" string in the end of the url, ex: http://google.com/?private
 *
 * @param  string Name of the file been updated by the administration, ex: home_tabs_logged_in_($language).html
 */
function home_tabs($file_logged_in)
{
    $post = strpos($file_logged_in, "_logged_in");
    if ($post !== false) {
        $file_logged_out = str_replace('_logged_in', '', $file_logged_in);
        //variables initialization
        $data_logged_out = [];
        $data_logged_in = [];

        //we read the file with all links
        $file = file($file_logged_in);
        foreach ($file as $line) {
            $line = str_replace("\n", '', $line);
            //not logged user only sees public links
            if (!preg_match('/::private/', $line)) {
                $data_logged_out[] = $line;
            }
            //logged user only sees all links
            $data_logged_in[] = $line;
        }
        //tabs file for logged out users
        if (file_exists($file_logged_out)) {
            $fp = fopen($file_logged_out, 'w');
            fputs($fp, implode("\n", $data_logged_out));
            fclose($fp);
        }
        //tabs file for logged in users
        $fp = fopen($file_logged_in, 'w');
        fputs($fp, implode("\n", $data_logged_in));
        fclose($fp);
    }
}

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
$_SESSION['this_section'] = $this_section;
$this_page = '';

api_protect_admin_script();

$httpRequest = HttpRequest::createFromGlobals();

$htmlHeadXtra[] = '<script>
$(function() {
    $("#all_langs").change(function() {
        if ($("#all_langs[type=checkbox]").is(":checked")) {
            $("#table_langs [type=checkbox]").prop("checked", true);
        } else {
            $("#table_langs [type=checkbox]").prop("checked", false);
        }
    });
});
</script>';

$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : null;
$tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
$tool_name = get_lang('ConfigureHomePage');
$_languages = api_get_languages();
$selfUrl = api_get_self();
$interbreadcrumb[] = [
    'url' => 'index.php',
    'name' => get_lang('PlatformAdmin'),
];

if (!empty($action)) {
    $interbreadcrumb[] = [
        'url' => 'configure_homepage.php',
        'name' => get_lang('ConfigureHomePage'),
    ];

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

$languageGet = isset($_GET['language']) ? Security::remove_XSS($_GET['language']) : $lang;

// Ensuring availability of main files in the corresponding language
$homePath = api_get_path(SYS_HOME_PATH);

if (api_is_multiple_url_enabled()) {
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $url_info = api_get_access_url($access_url_id);
        $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
        $clean_url = api_replace_dangerous_char($url);
        $clean_url = str_replace('/', '-', $clean_url);
        $clean_url .= '/';

        $homep = $homePath; //homep for Home Path
        $homep_new = $homePath.$clean_url; //homep for Home Path added the url
        $new_url_dir = $homePath.$clean_url;
        //we create the new dir for the new sites
        if (!is_dir($new_url_dir)) {
            mkdir($new_url_dir, api_get_permissions_for_new_directories());
        }
    }
} else {
    $homep_new = '';
    $homep = $homePath; //homep for Home Path
}

$menuf = 'home_menu'; //menuf for Menu File
$newsf = 'home_news'; //newsf for News File
$topf = 'home_top'; //topf for Top File
$noticef = 'home_notice'; //noticef for Notice File
$menutabs = 'home_tabs'; //menutabs for tabs Menu
$mtloggedin = 'home_tabs_logged_in'; //menutabs for tabs Menu
$ext = '.html'; //ext for HTML Extension - when used frequently, variables are
// faster than hardcoded strings
$homef = [$menuf, $newsf, $topf, $noticef, $menutabs, $mtloggedin];

// If language-specific file does not exist, create it by copying default file
foreach ($homef as $my_file) {
    if (api_is_multiple_url_enabled()) {
        if (!file_exists($homep_new.$my_file.'_'.$lang.$ext)) {
            if (!file_exists($homep.$my_file.$ext)) {
                touch($homep.$my_file.$ext);
            }
            @copy($homep.$my_file.$ext, $homep_new.$my_file.'_'.$lang.$ext);
        }
    } else {
        if (!file_exists($homep.$my_file.'_'.$lang.$ext)) {
            if (!file_exists($homep.$my_file.$ext)) {
                touch($homep.$my_file.$ext);
            }
            @copy($homep.$my_file.$ext, $homep.$my_file.'_'.$lang.$ext);
        }
    }
}

if (api_is_multiple_url_enabled()) {
    $homep = $homep_new;
}

// Check WCAG settings and prepare edition using WCAG
$errorMsg = '';

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

// Start analysing requested actions
if (!empty($action)) {
    if (!empty($_POST['formSent'])) {
        // Variables used are $homep for home path, $menuf for menu file, $newsf
        // for news file, $topf for top file, $noticef for noticefile,
        // $ext for '.html'
        switch ($action) {
            case 'edit_top':
                // Filter
                $home_top = trim(stripslashes($_POST['home_top']));

                // Write
                if (is_writable($homep)) {
                    // Default
                    if (is_writable($homep.$topf.'_'.$lang.$ext)) {
                        $fp = fopen($homep.$topf.'_'.$lang.$ext, 'w');
                        fputs($fp, $home_top);
                        fclose($fp);

                        // Language
                        foreach ($_languages['name'] as $key => $value) {
                            $lang_name = $_languages['folder'][$key];
                            if (isset($_POST[$lang_name])) {
                                $fp = fopen($homep.$topf.'_'.$lang_name.$ext, 'w');
                                fputs($fp, $home_top);
                                fclose($fp);
                            }
                        }
                    } else {
                        $errorMsg = get_lang('HomePageFilesNotWritable');
                    }
                } else {
                    //File does not exist
                    $fp = fopen($homep.$topf.'_'.$lang.$ext, 'w');
                    fputs($fp, $home_top);
                    fclose($fp);

                    foreach ($_languages['name'] as $key => $value) {
                        $lang_name = $_languages['folder'][$key];
                        if (isset($_POST[$lang_name])) {
                            if (file_exists($homep.$topf.'_'.$lang_name.$ext)) {
                                $fp = fopen($homep.$topf.'_'.$lang_name.$ext, 'w');
                                fputs($fp, $home_top);
                                fclose($fp);
                            }
                        }
                    }
                }

                if (EventsMail::check_if_using_class('portal_homepage_edited')) {
                    EventsDispatcher::events('portal_homepage_edited', ['about_user' => api_get_user_id()]);
                }
                Event::addEvent(
                    LOG_HOMEPAGE_CHANGED,
                    'edit_top',
                    cut(strip_tags($home_top), 254),
                    api_get_utc_datetime(),
                    api_get_user_id()
                );
                break;
            case 'edit_notice':
                // Filter
                $notice_title = trim(strip_tags(stripslashes($_POST['notice_title'])));
                $notice_text = trim(str_replace(["\r", "\n"], ['', '<br />'], strip_tags(stripslashes($_POST['notice_text']), '<a>')));
                if (empty($notice_title) || empty($notice_text)) {
                    $errorMsg = get_lang('NoticeWillBeNotDisplayed');
                }
                // Write
                if (file_exists($homep.$noticef.'_'.$lang.$ext)) {
                    if (is_writable($homep.$noticef.'_'.$lang.$ext)) {
                        $fp = fopen($homep.$noticef.'_'.$lang.$ext, 'w');
                        if ($errorMsg == '') {
                            fputs($fp, "<h5>$notice_title</h5><p>\n$notice_text");

                            foreach ($_languages['name'] as $key => $value) {
                                $lang_name = $_languages['folder'][$key];
                                if (isset($_POST[$lang_name])) {
                                    if (file_exists($homep.$noticef.'_'.$lang_name.$ext)) {
                                        if (is_writable($homep.$noticef.'_'.$lang_name.$ext)) {
                                            $fp = fopen($homep.$noticef.'_'.$lang_name.$ext, 'w');
                                            fputs($fp, "<h5>$notice_title</h5><p>\n$notice_text");
                                            fclose($fp);
                                        }
                                    }
                                }
                            }
                        } else {
                            fputs($fp, '');

                            foreach ($_languages['name'] as $key => $value) {
                                $lang_name = $_languages['folder'][$key];
                                if (isset($_POST[$lang_name])) {
                                    if (file_exists($homep.$noticef.'_'.$lang_name.$ext)) {
                                        $fp1 = fopen($homep.$noticef.'_'.$lang_name.$ext, 'w');
                                        fputs($fp1, '');
                                        fclose($fp1);
                                    }
                                }
                            }
                        }
                        fclose($fp);
                    } else {
                        $errorMsg .= "<br/>\n".get_lang('HomePageFilesNotWritable');
                    }
                } else {
                    //File does not exist
                    $fp = fopen($homep.$noticef.'_'.$lang.$ext, 'w');
                    fputs($fp, "<h5>$notice_title</h5><p>\n$notice_text");
                    fclose($fp);
                }
                Event::addEvent(
                    LOG_HOMEPAGE_CHANGED,
                    'edit_notice',
                    cut(strip_tags($notice_title), 254),
                    api_get_utc_datetime(),
                    api_get_user_id()
                );
                break;
            case 'edit_news':
                //Filter
                $home_news = trim(stripslashes($_POST['home_news']));

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
                Event::addEvent(
                    LOG_HOMEPAGE_CHANGED,
                    'edit_news',
                    strip_tags(cut($home_news, 254)),
                    api_get_utc_datetime(),
                    api_get_user_id()
                );
                break;
            case 'insert_tabs':
            case 'edit_tabs':
            case 'insert_link':
            case 'edit_link':
                $link_index = $httpRequest->request->getInt('link_index');
                $insert_where = $httpRequest->request->getInt('insert_where');
                $link_name = Security::remove_XSS($httpRequest->request->get('link_name'));
                $link_url = Security::remove_XSS($_POST['link_url']);
                $add_in_tab = $httpRequest->request->getInt('add_in_tab');
                $link_html = Security::remove_XSS($_POST['link_html']);
                $filename = Security::remove_XSS($_POST['filename']);
                $target_blank = $httpRequest->request->has('target_blank');

                if ($link_url == 'http://' || $link_url == 'https://') {
                    $link_url = '';
                } elseif (!empty($link_url) && !strstr($link_url, '://')) {
                    $link_url = 'http://'.$link_url;
                }
                $menuf = ($action == 'insert_tabs' || $action == 'edit_tabs') ? $mtloggedin : $menuf;

                if (!is_writable($homep.$menuf.'_'.$lang.$ext)) {
                    $errorMsg = get_lang('HomePageFilesNotWritable');
                } elseif (empty($link_name)) {
                    $errorMsg = get_lang('PleaseEnterLinkName');
                } else {
                    // New links are added as new files in the home/ directory
                    if ($action == 'insert_link' || $action == 'insert_tabs' || empty($filename) || strstr($filename, '/') || !strstr($filename, '.html')) {
                        $filename = api_replace_dangerous_char($link_name).'.html';
                    }

                    // "home_" prefix for links are renamed to "user_" prefix (to avoid name clash with existing home page files)
                    if (!empty($filename)) {
                        $filename = str_replace('home_', 'user_', $filename);
                    }
                    // If the typical language suffix is not found in the file name,
                    // replace the ".html" suffix by "_en.html" or the active menu language
                    if (!strstr($filename, '_'.$lang.$ext)) {
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

                    if (empty($link_url) || $link_url == 'http://' || $link_url == 'https://') {
                        $link_url = api_get_path(WEB_PATH).'index.php?include='.urlencode($filename);
                        // If the file doesn't exist, then create it and
                        // fill it with default text

                        $fp = @fopen($homep.$filename, 'w');
                        if ($fp) {
                            if (empty($link_html)) {
                                fputs($fp, get_lang('MyTextHere'));
                                home_tabs($homep.$filename);
                            } else {
                                fputs($fp, $link_html);
                                home_tabs($homep.$filename);
                            }
                            fclose($fp);
                        }
                    }
                    // If the requested action is to edit a link, open the file and
                    // write to it (if the file doesn't exist, create it)
                    if (in_array($action, ['edit_link']) && !empty($link_html)) {
                        $fp = @fopen($homep.$filename, 'w');
                        if ($fp) {
                            fputs($fp, $link_html);
                            home_tabs($homep.$filename);
                            fclose($fp);
                        }
                    }

                    $class_add_in_tab = 'class="show_menu"';

                    if (!$add_in_tab) {
                        $class_add_in_tab = 'class="hide_menu"';
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
                        $home_menu[$insert_where + 1] = '<li '.$class_add_in_tab.'><a href="'.$link_url.'" target="'.($target_blank ? '_blank' : '_self').'">'.$link_name.'</a></li>';
                    } else {
                        // If the request is about a link edition, change the link
                        $home_menu[$link_index] = '<li '.$class_add_in_tab.'><a href="'.$link_url.'" target="'.($target_blank ? '_blank' : '_self').'">'.$link_name.'</a></li>';
                    }
                    // Re-build the file from the home_menu array
                    $home_menu = implode("\n", $home_menu);
                    // Write
                    if (file_exists($homep.$menuf.'_'.$lang.$ext)) {
                        if (is_writable($homep.$menuf.'_'.$lang.$ext)) {
                            $fp = fopen($homep.$menuf.'_'.$lang.$ext, 'w');
                            fputs($fp, $home_menu);
                            home_tabs($homep.$menuf.'_'.$lang.$ext);
                            fclose($fp);

                            foreach ($_languages['name'] as $key => $value) {
                                $lang_name = $_languages['folder'][$key];
                                if (isset($_POST[$lang_name])) {
                                    $fp = fopen($homep.$menuf.'_'.$lang_name.$ext, 'w');
                                    fputs($fp, $home_menu);
                                    home_tabs($homep.$menuf.'_'.$lang_name.$ext);
                                    fclose($fp);
                                }
                            }

                            if (file_exists($homep.$menuf.$ext)) {
                                if (is_writable($homep.$menuf.$ext)) {
                                    $fpo = fopen($homep.$menuf.$ext, 'w');
                                    fputs($fpo, $home_menu);
                                    home_tabs($homep.$menuf.$ext);
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
                        home_tabs($homep.$menuf.'_'.$lang.$ext);
                        fclose($fp);

                        foreach ($_languages['name'] as $key => $value) {
                            $lang_name = $_languages['folder'][$key];
                            if (isset($_POST[$lang_name])) {
                                $fp = fopen($homep.$menuf.'_'.$lang_name.$ext, 'w');
                                fputs($fp, $home_menu);
                                home_tabs($homep.$menuf.'_'.$lang_name.$ext);
                                fclose($fp);
                            }
                        }
                    }
                }
                Event::addEvent(
                    LOG_HOMEPAGE_CHANGED,
                    $action,
                    cut($link_name.':'.$link_url, 254),
                    api_get_utc_datetime(),
                    api_get_user_id()
                );
                break;
        } //end of switch($action)

        if (empty($errorMsg)) {
            header('Location: '.$selfUrl.'?language='.$languageGet);
            exit();
        }
    } else {
        //if POST[formSent] is not set
        switch ($action) {
            case 'delete_all':
                foreach ($_languages['name'] as $key => $value) {
                    $lang = $_languages['folder'][$key];
                    $link_index = intval($_GET['link_index']);
                    $menuf = $mtloggedin;
                    $home_menu = @file($homep.$menuf.'_'.$lang.$ext);
                    if (empty($home_menu)) {
                        $home_menu = [];
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
                    home_tabs($homep.$menuf.'_'.$lang.$ext);
                    fclose($fp);
                    if (file_exists($homep.$menuf.$ext)) {
                        if (is_writable($homep.$menuf.$ext)) {
                            $fpo = fopen($homep.$menuf.$ext, 'w');
                            fputs($fpo, $home_menu);
                            home_tabs($homep.$menuf.$ext);
                            fclose($fpo);
                        }
                    }
                    header('Location: '.$selfUrl);
                }
                exit();
                break;
            case 'open_link':
                // Previously, filtering of GET['link'] was done here but it left
                // a security threat. Filtering has now been moved outside conditions
                break;
            case 'delete_tabs':
            case 'delete_link':
                // A link is deleted by getting the file into an array, removing the
                // link and re-writing the array to the file
                $link_index = intval($_GET['link_index']);
                $menuf = ($action == 'delete_tabs') ? $mtloggedin : $menuf;
                $home_menu = @file($homep.$menuf.'_'.$lang.$ext);
                if (empty($home_menu)) {
                    $home_menu = [];
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
                home_tabs($homep.$menuf.'_'.$lang.$ext);
                fclose($fp);
                if (file_exists($homep.$menuf.$ext)) {
                    if (is_writable($homep.$menuf.$ext)) {
                        $fpo = fopen($homep.$menuf.$ext, 'w');
                        fputs($fpo, $home_menu);
                        home_tabs($homep.$menuf.$ext);
                        fclose($fpo);
                    }
                }
                header('Location: '.$selfUrl);
                exit();
                break;
            case 'edit_top':
                // This request is only the preparation for the update of the home_top
                $home_top = '';
                if (is_file($homep.$topf.'_'.$lang.$ext) && is_readable($homep.$topf.'_'.$lang.$ext)) {
                    $home_top = @(string) file_get_contents($homep.$topf.'_'.$lang.$ext);
                } elseif (is_file($homep.$topf.$lang.$ext) && is_readable($homep.$topf.$lang.$ext)) {
                    $home_top = @(string) file_get_contents($homep.$topf.$lang.$ext);
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
                    $home_notice = [];
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
                    $home_news = @(string) file_get_contents($homep.$newsf.'_'.$lang.$ext);
                } elseif (is_file($homep.$newsf.$lang.$ext) && is_readable($homep.$newsf.$lang.$ext)) {
                    $home_news = @(string) file_get_contents($homep.$newsf.$lang.$ext);
                } else {
                    $errorMsg = get_lang('HomePageFilesNotReadable');
                }
                $home_news = api_to_system_encoding($home_news, api_detect_encoding(strip_tags($home_news)));
                break;
            case 'insert_link':
                // This request is the preparation for the addition of an item in home_menu
                $home_menu = '';
                $menuf = ($action == 'edit_tabs') ? $mtloggedin : $menuf;
                if (is_file($homep.$menuf.'_'.$lang.$ext) && is_readable($homep.$menuf.'_'.$lang.$ext)) {
                    $home_menu = @file($homep.$menuf.'_'.$lang.$ext);
                } elseif (is_file($homep.$menuf.$lang.$ext) && is_readable($homep.$menuf.$lang.$ext)) {
                    $home_menu = @file($homep.$menuf.$lang.$ext);
                } else {
                    $errorMsg = get_lang('HomePageFilesNotReadable');
                }
                if (empty($home_menu)) {
                    $home_menu = [];
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
                if (is_file($homep.$mtloggedin.'_'.$lang.$ext) && is_readable($homep.$mtloggedin.'_'.$lang.$ext)) {
                    $home_menu = @file($homep.$mtloggedin.'_'.$lang.$ext);
                } elseif (is_file($homep.$mtloggedin.$lang.$ext) && is_readable($homep.$mtloggedin.$lang.$ext)) {
                    $home_menu = @file($homep.$mtloggedin.$lang.$ext);
                } elseif (touch($homep.$mtloggedin.'_'.$lang.$ext)) {
                    $home_menu = @file($homep.$mtloggedin.'_'.$lang.$ext);
                } else {
                    $errorMsg = get_lang('HomePageFilesNotReadable');
                }
                if (empty($home_menu)) {
                    $home_menu = [];
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
                $menuf = ($action == 'edit_tabs') ? $mtloggedin : $menuf;
                if (is_file($homep.$menuf.'_'.$lang.$ext) && is_readable($homep.$menuf.'_'.$lang.$ext)) {
                    $home_menu = @file($homep.$menuf.'_'.$lang.$ext);
                } elseif (is_file($homep.$menuf.$lang.$ext) && is_readable($homep.$menuf.$lang.$ext)) {
                    $home_menu = @file($homep.$menuf.$lang.$ext);
                } else {
                    $errorMsg = get_lang('HomePageFilesNotReadable');
                }

                if (empty($home_menu)) {
                    if (file_exists($homep.$menutabs.'_'.$lang.$ext)) {
                        $home_menu = @file($homep.$menutabs.'_'.$lang.$ext);
                    }
                }

                if (empty($home_menu)) {
                    $home_menu = [];
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

                        if (strstr($enreg, 'hide_menu')) {
                            $add_in_tab = false;
                        } else {
                            $add_in_tab = true;
                        }

                        // Remove dangerous HTML tags from the link itself (this is an
                        // additional measure in case a link previously contained
                        // unsecure tags)
                        $link_name = strip_tags($enreg);

                        // Get the contents of "href" attribute in $link_url
                        $enreg = explode('href="', $enreg);
                        list($link_url) = explode('"', $enreg[sizeof($enreg) - 1]);

                        // If the link contains the web root of this portal, then strip
                        // it off and keep only the name of the file that needs edition
                        if (strstr($link_url, '?include=')) {
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
    $Categories = CourseCategory::getCategoriesToDisplayInHomePage();
}

// Display section

Display::display_header($tool_name);

switch ($action) {
    case 'open_link':
        if (!empty($link)) {
            // $link is only set in case of action=open_link and is filtered
            $open = @(string) file_get_contents($homep.$link);
            $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
            echo $open;
        }
        break;
    case 'edit_notice':
        // Display for edit_notice case
        ?>
        <form action="<?php echo $selfUrl; ?>?action=<?php echo $action; ?>" method="post" class="form-horizontal">
            <legend><?php echo $tool_name; ?></legend>
            <input type="hidden" name="formSent" value="1"/>
            <?php
            if (!empty($errorMsg)) {
                echo Display::return_message($errorMsg, 'normal');
            }
            ?>
            <div class="row">
                <div class="col-md-12">
                    <p><?php echo get_lang('LetThoseFieldsEmptyToHideTheNotice'); ?></p>
                    <div class="form-group">
                        <label class="col-sm-3 control-label"> <?php echo get_lang('NoticeTitle'); ?> </label>
                        <div class="col-sm-6">
                            <input type="text" name="notice_title" size="30" maxlength="50"
                                   value="<?php echo $notice_title; ?>" class="form-control"/>
                        </div>
                        <div class="col-sm-3"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_lang('NoticeText'); ?></label>
                        <div class="col-sm-6">
                            <textarea name="notice_text" cols="30" rows="5" wrap="virtual"
                                      class="form-control"><?php echo $notice_text; ?></textarea>
                        </div>
                        <div class="col-sm-3"></div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-3"></div>
                        <div class="col-sm-6">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="all_langs"
                                           value="<?php echo get_lang('ApplyAllLanguages'); ?>"/> <?php echo get_lang('ApplyAllLanguages'); ?>
                                </label>
                            </div>
                        </div>
                        <div class="col-sm-3"></div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button class="btn btn-primary" type="submit"
                                    value="<?php echo get_lang('Ok'); ?>"><?php echo get_lang('Ok'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php
        break;
    case 'insert_tabs':
    case 'edit_tabs':
    case 'insert_link':
    case 'edit_link':
        $menuf = ($action == 'insert_tabs' || $action == 'edit_tabs') ? $mtloggedin : $menuf;
        if (!empty($errorMsg)) {
            echo Display::return_message($errorMsg, 'normal');
        }
        $default = [];
        $form = new FormValidator('configure_homepage_'.$action, 'post', $selfUrl.'?action='.$action, '', ['style' => 'margin: 0px;']);
        $renderer = &$form->defaultRenderer();

        $form->addElement('header', '', $tool_name);
        $form->addElement('hidden', 'formSent', '1');
        $form->addElement('hidden', 'link_index', ($action == 'edit_link' || $action == 'edit_tabs') ? $link_index : '0');
        $form->addElement('hidden', 'filename', ($action == 'edit_link' || $action == 'edit_tabs') ? (!empty($filename) ? $filename : '') : '');

        $form->addElement('text', 'link_name', get_lang('LinkName'), ['size' => '30', 'maxlength' => '50']);
        $form->applyFilter('text', 'html_filter');
        if (!empty($link_name)) {
            $default['link_name'] = $link_name;
        }
        $default['link_url'] = empty($link_url) ? 'http://' : api_htmlentities($link_url, ENT_QUOTES);
        $linkUrlComment = ($action == 'insert_tabs') ? get_lang('Optional').'<br />'.get_lang('GlobalLinkUseDoubleColumnPrivateToShowPrivately') : '';
        $form->addElement('text', 'link_url', [get_lang('LinkURL'), $linkUrlComment], ['size' => '30', 'maxlength' => '100', 'style' => 'width: 350px;']);
        $form->applyFilter('link_url', 'html_filter');

        $options = ['-1' => get_lang('FirstPlace')];

        $selected = '';

        if ($action == 'insert_link' || $action == 'insert_tabs') {
            $add_in_tab = 1;
            if (is_array($home_menu)) {
                foreach ($home_menu as $key => $enreg) {
                    if (strlen($enreg = trim(strip_tags($enreg))) > 0) {
                        $options[$key] = get_lang('After').' &quot;'.$enreg.'&quot;';
                        $formSentCheck = (!empty($_POST['formSent']) ? true : false);
                        $selected = $formSentCheck && $insert_where == $key ? $key : '';
                    }
                }
            }
            $default['insert_link'] = $selected;
            $form->addElement('select', 'insert_where', get_lang('InsertThisLink'), $options);
        }

        $target_blank_checkbox = $form->addElement('checkbox', 'target_blank', null, get_lang('OpenInNewWindow'), 1);

        if ($action == 'insert_tabs' || $action == 'edit_tabs') {
            $form->addElement('checkbox', 'add_in_tab', null, get_lang('AddInMenu'), 1);
            $default['add_in_tab'] = $add_in_tab;
        }

        if (!empty($target_blank)) {
            $target_blank_checkbox->setChecked(true);
        }

        if ($action == 'edit_link' && (empty($link_url) || $link_url == 'http://' || $link_url == 'https://')) {
            $default['link_html'] = isset($_POST['link_html']) ? $_POST['link_html'] : $link_html;
            $form->addHtmlEditor('link_html', get_lang('Content'), false, false, ['ToolbarSet' => 'PortalHomePage', 'Width' => '100%', 'Height' => '400']);
            $form->addButtonSave(get_lang('Save'), 'submit');
        } else {
            if (in_array($action, ['edit_tabs', 'insert_tabs'])) {
                $default['link_html'] = isset($_POST['link_html']) ? $_POST['link_html'] : (!empty($link_html) ? $link_html : '');
                $form->addHtmlEditor('link_html', get_lang('Content'), false, false, ['ToolbarSet' => 'PortalHomePage', 'Width' => '100%', 'Height' => '400']);
            }
            $form->addElement('checkbox', 'all_langs', null, get_lang('ApplyAllLanguages'), ['id' => 'all_langs']);
            $form->addElement('html', '<table id="table_langs" style="margin-left:159px;"><tr>');
            $i = 0;
            foreach ($_languages['name'] as $key => $value) {
                $i++;
                $lang_name = $_languages['folder'][$key];
                $html_langs = '<td width="300">';
                $html_langs .= '<label><input type="checkbox" id="lang" name="'.$lang_name.'" />&nbsp;'.$lang_name.'<label/>';
                $html_langs .= '</td>';
                if ($i % 5 == 0) {
                    $html_langs .= '</tr><tr>';
                }
                $form->addElement('html', $html_langs);
            }
            $form->addElement('html', '</tr></table><br/>');
            $form->addButtonSave(get_lang('Save'), 'submit');
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
            $open = @(string) file_get_contents($homep.$newsf.'_'.$lang.$ext);
        }
        $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));

        if (!empty($errorMsg)) {
            echo Display::return_message($errorMsg, 'normal'); //main API
        }

        $default = [];
        $form = new FormValidator(
            'configure_homepage_'.$action,
            'post',
            $selfUrl.'?action='.$action,
            '',
            ['style' => 'margin: 0px;']
        );
        $renderer = &$form->defaultRenderer();
        $renderer->setHeaderTemplate('');
        $renderer->setFormTemplate('<form{attributes}><table border="0" cellpadding="5" cellspacing="0" width="100%">{content}</table></form>');
        $renderer->setCustomElementTemplate('<tr><td>{element}</td></tr>');
        $renderer->setRequiredNoteTemplate('');
        $form->addElement('hidden', 'formSent', '1');

        if ($action == 'edit_news') {
            $_languages = api_get_languages();
            $html = '<tr><td>'.get_lang('ChooseNewsLanguage').' : ';
            $html .= '<select name="news_languages">';
            $html .= '<option value="all">'.get_lang('ApplyAllLanguages').'</option>';
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

        $default[$name] = str_replace('{rel_path}', api_get_path(REL_PATH), $open);
        $form->addHtmlEditor($name, '', true, false, ['ToolbarSet' => 'PortalHomePage', 'Width' => '100%', 'Height' => '400']);
        $form->addElement('checkbox', 'all_langs', null, get_lang('ApplyAllLanguages'), ['id' => 'all_langs']);
        $form->addElement('html', '<table id="table_langs" style="margin-left:5px;"><tr>');

        $currentLanguage = api_get_interface_language();
        $i = 0;
        foreach ($_languages['name'] as $key => $value) {
            $lang_name = $_languages['folder'][$key];
            $i++;

            $checked = null;
            if ($languageGet == $lang_name) {
                $checked = "checked";
            }
            $html_langs = '<td width="300">';
            $html_langs .= '<label><input type="checkbox" '.$checked.' id="lang" name="'.$lang_name.'" />&nbsp;'.$value.'<label/>';
            $html_langs .= '</td>';
            if ($i % 5 == 0) {
                $html_langs .= '</tr><tr>';
            }
            $form->addElement('html', $html_langs);
        }
        $form->addElement('html', '</tr></table><br/>');
        $form->addButtonSave(get_lang('Save'));
        $form->setDefaults($default);
        $form->display();

        break;
    default: // When no action applies, default page to update campus homepage
        ?>

        <section id="page-home">
            <div class="row">
                <div class="col-md-3">

                    <!-- login block -->
                    <div id="login-block" class="panel panel-default">
                        <div class="panel-body">
                            <?php echo api_display_language_form(false, true); ?>
                            <form id="formLogin" class="form-horizontal">
                                <div class="input-group">
                                    <div class="input-group-addon"><em class="fa fa-user"></em></div>
                                    <input class="form-control" type="text" id="login" value="" disabled="disabled"/>
                                </div>
                                <div class="input-group">
                                    <div class="input-group-addon"><em class="fa fa-lock"></em></div>
                                    <input type="password" id="password" class="form-control" value=""
                                           disabled="disabled"/>
                                </div>
                                <button class="btn btn-primary btn-block" type="button" name="submitAuth"
                                        value="<?php echo get_lang('LoginEnter'); ?>"
                                        disabled="disabled"><?php echo get_lang('LoginEnter'); ?></button>
                            </form>
                            <ul class="nav nav-pills nav-stacked">
                                <li><?php echo api_ucfirst(get_lang('SignUp')); ?></li>
                                <li><?php echo api_ucfirst(get_lang('LostPassword')); ?></li>
                            </ul>
                        </div>
                    </div>

                    <!-- notice block -->


                    <div class="panel-group" id="notice-block" role="tablist" aria-multiselectable="true">
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="headingOne">
                                <h4 class="panel-title">
                                    <a role="button" data-toggle="collapse" data-parent="#notice-block"
                                       href="#notice-list" aria-expanded="true" aria-controls="notice-list">
                                        <?php echo get_lang('Notice'); ?>
                                        <a class="pull-right"
                                           href="<?php echo $selfUrl; ?>?action=edit_notice"><?php Display::display_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL); ?></a>
                                    </a>
                                </h4>
                            </div>
                            <div id="notice-list" class="panel-collapse collapse in" role="tabpanel"
                                 aria-labelledby="headingOne">
                                <div class="panel-body">
                                    <?php
                                    $home_notice = '';
                                    if (file_exists($homep.$noticef.'_'.$lang.$ext)) {
                                        $home_notice = @(string) file_get_contents($homep.$noticef.'_'.$lang.$ext);
                                    } else {
                                        $home_notice = @(string) file_get_contents($homep.$noticef.$ext);
                                    }
                                    $home_notice = api_to_system_encoding($home_notice, api_detect_encoding(strip_tags($home_notice)));
                                    echo '<div class="homepage_notice">';
                                    echo $home_notice;
                                    echo '</div>';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- insert link block -->

                    <div class="panel-group" id="links-block" role="tablist" aria-multiselectable="true">
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="headingOne">
                                <h4 class="panel-title">
                                    <a role="button" data-toggle="collapse" data-parent="#links-block"
                                       href="#links-list" aria-expanded="true" aria-controls="links-list">
                                        <?php echo api_ucfirst(get_lang('MenuGeneral')); ?>
                                    </a>
                                </h4>
                            </div>
                            <div id="links-list" class="panel-collapse collapse in" role="tabpanel"
                                 aria-labelledby="headingOne">
                                <div class="panel-body">
                                    <a href="<?php echo $selfUrl; ?>?action=insert_link"><?php echo Display::return_icon('add.png', get_lang('InsertLink')).'&nbsp;'.get_lang('InsertLink'); ?>
                                    </a>
                                    <ul class="menulist">
                                        <?php
                                        $home_menu = '';
                                        if (file_exists($homep.$menuf.'_'.$lang.$ext)) {
                                            $home_menu = @file($homep.$menuf.'_'.$lang.$ext);
                                        } else {
                                            $home_menu = @file($homep.$menuf.$ext);
                                        }
                                        if (empty($home_menu)) {
                                            $home_menu = [];
                                        }
                                        if (!empty($home_menu)) {
                                            $home_menu = implode("\n", $home_menu);
                                            $home_menu = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
                                            $home_menu = explode("\n", $home_menu);
                                        }
                                        $i = 0;

                                        $editIcon = Display::return_icon('edit.png', get_lang('Edit'));
                                        $deleteIcon = Display::return_icon('delete.png', get_lang('Delete'));

                                        foreach ($home_menu as $enreg) {
                                            $enreg = trim($enreg);
                                            if (!empty($enreg)) {
                                                $edit_link = Display::url(
                                                    $editIcon,
                                                    "$selfUrl?".http_build_query(['action' => 'edit_link', 'link_index' => $i])
                                                );
                                                $delete_link = Display::url(
                                                    $deleteIcon,
                                                    "$selfUrl?".http_build_query(['action' => 'delete_link', 'link_index' => $i]),
                                                    [
                                                        'onclick' => 'javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES)).'\')) return false;',
                                                    ]
                                                );
                                                echo str_replace(
                                                    ['href="'.api_get_path(WEB_PATH).'index.php?include=', '</li>'],
                                                    [
                                                        'href="'.api_get_path(WEB_CODE_PATH).'admin/'.basename($selfUrl).'?action=open_link&link=',
                                                        $edit_link.PHP_EOL.$delete_link.PHP_EOL.'</li>',
                                                    ],
                                                    $enreg
                                                );
                                                $i++;
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-md-9">
                    <div class="actions">
                        <a href="<?php echo $selfUrl; ?>?action=edit_top&language=<?php echo $languageGet; ?>">
                            <?php echo Display::return_icon('edit.png', get_lang('EditHomePage'), null, ICON_SIZE_SMALL).'&nbsp;'.get_lang('EditHomePage'); ?>
                        </a>
                    </div>
                    <section id="homepage-home">
                        <?php
                        //print home_top contents
                        if (file_exists($homep.$topf.'_'.$lang.$ext)) {
                            $home_top_temp = @(string) file_get_contents($homep.$topf.'_'.$lang.$ext);
                        } else {
                            $home_top_temp = @(string) file_get_contents($homep.$topf.$ext);
                        }
                        $open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
                        $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
                        echo $open;
                        ?>
                    </section>

                    <?php
                    $access_url_id = 1;
                    // we only show the category options for the main chamilo installation
                    if (api_is_multiple_url_enabled()) {
                        $access_url_id = api_get_current_access_url_id();
                    }

                    if ($access_url_id == 1) {
                        echo '<div class="actions">';
                        echo '<a href="course_category.php">'.Display::return_icon('edit.png', get_lang('Edit')).'&nbsp;'.get_lang('EditCategories').'</a>';
                        echo '</div>';
                        echo '<ul class="list-group">';

                        if (count($Categories)) {
                            foreach ($Categories as $enreg) {
                                echo '<li class="list-group-item">'
                                    .Display::return_icon('folder.png', get_lang('CourseCategory')).' '.$enreg['name']
                                    .'</li>';
                            }
                            unset($Categories);
                        } else {
                            echo '<li class="list-group-item">'.get_lang('NoCategories').'</li>';
                        }

                        echo '</ul>';
                    }
                    ?>

                    <?php
                    if (file_exists($homep.$newsf.'_'.$lang.$ext)) {
                        $open = @(string) file_get_contents($homep.$newsf.'_'.$lang.$ext);
                        $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
                        echo $open;
                    } else {
                        $open = @(string) file_get_contents($homep.$newsf.$ext);
                        $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
                        echo $open;
                    }
                    ?>

                    <?php
                    // Add new page
                    $home_menu = '';
                    if (file_exists($homep.$mtloggedin.'_'.$lang.$ext)) {
                        $home_menu = @file($homep.$mtloggedin.'_'.$lang.$ext);
                    } else {
                        $home_menu = @file($homep.$mtloggedin.$ext);
                    }
                    if (empty($home_menu)) {
                        if (file_exists($homep.$menutabs.'_'.$lang.$ext)) {
                            $home_menu = @file($homep.$menutabs.'_'.$lang.$ext);
                        }
                    }
                    if (empty($home_menu)) {
                        $home_menu = [];
                    }
                    if (!empty($home_menu)) {
                        $home_menu = implode("\n", $home_menu);
                        $home_menu = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
                        $home_menu = explode("\n", $home_menu);
                    }
                    $link_list = '';
                    $tab_counter = 0;
                    foreach ($home_menu as $enreg) {
                        $enreg = trim($enreg);
                        if (!empty($enreg)) {
                            $edit_link = ' <a href="'.$selfUrl.'?action=edit_tabs&amp;link_index='.$tab_counter.'" ><span>'.Display::return_icon('edit.png', get_lang('Edit')).'</span></a>';
                            $delete_link = ' <a href="'.$selfUrl.'?action=delete_tabs&amp;link_index='.$tab_counter.'"  onclick="javascript: if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES)).'\')) return false;"><span>'.Display::return_icon('delete.png', get_lang('Delete')).'</span></a>';
                            $delete_all = ' <a href="'.$selfUrl.'?action=delete_all&amp;link_index='.$tab_counter.'"
                                            onclick="javascript: if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES)).'\'))
                                            return false;"><span>'.Display::return_icon('closed-circle.png', get_lang('DeleteInAllLanguages')).'</span></a>';
                            $tab_string = str_replace(
                                ['href="'.api_get_path(WEB_PATH).'index.php?include=', '</li>'],
                                ['href="'.api_get_path(WEB_CODE_PATH).'admin/'.basename($selfUrl).'?action=open_link&link=',
                                        $edit_link.$delete_link.$delete_all.'</li>', ],
                                $enreg
                            );
                            $tab_string = str_replace([' class="hide_menu"', ' class="show_menu"'], '', $tab_string);
                            $tab_string = str_replace(['<li>', '</li>'], '', $tab_string);
                            $link_list .= Display::tag('li', $tab_string, ['class' => 'list-group-item']);
                            $tab_counter++;
                        }
                    }
                    ?>
                    <div class="actions">
                        <a href="<?php echo $selfUrl; ?>?action=insert_tabs">
                            <?php echo Display::return_icon('add.png', get_lang('InsertLink')).'&nbsp;'.get_lang('InsertLink'); ?>
                        </a>
                    </div>
                    <?php
                    echo '<ul id="list-hiperlink" class="list-group">';
                    echo $link_list;
                    echo '</ul>';
                    ?>
                </div>
            </div>
        </section>
        <?php
        break;
}
Display::display_footer();
