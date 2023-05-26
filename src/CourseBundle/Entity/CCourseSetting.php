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
#[ORM\Index(name: 'course', columns: ['c_id'])]
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

    public function setVariable(string $variable): self
    {
        $this->variable = $variable;

        return $this;
    }

    public function getVariable(): string
    {
        return $this->variable;
    }

    public function setSubkey(string $subkey): self
    {
        $this->subkey = $subkey;

        return $this;
    }

    /**
     * Get subkey.
     *
     * @return string
     */
    public function getSubkey()
    {
        return $this->subkey;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
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

    public function setSubkeytext(string $subkeytext): self
    {
        $this->subkeytext = $subkeytext;

        return $this;
    }

    /**
     * Get subkeytext.
     *
     * @return string
     */
    public function getSubkeytext()
    {
        return $this->subkeytext;
    }

    /**
     * Set cId.
     *
     * @return CCourseSetting
     */
    public function setCId(int $cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }
}
