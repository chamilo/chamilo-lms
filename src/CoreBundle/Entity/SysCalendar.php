<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * SysCalendar.
 *
 * @ORM\Table(name="sys_calendar")
 * @ORM\Entity
 */
class SysCalendar
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected ?string $content;

    /**
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     */
    protected ?DateTime $startDate;

    /**
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    protected ?DateTime $endDate;

    /**
     * @ORM\Column(name="access_url_id", type="integer", nullable=false)
     */
    protected int $accessUrlId;

    /**
     * @ORM\Column(name="all_day", type="integer", nullable=false)
     */
    protected int $allDay;

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return SysCalendar
     */
    public function setTitle($title): self
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
     * Set content.
     *
     * @param string $content
     *
     * @return SysCalendar
     */
    public function setContent($content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set startDate.
     *
     * @param DateTime $startDate
     *
     * @return SysCalendar
     */
    public function setStartDate($startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate.
     *
     * @param DateTime $endDate
     *
     * @return SysCalendar
     */
    public function setEndDate($endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate.
     *
     * @return DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set accessUrlId.
     *
     * @param int $accessUrlId
     *
     * @return SysCalendar
     */
    public function setAccessUrlId($accessUrlId): self
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    /**
     * Get accessUrlId.
     *
     * @return int
     */
    public function getAccessUrlId()
    {
        return $this->accessUrlId;
    }

    /**
     * Set allDay.
     *
     * @param int $allDay
     *
     * @return SysCalendar
     */
    public function setAllDay($allDay): self
    {
        $this->allDay = $allDay;

        return $this;
    }

    /**
     * Get allDay.
     *
     * @return int
     */
    public function getAllDay()
    {
        return $this->allDay;
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
