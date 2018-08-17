<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Legal.
 *
 * @ORM\Table(name="legal")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\LegalRepository")
 */
class Legal
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
     * @var int
     *
     * @ORM\Column(name="date", type="integer", nullable=false)
     */
    protected $date;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected $content;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="changes", type="text", nullable=false)
     */
    protected $changes;

    /**
     * @var int
     *
     * @ORM\Column(name="version", type="integer", nullable=true)
     */
    protected $version;

    /**
     * @var int
     *
     * @ORM\Column(name="language_id", type="integer")
     */
    protected $languageId;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Legal
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set date.
     *
     * @param int $date
     *
     * @return Legal
     */
    public function setDate($date)
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
     * @param string $content
     *
     * @return Legal
     */
    public function setContent($content)
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
     * @param int $type
     *
     * @return Legal
     */
    public function setType($type)
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
     * @param string $changes
     *
     * @return Legal
     */
    public function setChanges($changes)
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
     * @param int $version
     *
     * @return Legal
     */
    public function setVersion($version)
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
     * @param int $languageId
     *
     * @return Legal
     */
    public function setLanguageId($languageId)
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
