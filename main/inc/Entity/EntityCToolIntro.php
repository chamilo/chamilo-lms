<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCToolIntro
 *
 * @Table(name="c_tool_intro")
 * @Entity
 */
class EntityCToolIntro
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
     * @var string
     *
     * @Column(name="id", type="string", length=50, precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $sessionId;

    /**
     * @var string
     *
     * @Column(name="intro_text", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $introText;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCToolIntro
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
     * @param string $id
     * @return EntityCToolIntro
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCToolIntro
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set introText
     *
     * @param string $introText
     * @return EntityCToolIntro
     */
    public function setIntroText($introText)
    {
        $this->introText = $introText;

        return $this;
    }

    /**
     * Get introText
     *
     * @return string 
     */
    public function getIntroText()
    {
        return $this->introText;
    }
}
