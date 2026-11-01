<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

use const DATE_ATOM;

/**
 * Append-only teacher assessment of a Hot Potatoes attempt.
 * This is not an automatic or independently verified HTML score.
 */
#[ORM\Entity]
#[ORM\Table(name: 'hotpotatoes_attempt_review')]
#[ORM\Index(name: 'idx_hp_review_attempt_id', columns: ['attempt_id', 'id'])]
class HotPotatoesAttemptReview
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'attempt_id', type: 'integer')]
    private int $attemptId;

    #[ORM\Column(name: 'score_percent', type: 'smallint')]
    private int $scorePercent;

    #[ORM\Column(name: 'reviewed_by_user_id', type: 'integer')]
    private int $reviewedByUserId;

    #[ORM\Column(name: 'reviewed_at', type: 'datetime_immutable')]
    private DateTimeImmutable $reviewedAt;

    public function __construct(int $attemptId, int $scorePercent, int $reviewedByUserId)
    {
        if ($attemptId <= 0 || $reviewedByUserId <= 0 || $scorePercent < 0 || $scorePercent > 100) {
            throw new InvalidArgumentException('Invalid Hot Potatoes review values.');
        }

        $this->attemptId = $attemptId;
        $this->scorePercent = $scorePercent;
        $this->reviewedByUserId = $reviewedByUserId;
        $this->reviewedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttemptId(): int
    {
        return $this->attemptId;
    }

    public function getScorePercent(): int
    {
        return $this->scorePercent;
    }

    public function getReviewedByUserId(): int
    {
        return $this->reviewedByUserId;
    }

    public function getReviewedAt(): DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    /**
     * @return array{scorePercent: int, reviewedByUserId: int, reviewedAt: string, scoreSource: string}
     */
    public function toSummary(): array
    {
        return [
            'scorePercent' => $this->scorePercent,
            'reviewedByUserId' => $this->reviewedByUserId,
            'reviewedAt' => $this->reviewedAt->format(DATE_ATOM),
            'scoreSource' => 'teacher_review',
        ];
    }
}
