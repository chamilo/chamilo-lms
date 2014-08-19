<?php

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Bundle\SettingsBundle\Model\ParameterInterface;

/**
 * CCourseSetting
 *
 * @ORM\Table(name="c_course_setting")
 * @ORM\Entity
 */
class CCourseSetting implements ParameterInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="variable", type="string", length=255, nullable=false)
     */
    private $variable;

    /**
     * @var string
     *
     * @ORM\Column(name="subkey", type="string", length=255, nullable=true)
     */
    private $subkey;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=255, nullable=true)
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=false)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=255, nullable=true)
     */
    private $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="subkeytext", type="string", length=255, nullable=true)
     */
    private $subkeytext;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $iid;

    /**
     * Get iid
     *
     * @return integer
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CCourseDescription
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
     * Set title
     *
     * @param string $title
     * @return CCourseDescription
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
     * Set category
     *
     * @param string $category
     * @return CCourseSetting
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set category
     *
     * @param string $variable
     *
     * @return CCourseSetting
     */
    public function setVariable($variable)
    {
        $this->variable = $variable;

        return $this;
    }

    /**
     * Get variable
     *
     * @return string
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Set category
     *
     * @param string $subkey
     *
     * @return CCourseSetting
     */
    public function setSubkey($subkey)
    {
        $this->subkey = $subkey;

        return $this;
    }

    /**
     * Get subkey
     *
     * @return string
     */
    public function getSubkey()
    {
        return $this->subkey;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return CCourseSetting
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return CCourseSetting
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Set subkeytext
     *
     * @param string $subkeytext
     * @return CCourseSetting
     */
    public function setSubkeytext($subkeytext)
    {
        $this->subkeytext = $subkeytext;

        return $this;
    }

    /**
     * Get subkeytext
     *
     * @return string
     */
    public function getSubkeytext()
    {
        return $this->subkeytext;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return $this->getCategory();
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespace($namespace)
    {
        $this->setCategory($namespace);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getVariable();
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->setTitle($name);
        $this->setVariable($name);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}
