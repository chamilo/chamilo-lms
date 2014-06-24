<?php

namespace JMS\SecurityExtraBundle\Tests\Functional\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name = "posts")
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class Post
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type = "string")
     */
    private $title;

    public function __construct($title)
    {
        $this->title = $title;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
