<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCWikiDiscuss
 *
 * @Table(name="c_wiki_discuss")
 * @Entity
 */
class EntityCWikiDiscuss
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
     * @Column(name="publication_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $publicationId;

    /**
     * @var integer
     *
     * @Column(name="userc_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $usercId;

    /**
     * @var string
     *
     * @Column(name="comment", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $comment;

    /**
     * @var string
     *
     * @Column(name="p_score", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $pScore;

    /**
     * @var \DateTime
     *
     * @Column(name="dtime", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $dtime;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCWikiDiscuss
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
     * @return EntityCWikiDiscuss
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
     * Set publicationId
     *
     * @param integer $publicationId
     * @return EntityCWikiDiscuss
     */
    public function setPublicationId($publicationId)
    {
        $this->publicationId = $publicationId;

        return $this;
    }

    /**
     * Get publicationId
     *
     * @return integer 
     */
    public function getPublicationId()
    {
        return $this->publicationId;
    }

    /**
     * Set usercId
     *
     * @param integer $usercId
     * @return EntityCWikiDiscuss
     */
    public function setUsercId($usercId)
    {
        $this->usercId = $usercId;

        return $this;
    }

    /**
     * Get usercId
     *
     * @return integer 
     */
    public function getUsercId()
    {
        return $this->usercId;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return EntityCWikiDiscuss
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
     * Set pScore
     *
     * @param string $pScore
     * @return EntityCWikiDiscuss
     */
    public function setPScore($pScore)
    {
        $this->pScore = $pScore;

        return $this;
    }

    /**
     * Get pScore
     *
     * @return string 
     */
    public function getPScore()
    {
        return $this->pScore;
    }

    /**
     * Set dtime
     *
     * @param \DateTime $dtime
     * @return EntityCWikiDiscuss
     */
    public function setDtime($dtime)
    {
        $this->dtime = $dtime;

        return $this;
    }

    /**
     * Get dtime
     *
     * @return \DateTime 
     */
    public function getDtime()
    {
        return $this->dtime;
    }
}
