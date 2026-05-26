<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\IntrusionDetectionLogHelper;
use Chamilo\CoreBundle\Helpers\WeakPasswordCheckerHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\TrackELoginRecordRepository;
use DateTimeImmutable;
use Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

#[Route('/admin/security')]
final class SecurityController extends BaseController
{
    private const PASSWORD_STRENGTH_SCAN_BATCH_SIZE = 1;
    private const PASSWORD_STRENGTH_SCAN_FOUND_KEY = 'admin_password_strength_found_user_ids';
    private const PASSWORD_STRENGTH_SCAN_OFFSET_KEY = 'admin_password_strength_scan_offset';
    private const PASSWORD_STRENGTH_SCAN_TOTAL_KEY = 'admin_password_strength_scan_total';
    private const PASSWORD_STRENGTH_SCAN_FILTER_IDS_KEY = 'admin_password_strength_scan_filter_user_ids';

    public function __construct(
        private readonly TrackELoginRecordRepository $repo,
        private readonly IntrusionDetectionLogHelper $idsLog,
        private readonly WeakPasswordCheckerHelper $weakPasswordChecker,
        private readonly UserRepository $userRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly TranslatorInterface $translator,
        #[Autowire(param: 'chamilo.ids.enabled')]
        private readonly bool $idsEnabled,
    ) {}

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/login-attempts', name: 'admin_security_login_attempts', methods: ['GET'])]
    public function loginAttempts(Request $r): Response
    {
        $page = max(1, $r->query->getInt('page', 1));
        $pageSize = min(100, max(1, $r->query->getInt('pageSize', 25)));
        $filters = [
            'username' => trim((string) $r->query->get('username', '')),
            'ip' => trim((string) $r->query->get('ip', '')),
            'from' => $r->query->get('from'),
            'to' => $r->query->get('to'),
        ];

        $list = $this->repo->findFailedPaginated($page, $pageSize, $filters);

        $stats = [
            'byDay' => $this->repo->failedByDay(7),
            'byMonth' => $this->repo->failedByMonth(12),
            'topUsernames' => $this->repo->topUsernames(30, 5),
            'topIps' => $this->repo->topIps(30, 5),
            'successVsFailed' => $this->repo->successVsFailedByDay(30),
            'byHour' => $this->repo->failedByHourOfDay(7),
            'uniqueIps' => $this->repo->uniqueIpsByDay(30),
        ];

        return $this->render('@ChamiloCore/Admin/Security/login_attempts.html.twig', [
            'items' => $list['items'],
            'total' => $list['total'],
            'page' => $list['page'],
            'pageSize' => $list['pageSize'],
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/simple-ids', name: 'admin_security_simple_ids', methods: ['GET'])]
    public function simpleIds(Request $r): Response
    {
        $page = max(1, $r->query->getInt('page', 1));
        $pageSize = min(100, max(1, $r->query->getInt('pageSize', 25)));
        $filters = [
            'ip' => trim((string) $r->query->get('ip', '')),
            'type' => trim((string) $r->query->get('type', '')),
            'from' => $r->query->get('from'),
            'to' => $r->query->get('to'),
        ];

        $list = $this->idsLog->parseEvents($page, $pageSize, $filters);

        $stats = [
            'byDay' => $this->idsLog->getStatsByDay(7),
            'byType' => $this->idsLog->getStatsByType(30),
            'topIps' => $this->idsLog->getTopIps(30, 5),
        ];

        $knownTypes = $this->idsLog->getKnownTypes();

        return $this->render('@ChamiloCore/Admin/Security/ids_events.html.twig', [
            'items' => $list['items'],
            'total' => $list['total'],
            'page' => $list['page'],
            'pageSize' => $list['pageSize'],
            'filters' => $filters,
            'stats' => $stats,
            'knownTypes' => $knownTypes,
            'idsEnabled' => $this->idsEnabled,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/password-strength', name: 'admin_security_password_strength', methods: ['GET', 'POST'])]
    public function passwordStrength(Request $request): Response
    {
        $csrfToken = $this->csrfTokenManager->getToken('password_strength_action')->getValue();
        $session = $request->getSession();

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token', '');

            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('password_strength_action', $token))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }

            $action = (string) $request->request->get('action', '');

            if ('scan_clear' === $action) {
                $this->clearPasswordStrengthScanSession($request);

                return new RedirectResponse($this->generateUrl('admin_security_password_strength'));
            }

            if (!\in_array($action, ['request_change', 'force_reset'], true)) {
                $this->addFlash('warning', $this->translator->trans('Invalid action.'));

                return new RedirectResponse($this->generateUrl('admin_security_password_strength'));
            }

            $singleUserId = $request->request->getInt('user_id', 0);
            $selectedUserIds = $request->request->all('user_ids');
            $userIds = $singleUserId > 0 ? [$singleUserId] : $selectedUserIds;

            if (empty($userIds)) {
                $this->addFlash('warning', $this->translator->trans('No users selected.'));

                return new RedirectResponse($this->generateUrl('admin_security_password_strength'));
            }

            // Re-check selected users before any action. Never trust the submitted list alone.
            $weakUsers = $this->weakPasswordChecker->findWeakPasswordUsers($userIds);
            $processed = 0;

            foreach ($weakUsers as $weakUser) {
                if ('request_change' === $action && $this->sendPasswordChangeRequest($weakUser)) {
                    $processed++;

                    continue;
                }

                if ('force_reset' === $action && $this->forcePasswordReset($weakUser)) {
                    $processed++;
                }
            }

            $this->addFlash(
                'success',
                \sprintf($this->translator->trans('%s user(s) processed.'), (string) $processed)
            );

            return new RedirectResponse($this->generateUrl('admin_security_password_strength'));
        }

        $total = (int) $session->get(self::PASSWORD_STRENGTH_SCAN_TOTAL_KEY, 0);
        $offset = (int) $session->get(self::PASSWORD_STRENGTH_SCAN_OFFSET_KEY, 0);
        $foundUserIds = $session->get(self::PASSWORD_STRENGTH_SCAN_FOUND_KEY, []);
        $hasFinishedScan = $total > 0 && $offset >= $total;

        return $this->render('@ChamiloCore/Admin/Security/password_strength.html.twig', [
            'users' => $hasFinishedScan ? $this->weakPasswordChecker->findUsersByIds($foundUserIds) : [],
            'has_scanned' => $hasFinishedScan,
            'scanned_count' => $hasFinishedScan ? $total : 0,
            'scan_total' => $hasFinishedScan ? $total : 0,
            'weak_count' => $hasFinishedScan ? \count($foundUserIds) : 0,
            'csrf_token' => $csrfToken,
            'scan_endpoint' => $this->generateUrl('admin_security_password_strength_scan_batch'),
            'scan_filter_user_ids' => implode(',', $this->getPasswordStrengthScanFilterUserIds($request)),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/password-strength/scan-batch', name: 'admin_security_password_strength_scan_batch', methods: ['POST'])]
    public function passwordStrengthScanBatch(Request $request): JsonResponse
    {
        $token = (string) $request->request->get('_token', '');

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('password_strength_action', $token))) {
            return new JsonResponse([
                'error' => $this->translator->trans('Invalid CSRF token.'),
            ], Response::HTTP_FORBIDDEN);
        }

        $session = $request->getSession();
        $restart = $request->request->getBoolean('restart');

        if ($restart) {
            $filterUserIds = $this->parseUserIds((string) $request->request->get('user_ids_filter', ''));

            $session->remove(self::PASSWORD_STRENGTH_SCAN_FOUND_KEY);
            $session->remove(self::PASSWORD_STRENGTH_SCAN_OFFSET_KEY);
            $session->remove(self::PASSWORD_STRENGTH_SCAN_TOTAL_KEY);
            $session->set(self::PASSWORD_STRENGTH_SCAN_FILTER_IDS_KEY, $filterUserIds);
        }

        $filterUserIds = $this->getPasswordStrengthScanFilterUserIds($request);
        $total = (int) $session->get(self::PASSWORD_STRENGTH_SCAN_TOTAL_KEY, 0);

        if (0 === $total) {
            $total = [] === $filterUserIds
                ? $this->weakPasswordChecker->countScannableUsers()
                : $this->weakPasswordChecker->countScannableUsersByIds($filterUserIds);

            $session->set(self::PASSWORD_STRENGTH_SCAN_TOTAL_KEY, $total);
        }

        if (0 === $total) {
            return new JsonResponse([
                'finished' => true,
                'scanned_count' => 0,
                'scan_total' => 0,
                'weak_count' => 0,
                'filter_user_ids' => $filterUserIds,
                'scanned_users' => [],
                'weak_users' => [],
            ]);
        }

        $offset = (int) $session->get(self::PASSWORD_STRENGTH_SCAN_OFFSET_KEY, 0);

        if ($offset >= $total) {
            $foundUserIds = $session->get(self::PASSWORD_STRENGTH_SCAN_FOUND_KEY, []);

            return new JsonResponse([
                'finished' => true,
                'scanned_count' => $total,
                'scan_total' => $total,
                'weak_count' => \count($foundUserIds),
                'filter_user_ids' => $filterUserIds,
                'scanned_users' => [],
                'weak_users' => [],
            ]);
        }

        $result = [] === $filterUserIds
            ? $this->weakPasswordChecker->scanUsersBatch(
                $offset,
                self::PASSWORD_STRENGTH_SCAN_BATCH_SIZE
            )
            : $this->weakPasswordChecker->scanUsersByIdsBatch(
                $filterUserIds,
                $offset,
                self::PASSWORD_STRENGTH_SCAN_BATCH_SIZE
            );

        $scannedUsers = $result['scanned_users'];
        $weakUsers = $result['weak_users'];
        $foundUserIds = $session->get(self::PASSWORD_STRENGTH_SCAN_FOUND_KEY, []);

        foreach ($weakUsers as $weakUser) {
            $foundUserIds[] = (int) $weakUser->getId();
        }

        $foundUserIds = array_values(array_unique(array_filter(array_map('intval', $foundUserIds))));
        $nextOffset = min($total, $offset + \count($scannedUsers));

        $session->set(self::PASSWORD_STRENGTH_SCAN_FOUND_KEY, $foundUserIds);
        $session->set(self::PASSWORD_STRENGTH_SCAN_OFFSET_KEY, $nextOffset);

        return new JsonResponse([
            'finished' => $nextOffset >= $total,
            'scanned_count' => $nextOffset,
            'scan_total' => $total,
            'weak_count' => \count($foundUserIds),
            'filter_user_ids' => $filterUserIds,
            'scanned_users' => array_map([$this, 'formatPasswordStrengthUser'], $scannedUsers),
            'weak_users' => array_map([$this, 'formatPasswordStrengthUser'], $weakUsers),
        ]);
    }

    /**
     * @return array{id: int, name: string, username: string, email: string}
     */
    private function formatPasswordStrengthUser(User $user): array
    {
        return [
            'id' => (int) $user->getId(),
            'name' => trim($user->getFirstname().' '.$user->getLastname()),
            'username' => (string) $user->getUsername(),
            'email' => (string) $user->getEmail(),
        ];
    }

    private function sendPasswordChangeRequest(User $user): bool
    {
        if (empty($user->getEmail())) {
            return false;
        }

        $platformTitle = $this->getPlatformTitle();
        $adminName = $this->getCurrentAdminName();
        $resetUrl = $this->generateUrl(
            'app_forgot_password_request',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $subject = \sprintf(
            $this->translator->trans('[%s] Please change your password'),
            $platformTitle
        );

        $body = \sprintf(
            $this->translator->trans("Dear %s,\n\nOur security enforcing process shows you use a password that is commonly used on the internet, which means your account could easily be stolen. It is probably a simple sequence or a very common word, we don't really know (we cannot see your password), but we ask you to please connect to the platform and request a password change. You can follow the link here: %s to do that now.\n\nPlease note we will never ask for your password in this process. You just enter your username or e-mail and we send you a link. If you are asked to introduce your existing password to do that, someone is probably trying to do Phishing on your account. Be safe, change your password now!\n\n%s\n%s"),
            $user->getFirstname(),
            $resetUrl,
            $adminName,
            $platformTitle
        );

        return $this->sendSecurityMail($user, $subject, $body);
    }

    private function forcePasswordReset(User $user): bool
    {
        if (empty($user->getEmail())) {
            return false;
        }

        $newPassword = $this->generateSecurePassword();

        try {
            $user
                ->setPlainPassword($newPassword)
                ->setPasswordUpdatedAt(new DateTimeImmutable())
            ;

            $this->userRepository->updateUser($user);
        } catch (Throwable) {
            return false;
        }

        $platformTitle = $this->getPlatformTitle();
        $adminName = $this->getCurrentAdminName();

        $subject = \sprintf(
            $this->translator->trans('[%s] Your password has been reset'),
            $platformTitle
        );

        $body = \sprintf(
            $this->translator->trans("Dear %s,\n\nOur security enforcing process flagged you as using a password that is commonly used on the internet, which means your account could easily be stolen. As a prevention measure, we have decided to initiate a password reset process. Your new, automatically generated password is now:\n\n%s\n\nPlease login to the platform soon (using this new password) to set your own, personal and secure, password.\n\nBe safe.\n\n%s\n%s"),
            $user->getFirstname(),
            $newPassword,
            $adminName,
            $platformTitle
        );

        return $this->sendSecurityMail($user, $subject, $body);
    }

    private function sendSecurityMail(User $user, string $subject, string $body): bool
    {
        $recipientName = trim($user->getFirstname().' '.$user->getLastname());
        $safeBody = nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        $senderName = api_get_setting('mail.mailer_from_name');
        $senderEmail = api_get_setting('mail.mailer_from_email');

        return api_mail_html(
            $recipientName,
            $user->getEmail(),
            $subject,
            $safeBody,
            $senderName,
            $senderEmail
        );
    }

    private function generateSecurePassword(): string
    {
        $requirements = Security::getPasswordRequirements()['min'];

        $lowercaseCharacters = 'abcdefghijkmnopqrstuvwxyz';
        $uppercaseCharacters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $digitCharacters = '23456789';
        $specialCharacters = '!@#$%&*-_?';
        $allCharacters = $lowercaseCharacters.$uppercaseCharacters.$digitCharacters.$specialCharacters;

        $characters = [];

        $this->addRequiredCharacters(
            $characters,
            $lowercaseCharacters,
            max(1, (int) $requirements['lowercase'])
        );
        $this->addRequiredCharacters(
            $characters,
            $uppercaseCharacters,
            max(1, (int) $requirements['uppercase'])
        );
        $this->addRequiredCharacters(
            $characters,
            $digitCharacters,
            max(1, (int) $requirements['numeric'])
        );
        $this->addRequiredCharacters(
            $characters,
            $specialCharacters,
            max(1, (int) $requirements['specials'])
        );

        $minimumLength = max(16, (int) $requirements['length'], \count($characters));

        while (\count($characters) < $minimumLength) {
            $characters[] = $this->pickRandomCharacter($allCharacters);
        }

        for ($i = \count($characters) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$characters[$i], $characters[$j]] = [$characters[$j], $characters[$i]];
        }

        return implode('', $characters);
    }

    /**
     * @param string[] $characters
     */
    private function addRequiredCharacters(array &$characters, string $source, int $amount): void
    {
        for ($i = 0; $i < $amount; $i++) {
            $characters[] = $this->pickRandomCharacter($source);
        }
    }

    private function pickRandomCharacter(string $characters): string
    {
        return $characters[random_int(0, \strlen($characters) - 1)];
    }

    private function getCurrentAdminName(): string
    {
        $admin = $this->getUser();

        if ($admin instanceof User) {
            $name = trim($admin->getFirstname().' '.$admin->getLastname());

            if ('' !== $name) {
                return $name;
            }
        }

        $senderName = (string) api_get_setting('mail.mailer_from_name');

        return '' !== trim($senderName) ? $senderName : 'Administrator';
    }

    private function getPlatformTitle(): string
    {
        $platformTitle = (string) api_get_setting('platform.site_name');

        if ('' === trim($platformTitle)) {
            $platformTitle = (string) api_get_setting('siteName');
        }

        return '' !== trim($platformTitle) ? $platformTitle : 'Chamilo';
    }

    /**
     * @return int[]
     */
    private function parseUserIds(string $rawUserIds): array
    {
        if ('' === trim($rawUserIds)) {
            return [];
        }

        $items = preg_split('/[,\s;]+/', $rawUserIds) ?: [];

        return array_values(array_unique(array_filter(
            array_map('intval', $items),
            static fn (int $id): bool => $id > 0
        )));
    }

    /**
     * @return int[]
     */
    private function getPasswordStrengthScanFilterUserIds(Request $request): array
    {
        return $request->getSession()->get(self::PASSWORD_STRENGTH_SCAN_FILTER_IDS_KEY, []);
    }

    private function clearPasswordStrengthScanSession(Request $request): void
    {
        $session = $request->getSession();
        $session->remove(self::PASSWORD_STRENGTH_SCAN_FOUND_KEY);
        $session->remove(self::PASSWORD_STRENGTH_SCAN_OFFSET_KEY);
        $session->remove(self::PASSWORD_STRENGTH_SCAN_TOTAL_KEY);
        $session->remove(self::PASSWORD_STRENGTH_SCAN_FILTER_IDS_KEY);
    }
}
