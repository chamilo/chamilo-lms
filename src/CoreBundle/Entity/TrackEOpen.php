<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
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
     * @ORM\Column(name="open_remote_host", type="text", nullable=false)
     */
    protected string $openRemoteHost;

    /**
     * @ORM\Column(name="open_agent", type="text", nullable=false)
     */
    protected string $openAgent;

    /**
     * @ORM\Column(name="open_referer", type="text", nullable=false)
     */
    protected string $openReferer;

    /**
     * @ORM\Column(name="open_date", type="datetime", nullable=false)
     */
    protected DateTime $openDate;

    /**
     * @ORM\Column(name="open_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $openId;

    /**
     * Set openRemoteHost.
     *
     * @return TrackEOpen
     */
    public function setOpenRemoteHost(string $openRemoteHost)
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
     * @return TrackEOpen
     */
    public function setOpenAgent(string $openAgent)
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
     * @return TrackEOpen
     */
    public function setOpenReferer(string $openReferer)
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
     * @return TrackEOpen
     */
    public function setOpenDate(DateTime $openDate)
    {
        $this->openDate = $openDate;

        return $this;
    }

    /**
     * Get openDate.
     *
     * @return DateTime
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
