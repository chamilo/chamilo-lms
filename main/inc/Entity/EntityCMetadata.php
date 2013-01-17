<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCMetadata
 *
 * @Table(name="c_metadata")
 * @Entity
 */
class EntityCMetadata
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var string
     *
     * @Column(name="eid", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $eid;

    /**
     * @var string
     *
     * @Column(name="mdxmltext", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $mdxmltext;

    /**
     * @var string
     *
     * @Column(name="md5", type="string", length=32, precision=0, scale=0, nullable=true, unique=false)
     */
    private $md5;

    /**
     * @var string
     *
     * @Column(name="htmlcache1", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $htmlcache1;

    /**
     * @var string
     *
     * @Column(name="htmlcache2", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $htmlcache2;

    /**
     * @var string
     *
     * @Column(name="indexabletext", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $indexabletext;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCMetadata
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

    /**
     * Set eid
     *
     * @param string $eid
     * @return EntityCMetadata
     */
    public function setEid($eid)
    {
        $this->eid = $eid;

        return $this;
    }

    /**
     * Get eid
     *
     * @return string 
     */
    public function getEid()
    {
        return $this->eid;
    }

    /**
     * Set mdxmltext
     *
     * @param string $mdxmltext
     * @return EntityCMetadata
     */
    public function setMdxmltext($mdxmltext)
    {
        $this->mdxmltext = $mdxmltext;

        return $this;
    }

    /**
     * Get mdxmltext
     *
     * @return string 
     */
    public function getMdxmltext()
    {
        return $this->mdxmltext;
    }

    /**
     * Set md5
     *
     * @param string $md5
     * @return EntityCMetadata
     */
    public function setMd5($md5)
    {
        $this->md5 = $md5;

        return $this;
    }

    /**
     * Get md5
     *
     * @return string 
     */
    public function getMd5()
    {
        return $this->md5;
    }

    /**
     * Set htmlcache1
     *
     * @param string $htmlcache1
     * @return EntityCMetadata
     */
    public function setHtmlcache1($htmlcache1)
    {
        $this->htmlcache1 = $htmlcache1;

        return $this;
    }

    /**
     * Get htmlcache1
     *
     * @return string 
     */
    public function getHtmlcache1()
    {
        return $this->htmlcache1;
    }

    /**
     * Set htmlcache2
     *
     * @param string $htmlcache2
     * @return EntityCMetadata
     */
    public function setHtmlcache2($htmlcache2)
    {
        $this->htmlcache2 = $htmlcache2;

        return $this;
    }

    /**
     * Get htmlcache2
     *
     * @return string 
     */
    public function getHtmlcache2()
    {
        return $this->htmlcache2;
    }

    /**
     * Set indexabletext
     *
     * @param string $indexabletext
     * @return EntityCMetadata
     */
    public function setIndexabletext($indexabletext)
    {
        $this->indexabletext = $indexabletext;

        return $this;
    }

    /**
     * Get indexabletext
     *
     * @return string 
     */
    public function getIndexabletext()
    {
        return $this->indexabletext;
    }
}
