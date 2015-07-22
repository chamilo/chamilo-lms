<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GradebookScoreLog
 *
 * @ORM\Table(
 *      name="gradebook_score_log", indexes={
 *          @ORM\Index(name="idx_gradebook_score_log_user", columns={"user_id"}),
 *          @ORM\Index(name="idx_gradebook_score_log_user_category", columns={"user_id", "category_id"})
 *      }
 * )
 * @ORM\Entity
 */
class GradebookScoreLog
{

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", nullable=false)
     */
    private $categoryId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=false)
     */
    private $score;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registered_at", type="datetime", nullable=false)
     */
    private $registeredAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Get the category id
     * @return type
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Get the user id
     * @return type
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Get the achieved score
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Get the datetime of register
     * @return \DateTime
     */
    public function getRegisteredAt()
    {
        return $this->registeredAt;
    }

    /**
     * Get the id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the category id
     * @param type $categoryId
     * @return \Chamilo\CoreBundle\Entity\GradebookUserLog
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Set the user id
     * @param type $userId
     * @return \Chamilo\CoreBundle\Entity\GradebookUserLog
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Set the achieved score
     * @param type $score
     * @return \Chamilo\CoreBundle\Entity\GradebookUserLog
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Set the datetime of register
     * @param \DateTime $registeredAt
     * @return \Chamilo\CoreBundle\Entity\GradebookUserLog
     */
    public function setRegisteredAt(\DateTime $registeredAt)
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * Set the id
     * @param type $id
     * @return \Chamilo\CoreBundle\Entity\GradebookUserLog
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

}
