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
 * @ORM\Entity()
 *
 * @ORM\Table(name="plugin_h5p_import_results")
 */
class H5pImportResults
{
    /**
     * @var int
     *
     * @ORM\Column(name="start_time", type="integer", nullable=false)
     */
    protected $startTime;

    /**
     * @var int
     *
     * @ORM\Column(name="total_time", type="integer", nullable=false)
     */
    protected $totalTime;

    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="score", type="integer")
     */
    private $score;

    /**
     * @var int
     *
     * @ORM\Column(name="max_score", type="integer")
     */
    private $maxScore;

    /**
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     *
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $course;

    /**
     * @var null|Session
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     *
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    private $session;

    /**
     * @var H5pImport
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\H5pImport\H5pImport")
     *
     * @ORM\JoinColumn(name="plugin_h5p_import_id", referencedColumnName="iid", nullable=false, onDelete="CASCADE")
     */
    private $h5pImport;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     *
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @var CLpItemView
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CLpItemView")
     *
     * @ORM\JoinColumn(name="c_lp_item_view_id", referencedColumnName="iid", nullable=true, onDelete="CASCADE")
     */
    private $cLpItemView;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=false)
     */
    private $modifiedAt;

    public function getIid(): int
    {
        return $this->iid;
    }

    public function setIid(int $iid): H5pImportResults
    {
        $this->iid = $iid;

        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): H5pImportResults
    {
        $this->score = $score;

        return $this;
    }

    public function getMaxScore(): int
    {
        return $this->maxScore;
    }

    public function setMaxScore(int $maxScore): H5pImportResults
    {
        $this->maxScore = $maxScore;

        return $this;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): H5pImportResults
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): H5pImportResults
    {
        $this->session = $session;

        return $this;
    }

    public function getH5pImport(): H5pImport
    {
        return $this->h5pImport;
    }

    public function setH5pImport(H5pImport $h5pImport): H5pImportResults
    {
        $this->h5pImport = $h5pImport;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): H5pImportResults
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getCLpItemView(): CLpItemView
    {
        return $this->cLpItemView;
    }

    public function setCLpItemView(CLpItemView $cLpItemView): H5pImportResults
    {
        $this->cLpItemView = $cLpItemView;

        return $this;
    }

    public function getStartTime(): int
    {
        return $this->startTime;
    }

    public function setStartTime(int $startTime): H5pImportResults
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getTotalTime(): int
    {
        return $this->totalTime;
    }

    public function setTotalTime(int $totalTime): H5pImportResults
    {
        $this->totalTime = $totalTime;

        return $this;
    }

    public function setCreatedAt(\DateTime $createdAt): H5pImportResults
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModifiedAt(): \DateTime
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(\DateTime $modifiedAt): H5pImportResults
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }
}
