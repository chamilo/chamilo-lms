<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'welfare_message')]
#[ORM\Index(name: 'idx_welfare_message_report', columns: ['report_id'])]
class WelfareMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WelfareReport::class)]
    #[ORM\JoinColumn(name: 'report_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public WelfareReport $report;

    #[ORM\Column(name: 'reference_token', type: 'string', length: 32, unique: true)]
    public string $reference;

    #[ORM\Column(name: 'author_kind', type: 'string', length: 16)]
    public string $authorKind;

    #[ORM\Column(name: 'author_cipher', type: 'text')]
    public string $authorCipher;

    #[ORM\Column(name: 'body_cipher', type: 'text')]
    public string $bodyCipher;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt;
}
