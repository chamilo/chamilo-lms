<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Component\Utils;

enum ObjectIcon: string
{
    // Document
    case DOCUMENT = 'bookshelf';
    // Test/Exercise/Exam
    case TEST = 'order-bool-ascending-variant';
    // Link
    case LINK = 'file-link';
    // Assignment/Student publication
    case ASSIGNMENT = 'inbox-full';
    // Forum item
    case FORUM = 'comment-quote';
    // Forum thread
    case FORUM_THREAD = 'format-quote-open-outline';
    // Forum post
    case FORUM_POST = 'format-quote-open';
    // Chapter (in LP or elsewhere) / folder type
    case CHAPTER = 'bookmark-multiple';
    // Certificate
    case CERTIFICATE = 'certificate';
    // Report/stats/tracking
    case REPORT = 'chart-box';
    // Prerequisites (e.g. in learning paths)
    case PREREQUISITE = 'graph';
    // Learning paths
    case LP = 'map-marker-path';
    // Presentation file/format
    case PPT = 'file-powerpoint';
    // PDF file/format
    case PDF = 'arrow-down-bold';
    // Indicator of session or admin resource
    case STAR = 'star';
    // Indicator of something new
    case STAR_EMPTY = 'star-outline';
    // Homepage
    case HOME = 'home';
    // Courses
    case COURSE = 'book-open-page-variant';
    // Session
    case SESSION = 'google-classroom';
    // Agenda
    case AGENDA = 'calendar-text';
    // Calendar event
    case AGENDA_EVENT = 'calendar-month';
    // Platform event
    case AGENDA_PLATFORM_EVENT = 'calendar-star-outline';
    // Weekly agenda view
    case AGENDA_WEEK = 'calendar-week';
    // List of users
    case USER_LIST = 'account-multiple';
    // Admin settings
    case ADMIN_SETTINGS = 'cogs';
    // Normal settings
    case SETTINGS = 'cog';
    // Announcements
    case ANNOUNCEMENT = 'bullhorn';
    // Attendances
    case ATTENDANCE = 'av-timer';
    // Course description
    case DESCRIPTION = 'apple-safari';
    // Course progress
    case COURSE_PROGRESS = 'progress-upload';
    // Glossary
    case GLOSSARY = 'alphabetical';
    // Groups
    case GROUP = 'account-group';
    // User account
    case USER = 'account';
    // Teacher
    case TEACHER = 'human-male-board';
    // Survey
    case SURVEY = 'form-dropdown';
    // Doodle-type survey
    case SURVEY_DOODLE = 'calendar-multiselect';
    // Anonymous user
    case ANONYMOUS = 'incognito';
    // Videoconference
    case VIDEOCONFERENCE = 'video';
    // Dictionary
    case DICTIONARY = 'book-alphabet';
    // Badges
    case BADGE = 'shield-star';
    // Categories (of anything)
    case CATEGORY = 'file-tree-outline';
    // Resource (in the large sense)
    case RESOURCE = 'package-variant-closed';
    // Audio file
    case AUDIO = 'music-note';
    // Attachment
    case ATTACHMENT = 'paperclip';
    // Single element (single squared white sheet as opposed to multiple)
    case SINGLE_ELEMENT = 'note-outline';
    // Multiple element (two squared white sheets as opposed to single one)
    case MULTI_ELEMENT = 'note-multiple-outline';
    // Tracking time, specifically
    case TIME_REPORT = 'timetable';
    // Template document
    case TEMPLATE = 'file-cad-box';
    // Vcard
    case VCARD = 'card-account-details-outline';
    // Skills wheel
    case WHEEL = 'tire';
    // List (of anything)
    case LIST = 'list-box-outline';
    // Default document (replaces default.png)
    case DEFAULT = 'text-box-outline';
}
