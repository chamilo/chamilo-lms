<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\H5pImport;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImportLibrary;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class H5pImport.
 *
 * @package Chamilo\PluginBundle\Entity\H5pImport
 *
 * @ORM\Entity()
 * @ORM\Table(name="plugin_h5p_import")
 */

class H5pImport
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
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    private Course $course;
    /**
     * @var Session|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    private ?Session $session;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", nullable=true)
     */
    private string $name;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="text", nullable=false)
     */
    protected string $path;

    /**
     * @var Collection<int, H5pImportLibrary>
     * @ORM\ManyToMany(targetEntity="H5pImportLibrary", mappedBy="h5pImports", cascade={"persist"})
     */
    private $libraries;

    /**
     * @var H5pImportLibrary
     * @ORM\ManyToOne(targetEntity="H5pImportLibrary")
     * @ORM\JoinColumn(name="main_library_id", referencedColumnName="iid", onDelete="SET NULL")
     */
    private H5pImportLibrary $mainLibrary;

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
        $this->libraries = new ArrayCollection();
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
     */
    public function setIid(int $iid): void
    {
        $this->iid = $iid;
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
     * @return H5pImport
     */
    public function setCourse(Course $course): H5pImport
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @return Session|null
     */
    public function getSession(): ?Session
    {
        return $this->session;
    }

    /**
     * @param Session|null $session
     * @return H5pImport
     */
    public function setSession(?Session $session): H5pImport
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return H5pImport
     */
    public function setName(string $name): H5pImport
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return H5pImport
     */
    public function setPath(string $path): H5pImport
    {
        $this->path = $path;

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
     * @return H5pImport
     */
    public function setCreatedAt(DateTime $createdAt): H5pImport
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
     */
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
