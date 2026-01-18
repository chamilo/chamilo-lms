<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'portfolio_comment')]
class PortfolioComment extends AbstractResource implements ResourceInterface, Stringable
{
    public const VISIBILITY_VISIBLE = 1;
    public const VISIBILITY_PER_USER = 2;

    #[ORM\Column(type: 'smallint', options: ['default' => self::VISIBILITY_VISIBLE])]
    protected int $visibility;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Portfolio::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Portfolio $item;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime')]
    private DateTime $date;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isImportant;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $score;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isTemplate = false;

    public function __construct()
    {
        $this->isImportant = false;
        $this->visibility = self::VISIBILITY_VISIBLE;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getItem(): Portfolio
    {
        return $this->item;
    }

    public function setItem(Portfolio $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function isImportant(): bool
    {
        return $this->isImportant;
    }

    public function setIsImportant(bool $isImportant): void
    {
        $this->isImportant = $isImportant;
    }

    public function getExcerpt(int $count = 190): string
    {
        return api_get_short_text_from_html($this->content, $count);
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): void
    {
        $this->score = $score;
    }

    public function isTemplate(): bool
    {
        return $this->isTemplate;
    }

    public function setIsTemplate(bool $isTemplate): self
    {
        $this->isTemplate = $isTemplate;

        return $this;
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getResourceName(): string
    {
        if ($this->id) {
            return 'portfolio_comment_'.$this->id;
        }

        return Slugify::create()->slugify(
            $this->date->format('c')
        );
    }

    public function setResourceName(string $name): static
    {
        return $this->setContent($name);
    }

    public function __toString(): string
    {
        return $this->getContent();
    }

    public function getResourceIdentifier(): int|Uuid
    {
        return $this->getId();
    }
}
