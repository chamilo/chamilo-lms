<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackELinks
 *
 * @Table(name="track_e_links")
 * @Entity
 */
class EntityTrackELinks
{
    /**
     * @var integer
     *
     * @Column(name="links_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $linksId;

    /**
     * @var integer
     *
     * @Column(name="links_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $linksUserId;

    /**
     * @var \DateTime
     *
     * @Column(name="links_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $linksDate;

    /**
     * @var string
     *
     * @Column(name="links_cours_id", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $linksCoursId;

    /**
     * @var integer
     *
     * @Column(name="links_link_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $linksLinkId;

    /**
     * @var integer
     *
     * @Column(name="links_session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $linksSessionId;


    /**
     * Get linksId
     *
     * @return integer 
     */
    public function getLinksId()
    {
        return $this->linksId;
    }

    /**
     * Set linksUserId
     *
     * @param integer $linksUserId
     * @return EntityTrackELinks
     */
    public function setLinksUserId($linksUserId)
    {
        $this->linksUserId = $linksUserId;

        return $this;
    }

    /**
     * Get linksUserId
     *
     * @return integer 
     */
    public function getLinksUserId()
    {
        return $this->linksUserId;
    }

    /**
     * Set linksDate
     *
     * @param \DateTime $linksDate
     * @return EntityTrackELinks
     */
    public function setLinksDate($linksDate)
    {
        $this->linksDate = $linksDate;

        return $this;
    }

    /**
     * Get linksDate
     *
     * @return \DateTime 
     */
    public function getLinksDate()
    {
        return $this->linksDate;
    }

    /**
     * Set linksCoursId
     *
     * @param string $linksCoursId
     * @return EntityTrackELinks
     */
    public function setLinksCoursId($linksCoursId)
    {
        $this->linksCoursId = $linksCoursId;

        return $this;
    }

    /**
     * Get linksCoursId
     *
     * @return string 
     */
    public function getLinksCoursId()
    {
        return $this->linksCoursId;
    }

    /**
     * Set linksLinkId
     *
     * @param integer $linksLinkId
     * @return EntityTrackELinks
     */
    public function setLinksLinkId($linksLinkId)
    {
        $this->linksLinkId = $linksLinkId;

        return $this;
    }

    /**
     * Get linksLinkId
     *
     * @return integer 
     */
    public function getLinksLinkId()
    {
        return $this->linksLinkId;
    }

    /**
     * Set linksSessionId
     *
     * @param integer $linksSessionId
     * @return EntityTrackELinks
     */
    public function setLinksSessionId($linksSessionId)
    {
        $this->linksSessionId = $linksSessionId;

        return $this;
    }

    /**
     * Get linksSessionId
     *
     * @return integer 
     */
    public function getLinksSessionId()
    {
        return $this->linksSessionId;
    }
}
