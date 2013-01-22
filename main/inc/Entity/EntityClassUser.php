<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityClassUser
 *
 * @Table(name="class_user")
 * @Entity
 */
class EntityClassUser
{
    /**
     * @var integer
     *
     * @Column(name="class_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $classId;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $userId;


    /**
     * Set classId
     *
     * @param integer $classId
     * @return EntityClassUser
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;

        return $this;
    }

    /**
     * Get classId
     *
     * @return integer 
     */
    public function getClassId()
    {
        return $this->classId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return EntityClassUser
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
}
