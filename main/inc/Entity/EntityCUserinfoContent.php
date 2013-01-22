<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCUserinfoContent
 *
 * @Table(name="c_userinfo_content")
 * @Entity
 */
class EntityCUserinfoContent
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="definition_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $definitionId;

    /**
     * @var string
     *
     * @Column(name="editor_ip", type="string", length=39, precision=0, scale=0, nullable=true, unique=false)
     */
    private $editorIp;

    /**
     * @var \DateTime
     *
     * @Column(name="edition_time", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $editionTime;

    /**
     * @var string
     *
     * @Column(name="content", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $content;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCUserinfoContent
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer 
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCUserinfoContent
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set userId
     *
     * @param integer $userId
     * @return EntityCUserinfoContent
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set definitionId
     *
     * @param integer $definitionId
     * @return EntityCUserinfoContent
     */
    public function setDefinitionId($definitionId)
    {
        $this->definitionId = $definitionId;

        return $this;
    }

    /**
     * Get definitionId
     *
     * @return integer 
     */
    public function getDefinitionId()
    {
        return $this->definitionId;
    }

    /**
     * Set editorIp
     *
     * @param string $editorIp
     * @return EntityCUserinfoContent
     */
    public function setEditorIp($editorIp)
    {
        $this->editorIp = $editorIp;

        return $this;
    }

    /**
     * Get editorIp
     *
     * @return string 
     */
    public function getEditorIp()
    {
        return $this->editorIp;
    }

    /**
     * Set editionTime
     *
     * @param \DateTime $editionTime
     * @return EntityCUserinfoContent
     */
    public function setEditionTime($editionTime)
    {
        $this->editionTime = $editionTime;

        return $this;
    }

    /**
     * Get editionTime
     *
     * @return \DateTime 
     */
    public function getEditionTime()
    {
        return $this->editionTime;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return EntityCUserinfoContent
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }
}
