<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\ChamiloCoreBundle;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Language
 *
 * @ORM\Table(name="language", indexes={@ORM\Index(name="idx_language_dokeos_folder", columns={"dokeos_folder"})})
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\LanguageRepository")
 */
class Language
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="original_name", type="string", length=255, nullable=true)
     */
    private $originalName;

    /**
     * @var string
     *
     * @ORM\Column(name="english_name", type="string", length=255, nullable=true)
     */
    private $englishName;

    /**
     * @var string
     *
     * @ORM\Column(name="isocode", type="string", length=10, nullable=true)
     */
    private $isocode;

    /**
     * @var string
     *
     * @ORM\Column(name="dokeos_folder", type="string", length=250, nullable=true)
     */
    private $dokeosFolder;

    /**
     * @var boolean
     *
     * @ORM\Column(name="available", type="boolean", nullable=false)
     */
    private $available;

    /**
     * @var \Chamilo\CoreBundle\Entity\Language
     * @ORM\ManyToOne(targetEntity="Language", inversedBy="subLanguages")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    private $parent;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Language", mappedBy="parent")
     */
    private $subLanguages;

    /**
     * Language constructor
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * Set originalName
     *
     * @param string $originalName
     * @return Language
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;

        return $this;
    }

    /**
     * Get originalName
     *
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Set englishName
     *
     * @param string $englishName
     * @return Language
     */
    public function setEnglishName($englishName)
    {
        $this->englishName = $englishName;

        return $this;
    }

    /**
     * Get englishName
     *
     * @return string
     */
    public function getEnglishName()
    {
        return $this->englishName;
    }

    /**
     * Set isocode
     *
     * @param string $isocode
     * @return Language
     */
    public function setIsocode($isocode)
    {
        $this->isocode = $isocode;

        return $this;
    }

    /**
     * Get isocode
     *
     * @return string
     */
    public function getIsocode()
    {
        return $this->isocode;
    }

    /**
     * Set dokeosFolder
     *
     * @param string $dokeosFolder
     * @return Language
     */
    public function setDokeosFolder($dokeosFolder)
    {
        $this->dokeosFolder = $dokeosFolder;

        return $this;
    }

    /**
     * Get dokeosFolder
     *
     * @return string
     */
    public function getDokeosFolder()
    {
        return $this->dokeosFolder;
    }

    /**
     * Set available
     *
     * @param boolean $available
     * @return Language
     */
    public function setAvailable($available)
    {
        $this->available = $available;

        return $this;
    }

    /**
     * Get available
     *
     * @return boolean
     */
    public function getAvailable()
    {
        return $this->available;
    }

    /**
     * Set parent
     *
     * @param Language $parent
     * @return Language
     */
    public function setParent(Language $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return Language
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get subLanguages
     *
     * @return ArrayCollection
     */
    public function getSubLanguages()
    {
        return $this->subLanguages;
    }

    /**
     * Get id
     *
     * @return boolean
     */
    public function getId()
    {
        return $this->id;
    }
}
