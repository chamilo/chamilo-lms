<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use APY\DataGridBundle\Grid\Mapping as GRID;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceInterface;
use Chamilo\CourseBundle\Traits\PersonalResourceTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Illustration.
 * @ORM\Table(name="illustration")
 * @ORM\Entity
 * @GRID\Source(columns="id, name, resourceNode.createdAt", filterable=false, groups={"resource"})
 */
class Illustration extends AbstractResource implements ResourceInterface
{
    use PersonalResourceTrait;
    use TimestampableEntity;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * Illustration constructor.
     */
    public function __construct()
    {
        $this->name = 'illustration';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Illustration
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }

    public function setName(string $name): Illustration
    {
        $this->name = $name;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    public function getResourceName(): string
    {
        return $this->getName();
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
