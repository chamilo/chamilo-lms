<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class PortfolioAttachment.
 *
 * @package Chamilo\CoreBundle\Entity
 *
 * @ORM\Table(name="portfolio_attachment")
 * Add @ to the next line if api_get_configuration_value('allow_portfolio_tool') is true
 * ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\PortfolioAttachmentRepository")
 */
class PortfolioAttachment
{
    public const TYPE_ITEM = 1;
    public const TYPE_COMMENT = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255)
     */
    protected $path;

    /**
     * @var string|null
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var int
     *
     * @ORM\Column(name="size", type="integer")
     */
    protected $size;
    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255)
     */
    protected $filename;
    /**
     * @var int
     *
     * @ORM\Column(name="origin_id", type="integer")
     */
    private $origin;
    /**
     * @var int
     *
     * @ORM\Column(name="origin_type", type="integer")
     */
    private $originType;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return PortfolioAttachment
     */
    public function setPath(string $path): PortfolioAttachment
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     *
     * @return PortfolioAttachment
     */
    public function setComment(?string $comment): PortfolioAttachment
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param int $size
     *
     * @return PortfolioAttachment
     */
    public function setSize(int $size): PortfolioAttachment
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     *
     * @return PortfolioAttachment
     */
    public function setFilename(string $filename): PortfolioAttachment
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrigin(): int
    {
        return $this->origin;
    }

    /**
     * @param int $origin
     *
     * @return PortfolioAttachment
     */
    public function setOrigin(int $origin): PortfolioAttachment
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @return int
     */
    public function getOriginType(): int
    {
        return $this->originType;
    }

    /**
     * @param int $originType
     *
     * @return PortfolioAttachment
     */
    public function setOriginType(int $originType): PortfolioAttachment
    {
        $this->originType = $originType;

        return $this;
    }
}
