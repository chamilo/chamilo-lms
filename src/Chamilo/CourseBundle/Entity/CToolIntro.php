<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CToolIntro.
 *
 * @ORM\Table(
 *  name="c_tool_intro",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CToolIntro
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="string", nullable=false)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="intro_text", type="text", nullable=false)
     */
    protected $introText;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer")
     */
    protected $sessionId;

    /**
     * Set introText.
     *
     * @param string $introText
     *
     * @return CToolIntro
     */
    public function setIntroText($introText)
    {
        $this->introText = $introText;

        return $this;
    }

    /**
     * Get introText.
     *
     * @return string
     */
    public function getIntroText()
    {
        return $this->introText;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CToolIntro
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CToolIntro
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CToolIntro
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Get iid.
     *
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }
}
