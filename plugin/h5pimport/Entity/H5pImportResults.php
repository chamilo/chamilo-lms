<?php

namespace Chamilo\PluginBundle\Entity\H5pImport;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\UserBundle\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class H5pImportResults.
 *
 * @package Chamilo\PluginBundle\Entity\H5pImport
 *
 * @ORM\Entity()
 * @ORM\Table(name="plugin_h5p_import_results")
 */
class H5pImportResults
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private int $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="score", type="integer")
     */
    private int $score;

    /**
     * @var int
     *
     * @ORM\Column(name="max_score", type="integer")
     */
    private int $maxScore;

    /**
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private Course $course;

    /**
     * @var Session|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    private ?Session $session;

    /**
     * @var H5pImport
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\H5pImport\H5pImport")
     * @ORM\JoinColumn(name="plugin_h5p_import_id", referencedColumnName="iid", nullable=false, onDelete="CASCADE")
     */
    private H5pImport $h5pImport;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private User $user;

    /**
     * @var CLpItemView
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CLpItemView")
     * @ORM\JoinColumn(name="c_lp_item_view_id", referencedColumnName="iid", nullable=true, onDelete="CASCADE")
     */
    private CLpItemView $cLpItemView;

    /**
     * @var int
     *
     * @ORM\Column(name="start_time", type="integer", nullable=false)
     */
    protected int $startTime;

    /**
     * @var int
     *
     * @ORM\Column(name="total_time", type="integer", nullable=false)
     */
    protected int $totalTime;
    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private DateTime $createdAt;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="modified_at", type="datetime", nullable=false)
     */
    private DateTime $modifiedAt;

    /**
     * @return int
     */
    public function getIid(): int
    {
        return $this->iid;
    }

    /**
     * @param int $iid
     * @return H5pImportResults
     */
    public function setIid(int $iid): H5pImportResults
    {
        $this->iid = $iid;
        return $this;
    }

    /**
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * @param int $score
     * @return H5pImportResults
     */
    public function setScore(int $score): H5pImportResults
    {
        $this->score = $score;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxScore(): int
    {
        return $this->maxScore;
    }

    /**
     * @param int $maxScore
     * @return H5pImportResults
     */
    public function setMaxScore(int $maxScore): H5pImportResults
    {
        $this->maxScore = $maxScore;
        return $this;
    }

    /**
     * @return Course
     */
    public function getCourse(): Course
    {
        return $this->course;
    }

    /**
     * @param Course $course
     * @return H5pImportResults
     */
    public function setCourse(Course $course): H5pImportResults
    {
        $this->course = $course;
        return $this;
    }

    /**
     * @return Session|null
     */
    public function getSession(): ?Session
    {
        return $this->session;
    }

    /**
     * @param Session|null $session
     * @return H5pImportResults
     */
    public function setSession(?Session $session): H5pImportResults
    {
        $this->session = $session;
        return $this;
    }

    /**
     * @return H5pImport
     */
    public function getH5pImport(): H5pImport
    {
        return $this->h5pImport;
    }

    /**
     * @param H5pImport $h5pImport
     * @return H5pImportResults
     */
    public function setH5pImport(H5pImport $h5pImport): H5pImportResults
    {
        $this->h5pImport = $h5pImport;
        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return H5pImportResults
     */
    public function setUser(User $user): H5pImportResults
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return CLpItemView
     */
    public function getCLpItemView(): CLpItemView
    {
        return $this->cLpItemView;
    }

    /**
     * @param CLpItemView $cLpItemView
     * @return H5pImportResults
     */
    public function setCLpItemView(CLpItemView $cLpItemView): H5pImportResults
    {
        $this->cLpItemView = $cLpItemView;
        return $this;
    }

    /**
     * @return int
     */
    public function getStartTime(): int
    {
        return $this->startTime;
    }

    /**
     * @param int $startTime
     * @return H5pImportResults
     */
    public function setStartTime(int $startTime): H5pImportResults
    {
        $this->startTime = $startTime;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalTime(): int
    {
        return $this->totalTime;
    }

    /**
     * @param int $totalTime
     * @return H5pImportResults
     */
    public function setTotalTime(int $totalTime): H5pImportResults
    {
        $this->totalTime = $totalTime;
        return $this;
    }

    /**
     * @param DateTime $createdAt
     * @return H5pImportResults
     */
    public function setCreatedAt(DateTime $createdAt): H5pImportResults
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getModifiedAt(): DateTime
    {
        return $this->modifiedAt;
    }

    /**
     * @param DateTime $modifiedAt
     * @return H5pImportResults
     */
    public function setModifiedAt(DateTime $modifiedAt): H5pImportResults
    {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

}
