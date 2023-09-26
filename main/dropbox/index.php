<?php

/* For licensing terms, see /license.txt */

// The file that contains all the initialisation stuff (and includes all the configuration stuff)
require_once 'dropbox_init.inc.php';

$_course = api_get_course_info();

$last_access = '';
// get the last time the user accessed the tool
if (isset($_SESSION[$_course['id']]) &&
    $_SESSION[$_course['id']]['last_access'][TOOL_DROPBOX] == ''
) {
    $last_access = get_last_tool_access(TOOL_DROPBOX);
    $_SESSION[$_course['id']]['last_access'][TOOL_DROPBOX] = $last_access;
} else {
    if (isset($_SESSION[$_course['id']])) {
        $last_access = $_SESSION[$_course['id']]['last_access'][TOOL_DROPBOX];
    }
}

$postAction = isset($_POST['action']) ? $_POST['action'] : null;
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : null;
$view = isset($_GET['view']) ? Security::remove_XSS($_GET['view']) : null;
$viewReceivedCategory = isset($_GET['view_received_category']) ? Security::remove_XSS($_GET['view_received_category']) : null;
$viewSentCategory = isset($_GET['view_sent_category']) ? Security::remove_XSS($_GET['view_sent_category']) : null;
$showSentReceivedTabs = true;

// Do the tracking
Event::event_access_tool(TOOL_DROPBOX);

$logInfo = [
    'tool' => TOOL_DROPBOX,
    'tool_id' => 0,
    'tool_id_detail' => 0,
    'action' => $action,
];
Event::registerLog($logInfo);

/*	DISPLAY SECTION */
Display::display_introduction_section(TOOL_DROPBOX);

// Build URL-parameters for table-sorting
$sort_params = [];
if (isset($_GET['dropbox_column'])) {
    $sort_params[] = 'dropbox_column='.intval($_GET['dropbox_column']);
}
if (isset($_GET['dropbox_page_nr'])) {
    $sort_params[] = 'page_nr='.intval($_GET['dropbox_page_nr']);
}
if (isset($_GET['dropbox_per_page'])) {
    $sort_params[] = 'dropbox_per_page='.intval($_GET['dropbox_per_page']);
}
if (isset($_GET['dropbox_direction']) && in_array($_GET['dropbox_direction'], ['ASC', 'DESC'])) {
    $sort_params[] = 'dropbox_direction='.$_GET['dropbox_direction'];
}

$sort_params = Security::remove_XSS(implode('&', $sort_params));

// Display the form for adding a new dropbox item.
if (in_array($action, ['add', 'send_other_users'])) {
    if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
        api_not_allowed();
    }
    $dropboxId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    display_add_form(
        $viewReceivedCategory,
        $viewSentCategory,
        $view,
        $dropboxId,
        $action
    );
}

if (isset($_POST['submitWork'])) {
    $check = Security::check_token();
    if ($check) {
        store_add_dropbox();

        echo Display::getFlashToString();
    }
}

// Display the form for adding a category
if ($action == 'addreceivedcategory' || $action == 'addsentcategory') {
    if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
        api_not_allowed();
    }
    $categoryName = isset($_POST['category_name']) ? $_POST['category_name'] : '';
    display_addcategory_form($categoryName, '', $_GET['action']);
}

// Editing a category: displaying the form
if ($action == 'editcategory' && isset($_GET['id'])) {
    if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
        api_not_allowed();
    }
    if (!$_POST) {
        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }
        display_addcategory_form('', $_GET['id'], 'editcategory');
    }
}

// Storing a new or edited category
if (isset($_POST['StoreCategory'])) {
    if (api_get_session_id() != 0 &&
        !api_is_allowed_to_session_edit(false, true)
    ) {
        api_not_allowed();
    }
    $return_information = store_addcategory();
    if ($return_information['type'] == 'confirmation') {
        echo Display::return_message($return_information['message'], 'confirmation');
    }
    if ($return_information['type'] == 'error') {
        echo Display::return_message(
            get_lang('FormHasErrorsPleaseComplete').'<br />'.$return_information['message'],
            'error'
        );
        display_addcategory_form($_POST['category_name'], $_POST['edit_id'], $postAction);
    }
}

// Move a File
if (($action == 'movesent' || $action == 'movereceived') && isset($_GET['move_id'])) {
    if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
        api_not_allowed();
    }
    display_move_form(
        str_replace('move', '', $action),
        $_GET['move_id'],
        get_dropbox_categories(str_replace('move', '', $action)),
        $sort_params,
        $viewReceivedCategory,
        $viewSentCategory,
        $view
    );
}
if (isset($_POST['do_move'])) {
    $result = store_move(
        $_POST['id'],
        $_POST['move_target'],
        $_POST['part']
    );
    echo Display::return_message(
        $result,
        'confirm'
    );
}

// Delete a file
if (($action == 'deletereceivedfile' || $action == 'deletesentfile') && isset($_GET['id']) && is_numeric($_GET['id'])) {
    if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
        api_not_allowed();
    }
    $dropboxfile = new Dropbox_Person(
        api_get_user_id(),
        $is_courseAdmin,
        $is_courseTutor
    );
    if ($action == 'deletereceivedfile') {
        $dropboxfile->deleteReceivedWork($_GET['id']);
        $message = get_lang('ReceivedFileDeleted');
    }
    if ($action == 'deletesentfile') {
        $dropboxfile->deleteSentWork($_GET['id']);
        $message = get_lang('SentFileDeleted');
    }
    echo Display::return_message($message, 'confirmation');
}

// Delete a category
if (($action == 'deletereceivedcategory' || $action == 'deletesentcategory') &&
    isset($_GET['id']) && is_numeric($_GET['id'])
) {
    if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
        api_not_allowed();
    }
    $message = delete_category($action, $_GET['id']);
    echo Display::return_message($message, 'confirmation');
}

// Do an action on multiple files
// only the download has is handled separately in
// dropbox_init_inc.php because this has to be done before the headers are sent
// (which also happens in dropbox_init.inc.php
if (!isset($_POST['feedback']) && (
    strstr($postAction, 'move_received') ||
    strstr($postAction, 'move_sent') ||
    $postAction == 'delete_received' ||
    $postAction == 'download_received' ||
    $postAction == 'delete_sent' ||
    $postAction == 'download_sent'
)
) {
    $display_message = handle_multiple_actions();
    echo Display::return_message($display_message, 'normal');
}

// Store Feedback
if (isset($_POST['feedback'])) {
    if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
        api_not_allowed();
    }
    $check = Security::check_token();
    if ($check) {
        $display_message = store_feedback();
        echo Display::return_message($display_message, 'normal');
        Security::check_token();
    }
}

// Error Message
if (isset($_GET['error']) && !empty($_GET['error'])) {
    echo Display::return_message(get_lang($_GET['error']), 'normal');
}

$dropbox_data_sent = [];
$movelist = [];
$dropbox_data_recieved = [];

if (!in_array($action, ['add', 'send_other_users'])) {
    // Getting all the categories in the dropbox for the given user
    $dropbox_categories = get_dropbox_categories();
    // Greating the arrays with the categories for the received files and for the sent files
    foreach ($dropbox_categories as $category) {
        if ($category['received'] == '1') {
            $dropbox_received_category[] = $category;
        }
        if ($category['sent'] == '1') {
            $dropbox_sent_category[] = $category;
        }
    }

    // ACTIONS
    if ($view == 'received' || !$showSentReceivedTabs) {
        // This is for the categories
        if (isset($viewReceivedCategory) && $viewReceivedCategory != '') {
            $view_dropbox_category_received = $viewReceivedCategory;
        } else {
            $view_dropbox_category_received = 0;
        }
        /* Menu Received */

        if (api_get_session_id() == 0) {
            echo '<div class="actions">';
            if ($view_dropbox_category_received != 0 && api_is_allowed_to_session_edit(false, true)) {
                echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category=0&view_sent_category='.$viewSentCategory.'&view='.$view.'">'.
                    Display::return_icon('folder_up.png', get_lang('Up').' '.get_lang('Root'), '', ICON_SIZE_MEDIUM).
                    "</a>";
                echo get_lang('Category').': <strong>'.Security::remove_XSS($dropbox_categories[$view_dropbox_category_received]['cat_name']).'</strong> ';
                $movelist[0] = 'Root'; // move_received selectbox content
            } else {
                echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=addreceivedcategory&view='.$view.'">'.
                    Display::return_icon('new_folder.png', get_lang('AddNewCategory'), '', ICON_SIZE_MEDIUM).'</a>';
            }
            echo '</div>';
        } else {
            if (api_is_allowed_to_session_edit(false, true)) {
                echo '<div class="actions">';
                if ($view_dropbox_category_received != 0 && api_is_allowed_to_session_edit(false, true)) {
                    echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category=0&view_sent_category='.$viewSentCategory.'&view='.$view.'">'.
                        Display::return_icon('folder_up.png', get_lang('Up').' '.get_lang('Root'), '', ICON_SIZE_MEDIUM)."</a>";
                    echo get_lang('Category').': <strong>'.Security::remove_XSS($dropbox_categories[$view_dropbox_category_received]['cat_name']).'</strong> ';
                    $movelist[0] = 'Root'; // move_received selectbox content
                } else {
                    echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=addreceivedcategory&view='.$view.'">'.
                        Display::return_icon('new_folder.png', get_lang('AddNewCategory'), '', ICON_SIZE_MEDIUM).
                        '</a>';
                }
                echo '</div>';
            }
        }
    }

    if (!$view || $view == 'sent' || !$showSentReceivedTabs) {
        // This is for the categories
        if (isset($viewSentCategory) && $viewSentCategory != '') {
            $view_dropbox_category_sent = $viewSentCategory;
        } else {
            $view_dropbox_category_sent = 0;
        }

        /* Menu Sent */
        if (api_get_session_id() == 0) {
            echo '<div class="actions">';
            if (empty($viewSentCategory)) {
                echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&view=".$view."&action=add\">".
                    Display::return_icon('upload_file.png', get_lang('UploadNewFile'), '', ICON_SIZE_MEDIUM).
                    "</a>";
            }
            if ($view_dropbox_category_sent != 0) {
                echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category=0&view='.$view.'">'.
                    Display::return_icon('folder_up.png', get_lang('Up').' '.get_lang('Root'), '', ICON_SIZE_MEDIUM).
                    "</a>";
                echo get_lang('Category').': <strong>'.Security::remove_XSS($dropbox_categories[$view_dropbox_category_sent]['cat_name']).'</strong> ';
            } else {
                echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&view=".$view."&action=addsentcategory\">".
                    Display::return_icon('new_folder.png', get_lang('AddNewCategory'), '', ICON_SIZE_MEDIUM)."</a>\n";
            }
            echo '</div>';
        } else {
            if (api_is_allowed_to_session_edit(false, true)) {
                echo '<div class="actions">';
                if (empty($viewSentCategory)) {
                    echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&view=".$view."&action=add\">".
                        Display::return_icon('upload_file.png', get_lang('UploadNewFile'), '', ICON_SIZE_MEDIUM).
                    "</a>";
                }
                if ($view_dropbox_category_sent != 0) {
                    echo get_lang('CurrentlySeeing').': <strong>'.Security::remove_XSS($dropbox_categories[$view_dropbox_category_sent]['cat_name']).'</strong> ';
                    echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category=0&view='.$view.'">'.
                        Display::return_icon('folder_up.png', get_lang('Up').' '.get_lang('Root'), '', ICON_SIZE_MEDIUM).
                        "</a>";
                } else {
                    echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&view=".$view."&action=addsentcategory\">".
                        Display::return_icon('new_folder.png', get_lang('AddNewCategory'), '', ICON_SIZE_MEDIUM)."</a>\n";
                }
                echo '</div>';
            }
        }
    }
    /*	THE MENU TABS */
    if ($showSentReceivedTabs) {
        ?>
<ul class="nav nav-tabs">
    <li <?php if (!$view || $view == 'sent') {
            echo 'class="active"';
        } ?> >
        <a href="<?php echo api_get_path(WEB_CODE_PATH).'dropbox/'; ?>index.php?<?php echo api_get_cidreq(); ?>&view=sent" >
            <?php echo get_lang('SentFiles'); ?>
        </a>
    </li>
    <li <?php if ($view == 'received') {
            echo 'class="active"';
        } ?> >
        <a href="<?php echo api_get_path(WEB_CODE_PATH).'dropbox/'; ?>index.php?<?php echo api_get_cidreq(); ?>&view=received"  >
            <?php echo get_lang('ReceivedFiles'); ?></a>
    </li>
</ul>
<?php
    }
    /*	RECEIVED FILES */
    if ($view == 'received' || !$showSentReceivedTabs) {
        // This is for the categories
        if (isset($viewReceivedCategory) && $viewReceivedCategory != '') {
            $view_dropbox_category_received = $viewReceivedCategory;
        } else {
            $view_dropbox_category_received = 0;
        }

        // Object initialisation
        $dropbox_person = new Dropbox_Person(api_get_user_id(), $is_courseAdmin, $is_courseTutor);
        // note: are the $is_courseAdmin and $is_courseTutor parameters needed????

        // Constructing the array that contains the total number of feedback messages per document.
        $number_feedback = get_total_number_feedback();

        // Sorting and paging options
        $sorting_options = [];
        $paging_options = [];

        // The headers of the sortable tables
        $column_header = [];
        $column_header[] = ['', false, ''];
        $column_header[] = [get_lang('Type'), true, 'style="width:40px"', 'style="text-align:center"'];
        $column_header[] = [get_lang('ReceivedTitle'), true, ''];
        $column_header[] = [get_lang('Size'), true, ''];
        $column_header[] = [get_lang('Authors'), true, ''];
        $column_header[] = [get_lang('LastResent'), true];

        if (api_get_session_id() == 0) {
            $column_header[] = [get_lang('Modify'), false, '', 'nowrap style="text-align: right"'];
        } elseif (api_is_allowed_to_session_edit(false, true)) {
            $column_header[] = [get_lang('Modify'), false, '', 'nowrap style="text-align: right"'];
        }

        $column_header[] = ['RealDate', true];
        $column_header[] = ['RealSize', true];

        // An array with the setting of the columns -> 1: columns that we will show, 0:columns that will be hide
        $column_show[] = 1;
        $column_show[] = 1;
        $column_show[] = 1;
        $column_show[] = 1;
        $column_show[] = 1;
        $column_show[] = 1;

        if (api_get_session_id() == 0) {
            $column_show[] = 1;
        } elseif (api_is_allowed_to_session_edit(false, true)) {
            $column_show[] = 1;
        }
        $column_show[] = 0;

        // Here we change the way how the columns are going to be sort
        // in this case the the column of LastResent ( 4th element in $column_header) we will be order like the column RealDate
        // because in the column RealDate we have the days in a correct format "2008-03-12 10:35:48"
        $column_order[3] = 8;
        $column_order[5] = 7;
        // The content of the sortable table = the received files
        foreach ($dropbox_person->receivedWork as $dropbox_file) {
            $dropbox_file_data = [];
            if ($view_dropbox_category_received == $dropbox_file->category) {
                // we only display the files that are in the category that we are in.
                $dropbox_file_data[] = $dropbox_file->id;

                if (isset($_SESSION['_seen']) && !is_array($_SESSION['_seen'][$_course['id']][TOOL_DROPBOX])) {
                    $_SESSION['_seen'][$_course['id']][TOOL_DROPBOX] = [];
                }

                // New icon
                $new_icon = '';
                if (isset($_SESSION['_seen'])) {
                    if ($dropbox_file->last_upload_date > $last_access &&
                        !in_array(
                            $dropbox_file->id,
                            $_SESSION['_seen'][$_course['id']][TOOL_DROPBOX]
                        )
                    ) {
                        $new_icon = '&nbsp;'.Display::return_icon(
                            'new_dropbox_message.png',
                            get_lang('New'),
                            '',
                            ICON_SIZE_SMALL
                        );
                    }
                }

                $link_open = '<a href="'.api_get_path(WEB_CODE_PATH).'dropbox/dropbox_download.php?'.api_get_cidreq().'&id='.$dropbox_file->id.'">';
                $dropbox_file_data[] = $link_open.DocumentManager::build_document_icon_tag('file', $dropbox_file->title).'</a>';
                $dropbox_file_data[] = '<a href="'.api_get_path(WEB_CODE_PATH).'dropbox/dropbox_download.php?'.api_get_cidreq().'&id='.$dropbox_file->id.'&action=download">'.
                    Display::return_icon('save.png', get_lang('Download'), ['style' => 'float:right;'], ICON_SIZE_SMALL).
                    '</a>'.$link_open.$dropbox_file->title.'</a>'.$new_icon.'<br />'.$dropbox_file->description;
                $file_size = $dropbox_file->filesize;
                $dropbox_file_data[] = format_file_size($file_size);
                $authorInfo = api_get_user_info($dropbox_file->uploader_id);
                if ($authorInfo) {
                    $dropbox_file_data[] = $authorInfo['complete_name'];
                } else {
                    $dropbox_file_data[] = '';
                }

                $lastUploadDate = Display::dateToStringAgoAndLongDate($dropbox_file->last_upload_date);
                $dropbox_file_data[] = $lastUploadDate;

                $action_icons = check_number_feedback($dropbox_file->id, $number_feedback).' '.get_lang('Feedback').'
                <a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&action=viewfeedback&id='.$dropbox_file->id.'&'.$sort_params.'">'.
                    Display::return_icon('discuss.png', get_lang('Comment'), '', ICON_SIZE_SMALL).'</a>
                <a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&action=movereceived&move_id='.$dropbox_file->id.'&'.$sort_params.'">'.
                    Display::return_icon('move.png', get_lang('Move'), '', ICON_SIZE_SMALL).'</a>
                <a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&action=deletereceivedfile&id='.$dropbox_file->id.'&'.$sort_params.'" onclick="javascript: return confirmation(\''.$dropbox_file->title.'\');">'.
                    Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).
                '</a>';

                // This is a hack to have an additional row in a sortable table
                if ($action == 'viewfeedback' && isset($_GET['id']) && is_numeric($_GET['id']) && $dropbox_file->id == $_GET['id']) {
                    $action_icons .= "</td></tr>"; // Ending the normal row of the sortable table
                    $url = api_get_path(WEB_CODE_PATH).'dropbox/index.php?"'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory."&view_sent_category=".$viewSentCategory."&view=".$view.'&'.$sort_params;
                    $action_icons .= "
                        <tr>
                        <td colspan=\"9\">".
                        feedback($dropbox_file->feedback2, $url).
                        "</td></tr>";
                }
                if (api_get_session_id() == 0) {
                    $dropbox_file_data[] = $action_icons;
                } elseif (api_is_allowed_to_session_edit(false, true)) {
                    $dropbox_file_data[] = $action_icons;
                }
                $action_icons = '';
                $dropbox_file_data[] = $lastUploadDate;
                $dropbox_file_data[] = $file_size;
                $dropbox_data_recieved[] = $dropbox_file_data;
            }
        }

        // The content of the sortable table = the categories (if we are not in the root)
        if ($view_dropbox_category_received == 0) {
            foreach ($dropbox_categories as $category) {
                /*  Note: This can probably be shortened since the categories
                for the received files are already in the
                $dropbox_received_category array;*/
                $dropbox_category_data = [];
                if ($category['received'] == '1') {
                    $movelist[$category['cat_id']] = $category['cat_name'];
                    // This is where the checkbox icon for the files appear
                    $dropbox_category_data[] = $category['cat_id'];
                    // The icon of the category
                    $link_open = '<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$category['cat_id'].'&view_sent_category='.$viewSentCategory.'&view='.$view.'">';
                    $dropbox_category_data[] = $link_open.DocumentManager::build_document_icon_tag('folder', $category['cat_name']).'</a>';
                    $dropbox_category_data[] =
                        '<a href="'.api_get_path(WEB_CODE_PATH).'dropbox/dropbox_download.php?'.api_get_cidreq().'&cat_id='.$category['cat_id'].'&action=downloadcategory&sent_received=received">'.
                        Display::return_icon('save_pack.png', get_lang('Save'), ['style' => 'float:right;'], ICON_SIZE_SMALL).'</a>'.$link_open.$category['cat_name'].'</a>';
                    $dropbox_category_data[] = '';
                    $dropbox_category_data[] = '';
                    $dropbox_category_data[] = '';
                    $dropbox_category_data[] =
                        '<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&action=editcategory&id='.$category['cat_id'].'">'.
                        Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>
                        <a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&action=deletereceivedcategory&id='.$category['cat_id'].'" onclick="javascript: return confirmation(\''.Security::remove_XSS($category['cat_name']).'\');">'.
                        Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                }
                if (is_array($dropbox_category_data) && count($dropbox_category_data) > 0) {
                    $dropbox_data_recieved[] = $dropbox_category_data;
                }
            }
        }

        // Displaying the table
        $additional_get_parameters = [
            'view' => $view,
            'view_received_category' => $viewReceivedCategory,
            'view_sent_category' => $viewSentCategory,
        ];
        $selectlist = [
            'delete_received' => get_lang('Delete'),
            'download_received' => get_lang('Download'),
        ];

        if (is_array($movelist)) {
            foreach ($movelist as $catid => $catname) {
                $selectlist['move_received_'.$catid] = get_lang('Move').'->'.Security::remove_XSS($catname);
            }
        }

        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            $selectlist = [];
        }
        echo '<div class="files-table">';
        Display::display_sortable_config_table(
            'dropbox',
            $column_header,
            $dropbox_data_recieved,
            $sorting_options,
            $paging_options,
            $additional_get_parameters,
            $column_show,
            $column_order,
            $selectlist
        );
        echo '</div>';
    }

    /*	SENT FILES */
    if (!$view || $view == 'sent' || !$showSentReceivedTabs) {
        // This is for the categories
        if (isset($viewSentCategory) && $viewSentCategory != '') {
            $view_dropbox_category_sent = $viewSentCategory;
        } else {
            $view_dropbox_category_sent = 0;
        }

        // Object initialisation
        $dropbox_person = new Dropbox_Person(api_get_user_id(), $is_courseAdmin, $is_courseTutor);
        // Constructing the array that contains the total number of feedback messages per document.
        $number_feedback = get_total_number_feedback();
        // Sorting and paging options
        $sorting_options = [];
        $paging_options = [];
        // The headers of the sortable tables
        $column_header = [];
        $column_header[] = ['', false, ''];
        $column_header[] = [get_lang('Type'), true, 'style="width:40px"', 'style="text-align:center"'];
        $column_header[] = [get_lang('SentTitle'), true, ''];
        $column_header[] = [get_lang('Size'), true, ''];
        $column_header[] = [get_lang('SentTo'), true, ''];
        $column_header[] = [get_lang('LastResent'), true, ''];

        if (api_get_session_id() == 0) {
            $column_header[] = [get_lang('Modify'), false, '', 'nowrap style="text-align: right"'];
        } elseif (api_is_allowed_to_session_edit(false, true)) {
            $column_header[] = [get_lang('Modify'), false, '', 'nowrap style="text-align: right"'];
        }

        $column_header[] = ['RealDate', true];
        $column_header[] = ['RealSize', true];

        $column_show = [];
        $column_order = [];

        // An array with the setting of the columns -> 1: columns that we will show, 0:columns that will be hide
        $column_show[] = 1;
        $column_show[] = 1;
        $column_show[] = 1;
        $column_show[] = 1;
        $column_show[] = 1;
        $column_show[] = 1;
        if (api_get_session_id() == 0) {
            $column_show[] = 1;
        } elseif (api_is_allowed_to_session_edit(false, true)) {
            $column_show[] = 1;
        }
        $column_show[] = 0;

        // Here we change the way how the colums are going to be sort
        // in this case the the column of LastResent ( 4th element in $column_header) we will be order like the column RealDate
        // because in the column RealDate we have the days in a correct format "2008-03-12 10:35:48"
        $column_order[3] = 8;
        $column_order[5] = 7;
        // The content of the sortable table = the received files
        foreach ($dropbox_person->sentWork as $dropbox_file) {
            $dropbox_file_data = [];
            if ($view_dropbox_category_sent == $dropbox_file->category) {
                $dropbox_file_data[] = $dropbox_file->id;
                $link_open = '<a href="'.api_get_path(WEB_CODE_PATH).'dropbox/dropbox_download.php?'.api_get_cidreq().'&id='.$dropbox_file->id.'">';
                $dropbox_file_data[] = $link_open.DocumentManager::build_document_icon_tag('file', $dropbox_file->title).'</a>';
                $dropbox_file_data[] = '<a href="'.api_get_path(WEB_CODE_PATH).'dropbox/dropbox_download.php?'.api_get_cidreq().'&id='.$dropbox_file->id.'&action=download">'.
                    Display::return_icon('save.png', get_lang('Save'), ['style' => 'float:right;'], ICON_SIZE_SMALL).
                    '</a>'.
                    $link_open.
                    $dropbox_file->title.
                    '</a><br />'.$dropbox_file->description;
                $file_size = $dropbox_file->filesize;
                $dropbox_file_data[] = format_file_size($file_size);
                $receivers_celldata = '';
                foreach ($dropbox_file->recipients as $recipient) {
                    if (isset($recipient['user_id'])) {
                        $userInfo = api_get_user_info($recipient['user_id']);
                        $receivers_celldata = UserManager::getUserProfileLink($userInfo).', '.$receivers_celldata;
                    }
                }
                $receivers_celldata = trim(trim($receivers_celldata), ','); // Removing the trailing comma.
                $dropbox_file_data[] = $receivers_celldata;

                $lastUploadDate = Display::dateToStringAgoAndLongDate($dropbox_file->last_upload_date);
                $dropbox_file_data[] = $lastUploadDate;
                $receivers_celldata = '';

                $action_icons = check_number_feedback($dropbox_file->id, $number_feedback).' '.get_lang('Feedback').'
                    <a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&action=viewfeedback&id='.$dropbox_file->id.'&'.$sort_params.'">'.
                        Display::return_icon('discuss.png', get_lang('Comment'), '', ICON_SIZE_SMALL).
                    '</a>
                    <a href="'.api_get_path(WEB_CODE_PATH).'dropbox/update.php?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&action=update&id='.$dropbox_file->id.'&'.$sort_params.'">'.
                        Display::return_icon('upload_file.png', get_lang('Update'), '', ICON_SIZE_SMALL).
                    '</a>
                    <a href="'.api_get_path(WEB_CODE_PATH).'dropbox/index.php?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&action=send_other_users&id='.$dropbox_file->id.'&'.$sort_params.'">'.
                    Display::return_icon('addworkuser.png', get_lang('SendOtherUsers'), '', ICON_SIZE_SMALL).
                    '</a>
                    <a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&action=movesent&move_id='.$dropbox_file->id.'&'.$sort_params.'">'.
                        Display::return_icon('move.png', get_lang('Move'), '', ICON_SIZE_SMALL).'
                    </a>
                    <a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&action=deletesentfile&id='.$dropbox_file->id.'&'.$sort_params.'" onclick="javascript: return confirmation(\''.$dropbox_file->title.'\');">'.
                        Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).
                    '</a>';
                // This is a hack to have an additional row in a sortable table
                if ($action == 'viewfeedback' && isset($_GET['id']) && is_numeric($_GET['id']) && $dropbox_file->id == $_GET['id']) {
                    $action_icons .= "</td></tr>\n"; // ending the normal row of the sortable table
                    $action_icons .= "<tr><td colspan=\"9\">";
                    $url = api_get_path(WEB_CODE_PATH)."dropbox/index.php?".api_get_cidreq()."&view_received_category=".$viewReceivedCategory."&view_sent_category=".$viewSentCategory."&view=".$view.'&'.$sort_params;
                    $action_icons .= feedback($dropbox_file->feedback2, $url);
                    //$action_icons .= "<a class=\"btn btn-default\" href=\""><i class=\"fa fa-times\" aria-hidden=\"true\"></i></a>";
                    $action_icons .= "</tr>";
                }
                $dropbox_file_data[] = $action_icons;
                $dropbox_file_data[] = $lastUploadDate;
                $dropbox_file_data[] = $file_size;
                $action_icons = '';
                $dropbox_data_sent[] = $dropbox_file_data;
            }
        }

        $moveList = [];
        // The content of the sortable table = the categories (if we are not in the root)
        if ($view_dropbox_category_sent == 0) {
            foreach ($dropbox_categories as $category) {
                $dropbox_category_data = [];
                if ($category['sent'] == '1') {
                    $moveList[$category['cat_id']] = $category['cat_name'];
                    $dropbox_category_data[] = $category['cat_id'];
                    // This is where the checkbox icon for the files appear.
                    $link_open = '<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category='.$category['cat_id'].'&view='.$view.'">';
                    $dropbox_category_data[] = $link_open.DocumentManager::build_document_icon_tag('folder', Security::remove_XSS($category['cat_name'])).'</a>';
                    $dropbox_category_data[] = '<a href="'.api_get_path(WEB_CODE_PATH).'dropbox/dropbox_download.php?'.api_get_cidreq().'&cat_id='.$category['cat_id'].'&action=downloadcategory&sent_received=sent">'.
                        Display::return_icon('save_pack.png', get_lang('Save'), ['style' => 'float:right;'], ICON_SIZE_SMALL).'</a>'.$link_open.Security::remove_XSS($category['cat_name']).'</a>';
                    $dropbox_category_data[] = '';
                    $dropbox_category_data[] = '';
                    $dropbox_category_data[] = '';
                    $dropbox_category_data[] = '<a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&action=editcategory&id='.$category['cat_id'].'">'.
                        Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>
                        <a href="'.api_get_self().'?'.api_get_cidreq().'&view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&action=deletesentcategory&id='.$category['cat_id'].'" onclick="javascript: return confirmation(\''.Security::remove_XSS($category['cat_name']).'\');">'.
                        Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                }
                if (is_array($dropbox_category_data) && count($dropbox_category_data) > 0) {
                    $dropbox_data_sent[] = $dropbox_category_data;
                }
            }
        }

        // Displaying the table
        $additional_get_parameters = [
            'view' => $view,
            'view_received_category' => $viewReceivedCategory,
            'view_sent_category' => $viewSentCategory,
        ];

        $selectlist = [
            'delete_received' => get_lang('Delete'),
            'download_received' => get_lang('Download'),
        ];

        if (!empty($moveList)) {
            foreach ($moveList as $catid => $catname) {
                $selectlist['move_sent_'.$catid] = get_lang('Move').'->'.Security::remove_XSS($catname);
            }
        }

        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            $selectlist = ['download_received' => get_lang('Download')];
        }

        echo '<div class="files-table">';
        Display::display_sortable_config_table(
            'dropbox',
            $column_header,
            $dropbox_data_sent,
            $sorting_options,
            $paging_options,
            $additional_get_parameters,
            $column_show,
            $column_order,
            $selectlist
        );
        echo '</div>';
    }
}

Display::display_footer();
