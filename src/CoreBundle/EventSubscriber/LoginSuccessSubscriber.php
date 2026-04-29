<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\LoginCaptchaManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSuccessSubscriber implements EventSubscriberInterface
{
    private const COURSE_STUDENT_STATUS = 5;

    public function __construct(
        private readonly LoginCaptchaManager $loginCaptchaManager,
        private readonly SettingsManager $settingsManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => ['onLoginSuccess', 10],
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $passport = $event->getPassport();

        /** @var UserBadge $userBadge */
        $userBadge = $passport->getBadge(UserBadge::class);
        $username = $userBadge->getUserIdentifier();

        $this->loginCaptchaManager->resetCaptchaState($username);

        $request = $event->getRequest();

        if ($request->hasSession()) {
            $request->getSession()->remove('login_captcha.code');
        }

        $user = $event->getUser();

        if (!$user instanceof User || null === $user->getId()) {
            return;
        }

        $this->applyRegistrationAutosubscribe($user);
    }

    private function applyRegistrationAutosubscribe(User $user): void
    {
        $settingName = $this->getAutosubscribeSettingName($user);
        $courseCodes = $this->parseLegacyAutosubscribeCourseCodes(
            $this->settingsManager->getSetting($settingName)
        );

        if (empty($courseCodes)) {
            return;
        }

        $mustFlush = false;

        foreach ($courseCodes as $courseCode) {
            try {
                if ($this->subscribeUserToCourseIfNeeded($user, $courseCode)) {
                    $mustFlush = true;
                }
            } catch (\Throwable $exception) {
                $this->logger->warning('Registration autosubscribe failed for one course.', [
                    'user_id' => $user->getId(),
                    'course_code' => $courseCode,
                    'setting' => $settingName,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if (!$mustFlush) {
            return;
        }

        try {
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            $this->logger->warning('Registration autosubscribe flush failed.', [
                'user_id' => $user->getId(),
                'setting' => $settingName,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function getAutosubscribeSettingName(User $user): string
    {
        $roles = array_map(
            static fn (string $role): string => strtoupper($role),
            $user->getRoles()
        );

        /*
         * Legacy eventLogin priority:
         * session admin, teacher, DRH, otherwise student.
         */
        if (in_array('ROLE_SESSION_MANAGER', $roles, true)) {
            return 'registration.sessionadmin_autosubscribe';
        }

        if (in_array('ROLE_TEACHER', $roles, true)) {
            return 'registration.teacher_autosubscribe';
        }

        if (
            in_array('ROLE_DRH', $roles, true)
            || in_array('ROLE_STUDENT_BOSS', $roles, true)
        ) {
            return 'registration.drh_autosubscribe';
        }

        return 'registration.student_autosubscribe';
    }

    /**
     * Legacy compatibility:
     * Chamilo 1.11 used course codes separated by "|".
     *
     * Example:
     * COURSE1|COURSE2|COURSE3
     *
     * @return string[]
     */
    private function parseLegacyAutosubscribeCourseCodes(mixed $rawValue): array
    {
        if (null === $rawValue || false === $rawValue) {
            return [];
        }

        $value = trim((string) $rawValue);

        if ('' === $value || 'false' === strtolower($value)) {
            return [];
        }

        $courseCodes = [];

        foreach (explode('|', $value) as $courseCode) {
            $courseCode = trim($courseCode);

            if ('' === $courseCode) {
                continue;
            }

            if (!preg_match('/^[A-Za-z0-9_.-]{1,40}$/', $courseCode)) {
                $this->logger->warning('Registration autosubscribe skipped invalid course code.', [
                    'course_code' => $courseCode,
                ]);

                continue;
            }

            $courseCodes[] = $courseCode;
        }

        return array_values(array_unique($courseCodes));
    }

    private function subscribeUserToCourseIfNeeded(User $user, string $courseCode): bool
    {
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['code' => $courseCode])
        ;

        if (!$course instanceof Course || null === $course->getId()) {
            $this->logger->warning('Registration autosubscribe skipped missing course.', [
                'course_code' => $courseCode,
            ]);

            return false;
        }

        $existingSubscription = $this->entityManager
            ->getRepository(CourseRelUser::class)
            ->findOneBy([
                'user' => $user,
                'course' => $course,
            ])
        ;

        if ($existingSubscription instanceof CourseRelUser) {
            return false;
        }

        /*
         * Match the legacy behavior:
         * CourseManager::subscribeUser($userId, $courseCode) subscribed the
         * user to the course without granting course teacher permissions.
         */
        $subscription = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($user)
            ->setStatus(self::COURSE_STUDENT_STATUS)
            ->setTutor(false)
            ->setRelationType(0)
            ->setUserCourseCat(0)
        ;

        $this->entityManager->persist($subscription);

        return true;
    }
}
