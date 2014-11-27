<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SessionFieldValues
 *
 * @ORM\Table(name="session_field_values", indexes={@ORM\Index(name="idx_session_field_values_session_id", columns={"session_id"}), @ORM\Index(name="idx_session_field_values_field_id", columns={"field_id"})})
 * @ORM\Entity
 * @Gedmo\Loggable
 */
class SessionFieldValues extends ExtraFieldValues
{
    /**
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\SessionField")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    protected $field;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    protected $session;

    /**
     * @var string
     * @Gedmo\Versioned
     *
     * @ORM\Column(name="field_value", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $fieldValue;

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
