<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEOpen.
 *
 * @ORM\Table(name="track_e_open")
 * @ORM\Entity
 */
class TrackEOpen
{
    /**
     * @var string
     *
     * @ORM\Column(name="open_remote_host", type="text", nullable=false)
     */
    protected $openRemoteHost;

    /**
     * @var string
     *
     * @ORM\Column(name="open_agent", type="text", nullable=false)
     */
    protected $openAgent;

    /**
     * @var string
     *
     * @ORM\Column(name="open_referer", type="text", nullable=false)
     */
    protected $openReferer;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="open_date", type="datetime", nullable=false)
     */
    protected $openDate;

    /**
     * @var int
     *
     * @ORM\Column(name="open_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $openId;

    /**
     * Set openRemoteHost.
     *
     * @param string $openRemoteHost
     *
     * @return TrackEOpen
     */
    public function setOpenRemoteHost($openRemoteHost)
    {
        $this->openRemoteHost = $openRemoteHost;

        return $this;
    }

    /**
     * Get openRemoteHost.
     *
     * @return string
     */
    public function getOpenRemoteHost()
    {
        return $this->openRemoteHost;
    }

    /**
     * Set openAgent.
     *
     * @param string $openAgent
     *
     * @return TrackEOpen
     */
    public function setOpenAgent($openAgent)
    {
        $this->openAgent = $openAgent;

        return $this;
    }

    /**
     * Get openAgent.
     *
     * @return string
     */
    public function getOpenAgent()
    {
        return $this->openAgent;
    }

    /**
     * Set openReferer.
     *
     * @param string $openReferer
     *
     * @return TrackEOpen
     */
    public function setOpenReferer($openReferer)
    {
        $this->openReferer = $openReferer;

        return $this;
    }

    /**
     * Get openReferer.
     *
     * @return string
     */
    public function getOpenReferer()
    {
        return $this->openReferer;
    }

    /**
     * Set openDate.
     *
     * @param \DateTime $openDate
     *
     * @return TrackEOpen
     */
    public function setOpenDate($openDate)
    {
        $this->openDate = $openDate;

        return $this;
    }

    /**
     * Get openDate.
     *
     * @return \DateTime
     */
    public function getOpenDate()
    {
        return $this->openDate;
    }

    /**
     * Get openId.
     *
     * @return int
     */
    public function getOpenId()
    {
        return $this->openId;
    }
}
