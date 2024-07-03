<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\AiHelper;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Platform.
 *
 * @package Chamilo\PluginBundle\Entity\AiHelper
 *
 * @ORM\Table(name="plugin_ai_helper_requests")
 * @ORM\Entity()
 */
class Requests
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
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="tool_name", type="string")
     */
    private $toolName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="requested_at", type="datetime", nullable=true)
     */
    private $requestedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="request_text", type="string")
     */
    private $requestText;

    /**
     * @var int
     *
     * @ORM\Column(name="prompt_tokens", type="integer")
     */
    private $promptTokens;

    /**
     * @var int
     *
     * @ORM\Column(name="completion_tokens", type="integer")
     */
    private $completionTokens;

    /**
     * @var int
     *
     * @ORM\Column(name="total_tokens", type="integer")
     */
    private $totalTokens;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): Requests
    {
        $this->userId = $userId;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Requests
    {
        $this->id = $id;

        return $this;
    }

    public function getRequestedAt(): \DateTime
    {
        return $this->requestedAt;
    }

    public function setRequestedAt(\DateTime $requestedAt): Requests
    {
        $this->requestedAt = $requestedAt;

        return $this;
    }

    public function getRequestText(): string
    {
        return $this->requestText;
    }

    public function setRequestText(string $requestText): Requests
    {
        $this->requestText = $requestText;

        return $this;
    }

    public function getPromptTokens(): int
    {
        return $this->promptTokens;
    }

    public function setPromptTokens(int $promptTokens): Requests
    {
        $this->promptTokens = $promptTokens;

        return $this;
    }

    public function getCompletionTokens(): int
    {
        return $this->completionTokens;
    }

    public function setCompletionTokens(int $completionTokens): Requests
    {
        $this->completionTokens = $completionTokens;

        return $this;
    }

    public function getTotalTokens(): int
    {
        return $this->totalTokens;
    }

    public function setTotalTokens(int $totalTokens): Requests
    {
        $this->totalTokens = $totalTokens;

        return $this;
    }

    public function getToolName(): string
    {
        return $this->toolName;
    }

    public function setToolName(string $toolName): Requests
    {
        $this->toolName = $toolName;

        return $this;
    }
}
