<?php

namespace ChamiloLMS\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SessionFieldValues
 *
 * @ORM\Table(name="session_field_values")
 * @ORM\Entity
 * @Gedmo\Loggable
 */
class SessionFieldValues extends ExtraFieldValues
{

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var string
     * @Gedmo\Versioned
     *
     * @ORM\Column(name="field_value", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fieldValue;

    /**
     * Set fieldValue
     *
     * @param string $fieldValue
     * @return ExtraFieldValues
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
     * Set questionId
     *
     * @param integer $questionId
     * @return QuestionFieldValues
     */
    public function setSessionId($id)
    {
        $this->sessionId = $id;
        return $this;
    }

    /**
     * Get questionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
}
