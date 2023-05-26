<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

/**
 * ToolResourceRight.
 */
#[ORM\Table(name: 'tool_resource_right')]
#[ORM\Entity]
class ToolResourceRight implements Stringable
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'role', type: 'string', length: 255, nullable: false)]
    protected string $role;

    #[ORM\Column(name: 'mask', type: 'integer', nullable: false)]
    protected int $mask;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Tool::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'tool_id', referencedColumnName: 'id')]
    protected ?Tool $tool = null;

    public function __toString(): string
    {
        return (string) $this->getMask();
    }

    public function getTool(): Tool
    {
        return $this->tool;
    }

    public function setTool(Tool $tool): self
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

    public function setRole(string $role): self
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

    public function setMask(int $mask): self
    {
        $this->mask = $mask;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public static function getDefaultRoles(): array
    {
        return [
            'Students' => 'ROLE_STUDENT',
            'Teachers' => 'ROLE_TEACHER',
        ];
    }

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
