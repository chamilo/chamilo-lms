<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CWikiConf.
 *
 * @ORM\Table(
 *  name="c_wiki_conf",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="page_id", columns={"page_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CWikiConf
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="page_id", type="integer")
     */
    protected $pageId;

    /**
     * @var string
     *
     * @ORM\Column(name="task", type="text", nullable=false)
     */
    protected $task;

    /**
     * @var string
     *
     * @ORM\Column(name="feedback1", type="text", nullable=false)
     */
    protected $feedback1;

    /**
     * @var string
     *
     * @ORM\Column(name="feedback2", type="text", nullable=false)
     */
    protected $feedback2;

    /**
     * @var string
     *
     * @ORM\Column(name="feedback3", type="text", nullable=false)
     */
    protected $feedback3;

    /**
     * @var string
     *
     * @ORM\Column(name="fprogress1", type="string", length=3, nullable=false)
     */
    protected $fprogress1;

    /**
     * @var string
     *
     * @ORM\Column(name="fprogress2", type="string", length=3, nullable=false)
     */
    protected $fprogress2;

    /**
     * @var string
     *
     * @ORM\Column(name="fprogress3", type="string", length=3, nullable=false)
     */
    protected $fprogress3;

    /**
     * @var int
     *
     * @ORM\Column(name="max_size", type="integer", nullable=true)
     */
    protected $maxSize;

    /**
     * @var int
     *
     * @ORM\Column(name="max_text", type="integer", nullable=true)
     */
    protected $maxText;

    /**
     * @var int
     *
     * @ORM\Column(name="max_version", type="integer", nullable=true)
     */
    protected $maxVersion;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startdate_assig", type="datetime", nullable=true)
     */
    protected $startdateAssig;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="enddate_assig", type="datetime", nullable=true)
     */
    protected $enddateAssig;

    /**
     * @var int
     *
     * @ORM\Column(name="delayedsubmit", type="integer", nullable=false)
     */
    protected $delayedsubmit;

    /**
     * Set task.
     *
     * @param string $task
     *
     * @return CWikiConf
     */
    public function setTask($task)
    {
        $this->task = $task;

        return $this;
    }

    /**
     * Get task.
     *
     * @return string
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Set feedback1.
     *
     * @param string $feedback1
     *
     * @return CWikiConf
     */
    public function setFeedback1($feedback1)
    {
        $this->feedback1 = $feedback1;

        return $this;
    }

    /**
     * Get feedback1.
     *
     * @return string
     */
    public function getFeedback1()
    {
        return $this->feedback1;
    }

    /**
     * Set feedback2.
     *
     * @param string $feedback2
     *
     * @return CWikiConf
     */
    public function setFeedback2($feedback2)
    {
        $this->feedback2 = $feedback2;

        return $this;
    }

    /**
     * Get feedback2.
     *
     * @return string
     */
    public function getFeedback2()
    {
        return $this->feedback2;
    }

    /**
     * Set feedback3.
     *
     * @param string $feedback3
     *
     * @return CWikiConf
     */
    public function setFeedback3($feedback3)
    {
        $this->feedback3 = $feedback3;

        return $this;
    }

    /**
     * Get feedback3.
     *
     * @return string
     */
    public function getFeedback3()
    {
        return $this->feedback3;
    }

    /**
     * Set fprogress1.
     *
     * @param string $fprogress1
     *
     * @return CWikiConf
     */
    public function setFprogress1($fprogress1)
    {
        $this->fprogress1 = $fprogress1;

        return $this;
    }

    /**
     * Get fprogress1.
     *
     * @return string
     */
    public function getFprogress1()
    {
        return $this->fprogress1;
    }

    /**
     * Set fprogress2.
     *
     * @param string $fprogress2
     *
     * @return CWikiConf
     */
    public function setFprogress2($fprogress2)
    {
        $this->fprogress2 = $fprogress2;

        return $this;
    }

    /**
     * Get fprogress2.
     *
     * @return string
     */
    public function getFprogress2()
    {
        return $this->fprogress2;
    }

    /**
     * Set fprogress3.
     *
     * @param string $fprogress3
     *
     * @return CWikiConf
     */
    public function setFprogress3($fprogress3)
    {
        $this->fprogress3 = $fprogress3;

        return $this;
    }

    /**
     * Get fprogress3.
     *
     * @return string
     */
    public function getFprogress3()
    {
        return $this->fprogress3;
    }

    /**
     * Set maxSize.
     *
     * @param int $maxSize
     *
     * @return CWikiConf
     */
    public function setMaxSize($maxSize)
    {
        $this->maxSize = $maxSize;

        return $this;
    }

    /**
     * Get maxSize.
     *
     * @return int
     */
    public function getMaxSize()
    {
        return $this->maxSize;
    }

    /**
     * Set maxText.
     *
     * @param int $maxText
     *
     * @return CWikiConf
     */
    public function setMaxText($maxText)
    {
        $this->maxText = $maxText;

        return $this;
    }

    /**
     * Get maxText.
     *
     * @return int
     */
    public function getMaxText()
    {
        return $this->maxText;
    }

    /**
     * Set maxVersion.
     *
     * @param int $maxVersion
     *
     * @return CWikiConf
     */
    public function setMaxVersion($maxVersion)
    {
        $this->maxVersion = $maxVersion;

        return $this;
    }

    /**
     * Get maxVersion.
     *
     * @return int
     */
    public function getMaxVersion()
    {
        return $this->maxVersion;
    }

    /**
     * Set startdateAssig.
     *
     * @param \DateTime $startdateAssig
     *
     * @return CWikiConf
     */
    public function setStartdateAssig($startdateAssig)
    {
        $this->startdateAssig = $startdateAssig;

        return $this;
    }

    /**
     * Get startdateAssig.
     *
     * @return \DateTime
     */
    public function getStartdateAssig()
    {
        return $this->startdateAssig;
    }

    /**
     * Set enddateAssig.
     *
     * @param \DateTime $enddateAssig
     *
     * @return CWikiConf
     */
    public function setEnddateAssig($enddateAssig)
    {
        $this->enddateAssig = $enddateAssig;

        return $this;
    }

    /**
     * Get enddateAssig.
     *
     * @return \DateTime
     */
    public function getEnddateAssig()
    {
        return $this->enddateAssig;
    }

    /**
     * Set delayedsubmit.
     *
     * @param int $delayedsubmit
     *
     * @return CWikiConf
     */
    public function setDelayedsubmit($delayedsubmit)
    {
        $this->delayedsubmit = $delayedsubmit;

        return $this;
    }

    /**
     * Get delayedsubmit.
     *
     * @return int
     */
    public function getDelayedsubmit()
    {
        return $this->delayedsubmit;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CWikiConf
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set pageId.
     *
     * @param int $pageId
     *
     * @return CWikiConf
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;

        return $this;
    }

    /**
     * Get pageId.
     *
     * @return int
     */
    public function getPageId()
    {
        return $this->pageId;
    }
}
