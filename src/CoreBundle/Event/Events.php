<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

final class Events
{
    public const string USER_CREATED = 'chamilo.event.user_created';
    public const string USER_UPDATED = 'chamilo.event.user_updated';
    public const string USER_DELETED = 'chamilo.event.user_deleted';

    public const string COURSE_CREATED = 'chamilo.event.course_created';
    public const string COURSE_USER_SUBSCRIPTION_CHECK = 'chamilo.event.course_user_subscription_check';

    public const string LOGIN_CREDENTIALS_CHECKED = 'chamilo.event.login_credentials_checked';

    public const string SESSION_RESUBSCRIPTION = 'chamilo.event.session_resubscription';
    public const string LOGIN_CONDITION_CHECKED = 'chamilo.event.login_condition_checked';

    public const string MY_STUDENTS_EXERCISE_TRACKING = 'chamilo.event.my_students_exercise_tracking';
    public const string MY_STUDENTS_LP_TRACKING = 'chamilo.event.my_students_lp_tracking';

    public const string DOCUMENT_ITEM_ACTION = 'chamilo.event.document_item_action';
    public const string DOCUMENT_ACTION = 'chamilo.event.document_action';
    public const string DOCUMENT_ITEM_VIEW = 'chamilo.event.document_item_view';

    public const string LP_CREATED = 'chamilo.event.learning_path_created';
    public const string LP_ITEM_VIEWED = 'chamilo.event.learning_path_item_viewed';
    public const string LP_ENDED = 'chamilo.event.learning_path_ended';

    public const string EXERCISE_QUESTION_ANSWERED = 'chamilo.event.question_answered';
    public const string EXERCISE_ENDED = 'chamilo.event.exercise_ended';
    public const string EXERCISE_REPORT_ACTION = 'chamilo.event.exercise_report_action';

    public const string PORTFOLIO_ITEM_ADDED = 'chamilo.event.portfolio_item_added';
    public const string PORTFOLIO_ITEM_EDITED = 'chamilo.event.portfolio_item_edited';
    public const string PORTFOLIO_ITEM_VIEWED = 'chamilo.event.portfolio_item_viewed';
    public const string PORTFOLIO_ITEM_DELETED = 'chamilo.event.portfolio_item_deleted';
    public const string PORTFOLIO_ITEM_VISIBILITY_CHANGED = 'chamilo.event.portfolio_item_visibility_changed';
    public const string PORTFOLIO_ITEM_COMMENTED = 'chamilo.event.portfolio_item_commented';
    public const string PORTFOLIO_ITEM_HIGHLIGHTED = 'chamilo.event.portfolio_item_highlighted';
    public const string PORTFOLIO_DOWNLOADED = 'chamilo.event.portfolio_downloaded';
    public const string PORTFOLIO_ITEM_SCORED = 'chamilo.event.portfolio_item_scored';
    public const string PORTFOLIO_COMMENT_SCORED = 'chamilo.event.portfolio_comment_scored';
    public const string PORTFOLIO_COMMENT_EDITED = 'chamilo.event.portfolio_comment_edited';

    public const string NOTIFICATION_CONTENT_FORMATTED = 'chamilo_hook_event.notification_content';
    public const string NOTIFICATION_TITLE_FORMATTED = 'chamilo_hook_event.notification_title_formatted';

    public const string ADMIN_BLOCK_DISPLAYED = 'chamilo.event.admin_block_displayed';
    public const string COURSE_ACCESS_CHECK = 'chamilo.course_access_check';
}
