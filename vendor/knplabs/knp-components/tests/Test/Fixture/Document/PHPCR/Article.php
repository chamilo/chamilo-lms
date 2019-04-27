<?php

namespace Test\Fixture\Document\PHPCR;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

/**
 * @PHPCR\Document
 */
class Article
{
    /**
     * @PHPCR\Id
     */
    private $id;

    /**
     * @PHPCR\ParentDocument
     */
    private $parent;

    /**
     * @PHPCR\Field(type="string")
     */
    private $title;

    public function getId()
    {
        return $this->id;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
