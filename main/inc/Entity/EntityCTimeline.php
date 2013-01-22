<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCTimeline
 *
 * @Table(name="c_timeline")
 * @Entity
 */
class EntityCTimeline
{

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var string
     *
     * @Column(name="headline", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $headline;

    /**
     * @var string
     *
     * @Column(name="type", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;

    /**
     * @var string
     *
     * @Column(name="start_date", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $startDate;

    /**
     * @var string
     *
     * @Column(name="end_date", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $endDate;

    /**
     * @var string
     *
     * @Column(name="text", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $text;

    /**
     * @var string
     *
     * @Column(name="media", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $media;

    /**
     * @var string
     *
     * @Column(name="media_credit", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $mediaCredit;

    /**
     * @var string
     *
     * @Column(name="media_caption", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $mediaCaption;

    /**
     * @var string
     *
     * @Column(name="title_slide", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $titleSlide;

    /**
     * @var integer
     *
     * @Column(name="parent_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $parentId;

    /**
     * @var integer
     *
     * @Column(name="status", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;


    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCTimeline
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
     * Set cId
     *
     * @param integer $cId
     * @return EntityCTimeline
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
     * Set headline
     *
     * @param string $headline
     * @return EntityCTimeline
     */
    public function setHeadline($headline)
    {
        $this->headline = $headline;

        return $this;
    }

    /**
     * Get headline
     *
     * @return string
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return EntityCTimeline
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
     * Set startDate
     *
     * @param string $startDate
     * @return EntityCTimeline
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return string
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param string $endDate
     * @return EntityCTimeline
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return string
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set text
     *
     * @param string $text
     * @return EntityCTimeline
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set media
     *
     * @param string $media
     * @return EntityCTimeline
     */
    public function setMedia($media)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * Get media
     *
     * @return string
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Set mediaCredit
     *
     * @param string $mediaCredit
     * @return EntityCTimeline
     */
    public function setMediaCredit($mediaCredit)
    {
        $this->mediaCredit = $mediaCredit;

        return $this;
    }

    /**
     * Get mediaCredit
     *
     * @return string
     */
    public function getMediaCredit()
    {
        return $this->mediaCredit;
    }

    /**
     * Set mediaCaption
     *
     * @param string $mediaCaption
     * @return EntityCTimeline
     */
    public function setMediaCaption($mediaCaption)
    {
        $this->mediaCaption = $mediaCaption;

        return $this;
    }

    /**
     * Get mediaCaption
     *
     * @return string
     */
    public function getMediaCaption()
    {
        return $this->mediaCaption;
    }

    /**
     * Set titleSlide
     *
     * @param string $titleSlide
     * @return EntityCTimeline
     */
    public function setTitleSlide($titleSlide)
    {
        $this->titleSlide = $titleSlide;

        return $this;
    }

    /**
     * Get titleSlide
     *
     * @return string
     */
    public function getTitleSlide()
    {
        return $this->titleSlide;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     * @return EntityCTimeline
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return EntityCTimeline
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }
}
