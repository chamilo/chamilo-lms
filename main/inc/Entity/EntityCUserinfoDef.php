<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCUserinfoDef
 *
 * @Table(name="c_userinfo_def")
 * @Entity
 */
class EntityCUserinfoDef
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
     * @var string
     *
     * @Column(name="title", type="string", length=80, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="comment", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $comment;

    /**
     * @var boolean
     *
     * @Column(name="line_count", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lineCount;

    /**
     * @var boolean
     *
     * @Column(name="rank", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $rank;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCUserinfoDef
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
     * @return EntityCUserinfoDef
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
     * Set title
     *
     * @param string $title
     * @return EntityCUserinfoDef
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
     * @return EntityCUserinfoDef
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
     * Set lineCount
     *
     * @param boolean $lineCount
     * @return EntityCUserinfoDef
     */
    public function setLineCount($lineCount)
    {
        $this->lineCount = $lineCount;

        return $this;
    }

    /**
     * Get lineCount
     *
     * @return boolean 
     */
    public function getLineCount()
    {
        return $this->lineCount;
    }

    /**
     * Set rank
     *
     * @param boolean $rank
     * @return EntityCUserinfoDef
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Get rank
     *
     * @return boolean 
     */
    public function getRank()
    {
        return $this->rank;
    }
}
