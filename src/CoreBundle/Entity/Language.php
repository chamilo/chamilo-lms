<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Language.
 *
 * @ORM\Table(
 *     name="language",
 *     options={"row_format"="DYNAMIC"}
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\LanguageRepository")
 */
class Language
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="original_name", type="string", length=255, nullable=true)
     */
    protected ?string $originalName = null;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="english_name", type="string", length=255)
     */
    protected string $englishName;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="isocode", type="string", length=10)
     */
    protected string $isocode;

    /**
     * @ORM\Column(name="available", type="boolean", nullable=false)
     */
    protected bool $available;

    /**
     * @ORM\ManyToOne(targetEntity="Language", inversedBy="subLanguages")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    protected ?Language $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="Language", mappedBy="parent")
     */
    protected Collection $subLanguages;

    public function __construct()
    {
        $this->subLanguages = new ArrayCollection();
    }

    /**
     * Set originalName.
     *
     * @return Language
     */
    public function setOriginalName(string $originalName)
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
     * @return Language
     */
    public function setEnglishName(string $englishName)
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
     * @return Language
     */
    public function setIsocode(string $isocode)
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
     * Set available.
     *
     * @return Language
     */
    public function setAvailable(bool $available)
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
    public function setParent(self $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getSubLanguages(): Collection
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
