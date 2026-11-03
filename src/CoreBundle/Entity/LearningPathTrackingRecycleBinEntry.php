<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Maps the learning path tracking recycle archive for fresh installations.
 * The recycle service manages archive rows through DBAL.
 */
#[ORM\Table(name: 'learning_path_tracking_recycle_bin')]
#[ORM\Index(name: 'idx_lp_tracking_recycle_context', columns: ['lp_id', 'course_id', 'session_id', 'restored_at'])]
#[ORM\Index(name: 'idx_lp_tracking_recycle_deleted', columns: ['deleted_at'])]
#[ORM\Entity]
class LearningPathTrackingRecycleBinEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'lp_id', type: 'integer')]
    protected int $lpId;

    #[ORM\Column(name: 'course_id', type: 'integer')]
    protected int $courseId;

    #[ORM\Column(name: 'session_id', type: 'integer', nullable: true)]
    protected ?int $sessionId = null;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    protected int $userId;

    #[ORM\Column(name: 'deleted_by', type: 'integer')]
    protected int $deletedBy;

    #[ORM\Column(name: 'deleted_at', type: 'datetime')]
    protected DateTime $deletedAt;

    #[ORM\Column(name: 'snapshot_sha256', type: 'string', length: 64, options: ['fixed' => true])]
    protected string $snapshotSha256;

    #[ORM\Column(name: 'snapshot', type: 'text')]
    protected string $snapshot;

    #[ORM\Column(name: 'restored_by', type: 'integer', nullable: true)]
    protected ?int $restoredBy = null;

    #[ORM\Column(name: 'restored_at', type: 'datetime', nullable: true)]
    protected ?DateTime $restoredAt = null;
}
