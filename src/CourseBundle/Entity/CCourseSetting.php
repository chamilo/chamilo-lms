<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Course settings.
 */
#[ORM\Table(name: 'c_course_setting')]
#[ORM\Index(columns: ['c_id'], name: 'course')]
#[ORM\Entity]
class CCourseSetting
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'c_id', type: 'integer')]
    protected int $cId;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'variable', type: 'string', length: 255, nullable: false)]
    protected string $variable;

    #[ORM\Column(name: 'subkey', type: 'string', length: 255, nullable: true)]
    protected ?string $subkey = null;

    #[ORM\Column(name: 'type', type: 'string', length: 255, nullable: true)]
    protected ?string $type = null;

    #[ORM\Column(name: 'category', type: 'string', length: 255, nullable: true)]
    protected ?string $category = null;

    #[ORM\Column(name: 'value', type: 'text', nullable: true)]
    protected ?string $value = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected ?string $title = null;

    #[ORM\Column(name: 'comment', type: 'string', length: 255, nullable: true)]
    protected ?string $comment = null;

    #[ORM\Column(name: 'subkeytext', type: 'string', length: 255, nullable: true)]
    protected ?string $subkeytext = null;

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

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getTitle(): ?string
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

    public function getSubkeytext(): ?string
    {
        return $this->subkeytext;
    }

    public function setSubkeytext(string $subkeytext): self
    {
        $this->subkeytext = $subkeytext;

        return $this;
    }

    public function getCId(): int
    {
        return $this->cId;
    }

    public function setCId(int $cId): static
    {
        $this->cId = $cId;

        return $this;
    }
}
