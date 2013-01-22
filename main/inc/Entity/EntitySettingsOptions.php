<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySettingsOptions
 *
 * @Table(name="settings_options")
 * @Entity
 */
class EntitySettingsOptions
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="variable", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $variable;

    /**
     * @var string
     *
     * @Column(name="value", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $value;

    /**
     * @var string
     *
     * @Column(name="display_text", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $displayText;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set variable
     *
     * @param string $variable
     * @return EntitySettingsOptions
     */
    public function setVariable($variable)
    {
        $this->variable = $variable;

        return $this;
    }

    /**
     * Get variable
     *
     * @return string 
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return EntitySettingsOptions
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set displayText
     *
     * @param string $displayText
     * @return EntitySettingsOptions
     */
    public function setDisplayText($displayText)
    {
        $this->displayText = $displayText;

        return $this;
    }

    /**
     * Get displayText
     *
     * @return string 
     */
    public function getDisplayText()
    {
        return $this->displayText;
    }
}
