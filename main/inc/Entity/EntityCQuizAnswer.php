<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCQuizAnswer
 *
 * @Table(name="c_quiz_answer")
 * @Entity
 */
class EntityCQuizAnswer
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id_auto", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $idAuto;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionId;

    /**
     * @var string
     *
     * @Column(name="answer", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $answer;

    /**
     * @var integer
     *
     * @Column(name="correct", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $correct;

    /**
     * @var string
     *
     * @Column(name="comment", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $comment;

    /**
     * @var float
     *
     * @Column(name="ponderation", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $ponderation;

    /**
     * @var integer
     *
     * @Column(name="position", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $position;

    /**
     * @var string
     *
     * @Column(name="hotspot_coordinates", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $hotspotCoordinates;

    /**
     * @var string
     *
     * @Column(name="hotspot_type", type="string", precision=0, scale=0, nullable=true, unique=false)
     */
    private $hotspotType;

    /**
     * @var string
     *
     * @Column(name="destination", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $destination;

    /**
     * @var string
     *
     * @Column(name="answer_code", type="string", length=10, precision=0, scale=0, nullable=true, unique=false)
     */
    private $answerCode;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCQuizAnswer
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer 
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set idAuto
     *
     * @param integer $idAuto
     * @return EntityCQuizAnswer
     */
    public function setIdAuto($idAuto)
    {
        $this->idAuto = $idAuto;

        return $this;
    }

    /**
     * Get idAuto
     *
     * @return integer 
     */
    public function getIdAuto()
    {
        return $this->idAuto;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCQuizAnswer
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set questionId
     *
     * @param integer $questionId
     * @return EntityCQuizAnswer
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;

        return $this;
    }

    /**
     * Get questionId
     *
     * @return integer 
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }

    /**
     * Set answer
     *
     * @param string $answer
     * @return EntityCQuizAnswer
     */
    public function setAnswer($answer)
    {
        $this->answer = $answer;

        return $this;
    }

    /**
     * Get answer
     *
     * @return string 
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * Set correct
     *
     * @param integer $correct
     * @return EntityCQuizAnswer
     */
    public function setCorrect($correct)
    {
        $this->correct = $correct;

        return $this;
    }

    /**
     * Get correct
     *
     * @return integer 
     */
    public function getCorrect()
    {
        return $this->correct;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return EntityCQuizAnswer
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set ponderation
     *
     * @param float $ponderation
     * @return EntityCQuizAnswer
     */
    public function setPonderation($ponderation)
    {
        $this->ponderation = $ponderation;

        return $this;
    }

    /**
     * Get ponderation
     *
     * @return float 
     */
    public function getPonderation()
    {
        return $this->ponderation;
    }

    /**
     * Set position
     *
     * @param integer $position
     * @return EntityCQuizAnswer
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer 
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set hotspotCoordinates
     *
     * @param string $hotspotCoordinates
     * @return EntityCQuizAnswer
     */
    public function setHotspotCoordinates($hotspotCoordinates)
    {
        $this->hotspotCoordinates = $hotspotCoordinates;

        return $this;
    }

    /**
     * Get hotspotCoordinates
     *
     * @return string 
     */
    public function getHotspotCoordinates()
    {
        return $this->hotspotCoordinates;
    }

    /**
     * Set hotspotType
     *
     * @param string $hotspotType
     * @return EntityCQuizAnswer
     */
    public function setHotspotType($hotspotType)
    {
        $this->hotspotType = $hotspotType;

        return $this;
    }

    /**
     * Get hotspotType
     *
     * @return string 
     */
    public function getHotspotType()
    {
        return $this->hotspotType;
    }

    /**
     * Set destination
     *
     * @param string $destination
     * @return EntityCQuizAnswer
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * Get destination
     *
     * @return string 
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * Set answerCode
     *
     * @param string $answerCode
     * @return EntityCQuizAnswer
     */
    public function setAnswerCode($answerCode)
    {
        $this->answerCode = $answerCode;

        return $this;
    }

    /**
     * Get answerCode
     *
     * @return string 
     */
    public function getAnswerCode()
    {
        return $this->answerCode;
    }
}
