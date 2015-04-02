<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Language
 *
 * @ORM\Table(name="language", indexes={@ORM\Index(name="idx_language_dokeos_folder", columns={"dokeos_folder"})})
 * @ORM\Entity
 */
class Language
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
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
     * @var boolean
     *
     * @ORM\Column(name="parent_id", type="boolean", nullable=true)
     */
    private $parentId;


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
     * Set parentId
     *
     * @param boolean $parentId
     * @return Language
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return boolean
     */
    public function getParentId()
    {
        return $this->parentId;
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
