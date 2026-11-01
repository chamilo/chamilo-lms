<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

use const DATE_ATOM;

/**
 * Auditable activity launches and learner-acknowledged completions.
 * No score is stored because Hot Potatoes HTML cannot attest its own grading.
 */
#[ORM\Entity]
#[ORM\Table(name: 'hotpotatoes_attempt')]
#[ORM\Index(name: 'idx_hp_attempt_course_document', columns: ['course_id', 'document_id'])]
#[ORM\Index(name: 'idx_hp_attempt_user_started', columns: ['user_id', 'started_at'])]
class HotPotatoesAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'document_id', type: 'integer')]
    private int $documentId;

    #[ORM\Column(name: 'entrypoint', type: 'string', length: 512)]
    private string $entrypoint;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    private int $userId;

    #[ORM\Column(name: 'course_id', type: 'integer')]
    private int $courseId;

    #[ORM\Column(name: 'session_id', type: 'integer')]
    private int $sessionId;

    #[ORM\Column(name: 'group_id', type: 'integer')]
    private int $groupId;

    #[ORM\Column(name: 'started_at', type: 'datetime_immutable')]
    private DateTimeImmutable $startedAt;

    #[ORM\Column(name: 'completed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    public function __construct(int $documentId, string $entrypoint, int $userId, int $courseId, int $sessionId, int $groupId)
    {
        $this->documentId = $documentId;
        $this->entrypoint = $entrypoint;
        $this->userId = $userId;
        $this->courseId = $courseId;
        $this->sessionId = $sessionId;
        $this->groupId = $groupId;
        $this->startedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getDocumentId(): int
    {
        return $this->documentId;
    }
    public function getEntrypoint(): string
    {
        return $this->entrypoint;
    }
    public function getUserId(): int
    {
        return $this->userId;
    }
    public function getCourseId(): int
    {
        return $this->courseId;
    }
    public function getSessionId(): int
    {
        return $this->sessionId;
    }
    public function getGroupId(): int
    {
        return $this->groupId;
    }
    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }
    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function complete(): void
    {
        $this->completedAt ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    /**
     * @return array<string, int|string|bool|null>
     */
    public function toSummary(bool $includeUser): array
    {
        $data = [
            'id' => $this->id,
            'entrypoint' => $this->entrypoint,
            'startedAt' => $this->startedAt->format(DATE_ATOM),
            'completedAt' => $this->completedAt?->format(DATE_ATOM),
            'status' => null === $this->completedAt ? 'started' : 'completed',
            'graded' => false,
        ];
        if ($includeUser) {
            $data['userId'] = $this->userId;
        }

        return $data;
    }
}
