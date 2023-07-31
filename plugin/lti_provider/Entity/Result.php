<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\LtiProvider;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Result.
 *
 * @ORM\Table(name="plugin_lti_provider_result")
 * @ORM\Entity()
 */
class Result
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="issuer", type="text")
     */
    protected $issuer;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="client_uid", type="string", nullable=false)
     */
    protected $clientUId;

    /**
     * @var string
     *
     * @ORM\Column(name="course_code", type="string", length=40, nullable=true)
     */
    protected $courseCode;

    /**
     * @var int
     *
     * @ORM\Column(name="tool_id", type="integer", nullable=false)
     */
    protected $toolId;

    /**
     * @var string
     *
     * @ORM\Column(name="tool_name", type="string")
     */
    protected $toolName;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", precision=6, scale=2, nullable=false)
     */
    protected $score;

    /**
     * @var int
     *
     * @ORM\Column(name="progress", type="integer", nullable=false)
     */
    protected $progress;

    /**
     * @var int
     *
     * @ORM\Column(name="duration", type="integer", nullable=false)
     */
    protected $duration;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    protected $startDate;

    /**
     * @var string
     *
     * @ORM\Column(name="user_ip", type="string")
     */
    protected $userIp;

    /**
     * @var string
     *
     * @ORM\Column(name="lti_launch_id", type="string")
     */
    protected $ltiLaunchId;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Result
    {
        $this->id = $id;

        return $this;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function setIssuer(string $issuer): Result
    {
        $this->issuer = $issuer;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): Result
    {
        $this->userId = $userId;

        return $this;
    }

    public function getClientUId(): string
    {
        return $this->clientUId;
    }

    public function setClientUId(string $clientUId): Result
    {
        $this->clientUId = $clientUId;

        return $this;
    }

    public function getCourseCode(): string
    {
        return $this->courseCode;
    }

    /**
     * @param string $tool
     */
    public function setCourseCode(string $courseCode): Result
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    public function getToolId(): int
    {
        return $this->toolId;
    }

    public function setToolId(int $toolId): Result
    {
        $this->toolId = $toolId;

        return $this;
    }

    public function getToolName(): string
    {
        return $this->toolName;
    }

    public function setToolName(string $toolName): Result
    {
        $this->toolName = $toolName;

        return $this;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): Result
    {
        $this->score = $score;

        return $this;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): Result
    {
        $this->progress = $progress;

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): Result
    {
        $this->duration = $duration;

        return $this;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): Result
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getUserIp(): string
    {
        return $this->userIp;
    }

    public function setUserIp(string $userIp): Result
    {
        $this->userIp = $userIp;

        return $this;
    }

    public function getLtiLaunchId(): string
    {
        return $this->ltiLaunchId;
    }

    public function setLtiLaunchId(string $ltiLaunchId): Result
    {
        $this->ltiLaunchId = $ltiLaunchId;

        return $this;
    }
}
