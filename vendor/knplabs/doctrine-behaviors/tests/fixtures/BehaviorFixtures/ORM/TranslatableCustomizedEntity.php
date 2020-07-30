<?php

namespace BehaviorFixtures\ORM;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 * Used to test translation classes which declare custom translatable classes.
 */
class TranslatableCustomizedEntity
{
    use Model\Translatable\Translatable;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * {@inheritdoc}
     */
    public static function getTranslationEntityClass()
    {
        return '\BehaviorFixtures\ORM\Translation\TranslatableCustomizedEntityTranslation';
    }

    public function __call($method, $arguments)
    {
        return $this->proxyCurrentLocaleTranslation($method, $arguments);
    }

    /**
     * Returns object id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
