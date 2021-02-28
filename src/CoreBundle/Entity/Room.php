<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Room.
 *
 * @ORM\Table(name="room")
 * @ORM\Entity
 */
class Room
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected string $title;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description;

    /**
     * @ORM\Column(name="geolocation", type="string", length=255, nullable=true, unique=false)
     */
    protected ?string $geolocation;

    /**
     * @ORM\Column(name="ip", type="string", length=39, nullable=true, unique=false)
     */
    protected ?string $ip;

    /**
     * @ORM\Column(name="ip_mask", type="string", length=6, nullable=true, unique=false)
     */
    protected ?string $ipMask;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\BranchSync")
     * @ORM\JoinColumn(name="branch_id", referencedColumnName="id")
     */
    protected BranchSync $branch;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return Room
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getGeolocation()
    {
        return $this->geolocation;
    }

    /**
     * @param string $geolocation
     *
     * @return Room
     */
    public function setGeolocation($geolocation)
    {
        $this->geolocation = $geolocation;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     *
     * @return Room
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    public function getIpMask()
    {
        return $this->ipMask;
    }

    /**
     * @param string $ipMask
     *
     * @return Room
     */
    public function setIpMask($ipMask)
    {
        $this->ipMask = $ipMask;

        return $this;
    }

    /**
     * @return BranchSync
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * @param BranchSync $branch
     *
     * @return $this
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;

        return $this;
    }
}
