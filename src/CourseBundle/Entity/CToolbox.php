<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Listener\ResourceListener;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CourseBundle\Repository\CToolboxRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ORM\Table(name: 'c_toolbox')]
#[ORM\Entity(repositoryClass: CToolboxRepository::class)]
#[ORM\EntityListeners([ResourceListener::class])]
class CToolbox extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title = '';

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'current_version', type: 'integer', nullable: false, options: ['default' => 1])]
    protected int $currentVersion = 1;

    /**
     * @var Collection<int, CToolboxVersion>
     */
    #[ORM\OneToMany(
        mappedBy: 'toolbox',
        targetEntity: CToolboxVersion::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['versionNumber' => 'DESC'])]
    protected Collection $versions;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCurrentVersion(): int
    {
        return $this->currentVersion;
    }

    public function setCurrentVersion(int $currentVersion): self
    {
        $this->currentVersion = max(1, $currentVersion);

        return $this;
    }

    /**
     * @return Collection<int, CToolboxVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function getCurrentVersionEntity(): ?CToolboxVersion
    {
        foreach ($this->versions as $version) {
            if ($version->getVersionNumber() === $this->currentVersion) {
                return $version;
            }
        }

        return null;
    }

    public function addVersion(CToolboxVersion $version): self
    {
        if (!$this->versions->contains($version)) {
            $this->versions->add($version);
            $version->setToolbox($this);
        }

        return $this;
    }

    public function removeVersion(CToolboxVersion $version): self
    {
        $this->versions->removeElement($version);

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return (int) $this->iid;
    }

    public function getResourceName(): string
    {
        return $this->title;
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
