<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackCOs
 *
 * @Table(name="track_c_os")
 * @Entity
 */
class EntityTrackCOs
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="os", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $os;

    /**
     * @var integer
     *
     * @Column(name="counter", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $counter;


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
     * Set os
     *
     * @param string $os
     * @return EntityTrackCOs
     */
    public function setOs($os)
    {
        $this->os = $os;

        return $this;
    }

    /**
     * Get os
     *
     * @return string 
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * Set counter
     *
     * @param integer $counter
     * @return EntityTrackCOs
     */
    public function setCounter($counter)
    {
        $this->counter = $counter;

        return $this;
    }

    /**
     * Get counter
     *
     * @return integer 
     */
    public function getCounter()
    {
        return $this->counter;
    }
}
