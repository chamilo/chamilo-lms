<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityLegal
 *
 * @Table(name="legal")
 * @Entity
 */
class EntityLegal
{
    /**
     * @var integer
     *
     * @Column(name="legal_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $legalId;

    /**
     * @var integer
     *
     * @Column(name="language_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $languageId;

    /**
     * @var integer
     *
     * @Column(name="date", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $date;

    /**
     * @var string
     *
     * @Column(name="content", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $content;

    /**
     * @var integer
     *
     * @Column(name="type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;

    /**
     * @var string
     *
     * @Column(name="changes", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $changes;

    /**
     * @var integer
     *
     * @Column(name="version", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $version;


    /**
     * Set legalId
     *
     * @param integer $legalId
     * @return EntityLegal
     */
    public function setLegalId($legalId)
    {
        $this->legalId = $legalId;

        return $this;
    }

    /**
     * Get legalId
     *
     * @return integer 
     */
    public function getLegalId()
    {
        return $this->legalId;
    }

    /**
     * Set languageId
     *
     * @param integer $languageId
     * @return EntityLegal
     */
    public function setLanguageId($languageId)
    {
        $this->languageId = $languageId;

        return $this;
    }

    /**
     * Get languageId
     *
     * @return integer 
     */
    public function getLanguageId()
    {
        return $this->languageId;
    }

    /**
     * Set date
     *
     * @param integer $date
     * @return EntityLegal
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return integer 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return EntityLegal
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return EntityLegal
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set changes
     *
     * @param string $changes
     * @return EntityLegal
     */
    public function setChanges($changes)
    {
        $this->changes = $changes;

        return $this;
    }

    /**
     * Get changes
     *
     * @return string 
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Set version
     *
     * @param integer $version
     * @return EntityLegal
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return integer 
     */
    public function getVersion()
    {
        return $this->version;
    }
}
