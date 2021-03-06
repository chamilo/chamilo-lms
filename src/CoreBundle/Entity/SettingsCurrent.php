<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SettingsCurrent.
 *
 * @ORM\Table(
 *     name="settings_current",
 *     options={"row_format"="DYNAMIC"},
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="unique_setting",
 *             columns={"variable", "subkey", "access_url"})
 *     },
 *     indexes={
 *         @ORM\Index(name="access_url", columns={"access_url"})
 *     }
 * )
 * @ORM\Entity
 */
class SettingsCurrent
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="AccessUrl", inversedBy="settings", cascade={"persist"})
     * @ORM\JoinColumn(name="access_url", referencedColumnName="id")
     */
    protected AccessUrl $url;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="variable", type="string", length=190, nullable=false)
     */
    protected string $variable;

    /**
     * @ORM\Column(name="subkey", type="string", length=190, nullable=true)
     */
    protected ?string $subkey = null;

    /**
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    protected ?string $type = null;

    /**
     * @ORM\Column(name="category", type="string", length=255, nullable=true)
     */
    protected ?string $category = null;

    /**
     * @ORM\Column(name="selected_value", type="text", nullable=true)
     */
    protected ?string $selectedValue = null;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="comment", type="string", length=255, nullable=true)
     */
    protected ?string $comment = null;

    /**
     * @ORM\Column(name="scope", type="string", length=50, nullable=true)
     */
    protected string $scope;

    /**
     * @ORM\Column(name="subkeytext", type="string", length=255, nullable=true)
     */
    protected string $subkeytext;

    /**
     * @ORM\Column(name="access_url_changeable", type="integer", nullable=false)
     */
    protected int $accessUrlChangeable;

    /**
     * @ORM\Column(name="access_url_locked", type="integer", nullable=false, options={"default":0 })
     */
    protected int $accessUrlLocked = 0;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->accessUrlLocked = 0;
    }

    /**
     * Set variable.
     *
     * @return SettingsCurrent
     */
    public function setVariable(string $variable)
    {
        $this->variable = $variable;

        return $this;
    }

    /**
     * Get variable.
     *
     * @return string
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Set subkey.
     *
     * @return SettingsCurrent
     */
    public function setSubkey(string $subkey)
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

    /**
     * Set type.
     *
     * @return SettingsCurrent
     */
    public function setType(string $type)
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

    public function setCategory(?string $category): self
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

    /**
     * Set selectedValue.
     *
     * @return SettingsCurrent
     */
    public function setSelectedValue(?string $selectedValue)
    {
        $this->selectedValue = $selectedValue;

        return $this;
    }

    /**
     * Get selectedValue.
     *
     * @return string
     */
    public function getSelectedValue()
    {
        return $this->selectedValue;
    }

    /**
     * Set title.
     *
     * @return SettingsCurrent
     */
    public function setTitle(string $title)
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
     * Set comment.
     *
     * @return SettingsCurrent
     */
    public function setComment(string $comment)
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

    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get scope.
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
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

    public function setAccessUrlChangeable(int $accessUrlChangeable): self
    {
        $this->accessUrlChangeable = $accessUrlChangeable;

        return $this;
    }

    /**
     * Get accessUrlChangeable.
     *
     * @return int
     */
    public function getAccessUrlChangeable()
    {
        return $this->accessUrlChangeable;
    }

    public function setAccessUrlLocked(int $accessUrlLocked): self
    {
        $this->accessUrlLocked = (int) $accessUrlLocked;

        return $this;
    }

    /**
     * Get accessUrlLocked.
     *
     * @return int
     */
    public function getAccessUrlLocked()
    {
        return $this->accessUrlLocked;
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

    /**
     * @return AccessUrl
     */
    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl(AccessUrl $url): self
    {
        $this->url = $url;

        return $this;
    }
}
