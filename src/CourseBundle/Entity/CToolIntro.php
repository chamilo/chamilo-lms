<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CToolIntro.
 *
 * @ORM\Table(
 *     name="c_tool_intro",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CToolIntro
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="intro_text", type="text", nullable=false)
     */
    protected string $introText;

    /**
     * @ORM\Column(name="session_id", type="integer")
     */
    protected int $sessionId;

    /**
     * Set introText.
     *
     * @return CToolIntro
     */
    public function setIntroText(string $introText)
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
     * Set cId.
     *
     * @return CToolIntro
     */
    public function setCId(int $cId)
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
     * @return CToolIntro
     */
    public function setSessionId(int $sessionId)
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
}
