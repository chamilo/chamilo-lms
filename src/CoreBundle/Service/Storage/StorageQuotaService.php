<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Storage;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UnexpectedValueException;

use const PHP_INT_MAX;

/**
 * Separate My Files and Dropbox storage limits. Course/Group/BuyCourses limits
 * remain owned by the existing Documents quota implementation.
 *
 * Call lockUser()/lockCourse() in the same database transaction as the write,
 * then assert*Quota() before persisting the file. A row lock serializes uploads.
 */
final readonly class StorageQuotaService
{
    private const BYTES_PER_MB = 1048576;

    public function __construct(
        private EntityManagerInterface $em,
        private SettingsManager $settingsManager,
    ) {}

    public function lockUser(User $user): void
    {
        $this->em->lock($this->em->getReference(User::class, (int) $user->getId()), LockMode::PESSIMISTIC_WRITE);
    }

    public function lockCourse(Course $course): void
    {
        $this->em->lock($this->em->getReference(Course::class, (int) $course->getId()), LockMode::PESSIMISTIC_WRITE);
    }

    public function personalQuotaMb(): int
    {
        return $this->quotaMb('platform.personal_files_quota_mb');
    }

    public function dropboxUserQuotaMb(): int
    {
        return $this->quotaMb('dropbox.dropbox_user_quota_mb');
    }

    public function dropboxCourseQuotaMb(): int
    {
        return $this->quotaMb('dropbox.dropbox_course_quota_mb');
    }

    public function personalUsedBytes(int $userId): int
    {
        // Charge only owned resources, not each recipient of a shared file.
        // Count all stored variants on each PersonalFile ResourceNode.
        return (int) $this->em->getConnection()->fetchOne(
            'SELECT COALESCE(SUM(rf.size), 0)
               FROM personal_file pf
               INNER JOIN resource_node rn ON rn.id = pf.resource_node_id
               INNER JOIN resource_file rf ON rf.resource_node_id = rn.id
              WHERE rn.creator_id = :userId',
            ['userId' => $userId],
        );
    }

    public function dropboxUserUsedBytes(int $userId): int
    {
        // A sent file remains stored when a recipient only hides its visibility.
        return (int) $this->em->getConnection()->fetchOne(
            'SELECT COALESCE(SUM(filesize), 0) FROM c_dropbox_file WHERE uploader_id = :userId',
            ['userId' => $userId],
        );
    }

    public function dropboxCourseUsedBytes(int $courseId): int
    {
        return (int) $this->em->getConnection()->fetchOne(
            'SELECT COALESCE(SUM(filesize), 0) FROM c_dropbox_file WHERE c_id = :courseId',
            ['courseId' => $courseId],
        );
    }

    public function assertPersonalQuota(User $user, int $incomingBytes, int $replacedBytes = 0): void
    {
        $limit = $this->personalQuotaMb();
        if ($limit <= 0) {
            return;
        }

        $this->assertCapacity(
            $this->personalUsedBytes((int) $user->getId()),
            $incomingBytes - $replacedBytes,
            $limit,
            'My Files storage quota exceeded.',
        );
    }

    public function assertDropboxQuotas(User $user, Course $course, int $incomingBytes): void
    {
        $userLimit = $this->dropboxUserQuotaMb();
        if ($userLimit > 0) {
            $this->assertCapacity(
                $this->dropboxUserUsedBytes((int) $user->getId()),
                $incomingBytes,
                $userLimit,
                'Dropbox user storage quota exceeded.',
            );
        }

        $courseLimit = $this->dropboxCourseQuotaMb();
        if ($courseLimit > 0) {
            $this->assertCapacity(
                $this->dropboxCourseUsedBytes((int) $course->getId()),
                $incomingBytes,
                $courseLimit,
                'Dropbox course storage quota exceeded.',
            );
        }
    }

    public function normalizeParentNodeId(mixed $value): int
    {
        if (\is_int($value) && $value > 0) {
            return $value;
        }

        if (\is_string($value)) {
            $value = trim($value);
            if (ctype_digit($value)) {
                return (int) $value;
            }
            if (preg_match('#^/api/resource_nodes/(\d+)$#', $value, $matches)) {
                return (int) $matches[1];
            }
        }

        return 0;
    }

    public function assertPersonalParent(User $user, int $parentNodeId): void
    {
        $root = $user->getResourceNode();
        $parent = $parentNodeId > 0
            ? $this->em->getRepository(ResourceNode::class)->find($parentNodeId)
            : null;
        if (!$root instanceof ResourceNode || !$parent instanceof ResourceNode) {
            throw new AccessDeniedHttpException('Invalid personal files folder.');
        }

        // Walk the persisted ancestry rather than comparing path string prefixes:
        // two user roots may have similar paths, but never the same ancestor.
        for ($node = $parent; null !== $node; $node = $node->getParent()) {
            if ($node->getId() === $root->getId()) {
                return;
            }
        }

        throw new AccessDeniedHttpException('Personal files folder does not belong to the current user.');
    }

    public function isPersonalFileNode(ResourceNode $node): bool
    {
        return null !== $this->em->getRepository(PersonalFile::class)->findOneBy(['resourceNode' => $node]);
    }

    private function quotaMb(string $setting): int
    {
        // 0 = unlimited: this preserves current behavior until admin opts in.
        $value = trim((string) ($this->settingsManager->getSetting($setting, true) ?? '0'));

        if (!ctype_digit($value)) {
            throw new UnexpectedValueException('Invalid storage quota setting: '.$setting);
        }

        return min((int) $value, intdiv(PHP_INT_MAX, self::BYTES_PER_MB));
    }

    private function assertCapacity(int $usedBytes, int $deltaBytes, int $limitMb, string $message): void
    {
        if ($deltaBytes <= 0) {
            return;
        }

        $limitBytes = $limitMb * self::BYTES_PER_MB;
        if ($usedBytes > $limitBytes - $deltaBytes) {
            throw new BadRequestHttpException($message);
        }
    }
}
