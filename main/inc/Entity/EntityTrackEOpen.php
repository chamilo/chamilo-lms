<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackEOpen
 *
 * @Table(name="track_e_open")
 * @Entity
 */
class EntityTrackEOpen
{
    /**
     * @var integer
     *
     * @Column(name="open_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $openId;

    /**
     * @var string
     *
     * @Column(name="open_remote_host", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $openRemoteHost;

    /**
     * @var string
     *
     * @Column(name="open_agent", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $openAgent;

    /**
     * @var string
     *
     * @Column(name="open_referer", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $openReferer;

    /**
     * @var \DateTime
     *
     * @Column(name="open_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $openDate;


    /**
     * Get openId
     *
     * @return integer 
     */
    public function getOpenId()
    {
        return $this->openId;
    }

    /**
     * Set openRemoteHost
     *
     * @param string $openRemoteHost
     * @return EntityTrackEOpen
     */
    public function setOpenRemoteHost($openRemoteHost)
    {
        $this->openRemoteHost = $openRemoteHost;

        return $this;
    }

    /**
     * Get openRemoteHost
     *
     * @return string 
     */
    public function getOpenRemoteHost()
    {
        return $this->openRemoteHost;
    }

    /**
     * Set openAgent
     *
     * @param string $openAgent
     * @return EntityTrackEOpen
     */
    public function setOpenAgent($openAgent)
    {
        $this->openAgent = $openAgent;

        return $this;
    }

    /**
     * Get openAgent
     *
     * @return string 
     */
    public function getOpenAgent()
    {
        return $this->openAgent;
    }

    /**
     * Set openReferer
     *
     * @param string $openReferer
     * @return EntityTrackEOpen
     */
    public function setOpenReferer($openReferer)
    {
        $this->openReferer = $openReferer;

        return $this;
    }

    /**
     * Get openReferer
     *
     * @return string 
     */
    public function getOpenReferer()
    {
        return $this->openReferer;
    }

    /**
     * Set openDate
     *
     * @param \DateTime $openDate
     * @return EntityTrackEOpen
     */
    public function setOpenDate($openDate)
    {
        $this->openDate = $openDate;

        return $this;
    }

    /**
     * Get openDate
     *
     * @return \DateTime 
     */
    public function getOpenDate()
    {
        return $this->openDate;
    }
}
