<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Legal.
 *
 * @ORM\Table(name="legal")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\LegalRepository")
 */
class Legal
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="date", type="integer", nullable=false)
     */
    protected int $date;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected ?string $content = null;

    /**
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    protected int $type;

    /**
     * @ORM\Column(name="changes", type="text", nullable=false)
     */
    protected string $changes;

    /**
     * @ORM\Column(name="version", type="integer", nullable=true)
     */
    protected ?int $version = null;

    /**
     * @ORM\Column(name="language_id", type="integer")
     */
    protected int $languageId;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date.
     *
     * @return Legal
     */
    public function setDate(int $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set content.
     *
     * @return Legal
     */
    public function setContent(string $content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set type.
     *
     * @return Legal
     */
    public function setType(int $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set changes.
     *
     * @return Legal
     */
    public function setChanges(string $changes)
    {
        $this->changes = $changes;

        return $this;
    }

    /**
     * Get changes.
     *
     * @return string
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Set version.
     *
     * @return Legal
     */
    public function setVersion(int $version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set languageId.
     *
     * @return Legal
     */
    public function setLanguageId(int $languageId)
    {
        $this->languageId = $languageId;

        return $this;
    }

    /**
     * Get languageId.
     *
     * @return int
     */
    public function getLanguageId()
    {
        return $this->languageId;
    }
}
