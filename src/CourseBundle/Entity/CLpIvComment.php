<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLpIvComment.
 */
#[ORM\Table(name: 'c_lp_iv_comment')]
#[ORM\Index(name: 'course', columns: ['c_id'])]
#[ORM\Index(name: 'lp_iv_id', columns: ['lp_iv_id'])]
#[ORM\Entity]
class CLpIvComment
{
    public const SOURCE_LEARNER = 'learner';
    public const SOURCE_LMS = 'lms';

    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\Column(name: 'c_id', type: 'integer')]
    protected int $cId;

    #[ORM\Column(name: 'lp_iv_id', type: 'integer', nullable: false)]
    protected int $lpIvId;

    #[ORM\Column(name: 'order_id', type: 'integer', nullable: false)]
    protected int $orderId;

    #[ORM\Column(name: 'source', type: 'string', length: 16, nullable: false)]
    protected string $source;

    #[ORM\Column(name: 'comment', type: 'text', nullable: false)]
    protected string $comment;

    #[ORM\Column(name: 'location', type: 'string', length: 255, nullable: false)]
    protected string $location;

    #[ORM\Column(name: 'comment_timestamp', type: 'string', length: 32, nullable: false)]
    protected string $commentTimestamp;

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function setCId(int $cId): self
    {
        $this->cId = $cId;

        return $this;
    }

    public function getCId(): int
    {
        return $this->cId;
    }

    public function setLpIvId(int $lpIvId): self
    {
        $this->lpIvId = $lpIvId;

        return $this;
    }

    public function getLpIvId(): int
    {
        return $this->lpIvId;
    }

    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setCommentTimestamp(string $commentTimestamp): self
    {
        $this->commentTimestamp = $commentTimestamp;

        return $this;
    }

    public function getCommentTimestamp(): string
    {
        return $this->commentTimestamp;
    }
}
