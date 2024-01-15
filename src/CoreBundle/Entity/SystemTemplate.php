<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SystemTemplate.
 */
#[ORM\Table(name: 'system_template')]
#[ORM\Entity]
class SystemTemplate
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'title', type: 'string', length: 250, nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'comment', type: 'text', nullable: false)]
    protected string $comment;

    #[ORM\ManyToOne(targetEntity: Asset::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Asset $image = null;

    #[ORM\Column(name: 'content', type: 'text', nullable: false)]
    protected string $content;

    #[ORM\Column(name: 'language', type: 'string', length: 40, nullable: true)]
    protected string $language;

    public function __construct()
    {
        $this->comment = '';
    }

    public function setTitle(string $title): self
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

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    public function getImage(): ?Asset
    {
        return $this->image;
    }

    public function setImage(?Asset $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function hasImage(): bool
    {
        return null !== $this->image;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }
}
