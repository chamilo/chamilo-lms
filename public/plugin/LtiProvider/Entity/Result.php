<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\LtiProvider\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'plugin_lti_provider_result')]
#[ORM\Entity]
class Result
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id;

    #[ORM\Column(name: 'issuer', type: 'text')]
    protected string $issuer;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    protected int $userId;

    #[ORM\Column(name: 'client_uid', type: 'text')]
    protected string $clientUId;

    #[ORM\Column(name: 'course_code', type: 'text', length: 40, nullable: true)]
    protected string $courseCode;

    #[ORM\Column(name: 'tool_id', type: 'integer')]
    protected int $toolId;

    #[ORM\Column(name: 'tool_name', type: 'string')]
    protected string $toolName;

    #[ORM\Column(name: 'score', type: 'float', precision: 6, scale: 2)]
    protected float $score;

    #[ORM\Column(name: 'progress', type: 'integer')]
    protected int $progress;

    #[ORM\Column(name: 'duration', type: 'integer')]
    protected int $duration;

    #[ORM\Column(name: 'start_date', type: 'datetime')]
    protected \DateTime $startDate;

    #[ORM\Column(name: 'user_ip', type: 'string')]
    protected string $userIp;

    #[ORM\Column(name: 'lti_launch_id', type: 'string')]
    protected string $ltiLaunchId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function setIssuer(string $issuer): static
    {
        $this->issuer = $issuer;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getClientUId(): string
    {
        return $this->clientUId;
    }

    public function setClientUId(string $clientUId): static
    {
        $this->clientUId = $clientUId;

        return $this;
    }

    public function getCourseCode(): string
    {
        return $this->courseCode;
    }

    public function setCourseCode(string $courseCode): static
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    public function getToolId(): int
    {
        return $this->toolId;
    }

    public function setToolId(int $toolId): static
    {
        $this->toolId = $toolId;

        return $this;
    }

    public function getToolName(): string
    {
        return $this->toolName;
    }

    public function setToolName(string $toolName): static
    {
        $this->toolName = $toolName;

        return $this;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): static
    {
        $this->progress = $progress;

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getUserIp(): string
    {
        return $this->userIp;
    }

    public function setUserIp(string $userIp): static
    {
        $this->userIp = $userIp;

        return $this;
    }

    public function getLtiLaunchId(): string
    {
        return $this->ltiLaunchId;
    }

    public function setLtiLaunchId(string $ltiLaunchId): static
    {
        $this->ltiLaunchId = $ltiLaunchId;

        return $this;
    }
}
