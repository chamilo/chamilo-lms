<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

// Immutable audit snapshots: deleting a user or resource must not delete scan history.
#[ORM\Entity]
#[ORM\Table(name: 'antivirus_scan')]
#[ORM\Index(name: 'idx_av_portal_id', columns: ['access_url_id', 'id'])]
#[ORM\Index(name: 'idx_av_portal_status_id', columns: ['access_url_id', 'status', 'id'])]
class AntivirusScan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'access_url_id', type: 'integer')]
    // @phpstan-ignore property.onlyRead (Hydrated by Doctrine; audit inserts use an independent DBAL connection.)
    private int $accessUrlId;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(name: 'course_id', type: 'integer', nullable: true)]
    private ?int $courseId = null;

    #[ORM\Column(name: 'session_id', type: 'integer', nullable: true)]
    private ?int $sessionId = null;

    #[ORM\Column(name: 'filename', type: 'string', length: 255)]
    // @phpstan-ignore property.onlyRead (Hydrated by Doctrine; audit inserts use an independent DBAL connection.)
    private string $filename;

    #[ORM\Column(name: 'file_size', type: 'bigint')]
    // @phpstan-ignore property.onlyRead (Hydrated by Doctrine; audit inserts use an independent DBAL connection.)
    private int $fileSize;

    #[ORM\Column(name: 'sha256', type: 'string', length: 64, nullable: true)]
    private ?string $sha256 = null;

    #[ORM\Column(name: 'scanner', type: 'string', length: 32)]
    // @phpstan-ignore property.onlyRead (Hydrated by Doctrine; audit inserts use an independent DBAL connection.)
    private string $scanner;

    #[ORM\Column(name: 'status', type: 'string', length: 16)]
    // @phpstan-ignore property.onlyRead (Hydrated by Doctrine; audit inserts use an independent DBAL connection.)
    private string $status;

    #[ORM\Column(name: 'threat_name', type: 'string', length: 255, nullable: true)]
    private ?string $threatName = null;

    #[ORM\Column(name: 'source', type: 'string', length: 255)]
    // @phpstan-ignore property.onlyRead (Hydrated by Doctrine; audit inserts use an independent DBAL connection.)
    private string $source;

    #[ORM\Column(name: 'scanned_at', type: 'datetime_immutable')]
    // @phpstan-ignore property.onlyRead (Hydrated by Doctrine; audit inserts use an independent DBAL connection.)
    private DateTimeImmutable $scannedAt;

    #[ORM\Column(name: 'duration_ms', type: 'integer')]
    // @phpstan-ignore property.onlyRead (Hydrated by Doctrine; audit inserts use an independent DBAL connection.)
    private int $durationMs;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccessUrlId(): int
    {
        return $this->accessUrlId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getCourseId(): ?int
    {
        return $this->courseId;
    }

    public function getSessionId(): ?int
    {
        return $this->sessionId;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function getSha256(): ?string
    {
        return $this->sha256;
    }

    public function getScanner(): string
    {
        return $this->scanner;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getThreatName(): ?string
    {
        return $this->threatName;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getScannedAt(): DateTimeImmutable
    {
        return $this->scannedAt;
    }

    public function getDurationMs(): int
    {
        return $this->durationMs;
    }
}
