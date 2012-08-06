<?php
/* For licensing terms, see /license.txt */

/**
 *	@author Patrick Cool - original version
 *	@author Denes Nagy - further improvements for learning path builder
 *	@author Roan Embrechts - refactoring to improve code organisation
 *	@package chamilo.resourcelinker
 *	@todo use the constants for the tools
 *	@todo use Database API instead of creating table names locally.
 */
/**
 * Code
 */
/* INIT SECTION */

//$language_file = 'resourcelinker';

// Flag to allow for anonymous user - needs to be set before global.inc.php.

use \ChamiloSession as Session;

$use_anonymous = true;

require_once 'back_compat.inc.php';
require_once api_get_path(SYS_CODE_PATH).'lang/english/resourcelinker.inc.php';
if (!empty($_course['language'])){
    $resource_linker_file =  api_get_path(SYS_CODE_PATH).'lang/'.$_course['language'].'/resourcelinker.inc.php';
    if (file_exists($resource_linker_file)) {
        require_once $resource_linker_file;
    }
}
require_once '../exercice/hotpotatoes.lib.php';

/* FUNCTIONS */

function unset_session_resources() {
    $_SESSION['addedresource'] = '';
    $_SESSION['addedresourceid'] = '';
    Session::erase(addedresource);
    Session::erase(addedresourceid);
}

/**
 * Insert description here.
 */
function show_folder_up() {
    global $folder;
    global $source_id, $action, $learnpath_id, $chapter_id, $originalresource;

    $level = get_levels($folder);

    if ($level == 1) {
        echo "<a href='".api_get_self()."?content=Document&amp;source_forum=".$_GET['source_forum']."&amp;source_id=$source_id&amp;action=$action&amp;learnpath_id=$learnpath_id&amp;chapter_id=$chapter_id&amp;originalresource=no'><img src='../img/folder_up.gif' border='0' />".get_lang('LevelUp')."</a>";
    }
    if ($level && $level != 0 && $level != 1) {
        $folder_up=$folder;
        $folder_temp=explode('/',$folder);
        $last=count($folder_temp)-1;
        unset($folder_temp[$last]);
        $folder_up=implode('/',$folder_temp);
        echo "<a href='".api_get_self()."?content=Document&amp;source_forum=".$_GET['source_forum']."&amp;folder=$folder_up&amp;source_id=$source_id&amp;action=$action&amp;learnpath_id=$learnpath_id&amp;chapter_id=$chapter_id&amp;originalresource=no'><img src='../img/folder_up.gif' border='0' />".get_lang('LevelUp')."</a>";
    }
}

/**
 * Shows the documents of the document tool
 * @param $folder
 */
function show_documents($folder) {
    global $_course;
    global $source_id, $action, $learnpath_id, $chapter_id, $originalresource;

    // Documents are a special case: The teacher can add an invisible document (it will be viewable by the user)
    // other tools do not have this feature.
    if (api_is_allowed_to_edit()) {
        $visibility = "ip.visibility<>'2'";
    } else {
        $visibility = "ip.visibility='1'";
    }
    
    $course_id = api_get_course_int_id();

    $item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $document_table = Database::get_course_table(TABLE_DOCUMENT);
    $sql = "SELECT * from $document_table docs , $item_property_table ip 
            WHERE   docs.c_id = $course_id AND 
                    ip.c_id = $course_id AND 
                    docs.id=ip.ref AND ip.tool = '".TOOL_DOCUMENT."' AND $visibility AND ip.to_group_id = 0 AND ip.to_user_id IS NULL  ORDER BY docs.path ASC";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        if (!$folder) {
            if (get_levels($row['path'])-1 == 1) {
                // showing the right icon.
                if (file_or_folder($row['path'])) {
                        echo '<img src="../img/file.gif" align="middle" />';
                } else {
                    $image = choose_image($row['path']);
                    echo "<img src=\"../img/$image\" align=\"middle\" />";
                }

                // Folders should be clickable.
                if (file_or_folder($row['path'])) {
                    echo "<a href='".api_get_self()."?content=Document";
                    echo "&folder=".substr($row['path'], 1)."&source_id=$source_id&source_forum=".$_GET['source_forum']."&action=$action&lp_id=$learnpath_id&parent_item_id=$chapter_id&originalresource=no'>".substr($row['path'], 1).'</a><br />';
                } else {
                    echo substr($row['path'], 1).' ';
                    echo showorhide_addresourcelink('Document',$row['id']);
                    echo '<br />';
                }
            }
        } else {
            // We calculate the level we are in by using the $folder in the url.
            // We put +1 because it does not start with an / and in the database it does
            $level = get_levels($folder) + 1;

            // We calculate each level of the database entry.
            $file_level=get_levels($row['path'])-1;
            // If the level of the database entry is equal to the level we ar in, we put it into an array
            // as this is a potential good entry.
            if ($file_level == $level) {
                $good_paths[] = $row['path'];
                $good_ids[] = $row['id'];
            }
            //$haystack=$row['path'];
            //$conform_folder=strstr($haystack, $folder);
            //if (str_replace($folder.'/', '', $conform_folder) !== $folder) {
            //	$good_folders[] = $row['path'];
                //echo str_replace($folder.'/', '', $conform_folder);
            //	echo '<br />';
            //	}// if (str_replace($folder.'/','',$conform_folder)!==$folder)
        } // else (if (!$folder))
    } //while ($row=Database::fetch_array($result))

    // This is code for the case that we are in a subfolder.
    if ($good_paths) {
        // We have all the potential good database entries, the good ones are those that start with $folder
        foreach ($good_paths as $path) {
            if (strstr($path, $folder)) {
                $good_key = key($good_paths);
                // Showing the right icon.
                if (file_or_folder($path)) {
                    echo '<img src="../img/file.gif" align="middle" />';
                } else {
                    $image = choose_image($path);
                    echo "<img src=\"../img/$image\" align=\"middle\" />";
                }

                // Folders should be clickable
                if (file_or_folder($path)) {
                    $path = substr($path, 1); // Remove the first / in folder_up.
                    $uri = str_replace($folder, $path, $_SERVER['REQUEST_URI']);
                    $newuri = str_replace('add=', 'addnot=', $uri);
                    // Using the correct name of the folder.
                    $folder_name = str_replace($folder.'/', '', $path);
                    echo "<a href='$newuri'>".$folder_name.'</a><br />';
                } else {
                    echo str_replace("/$folder/", '', $path).' ';
                    echo showorhide_addresourcelink('Document', $good_ids[$good_key]);
                    echo '<br />';
                }
            }
            next($good_paths);
        }
    }
}

/**
 * Checks wether something is a file or a folder
 * 0 means file, 1 means folder
 * @param $filefolder
 * @todo use true and false instead of 1 and 0.
 */
function file_or_folder($filefolder) {
    global $_course;
    global $baseServDir;
    $courseDir   = $_course['path'].'/document';
    $baseWorkDir = api_get_path(SYS_COURSE_PATH).$courseDir;
    return (is_dir($baseWorkDir.$filefolder) ? 1 : 0);
}

/**
 * Inserts a resource into the database
 *
 * @param $source_type
 * @param $source_id
 */
function store_resources($source_type, $source_id) {
    global $_course;
    $resource_table = Database::get_course_table(TABLE_LINKED_RESOURCES);
    $course_id = api_get_course_int_id();
    
    $addedresource = $_SESSION['addedresource'];
    $addedresourceid = $_SESSION['addedresourceid'];
    if ($_SESSION['addedresource']) {
        foreach ($addedresource as $resource_type) {
            $sql="INSERT INTO $resource_table (c_id, source_type, source_id, resource_type, resource_id) VALUES 
            ($course_id, '$source_type', '$source_id', '$resource_type', '".$addedresourceid[key($addedresource)]."')";
            Database::query($sql);
            $i=key($addedresource);
            next($addedresource);
        }
        $_SESSION['addedresource']='';
        $_SESSION['addedresourceid']='';
    }
}

/**
 * DEPRECATED - use rl_get_resource_link() instead - DEPRECATED
 * Displays the link that opens a new browser window that views the added resource.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param $type the type of the tool
 * @param $id the id of the resource
 * @param $style this is used to style the link (for instance when a resource is hidden => the added resources should also be styled like they are hidden)
 * @todo use the constants for the type definitions.
 */
function display_addedresource_link($type, $id, $style = '') {
    global $_course;
    $course_id = api_get_course_int_id();

    // Styling the link of the added resource.
    if ($style != '') {
        $styling = ' class="'.$style.'"';
    }

    switch ($type) {
        case 'Agenda':
            $TABLEAGENDA = Database::get_course_table(TABLE_AGENDA);
            $result = Database::query("SELECT * FROM $TABLEAGENDA WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            echo '<img src="../img/agenda.gif" align="middle" /> <a href="../calendar/agenda.php"'.$styling.'>'.$myrow['title']."</a><br />\n";
            break;
        case 'Ad_Valvas':
            $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
            $result = Database::query("SELECT * FROM $tbl_announcement WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            echo '<img src="../img/valves.gif" align="middle" /> <a href="../announcements/announcements.php"'.$styling.'>'.$myrow['title']."</a><br />\n";
            break;
        case 'Link':
            $TABLETOOLLINK = Database::get_course_table(TABLE_LINK);
            $result = Database::query("SELECT * FROM $TABLETOOLLINK WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            echo '<img src="../img/links.gif" align="middle" /> <a href="#" onclick="javascript:window.open(\'../link/link_goto.php?link_id='.$myrow['id'].'&amp;link_url='.urlencode($myrow['url'])."','MyWindow','width=500,height=400,top='+((screen.height-400)/2)+',left='+((screen.width-500)/2)+',scrollbars=1,resizable=1,menubar=1'); return false;\"".$styling.'>'.$myrow['title']."</a><br />\n";
            break;
        case 'Exercise':
            $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
            $result = Database::query("SELECT * FROM $TBL_EXERCICES WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            echo '<img src="../img/quiz.gif" align="middle" /> <a href="../exercice/exercise_submit.php?exerciseId='.$myrow['id'].'"'.$styling.'>'.$myrow['title']."</a><br />\n";
            break;
        case 'Forum':           
            $TBL_FORUMS = Database::get_course_table(TABLE_FORUM);
            $result = Database::query("SELECT * FROM $TBL_FORUMS WHERE c_id = $course_id AND forum_id=$id");
            $myrow = Database::fetch_array($result);
            echo '<img src="../img/forum.gif" align="middle" /> <a href="../phpbb/viewforum.php?forum='.$myrow['forum_id'].'&amp;md5='.$myrow['md5'].'"'.$styling.'>'.$myrow['forum_name']."</a><br />\n";
            break;
        case 'Thread':  //=topics
            //@deprecated bb_posts, bb_posts_text, bb_forums
            /*
            $tbl_posts		= $_course['dbNameGlu'].'bb_posts';
            $tbl_posts_text	= $_course['dbNameGlu'].'bb_posts_text';
            $TBL_FORUMS		= $_course['dbNameGlu'].'bb_forums';
            $result = Database::query("SELECT * FROM $tbl_posts posts, $TBL_FORUMS forum WHERE forum.forum_id=posts.forum_id and post_id=$id");
            $myrow = Database::fetch_array($result);
            // grabbing the title of the post
            $sql_title = "SELECT * FROM $tbl_posts_text WHERE c_id = $course_id AND post_id=".$myrow["post_id"];
            $result_title = Database::query($sql_title);
            $myrow_title = Database::fetch_array($result_title);
            echo '<img src="../img/forum.gif" align="middle" /> <a href="../phpbb/viewtopic.php?topic='.$myrow['topic_id'].'&amp;forum='.$myrow['forum_id'].'&amp;md5='.$myrow['md5'].'"'.$styling.'>'.$myrow_title['post_title']."</a><br />\n";
             */
            break;
        case 'Post':
            $tbl_post = Database::get_course_table(TABLE_FORUM_POST);
            $sql = "SELECT * FROM $tbl_post p WHERE c_id = $course_id AND post_id = $id";
            $result = Database::query($sql);
            $post = Database::fetch_object($result);
            echo '<img src="../img/forum.gif" align="middle" /> <a href="../phpbb/viewtopic.php?topic='.$post->topic_id.'&amp;forum='.$post->forum_id.'"'.$styling.'>'.$post->post_title."</a><br />\n";
            break;
        case 'Document':
            $dbTable = Database::get_course_table(TABLE_DOCUMENT);
            $result = Database::query("SELECT * FROM $dbTable WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            $pathname = explode('/',$myrow['path']); // Making a correct name for the link.
            $last = count($pathname) - 1;  // Making a correct name for the link.
            $filename = $pathname[$last];  // Making a correct name for the link.
            $image = choose_image($filename);
            $ext = explode('.', $filename);
            $ext = strtolower($ext[sizeof($ext)-1]);
            $myrow['path'] = rawurlencode($myrow['path']);
			
			$array_ext=array('htm', 'html', 'gif', 'jpg', 'jpeg', 'png');
			
			if (api_browser_support('svg')){				
				$array_ext[]='svg';
			}
			if (api_browser_support('ogg')){
				$array_ext[]='ogg';
			}
							
            $in_frames = in_array($ext, $array_ext);

            echo '<img src="../img/'.$image.'" align="middle" /> <a href="../document/'.($in_frames ? 'showinframes.php?file=' : 'download.php?doc_url=').$myrow['path'].'"'.$styling.'>'.$filename."</a><br />\n";
            break;
        case 'Externallink':
            echo '<img src="../img/links.gif" align="middle" /> <a href="'.$id.'"'.$styling.'>'.$id."</a><br />\n";
            break;
    }
}

/**
 * This function is to display the added resources (lessons) in the learning path player and builder
 * this function is a modification of display_addedresource_link($type, $id) function
 * the two ids are a bit confusing, I admit, but I did not want to change Patrick's work, I was
 * building upon it. - Denes
 *
 * Parameters:
 * @param completed   - if ="completed" then green presentation with checkbox
 * @param id_in_path  - if onclick then this lesson will be considered completed, that is the unique index in the items table
 * @param id          - that is the correspondent id in the mirror tool (like Agenda item 2)
 * @param type        - that is the correspondent type in the mirror tool (like this is a Link item)
 * @param builder     - if ="builder" then onclick shows in new window
 * @param icon        - if ="icon" then the small icon will appear
 *                      if ="wrap" then wrapped settings are used (and no icon is displayed)
 *                      if ="nolink" then only the name is returned with no href and no icon (note:only in this case, the result is not displayed, but returned)
 * @todo this function is too long, rewrite
 */
function display_addedresource_link_in_learnpath($type, $id, $completed, $id_in_path, $builder, $icon, $level = 0) {
    global $_course, $learnpath_id, $tbl_learnpath_item, $items;
    global $curDirPath, $_configuration, $enableDocumentParsing, $_course, $_user, $_cid;
    
    $course_id = api_get_course_int_id();
    

    $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
    $hyperlink_target_parameter = ''; //or e.g. 'target="_blank"'

    $length = ((($builder == 'builder') && ($icon == 'nolink')) ? 65 : 32);

    if ($builder != 'builder') $origin = 'learnpath';	//origin = learnpath in student view
    $linktype = $type;
    if (($type == 'Link _self') or ($type == 'Link _blank')) $type = 'Link';

    // YW switched litteral tool names to use of constants declared in main_api.lib.php
    switch ($type) {
        case TOOL_CALENDAR_EVENT:
        case 'Agenda':
            $TABLEAGENDA 		= Database::get_course_table(TABLE_AGENDA);
            $result = Database::query("SELECT * FROM $TABLEAGENDA WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);

            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	$row = Database::fetch_array($result);
            if ($row['title'] != '') { $myrow['title'] = $row['title']; }
            $desc = $row['description'];
            $agenda_id = $row['item_id'];
            echo str_repeat("&nbsp;&gt;", $level);
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                }	else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($myrow['title'] == '') { echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>"; return(true); }

            if ($icon == 'nolink') { return(shorten($myrow['title'], $length)); }
            if ($icon == 'icon') { echo "<img src='../img/agenda.gif' align=\"absmiddle\" alt='agenda'>"; }
            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Agenda&origin=$origin&agenda_id=$agenda_id#$id_in_path\" class='$completed'>".shorten($myrow['title'], ($length-3*$level))."</a>";
                $items[] = api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Agenda&origin=$origin&agenda_id=$agenda_id#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div></td></tr>";
                    } else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div>";
                    }
                }
            } else {
                echo "<a href=\"../calendar/agenda.php?origin=$origin&agenda_id=$agenda_id\" class='$completed' target='_blank'>".shorten($myrow['title'], ($length-3*$level))."</a>";
            }
            break;

        case TOOL_ANNOUNCEMENT:
        case 'Ad_Valvas':
            $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
            $result = Database::query("SELECT * FROM $tbl_announcement WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);

            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	
            $row = Database::fetch_array($result);
            if ($row['title'] != '') {
                 $myrow['content'] = $row['title']; 
            }
            $desc = $row['description'];
            $ann_id = $row['item_id'];
            echo str_repeat("&nbsp;&gt;", $level);

            // The title and the text are in the content field and we only want to display the title.
            list($title, $text) = split('<br>', $myrow['content']);
            if ($title == '') { $title = $myrow['content']; }
            $title = $myrow['title'];
            $text = $myrow['content'];
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                }	else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($title == '') {
                $type = 'Announcement';
                echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>";
                return(true);
            }

            if ($icon == 'nolink') { return(shorten($title,$length)); }
            if ($icon == 'icon') { echo "<img src='../img/valves.gif' align=\"absmiddle\" alt='ad valvas'>"; }
            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Ad_Valvas&origin=$origin&ann_id=$ann_id#$id_in_path\" class='$completed'>".shorten($title, ($length-3*$level))."</a>";
                $items[] = api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Ad_Valvas&origin=$origin&ann_id=$ann_id#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div></td></tr>";
                    } else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div>";
                    }
                }
            } else {
                echo "<a href=\"../announcements/announcements.php?origin=$origin&ann_id=$ann_id\" class='$completed' target='_blank'>".shorten($title, ($length-3*$level))."</a>";
            }
            break;

        case TOOL_LINK:
        case 'Link':
            $TABLETOOLLINK	= Database::get_course_table(TABLE_LINK);
            $result = Database::query("SELECT * FROM $TABLETOOLLINK WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);

            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	
            $row = Database::fetch_array($result);
            if ($row['title'] != '') { $myrow['title'] = $row['title']; }
            $desc=$row['description'];
            echo str_repeat("&nbsp;&gt;", $level);

            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                }	else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($myrow['title'] == '') {
                echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>";
                return(true);
            }

            if ($icon == 'nolink') { return(shorten($myrow['title'], $length)); }
            if ($icon == 'icon') {
                if ($linktype == 'Link _self') { echo "<img src='../img/links.gif' align=\"absmiddle\" alt='links'>"; }
                   else { echo "<img src='../img/link_blank.gif' align=\"absmiddle\" alt='blank links'>"; }
            }
            $thelink = $myrow['url'];
            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=$linktype&origin=$origin&thelink=$thelink#$id_in_path\" class='$completed'>".shorten($myrow['title'], ($length-3*$level))."</a>";
                $items[]=api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=$linktype&origin=$origin&thelink=$thelink#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div></td></tr>";
                    } else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div>";
                    }
                }
            } else {
                echo "<a href=\"$thelink\" class='$completed' target='_blank'>".shorten($myrow['title'], ($length-3*$level))."</a>";
            }
            break;

        case TOOL_QUIZ:
        case 'Exercise':
            $TBL_EXERCICES  = Database::get_course_table(TABLE_QUIZ_TEST);
            $result = Database::query("SELECT * FROM $TBL_EXERCICES WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);

            if ($builder == 'builder') { $origin = 'builder'; }
              // This is needed for the exercise_submit.php can delete the session info about tests.

            $sql = "select * from $tbl_lp_item where id=$id_in_path";
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            if ($row['title'] != '') { $myrow['title'] = $row['title']; }
            $desc = $row['description'];
            echo str_repeat("&nbsp;&gt;", $level);

            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                } else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($myrow['title'] == '') {
                echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>";
                return(true);
            }

            if ($icon == 'nolink') { return(shorten($myrow['title'], $length)); }
            if ($icon == 'icon') { echo "<img src='../img/quiz.gif' align=\"absmiddle\" alt='quizz'>"; }
            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Exercise&origin=$origin&exerciseId=".$myrow["id"]."#$id_in_path\" class='$completed'>".shorten($myrow['title'], ($length-3*$level))."</a>";
                $items[] = api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Exercise&origin=$origin&exerciseId=".$myrow["id"]."#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div></td></tr>";
                    } else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div>";
                    }
                }
            } else {
                echo "<a href=\"../exercice/exercise_submit.php?origin=$origin&exerciseId=".$myrow['id']."\" class='$completed' target='_blank'>".shorten($myrow['title'], ($length-3*$level))."</a>";
            }
            break;

        case 'hotpotatoes':
        case 'HotPotatoes':
            $TBL_DOCUMENT  = Database::get_course_table(TABLE_DOCUMENT);
            $documentPath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
            $result = Database::query("SELECT * FROM ".$TBL_DOCUMENT." WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            $path = $myrow['path'];
            $name = GetQuizName($path, $documentPath);

            if ($builder == 'builder') { $origin='builder'; }
              // This is needed for the exercise_submit.php can delete the session info about tests.

            $sql = "select * from $tbl_lp_item where id=$id_in_path";
            $result = Database::query($sql);	$row = Database::fetch_array($result);
            if ($row['title'] != '') { $name=$row['title']; }
            $desc = $row['description'];
            echo str_repeat("&nbsp;&gt;", $level);

            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                }	else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($name=='') { echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>"; return(true); }

            if ($icon == 'nolink') { return(shorten($name,$length)); }
            if ($icon == 'icon') { echo "<img src='../img/jqz.jpg' align=\"absmiddle\" alt='hot potatoes'>"; }

            $cid = $_course['official_code'];

            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=HotPotatoes&origin=$origin&id=$id#$id_in_path\" class='$completed'>".shorten($name,($length-3*$level))."</a>";
                $items[] = api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=HotPotatoes&origin=$origin&id=$id#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div></td></tr>";
                    } else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div>";
                    }
                }
            } else {
                echo "&nbsp;<a href=\"../exercice/showinframes.php?file=$path&cid=$cid&uid=".$_user['user_id']."\" class='$completed' target='_blank'>".shorten($name,($length-3*$level))."</a>";
            }
            break;

        case TOOL_FORUM:
        case 'Forum':
            $TBL_FORUMS = Database::get_course_table(TABLE_FORUM);
            $result = Database::query("SELECT * FROM $TBL_FORUMS WHERE c_id = $course_id AND forum_id=$id");
            $myrow = Database::fetch_array($result);

            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	$row = Database::fetch_array($result);
            if ($row['title'] != '') { $myrow["forum_name"] = $row['title']; }
            $desc = $row['description'];
            echo str_repeat("&nbsp;&gt;", $level);

            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                }	else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($myrow["forum_name"]=='') { $type="Forum"; echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>"; return(true); }

            if ($icon == 'nolink') { return(shorten($myrow['forum_name'], $length)); }
            if ($icon == 'icon') { echo "<img src='../img/forum.gif' align=\"absmiddle\" alt='forum'>"; }
            $forumparameters = "forum=".$myrow["forum_id"]."&md5=".$myrow["md5"];
            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Forum&origin=$origin&forumparameters=$forumparameters#$id_in_path\" class='$completed'>".shorten($myrow["forum_name"],($length-3*$level))."</a>";
                $items[] = api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Forum&origin=$origin&forumparameters=$forumparameters#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc,($length-3*$level))."</div></td></tr>";
                    } else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc,($length-3*$level))."</div>";
                    }
                }
            } else {
                echo "<a href=\"../phpbb/viewforum.php?$forumparameters\" class='$completed' target='_blank'>".shorten($myrow["forum_name"],($length-3*$level))."</a>";
            }
            break;

        case TOOL_THREAD:
        case 'Thread':  //forum post
            $tbl_topics = Database::get_course_table(TABLE_FORUM_THREAD);
            $sql = "SELECT * FROM $tbl_topics where c_id = $course_id AND topic_id=$id";
            $result = Database::query($sql);
            $myrow = Database::fetch_array($result);

            $sql = "select * from $tbl_lp_item where id=$id_in_path";
            $result = Database::query($sql);	$row = Database::fetch_array($result);
            if ($row['title'] != '') { $myrow['topic_title'] = $row['title']; }
            $desc = $row['description'];
            echo str_repeat("&nbsp;&gt;", $level);

            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                }	else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($myrow["topic_title"] == '') { $type = "Forum Post"; echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>"; return(true); }

            if ($icon == 'nolink') { return(shorten($myrow['topic_title'], $length)); }
            if ($icon == 'icon') { echo "<img src='../img/forum.gif' align=\"absmiddle\" alt='forum'>"; }
            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Thread&origin=$origin&topic=".$myrow["topic_id"]."&forum=".$myrow["forum_id"]."&md5=".$myrow["md5"]."#$id_in_path\" class='$completed'>".shorten($myrow["topic_title"], ($length-3*$level))."</a>";
                $items[] = api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Thread&origin=$origin&topic=".$myrow["topic_id"]."&forum=".$myrow["forum_id"]."&md5=".$myrow["md5"]."#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc,($length-3*$level))."</div></td></tr>";
                    } else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc,($length-3*$level))."</div>";
                    }
                }
            } else {
                echo "<a href=\"../phpbb/viewtopic.php?topic=".$myrow["topic_id"]."&forum=".$myrow["forum_id"]."&md5=".$myrow["md5"]."\" class='$completed' target='_blank'>".shorten($myrow["topic_title"],($length-3*$level))."</a>";
            }
            break;

        case TOOL_POST:
        case 'Post':
        //deprecated
            $tbl_posts = Database::get_course_table(TABLE_FORUM_POST);
            $TBL_FORUMS = Database::get_course_table(TABLE_FORUM);
            $result = Database::query("SELECT * FROM $tbl_posts where c_id = $course_id AND post_id=$id");
            $myrow = Database::fetch_array($result);
            // Grabbing the title of the post.
            
            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	
            $row=Database::fetch_array($result);
            if ($row['title'] != '') { $myrow['post_title'] = $row['title']; }
            $desc = $row['description'];
            echo str_repeat("&nbsp;&gt;", $level);

            $posternom = $myrow['nom'];				$posterprenom = $myrow['prenom'];
            $posttime = $myrow['post_time'];			$posttext = $myrow['post_text'];
            $posttitle = $myrow['post_title'];
            $posttext = str_replace('"', "'", $posttext);

            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                }	else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($myrow["post_title"] == '') {
                $type = 'Forum';
                echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>"; return(true);
            }

            if ($icon == 'nolink') { return(shorten($myrow["post_title"],$length)); }
            if ($icon == 'icon') { echo "<img src='../img/forum.gif' align=\"absmiddle\" alt='forum'>"; }
            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".intval($_GET['source_forum'])."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Post&origin=$origin&posternom=$posternom&posterprenom=$posterprenom&posttime=$posttime&posttext=$posttext&posttitle=$posttitle#$id_in_path\" class='$completed'>".shorten($myrow["post_title"],($length-3*$level))."</a>"; $items[]=api_get_self()."?action=closelesson&source_forum=".intval($_GET['source_forum'])."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Post&origin=$origin&posternom=$posternom&posterprenom=$posterprenom&posttime=$posttime&posttext=$posttext&posttitle=$posttitle#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc,($length-3*$level))."</div></td></tr>";
                    } else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc,($length-3*$level))."</div>";
                    }
                }
            } else {
                echo "<a href=\"../phpbb/viewtopic.php?topic=".$myrow["topic_id"]."&forum=".$myrow["forum_id"]."&md5=".$myrow["md5"]."\" class='$completed' target='_blank'>".shorten($myrow["post_title"],($length-3*$level))."</a>";
            }
            break;

        case TOOL_DOCUMENT:
        case 'Document':
            $dbTable = Database::get_course_table(TABLE_DOCUMENT);
            $my_sql = "SELECT * FROM $dbTable WHERE c_id = $course_id AND id=$id";
            //error_log('New LP - Querying document table: '.$my_sql,0);
            $result = Database::query($my_sql);
            $myrow = Database::fetch_array($result);

            $pathname = explode('/', $myrow['path']); // Making a correct name for the link.
            $last = count($pathname) - 1;  // Making a correct name for the link.
            $filename=$pathname[$last];  // Making a correct name for the link.
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }

            echo str_repeat("&nbsp;&gt;", $level);

            if ($icon != 'nolink') {
                if ($completed=='completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                } else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }
            $image = choose_image($filename);

            $sql = "select * from $tbl_lp_item where id=$id_in_path";
            //error_log('New LP - Querying lp_item table: '.$sql, 0);
            $result = Database::query($sql);	$row = Database::fetch_array($result);
            if ($row['title'] != '') { $filename = $row['title']; }
            $desc=$row['description'];

            if (($myrow['path'] == '') && ($filename == '')) {
                echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>";
                return(true);
            }

            if ($icon == 'nolink') { return(shorten($filename, $length)); }
            if ($icon == 'icon') { echo "<img src='../img/$image' align=\"absmiddle\" alt='$image'>"; }
            if ($builder != 'builder')
            {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Document&origin=$origin&docurl=".$myrow["path"]."#$id_in_path\" class='$completed'>".shorten($filename,($length-3*$level))."</a>";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc,($length-3*$level))."</div></td></tr>";
                    } else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc,($length-3*$level))."</div>";
                    }
                } $items[] = api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Document&origin=$origin&docurl=".$myrow["path"]."#$id_in_path";
            } else {
                $enableDocumentParsing=yes;
                if (!$enableDocumentParsing) {
                    // This is the solution for the non-parsing version in the builder.
                    $file = urlencode($myrow['path']);
                    echo "<a href='../document/showinframes.php?file=$file' class='$completed' $hyperlink_target_parameter>".shorten($filename,($length-3*$level))."</a>";
                } else {
                    echo "<a href=\"../document/download.php?doc_url=".$myrow['path']."\" class='$completed' $hyperlink_target_parameter>".shorten($filename,($length-3*$level))."</a>";
                }
            }
            break;

        case 'assignments':
        case 'Assignments':
            $name = get_lang('Assignments');
            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            if ($row['title'] != '') { $name=$row['title']; }
            $desc = $row['description'];
            echo str_repeat("&nbsp;&gt;", $level);

            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                }	else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($name=='') {
                echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>"; return(true);
            }

            if ($icon == 'nolink') { return(shorten($name, $length)); }
            if ($icon == 'icon') { echo "<img src='../img/works.gif' align=\"absmiddle\">"; }
            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Assignments&origin=$origin#$id_in_path\" class='$completed'>".shorten($name,($length-3*$level))."</a>"; $items[]=api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Assignments&origin=$origin#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div></td></tr>";
                    } else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div>";
                    }
                }
            } else {
                echo "<a href=\"../work/work.php\" class='$completed' target='_blank'>".shorten($name, ($length-3*$level))."</a>";
            }
            break;

        case TOOL_DROPBOX:
        case 'Dropbox':
            $name = get_lang('Dropbox');
            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            if ($row['title'] != '') { $name = $row['title']; }
            $desc = $row['description'];
            echo str_repeat("&nbsp;&gt;", $level);

            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                }	else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($name == '') { echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>"; return(true); }

            if ($icon == 'nolink') { return(shorten($name, $length)); }
            if ($icon == 'icon') { echo "<img src='../img/dropbox.gif' align=\"absmiddle\">"; }

            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Dropbox&origin=$origin#$id_in_path\" class='$completed'>".shorten($name,($length-3*$level))."</a>"; $items[]=api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Dropbox&origin=$origin#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div></td></tr>"; }
                    else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div>"; }
                }
            } else {
                echo "<a href=\"../dropbox/index.php\" class='$completed' target='_blank'>".shorten($name, ($length-3*$level))."</a>";
            }
            break;

        case 'introduction_text':
        case 'Introduction_text':
            $name = get_lang('IntroductionText');
            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	$row = Database::fetch_array($result);
            if ($row['title'] != '') { $name = $row['title']; }
            $desc = $row['description'];
            echo str_repeat("&nbsp;&gt;", $level);

            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                } else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($name == '') { echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>"; return(true); }

            if ($icon == 'nolink') { return(shorten($name, $length)); }
            if ($icon == 'icon') { echo "<img src='../img/introduction.gif' align=\"absmiddle\" alt='introduction'>"; }

            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Introduction_text&origin=$origin#$id_in_path\" class='$completed'>".shorten($name,($length-3*$level))."</a>";
                $items[] = api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Introduction_text&origin=$origin#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div></td></tr>"; }
                    else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div>"; }
                }
            } else {
                $s = api_get_path(WEB_COURSE_PATH)."$_cid/index.php?intro_cmdEdit=1";
                echo "<a href=\"$s\" class='$completed' target='_blank'>".shorten($name, ($length-3*$level))."</a>";
            }
            break;

        case TOOL_COURSE_DESCRIPTION:
        case 'Course_description':
            $name = get_lang('CourseDescription');
            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	$row = Database::fetch_array($result);
            if ($row['title'] != '') { $name = $row['title']; }
            $desc = $row['description'];
            echo str_repeat("&nbsp;&gt;", $level);

            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                }	else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($name == '') { echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>"; return(true); }

            if ($icon == 'nolink') { return(shorten($name,$length)); }
            if ($icon == 'icon') { echo "<img src='../img/info.gif' align=\"absmiddle\" alt='info'>"; }

            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Course_description&origin=$origin#$id_in_path\" class='$completed'>".shorten($name,($length-3*$level))."</a>"; $items[]=api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Course_description&origin=$origin#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div></td></tr>"; }
                    else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div>"; }
                }
            } else {
                $s=api_get_path(WEB_CODE_PATH)."course_description";
                echo "<a href=\"$s\" class='$completed' target='_blank'>".shorten($name, ($length-3*$level))."</a>";
            }
            break;

        case TOOL_GROUP:
        case 'Groups':
            $name = get_lang('Groups');
            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	
            $row = Database::fetch_array($result);
            if ($row['title'] != '') { $name=$row['title']; }
            $desc = $row['description'];
            echo str_repeat("&nbsp;&gt;", $level);

            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                } else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($name=='') { echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>"; return(true); }

            if ($icon == 'nolink') { return(shorten($name, $length)); }
            if ($icon == 'icon') { echo "<img src='../img/group.gif' align=\"absmiddle\" alt='group'>"; }

            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Groups&origin=$origin#$id_in_path\" class='$completed'>".shorten($name,($length-3*$level))."</a>"; $items[]=api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Groups&origin=$origin#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div></td></tr>"; }
                    else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div>"; }
                }
            } else {
                echo "<a href=\"../group/group.php?origin=$origin\" class='$completed' target='_blank'>".shorten($name, ($length-3*$level))."</a>";
            }
            break;

        case TOOL_USER:
        case 'Users':
            $name = get_lang('Users');
            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	
            $row = Database::fetch_array($result);
            if ($row['title'] != '') { $name = $row['title']; }
            $desc = $row['description'];
            echo str_repeat("&nbsp;&gt;", $level);

            if (($builder != 'builder') && ($icon != 'wrap')) { echo "<td>"; }
            if ($icon != 'nolink') {
                if ($completed == 'completed') {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on'>";
                } else {
                    echo "<img src='../img/checkbox_on2.gif' border='0' width='13' height='11' alt='on' style='visibility: hidden'>";
                    //echo "&nbsp;";
                }
            }
            if (($builder != 'builder') && ($icon != 'wrap')) { echo "</td><td>"; }

            if ($name=='') { echo "<span class='messagesmall'>".get_lang('StepDeleted1')." $type ".get_lang('StepDeleted2')."</span>"; return(true); }

            if ($icon == 'nolink') { return(shorten($name,$length)); }
            if ($icon == 'icon') { echo "<img src='../img/members.gif' align=\"absmiddle\" alt='members'>"; }

            if ($builder != 'builder') {
                echo "<a href=\"".api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Users&origin=$origin#$id_in_path\" class='$completed'>".shorten($name,($length-3*$level))."</a>"; $items[]=api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Users&origin=$origin#$id_in_path";
                if ($desc != '') {
                    if ($icon != 'wrap') {
                        echo "</tr><tr><td></td><td></td><td><div class='description'>&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div></td></tr>"; }
                    else {
                        echo "<div class='description'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".shorten($desc, ($length-3*$level))."</div>"; }
                }
            } else {
                echo "<a href=\"../user/user.php?origin=$origin\" class='$completed' target='_blank'>".shorten($name, ($length-3*$level))."</a>";
            }
            break;
    }//end huge switch-statement
}

/**
 * This function is to create and return a link to the added resources (lessons).
 * It returns the same thing as display_addedresource_link_in_learnpath() but doesn't display
 * anything.
 *
 * Parameters:
 * @param type        - that is the correspondent type in the mirror tool (like this is a Link item)
 * @param id          - that is the correspondent id in the mirror tool (like Agenda item 2)
 * @param id_in_path  - the unique index in the items table
 */
function get_addedresource_link_in_learnpath($type, $id, $id_in_path) {
    global $_course, $learnpath_id, $tbl_learnpath_item, $items;
    global $curDirPath, $_configuration, $enableDocumentParsing, $_user, $_cid;

    $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
    $course_id = api_get_course_int_id();
    $hyperlink_target_parameter = ''; // or e.g. target='_blank'
    $builder = 'player';
    $origin = 'learnpath';

    $linktype = $type;
    if (($type == 'Link _self') || ($type == 'Link _blank')) { $type = 'Link'; }

    $link = '';

    switch ($type) {
        case 'Agenda':
            $TABLEAGENDA = Database::get_course_table(TABLE_AGENDA);
            $result = Database::query("SELECT * FROM $TABLEAGENDA WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);

            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	$row = Database::fetch_array($result);
            if ($row['title'] != '') { $myrow['title'] = $row['title']; }
            $desc = $row['description'];
            $agenda_id = $row['item_id'];

            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Agenda&origin=$origin&agenda_id=$agenda_id#$id_in_path";
            } else {
                $link .= "../calendar/agenda.php?origin=$origin&agenda_id=$agenda_id";
            }
            break;

        case 'Ad_Valvas':
            $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
            $result = Database::query("SELECT * FROM $tbl_announcement WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);

            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Ad_Valvas&origin=$origin&ann_id=$id#$id_in_path";
            } else {
                $link .= "../announcements/announcements.php?origin=$origin&ann_id=$id";
            }
            break;

        case 'Link':
            $TABLETOOLLINK	= Database::get_course_table(TABLE_LINK);
            $result = Database::query("SELECT * FROM $TABLETOOLLINK WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);

            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	
            $row = Database::fetch_array($result);

            $thelink = $myrow['url'];
            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=$linktype&origin=$origin&thelink=$thelink#$id_in_path";
            } else {
                $link .= $thelink;
            }
            break;

        case 'Exercise':
            $TBL_EXERCICES  = Database::get_course_table(TABLE_QUIZ_TEST);
            $result = Database::query("SELECT * FROM $TBL_EXERCICES WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);

            if ($builder == 'builder') { $origin = 'builder'; }
              // This is needed for the exercise_submit.php can delete the session info about tests.

            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	$row = Database::fetch_array($result);
            if ($row['title'] != '') { $myrow['title'] = $row['title']; }

            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Exercise&origin=$origin&exerciseId=".$myrow["id"]."#$id_in_path";
            } else {
                $link .= "../exercice/exercise_submit.php?origin=$origin&exerciseId=".$myrow["id"];
            }
            break;

        case 'HotPotatoes':
              $TBL_DOCUMENT  = Database::get_course_table(TABLE_DOCUMENT);
            $documentPath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
            $result = Database::query("SELECT * FROM ".$TBL_DOCUMENT." WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            $path = $myrow['path'];
              $name = GetQuizName($path, $documentPath);

            if ($builder=='builder') { $origin='builder'; }

            $cid = $_course['official_code'];

            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=HotPotatoes&origin=$origin&id=$id#$id_in_path";
            } else {
                $link .= "../exercice/showinframes.php?file=$path&cid=$cid&uid=".$_user['user_id'];
            }
            break;

        case 'Forum':
        //deprecated
            $TBL_FORUMS = Database::get_course_table(TABLE_FORUM);  // TODO: This is the old table name, it should be corrected.
            $result = Database::query("SELECT * FROM $TBL_FORUMS WHERE c_id = $course_id AND forum_id=$id");
            $myrow = Database::fetch_array($result);

            if ($builder == 'builder') { $origin = 'builder'; }

            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	$row = Database::fetch_array($result);
            if ($row['title'] != '') { $myrow['forum_name'] = $row['title']; }

            if ($myrow['forum_name'] == '') { $type = 'Forum'; }

            $forumparameters = "forum=".$myrow["forum_id"]."&md5=".$myrow["md5"];
            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Forum&origin=$origin&forumparameters=$forumparameters#$id_in_path";
            } else {
                $link .= "../phpbb/viewforum.php?$forumparameters";
            }
            break;

        case 'Thread':  //forum post
        //deprecated
            $tbl_topics = Database::get_course_table(TABLE_FORUM_THREAD);
            $tbl_posts = Database::get_course_table(TABLE_FORUM_POST);
            $TBL_FORUMS = Database::get_course_table(TABLE_FORUM);
            $sql = "SELECT * FROM $tbl_topics where c_id = $course_id AND topic_id=$id";
            $result = Database::query($sql);
            $myrow = Database::fetch_array($result);

            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	$row = Database::fetch_array($result);

            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Thread&origin=$origin&topic=".$myrow["topic_id"]."&forum=".$myrow["forum_id"]."&md5=".$myrow["md5"]."#$id_in_path";
            } else {
                $link .= "../forum/viewtopic.php?topic=".$myrow["topic_id"]."&forum=".$myrow["forum_id"]."&md5=".$myrow["md5"];
            }
            break;

        case 'Post':
        //deprecated
            $tbl_posts = Database::get_course_table(TABLE_FORUM_POST);
            
            $result = Database::query("SELECT * FROM $tbl_posts where c_id = $course_id AND post_id=$id");
            $myrow = Database::fetch_array($result);
            // Grabbing the title of the post.

            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	
            $row = Database::fetch_array($result);
            if ($row['title'] != '') { $myrow['post_title'] = $row['title']; }
            $desc = $row['description'];
            //$link .= str_repeat("&nbsp;&gt;", $level);

            $posternom = $myrow['nom'];				$posterprenom = $myrow['prenom'];
            $posttime = $myrow['post_time'];		$posttext = $myrow['post_text'];
            $posttitle = $myrow['post_title'];
            $posttext = str_replace('"', "'", $posttext);

            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Post&origin=$origin&posternom=$posternom&posterprenom=$posterprenom&posttime=$posttime&posttext=$posttext&posttitle=$posttitle#$id_in_path";
            } else {
                $link .= "../phpbb/viewtopic.php?topic=".$myrow["topic_id"]."&forum=".$myrow["forum_id"]."&md5=".$myrow["md5"];
            }
            break;

        case 'Document':
            $dbTable = Database::get_course_table(TABLE_DOCUMENT);
            $result = Database::query("SELECT * FROM $dbTable WHERE id=$id");
            $myrow = Database::fetch_array($result);

            $pathname = explode('/', $myrow['path']); // Making a correct name for the link.
            $last = count($pathname) - 1;  // Making a correct name for the link.
            $filename = $pathname[$last];  // Making a correct name for the link.

            $sql = "select * from $tbl_lp_item where c_id = $course_id AND id=$id_in_path";
            $result = Database::query($sql);	
            $row = Database::fetch_array($result);

            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Document&origin=$origin&docurl=".$myrow["path"]."#$id_in_path";
            } else {
                $enableDocumentParsing = yes;
                if (!$enableDocumentParsing) {
                    // This is the solution for the non-parsing version in the builder.
                    $file = urlencode($myrow['path']);
                    $link .= "../document/showinframes.php?file=$file";
                } else {
                    $link .= "../document/download.php?doc_url=".$myrow['path'];
                }
            }
            break;

        case 'Assignments':
            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Assignments&origin=$origin#$id_in_path";
            } else {
                $link .= "../work/work.php";
            }
            break;
        case 'Dropbox':
            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Dropbox&origin=$origin#$id_in_path";
            } else {
                $link .= "../dropbox/index.php";
            }
            break;
        case 'Introduction_text':
            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Introduction_text&origin=$origin#$id_in_path";
            } else {
                $s = api_get_path(WEB_COURSE_PATH)."$_cid/index.php?intro_cmdEdit=1";
                $link .= $s;
            }
            break;
        case 'Course_description':
            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Course_description&origin=$origin#$id_in_path";
            } else {
                $s = api_get_path(WEB_CODE_PATH).'course_description';
                $link .= $s;
            }
            break;
        case 'Groups':

            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Groups&origin=$origin#$id_in_path";
            } else {
                $link .= "../group/group.php?origin=$origin";
            }
            break;
        case 'Users':
            if ($builder != 'builder') {
                $link .= api_get_self()."?action=closelesson&source_forum=".$_GET['source_forum']."&how=complete&id_in_path=$id_in_path&learnpath_id=$learnpath_id&type=Users&origin=$origin#$id_in_path";
            } else {
                $link .= "../user/user.php?origin=$origin";
            }
            break;
    }//end huge switch-statement
    return $link;
}

/**
 * This function is to remove an resource item from the array
 */
function remove_resource($resource_key) {
    $addedresource = $_SESSION['addedresource'];
    $addedresourceid = $_SESSION['addedresourceid'];
    unset($addedresource[$resource_key]);
    unset($addedresourceid[$resource_key]);
    $_SESSION['addedresource'] = $addedresource;
    $_SESSION['addedresourceid'] = $addedresourceid ;
}

/**
 * This function is to show the button "click to add resource" on the tool page
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function show_addresource_button($additionalparameters = '') {
    global $charset;
    echo '<label for="addresources"><img src="../img/attachment.gif" /></label><input class="link_alike" type="submit" name="addresources" id="addresources" value="'.api_htmlentities(get_lang('Attachment'), ENT_QUOTES, $charset).'" '.$additionalparameters.' />';
}

/**
 * This function is to delete ONE specific resource that were added to a specific item
 * Deprecated
 *//*
function delete_one_added_resource($source_type, $source_id, $resource_type, $resource_id) {
    //echo "delete_one_added_resource";
    global $_course;
    $TABLERESOURCE = $_course['dbNameGlu'].'resource';
    $sql = "DELETE FROM $TABLERESOURCE WHERE source_type='$source_type' and source_id='$source_id' and resource_type='$resource_type' and resource_id='$resource_id'";
    Database::query($sql);
}*/

/**
 * This function is to delete the resources that were added to a specific item
 */
function delete_added_resource($type, $id) {
    global $_course;
    $course_id = api_get_course_int_id();
    $TABLERESOURCE = Database::get_course_table(TABLE_LINKED_RESOURCES);
    $sql = "DELETE FROM $TABLERESOURCE WHERE c_id = $course_id AND source_type='$type' and source_id='$id'";
    Database::query($sql);
}

/**
 * This function is te delete all resources of a specific type (only used in announcements -- delete all)
 * Author : Frederik Vermeire <frederik.vermeire@pandora.be>
 */
function delete_all_resources_type($type) {
  global $_course;
  $course_id = api_get_course_int_id();
  $TABLERESOURCE = Database::get_course_table(TABLE_LINKED_RESOURCES);
  $sql = "DELETE FROM $TABLERESOURCE WHERE c_id = $course_id AND source_type='$type'";
  Database::query($sql);
}

/**
 * This function checks wether there are added resources or not
 */
function check_added_resources($type, $id) {
    global $_course, $origin;
    $course_id = api_get_course_int_id();
    $TABLERESOURCE = Database::get_course_table(TABLE_LINKED_RESOURCES);
    $sql = "SELECT * FROM $TABLERESOURCE WHERE c_id = $course_id AND source_type='$type' and source_id='$id'";
    $result = Database::query($sql);
    $number_added = Database::num_rows($result);
    if ($number_added != 0)
        return true;
    else
        return false;
}

/**
 * this function is to load the resources that were added to a specific item
 * into the session variables
 */
function edit_added_resources($type, $id) {
    global $_course;
    $course_id = api_get_course_int_id();
    $TABLERESOURCE = Database::get_course_table(TABLE_LINKED_RESOURCES);

    $sql="SELECT * FROM $TABLERESOURCE WHERE c_id = $course_id AND source_type='$type' and source_id=$id";
    $result=Database::query($sql);
    while ($row=Database::fetch_array($result))
    {
        $addedresource[]=$row["resource_type"];
        $addedresourceid[]=$row["resource_id"];
    }
    $_SESSION['addedresource']=$addedresource;
    $_SESSION['addedresourceid']=$addedresourceid;
}

/**
 * this function is store the modified resources
 * first we delete all the added resources in the database,
 * then we add all the resources from the session object.
 */
function update_added_resources($type, $id) {
    global $_course;
    $course_id = api_get_course_int_id();
    $TABLERESOURCE = Database::get_course_table(TABLE_LINKED_RESOURCES);
    // delete all the added resources for this item in the database;
    $sql="DELETE FROM $TABLERESOURCE WHERE c_id = $course_id AND source_type='$type' AND source_id='$id'";
    //echo $sql;
    Database::query($sql);

    // Store the resources from the session into the database.
    store_resources($type, $id);

    //delete_added_resource_($type, $id);
    unset_session_resources();
}

/**
 * this function is to display the resources that were added to a specific item
 */
function display_added_resources($type, $id, $style = '') {
    $course_id = api_get_course_int_id();
    // The array containing the icons
    $arr_icons = array('Agenda'=>'../img/agenda.gif', 'Ad Valvas'=>'../img/valves.gif', 'Link'=>'../img/links.gif', 'Exercise'=>'../img/quiz.gif' );

    global $_course, $origin;
    $TABLERESOURCE = Database::get_course_table(TABLE_LINKED_RESOURCES);

    $sql = "SELECT * FROM $TABLERESOURCE WHERE c_id = $course_id AND source_type='$type' and source_id='$id'";
    $result = Database::query($sql);
    while ($row=Database::fetch_array($result)) {
        if ($origin != 'learnpath') {
            display_addedresource_link($row['resource_type'], $row['resource_id'], $style) ;
        } else {
            display_addedresource_link_in_learnpath($row['resource_type'], $row['resource_id'],'agendaitems','','builder','icon') ; echo "<br>";
        }
    }
}

/**
 * This function is to show the added resources when adding an item
 * $showdeleteimg determine if the delete image should appear or not.
 * deleting an added resource is only possible through the resource linker file itself
 */
function display_resources($showdeleteimg) {
    global $action;
    global $resourceaction;
    global $id;
    global $locationkey;
    global $source_id, $action, $learnpath_id, $chapter_id, $originalresource;

    if ($resourceaction == 'removeresource') {
        remove_resource($locationkey);
    }
    $addedresource = $_SESSION['addedresource'];
    $addedresourceid = $_SESSION['addedresourceid'];
    if (is_array($addedresource)) {
        echo '<table>';
        foreach ($addedresource as $resource) {
            echo '<tr><td>';
            display_addedresource_link($resource, $addedresourceid[key($addedresource)]);
            echo '</td><td width="30">';

            // if $_SERVER['REQUEST_URI'] contains and ?id=xx we have an edit and the url for deleting a session added resource
            // should also contain this id.
            $test = parse_url($_SERVER['REQUEST_URI']);
            $output = array();
            parse_str($test[query], $output);

            if ($showdeleteimg == 1) {
                echo "<a href=".api_get_self()."?showresources=true&amp;source_forum=".$_GET['source_forum']."&amp;resourceaction=removeresource&amp;locationkey=".key($addedresource)."&amp;source_id=$source_id&amp;action=$action&amp;learnpath_id=$learnpath_id&amp;chapter_id=$chapter_id&amp;originalresource=no><img src='../img/delete.gif' border='0' alt='resource ".get_lang('Delete')."' /></a><br />";
            }
            echo '</td></tr>';
            next($addedresource);
        }
        echo '</table>';
    }
    else { // it is a string
        echo '';
    }
} // end of the display_resources function


/**
* This function checks wether the link add resource should be displayed next the item in the linker page
* So we have to check if the specific id of that tool is already in the array of the added resources
* if it is already in, the link should not be showed since it would make it possible to add
* the same resource a second time (=duplication of added resources)
*/
function showorhide_addresourcelink($type, $id) {
    global $from_learnpath, $source_id, $action, $learnpath_id, $chapter_id, $originalresource, $folder, $content, $target;
    $addedresource = $_SESSION['addedresource'];
    $addedresourceid = $_SESSION['addedresourceid'];

    if (is_array($_SESSION['addedresource'])) {
        foreach ($addedresource as $toolcompare) {
            if ($toolcompare==$type && $addedresourceid[key($addedresource)] == $id) {
                $show = 0;
            }
            next($addedresource);
        }
        if ($from_learnpath) { $lang_add_it_or_resource = get_lang('AddIt'); } else { $lang_add_it_or_resource = get_lang('AddResource'); }
        if ($show !== 0) {
            if ($type == 'Document') {
                echo "<a href=".api_get_self()."?content=".$type."&folder=".$folder."&source_forum=".$_GET['source_forum']."&add=".$id."&source_id=$source_id&action=$action&lp_id=$learnpath_id&parent_item_id=$chapter_id&originalresource=no>".$lang_add_it_or_resource."</a>";
            } else {
                echo "<a href='".api_get_self()."?content=".$type."&source_forum=".$_GET['source_forum']."&add=".$id."&source_id=$source_id&action=$action&lp_id=$learnpath_id&parent_item_id=$chapter_id&originalresource=no&target=$target'>".$lang_add_it_or_resource."</a>";
            }
        }
    }
    else { // if it is not an array, it is a string
        if ($_SESSION['addedresource'] !== $type || $_SESSION['addedresourceid'] !== $id) {
            if ($from_learnpath) { $lang_add_it_or_resource = get_lang('AddIt'); } else { $lang_add_it_or_resource = get_lang('AddResource'); }
            echo "<a href='".api_get_self()."?content=".$type."&folder=".$folder."&source_forum=".$_GET['source_forum']."&add=".$id."&source_id=$source_id&action=$action&lp_id=$learnpath_id&parent_item_id=$chapter_id&originalresource=no&target=$target'>".$lang_add_it_or_resource."</a>";
        }
    }
}

/**
 * Gets the link to the given added resource.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Yannick Warnier <ywarnier@beeznest.org>, Dokeos - rebranding
 * @param string 	Course code
 * @param string 	The tool type (using constants declared in main_api.lib.php)
 * @param integer 	The resource ID
 * @param string	Resource style (e.g. when a resource is hidden => the added resources should also be styled like they are hidden)
 * @param boolean	Open in a new window (true) or in the current frame/window (false)?
 * @todo use the constants for the type definitions.
 */
function rl_get_html_resource_link($course_code, $type, $id, $style='', $new_window = true) {
    $_course = Database::get_course_info($course_code);
    
    $course_id = api_get_course_int_id();

    // Styling the link of the added resource
    if ($style != '') $styling = ' class="'.$style.'"';
    if ($new_window) { $target = ' target = "_blank" '; } else { $target = ' target = "_self" '; }

    $output = '';

    switch ($type) {
        case TOOL_CALENDAR_EVENT:
            $TABLEAGENDA = Database::get_course_table(TABLE_AGENDA);
            $result = Database::query("SELECT * FROM $TABLEAGENDA WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            $output = '<img src="../img/agenda.gif" align="middle" /> <a href="../calendar/agenda.php"'.$styling.' '.$target.'>'.$myrow['title']."</a><br />\n";
            break;
        case TOOL_ANNOUNCEMENT:
            $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
            $result = Database::query("SELECT * FROM $tbl_announcement WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            $output = '<img src="../img/valves.gif" align="middle" /> <a href="../announcements/announcements.php"'.$styling.' '.$target.'>'.$myrow['title']."</a><br />\n";
            break;
        case TOOL_LINK:
            //doesn't take $target into account
            $TABLETOOLLINK = Database::get_course_table(TABLE_LINK);
            $result = Database::query("SELECT * FROM $TABLETOOLLINK WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            $output = '<img src="../img/links.gif" align="middle" /> <a href="#" onclick="javascript:window.open(\'../link/link_goto.php?link_id='.$myrow['id'].'&amp;link_url='.urlencode($myrow['url'])."','MyWindow','width=500,height=400,top='+((screen.height-400)/2)+',left='+((screen.width-500)/2)+',scrollbars=1,resizable=1,menubar=1'); return false;\"".$styling.'>'.$myrow['title']."</a><br />\n";
            break;
        case TOOL_QUIZ:
            $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
            $result = Database::query("SELECT * FROM $TBL_EXERCICES WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            $output = '<img src="../img/quiz.gif" align="middle" /> <a href="../exercice/exercise_submit.php?exerciseId='.$myrow['id'].'"'.$styling.' '.$target.'>'.$myrow['title']."</a><br />\n";
            break;
        case TOOL_FORUM:
            $TBL_FORUMS = Database::get_course_table(TABLE_FORUM);
            $result = Database::query("SELECT * FROM $TBL_FORUMS WHERE c_id = $course_id AND forum_id=$id");
            $myrow = Database::fetch_array($result);
            $output = '<img src="../img/forum.gif" align="middle" /> <a href="../phpbb/viewforum.php?forum='.$myrow['forum_id'].'&md5='.$myrow['md5'].'"'.$styling.' '.$target.'>'.$myrow['forum_name']."</a><br />\n";
            break;
        case TOOL_THREAD:  //=topics
            //$tbl_forum 		= Database::get_course_table(TABLE_FORUM);
            //$tbl_thread 	= Database::get_course_table(TABLE_FORUM_THREAD);
            $tbl_post 		= Database::get_course_table(TABLE_FORUM_POST);
            // grabbing the title of the post
            $sql_title = "SELECT * FROM $tbl_post WHERE c_id = $course_id AND post_id=".$id;
            $result_title = Database::query($sql_title);
            $myrow_title = Database::fetch_array($result_title);
            $output = '<img src="../img/forum.gif" align="middle" /> <a href="../phpbb/viewtopic.php?topic='.$myrow_title['thread_id'].'&forum='.$myrow_title['forum_id'].'" '.$styling.' '.$target.'>'.$myrow_title['post_title']."</a><br />\n";
            break;
        case TOOL_POST:
            $tbl_post = Database::get_course_table(TABLE_FORUM_POST);
            //$tbl_post_text = Database::get_course_table(FORUM_POST_TEXT_TABLE);
            $sql = "SELECT * FROM $tbl_post p WHERE c_id = $course_id AND p.post_id = $id";
            $result = Database::query($sql);
            $post = Database::fetch_array($result);
            $output = '<img src="../img/forum.gif" align="middle" /> <a href="../phpbb/viewtopic.php?topic='.$post['thread_id'].'&forum='.$post['forum_id'].'"'.$styling.' '.$target.'>'.$post['post_title']."</a><br />\n";
            break;
        case TOOL_DOCUMENT:
            $tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
            $result = Database::query("SELECT * FROM $tbl_doc WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            $pathname = explode('/', $myrow['path']); // Making a correct name for the link.
            $last = count($pathname) - 1;  // Making a correct name for the link.
            $filename = $pathname[$last];  // Making a correct name for the link.
            $image = choose_image($filename);
            $ext = explode('.', $filename);
            $ext = strtolower($ext[sizeof($ext) - 1]);
            $myrow['path'] = rawurlencode($myrow['path']);
            $in_frames = in_array($ext, array('htm', 'html', 'gif', 'jpg', 'jpeg', 'png'));
            $output = '<img src="../img/'.$image.'" align="middle" /> <a href="../document/'.($in_frames ? 'showinframes.php?file=' : 'download.php?doc_url=').$myrow['path'].'"'.$styling.' '.$target.'>'.$filename."</a><br />\n";
            break;
        /*
        case 'Externallink':
            $output = '<img src="../img/links.gif" align="middle" /> <a href="'.$id.'"'.$styling.' '.$target.'>'.$id."</a><br />\n";
            break;
        */
    }
    return $output;
}

/**
 * Returns an HTML-formatted link to a resource, to incorporate directly into
 * the new learning path tool.
 *
 * The function is a big switch on tool type.
 * In each case, we query the corresponding table for information and build the link
 * with that information.
 * @author	Yannick Warnier <ywarnier@beeznest.org> - rebranding based on previous work (display_addedresource_link_in_learnpath())
 * @param	string	Course code
 * @param	integer	The learning path ID (in lp table)
 * @param	integer	The database ID for that item in the lp_item table
 * @param id          - that is the correspondent id in the mirror tool (like Agenda item 2)
 * @param id_in_path  - the unique index in the items table
 */
function rl_get_resource_link_for_learnpath($course_code, $learnpath_id, $id_in_path) {
    $_course 		= Database::get_course_info($course_code);
    $tbl_lp_item 	= Database::get_course_table(TABLE_LP_ITEM);
    $learnpath_id 	= intval($learnpath_id);
    $id_in_path		= intval($id_in_path);
    
    $course_id = $_course['real_id'];
    
    $sql_item = "SELECT * FROM $tbl_lp_item WHERE c_id = $course_id AND lp_id = $learnpath_id AND id = $id_in_path";
    $res_item = Database::query($sql_item);
    if (Database::num_rows($res_item) < 1) return -1; //exit
    $row_item = Database::fetch_array($res_item);

    $type = strtolower($row_item['item_type']);
    $id = (strcmp($row_item['path'], '') == 0) ? '0' : $row_item['path'];
    $origin = 'learnpath';
    $main_dir_path = api_get_path(WEB_CODE_PATH);
    $main_course_path = api_get_path(WEB_COURSE_PATH).$_course['directory'].'/';

    $link = '';

    switch ($type) {
        case 'dokeos_chapter':
            $link .= $main_dir_path.'newscorm/blank.php';
        case TOOL_CALENDAR_EVENT:
            $link .= $main_dir_path.'calendar/agenda.php?origin='.$origin.'&agenda_id='.$id;
            break;
        case TOOL_ANNOUNCEMENT:
            $link .= $main_dir_path.'announcements/announcements.php?origin='.$origin.'&ann_id='.$id;
            break;
        case TOOL_LINK:
            $TABLETOOLLINK = Database::get_course_table(TABLE_LINK);
            $result= Database::query("SELECT * FROM $TABLETOOLLINK WHERE c_id = $course_id AND id=$id");
            $myrow=Database::fetch_array($result);
            $thelink=$myrow["url"];
            $link .= $thelink;
            break;
        case TOOL_QUIZ:
            if (!empty($id)) {
                $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
                $sql = "SELECT * FROM $TBL_EXERCICES WHERE c_id = $course_id AND id=$id";
                $result= Database::query($sql);
                $myrow=Database::fetch_array($result);

                if ($row_item['title'] != '') { $myrow['title'] = $row_item['title']; }
                //$link .= $main_dir_path.'exercice/exercise_submit.php?lp_init=1&origin='.$origin.'&learnpath_id='.$learnpath_id.'&learnpath_item_id='.$id_in_path.'&exerciseId='.$id;
                $link .= $main_dir_path.'exercice/overview.php?lp_init=1&origin='.$origin.'&learnpath_id='.$learnpath_id.'&learnpath_item_id='.$id_in_path.'&exerciseId='.$id;
            }
            break;
        case 'hotpotatoes': //lowercase because of strtolower above
            $TBL_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
            $result = Database::query("SELECT * FROM ".$TBL_DOCUMENT." WHERE c_id = $course_id AND  id=$id");
            $myrow = Database::fetch_array($result);
            $path = $myrow['path'];
            $link .= $main_dir_path.'exercice/showinframes.php?file='.$path.'' .
                    '&origin='.$origin.'&cid='.$course_code.'&uid='.api_get_user_id().'' .
                    '&learnpath_id='.$learnpath_id.'&learnpath_item_id='.$id_in_path;
            break;
        case TOOL_FORUM:
            $link .= $main_dir_path.'forum/viewforum.php?forum='.$id.'&lp=true&origin=learnpath';
            break;
        case TOOL_THREAD:  //forum post
            $tbl_topics = Database::get_course_table(TABLE_FORUM_THREAD);
            if (!empty($id)) {
                $sql = "SELECT * FROM $tbl_topics WHERE c_id = $course_id AND thread_id=$id";
                $result = Database::query($sql);
                $myrow = Database::fetch_array($result);
                $link .= $main_dir_path.'forum/viewthread.php?origin=learnpath&thread='.$id.'' .
                        '&forum='.$myrow['forum_id'].'&lp=true';
            }
            break;
        case TOOL_POST:
            $tbl_post = Database::get_course_table(TABLE_FORUM_POST);
            $result = Database::query("SELECT * FROM $tbl_post WHERE c_id = $course_id AND post_id=$id");
            $myrow = Database::fetch_array($result);
            $title = $myrow['post_title'];
            //$desc = $row_item['description'];
            $posternom = $myrow['poster_name'];
            $posttime = $myrow['post_date'];
            $posttext = $myrow['post_text'];
            $posttitle = $title;
            $posttext = str_replace('"', "'", $posttext);

            $link .= $main_dir_path.'forum/viewthread.php?post='.$id.'' .
                    '&thread='.$myrow['thread_id'].'&forum='.$myrow['forum_id'].'' .
                    '&lp=true';
            break;
        case TOOL_DOCUMENT:
            $tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
            $sql = "SELECT * FROM $tbl_doc WHERE c_id = $course_id AND id=$id";
            $result = Database::query($sql);
            $myrow = Database::fetch_array($result);
            $docurl = str_replace('%2F', '/', urlencode($myrow['path']));
            $link .= $main_course_path.'document'.$docurl.'?'.api_get_cidreq();
            $openmethod = 2;
            $officedoc = false;
            Session::write('openmethod',$openmethod);
            Session::write('officedoc',$officedoc);
            break;
        case 'assignments':
            $link .= $main_dir_path.'work/work.php?origin='.$origin;
            break;
        case TOOL_DROPBOX:
            $link .= $main_dir_path.'dropbox/index.php?origin=learnpath';
            break;
        case 'introduction_text': //DEPRECATED
            $link .= '';
            break;
        case TOOL_COURSE_DESCRIPTION:
            $link .= $main_dir_path.'course_description?origin=learnpath';
            break;
        case TOOL_GROUP:
            $link .= $main_dir_path.'group/group.php?origin='.$origin;
            break;
        case TOOL_USER:
            $link .= $main_dir_path.'user/user.php?origin='.$origin;
            break;
        case 'student_publication' :
            $link .= $main_dir_path.'work/work.php?origin=learnpath';
            break;
    } //end switch
    return $link;
}

/**
 * Gets the name of a resource (generally used in learnpath when no name is provided)
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>, Dokeos - rebranding
 * @param string 	Course code
 * @param string 	The tool type (using constants declared in main_api.lib.php)
 * @param integer 	The resource ID
 */
function rl_get_resource_name($course_code, $learnpath_id, $id_in_path) {
    $_course = Database::get_course_info($course_code);
    $course_id = $_course['real_id'];
    $tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);

    $sql_item = "SELECT item_type, title, ref FROM $tbl_lp_item WHERE c_id = $course_id AND lp_id = $learnpath_id AND id = $id_in_path";
    $res_item = Database::query($sql_item);

    if (Database::num_rows($res_item) < 1) {
        return ''; //exit
    }
    $row_item = Database::fetch_array($res_item);
    $type = strtolower($row_item['item_type']);
    $id = $row_item['ref'];
    $output = '';

    switch ($type) {
        case TOOL_CALENDAR_EVENT:
            $TABLEAGENDA = Database::get_course_table(TABLE_AGENDA);
            $result = Database::query("SELECT * FROM $TABLEAGENDA WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            $output = $myrow['title'];
            break;
        case TOOL_ANNOUNCEMENT:
            $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
            $result = Database::query("SELECT * FROM $tbl_announcement WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            $output = $myrow['title'];
            break;
        case TOOL_LINK:
            // Doesn't take $target into account.
            $TABLETOOLLINK = Database::get_course_table(TABLE_LINK);
            $result = Database::query("SELECT * FROM $TABLETOOLLINK WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            $output = $myrow['title'];
            break;
        case TOOL_QUIZ:
            $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
            $result = Database::query("SELECT * FROM $TBL_EXERCICES WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            $output = $myrow['title'];
            break;
        case TOOL_FORUM:
            $TBL_FORUMS = Database::get_course_table(TABLE_FORUM);
            $result = Database::query("SELECT * FROM $TBL_FORUMS WHERE c_id = $course_id AND forum_id=$id");
            $myrow = Database::fetch_array($result);
            $output = $myrow['forum_name'];
            break;
        case TOOL_THREAD:  //=topics
            $tbl_post = Database::get_course_table(TABLE_FORUM_POST);
            // Grabbing the title of the post.
            $sql_title = "SELECT * FROM $tbl_post WHERE c_id = $course_id AND post_id=".$id;
            $result_title = Database::query($sql_title);
            $myrow_title = Database::fetch_array($result_title);
            $output = $myrow_title['post_title'];
            break;
        case TOOL_POST:
            $tbl_post = Database::get_course_table(TABLE_FORUM_POST);
            //$tbl_post_text = Database::get_course_table(FORUM_POST_TEXT_TABLE);
            $sql = "SELECT * FROM $tbl_post p WHERE c_id = $course_id AND p.post_id = $id";
            $result = Database::query($sql);
            $post = Database::fetch_array($result);
            $output = $post['post_title'];
            break;
        case 'dokeos_chapter':
            $title = $row_item['title'];
            if (!empty($title)) {
                $output = $title;
            } else {
                $output = '-';
            }
            break;
        case TOOL_DOCUMENT:
            $title = $row_item['title'];
            if (!empty($title)) {
                $output = $title;
            } else {
                $output = '-';
            }
            break;
        case 'hotpotatoes':
            $tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
            $result = Database::query("SELECT * FROM $tbl_doc WHERE c_id = $course_id AND id=$id");
            $myrow = Database::fetch_array($result);
            $pathname = explode('/', $myrow['path']); // Making a correct name for the link.
            $last = count($pathname) - 1;  // Making a correct name for the link.
            $filename = $pathname[$last];  // Making a correct name for the link.
            $image = choose_image($filename);
            $ext = explode('.', $filename);
            $ext = strtolower($ext[sizeof($ext) - 1]);
            $myrow['path'] = rawurlencode($myrow['path']);
            $in_frames = in_array($ext, array('htm', 'html', 'gif', 'jpg', 'jpeg', 'png'));
            $output = $filename;
            break;
        /*
        case 'externallink':
            $output = '<img src="../img/links.gif" align="middle" /> <a href="'.$id.'"'.$styling.' '.$target.'>'.$id."</a><br />\n";
            break;
        */
    }
    return stripslashes($output);
}
