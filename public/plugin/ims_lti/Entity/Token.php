<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\ImsLti;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Token.
 *
 * @package Chamilo\PluginBundle\Entity\ImsLti
 *
 * @ORM\Table(name="plugin_ims_lti_token")
 * @ORM\Entity()
 */
class Token
{
    const TOKEN_LIFETIME = 3600;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    protected $id;
    /**
     * @var ImsLtiTool
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool")
     * @ORM\JoinColumn(name="tool_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $tool;
    /**
     * @var array
     *
     * @ORM\Column(name="scope", type="json")
     */
    private $scope;
    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string")
     */
    private $hash;
    /**
     * @var int
     *
     * @ORM\Column(name="created_at", type="integer")
     */
    private $createdAt;
    /**
     * @var int
     *
     * @ORM\Column(name="expires_at", type="integer")
     */
    private $expiresAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ImsLtiTool
     */
    public function getTool()
    {
        return $this->tool;
    }

    /**
     * @param ImsLtiTool $tool
     *
     * @return Token
     */
    public function setTool($tool)
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * @return array
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param array $scope
     *
     * @return Token
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     *
     * @return Token
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return int
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param int $createdAt
     *
     * @return Token
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @param int $expiresAt
     *
     * @return Token
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getScopeInString()
    {
        return implode(' ', $this->scope);
    }

    /**
     * Generate unique hash.
     *
     * @return Token
     */
    public function generateHash()
    {
        $this->hash = sha1(uniqid(mt_rand()));

        return $this;
    }
}
