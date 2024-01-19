<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\TrackEDownloadsRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEDownloads.
 */
#[ORM\Table(name: 'track_e_downloads')]
#[ORM\Index(name: 'idx_ted_user_id', columns: ['down_user_id'])]
#[ORM\Entity(repositoryClass: TrackEDownloadsRepository::class)]
class TrackEDownloads
{
    #[ORM\Column(name: 'down_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $downId;

    #[ORM\Column(name: 'down_user_id', type: 'integer', nullable: true)]
    protected ?int $downUserId = null;

    #[ORM\Column(name: 'down_date', type: 'datetime', nullable: false)]
    protected DateTime $downDate;

    #[ORM\Column(name: 'down_doc_path', type: 'string', length: 255, nullable: true)]
    protected string $downDocPath;

    #[ORM\ManyToOne(targetEntity: ResourceLink::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'resource_link_id', referencedColumnName: 'id', onDelete: 'SET NULL', nullable: true)]
    protected ?ResourceLink $resourceLink = null;

    /**
     * Set downDocPath.
     *
     * @return TrackEDownloads
     */
    public function setDownDocPath(string $downDocPath)
    {
        $this->downDocPath = $downDocPath;

        return $this;
    }

    /**
     * Get downDocPath.
     *
     * @return string
     */
    public function getDownDocPath()
    {
        return $this->downDocPath;
    }

    /**
     * Get downId.
     *
     * @return int
     */
    public function getDownId()
    {
        return $this->downId;
    }

    /**
     * Set downUserId.
     *
     * @return TrackEDownloads
     */
    public function setDownUserId(int $downUserId)
    {
        $this->downUserId = $downUserId;

        return $this;
    }

    /**
     * Get downUserId.
     *
     * @return int
     */
    public function getDownUserId()
    {
        return $this->downUserId;
    }

    /**
     * Set downDate.
     *
     * @return TrackEDownloads
     */
    public function setDownDate(DateTime $downDate)
    {
        $this->downDate = $downDate;

        return $this;
    }

    /**
     * Get downDate.
     *
     * @return DateTime
     */
    public function getDownDate()
    {
        return $this->downDate;
    }

    /**
     * Set resourceLink.
     */
    public function setResourceLink(?ResourceLink $resourceLink): self
    {
        $this->resourceLink = $resourceLink;

        return $this;
    }
}
