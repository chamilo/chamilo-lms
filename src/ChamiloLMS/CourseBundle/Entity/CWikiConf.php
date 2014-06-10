<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CWikiConf
 *
 * @ORM\Table(name="c_wiki_conf", indexes={@ORM\Index(name="page_id", columns={"page_id"})})
 * @ORM\Entity
 */
class CWikiConf
{
    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="page_id", type="integer", nullable=false)
     */
    private $pageId;

    /**
     * @var string
     *
     * @ORM\Column(name="task", type="text", nullable=false)
     */
    private $task;

    /**
     * @var string
     *
     * @ORM\Column(name="feedback1", type="text", nullable=false)
     */
    private $feedback1;

    /**
     * @var string
     *
     * @ORM\Column(name="feedback2", type="text", nullable=false)
     */
    private $feedback2;

    /**
     * @var string
     *
     * @ORM\Column(name="feedback3", type="text", nullable=false)
     */
    private $feedback3;

    /**
     * @var string
     *
     * @ORM\Column(name="fprogress1", type="string", length=3, nullable=false)
     */
    private $fprogress1;

    /**
     * @var string
     *
     * @ORM\Column(name="fprogress2", type="string", length=3, nullable=false)
     */
    private $fprogress2;

    /**
     * @var string
     *
     * @ORM\Column(name="fprogress3", type="string", length=3, nullable=false)
     */
    private $fprogress3;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_size", type="integer", nullable=true)
     */
    private $maxSize;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_text", type="integer", nullable=true)
     */
    private $maxText;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_version", type="integer", nullable=true)
     */
    private $maxVersion;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startdate_assig", type="datetime", nullable=false)
     */
    private $startdateAssig;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="enddate_assig", type="datetime", nullable=false)
     */
    private $enddateAssig;

    /**
     * @var integer
     *
     * @ORM\Column(name="delayedsubmit", type="integer", nullable=false)
     */
    private $delayedsubmit;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
