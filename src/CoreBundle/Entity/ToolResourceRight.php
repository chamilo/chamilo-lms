<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Doctrine\ORM\Mapping as ORM;

/**
 * ToolResourceRight.
 *
 * @ORM\Table(name="tool_resource_right")
 * @ORM\Entity
 */
class ToolResourceRight
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

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
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Tool", inversedBy="toolResourceRight", cascade={"persist"})
     * @ORM\JoinColumn(name="tool_id", referencedColumnName="id")
     */
    protected $tool;

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
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public static function getDefaultRoles(): array
    {
        return [
            'Students' => 'ROLE_STUDENT',
            'Teachers' => 'ROLE_TEACHER',
        ];
    }

    /**
     * @return array
     */
    public static function getMaskList(): array
    {
        $readerMask = ResourceNodeVoter::getReaderMask();
        $editorMask = ResourceNodeVoter::getEditorMask();

        return [
            'Can read' => $readerMask,
            'Can edit' => $editorMask,
        ];
    }
}
