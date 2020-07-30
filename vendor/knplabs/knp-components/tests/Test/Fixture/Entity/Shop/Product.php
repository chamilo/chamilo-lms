<?php

namespace Test\Fixture\Entity\Shop;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=64)
     */
    private $title;

    /**
     * @ORM\Column(length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="float", nullable=false)
     */
    private $price;

    /**
     * @ORM\ManyToMany(targetEntity="Tag")
     */
    private $tags;

    /**
     * @ORM\Column(type="integer")
     */
    private $numTags = 0;

    public function __construct()
    {
        $this->tags = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function addTag(Tag $tag)
    {
        $this->numTags++;
        $this->tags[] = $tag;
    }

    public function getTags()
    {
        return $this->tags;
    }
}
