<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityMigrationTransactionStatus
 *
 * @Table(name="migration_transaction_status")
 * @Entity
 */
class EntityMigrationTransactionStatus
{
    /**
     * @var boolean
     *
     * @Column(name="id", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $title;


    /**
     * Get id
     *
     * @return boolean 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return EntityMigrationTransactionStatus
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }
}
