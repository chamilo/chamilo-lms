<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackCBrowsers
 *
 * @Table(name="track_c_browsers")
 * @Entity
 */
class EntityTrackCBrowsers
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
     * @Column(name="browser", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $browser;

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
     * Set browser
     *
     * @param string $browser
     * @return EntityTrackCBrowsers
     */
    public function setBrowser($browser)
    {
        $this->browser = $browser;

        return $this;
    }

    /**
     * Get browser
     *
     * @return string 
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * Set counter
     *
     * @param integer $counter
     * @return EntityTrackCBrowsers
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
