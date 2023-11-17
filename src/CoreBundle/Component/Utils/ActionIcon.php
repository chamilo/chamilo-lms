<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Component\Utils;

enum ActionIcon: string
{
    // Add
    case ADD = 'plus-box';
    // Edit
    case EDIT = 'pencil';
    // Delete
    case DELETE = 'delete';
    // Configure
    case CONFIGURE = 'hammer-wrench';
    // Download
    case DOWNLOAD = 'download';
    // Download multiple items
    case DOWNLOAD_MULTIPLE = 'download-box';
    // Upload
    case UPLOAD = 'upload';
    // Go back one page
    case BACK = 'arrow-left-bold-box';
    // Assign groups of users to some resource
    case ASSIGN_GROUP_USERS_TO_RESOURCE = 'account-multiple-plus';
    // Handle to move an element by drag & drop
    case MOVE_DRAG_DROP = 'cursor-move';
    // Move backward one page (learning paths)
    case LP_MOVE_BACKWARD = 'chevron-left';
    // Move forward one page (learning paths)
    case LP_MOVE_FORWARD = 'chevron-right';
    // Move something up
    case UP = 'arrow-up-bold';
    // Move something down or show some unfolded interface component
    case DOWN = 'arrow-down-bold';
    // Move something (from one folder to another) or unfold some interface component
    case MOVE = 'arrow-right-bold';
    // Preview some content
    case CONTENT_PREVIEW = 'magnify-plus-outline';
    // Import some kind of archive/packaged
    case ARCHIVE_IMPORT = 'archive-arrow-up';
    // Create a category
    case CATEGORY_CREATE = 'folder-multiple-plus';
    // Create a folder
    case FOLDER_CREATE = 'folder-plus';
    // Alert the user of something important/unexpected/undesired
    case ALERT = 'alert';
    // Inform of something completed
    case INFORM = 'checkbox-marked';
    // Crossed pencil to show the inability to edit for the current user
    case EDIT_OFF = 'pencil-off';
    // Visible state
    case VISIBLE = 'eye';
    // Invisible state
    case INVISIBLE = 'eye-off';
    // Hide from course homepage (unpublish)
    case COURSE_TOOL_UNPUBLISHED = 'checkbox-multiple-blank';
    // Show on course homepage
    case COURSE_TOOL_PUBLISHED = 'checkbox-multiple-blank-outline';
    // Disable multiple attempts (or show multiple attempts are currently enabled)
    case MULTIPLE_ATTEMPTS_DISABLE = 'sync';
    // Enable multiple attempts (or show multiple attempts are currently disabled)
    case MULTIPLE_ATTEMPTS_ENABLE = 'sync-circle';
    // Display mode 1
    case DISPLAY_MODE_1 = 'fullscreen';
    // Display mode 2
    case LP_DISPLAY_MODE_2 = 'fullscreen-exit';
    // Display mode 3
    case LP_DISPLAY_MODE_3 = 'overscan';
    // Display mode 4
    case LP_DISPLAY_4 = 'play-box-outline';
    // Equivalent to fullscreen-exit?
    case LP_FULLSCREEN_EXIT = 'fit-to-screen';
    // Enable debug
    case DEBUG_ENABLE = 'bug-check';
    // Disable debug
    case DEBUG_DISABLE = 'bug-outline';
    // Export in some type of archive/package
    case ARCHIVE_EXPORT = 'archive-arrow-down';
    // Copy content
    case CONTENT_COPY = 'text-box-plus';
    // Enable/Disable auto-launch of some content
    case AUTO_LAUNCH = 'rocket-launch';
    // Export to PDF
    case EXPORT_PDF = 'file-pdf-box';
    // CSV export
    case EXPORT_CSV = 'file-delimited-outline';
    // Export to Excel
    case EXPORT_SPREADSHEET = 'microsoft-excel';
    // Export to Document
    case EXPORT_DOC = 'microsoft-word';
    // Save the current form
    case FORM_SAVE = 'content-save';
    // Send a message
    case MESSAGE_SEND = 'send';
    // Add an attachment
    case ATTACHMENT_ADD = 'file-plus';
    // ?
    //case CLOUD_UPLOAD = 'cloud-upload';
    // Three vertical dots to indicate the possibility to extend a menu/set of options
    case VERTICAL_DOTS = 'dots-vertical';
    // Information icon - Get more info
    case INFORMATION = 'information';
    // Login as
    case LOGIN_AS = 'account-key';
    // Take backup
    case TAKE_BACKUP = 'cloud-download';
    // Restore backup
    case RESTORE_BACKUP = 'cloud-upload';
    // Print
    case PRINT = 'printer';
    // See details/View details
    case DETAILS = 'fast-forward-outline';
    // Clean/Reset
    case RESET = 'broom';
    // Add audio
    case AUDIO_ADD = 'music-note-plus';
    // Collapse/Contract
    case COLLAPSE = 'arrow-collapse-all';
    // Expand
    case EXPAND = 'arrow-expand-all';
    // Give a grade to some work
    case GRADE = 'checkbox-marked-circle-plus-outline';
    // Lock
    case LOCK = 'lock';
    // Unlock
    case UNLOCK = 'lock-open-variant';
    // Refresh/reload
    case REFRESH = 'file-document-refresh';
}
