<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\FaqBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class CategoryTranslation.
 *
 * @ORM\Entity
 * @ORM\Table(name="faq_question_translation")
 * )
 *
 * @package Chamilo\FaqBundle\Entity
 */
class QuestionTranslation
{
    use ORMBehaviors\Translatable\Translation;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $headline;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $body;

    /**
     * @Gedmo\Slug(fields={"headline"}, updatable=false)
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    protected $slug;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->headline;
    }

    /**
     * @return string
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * @param string $headline
     *
     * @return QuestionTranslation
     */
    public function setHeadline($headline)
    {
        $this->headline = $headline;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return QuestionTranslation
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     *
     * @return QuestionTranslation
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }
}
