<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\H5pImport;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImport;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class H5pImportLibrary.
 *
 * @package Chamilo\PluginBundle\Entity\H5pImport
 *
 * @ORM\Entity()
 * @ORM\Table(name="plugin_h5p_import_library")
 */

class H5pImportLibrary extends EntityRepository
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private int $iid;

    /**
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    private string $title;

    /**
     * @ORM\Column(name="machine_name", type="string")
     */
    private string $machineName;

    /**
     * @ORM\Column(name="major_version", type="integer")
     */
    private int $majorVersion;

    /**
     * @ORM\Column(name="minor_version", type="integer")
     */
    private int $minorVersion;

    /**
     * @ORM\Column(name="patch_version", type="integer")
     */
    private int $patchVersion;

    /**
     * @ORM\Column(name="runnable", type="integer", nullable=true)
     */
    private ?int $runnable;

    /**
     * @ORM\Column(name="embed_types", type="array", nullable=true)
     */
    private ?array $embedTypes;

    /**
     * @ORM\Column(name="preloaded_js" , type="array", nullable=true)
     */
    private ?array $preloadedJs;

    /**
     * @ORM\Column(name="preloaded_css", type="array", nullable=true)
     */
    private ?array $preloadedCss;

    /**
     * @ORM\Column(name="library_path", type="string", length=255)
     */
    private string $libraryPath;

    /**
     * @var Collection<int, H5pImport>
     * @ORM\ManyToMany(targetEntity="H5pImport", inversedBy="libraries")
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
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    private Course $course;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected DateTime $createdAt;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="modified_at", type="datetime", nullable=false)
     */
    protected DateTime $modifiedAt;

    public function __construct()
    {
        $this->h5pImports = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getIid(): int
    {
        return $this->iid;
    }

    /**
     * @param int $iid
     * @return H5pImportLibrary
     */
    public function setIid(int $iid): H5pImportLibrary
    {
        $this->iid = $iid;
        return $this;
    }

    /**
     * @return string
     */
    public function getMachineName(): string
    {
        return $this->machineName;
    }

    /**
     * @param string $machineName
     * @return H5pImportLibrary
     */
    public function setMachineName(string $machineName): H5pImportLibrary
    {
        $this->machineName = $machineName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return H5pImportLibrary
     */
    public function setTitle(string $title): H5pImportLibrary
    {
        $this->title = $title;
        return $this;
    }
    /**
     * @return int
     */
    public function getMajorVersion(): int
    {
        return $this->majorVersion;
    }

    /**
     * @param int $majorVersion
     * @return H5pImportLibrary
     */
    public function setMajorVersion(int $majorVersion): H5pImportLibrary
    {
        $this->majorVersion = $majorVersion;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinorVersion(): int
    {
        return $this->minorVersion;
    }

    /**
     * @param int $minorVersion
     * @return H5pImportLibrary
     */
    public function setMinorVersion(int $minorVersion): H5pImportLibrary
    {
        $this->minorVersion = $minorVersion;
        return $this;
    }

    /**
     * @return int
     */
    public function getPatchVersion(): int
    {
        return $this->patchVersion;
    }

    /**
     * @param int $patchVersion
     * @return H5pImportLibrary
     */
    public function setPatchVersion(int $patchVersion): H5pImportLibrary
    {
        $this->patchVersion = $patchVersion;
        return $this;
    }

    /**
     * @return int
     */
    public function getRunnable(): int
    {
        return $this->runnable;
    }

    /**
     * @param int|null $runnable
     * @return H5pImportLibrary
     */
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

    /**
     * @param array|null $preloadedJs
     * @return H5pImportLibrary
     */
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

    /**
     * @param array|null $preloadedCss
     * @return H5pImportLibrary
     */
    public function setPreloadedCss(?array $preloadedCss): H5pImportLibrary
    {
        $this->preloadedCss = $preloadedCss;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getEmbedTypes(): ?array
    {
        return $this->embedTypes;
    }

    /**
     * @param array|null $embedTypes
     * @return H5pImportLibrary
     */
    public function setEmbedTypes(?array $embedTypes): H5pImportLibrary
    {
        $this->embedTypes = $embedTypes;
        return $this;
    }

    /**
     * @return string
     */
    public function getLibraryPath(): string
    {
        return $this->libraryPath;
    }

    /**
     * @param string $libraryPath
     * @return H5pImportLibrary
     */
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

    /**
     * @return Course
     */
    public function getCourse(): Course
    {
        return $this->course;
    }

    /**
     * @param Course $course
     * @return H5pImportLibrary
     */
    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return H5pImportLibrary
     */
    public function setCreatedAt(DateTime $createdAt): H5pImportLibrary
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getModifiedAt(): DateTime
    {
        return $this->modifiedAt;
    }

    /**
     * @param DateTime $modifiedAt
     * @return H5pImportLibrary
     */
    public function setModifiedAt(DateTime $modifiedAt): H5pImportLibrary
    {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    public function getLibraryByMachineNameAndVersions(string $machineName, int $majorVersion, int $minorVersion)
    {
        if (
            $this->machineName === $machineName &&
            $this->majorVersion === $majorVersion &&
            $this->minorVersion === $minorVersion
        ) {
            return $this;
        }
        return false;
    }

    /**
     * Get the preloaded JS array of the imported library formatted as a comma-separated string.
     *
     * @return string The preloaded JS array of the imported library formatted as a comma-separated string.
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
     * @return string The preloaded CSS array of the imported library formatted as a comma-separated string.
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
     * @return string The embed types array formatted as a comma-separated string.
     */
    public function getEmbedTypesFormatted(): string
    {
        return implode(',', $this->getEmbedTypes());
    }

}
