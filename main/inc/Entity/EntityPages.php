<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * EntityPages
 *
 * @Table(name="pages")
 * @Entity(repositoryClass="Entity\Repository\PagesRepository")
 * @HasLifecycleCallbacks()
 * @GRID\Source(columns="id, title")
 */
class EntityPages
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
     * @Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     * @Gedmo\Slug(fields={"title"})
     * @Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @var string
     *
     * @Column(name="content", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $content;


    /**
     * @var \DateTime
     * @Column(type="datetime")
     */
    private $created;

    /**
     * @var \DateTime
     * @Column(type="datetime")
     */
    private $updated;

    public function __construct()
    {
        $this->setCreated();
        $this->setUpdated();
    }

    /**
    * @preUpdate
    */
    public function setUpdated()
    {
        $this->updated = new \DateTime();
    }

    public function setCreated()
    {
        $this->created = new \DateTime();
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
     * Set title
     *
     * @param string $title
     * @return EntityPages
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

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return EntityPages
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
}
