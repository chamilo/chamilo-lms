<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Language.
 *
 * @ORM\Table(name="language", indexes={@ORM\Index(name="idx_language_dokeos_folder", columns={"dokeos_folder"})})
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\LanguageRepository")
 */
class Language
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
     * @var string
     *
     * @ORM\Column(name="original_name", type="string", length=255, nullable=true)
     */
    protected $originalName;

    /**
     * @var string
     *
     * @ORM\Column(name="english_name", type="string", length=255, nullable=true)
     */
    protected $englishName;

    /**
     * @var string
     *
     * @ORM\Column(name="isocode", type="string", length=10, nullable=true)
     */
    protected $isocode;

    /**
     * @var string
     *
     * @ORM\Column(name="dokeos_folder", type="string", length=250, nullable=true)
     */
    protected $dokeosFolder;

    /**
     * @var bool
     *
     * @ORM\Column(name="available", type="boolean", nullable=false)
     */
    protected $available;

    /**
     * @var \Chamilo\CoreBundle\Entity\Language
     * @ORM\ManyToOne(targetEntity="Language", inversedBy="subLanguages")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    protected $parent;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Language", mappedBy="parent")
     */
    protected $subLanguages;

    /**
     * Language constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * Set originalName.
     *
     * @param string $originalName
     *
     * @return Language
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;

        return $this;
    }

    /**
     * Get originalName.
     *
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Set englishName.
     *
     * @param string $englishName
     *
     * @return Language
     */
    public function setEnglishName($englishName)
    {
        $this->englishName = $englishName;

        return $this;
    }

    /**
     * Get englishName.
     *
     * @return string
     */
    public function getEnglishName()
    {
        return $this->englishName;
    }

    /**
     * Set isocode.
     *
     * @param string $isocode
     *
     * @return Language
     */
    public function setIsocode($isocode)
    {
        $this->isocode = $isocode;

        return $this;
    }

    /**
     * Get isocode.
     *
     * @return string
     */
    public function getIsocode()
    {
        return $this->isocode;
    }

    /**
     * Set dokeosFolder.
     *
     * @param string $dokeosFolder
     *
     * @return Language
     */
    public function setDokeosFolder($dokeosFolder)
    {
        $this->dokeosFolder = $dokeosFolder;

        return $this;
    }

    /**
     * Get dokeosFolder.
     *
     * @return string
     */
    public function getDokeosFolder()
    {
        return $this->dokeosFolder;
    }

    /**
     * Set available.
     *
     * @param bool $available
     *
     * @return Language
     */
    public function setAvailable($available)
    {
        $this->available = $available;

        return $this;
    }

    /**
     * Get available.
     *
     * @return bool
     */
    public function getAvailable()
    {
        return $this->available;
    }

    /**
     * Set parent.
     *
     * @return Language
     */
    public function setParent(Language $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return Language
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get subLanguages.
     *
     * @return ArrayCollection
     */
    public function getSubLanguages()
    {
        return $this->subLanguages;
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
}
