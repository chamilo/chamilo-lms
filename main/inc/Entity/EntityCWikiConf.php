<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCWikiConf
 *
 * @Table(name="c_wiki_conf")
 * @Entity
 */
class EntityCWikiConf
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
     * @Column(name="page_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $pageId;

    /**
     * @var string
     *
     * @Column(name="task", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $task;

    /**
     * @var string
     *
     * @Column(name="feedback1", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $feedback1;

    /**
     * @var string
     *
     * @Column(name="feedback2", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $feedback2;

    /**
     * @var string
     *
     * @Column(name="feedback3", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $feedback3;

    /**
     * @var string
     *
     * @Column(name="fprogress1", type="string", length=3, precision=0, scale=0, nullable=false, unique=false)
     */
    private $fprogress1;

    /**
     * @var string
     *
     * @Column(name="fprogress2", type="string", length=3, precision=0, scale=0, nullable=false, unique=false)
     */
    private $fprogress2;

    /**
     * @var string
     *
     * @Column(name="fprogress3", type="string", length=3, precision=0, scale=0, nullable=false, unique=false)
     */
    private $fprogress3;

    /**
     * @var integer
     *
     * @Column(name="max_size", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $maxSize;

    /**
     * @var integer
     *
     * @Column(name="max_text", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $maxText;

    /**
     * @var integer
     *
     * @Column(name="max_version", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $maxVersion;

    /**
     * @var \DateTime
     *
     * @Column(name="startdate_assig", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $startdateAssig;

    /**
     * @var \DateTime
     *
     * @Column(name="enddate_assig", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $enddateAssig;

    /**
     * @var integer
     *
     * @Column(name="delayedsubmit", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $delayedsubmit;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCWikiConf
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
     * Set pageId
     *
     * @param integer $pageId
     * @return EntityCWikiConf
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;

        return $this;
    }

    /**
     * Get pageId
     *
     * @return integer 
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * Set task
     *
     * @param string $task
     * @return EntityCWikiConf
     */
    public function setTask($task)
    {
        $this->task = $task;

        return $this;
    }

    /**
     * Get task
     *
     * @return string 
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Set feedback1
     *
     * @param string $feedback1
     * @return EntityCWikiConf
     */
    public function setFeedback1($feedback1)
    {
        $this->feedback1 = $feedback1;

        return $this;
    }

    /**
     * Get feedback1
     *
     * @return string 
     */
    public function getFeedback1()
    {
        return $this->feedback1;
    }

    /**
     * Set feedback2
     *
     * @param string $feedback2
     * @return EntityCWikiConf
     */
    public function setFeedback2($feedback2)
    {
        $this->feedback2 = $feedback2;

        return $this;
    }

    /**
     * Get feedback2
     *
     * @return string 
     */
    public function getFeedback2()
    {
        return $this->feedback2;
    }

    /**
     * Set feedback3
     *
     * @param string $feedback3
     * @return EntityCWikiConf
     */
    public function setFeedback3($feedback3)
    {
        $this->feedback3 = $feedback3;

        return $this;
    }

    /**
     * Get feedback3
     *
     * @return string 
     */
    public function getFeedback3()
    {
        return $this->feedback3;
    }

    /**
     * Set fprogress1
     *
     * @param string $fprogress1
     * @return EntityCWikiConf
     */
    public function setFprogress1($fprogress1)
    {
        $this->fprogress1 = $fprogress1;

        return $this;
    }

    /**
     * Get fprogress1
     *
     * @return string 
     */
    public function getFprogress1()
    {
        return $this->fprogress1;
    }

    /**
     * Set fprogress2
     *
     * @param string $fprogress2
     * @return EntityCWikiConf
     */
    public function setFprogress2($fprogress2)
    {
        $this->fprogress2 = $fprogress2;

        return $this;
    }

    /**
     * Get fprogress2
     *
     * @return string 
     */
    public function getFprogress2()
    {
        return $this->fprogress2;
    }

    /**
     * Set fprogress3
     *
     * @param string $fprogress3
     * @return EntityCWikiConf
     */
    public function setFprogress3($fprogress3)
    {
        $this->fprogress3 = $fprogress3;

        return $this;
    }

    /**
     * Get fprogress3
     *
     * @return string 
     */
    public function getFprogress3()
    {
        return $this->fprogress3;
    }

    /**
     * Set maxSize
     *
     * @param integer $maxSize
     * @return EntityCWikiConf
     */
    public function setMaxSize($maxSize)
    {
        $this->maxSize = $maxSize;

        return $this;
    }

    /**
     * Get maxSize
     *
     * @return integer 
     */
    public function getMaxSize()
    {
        return $this->maxSize;
    }

    /**
     * Set maxText
     *
     * @param integer $maxText
     * @return EntityCWikiConf
     */
    public function setMaxText($maxText)
    {
        $this->maxText = $maxText;

        return $this;
    }

    /**
     * Get maxText
     *
     * @return integer 
     */
    public function getMaxText()
    {
        return $this->maxText;
    }

    /**
     * Set maxVersion
     *
     * @param integer $maxVersion
     * @return EntityCWikiConf
     */
    public function setMaxVersion($maxVersion)
    {
        $this->maxVersion = $maxVersion;

        return $this;
    }

    /**
     * Get maxVersion
     *
     * @return integer 
     */
    public function getMaxVersion()
    {
        return $this->maxVersion;
    }

    /**
     * Set startdateAssig
     *
     * @param \DateTime $startdateAssig
     * @return EntityCWikiConf
     */
    public function setStartdateAssig($startdateAssig)
    {
        $this->startdateAssig = $startdateAssig;

        return $this;
    }

    /**
     * Get startdateAssig
     *
     * @return \DateTime 
     */
    public function getStartdateAssig()
    {
        return $this->startdateAssig;
    }

    /**
     * Set enddateAssig
     *
     * @param \DateTime $enddateAssig
     * @return EntityCWikiConf
     */
    public function setEnddateAssig($enddateAssig)
    {
        $this->enddateAssig = $enddateAssig;

        return $this;
    }

    /**
     * Get enddateAssig
     *
     * @return \DateTime 
     */
    public function getEnddateAssig()
    {
        return $this->enddateAssig;
    }

    /**
     * Set delayedsubmit
     *
     * @param integer $delayedsubmit
     * @return EntityCWikiConf
     */
    public function setDelayedsubmit($delayedsubmit)
    {
        $this->delayedsubmit = $delayedsubmit;

        return $this;
    }

    /**
     * Get delayedsubmit
     *
     * @return integer 
     */
    public function getDelayedsubmit()
    {
        return $this->delayedsubmit;
    }
}
