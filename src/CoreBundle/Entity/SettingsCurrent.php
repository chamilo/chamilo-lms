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
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var AccessUrl
     *
     * @ORM\ManyToOne(targetEntity="AccessUrl", inversedBy="settings", cascade={"persist"})
     * @ORM\JoinColumn(name="access_url", referencedColumnName="id")
     */
    protected $url;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="variable", type="string", length=190, nullable=false)
     */
    protected string $variable;

    /**
     * @ORM\Column(name="subkey", type="string", length=190, nullable=true)
     */
    protected ?string $subkey;

    /**
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    protected ?string $type;

    /**
     * @ORM\Column(name="category", type="string", length=255, nullable=true)
     */
    protected ?string $category;

    /**
     * @ORM\Column(name="selected_value", type="text", nullable=true)
     */
    protected ?string $selectedValue;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="comment", type="string", length=255, nullable=true)
     */
    protected ?string $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="scope", type="string", length=50, nullable=true)
     */
    protected $scope;

    /**
     * @var string
     *
     * @ORM\Column(name="subkeytext", type="string", length=255, nullable=true)
     */
    protected $subkeytext;

    /**
     * @var int
     *
     * @ORM\Column(name="access_url_changeable", type="integer", nullable=false)
     */
    protected $accessUrlChangeable;

    /**
     * @var int
     *
     * @ORM\Column(name="access_url_locked", type="integer", nullable=false, options={"default":0 })
     */
    protected $accessUrlLocked = 0;

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
     * @param string $variable
     *
     * @return SettingsCurrent
     */
    public function setVariable($variable)
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
     * @param string $subkey
     *
     * @return SettingsCurrent
     */
    public function setSubkey($subkey)
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
     * @param string $type
     *
     * @return SettingsCurrent
     */
    public function setType($type)
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

    /**
     * Set category.
     *
     * @param string $category
     *
     * @return SettingsCurrent
     */
    public function setCategory($category)
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
     * @param string $selectedValue
     *
     * @return SettingsCurrent
     */
    public function setSelectedValue($selectedValue)
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
     * @param string $title
     *
     * @return SettingsCurrent
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
     * Set comment.
     *
     * @param string $comment
     *
     * @return SettingsCurrent
     */
    public function setComment($comment)
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

    /**
     * Set scope.
     *
     * @param string $scope
     */
    public function setScope($scope): self
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

    /**
     * Set subkeytext.
     *
     * @param string $subkeytext
     */
    public function setSubkeytext($subkeytext): self
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
     * Set accessUrlChangeable.
     *
     * @param int $accessUrlChangeable
     */
    public function setAccessUrlChangeable($accessUrlChangeable): self
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

    /**
     * Set accessUrlLocked.
     *
     * @param int $accessUrlLocked
     */
    public function setAccessUrlLocked($accessUrlLocked): self
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

    /**
     * @param AccessUrl $url
     */
    public function setUrl($url): self
    {
        $this->url = $url;

        return $this;
    }
}
