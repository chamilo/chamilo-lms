<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platform settings.
 */
#[ORM\Table(name: 'settings_current', options: ['row_format' => 'DYNAMIC'])]
#[ORM\Index(columns: ['access_url'], name: 'access_url')]
#[ORM\UniqueConstraint(name: 'unique_setting', columns: ['variable', 'subkey', 'access_url'])]
#[ORM\Entity]
class SettingsCurrent
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class, cascade: ['persist'], inversedBy: 'settings')]
    #[ORM\JoinColumn(name: 'access_url', referencedColumnName: 'id')]
    protected AccessUrl $url;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'variable', type: 'string', length: 190, nullable: false)]
    protected string $variable;

    #[ORM\Column(name: 'subkey', type: 'string', length: 190, nullable: true)]
    protected ?string $subkey = null;

    #[ORM\Column(name: 'type', type: 'string', length: 255, nullable: true)]
    protected ?string $type = null;

    #[ORM\Column(name: 'category', type: 'string', length: 255, nullable: true)]
    protected ?string $category = null;

    #[ORM\Column(name: 'selected_value', type: 'text', nullable: true)]
    protected ?string $selectedValue = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'comment', type: 'string', length: 255, nullable: true)]
    protected ?string $comment = null;

    #[ORM\Column(name: 'scope', type: 'string', length: 50, nullable: true)]
    protected ?string $scope = null;

    #[ORM\Column(name: 'subkeytext', type: 'string', length: 255, nullable: true)]
    protected ?string $subkeytext = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'access_url_changeable', type: 'integer', nullable: false)]
    protected int $accessUrlChangeable;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'access_url_locked', type: 'integer', nullable: false, options: ['default' => 0])]
    protected int $accessUrlLocked = 0;

    public function __construct()
    {
        $this->accessUrlLocked = 0;
        $this->scope = '';
    }

    public function getVariable(): string
    {
        return $this->variable;
    }

    public function setVariable(string $variable): self
    {
        $this->variable = $variable;

        return $this;
    }

    public function getSubkey(): ?string
    {
        return $this->subkey;
    }

    public function setSubkey(string $subkey): self
    {
        $this->subkey = $subkey;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getSelectedValue(): ?string
    {
        return $this->selectedValue;
    }

    public function setSelectedValue(null|float|int|string $selectedValue): self
    {
        $this->selectedValue = $selectedValue;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getSubkeytext(): ?string
    {
        return $this->subkeytext;
    }

    public function setSubkeytext(string $subkeytext): self
    {
        $this->subkeytext = $subkeytext;

        return $this;
    }

    public function getAccessUrlChangeable(): int
    {
        return $this->accessUrlChangeable;
    }

    public function setAccessUrlChangeable(int $accessUrlChangeable): self
    {
        $this->accessUrlChangeable = $accessUrlChangeable;

        return $this;
    }

    public function getAccessUrlLocked(): int
    {
        return $this->accessUrlLocked;
    }

    public function setAccessUrlLocked(int $accessUrlLocked): self
    {
        $this->accessUrlLocked = $accessUrlLocked;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): AccessUrl
    {
        return $this->url;
    }

    public function setUrl(AccessUrl $url): self
    {
        $this->url = $url;

        return $this;
    }
}
