<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CUserinfoContent
 *
 * @ORM\Table(
 *  name="c_userinfo_content",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="user_id", columns={"user_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CUserinfoContent
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="definition_id", type="integer", nullable=false)
     */
    private $definitionId;

    /**
     * @var string
     *
     * @ORM\Column(name="editor_ip", type="string", length=39, nullable=true)
     */
    private $editorIp;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="edition_time", type="datetime", nullable=true)
     */
    private $editionTime;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    private $content;

    /**
     * Set userId
     *
     * @param integer $userId
     * @return CUserinfoContent
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
     * Set definitionId
     *
     * @param integer $definitionId
     * @return CUserinfoContent
     */
    public function setDefinitionId($definitionId)
    {
        $this->definitionId = $definitionId;

        return $this;
    }

    /**
     * Get definitionId
     *
     * @return integer
     */
    public function getDefinitionId()
    {
        return $this->definitionId;
    }

    /**
     * Set editorIp
     *
     * @param string $editorIp
     * @return CUserinfoContent
     */
    public function setEditorIp($editorIp)
    {
        $this->editorIp = $editorIp;

        return $this;
    }

    /**
     * Get editorIp
     *
     * @return string
     */
    public function getEditorIp()
    {
        return $this->editorIp;
    }

    /**
     * Set editionTime
     *
     * @param \DateTime $editionTime
     * @return CUserinfoContent
     */
    public function setEditionTime($editionTime)
    {
        $this->editionTime = $editionTime;

        return $this;
    }

    /**
     * Get editionTime
     *
     * @return \DateTime
     */
    public function getEditionTime()
    {
        return $this->editionTime;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return CUserinfoContent
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
     * Set id
     *
     * @param integer $id
     * @return CUserinfoContent
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
     * Set cId
     *
     * @param integer $cId
     * @return CUserinfoContent
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
}
