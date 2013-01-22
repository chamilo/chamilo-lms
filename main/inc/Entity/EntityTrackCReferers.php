<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackCReferers
 *
 * @Table(name="track_c_referers")
 * @Entity
 */
class EntityTrackCReferers
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
     * @Column(name="referer", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $referer;

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
     * Set referer
     *
     * @param string $referer
     * @return EntityTrackCReferers
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * Get referer
     *
     * @return string 
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * Set counter
     *
     * @param integer $counter
     * @return EntityTrackCReferers
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
