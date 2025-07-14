<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Legal;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\TrackELoginRecordRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private TrackELoginRecordRepository $trackELoginRecordRepository,
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
        private TokenStorageInterface $tokenStorage,
        private AuthorizationCheckerInterface $authorizationChecker,
        private readonly UserHelper $userHelper,
        private readonly RouterInterface $router,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    #[Route('/login_json', name: 'login_json', methods: ['POST'])]
    public function loginJson(
        Request $request,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
    ): Response {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException($translator->trans('Invalid login request: check that the Content-Type header is "application/json".'));
        }

        $user = $this->userHelper->getCurrent();

        if (1 !== $user->getActive()) {
            if (0 === $user->getActive()) {
                $message = $translator->trans('Account not activated.');
            } else {
                $message = $translator->trans('Invalid credentials. Please try again or contact support if you continue to experience issues.');
            }

            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();

            return $this->createAccessDeniedException($message);
        }

        if ($user->getMfaEnabled()) {
            $data = json_decode($request->getContent(), true);
            $totpCode = $data['totp'] ?? null;

            if (null === $totpCode) {
                $tokenStorage->setToken(null);
                $request->getSession()->invalidate();

                return $this->json(['requires2FA' => true], 200);
            }

            if (!$this->isTOTPValid($user, $totpCode)) {
                $tokenStorage->setToken(null);
                $request->getSession()->invalidate();

                return $this->json(['error' => 'Invalid 2FA code.'], 401);
            }
        }

        if (null !== $user->getExpirationDate() && $user->getExpirationDate() <= new DateTime()) {
            $message = $translator->trans('Your account has expired.');

            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();

            return $this->createAccessDeniedException($message);
        }

        $extraFieldValuesRepository = $this->entityManager->getRepository(ExtraFieldValues::class);
        $legalTermsRepo = $this->entityManager->getRepository(Legal::class);
        if (
            $user->hasRole('ROLE_STUDENT')
            && 'true' === $this->settingsManager->getSetting('allow_terms_conditions', true)
            && 'login' === $this->settingsManager->getSetting('load_term_conditions_section', true)
        ) {
            $termAndConditionStatus = false;
            $extraValue = $extraFieldValuesRepository->findLegalAcceptByItemId($user->getId());
            if (!empty($extraValue['value'])) {
                $result = $extraValue['value'];
                $userConditions = explode(':', $result);
                $version = $userConditions[0];
                $langId = (int) $userConditions[1];
                $realVersion = $legalTermsRepo->getLastVersion($langId);
                $termAndConditionStatus = ($version >= $realVersion);
            }

            if (false === $termAndConditionStatus) {
                $tempTermAndCondition = ['user_id' => $user->getId()];
                $this->tokenStorage->setToken(null);
                $request->getSession()->invalidate();
                $request->getSession()->start();
                $request->getSession()->set('term_and_condition', $tempTermAndCondition);

                return $this->json([
                    'load_terms' => true,
                    'redirect'   => '/main/auth/tc.php?return=' . urlencode('/home'),
                ]);
            }
            $request->getSession()->remove('term_and_condition');
        }

        $redirectUrl = $this->calculateRedirectUrl(
            $user,
            $this->entityManager->getRepository(Course::class),
        );

        if (null !== $redirectUrl) {
            return $this->json([
                'redirect' => $redirectUrl,
            ]);
        }

        // Password rotation check
        $days = (int) $this->settingsManager->getSetting('security.password_rotation_days', true);
        if ($days > 0) {
            $lastUpdate = $user->getPasswordUpdatedAt() ?? $user->getCreatedAt();
            $diffDays = (new DateTimeImmutable())->diff($lastUpdate)->days;

            if ($diffDays > $days) {
                // Clean token & session
                $tokenStorage->setToken(null);
                $request->getSession()->invalidate();

                return $this->json([
                    'rotate_password' => true,
                    'redirect' => '/account/change-password?rotate=1&userId='.$user->getId(),
                ]);
            }
        }

        $data = null;
        if ($user) {
            $data = $this->serializer->serialize($user, 'jsonld', ['groups' => ['user_json:read']]);
        }

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/check-session', name: 'check_session', methods: ['GET'])]
    public function checkSession(): JsonResponse
    {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $this->userHelper->getCurrent();
            $data = $this->serializer->serialize($user, 'jsonld', ['groups' => ['user_json:read']]);

            return new JsonResponse(['isAuthenticated' => true, 'user' => json_decode($data)], Response::HTTP_OK);
        }

        throw $this->createAccessDeniedException();
    }

    /**
     * Validates the provided TOTP code for the given user.
     *
     * @param mixed $user
     */
    private function isTOTPValid($user, string $totpCode): bool
    {
        $decryptedSecret = $this->decryptTOTPSecret($user->getMfaSecret(), $_ENV['APP_SECRET']);
        $totp = TOTP::create($decryptedSecret);

        return $totp->verify($totpCode);
    }

    /**
     * Decrypts the stored TOTP secret.
     */
    private function decryptTOTPSecret(string $encryptedSecret, string $encryptionKey): string
    {
        $cipherMethod = 'aes-256-cbc';

        try {
            list($iv, $encryptedData) = explode('::', base64_decode($encryptedSecret), 2);

            return openssl_decrypt($encryptedData, $cipherMethod, $encryptionKey, 0, $iv);
        } catch (Exception $e) {
            error_log('Exception caught during decryption: '.$e->getMessage());

            return '';
        }
    }

    private function calculateRedirectUrl(
        User $user,
        CourseRepository $courseRepo,
    ): ?string {
        /* Possible values: index.php, user_portal.php, main/auth/courses.php */
        $pageAfterLogin = $this->settingsManager->getSetting('registration.page_after_login');

        $url = null;

        if ($user->isStudent() && !empty($pageAfterLogin)) {
            $url = match ($pageAfterLogin) {
                'index.php' => null,
                'user_portal.php' => $this->router->generate('courses', [], RouterInterface::ABSOLUTE_URL),
                'main/auth/courses.php' => $this->router->generate('catalogue', ['slug' => 'courses'], RouterInterface::ABSOLUTE_URL),
                default => null,
            };
        }

        if ('true' !== $this->settingsManager->getSetting('course.go_to_course_after_login')) {
            return $url;
        }

        $personalCourseList = $courseRepo->getPersonalSessionCourses(
            $user,
            $this->accessUrlHelper->getCurrent(),
            $this->isAllowedToEditHelper->canCreateCourse()
        );

        $mySessionList = [];
        $countOfCoursesNoSessions = 0;

        foreach ($personalCourseList as $course) {
            if (!empty($course['sid'])) {
                $mySessionList[$course['sid']] = true;
            } else {
                $countOfCoursesNoSessions++;
            }
        }

        $countOfSessions = \count($mySessionList);

        if (1 === $countOfSessions && 0 === $countOfCoursesNoSessions) {
            $key = array_keys($personalCourseList);

            return $this->router->generate(
                'chamilo_core_course_home',
                [
                    'cid' => $personalCourseList[$key[0]]['cid'],
                    'sid' => $personalCourseList[$key[0]]['sid'] ?? 0,
                ]
            );
        }

        if (0 === $countOfSessions && 1 === $countOfCoursesNoSessions) {
            $key = array_keys($personalCourseList);

            return $this->router->generate(
                'chamilo_core_course_home',
                [
                    'cid' => $personalCourseList[$key[0]]['cid'],
                    'sid' => 0,
                ]
            );
        }

        return null;
    }
}
