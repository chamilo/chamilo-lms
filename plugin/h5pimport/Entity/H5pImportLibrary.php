<?php

// For licensing terms, see /license.txt

namespace Chamilo\PluginBundle\Entity\H5pImport;

use Chamilo\CoreBundle\Entity\Course;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class H5pImportLibrary.
 *
 * @ORM\Entity()
 *
 * @ORM\Table(name="plugin_h5p_import_library")
 */
class H5pImportLibrary extends EntityRepository
{
    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=false)
     */
    protected $modifiedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(name="machine_name", type="string")
     */
    private $machineName;

    /**
     * @ORM\Column(name="major_version", type="integer")
     */
    private $majorVersion;

    /**
     * @ORM\Column(name="minor_version", type="integer")
     */
    private $minorVersion;

    /**
     * @ORM\Column(name="patch_version", type="integer")
     */
    private $patchVersion;

    /**
     * @ORM\Column(name="runnable", type="integer", nullable=true)
     */
    private $runnable;

    /**
     * @ORM\Column(name="embed_types", type="array", nullable=true)
     */
    private $embedTypes;

    /**
     * @ORM\Column(name="preloaded_js" , type="array", nullable=true)
     */
    private $preloadedJs;

    /**
     * @ORM\Column(name="preloaded_css", type="array", nullable=true)
     */
    private $preloadedCss;

    /**
     * @ORM\Column(name="library_path", type="string", length=255)
     */
    private $libraryPath;

    /**
     * @var Collection<int, H5pImport>
     *
     * @ORM\ManyToMany(targetEntity="H5pImport", inversedBy="libraries")
     *
     * @ORM\JoinTable(
     *      name="plugin_h5p_import_rel_libraries",
     *      joinColumns={@ORM\JoinColumn(name="h5p_import_library_id", referencedColumnName="iid", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="h5p_import_id", referencedColumnName="iid", onDelete="CASCADE")}
     * )
     */
    private $h5pImports;

    /**
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     *
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    private $course;

    public function __construct()
    {
        $this->h5pImports = new ArrayCollection();
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function setIid(int $iid): H5pImportLibrary
    {
        $this->iid = $iid;

        return $this;
    }

    public function getMachineName(): string
    {
        return $this->machineName;
    }

    public function setMachineName(string $machineName): H5pImportLibrary
    {
        $this->machineName = $machineName;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): H5pImportLibrary
    {
        $this->title = $title;

        return $this;
    }

    public function getMajorVersion(): int
    {
        return $this->majorVersion;
    }

    public function setMajorVersion(int $majorVersion): H5pImportLibrary
    {
        $this->majorVersion = $majorVersion;

        return $this;
    }

    public function getMinorVersion(): int
    {
        return $this->minorVersion;
    }

    public function setMinorVersion(int $minorVersion): H5pImportLibrary
    {
        $this->minorVersion = $minorVersion;

        return $this;
    }

    public function getPatchVersion(): int
    {
        return $this->patchVersion;
    }

    public function setPatchVersion(int $patchVersion): H5pImportLibrary
    {
        $this->patchVersion = $patchVersion;

        return $this;
    }

    public function getRunnable(): int
    {
        return $this->runnable;
    }

    public function setRunnable(?int $runnable): H5pImportLibrary
    {
        $this->runnable = $runnable;

        return $this;
    }

    /**
     * @return array
     */
    public function getPreloadedJs(): ?array
    {
        return $this->preloadedJs;
    }

    public function setPreloadedJs(?array $preloadedJs): H5pImportLibrary
    {
        $this->preloadedJs = $preloadedJs;

        return $this;
    }

    /**
     * @return array
     */
    public function getPreloadedCss(): ?array
    {
        return $this->preloadedCss;
    }

    public function setPreloadedCss(?array $preloadedCss): H5pImportLibrary
    {
        $this->preloadedCss = $preloadedCss;

        return $this;
    }

    public function getEmbedTypes(): ?array
    {
        return $this->embedTypes;
    }

    public function setEmbedTypes(?array $embedTypes): H5pImportLibrary
    {
        $this->embedTypes = $embedTypes;

        return $this;
    }

    public function getLibraryPath(): string
    {
        return $this->libraryPath;
    }

    public function setLibraryPath(string $libraryPath): H5pImportLibrary
    {
        $this->libraryPath = $libraryPath;

        return $this;
    }

    public function addH5pImport(H5pImport $h5pImport): self
    {
        if (!$this->h5pImports->contains($h5pImport)) {
            $this->h5pImports[] = $h5pImport;
        }

        return $this;
    }

    public function removeH5pImport(H5pImport $h5pImport): self
    {
        $this->h5pImports->removeElement($h5pImport);

        return $this;
    }

    public function getH5pImports(): Collection
    {
        return $this->h5pImports;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): H5pImportLibrary
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModifiedAt(): \DateTime
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(\DateTime $modifiedAt): H5pImportLibrary
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    public function getLibraryByMachineNameAndVersions(string $machineName, int $majorVersion, int $minorVersion)
    {
        if (
            $this->machineName === $machineName
            && $this->majorVersion === $majorVersion
            && $this->minorVersion === $minorVersion
        ) {
            return $this;
        }

        return false;
    }

    /**
     * Get the preloaded JS array of the imported library formatted as a comma-separated string.
     *
     * @return string the preloaded JS array of the imported library formatted as a comma-separated string
     */
    public function getPreloadedJsFormatted(): string
    {
        $formattedJs = [];

        foreach ($this->preloadedJs as $value) {
            if (is_string($value->path)) {
                $formattedJs[] = $value->path;
            }
        }

        return implode(',', $formattedJs);
    }

    /**
     * Get the preloaded CSS array of the imported library formatted as a comma-separated string.
     *
     * @return string the preloaded CSS array of the imported library formatted as a comma-separated string
     */
    public function getPreloadedCssFormatted(): string
    {
        $formattedJCss = [];

        foreach ($this->preloadedCss as $value) {
            if (is_string($value->path)) {
                $formattedJCss[] = $value->path;
            }
        }

        return implode(',', $formattedJCss);
    }

    /**
     * Get the embed types array formatted as a comma-separated string.
     *
     * @return string the embed types array formatted as a comma-separated string
     */
    public function getEmbedTypesFormatted(): string
    {
        return implode(',', $this->getEmbedTypes());
    }
}
