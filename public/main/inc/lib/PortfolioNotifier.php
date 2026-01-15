<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;

class PortfolioNotifier
{
    public static function notifyTeachersAndAuthor(PortfolioComment $comment)
    {
        $item = $comment->getItem();
        $itemResourceLink = $item->getFirstResourceLink();
        $course = $itemResourceLink->getCourse();
        $session = $itemResourceLink->getSession();

        $messageSubject = sprintf(
            get_lang('[Portfolio] New comment in post %s'),
            $item->getTitle(true)
        );
        $userIdListToSend = [];
        $userIdListToSend[] = $comment->getItem()->resourceNode->getCreator()->getId();

        $cidreq = api_get_cidreq_params(
            $course ? $course->getCode() : '',
            $session ? $session->getId() : 0
        );
        $commentUrl = api_get_path(WEB_CODE_PATH).'portfolio/index.php?'
            .($course ? $cidreq.'&' : '')
            .http_build_query(['action' => 'view', 'id' => $item->getId()])."#comment-{$comment->getId()}";

        if ($course) {
            $courseInfo = api_get_course_info($course->getCode());

            if (1 !== (int) api_get_course_setting('email_alert_teachers_student_new_comment', $courseInfo)) {
                return;
            }

            $courseTitle = self::getCourseTitle($course, $session);
            $userIdListToSend = array_merge(
                $userIdListToSend,
                self::getTeacherList($course, $session)
            );

            $messageContent = sprintf(
                get_lang("There is a new comment in the post <i>%s</i> from the <i>%s</i> course portfolio. To view it <a href='%s'>go here</a>."),
                $item->getTitle(),
                $courseTitle,
                $commentUrl
            );
        } else {
            $messageContent = sprintf(
                get_lang("There is a new comment in the post <i>%s</i>. To view it <a href='%s'>go here</a>."),
                $item->getTitle(),
                $commentUrl
            );
        }

        $messageContent .= '<br><br><figure>'
            .'<blockquote>'.$comment->getExcerpt().'</blockquote>'
            .'<figcaption>'.$comment->resourceNode->getCreator()->getFullName().'</figcaption>'
            .'</figure>';

        foreach ($userIdListToSend as $userIdToSend) {
            MessageManager::send_message_simple(
                $userIdToSend,
                $messageSubject,
                $messageContent,
                0,
                false,
                false,
                false
            );
        }
    }

    private static function getCourseTitle(CourseEntity $course, ?SessionEntity $session = null): string
    {
        if ($session) {
            return "{$course->getTitle()} ({$session->getTitle()})";
        }

        return $course->getTitle();
    }

    private static function getTeacherList(CourseEntity $course, ?SessionEntity $session = null): array
    {
        if ($session) {
            $teachers = SessionManager::getCoachesByCourseSession(
                $session->getId(),
                $course->getId()
            );

            return array_values($teachers);
        }

        $teachers = CourseManager::get_teacher_list_from_course_code($course->getCode());

        return array_keys($teachers);
    }
}
