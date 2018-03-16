<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tool.
 *
 * @ORM\Table(name="tool_resource_rights")
 * @ORM\Entity
 */
class ToolResourceRights
{
    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=255, nullable=false)
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Column(name="mask", type="integer", nullable=false)
     */
    protected $mask;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Tool", inversedBy="toolResourceRights", cascade={"persist"})
     * @ORM\JoinColumn(name="tool_id", referencedColumnName="id")
     */
    protected $tool;
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getMask();
    }

    /**
     * @return Tool
     */
    public function getTool()
    {
        return $this->tool;
    }

    /**
     * @param Tool $tool
     *
     * @return $this
     */
    public function setTool($tool)
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     *
     * @return $this
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return int
     */
    public function getMask()
    {
        return $this->mask;
    }

    /**
     * @param mixed $mask
     *
     * @return $this
     */
    public function setMask($mask)
    {
        $this->mask = $mask;

        return $this;
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

    /**
     * @return array
     */
    public static function getDefaultRoles()
    {
        return [
            'ROLE_STUDENT' => 'Students',
            'ROLE_TEACHER' => 'Teachers',
        ];
    }

    /**
     * @return array
     */
    public static function getMaskList()
    {
        $readerMask = ResourceNodeVoter::getReaderMask();
        $editorMask = ResourceNodeVoter::getEditorMask();

        return [
            $readerMask => 'Can read',
            $editorMask => 'Can edit',
        ];
    }
}
