<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\TrackELoginRecord;
use Chamilo\CoreBundle\Entity\TrackEOnline;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\TrackELoginRecordRepository;
use Chamilo\CoreBundle\Repository\TrackELoginRepository;
use Chamilo\CoreBundle\Repository\TrackEOnlineRepository;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Chamilo\CoreBundle\ServiceHelper\IsAllowedToEditHelper;
use Chamilo\CoreBundle\ServiceHelper\LoginAttemptLogger;
use Chamilo\CoreBundle\ServiceHelper\UserHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginSuccessHandler
{
    public function __construct(
        private readonly UrlGeneratorInterface $router,
        private readonly AuthorizationCheckerInterface $checker,
        private readonly SettingsManager $settingsManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoginAttemptLogger $loginAttemptLogger,
        private readonly UserHelper $userHelper,
        private readonly CourseRepository $courseRepo,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(InteractiveLoginEvent $event): RedirectResponse
    {
        $request = $event->getRequest();
        $requestSession = $request->getSession();

        $user = $this->userHelper->getCurrent();

        // $userInfo = api_get_user_info($user->getId());
        // $userInfo['is_anonymous'] = false;

        // Backward compatibility.
        // $ip = $request->getClientIp();

        // Setting user info.
        // $requestSession->set('_user', $user);

        // Setting admin permissions for.
        if ($this->checker->isGranted('ROLE_ADMIN')) {
            $requestSession->set('is_platformAdmin', true);
        }

        // Setting teachers permissions.
        if ($this->checker->isGranted('ROLE_TEACHER')) {
            $requestSession->set('is_allowedCreateCourse', true);
        }

        // Setting last login datetime
        $requestSession->set('user_last_login_datetime', api_get_utc_datetime());
        $url = $this->getRedirectAfterLoginUrl() ?? $this->router->generate('index');

        $response = null;
        $goToCourse = $this->settingsManager->getSetting('course.go_to_course_after_login');

        $requestSession->set('_uid', $user->getId());
        // $requestSession->set('_user', $userInfo);
        // $requestSession->set('is_platformAdmin', \UserManager::is_admin($userId));
        // $requestSession->set('is_allowedCreateCourse', $userInfo['status'] === 1);
        // Redirecting to a course or a session.
        if ('true' === $goToCourse) {
            // Get the course list
            $personal_course_list = $this->courseRepo->getPersonalSessionCourses(
                $user,
                $this->accessUrlHelper->getCurrent(),
                $this->isAllowedToEditHelper->canCreateCourse()
            );
            $my_session_list = [];
            $count_of_courses_no_sessions = 0;
            foreach ($personal_course_list as $course) {
                if (!empty($course['sid'])) {
                    $my_session_list[$course['sid']] = true;
                } else {
                    $count_of_courses_no_sessions++;
                }
            }

            $count_of_sessions = \count($my_session_list);
            if (1 === $count_of_sessions && 0 === $count_of_courses_no_sessions) {
                $key = array_keys($personal_course_list);

                $url = $this->router->generate(
                    'chamilo_core_course_home',
                    [
                        'cid' => $personal_course_list[$key[0]]['cid'],
                        'sid' => $personal_course_list[$key[0]]['sid'] ?? 0,
                    ]
                );
            }

            if (0 === $count_of_sessions && 1 === $count_of_courses_no_sessions) {
                $key = array_keys($personal_course_list);
                $url = $this->router->generate(
                    'chamilo_core_course_home',
                    [
                        'cid' => $personal_course_list[$key[0]]['cid'],
                        'sid' => 0,
                    ]
                );
            }
        }

        if (!$requestSession->get('login_records_created')) {
            $userIp = $request->getClientIp();

            /** @var TrackEOnlineRepository $trackEOnlineRepository */
            $trackEOnlineRepository = $this->entityManager->getRepository(TrackEOnline::class);

            /** @var TrackELoginRepository $trackELoginRepository */
            $trackELoginRepository = $this->entityManager->getRepository(TrackELogin::class);

            /** @var TrackELoginRecordRepository $trackELoginRecordRepository */
            $trackELoginRecordRepository = $this->entityManager->getRepository(TrackELoginRecord::class);

            $trackELoginRepository->createLoginRecord($user, new DateTime(), $userIp);
            $trackEOnlineRepository->createOnlineSession($user, $userIp);

            $user->setLastLogin(new DateTime());
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Log of connection attempts
            $trackELoginRecordRepository->addTrackLogin($user->getUsername(), $userIp, true);
            $this->loginAttemptLogger->logAttempt(true, $user->getUsername(), $userIp);

            $requestSession->set('login_records_created', true);
        }

        if (!empty($url)) {
            $response = new RedirectResponse($url);
        }

        // Redirect the user to where they were before the login process begun.
        if (empty($response)) {
            $url = $request->headers->get('referer');
            $response = new RedirectResponse($url);
        }

        return $response;
    }

    private function getRedirectAfterLoginUrl(): ?string
    {
        $json = $this->settingsManager->getSetting('registration.redirect_after_login', true);
        if (empty($json)) {
            return null;
        }

        $map = json_decode($json, true);
        if (!is_array($map)) {
            return null;
        }

        $profile = null;
        if ($this->checker->isGranted('ROLE_ADMIN')) {
            $profile = 'ADMIN';
        } elseif ($this->checker->isGranted('ROLE_SESSION_ADMIN')) {
            $profile = 'SESSIONADMIN';
        } elseif ($this->checker->isGranted('ROLE_TEACHER')) {
            $profile = 'COURSEMANAGER';
        } elseif ($this->checker->isGranted('ROLE_STUDENT_BOSS')) {
            $profile = 'STUDENT_BOSS';
        } elseif ($this->checker->isGranted('ROLE_DRH')) {
            $profile = 'DRH';
        } elseif ($this->checker->isGranted('ROLE_INVITEE')) {
            $profile = 'INVITEE';
        } elseif ($this->checker->isGranted('ROLE_STUDENT')) {
            $profile = 'STUDENT';
        }

        if ($profile !== null && isset($map[$profile]) && !empty($map[$profile])) {
            $target = trim($map[$profile]);

            return match ($target) {
                'user_portal.php', 'index.php' => $this->router->generate('index'),
                'main/auth/courses.php' => '/courses',
                default => rtrim(api_get_path(WEB_PATH), '/') . '/' . ltrim($target, '/'),
            };
        }

        return null;
    }
}
