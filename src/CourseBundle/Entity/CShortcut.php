<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use APY\DataGridBundle\Grid\Mapping as GRID;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="c_shortcut")
 * @ORM\Entity
 * @GRID\Source(columns="id, name, resourceNode.createdAt", filterable=false, groups={"resource"})
 */
class CShortcut extends AbstractResource implements ResourceInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @Assert\NotBlank
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): CShortcut
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Resource identifier.
     */
    public function getResourceIdentifier(): int
    {
        return $this->id;
    }

    public function getResourceName(): string
    {
        return $this->getName();
    }
}
