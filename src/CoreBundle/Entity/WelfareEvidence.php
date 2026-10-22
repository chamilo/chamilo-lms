<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'welfare_evidence')]
#[ORM\Index(name: 'idx_welfare_evidence_report', columns: ['report_id'])]
class WelfareEvidence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WelfareReport::class)]
    #[ORM\JoinColumn(name: 'report_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public WelfareReport $report;

    #[ORM\Column(name: 'storage_key', type: 'string', length: 32, unique: true)]
    public string $storageKey;

    #[ORM\Column(name: 'filename_cipher', type: 'text')]
    public string $filenameCipher;

    #[ORM\Column(name: 'mime_type', type: 'string', length: 80)]
    public string $mimeType;

    #[ORM\Column(name: 'file_size', type: 'integer')]
    public int $fileSize;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt;
}
