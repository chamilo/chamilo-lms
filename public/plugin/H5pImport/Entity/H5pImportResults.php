<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\H5pImport\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLpItemView;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[ORM\Table(name: 'plugin_h5p_import_results')]
class H5pImportResults
{
    #[ORM\Column(name: 'start_time', type: 'integer', nullable: false)]
    protected int $startTime;

    #[ORM\Column(name: 'total_time', type: 'integer', nullable: false)]
    protected int $totalTime;

    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $iid;

    #[ORM\Column(name: 'score', type: 'integer')]
    private int $score;

    #[ORM\Column(name: 'max_score', type: 'integer')]
    private int $maxScore;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Course $course;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id')]
    private ?Session $session;

    #[ORM\ManyToOne(targetEntity: H5pImport::class)]
    #[ORM\JoinColumn(name: 'plugin_h5p_import_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    private H5pImport $h5pImport;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: CLpItemView::class)]
    #[ORM\JoinColumn(name: 'c_lp_item_view_id', referencedColumnName: 'iid', nullable: true, onDelete: 'CASCADE')]
    private ?CLpItemView $cLpItemView;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    private DateTime $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'modified_at', type: 'datetime', nullable: false)]
    private DateTime $modifiedAt;

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
