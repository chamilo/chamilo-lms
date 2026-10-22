<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Welfare;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\WelfareEvidence;
use Chamilo\CoreBundle\Entity\WelfareMessage;
use Chamilo\CoreBundle\Entity\WelfareReport;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\PermissionHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Throwable;

use const DATE_ATOM;

/**
 * Authorization, tenant scoping and confidentiality live on the server, never in Vue.
 * All reporter lookup uses a keyed blind index; investigator queries do not join identity.
 */
final readonly class WelfareReportingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private RequestStack $requestStack,
        private PermissionHelper $permissionHelper,
        private UserRepository $userRepository,
        private WelfareCrypto $crypto,
        private WelfareEvidenceStorage $evidenceStorage,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @return array{user:User, urlId:int}
     */
    public function currentContext(): array
    {
        // In particular, blocking all switch-user requests prevents a platform admin
        // from impersonating a reporter to read or submit their confidential reports.
        if (
            !$this->security->isGranted('IS_AUTHENTICATED_FULLY')
            || $this->security->isGranted('ROLE_PREVIOUS_ADMIN')
            || $this->security->getToken() instanceof SwitchUserToken
        ) {
            throw new AccessDeniedHttpException('Confidential reporting requires a direct login.');
        }

        $user = $this->security->getUser();
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$user instanceof User || null === $user->getId() || !$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new AccessDeniedHttpException('An authenticated user and access URL are required.');
        }

        if ($this->accessUrlHelper->isMultiple()) {
            // AccessUrlHelper can return the first portal as a fallback for unknown
            // hosts. That is not acceptable for confidential content.
            $request = $this->requestStack->getMainRequest();
            if (
                null === $request
                || strtolower(rtrim($accessUrl->getUrl(), '/')) !== strtolower(rtrim($request->getSchemeAndHttpHost(), '/'))
            ) {
                throw new AccessDeniedHttpException('Confidential reporting requires an exact portal match.');
            }
        }

        $this->crypto->assertEnabled();

        return ['user' => $user, 'urlId' => (int) $accessUrl->getId()];
    }

    /**
     * Navigation only: never returns report details or user identities.
     * Impersonation must not display the confidential reporting entry point.
     */
    public function isAvailable(): bool
    {
        if (
            !$this->security->isGranted('IS_AUTHENTICATED_FULLY')
            || $this->security->isGranted('ROLE_PREVIOUS_ADMIN')
            || $this->security->getToken() instanceof SwitchUserToken
        ) {
            return false;
        }

        return $this->crypto->isReady();
    }

    public function minimumDistinctReporters(): int
    {
        $value = (int) $this->settingsManager->getSetting('security.confidential_reporting_min_reporters');

        return max(2, min(10, $value));
    }

    public function attachmentsEnabled(): bool
    {
        return 'true' === (string) $this->settingsManager->getSetting('security.confidential_reporting_attachments_enabled');
    }

    public function notificationsEnabled(): bool
    {
        return 'true' === (string) $this->settingsManager->getSetting('security.confidential_reporting_notifications_enabled');
    }

    public function isInvestigator(): bool
    {
        $codes = [];
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $codes[] = 'ADMIN';
        }
        if ($this->security->isGranted('ROLE_GLOBAL_ADMIN')) {
            $codes[] = 'GLOBAL_ADMIN';
        }
        if ($this->security->isGranted('ROLE_HR')) {
            $codes[] = 'HR';
        }

        return [] !== $codes && $this->permissionHelper->hasPermission('welfare:investigate', $codes);
    }

    public function assertInvestigator(): void
    {
        if (!$this->isInvestigator()) {
            throw new AccessDeniedHttpException('Confidential welfare investigation permission required.');
        }
    }

    /**
     * @return list<array{id:int, label:string}>
     */
    public function searchTargets(string $keyword, int $urlId, int $userId): array
    {
        $keyword = trim($keyword);
        if (mb_strlen($keyword) < 3 || mb_strlen($keyword) > 100) {
            return [];
        }

        $rows = $this->entityManager->getConnection()->executeQuery(
            'SELECT u.id, u.firstname, u.lastname, u.username
             FROM user u INNER JOIN access_url_rel_user aru ON aru.user_id = u.id
             WHERE aru.access_url_id = :urlId AND u.id <> :currentUser AND u.active = 1
               AND (u.firstname LIKE :keyword OR u.lastname LIKE :keyword OR u.username LIKE :keyword)
             ORDER BY u.lastname, u.firstname, u.id LIMIT 10',
            ['urlId' => $urlId, 'currentUser' => $userId, 'keyword' => '%'.addcslashes($keyword, '%_\\').'%'],
        )->fetchAllAssociative();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'label' => trim($row['firstname'].' '.$row['lastname']).' ('.$row['username'].')',
        ], $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function myReports(User $user, int $urlId): array
    {
        $reports = $this->entityManager->getRepository(WelfareReport::class)->findBy(
            ['accessUrlId' => $urlId, 'reporterDigest' => $this->crypto->reporterDigest($urlId, (int) $user->getId())],
            ['createdAt' => 'DESC'],
            100,
        );

        return array_map(fn (WelfareReport $report): array => $this->summary($report), $reports);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function investigationTargets(int $urlId): array
    {
        $this->assertInvestigator();
        $rows = $this->entityManager->createQueryBuilder()
            ->select('r.targetUserId AS targetId')
            ->addSelect('COUNT(DISTINCT r.reporterDigest) AS reporters')
            ->addSelect('COUNT(r.id) AS reports')
            ->addSelect('MAX(r.createdAt) AS latestAt')
            ->from(WelfareReport::class, 'r')
            ->where('r.accessUrlId = :accessUrlId')
            ->groupBy('r.targetUserId')
            ->having('COUNT(DISTINCT r.reporterDigest) >= :minReporters')
            ->setParameter('accessUrlId', $urlId)
            ->setParameter('minReporters', $this->minimumDistinctReporters())
            ->orderBy('latestAt', 'DESC')
            ->getQuery()->getArrayResult()
        ;

        $rows = array_values(array_filter($rows, fn (array $row): bool => $this->canInvestigateTarget((int) $row['targetId'])));

        return array_map(function (array $row): array {
            $target = $this->entityManager->find(User::class, (int) $row['targetId']);

            return [
                'targetId' => (int) $row['targetId'],
                'targetName' => $target instanceof User ? $target->getFullName() : 'Unavailable user',
                'reporters' => (int) $row['reporters'],
                'reports' => (int) $row['reports'],
                'latestAt' => substr($this->dateString($row['latestAt']), 0, 10),
            ];
        }, $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function reportsForTarget(int $targetId, int $urlId): array
    {
        $this->assertInvestigator();
        if (!$this->canInvestigateTarget($targetId) || !$this->thresholdReached($urlId, $targetId)) {
            throw new NotFoundHttpException('No eligible investigation was found.');
        }

        $reports = $this->entityManager->getRepository(WelfareReport::class)->findBy(
            ['targetUserId' => $targetId, 'accessUrlId' => $urlId],
            ['createdAt' => 'DESC'],
        );

        return array_map(fn (WelfareReport $report): array => $this->summary($report, true), $reports);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(string $reference, User $user, int $urlId): array
    {
        [$report, $owner] = $this->authorizeReport($reference, $user, $urlId);
        $messages = $this->entityManager->getRepository(WelfareMessage::class)->findBy(
            ['report' => $report],
            ['createdAt' => 'ASC'],
        );
        $attachments = $this->entityManager->getRepository(WelfareEvidence::class)->findBy(
            ['report' => $report],
            ['createdAt' => 'ASC'],
        );

        $contact = null;
        if (!$owner && $report->shareContact) {
            // Explicit opt-in is scoped to this one report and reversible by its owner.
            $reporterId = (int) $this->crypto->decrypt($report->reporterCipher, 'reporter:'.$report->reference);
            $reporter = $this->entityManager->find(User::class, $reporterId);
            if ($reporter instanceof User) {
                $contact = ['name' => $reporter->getFullName(), 'email' => $reporter->getEmail()];
            }
        }

        return [
            ...$this->summary($report, !$owner),
            'subject' => $this->crypto->decrypt($report->subjectCipher, 'subject:'.$report->reference),
            'body' => $this->crypto->decrypt($report->bodyCipher, 'body:'.$report->reference),
            'isOwner' => $owner,
            'shareContact' => $owner ? $report->shareContact : null,
            'contact' => $contact,
            'messages' => array_map(fn (WelfareMessage $message): array => [
                'id' => $message->id,
                'authorKind' => $message->authorKind,
                'body' => $this->crypto->decrypt($message->bodyCipher, 'message:'.$message->reference),
                'createdAt' => $owner ? $this->dateString($message->createdAt) : $message->createdAt->format('Y-m-d'),
            ], $messages),
            'attachments' => array_map(fn (WelfareEvidence $attachment): array => [
                'id' => $attachment->id,
                'filename' => $this->crypto->decrypt(
                    $attachment->filenameCipher,
                    'filename:'.$report->reference.':'.$attachment->storageKey,
                ),
                'mimeType' => $attachment->mimeType,
                'size' => $attachment->fileSize,
            ], $attachments),
        ];
    }

    /**
     * @param list<UploadedFile> $files
     */
    public function create(User $user, int $urlId, int $targetId, string $subject, string $body, bool $shareContact, array $files): string
    {
        $this->validateText($subject, $body);
        if ($targetId <= 0 || $targetId === (int) $user->getId() || !$this->userBelongsToPortal($targetId, $urlId)) {
            throw new BadRequestHttpException('Select a valid user from the current portal.');
        }
        $this->validateFiles($files);

        $report = new WelfareReport();
        $report->reference = bin2hex(random_bytes(16));
        $report->accessUrlId = $urlId;
        $report->targetUserId = $targetId;
        $report->reporterDigest = $this->crypto->reporterDigest($urlId, (int) $user->getId());
        $report->reporterCipher = $this->crypto->encrypt((string) $user->getId(), 'reporter:'.$report->reference);
        $report->subjectCipher = $this->crypto->encrypt($subject, 'subject:'.$report->reference);
        $report->bodyCipher = $this->crypto->encrypt($body, 'body:'.$report->reference);
        $report->shareContact = $shareContact;
        $report->createdAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $connection = $this->entityManager->getConnection();
        $createdFiles = [];
        $connection->beginTransaction();

        try {
            $this->entityManager->persist($report);
            $this->entityManager->flush();
            $createdFiles = $this->attachEvidence($report, $files, $createdFiles);
            $this->entityManager->flush();
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();
            foreach ($createdFiles as $key) {
                $this->evidenceStorage->remove($key);
            }

            throw $exception;
        }

        return $report->reference;
    }

    /**
     * @param list<UploadedFile> $files
     */
    public function reply(string $reference, User $user, int $urlId, string $body, array $files): void
    {
        [$report, $owner] = $this->authorizeReport($reference, $user, $urlId);
        if ('' === trim($body) && [] === $files) {
            throw new BadRequestHttpException('A message or evidence attachment is required.');
        }
        if (mb_strlen($body) > 10000) {
            throw new BadRequestHttpException('Message exceeds the maximum length.');
        }
        $this->validateFiles($files);
        $this->ensureEvidenceLimit($report, \count($files));

        $message = new WelfareMessage();
        $message->report = $report;
        $message->reference = bin2hex(random_bytes(16));
        $message->authorKind = $owner ? 'reporter' : 'investigator';
        $message->authorCipher = $this->crypto->encrypt((string) $user->getId(), 'author:'.$message->reference);
        $message->bodyCipher = $this->crypto->encrypt($body, 'message:'.$message->reference);
        $message->createdAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $connection = $this->entityManager->getConnection();
        $createdFiles = [];
        $connection->beginTransaction();

        try {
            $this->entityManager->persist($message);
            $this->entityManager->flush();
            $createdFiles = $this->attachEvidence($report, $files, $createdFiles);
            $this->entityManager->flush();
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();
            foreach ($createdFiles as $key) {
                $this->evidenceStorage->remove($key);
            }

            throw $exception;
        }
    }

    public function updateContactConsent(string $reference, User $user, int $urlId, bool $shareContact): void
    {
        [$report, $owner] = $this->authorizeReport($reference, $user, $urlId);
        if (!$owner) {
            throw new AccessDeniedHttpException('Only the reporter can change contact consent.');
        }
        $report->shareContact = $shareContact;
        $this->entityManager->flush();
    }

    /**
     * @return array{contents:string, filename:string, mimeType:string}
     */
    public function download(int $id, string $reference, User $user, int $urlId): array
    {
        [$report] = $this->authorizeReport($reference, $user, $urlId);
        $evidence = $this->entityManager->getRepository(WelfareEvidence::class)->findOneBy(['id' => $id, 'report' => $report]);
        if (!$evidence instanceof WelfareEvidence) {
            throw new NotFoundHttpException('Evidence is not available.');
        }

        return [
            'contents' => $this->evidenceStorage->read($evidence->storageKey, $reference),
            'filename' => $this->crypto->decrypt($evidence->filenameCipher, 'filename:'.$reference.':'.$evidence->storageKey),
            'mimeType' => $evidence->mimeType,
        ];
    }

    /**
     * @return array{0:WelfareReport, 1:bool}
     */
    private function authorizeReport(string $reference, User $user, int $urlId): array
    {
        $report = $this->entityManager->getRepository(WelfareReport::class)->findOneBy([
            'reference' => $reference,
            'accessUrlId' => $urlId,
        ]);
        if (!$report instanceof WelfareReport) {
            throw new NotFoundHttpException('Confidential report was not found.');
        }

        $owner = hash_equals($report->reporterDigest, $this->crypto->reporterDigest($urlId, (int) $user->getId()));
        if (!$owner && (!$this->canInvestigateTarget($report->targetUserId) || !$this->thresholdReached($urlId, $report->targetUserId))) {
            // Same response for unknown and unauthorized references (including below-threshold cases).
            throw new NotFoundHttpException('Confidential report was not found.');
        }

        return [$report, $owner];
    }

    private function canInvestigateTarget(int $targetId): bool
    {
        if (!$this->isInvestigator()) {
            return false;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || (int) $user->getId() === $targetId) {
            // A person who is being reported must never investigate themselves,
            // even if they are a platform administrator.
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // HR investigators are limited to users they explicitly follow.
        return $this->userRepository->isUserFollowedByHrm($targetId, (int) $user->getId());
    }

    private function thresholdReached(int $urlId, int $targetId): bool
    {
        $count = $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT r.reporterDigest)')
            ->from(WelfareReport::class, 'r')
            ->where('r.accessUrlId = :urlId')
            ->andWhere('r.targetUserId = :targetId')
            ->setParameter('urlId', $urlId)
            ->setParameter('targetId', $targetId)
            ->getQuery()->getSingleScalarResult()
        ;

        return (int) $count >= $this->minimumDistinctReporters();
    }

    private function userBelongsToPortal(int $userId, int $urlId): bool
    {
        $user = $this->entityManager->find(User::class, $userId);
        if (!$user instanceof User || 1 !== $user->getActive()) {
            return false;
        }

        return (bool) $this->entityManager->getConnection()->fetchOne(
            'SELECT 1 FROM access_url_rel_user WHERE access_url_id = :urlId AND user_id = :userId LIMIT 1',
            ['urlId' => $urlId, 'userId' => $userId],
            ['urlId' => ParameterType::INTEGER, 'userId' => ParameterType::INTEGER],
        );
    }

    private function validateText(string $subject, string $body): void
    {
        if (mb_strlen(trim($subject)) < 3 || mb_strlen($subject) > 160 || mb_strlen(trim($body)) < 10 || mb_strlen($body) > 10000) {
            throw new BadRequestHttpException('A subject (3–160 characters) and message (10–10000 characters) are required.');
        }
    }

    /**
     * @param list<UploadedFile> $files
     */
    private function validateFiles(array $files): void
    {
        if ([] !== $files && !$this->attachmentsEnabled()) {
            throw new BadRequestHttpException('Confidential evidence attachments are disabled.');
        }
        if (\count($files) > WelfareEvidenceStorage::MAX_FILES) {
            throw new BadRequestHttpException('Too many evidence attachments.');
        }
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid() || $file->getSize() > WelfareEvidenceStorage::MAX_SIZE) {
                throw new BadRequestHttpException('An invalid evidence file was supplied.');
            }
        }
    }

    private function ensureEvidenceLimit(WelfareReport $report, int $newFiles): void
    {
        if ($this->entityManager->getRepository(WelfareEvidence::class)->count(['report' => $report]) + $newFiles > 10) {
            throw new BadRequestHttpException('A report can contain at most ten evidence attachments.');
        }
    }

    /**
     * @param list<UploadedFile> $files @param list<string> $createdFiles @return list<string>
     */
    private function attachEvidence(WelfareReport $report, array $files, array $createdFiles): array
    {
        try {
            foreach ($files as $file) {
                $result = $this->evidenceStorage->store($file, $report->reference);
                $createdFiles[] = $result['storageKey'];
                $attachment = new WelfareEvidence();
                $attachment->report = $report;
                $attachment->storageKey = $result['storageKey'];
                $attachment->filenameCipher = $result['filenameCipher'];
                $attachment->mimeType = $result['mimeType'];
                $attachment->fileSize = $result['fileSize'];
                $attachment->createdAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                $this->entityManager->persist($attachment);
            }
        } catch (Throwable $exception) {
            // The outer caller only receives the list when this method returns.
            // Clean up files written earlier in a failed multi-file upload here.
            foreach ($createdFiles as $key) {
                $this->evidenceStorage->remove($key);
            }

            throw $exception;
        }

        return $createdFiles;
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(WelfareReport $report, bool $forInvestigator = false): array
    {
        $target = $this->entityManager->find(User::class, $report->targetUserId);

        return [
            'reference' => $report->reference,
            'targetId' => $report->targetUserId,
            'targetName' => $target instanceof User ? $target->getFullName() : 'Unavailable user',
            'subject' => $this->crypto->decrypt($report->subjectCipher, 'subject:'.$report->reference),
            'createdAt' => $forInvestigator ? $report->createdAt->format('Y-m-d') : $this->dateString($report->createdAt),
        ];
    }

    private function dateString(DateTimeInterface|string $date): string
    {
        return $date instanceof DateTimeInterface ? $date->format(DATE_ATOM) : $date;
    }
}
