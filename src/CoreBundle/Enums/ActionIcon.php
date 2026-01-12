<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Enums;

enum ActionIcon: string
{
    // Add
    case ADD = 'plus-box';
    // Edit
    case EDIT = 'pencil';
    // Delete
    case DELETE = 'delete';
    // Reject (not same as delete)
    case REJECT = 'close-octagon-outline';
    // Accept
    case ACCEPT = 'clipboard-check';
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
    case SUBSCRIBE_GROUP_USERS_TO_RESOURCE = 'account-multiple-plus';
    // Handle to move an element by drag & drop
    case MOVE_DRAG_DROP = 'cursor-move';
    // Move backward one page (learning paths)
    case MOVE_LP_BACKWARD = 'chevron-left';
    // Move forward one page (learning paths)
    case MOVE_LP_FORWARD = 'chevron-right';
    // Move something up
    case UP = 'arrow-up-bold';
    // Move something down or show some unfolded interface component
    case DOWN = 'arrow-down-bold';
    // Move something (from one folder to another) or unfold some interface component
    case MOVE = 'arrow-right-bold';
    // Previous step
    case PREVIOUS = 'arrow-left-bold-circle-outline';
    // Next step
    case NEXT = 'arrow-right-bold-circle-outline';
    // Preview some content
    case PREVIEW_CONTENT = 'magnify-plus-outline';
    // Import some kind of archive/packaged
    case IMPORT_ARCHIVE = 'archive-arrow-up';
    // Create a category
    case CREATE_CATEGORY = 'folder-multiple-plus';
    // Create a folder
    case CREATE_FOLDER = 'folder-plus';
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
    case UNPUBLISH_COURSE_TOOL = 'checkbox-multiple-blank';
    // Show on course homepage
    case PUBLISH_COURSE_TOOL = 'checkbox-multiple-blank-outline';
    // Disable multiple attempts (or show multiple attempts are currently enabled)
    case DISABLE_MULTIPLE_ATTEMPTS = 'sync';
    // Enable multiple attempts (or show multiple attempts are currently disabled)
    case ENABLE_MULTIPLE_ATTEMPTS = 'sync-circle';
    // Display mode 1
    case SET_DISPLAY_MODE_1 = 'fullscreen';
    // Display mode 2
    case SET_DISPLAY_MODE_2 = 'fullscreen-exit';
    // Display mode 3
    case SET_DISPLAY_MODE_3 = 'overscan';
    // Display mode 4
    case SET_DISPLAY_MODE_4 = 'play-box-outline';
    // Equivalent to fullscreen-exit?
    case EXIT_FULLSCREEN = 'fit-to-screen';
    // Enable debug
    case ENABLE_DEBUG = 'bug-check';
    // Disable debug
    case DISABLE_DEBUG = 'bug-outline';
    // Export in some type of archive/package
    case EXPORT_ARCHIVE = 'archive-arrow-down';
    // Copy content
    case COPY_CONTENT = 'text-box-plus';
    // Enable/Disable auto-launch of some content
    case AUTOLAUNCH = 'rocket-launch';
    // Export to PDF
    case EXPORT_PDF = 'file-pdf-box';
    // CSV export
    case EXPORT_CSV = 'file-delimited-outline';
    // Export to Excel
    case EXPORT_SPREADSHEET = 'microsoft-excel';
    // Export to Document
    case EXPORT_DOC = 'microsoft-word';
    // Save the current form
    case SAVE_FORM = 'content-save';
    // Send a message
    case SEND_MESSAGE = 'send';
    // Send all examsheets by email
    case SEND_ALL_EMAILS = 'email-multiple';
    // Send a single examsheet by email
    case SEND_SINGLE_EMAIL = 'email-outline';
    // Add an attachment
    case ADD_ATTACHMENT = 'paperclip-plus';
    // Three vertical dots to indicate extended menu
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
    case VIEW_DETAILS = 'fast-forward-outline';
    // Clean/Reset
    case RESET = 'broom';
    // Add audio
    case ADD_AUDIO = 'music-note-plus';
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
    // Add user
    case ADD_USER = 'account-plus';
    // Fill something
    case FILL = 'format-color-fill';
    // Search / find
    case SEARCH = 'magnify';
    // Comment
    case COMMENT = 'comment-arrow-right-outline';
    // Sort alphabetically
    case SORT_ALPHA = 'sort-alphabetical-ascending';
    // Sort by date
    case SORT_DATE = 'sort-calendar-descending';
    // View more (by opposition to view less)
    case VIEW_MORE = 'unfold-more-horizontal';
    // View less (by opposition to view more)
    case VIEW_LESS = 'unfold-less-horizontal';
    // Exit/Leave (a group, a course, etc)
    case EXIT = 'exit-run';
    // Edit badges/skills
    case EDIT_BADGE = 'shield-edit-outline';
    // Import users from CSV to Access URL
    case IMPORT_USERS_TO_URL = 'file-import';
    // Remove users from Access URL using CSV
    case REMOVE_USERS_FROM_URL = 'file-remove';
    case ADD_EVENT_REMINDER = 'alarm-plus';
    case SWAP_FILE = 'file-swap';
    case ADD_FILE_VARIATION = 'file-replace';
    case CLOSE = 'close';
    case MULTI_COURSE_URL_ASSIGN = 'playlist-plus';
    case EMAIL_ON = 'email-check-outline';
    case EMAIL_OFF = 'email-off';
    case NOTIFY_OFF = 'email-off-outline';
    case HISTORY = 'history';
    case LINKS = 'link-variant';
    case WIKI_ASSIGNMENT = 'briefcase-check';
    case WIKI_WORK = 'account-hard-hat';
    case WIKI_TASK = 'clipboard-text-outline';
    case HOME = 'home-variant';
    case LIST = 'format-list-bulleted';
    case STAR = 'star';
    case STAR_OUTLINE = 'star-outline';
    case HEALTH_CHECK = 'clipboard-pulse-outline';
    case FIX = 'auto-fix';
    case AWARD = 'medal';
}
