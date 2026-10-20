<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Assignment;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CCourseSettingRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationFollowRepository;
use MessageManager;
use Security;
use Symfony\Contracts\Translation\TranslatorInterface;

use const ENT_QUOTES;

/**
 * Notifies the followers of an assignment when a learner comments on one of its
 * submissions. Shared by the legacy work/view.php page and the API comment action,
 * so both screens send the same message to the same people.
 */
final class AssignmentFeedbackNotifier
{
    /**
     * Course-wide master switch (1 = yes, 2 = no). When off, nobody is e-mailed,
     * whatever the follow state.
     */
    public const COURSE_SETTING = 'email_to_teachers_on_new_work_feedback';

    public function __construct(
        private readonly CStudentPublicationFollowRepository $followRepository,
        private readonly CCourseSettingRepository $courseSettingRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Mirrors the legacy check `1 == api_get_course_setting(...)`: a course without
     * the setting row (or with any other value) sends nothing.
     */
    public function isEnabledForCourse(Course $course): bool
    {
        $setting = $this->courseSettingRepository->findOneBy([
            'cId' => (int) $course->getId(),
            'variable' => self::COURSE_SETTING,
        ]);

        return $setting instanceof CCourseSetting && '1' === trim((string) $setting->getValue());
    }

    /**
     * Followers of the assignment who may still receive its learners' comments:
     * teachers of the course, coaches of the session (when in one) or platform admins.
     * A follower who lost that role (e.g. was unsubscribed) is skipped, and so is the commenter.
     *
     * @return list<int>
     */
    public function getRecipientIds(
        CStudentPublication $assignment,
        Course $course,
        ?Session $session,
        User $commenter
    ): array {
        if (!$this->isEnabledForCourse($course)) {
            return [];
        }

        $recipientIds = [];
        foreach ($this->followRepository->findFollowers($assignment) as $follower) {
            if ($follower->getId() === $commenter->getId() || !$follower->isActive()) {
                continue;
            }

            if ($this->mayReceive($follower, $course, $session)) {
                $recipientIds[] = (int) $follower->getId();
            }
        }

        return $recipientIds;
    }

    /**
     * @return list<int> the users who were sent a message
     */
    public function notifyLearnerFeedback(
        CStudentPublication $submission,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $commenter,
        string $comment
    ): array {
        $assignment = $submission->getPublicationParent() ?? $submission;
        $recipientIds = $this->getRecipientIds($assignment, $course, $session, $commenter);

        if (empty($recipientIds)) {
            return [];
        }

        $url = api_get_path(WEB_CODE_PATH).'work/view.php?'.http_build_query([
            'cid' => $course->getId(),
            'sid' => (int) $session?->getId(),
            'gid' => (int) $group?->getIid(),
            'id' => $submission->getIid(),
        ]);

        $subject = \sprintf($this->translator->trans("There's a new feedback in work: %s"), $assignment->getTitle());
        $content = \sprintf(
            $this->translator->trans("There's a new feedback in work: %s <a href='%s'>Click here</a> to see it."),
            htmlspecialchars($submission->getTitle(), ENT_QUOTES),
            htmlspecialchars($url, ENT_QUOTES)
        );

        if ('' !== trim($comment)) {
            $content .= '<br /><b>'.$this->translator->trans('Comment').':</b><br />'.Security::remove_XSS($comment);
        }

        foreach ($recipientIds as $recipientId) {
            MessageManager::send_message_simple($recipientId, $subject, $content, (int) $commenter->getId());
        }

        return $recipientIds;
    }

    private function mayReceive(User $follower, Course $course, ?Session $session): bool
    {
        // Recipient eligibility of a third party, not an authorization of the current
        // request: there is no token to ask isGranted() about, so read the role itself.
        if ($follower->isAdmin() || $course->hasUserAsTeacher($follower)) {
            return true;
        }

        return $session instanceof Session
            && ($session->hasCourseCoachInCourse($follower, $course) || $session->hasUserAsGeneralCoach($follower));
    }
}
