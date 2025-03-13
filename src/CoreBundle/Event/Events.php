<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

final class Events
{
    public const USER_CREATED = 'chamilo.event.user_created';
    public const USER_UPDATED = 'chamilo.event.user_updated';

    public const COURSE_CREATED = 'chamilo.event.course_created';

    public const CHECK_LOGIN_CREDENTIALS = 'chamilo.event.check_login_credentials';

    public const SESSION_RESUBSCRIPTION = 'chamilo.event.session_resubscription';
    public const CONDITIONAL_LOGIN = 'chamilo.event.conditional_login';

    public const MY_STUDENTS_EXERCISE_TRACKING = 'chamilo.event.my_students_exercise_tracking';
    public const MY_STUDENTS_LP_TRACKING = 'chamilo.event.my_students_lp_tracking';

    public const DOCUMENT_ITEM_ACTION = 'chamilo.event.document_item_action';
    public const DOCUMENT_ACTION = 'chamilo.event.document_action';
    public const DOCUMENT_ITEM_VIEW = 'chamilo.event.document_item_view';

    public const LP_CREATED = 'chamilo.event.learning_path_created';
    public const LP_ITEM_VIEWED = 'chamilo.event.learning_path_item_viewed';
    public const LP_ENDED = 'chamilo.event.learning_path_ended';

    public const EXERCISE_QUESTION_ANSWERED = 'chamilo.event.question_answered';
    public const EXERCISE_ENDED = 'chamilo.event.exercise_ended';

    public const PORTFOLIO_ITEM_ADDED = 'chamilo.event.portfolio_item_added';
    public const PORTFOLIO_ITEM_EDITED = 'chamilo.event.portfolio_item_edited';
    public const PORTFOLIO_ITEM_VIEWED = 'chamilo.event.portfolio_item_viewed';
    public const PORTOFLIO_ITEM_DELETED = 'chamilo.event.portfolio_item_deleted';
    public const PORTFOLIO_ITEM_VISIBILITY_CHANGED = 'chamilo.event.portfolio_item_visibility_changed';
    public const PORTFOLIO_ITEM_COMMENTED = 'chamilo.event.portfolio_item_commented';
    public const PORTFOLIO_ITEM_HIGHLIGHTED = 'chamilo.event.portfolio_item_highlighted';
    public const PORTFOLIO_DOWNLOADED = 'chamilo.event.portfolio_downloaded';
    public const PORTFOLIO_ITEM_SCORED = 'chamilo.event.portfolio_item_scored';
    public const PORTFOLIO_COMMENT_SCORED = 'chamilo.event.portfolio_comment_scored';
    public const PORTFOLIO_COMMENT_EDITED = 'chamilo.event.portfolio_comment_edited';

    public const NOTIFICATION_CONTENT = 'chamilo_hook_event.notification_content';
    public const NOTIFICATION_TITLE = 'chamilo_hook_event.notification_title';

    public const WS_REGISTRATION = 'chamilo.event.ws_registration';

    public const ADMIN_BLOCK = 'chamilo.event.admin_block';
}
