<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Maps the legacy certificate archive for fresh installations.
 * Existing archive operations use DBAL and preserve the migration's schema.
 */
#[ORM\Table(name: 'gradebook_certificate_legacy_reference', options: ['row_format' => 'DYNAMIC'])]
#[ORM\Index(name: 'idx_gclr_legacy_user_id', columns: ['legacy_user_id'])]
#[ORM\Index(name: 'idx_gclr_cat_id', columns: ['cat_id'])]
#[ORM\Entity]
class GradebookCertificateLegacyReference
{
    #[ORM\Id]
    #[ORM\Column(name: 'certificate_id', type: 'bigint')]
    protected int $certificateId;

    #[ORM\Column(name: 'legacy_user_id', type: 'integer')]
    protected int $legacyUserId;

    #[ORM\Column(name: 'cat_id', type: 'integer', nullable: true)]
    protected ?int $categoryId = null;

    #[ORM\Column(name: 'score_certificate', type: 'float')]
    protected float $scoreCertificate;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'path_certificate', type: 'text', nullable: true)]
    protected ?string $pathCertificate = null;

    #[ORM\Column(name: 'html_content', type: 'text', nullable: true)]
    protected ?string $htmlContent = null;

    #[ORM\Column(name: 'content_sha256', type: 'string', length: 64, nullable: true, options: ['fixed' => true])]
    protected ?string $contentSha256 = null;

    #[ORM\Column(name: 'imported_at', type: 'datetime')]
    protected DateTime $importedAt;
}
