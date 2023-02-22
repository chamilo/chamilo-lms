<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Table(
 *     name="track_course_ranking",
 *     indexes={
 *     @ORM\Index(name="idx_tcc_cid", columns={"c_id"}),
 *     @ORM\Index(name="idx_tcc_sid", columns={"session_id"}),
 *     @ORM\Index(name="idx_tcc_urlid", columns={"url_id"}),
 *     @ORM\Index(name="idx_tcc_creation_date", columns={"creation_date"})
 * })
 * @ORM\Entity
 */

#[\ApiPlatform\Core\Annotation\ApiResource(
    attributes: [
        'security' => "is_granted('ROLE_USER')",
    ],
    denormalizationContext: [
        'groups' => ['trackCourseRanking:write'],
    ],
    normalizationContext: [
        'groups' => ['trackCourseRanking:read'],
    ],
)]
class TrackCourseRanking
{
    /**
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="trackCourseRanking")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    #[Groups(['course:read','trackCourseRanking:read'])]
    protected Course $course;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    #[Groups(['trackCourseRanking:read'])]
    protected int $sessionId;

    /**
     * @ORM\Column(name="url_id", type="integer", nullable=false)
     */
    #[Groups(['trackCourseRanking:read'])]
    protected int $urlId;

    /**
     * @ORM\Column(name="accesses", type="integer", nullable=false)
     */
    #[Groups(['course:read','trackCourseRanking:read'])]
    protected int $accesses;

    /**
     * @ORM\Column(name="total_score", type="integer", nullable=false)
     */
    #[Groups(['course:read','trackCourseRanking:read'])]
    protected int $totalScore;

    /**
     * @ORM\Column(name="users", type="integer", nullable=false)
     */
    #[Groups(['course:read'])]
    protected int $users;

    /**
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    protected DateTime $creationDate;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    #[Groups(['course:read','trackCourseRanking:read'])]
    protected ?int $id = null;

    public function __construct()
    {
        $this->id = 0;
        $this->sessionId = 0;
        $this->urlId = 0;
        $this->accesses = 0;
        $this->totalScore = 0;
        $this->users = 0;
        $this->creationDate = new DateTime();
    }

    /**
     * Set cId.
     *
     * @return TrackCourseRanking
     */
    public function setCId(int $cId)
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
     * Set sessionId.
     *
     * @return TrackCourseRanking
     */
    public function setSessionId(int $sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set urlId.
     *
     * @return TrackCourseRanking
     */
    public function setUrlId(int $urlId)
    {
        $this->urlId = $urlId;

        return $this;
    }

    /**
     * Get urlId.
     *
     * @return int
     */
    public function getUrlId()
    {
        return $this->urlId;
    }

    /**
     * Set accesses.
     *
     * @return TrackCourseRanking
     */
    public function setAccesses(int $accesses)
    {
        $this->accesses = $accesses;

        return $this;
    }

    /**
     * Get accesses.
     *
     * @return int
     */
    public function getAccesses()
    {
        return $this->accesses;
    }

    /**
     * Set totalScore.
     *
     * @return TrackCourseRanking
     */
    public function setTotalScore(int $totalScore)
    {
        $this->totalScore = $totalScore;

        return $this;
    }

    /**
     * Get totalScore.
     *
     * @return int
     */
    public function getTotalScore()
    {
        return $this->totalScore;
    }

    /**
     * Set users.
     *
     * @return TrackCourseRanking
     */
    public function setUsers(int $users)
    {
        $this->users = $users;

        return $this;
    }

    /**
     * Get users.
     *
     * @return int
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set creationDate.
     *
     * @return TrackCourseRanking
     */
    public function setCreationDate(DateTime $creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
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
