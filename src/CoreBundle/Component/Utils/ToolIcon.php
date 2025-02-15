<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Component\Utils;

enum ToolIcon: string
{
    // Agenda/calendar
    case AGENDA = 'calendar-text';
    // Announcement
    case ANNOUNCEMENT = 'bullhorn';
    // Assignment/Work/Student publication
    case ASSIGNMENT = 'inbox-full';
    // Attendance
    case ATTENDANCE = 'av-timer';
    // Blog
    case BLOG = 'post-outline';
    // Chat
    case CHAT = 'chat-processing';
    // Course description
    case COURSE_DESCRIPTION = 'apple-safari';
    // Course homepage
    case COURSE_HOME = 'upload';
    // Course progress / Thematic advance
    case COURSE_PROGRESS = 'progress-star';
    // Course progress' lesson plan
    case COURSE_PROGRESS_PLAN = 'progress-check';
    // Course progress' schedule
    case COURSE_PROGRESS_SCHEDULE = 'progress-clock';
    // Course tool / Course entity
    case COURSE = 'book-open-page-variant';
    // Document
    case DOCUMENT = 'bookshelf';
    // Exercise / Test / Quiz / Exam
    case QUIZ = 'order-bool-ascending-variant';
    // Forum
    case FORUM = 'comment-quote';
    // Glossary
    case GLOSSARY = 'alphabetical';
    // Gradebook
    case GRADEBOOK = 'certificate';
    // Group
    case GROUP = 'account-group';
    // Learning path
    case LP = 'map-marker-path';
    // Link
    case LINK = 'file-link';
    // Maintenance
    case MAINTENANCE = 'wrench-cog';
    // Members / Users
    case MEMBER = 'account';
    // Notebook
    case NOTEBOOK = 'note';
    // Settings
    case SETTINGS = 'cog';
    // Shortcut
    case SHORTCUT = 'flash-outline';
    // Survey
    case SURVEY = 'form-dropdown';
    // Tool intro
    case INTRO = 'image-text';
    // Tracking/Reporting
    case TRACKING = 'chart-box';
    // Videoconference
    case VIDEOCONFERENCE = 'video';
    // Wiki
    case WIKI = 'view-dashboard-edit';
    // Security
    case SECURITY = 'security';
    // Plugin(s)
    case PLUGIN = 'puzzle';
    // Career
    case CAREER = 'library-shelves';
    // Promotion
    case PROMOTION = 'school-outline';
    // Translation
    case TRANSLATION = 'translate';
    // Help
    case HELP = 'face-agent';
    // Bug report
    case BUG_REPORT = 'bug-check';
    // Messages
    case MESSAGE = 'inbox';
    // Shared profile
    case SHARED_PROFILE = 'account-box-outline';
    // Dropbox
    case DROPBOX = 'dropbox';
    // Ai helpers
    case ROBOT = 'robot';
}
