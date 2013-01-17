<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackCProviders
 *
 * @Table(name="track_c_providers")
 * @Entity
 */
class EntityTrackCProviders
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
     * @Column(name="provider", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $provider;

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
     * Set provider
     *
     * @param string $provider
     * @return EntityTrackCProviders
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Get provider
     *
     * @return string 
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Set counter
     *
     * @param integer $counter
     * @return EntityTrackCProviders
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
