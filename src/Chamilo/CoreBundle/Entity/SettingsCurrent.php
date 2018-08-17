<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SettingsCurrent.
 *
 * @ORM\Table(
 *     name="settings_current",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="unique_setting", columns={"variable", "subkey", "access_url"})},
 *     indexes={@ORM\Index(name="access_url", columns={"access_url"})})
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
     * @var string
     *
     * @ORM\Column(name="variable", type="string", length=255, nullable=true)
     */
    protected $variable;

    /**
     * @var string
     *
     * @ORM\Column(name="subkey", type="string", length=255, nullable=true)
     */
    protected $subkey;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=255, nullable=true)
     */
    protected $category;

    /**
     * @var string
     *
     * @ORM\Column(name="selected_value", type="string", length=255, nullable=true)
     */
    protected $selectedValue;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=255, nullable=true)
     */
    protected $comment;

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
     * @ORM\Column(name="access_url", type="integer", nullable=false, options={"default": 1 } )
     */
    protected $accessUrl;

    /**
     * @var int
     *
     * @ORM\Column(name="access_url_changeable", type="integer", nullable=false)
     */
    protected $accessUrlChangeable;

    /**
     * @var int
     *
     * @ORM\Column(name="access_url_locked", type="integer", nullable=false, options={"default": 0 } )
     */
    protected $accessUrlLocked = 0;

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
     *
     * @return SettingsCurrent
     */
    public function setScope($scope)
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
     *
     * @return SettingsCurrent
     */
    public function setSubkeytext($subkeytext)
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
     * Set accessUrl.
     *
     * @param int $accessUrl
     *
     * @return SettingsCurrent
     */
    public function setAccessUrl($accessUrl)
    {
        $this->accessUrl = $accessUrl;

        return $this;
    }

    /**
     * Get accessUrl.
     *
     * @return int
     */
    public function getAccessUrl()
    {
        return $this->accessUrl;
    }

    /**
     * Set accessUrlChangeable.
     *
     * @param int $accessUrlChangeable
     *
     * @return SettingsCurrent
     */
    public function setAccessUrlChangeable($accessUrlChangeable)
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
     *
     * @return SettingsCurrent
     */
    public function setAccessUrlLocked($accessUrlLocked)
    {
        $this->accessUrlLocked = intval($accessUrlLocked);

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
}
