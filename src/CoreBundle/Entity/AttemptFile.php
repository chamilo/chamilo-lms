<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="attempt_file",
 * )
 * @ORM\Entity
 */
class AttemptFile
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid")
     */
    protected Uuid $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\TrackEAttempt", inversedBy="attemptFiles")
     * @ORM\JoinColumn(name="attempt_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[Assert\NotNull]
    protected TrackEAttempt $attempt;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Asset", cascade={"remove"} )
     * @ORM\JoinColumn(name="asset_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?Asset $asset;

    /**
     * @ORM\Column(name="comment", type="text", nullable=false)
     */
    protected string $comment;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->comment = '';
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAttempt(): TrackEAttempt
    {
        return $this->attempt;
    }

    public function setAttempt(TrackEAttempt $attempt): self
    {
        $this->attempt = $attempt;

        return $this;
    }

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(?Asset $asset): self
    {
        $this->asset = $asset;

        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
