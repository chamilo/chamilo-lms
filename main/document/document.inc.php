<?php
/* For licensing terms, see /license.txt */

/*	EXTRA FUNCTIONS FOR DOCUMENTS TOOL */

/**
 * Builds the form thats enables the user to
 * select a directory to browse/upload in
 *
 * @param array 	An array containing the folders we want to be able to select
 * @param string	The current folder (path inside of the "document" directory, including the prefix "/")
 * @param string	Group directory, if empty, prevents documents to be uploaded (because group documents cannot be uploaded in root)
 * @param	boolean	Whether to change the renderer (this will add a template <span> to the QuickForm object displaying the form)
 * @return string html form
 */

function build_directory_selector($folders, $curdirpath, $group_dir = '', $change_renderer = false) {
    $folder_titles = array();
    if (api_get_setting('use_document_title') == 'true') {
        if (is_array($folders)) {
            $escaped_folders = array();
            foreach ($folders as $key => & $val) {
                $escaped_folders[$key] = Database::escape_string($val);
            }
            $folder_sql = implode("','", $escaped_folders);
            $doc_table = Database::get_course_table(TABLE_DOCUMENT);
            $sql = "SELECT * FROM $doc_table WHERE filetype='folder' AND path IN ('".$folder_sql."')";
            $res = Database::query($sql);
            $folder_titles = array();
            while ($obj = Database::fetch_object($res)) {
                $folder_titles[$obj->path] = $obj->title;
            }
        }
    } else {
        if (is_array($folders)) {
            foreach ($folders as & $folder) {
                $folder_titles[$folder] = basename($folder);
            }
        }
    }

    require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
    $form = new FormValidator('selector', 'POST', api_get_self());

    $parent_select = $form->addElement('select', 'curdirpath', get_lang('CurrentDirectory'), '', 'onchange="javascript: document.selector.submit();"');

    if ($change_renderer) {
        $renderer = $form->defaultRenderer();
        $renderer->setElementTemplate('<span>{label} : {element}</span> ','curdirpath');
    }

    // Group documents cannot be uploaded in the root
    if (empty($group_dir)) {
        $parent_select -> addOption(get_lang('Documents'), '/');
        if (is_array($folders)) {
            foreach ($folders as & $folder) {
                $selected = ($curdirpath == $folder) ? ' selected="selected"' : '';
                $path_parts = explode('/', $folder);
                $folder_titles[$folder] = cut($folder_titles[$folder], 80);
                $label = str_repeat('&nbsp;&nbsp;&nbsp;', count($path_parts) - 2).' &mdash; '.$folder_titles[$folder];
                $parent_select -> addOption($label, $folder);
                if ($selected != '') {
                    $parent_select->setSelected($folder);
                }
            }
        }
    } else {
        foreach ($folders as & $folder) {
            $selected = ($curdirpath==$folder) ? ' selected="selected"' : '';
            $label = $folder_titles[$folder];
            if ($folder == $group_dir) {
                $label = get_lang('Documents');
            } else {
                $path_parts = explode('/', str_replace($group_dir, '', $folder));
                $label = cut($label, 80);
                $label = str_repeat('&nbsp;&nbsp;&nbsp;', count($path_parts) - 2).' &mdash; '.$label;
            }
            $parent_select -> addOption($label, $folder);
            if ($selected != '') {
                $parent_select->setSelected($folder);
            }
        }
    }

    $form = $form->toHtml();

    return $form;
}



/**
 * Create a html hyperlink depending on if it's a folder or a file
 *
 * @param string $www
 * @param string $title
 * @param string $path
 * @param string $filetype (file/folder)
 * @param int $visibility (1/0)
 * @param int $show_as_icon - if it is true, only a clickable icon will be shown
 * @return string url
 */
function create_document_link($document_data, $show_as_icon = false, $counter = null) {
    global $dbl_click_id;
    if (isset($_SESSION['_gid'])) {
        $req_gid = '&amp;gidReq='.$_SESSION['_gid'];
    } else {
        $req_gid = '';
    }
    $course_info = api_get_course_info();
    $www = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/document';
    $use_document_title = api_get_setting('use_document_title');
    
    // Get the title or the basename depending on what we're using
    if ($use_document_title == 'true' && $document_data['title'] != '') {
        $title = $document_data['title'];
    } else {
        $title = basename($document_data['title']);
    }
    
    $filetype = $document_data['filetype'];
    $size = $filetype == 'folder' ? get_total_folder_size($document_data['path'], api_is_allowed_to_edit(null, true)) : $document_data['size'];
    $visibility = $document_data['visibility'];
    $path = $document_data['path'];
      
    $url_path = urlencode($document_data['path']);
    // Add class="invisible" on invisible files
    $visibility_class = ($visibility == 0) ? ' class="invisible"' : '';

    if (!$show_as_icon) {
        // Build download link (icon)
        $forcedownload_link = ($filetype == 'folder') ? api_get_self().'?'.api_get_cidreq().'&action=downloadfolder&path='.$url_path.$req_gid : api_get_self().'?'.api_get_cidreq().'&amp;action=download&amp;id='.$url_path.$req_gid;
        // Folder download or file download?
        $forcedownload_icon = ($filetype == 'folder') ? 'save_pack.png' : 'save.png';
        // Prevent multiple clicks on zipped folder download
        $prevent_multiple_click = ($filetype == 'folder') ? " onclick=\"javascript: if(typeof clic_$dbl_click_id == 'undefined' || !clic_$dbl_click_id) { clic_$dbl_click_id=true; window.setTimeout('clic_".($dbl_click_id++)."=false;',10000); } else { return false; }\"":'';
    }

    $target = '_self';
    $is_browser_viewable_file = false; 
    
    if ($filetype == 'file') {
        // Check the extension
        $ext = explode('.', $path);
        $ext = strtolower($ext[sizeof($ext) - 1]);

        // HTML-files an some other types are shown in a frameset by default.
        $is_browser_viewable_file = is_browser_viewable($ext);
        
        if ($is_browser_viewable_file) {
            //$url = 'showinframes.php?'.api_get_cidreq().'&amp;file='.$url_path.$req_gid;
            $url = 'showinframes.php?'.api_get_cidreq().'&id='.$document_data['id'].$req_gid;
        } else {
            // url-encode for problematic characters (we may not call them dangerous characters...)
            $path = str_replace('%2F', '/',$url_path).'?'.api_get_cidreq();
            //$new_path = '?id='.$document_data['id'];
            $url = $www.$path;
        }
        //$path = str_replace('%2F', '/',$url_path).'?'.api_get_cidreq();
        $path = str_replace('%2F', '/',$url_path); //yox view hack otherwise the image can't be well read 
        $url = $www.$path;
        
        // Disabled fragment of code, there is a special icon for opening in a new window.
        //// Files that we want opened in a new window
        //if ($ext == 'txt' || $ext == 'log' || $ext == 'css' || $ext == 'js') { // Add here
        //    $target = '_blank';
        //}
    } else {
        //$url = api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.$url_path.$req_gid;
        $url = api_get_self().'?'.api_get_cidreq().'&id='.$document_data['id'].$req_gid;
    }

    // The little download icon
    //$tooltip_title = str_replace('?cidReq='.$_GET['cidReq'], '', basename($path));
    $tooltip_title = explode('?', basename($path));
    //$tooltip_title = $tooltip_title[0];
    $tooltip_title = $title;
    

    $tooltip_title_alt = $tooltip_title;
    if ($path == '/shared_folder') {
        $tooltip_title_alt = get_lang('UserFolders');
    } elseif(strstr($path, 'shared_folder_session_')) {
        $tooltip_title_alt = get_lang('UserFolders').' ('.api_get_session_name(api_get_session_id()).')';
    } elseif(strstr($tooltip_title, 'sf_user_')) {
        $userinfo = Database::get_user_info_from_id(substr($tooltip_title, 8));
        $tooltip_title_alt = get_lang('UserFolder').' '.api_get_person_name($userinfo['firstname'], $userinfo['lastname']);
    } elseif($path == '/chat_files') {
        $tooltip_title_alt = get_lang('ChatFiles');
    } elseif($path == '/video') {
        $tooltip_title_alt = get_lang('Video');
    } elseif($path == '/audio') {
        $tooltip_title_alt = get_lang('Audio');
    } elseif($path == '/flash') {
        $tooltip_title_alt = get_lang('Flash');
    } elseif($path == '/images') {
        $tooltip_title_alt = get_lang('Images');
    } elseif($path == '/images/gallery') {
        $tooltip_title_alt = get_lang('DefaultCourseImages');
    }
    $current_session_id = api_get_session_id();
    $copy_to_myfiles = $open_in_new_window_link = null;
    if (!$show_as_icon) {
        if ($filetype == 'folder') {
            if (api_is_allowed_to_edit() || api_is_platform_admin() || api_get_setting('students_download_folders') == 'true') {
                //filter when I am into shared folder, I can show for donwload only my shared folder
                if (isset($_GET['curdirpath']) && is_shared_folder($_GET['curdirpath'], $current_session_id)) {
                    if (preg_match('/shared_folder\/sf_user_'.api_get_user_id().'$/', urldecode($forcedownload_link))|| preg_match('/shared_folder_session_'.$current_session_id.'\/sf_user_'.api_get_user_id().'$/', urldecode($forcedownload_link)) || api_is_allowed_to_edit() || api_is_platform_admin()) {
                        $force_download_html = ($size == 0) ? '' : '<a href="'.$forcedownload_link.'" style="float:right"'.$prevent_multiple_click.'>'.Display::return_icon($forcedownload_icon, get_lang('Download'), array(),22).'</a>';
                    }
                } elseif(!preg_match('/shared_folder/', urldecode($forcedownload_link)) || api_is_allowed_to_edit() || api_is_platform_admin()) {
                    $force_download_html = ($size == 0) ? '' : '<a href="'.$forcedownload_link.'" style="float:right"'.$prevent_multiple_click.'>'.Display::return_icon($forcedownload_icon, get_lang('Download'), array(),22).'</a>';
                }
            }
        } else {
            $force_download_html = ($size==0)?'':'<a href="'.$forcedownload_link.'" style="float:right"'.$prevent_multiple_click.'>'.Display::return_icon($forcedownload_icon, get_lang('Download'), array(),22).'</a>';
        }

        //copy files to users myfiles
        if (api_get_setting('users_copy_files') == 'true' && api_get_user_id() != 0){
            $copy_myfiles_link = ($filetype == 'file') ? api_get_self().'?'.api_get_cidreq().'&curdirpath='.Security::remove_XSS($_GET['curdirpath']).'&amp;action=copytomyfiles&amp;id='.$url_path.$req_gid :api_get_self().'?'.api_get_cidreq();

            if ($filetype == 'file') {
                $copy_to_myfiles = '<a href="'.$copy_myfiles_link.'" style="float:right"'.$prevent_multiple_click.'>'.Display::return_icon('briefcase.png', get_lang('CopyToMyFiles'), array(),22).'&nbsp;&nbsp;</a>';
            }
        }
        
        $pdf_icon = '';
        $extension = pathinfo($path, PATHINFO_EXTENSION);          
        if (!api_is_allowed_to_edit() && api_get_setting('students_export2pdf') == 'true' && $filetype == 'file' && in_array($extension, array('html','htm'))) {            
            $pdf_icon = ' <a style="float:right".'.$prevent_multiple_click.' href="'.api_get_self().'?'.api_get_cidreq().'&action=export_to_pdf&id='.$document_data['id'].'">'.Display::return_icon('pdf.png', get_lang('Export2PDF'),array(), 22).'</a> ';
        } 
        
        if ($is_browser_viewable_file) {
            $open_in_new_window_link = '<a href="'.$www.str_replace('%2F', '/',$url_path).'?'.api_get_cidreq().'" style="float:right"'.$prevent_multiple_click.' target="_blank">'.Display::return_icon('open_in_new_window.png', get_lang('OpenInANewWindow'), array(),22).'&nbsp;&nbsp;</a>';
        }
        //target="'.$target.'"
        if ($filetype == 'file') {
            //Sound preview with jplayer
			if ( preg_match('/mp3$/',  urldecode($url))  ||
			     preg_match('/wav$/',  urldecode($url))  ||
			     preg_match('/ogg$/',  urldecode($url))) {			         
			     return '<span style="float:left" '.$visibility_class.' style="float:left">'.$title.'</span>'.$force_download_html.$copy_to_myfiles.$open_in_new_window_link.$pdf_icon;
            } elseif (
                //Show preview sith yoxview
                 preg_match('/swf$/',  urldecode($url))  || 
			     preg_match('/html$/', urldecode($url))  || 
			     preg_match('/htm$/',  urldecode($url))  //|| (preg_match('/wav$/', urldecode($url)) && api_get_setting('enable_nanogong') == 'true')
            ) {
				$url = 'showinframesmin.php?'.api_get_cidreq().'&id='.$document_data['id'].$req_gid;
				return '<a href="'.$url.'" class="yoxview" title="'.$tooltip_title_alt.'" target="yoxview" style="float:left" '.$visibility_class.' style="float:left">'.$title.'</a>'.$force_download_html.$copy_to_myfiles.$open_in_new_window_link.$pdf_icon;
			} else {
			    //Show preview sith yoxview			
            	return '<a href="'.$url.'" class="yoxview" title="'.$tooltip_title_alt.'" target="yoxview" style="float:left" '.$visibility_class.' style="float:left">'.$title.'</a>'.$force_download_html.$copy_to_myfiles.$open_in_new_window_link.$pdf_icon;
			}          
        } else {
            return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" '.$visibility_class.' style="float:left">'.$title.'</a>'.$force_download_html.$copy_to_myfiles.$open_in_new_window_link.$pdf_icon;
        }
        //end copy files to users myfiles
    } else {
        
		//Icon column
        if (preg_match('/shared_folder/', urldecode($url)) && preg_match('/shared_folder$/', urldecode($url))==false && preg_match('/shared_folder_session_'.$current_session_id.'$/', urldecode($url))==false){
			if ($filetype == 'file') {
                //Sound preview with jplayer
                if ( preg_match('/mp3$/',  urldecode($url))  ||
                     preg_match('/wav$/',  urldecode($url))  ||
                     preg_match('/ogg$/',  urldecode($url))) {                   
                     $sound_preview = DocumentManager::generate_mp3_preview($counter);
                     return $sound_preview ;
                } elseif (
                    //Show preview sith yoxview
                     preg_match('/swf$/',  urldecode($url))  || 
                     preg_match('/html$/', urldecode($url))  || 
                     preg_match('/htm$/',  urldecode($url))  //|| (preg_match('/wav$/', urldecode($url)) && api_get_setting('enable_nanogong') == 'true')
                ) {
					$url = 'showinframesmin.php?'.api_get_cidreq().'&id='.$document_data['id'].$req_gid;
					return '<a href="'.$url.'" class="yoxview" title="'.$tooltip_title_alt.'" target="yoxview"'.$visibility_class.' style="float:left">'.build_document_icon_tag($filetype, $path).Display::return_icon('shared.png', get_lang('ResourceShared'), array('hspace' => '5', 'align' => 'middle', 'height' => 22, 'width' => 22)).'</a>';
				} else {			
					return '<a href="'.$url.'" class="yoxview" title="'.$tooltip_title_alt.'" target="yoxview"'.$visibility_class.' style="float:left">'.build_document_icon_tag($filetype, $path).Display::return_icon('shared.png', get_lang('ResourceShared'), array('hspace' => '5', 'align' => 'middle', 'height' => 22, 'width' => 22)).'</a>';
				}          
        	} else {			
            	return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" target="'.$target.'"'.$visibility_class.' style="float:left">'.build_document_icon_tag($filetype, $path).Display::return_icon('shared.png', get_lang('ResourceShared'), array('hspace' => '5', 'align' => 'middle', 'height' => 22, 'width' => 22)).'</a>';
			}						
        } else {
			if ($filetype == 'file') {
                //Sound preview with jplayer
                if ( preg_match('/mp3$/',  urldecode($url))  ||
                     preg_match('/wav$/',  urldecode($url))  ||
                     preg_match('/ogg$/',  urldecode($url))) {
                     $sound_preview = DocumentManager::generate_mp3_preview($counter);
                     return $sound_preview ;                     
                } elseif (
                    //Show preview sith yoxview
                     preg_match('/swf$/',  urldecode($url))  || 
                     preg_match('/html$/', urldecode($url))  || 
                     preg_match('/htm$/',  urldecode($url))  //|| (preg_match('/wav$/', urldecode($url)) && api_get_setting('enable_nanogong') == 'true')
                ) {
					$url = 'showinframesmin.php?'.api_get_cidreq().'&id='.$document_data['id'].$req_gid;
					return '<a href="'.$url.'" class="yoxview" title="'.$tooltip_title_alt.'" target="yoxview"'.$visibility_class.' style="float:left">'.build_document_icon_tag($filetype, $path).'</a>';
				} else {			
					return '<a href="'.$url.'" class="yoxview" title="'.$tooltip_title_alt.'" target="yoxview"'.$visibility_class.' style="float:left">'.build_document_icon_tag($filetype, $path).'</a>';
				}          
        	} else {
             	return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" target="'.$target.'"'.$visibility_class.' style="float:left">'.build_document_icon_tag($filetype, $path).'</a>';
			}
		}
    }
}


/**
 * Builds an img html tag for the filetype
 *
 * @param string $type (file/folder)
 * @param string $path
 * @return string img html tag
 */
function build_document_icon_tag($type, $path) {
    $basename = basename($path);
    $current_session_id = api_get_session_id();
    $is_allowed_to_edit = api_is_allowed_to_edit(null, true);

    if ($type == 'file') {
        $icon = choose_image($basename);
    } else {
        if ($path == '/shared_folder') {
            $icon = 'folder_users.gif';
            if ($is_allowed_to_edit) {
                $basename = get_lang('HelpUsersFolder');
            } else {
                $basename = get_lang('UserFolders');
            }

        }elseif(strstr($basename, 'sf_user_')) {
            $userinfo = Database::get_user_info_from_id(substr($basename, 8));
            $image_path = UserManager::get_user_picture_path_by_id(substr($basename, 8), 'web', false, true);

            if ($image_path['file'] == 'unknown.jpg') {
                $icon = $image_path['file'];
            } else {
                $icon = '../upload/users/'.substr($basename, 8).'/'.$image_path['file'];
            }

            $basename = get_lang('UserFolder').' '.api_get_person_name($userinfo['firstname'], $userinfo['lastname']);}elseif(strstr($path, 'shared_folder_session_')) {
            if ($is_allowed_to_edit) {
                $basename = '***('.api_get_session_name($current_session_id).')*** '.get_lang('HelpUsersFolder');
            } else {
                $basename = get_lang('UserFolders').' ('.api_get_session_name($current_session_id).')';
            }
            $icon = 'folder_users.gif';

        } else {
            $icon = 'folder_document.gif';

            if($path=='/audio'){
                $icon = 'folder_audio.gif';
                if(api_is_allowed_to_edit()){
                    $basename=get_lang('HelpDefaultDirDocuments');
                }
                else{
                    $basename=get_lang('Audio');
                }
            }
            elseif($path =='/flash'){
                $icon = 'folder_flash.gif';
                if(api_is_allowed_to_edit()){
                    $basename=get_lang('HelpDefaultDirDocuments');
                }
                else{
                    $basename=get_lang('Flash');
                }
            }
            elseif($path =='/images'){
                $icon = 'folder_images.gif';
                if(api_is_allowed_to_edit()){
                    $basename=get_lang('HelpDefaultDirDocuments');
                }
                else{
                    $basename=get_lang('Images');
                }
            }
            elseif($path =='/video'){
                $icon = 'folder_video.gif';
                if(api_is_allowed_to_edit()){
                    $basename=get_lang('HelpDefaultDirDocuments');
                }
                else{
                    $basename=get_lang('Video');
                }
            }
            elseif($path =='/images/gallery'){
                $icon = 'folder_gallery.gif';
                if(api_is_allowed_to_edit()){
                    $basename=get_lang('HelpDefaultDirDocuments');
                }
                else{
                    $basename=get_lang('Gallery');
                }
            }
            elseif($path =='/chat_files'){
                $icon = 'folder_chat.gif';
                if(api_is_allowed_to_edit()){
                    $basename=get_lang('HelpFolderChat');
                }
                else{
                    $basename=get_lang('ChatFiles');
                }
            }
        }
    }

    return Display::return_icon($icon, $basename, array('hspace' => '5', 'align' => 'middle', 'height' => 22, 'width' => 22));
}

/**
 * Creates the row of edit icons for a file/folder
 *
 * @param string $curdirpath current path (cfr open folder)
 * @param string $type (file/folder)
 * @param string $path dbase path of file/folder
 * @param int $visibility (1/0)
 * @param int $id dbase id of the document
 * @return string html img tags with hyperlinks
 */
//function build_edit_icons($document_data, $curdirpath, $type, $path, $visibility, $id, $is_template, $is_read_only = 0, $session_id = 0) {
function build_edit_icons($document_data, $id, $is_template, $is_read_only = 0, $session_id = 0) {
    if (isset($_SESSION['_gid'])) {
        $req_gid = '&gidReq='.$_SESSION['_gid'];
    } else {
        $req_gid = '';
    }
    $document_id            = $document_data['id'];
    
    $type                   = $document_data['filetype'];
    $visibility             = $document_data['visibility'];
    $is_read_only           = $document_data['readonly'];
    $path                   = $document_data['path'];
    $parent_id              = DocumentManager::get_document_id(api_get_course_info(), dirname($path));    
    $curdirpath             = dirname($document_data['path']);
    $is_certificate_mode    = DocumentManager::is_certificate_mode($path);
    $curdirpath             = urlencode($curdirpath);
    $extension              = pathinfo($path, PATHINFO_EXTENSION);
    
    // Build URL-parameters for table-sorting
    $sort_params = array();
    if (isset($_GET['column'])) {
        $sort_params[] = 'column='.Security::remove_XSS($_GET['column']);
    }
    if (isset($_GET['page_nr'])) {
        $sort_params[] = 'page_nr='.Security::remove_XSS($_GET['page_nr']);
    }
    if (isset($_GET['per_page'])) {
        $sort_params[] = 'per_page='.Security::remove_XSS($_GET['per_page']);
    }
    if (isset($_GET['direction'])) {
        $sort_params[] = 'direction='.Security::remove_XSS($_GET['direction']);
    }
    $sort_params = implode('&amp;', $sort_params);
    $visibility_icon    = ($visibility == 0) ? 'invisible' : 'visible';
    $visibility_command = ($visibility == 0) ? 'set_visible' : 'set_invisible';
        
    $modify_icons = '';    
    
    // If document is read only *or* we're in a session and the document
    // is from a non-session context, hide the edition capabilities
    if ($is_read_only /*or ($session_id!=api_get_session_id())*/) {     
        if (api_is_course_admin() || api_is_platform_admin()) {
            if($extension=='svg' && api_browser_support('svg') && api_get_setting('enabled_support_svg') == 'true') {
                $modify_icons = '<a href="edit_draw.php?'.api_get_cidreq().'&id='.$document_id.$req_gid.'">'.Display::return_icon('edit.png', get_lang('Modify'),'',22).'</a>';
            } elseif($extension=='png' || $extension=='jpg' || $extension=='jpeg' || $extension=='bmp' || $extension=='gif' ||$extension=='pxd' && api_get_setting('enabled_support_pixlr') == 'true'){
                $modify_icons = '<a href="edit_paint.php?'.api_get_cidreq().'&id='.$document_id.$req_gid.'">'.Display::return_icon('edit.png', get_lang('Modify'),'',22).'</a>';       
            } else {
                $modify_icons = '<a href="edit_document.php?'.api_get_cidreq().'&id='.$document_id.$req_gid.'">'.Display::return_icon('edit.png', get_lang('Modify'),'',22).'</a>';
            }
        } else {
            $modify_icons  = Display::return_icon('edit_na.png', get_lang('Modify'),'',22);
        }
        $modify_icons .= '&nbsp;'.Display::return_icon('move_na.png', get_lang('Move'),array(), 22);
        if (api_is_allowed_to_edit() || api_is_platform_admin()) {
            $modify_icons .= '&nbsp;'.Display::return_icon($visibility_icon.'.png', get_lang('VisibilityCannotBeChanged'),'',22);
        }
        $modify_icons .= '&nbsp;'.Display::return_icon('delete_na.png', get_lang('Delete'),array(), 22);
    } else {        
        if ($is_certificate_mode) {
            // gradebook category doesn't seem to be taken into account
            //$modify_icons = '<a href="edit_document.php?'.api_get_cidreq().'&id='.$document_id.$req_gid.'&curdirpath=/certificates&selectcat='.$gradebook_category.'">'.Display::return_icon('edit.png', get_lang('Modify'),'',22).'</a>';            
            $modify_icons = '<a href="edit_document.php?'.api_get_cidreq().'&amp;id='.$document_id.$req_gid.'&curdirpath=/certificates">'.Display::return_icon('edit.png', get_lang('Modify'),'',22).'</a>';            
        } else {
            if (api_get_session_id()) {
                if ($document_data['session_id'] == api_get_session_id()) {  
                    if ($extension=='svg' && api_browser_support('svg') && api_get_setting('enabled_support_svg') == 'true') {
                        $modify_icons = '<a href="edit_draw.php?'.api_get_cidreq().'&amp;id='.$document_id.$req_gid.'">'.Display::return_icon('edit.png', get_lang('Modify'),'',22).'</a>';
                    } elseif($extension=='png' || $extension=='jpg' || $extension=='jpeg' || $extension=='bmp' || $extension=='gif' ||$extension=='pxd' && api_get_setting('enabled_support_pixlr') == 'true'){
                        $modify_icons = '<a href="edit_paint.php?'.api_get_cidreq().'&amp;id='.$document_id.$req_gid.'">'.Display::return_icon('edit.png', get_lang('Modify'),'',22).'</a>';       
                    } else {
                        $modify_icons = '<a href="edit_document.php?'.api_get_cidreq().'&amp;id='.$document_id.$req_gid.'">'.Display::return_icon('edit.png', get_lang('Modify'),'',22).'</a>';
                    }
                } else {
                    $modify_icons .= '&nbsp;'.Display::return_icon('edit_na.png', get_lang('Edit'),array(), 22).'</a>';                    
                }
            } else {
                if ($extension=='svg' && api_browser_support('svg') && api_get_setting('enabled_support_svg') == 'true') {
                    $modify_icons = '<a href="edit_draw.php?'.api_get_cidreq().'&amp;id='.$document_id.$req_gid.'">'.Display::return_icon('edit.png', get_lang('Modify'),'',22).'</a>';
                } elseif($extension=='png' || $extension=='jpg' || $extension=='jpeg' || $extension=='bmp' || $extension=='gif' ||$extension=='pxd' && api_get_setting('enabled_support_pixlr') == 'true'){
                    $modify_icons = '<a href="edit_paint.php?'.api_get_cidreq().'&amp;id='.$document_id.$req_gid.'">'.Display::return_icon('edit.png', get_lang('Modify'),'',22).'</a>';       
                } else {
                    $modify_icons = '<a href="edit_document.php?'.api_get_cidreq().'&amp;id='.$document_id.$req_gid.'">'.Display::return_icon('edit.png', get_lang('Modify'),'',22).'</a>';
                }
            }
        }
        if ($is_certificate_mode) {
            //$modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&id='.$parent_id.'&amp;move='.$document_id.$req_gid.'&selectcat='.$gradebook_category.'">'.Display::return_icon('move.png', get_lang('Move'),array(), 22).'</a>';
            $modify_icons .= '&nbsp;'.Display::return_icon('move_na.png', get_lang('Move'),array(), 22).'</a>';
            $modify_icons .= '&nbsp;'.Display::return_icon($visibility_icon.'.png', get_lang('VisibilityCannotBeChanged'),array(), 22).'</a>';
			Display::return_icon($visibility_icon.'.png', get_lang('VisibilityCannotBeChanged'),array(), 22).'</a>';			
        } else {
            if (api_get_session_id()) {
                if ($document_data['session_id'] == api_get_session_id()) {                       
                    $modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;id='.$parent_id.'&amp;move='.$document_id.$req_gid.'">'.Display::return_icon('move.png', get_lang('Move'),array(), 22).'</a>';
                } else {
                    $modify_icons .= '&nbsp;'.Display::return_icon('move_na.png', get_lang('Move'),array(), 22).'</a>';                        
                }    
            } else {
                $modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;id='.$parent_id.'&amp;move='.$document_id.$req_gid.'">'.Display::return_icon('move.png', get_lang('Move'),array(), 22).'</a>';
            }
            if (api_is_allowed_to_edit() || api_is_platform_admin()) {
				if ($visibility_icon=='invisible'){					
					$tip_visibility=get_lang('Show');					
				}else{
					$tip_visibility=get_lang('Hide');
				}
                $modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;id='.$parent_id.'&amp;'.$visibility_command.'='.$id.$req_gid.'&amp;'.$sort_params.'">'.Display::return_icon($visibility_icon.'.png', $tip_visibility,'',22).'</a>';

            }
        }        
        if (in_array($path, array('/audio', '/flash', '/images', '/shared_folder', '/video', '/chat_files', '/certificates'))) {
            $modify_icons .= '&nbsp;'.Display::return_icon('delete_na.png', get_lang('ThisFolderCannotBeDeleted'),array(), 22);
        } else {
            if (isset($_GET['curdirpath']) && $_GET['curdirpath']=='/certificates' && DocumentManager::get_default_certificate_id(api_get_course_id())==$id) {                
                //$modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;delete='.urlencode($path).$req_gid.'&amp;'.$sort_params.'delete_certificate_id='.$id.'&selectcat='.$gradebook_category.' " onclick="return confirmation(\''.basename($path).'\');">'.Display::return_icon('delete.png', get_lang('Delete'),array(), 22).'</a>';
                $modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.$curdirpath.'&amp;delete='.urlencode($path).$req_gid.'&amp;'.$sort_params.'delete_certificate_id='.$id.'" onclick="return confirmation(\''.basename($path).'\');">'.Display::return_icon('delete.png', get_lang('Delete'),array(), 22).'</a>';
            } else {
                if ($is_certificate_mode) {
                    //$modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;delete='.urlencode($path).$req_gid.'&amp;'.$sort_params.'&selectcat='.$gradebook_category.'" onclick="return confirmation(\''.basename($path).'\');">'.Display::return_icon('delete.png', get_lang('Delete'),array(), 22).'</a>';
                    $modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.$curdirpath.'&amp;delete='.urlencode($path).$req_gid.'&amp;'.$sort_params.'" onclick="return confirmation(\''.basename($path).'\');">'.Display::return_icon('delete.png', get_lang('Delete'),array(), 22).'</a>';
                } else {
                    if (api_get_session_id()) {
                        if ($document_data['session_id'] == api_get_session_id()) {                        
                            $modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.$curdirpath.'&amp;delete='.urlencode($path).$req_gid.'&amp;'.$sort_params.'" onclick="return confirmation(\''.basename($path).'\');">'.Display::return_icon('delete.png', get_lang('Delete'),array(), 22).'</a>';
                        } else {
                            $modify_icons .= '&nbsp;'.Display::return_icon('delete_na.png', get_lang('ThisFolderCannotBeDeleted'),array(), 22);                            
                        }
                    } else {                
                        $modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.$curdirpath.'&amp;delete='.urlencode($path).$req_gid.'&amp;'.$sort_params.'" onclick="return confirmation(\''.basename($path).'\');">'.Display::return_icon('delete.png', get_lang('Delete'),array(), 22).'</a>';
                    }
                }
            }
        }        
    }

    if ($type == 'file' && ($extension == 'html' || $extension == 'htm')) {
        if ($is_template == 0) {
            if ((isset($_GET['curdirpath']) && $_GET['curdirpath'] != '/certificates') || !isset($_GET['curdirpath'])) {
                $modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;add_as_template='.$id.$req_gid.'&amp;'.$sort_params.'">'.Display::return_icon('wizard.png', get_lang('AddAsTemplate'),array(), 22).'</a>';
            }
            if (isset($_GET['curdirpath']) && $_GET['curdirpath']=='/certificates') {//allow attach certificate to course
                $visibility_icon_certificate='nocertificate';
                if (DocumentManager::get_default_certificate_id(api_get_course_id())==$id) {
                    $visibility_icon_certificate='certificate';
                    $certificate=get_lang('DefaultCertificate');
                    $preview=get_lang('PreviewCertificate');
                    $is_preview=true;
                } else {
                    $is_preview=false;
                    $certificate=get_lang('NoDefaultCertificate');
                }
                if (isset($_GET['selectcat'])) {
                    $modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;selectcat='.Security::remove_XSS($_GET['selectcat']).'&amp;set_certificate='.$id.$req_gid.'&amp;'.$sort_params.'"><img src="../img/'.$visibility_icon_certificate.'.png" border="0" title="'.$certificate.'" alt="" /></a>';
                    if ($is_preview) {
                        $modify_icons .= '&nbsp;<a target="_blank"  href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;set_preview='.$id.$req_gid.'&amp;'.$sort_params.'" >'.
						Display::return_icon('preview_view.png', $preview,'',22).'</a>';
                    }
                }
            }
        } else {
            $modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&curdirpath='.$curdirpath.'&amp;remove_as_template='.$id.$req_gid.'&amp;'.$sort_params.'">'.
			Display::return_icon('wizard_na.png', get_lang('RemoveAsTemplate'),'',22).'</a>';
		}
        $modify_icons .= '&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&action=export_to_pdf&id='.$id.'">'.Display::return_icon('pdf.png', get_lang('Export2PDF'),array(), 22).'</a>';
    }
    return $modify_icons;
}

function build_move_to_selector($folders, $curdirpath, $move_file, $group_dir = '') {
    
    $form = '<form name="move_to" action="'.api_get_self().'" method="post">';
    $form .= '<input type="hidden" name="move_file" value="'.$move_file.'" />';
    
    $form .= '<div class="row">';
    $form .= '<div class="label">';
    $form .= get_lang('MoveTo');
    $form .= '</div>';
    
    $form .= '<div class="formw">';

    $form .= '<select name="move_to">';

    // Group documents cannot be uploaded in the root
    if ($group_dir == '') {
        if ($curdirpath != '/') {
            $form .= '<option value="/">'.get_lang('Documents').'</option>';
        }

        if (is_array($folders)) {
            foreach ($folders as & $folder) {
                //Hide some folders
                if($folder=='/HotPotatoes_files' || $folder=='/certificates' || basename($folder)=='css'){
                    continue;
                }
                //Admin setting for Hide/Show the folders of all users
                if(api_get_setting('show_users_folders') == 'false' && (strstr($folder, '/shared_folder') || strstr($folder, 'shared_folder_session_'))){
                    continue;
                }
                //Admin setting for Hide/Show Default folders to all users
                if(api_get_setting('show_default_folders') == 'false' && ($folder=='/images' || $folder=='/flash' || $folder=='/audio' || $folder=='/video' || strstr($folder, '/images/gallery') || $folder=='/video/flv')){
                    continue;
                }
                //Admin setting for Hide/Show chat history folder
                if(api_get_setting('show_chat_folder') == 'false' && $folder=='/chat_files'){
                    continue;
                }

                // You cannot move a file to:
                // 1. current directory
                // 2. inside the folder you want to move
                // 3. inside a subfolder of the folder you want to move
                if (($curdirpath != $folder) && ($folder != $move_file) && (substr($folder, 0, strlen($move_file) + 1) != $move_file.'/')) {
                    $path_displayed = $folder;
                    // If document title is used, we have to display titles instead of real paths...
                    if (api_get_setting('use_document_title')) {
                        $path_displayed = get_titles_of_path($folder);
                    }
                    if (empty($path_displayed)) {
                        $path_displayed = get_lang('Untitled');
                    }
                    $form .= '<option value="'.$folder.'">'.$path_displayed.'</option>';
                }
            }
        }
    } else {
        foreach ($folders as $folder) {
            if (($curdirpath != $folder) && ($folder != $move_file) && (substr($folder, 0, strlen($move_file) + 1) != $move_file.'/')) { // Cannot copy dir into his own subdir
                if (api_get_setting('use_document_title')) {
                    $path_displayed = get_titles_of_path($folder);
                }
                $display_folder = substr($path_displayed,strlen($group_dir));
                $display_folder = ($display_folder == '') ? get_lang('Documents') : $display_folder;
                $form .= '<option value="'.$folder.'">'.$display_folder.'</option>';
            }
        }
    }

    $form .= '		</select>';
    $form .= '	</div>';
    $form .= '  </div>';

    $form .= '<div class="row">';
    $form .= '	<div class="label"></div>';
    $form .= '	<div class="formw">';
    $form .= '		<button type="submit" class="next" name="move_file_submit">'.get_lang('MoveElement').'</button>';
    $form .= '	</div>';
    $form .= '</div>';

    $form .= '</form>';

    $form .= '<div style="clear: both; margin-bottom: 10px;"></div>';

    return $form;
}

/**
 * Gets the path translated with title of docs and folders
 * @param string the real path
 * @return the path which should be displayed
 */
function get_titles_of_path($path) {

    global $tmp_folders_titles;

    $nb_slashes = substr_count($path, '/');
    $tmp_path = '';
    $current_slash_pos = 0;
    $path_displayed = '';
    for ($i = 0; $i < $nb_slashes; $i++) {
        // For each folder of the path, retrieve title.
        $current_slash_pos = strpos($path, '/', $current_slash_pos + 1);
        $tmp_path = substr($path, strpos($path, '/', 0), $current_slash_pos);

        if (empty($tmp_path)) {
            // If empty, then we are in the final part of the path
            $tmp_path = $path;
        }

        if (!empty($tmp_folders_titles[$tmp_path])) {
            // If this path has soon been stored here we don't need a new query
            $path_displayed .= $tmp_folders_titles[$tmp_path];
        } else {
            $sql = 'SELECT title FROM '.Database::get_course_table(TABLE_DOCUMENT).' WHERE path LIKE BINARY "'.$tmp_path.'"';
            $rs = Database::query($sql);
            $tmp_title = '/'.Database::result($rs, 0, 0);
            $path_displayed .= $tmp_title;
            $tmp_folders_titles[$tmp_path] = $tmp_title;
        }
    }
    return $path_displayed;
}

/**
 * This function displays the name of the user and makes the link tothe user tool.
 *
 * @param $user_id
 * @param $name
 * @return a link to the userInfo.php
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function display_user_link_document($user_id, $name) {
    if ($user_id != 0) {
        return '<a href="../user/userInfo.php?uInfo='.$user_id.'">'.$name.'</a>';
    } else {
        return get_lang('Anonymous');
    }
}
/**
 * Creates form that asks for the directory name.
 * @return string	html-output text for the form
 */
function create_dir_form($current_dir_id) {
    global $document_id;
    $new_folder_text = '<form action="'.api_get_self().'" method="post">';
    $new_folder_text .= '<input type="hidden" name="dir_id" value="'.intval($document_id).'" />';
    $new_folder_text .= '<input type="hidden" name="id" value="'.intval($current_dir_id).'" />';

    // Form title
    $new_folder_text .= '<div class="row"><div class="form_header">'.get_lang('CreateDir').'</div></div>';

    // Folder field
    $new_folder_text .= '<div class="row">';
    $new_folder_text .= '<div class="label"><span class="form_required">*</span>'.get_lang('NewDir').'</div>';
    $new_folder_text .= '<div class="formw"><input type="text" name="dirname" /></div>';
    $new_folder_text .= '</div>';

    // Submit button
    $new_folder_text .= '<div class="row">';
    $new_folder_text .= '<div class="label">&nbsp;</div>';
    $new_folder_text .= '<div class="formw"><button type="submit" class="add" name="create_dir">'.get_lang('CreateFolder').'</button></div>';
    $new_folder_text .= '</div>';
    $new_folder_text .= '</form>';
    $new_folder_text .= '<div style="clear: both; margin-bottom: 10px;"></div>';

    return $new_folder_text;
}


/**
 * Checks whether the user is in shared folder
 * @return return bool Return true when user is into shared folder
 */
function is_shared_folder($curdirpath, $current_session_id) {
    $clean_curdirpath = Security::remove_XSS($curdirpath);
    if($clean_curdirpath== '/shared_folder'){
        return true;
    }
    elseif($clean_curdirpath== '/shared_folder_session_'.$current_session_id){
        return true;
    }
    else{
        return false;
    }
}

/**
 * Checks whether the user is into any user shared folder
 * @return return bool Return true when user is in any user shared folder
 */
function is_any_user_shared_folder($path, $current_session_id) {
    $clean_path = Security::remove_XSS($path);
    if(strpos($clean_path,'shared_folder/sf_user_')){
        return true;
    }
    elseif(strpos($clean_path, 'shared_folder_session_'.$current_session_id.'/sf_user_')){
        return true;
    }
    else{
        return false;
    }
}

/**
 * Checks whether the user is into his shared folder or into a subfolder
 * @return return bool Return true when user is in his user shared folder or into a subforder
 */
function is_my_shared_folder($user_id, $path, $current_session_id) {
    $clean_path = Security::remove_XSS($path).'/';
    $main_user_shared_folder = '/shared_folder\/sf_user_'.$user_id.'\//';//for security does not remove the last slash
    $main_user_shared_folder_session='/shared_folder_session_'.$current_session_id.'\/sf_user_'.$user_id.'\//';//for security does not remove the last slash

    if (preg_match($main_user_shared_folder, $clean_path)){
        return true;
    } elseif(preg_match($main_user_shared_folder_session, $clean_path)) {
        return true;
    } else {        
        return false;
    }
}

/**
 * Check if the file name or folder searched exist
 * @return return bool Return true when exist
 */
function search_keyword($document_name, $keyword) {
    if (api_strripos($document_name, $keyword) !== false){
        return true;
    } else {
        return false;
    }
}

/**
 * Checks whether a document can be previewed by using the browser.
 * @param string $file_extension    The filename extension of the document (it must be in lower case).
 * @return bool                     Returns TRUE or FALSE.
 */
function is_browser_viewable($file_extension) {
    static $allowed_extensions = array(
        'htm', 'html', 'xhtml', 'gif', 'jpg', 'jpeg', 'png', 'pdf', 'swf', 'mp3', 'mp4', 'ogg', 'ogx', 'oga', 'ogv', 'svg',
        'txt', 'log',
        'mpg', 'mpeg',
		'wav'
    );
    if (!($result = in_array($file_extension, $allowed_extensions))) { // Assignment + a logical check.
        return false;
    }
    switch ($file_extension) {
        case 'ogg':
            return api_browser_support('ogg');
        case 'svg':
            return api_browser_support('svg');
    }
    return $result;
}