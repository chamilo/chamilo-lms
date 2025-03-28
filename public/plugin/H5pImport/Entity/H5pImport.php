<?php

// For licensing terms, see /license.txt

namespace Chamilo\PluginBundle\Entity\H5pImport;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class H5pImport.
 *
 * @ORM\Entity()
 *
 * @ORM\Table(name="plugin_h5p_import")
 */
class H5pImport
{
    /**
     * @var string
     *
     * @ORM\Column(name="path", type="text", nullable=false)
     */
    protected $path;

    /**
     * @var string
     *
     * @ORM\Column(name="relative_path", type="text", nullable=false)
     */
    protected $relativePath;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @var DateTime
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
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     *
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    private $course;

    /**
     * @var null|Session
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     *
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    private $session;

    /**
     * @var null|string
     *
     * @ORM\Column(name="name", type="text", nullable=true)
     */
    private $name;

    /**
     * @var null|string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var Collection<int, H5pImportLibrary>
     *
     * @ORM\ManyToMany(targetEntity="H5pImportLibrary", mappedBy="h5pImports", cascade={"persist"})
     */
    private $libraries;

    /**
     * @var H5pImportLibrary
     *
     * @ORM\ManyToOne(targetEntity="H5pImportLibrary")
     *
     * @ORM\JoinColumn(name="main_library_id", referencedColumnName="iid", onDelete="SET NULL")
     */
    private $mainLibrary;

    public function __construct()
    {
        $this->libraries = new ArrayCollection();
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function setIid(int $iid): void
    {
        $this->iid = $iid;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): H5pImport
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): H5pImport
    {
        $this->session = $session;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): H5pImport
    {
        $this->name = $name;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): H5pImport
    {
        $this->path = $path;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): H5pImport
    {
        $this->description = $description;

        return $this;
    }

    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    public function setRelativePath(string $relativePath): H5pImport
    {
        $this->relativePath = $relativePath;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): H5pImport
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModifiedAt(): DateTime
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(DateTime $modifiedAt): void
    {
        $this->modifiedAt = $modifiedAt;
    }

    public function addLibraries(H5pImportLibrary $library): self
    {
        $library->addH5pImport($this);
        $this->libraries[] = $library;

        return $this;
    }

    public function removeLibraries(H5pImportLibrary $library): self
    {
        $this->libraries->removeElement($library);

        return $this;
    }

    public function getLibraries(): Collection
    {
        return $this->libraries;
    }

    public function setMainLibrary(H5pImportLibrary $library): self
    {
        $this->mainLibrary = $library;

        return $this;
    }

    public function getMainLibrary(): ?H5pImportLibrary
    {
        return $this->mainLibrary;
    }
}
