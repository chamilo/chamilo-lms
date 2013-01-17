<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCQuizQuestion
 *
 * @Table(name="c_quiz_question")
 * @Entity
 */
class EntityCQuizQuestion
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
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="question", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $question;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

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
     * @var boolean
     *
     * @Column(name="type", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;

    /**
     * @var string
     *
     * @Column(name="picture", type="string", length=50, precision=0, scale=0, nullable=true, unique=false)
     */
    private $picture;

    /**
     * @var integer
     *
     * @Column(name="level", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $level;

    /**
     * @var string
     *
     * @Column(name="extra", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $extra;

    /**
     * @var string
     *
     * @Column(name="question_code", type="string", length=10, precision=0, scale=0, nullable=true, unique=false)
     */
    private $questionCode;

    /**
     * @var integer
     *
     * @Column(name="parent_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $parentId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCQuizQuestion
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
     * Set id
     *
     * @param integer $id
     * @return EntityCQuizQuestion
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
     * Set question
     *
     * @param string $question
     * @return EntityCQuizQuestion
     */
    public function setQuestion($question)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question
     *
     * @return string 
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return EntityCQuizQuestion
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set ponderation
     *
     * @param float $ponderation
     * @return EntityCQuizQuestion
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
     * @return EntityCQuizQuestion
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
     * Set type
     *
     * @param boolean $type
     * @return EntityCQuizQuestion
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return boolean 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set picture
     *
     * @param string $picture
     * @return EntityCQuizQuestion
     */
    public function setPicture($picture)
    {
        $this->picture = $picture;

        return $this;
    }

    /**
     * Get picture
     *
     * @return string 
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * Set level
     *
     * @param integer $level
     * @return EntityCQuizQuestion
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return integer 
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set extra
     *
     * @param string $extra
     * @return EntityCQuizQuestion
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Get extra
     *
     * @return string 
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * Set questionCode
     *
     * @param string $questionCode
     * @return EntityCQuizQuestion
     */
    public function setQuestionCode($questionCode)
    {
        $this->questionCode = $questionCode;

        return $this;
    }

    /**
     * Get questionCode
     *
     * @return string 
     */
    public function getQuestionCode()
    {
        return $this->questionCode;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     * @return EntityCQuizQuestion
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer 
     */
    public function getParentId()
    {
        return $this->parentId;
    }
}
