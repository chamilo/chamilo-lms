<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Not an API resource: the reporter identity and encrypted columns must never be serialized.
 */
#[ORM\Entity]
#[ORM\Table(name: 'welfare_report')]
#[ORM\Index(name: 'idx_welfare_report_target', columns: ['access_url_id', 'target_user_id'])]
#[ORM\Index(name: 'idx_welfare_report_owner', columns: ['access_url_id', 'reporter_digest'])]
class WelfareReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(name: 'reference_token', type: 'string', length: 32, unique: true)]
    public string $reference;

    #[ORM\Column(name: 'access_url_id', type: 'integer')]
    public int $accessUrlId;

    #[ORM\Column(name: 'target_user_id', type: 'integer')]
    public int $targetUserId;

    #[ORM\Column(name: 'reporter_digest', type: 'string', length: 64)]
    public string $reporterDigest;

    #[ORM\Column(name: 'reporter_cipher', type: 'text')]
    public string $reporterCipher;

    #[ORM\Column(name: 'subject_cipher', type: 'text')]
    public string $subjectCipher;

    #[ORM\Column(name: 'body_cipher', type: 'text')]
    public string $bodyCipher;

    #[ORM\Column(name: 'share_contact', type: 'boolean')]
    public bool $shareContact = false;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt;
}
