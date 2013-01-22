<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySettingsCurrent
 *
 * @Table(name="settings_current")
 * @Entity
 */
class EntitySettingsCurrent
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="variable", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $variable;

    /**
     * @var string
     *
     * @Column(name="subkey", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $subkey;

    /**
     * @var string
     *
     * @Column(name="type", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $type;

    /**
     * @var string
     *
     * @Column(name="category", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $category;

    /**
     * @var string
     *
     * @Column(name="selected_value", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $selectedValue;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="comment", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $comment;

    /**
     * @var string
     *
     * @Column(name="scope", type="string", length=50, precision=0, scale=0, nullable=true, unique=false)
     */
    private $scope;

    /**
     * @var string
     *
     * @Column(name="subkeytext", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $subkeytext;

    /**
     * @var integer
     *
     * @Column(name="access_url", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessUrl;

    /**
     * @var integer
     *
     * @Column(name="access_url_changeable", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessUrlChangeable;

    /**
     * @var integer
     *
     * @Column(name="access_url_locked", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessUrlLocked;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set variable
     *
     * @param string $variable
     * @return EntitySettingsCurrent
     */
    public function setVariable($variable)
    {
        $this->variable = $variable;

        return $this;
    }

    /**
     * Get variable
     *
     * @return string 
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Set subkey
     *
     * @param string $subkey
     * @return EntitySettingsCurrent
     */
    public function setSubkey($subkey)
    {
        $this->subkey = $subkey;

        return $this;
    }

    /**
     * Get subkey
     *
     * @return string 
     */
    public function getSubkey()
    {
        return $this->subkey;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return EntitySettingsCurrent
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set category
     *
     * @param string $category
     * @return EntitySettingsCurrent
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string 
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set selectedValue
     *
     * @param string $selectedValue
     * @return EntitySettingsCurrent
     */
    public function setSelectedValue($selectedValue)
    {
        $this->selectedValue = $selectedValue;

        return $this;
    }

    /**
     * Get selectedValue
     *
     * @return string 
     */
    public function getSelectedValue()
    {
        return $this->selectedValue;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return EntitySettingsCurrent
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return EntitySettingsCurrent
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set scope
     *
     * @param string $scope
     * @return EntitySettingsCurrent
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get scope
     *
     * @return string 
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set subkeytext
     *
     * @param string $subkeytext
     * @return EntitySettingsCurrent
     */
    public function setSubkeytext($subkeytext)
    {
        $this->subkeytext = $subkeytext;

        return $this;
    }

    /**
     * Get subkeytext
     *
     * @return string 
     */
    public function getSubkeytext()
    {
        return $this->subkeytext;
    }

    /**
     * Set accessUrl
     *
     * @param integer $accessUrl
     * @return EntitySettingsCurrent
     */
    public function setAccessUrl($accessUrl)
    {
        $this->accessUrl = $accessUrl;

        return $this;
    }

    /**
     * Get accessUrl
     *
     * @return integer 
     */
    public function getAccessUrl()
    {
        return $this->accessUrl;
    }

    /**
     * Set accessUrlChangeable
     *
     * @param integer $accessUrlChangeable
     * @return EntitySettingsCurrent
     */
    public function setAccessUrlChangeable($accessUrlChangeable)
    {
        $this->accessUrlChangeable = $accessUrlChangeable;

        return $this;
    }

    /**
     * Get accessUrlChangeable
     *
     * @return integer 
     */
    public function getAccessUrlChangeable()
    {
        return $this->accessUrlChangeable;
    }

    /**
     * Set accessUrlLocked
     *
     * @param integer $accessUrlLocked
     * @return EntitySettingsCurrent
     */
    public function setAccessUrlLocked($accessUrlLocked)
    {
        $this->accessUrlLocked = $accessUrlLocked;

        return $this;
    }

    /**
     * Get accessUrlLocked
     *
     * @return integer 
     */
    public function getAccessUrlLocked()
    {
        return $this->accessUrlLocked;
    }
}
