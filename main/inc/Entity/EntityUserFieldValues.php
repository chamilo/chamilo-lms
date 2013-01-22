<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityUserFieldValues
 *
 * @Table(name="user_field_values")
 * @Entity
 */
class EntityUserFieldValues
{
    /**
     * @var integer
     *
     * @Column(name="id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="field_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $fieldId;

    /**
     * @var string
     *
     * @Column(name="field_value", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fieldValue;

    /**
     * @var \DateTime
     *
     * @Column(name="tms", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $tms;


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
     * Set userId
     *
     * @param integer $userId
     * @return EntityUserFieldValues
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set fieldId
     *
     * @param integer $fieldId
     * @return EntityUserFieldValues
     */
    public function setFieldId($fieldId)
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    /**
     * Get fieldId
     *
     * @return integer 
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * Set fieldValue
     *
     * @param string $fieldValue
     * @return EntityUserFieldValues
     */
    public function setFieldValue($fieldValue)
    {
        $this->fieldValue = $fieldValue;

        return $this;
    }

    /**
     * Get fieldValue
     *
     * @return string 
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * Set tms
     *
     * @param \DateTime $tms
     * @return EntityUserFieldValues
     */
    public function setTms($tms)
    {
        $this->tms = $tms;

        return $this;
    }

    /**
     * Get tms
     *
     * @return \DateTime 
     */
    public function getTms()
    {
        return $this->tms;
    }
}
