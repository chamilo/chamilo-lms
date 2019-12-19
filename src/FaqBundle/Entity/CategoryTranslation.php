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
 * @ORM\Table(
 *     name="faq_category_translation",
 *     options={"row_format":"DYNAMIC"}
 * )
 * @ORM\AttributeOverrides({
 *     @ORM\AttributeOverride(name="locale",
 *         column=@ORM\Column(
 *             name="locale",
 *             type="string",
 *             length=190
 *         )
 *     )
 * })
 */
class CategoryTranslation
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
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    protected $slug;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=190, nullable=false)
     */
    protected $locale;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->headline;
    }

    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * @return CategoryTranslation
     */
    public function setHeadline($headline)
    {
        $this->headline = $headline;

        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return CategoryTranslation
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return CategoryTranslation
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }
}
