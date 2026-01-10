<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Legal;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\ValidationToken;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Helpers\ValidationTokenHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\TrackELoginRecordRepository;
use Chamilo\CoreBundle\Repository\ValidationTokenRepository;
use Chamilo\CoreBundle\Security\Authenticator\Ldap\LdapAuthenticator;
use Chamilo\CoreBundle\Security\Authenticator\LoginTokenAuthenticator;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
        private readonly AccessUrlRepository $accessUrlRepo,
    ) {}

    #[Route('/login_json', name: 'login_json', methods: ['POST'])]
    public function loginJson(
        Request $request,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
    ): Response {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException($translator->trans('Invalid login request: check that the Content-Type header is <em>application/json</em>.'));
        }

        $dataRequest = json_decode($request->getContent(), true);
        if (!\is_array($dataRequest)) {
            $dataRequest = [];
        }

        $rememberRequested = (bool) ($dataRequest['_remember_me'] ?? false);

        $user = $this->userHelper->getCurrent();

        if (User::ACTIVE !== $user->getActive()) {
            if (User::INACTIVE === $user->getActive()) {
                $message = $translator->trans('Your account has not been activated.');
            } else {
                $message = $translator->trans('Invalid credentials. Please try again or contact support if you continue to experience issues.');
            }

            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();

            return $this->createAccessDeniedException($message);
        }

        if ($user->getMfaEnabled()) {
            $totpCode = $dataRequest['totp'] ?? null;

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
            $user->isStudent()
            && 'true' === $this->settingsManager->getSetting('allow_terms_conditions', true)
            && 'login' === $this->settingsManager->getSetting('workflows.load_term_conditions_section', true)
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

                $afterLogin = $this->getRedirectAfterLoginPath($user);

                return $this->json([
                    'load_terms' => true,
                    'redirect' => '/main/auth/tc.php?return='.urlencode($afterLogin),
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

        $response = new JsonResponse($data, Response::HTTP_OK, [], true);

        // Remember Me: only on HTTPS.
        if ($rememberRequested && $request->isSecure()) {
            $ttlSeconds = 1209600; // 14 days

            // Opportunistic cleanup of expired remember-me tokens.
            /** @var ValidationTokenRepository $validationTokenRepo */
            $validationTokenRepo = $this->entityManager->getRepository(ValidationToken::class);
            $validationTokenRepo->deleteExpiredRememberMeTokens((new DateTimeImmutable())->modify('-'.$ttlSeconds.' seconds'));

            $rawToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $hash = hash('sha256', $rawToken);

            $tokenEntity = new ValidationToken(ValidationTokenHelper::TYPE_REMEMBER_ME, (int) $user->getId(), $hash);
            $validationTokenRepo->save($tokenEntity, true);

            $secret = (string) $this->getParameter('kernel.secret');
            $sigRaw = hash_hmac('sha256', (string) $user->getId().'|'.$rawToken, $secret, true);
            $sig = rtrim(strtr(base64_encode($sigRaw), '+/', '-_'), '=');

            $cookieValue = $user->getId().':'.$rawToken.':'.$sig;
            $expiresAt = (new DateTimeImmutable())->modify('+'.$ttlSeconds.' seconds');

            $cookie = new Cookie(
                'ch_remember_me',
                $cookieValue,
                $expiresAt->getTimestamp(),
                '/',
                null,
                true,  // Secure
                true,  // HttpOnly
                false,
                Cookie::SAMESITE_STRICT
            );

            $response->headers->setCookie($cookie);
        }

        return $response;
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/check-session', name: 'check_session', methods: ['GET'])]
    public function checkSession(): JsonResponse
    {
        $user = $this->userHelper->getCurrent();
        $data = $this->serializer->serialize($user, 'jsonld', ['groups' => ['user_json:read']]);

        return new JsonResponse(['isAuthenticated' => true, 'user' => json_decode($data)], Response::HTTP_OK);
    }

    #[Route('/login/token/request', name: 'login_token_request', methods: ['GET'])]
    public function loginTokenRequest(
        JWTTokenManagerInterface $jwtManager,
        Security $security,
    ): JsonResponse {
        $user = $this->userHelper->getCurrent();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $token = $jwtManager->create($user);

        // Logout the current user in the login-only Access URL
        $security->logout(false);

        return new JsonResponse([
            'token' => $token,
        ]);
    }

    /**
     * @see LoginTokenAuthenticator
     */
    #[Route('/login/token/check', name: 'login_token_check', methods: ['POST'])]
    public function loginTokenCheck(): Response
    {
        // this response was managed in LoginTokenAuthenticator class
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @see LdapAuthenticator
     */
    #[Route('/login/ldap/check', name: 'login_ldap_check', methods: ['POST'], format: 'json')]
    public function ldapLoginCheck(AuthenticationConfigHelper $authConfigHelper): Response
    {
        $ldapConfig = $authConfigHelper->getLdapConfig();

        if (!$ldapConfig['enabled']) {
            throw $this->createAccessDeniedException();
        }

        // this response was managed in LdapAuthenticator class
        return new Response(null, Response::HTTP_NO_CONTENT);
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
        $pageAfterLogin = $this->settingsManager->getSetting('registration.redirect_after_login');

        $url = null;

        if ($user->isStudent() && !empty($pageAfterLogin)) {
            $url = match ($pageAfterLogin) {
                'index.php' => null,
                'user_portal.php' => $this->router->generate('courses', [], RouterInterface::ABSOLUTE_URL),
                'main/auth/courses.php' => $this->router->generate('catalogue', ['slug' => 'courses'], RouterInterface::ABSOLUTE_URL),
                default => null,
            };
        }

        if ('true' !== $this->settingsManager->getSetting('workflows.go_to_course_after_login')) {
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

    private function getRedirectAfterLoginPath(User $user): string
    {
        $setting = $this->settingsManager->getSetting('registration.redirect_after_login');

        if (!\is_string($setting) || '' === trim($setting)) {
            return '/home';
        }

        $map = json_decode($setting, true);
        if (!\is_array($map)) {
            return '/home';
        }

        $roles = $user->getRoles();

        $profile = null;
        if (\in_array('ROLE_ADMIN', $roles, true)) {
            $profile = 'ADMIN';
        } elseif (\in_array('ROLE_SESSION_MANAGER', $roles, true)) {
            $profile = 'SESSIONADMIN';
        } elseif (\in_array('ROLE_TEACHER', $roles, true)) {
            $profile = 'COURSEMANAGER';
        } elseif (\in_array('ROLE_STUDENT_BOSS', $roles, true)) {
            $profile = 'STUDENT_BOSS';
        } elseif (\in_array('ROLE_DRH', $roles, true)) {
            $profile = 'DRH';
        } elseif (\in_array('ROLE_INVITEE', $roles, true)) {
            $profile = 'INVITEE';
        } elseif (\in_array('ROLE_STUDENT', $roles, true)) {
            $profile = 'STUDENT';
        }

        $value = $profile && \array_key_exists($profile, $map) ? (string) $map[$profile] : '';
        if ('' === trim($value)) {
            return '/home';
        }

        // Normalize a relative path
        $value = ltrim($value, '/');

        // Keep backward compatibility with old known values
        if ('index.php' === $value || 'user_portal.php' === $value) {
            return '/home';
        }
        if ('main/auth/courses.php' === $value) {
            return '/courses';
        }

        return '/'.$value;
    }
}
