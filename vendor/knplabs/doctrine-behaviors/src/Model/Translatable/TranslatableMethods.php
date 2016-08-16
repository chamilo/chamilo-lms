<?php

/*
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\DoctrineBehaviors\Model\Translatable;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Translatable trait.
 *
 * Should be used inside entity, that needs to be translated.
 */
trait TranslatableMethods
{
    /**
     * Returns collection of translations.
     *
     * @return ArrayCollection
     */
    public function getTranslations()
    {
        return $this->translations = $this->translations ?: new ArrayCollection();
    }

    /**
     * Returns collection of new translations.
     *
     * @return ArrayCollection
     */
    public function getNewTranslations()
    {
        return $this->newTranslations = $this->newTranslations ?: new ArrayCollection();
    }

    /**
     * Adds new translation.
     *
     * @param Translation $translation The translation
     *
     * @return $this
     */
    public function addTranslation($translation)
    {
        $this->getTranslations()->set((string)$translation->getLocale(), $translation);
        $translation->setTranslatable($this);

        return $this;
    }

    /**
     * Removes specific translation.
     *
     * @param Translation $translation The translation
     */
    public function removeTranslation($translation)
    {
        $this->getTranslations()->removeElement($translation);
    }

    /**
     * Returns translation for specific locale (creates new one if doesn't exists).
     * If requested translation doesn't exist, it will first try to fallback default locale
     * If any translation doesn't exist, it will be added to newTranslations collection.
     * In order to persist new translations, call mergeNewTranslations method, before flush
     *
     * @param string $locale The locale (en, ru, fr) | null If null, will try with current locale
     * @param bool $fallbackToDefault Whether fallback to default locale
     *
     * @return Translation
     */
    public function translate($locale = null, $fallbackToDefault = true)
    {
        return $this->doTranslate($locale, $fallbackToDefault);
    }

    /**
     * Returns translation for specific locale (creates new one if doesn't exists).
     * If requested translation doesn't exist, it will first try to fallback default locale
     * If any translation doesn't exist, it will be added to newTranslations collection.
     * In order to persist new translations, call mergeNewTranslations method, before flush
     *
     * @param string $locale The locale (en, ru, fr) | null If null, will try with current locale
     * @param bool $fallbackToDefault Whether fallback to default locale
     *
     * @return Translation
     */
    protected function doTranslate($locale = null, $fallbackToDefault = true)
    {
        if (null === $locale) {
            $locale = $this->getCurrentLocale();
        }

        $translation = $this->findTranslationByLocale($locale);
        if ($translation and !$translation->isEmpty()) {
            return $translation;
        }

        if ($fallbackToDefault) {
            if (($fallbackLocale = $this->computeFallbackLocale($locale))
                && ($translation = $this->findTranslationByLocale($fallbackLocale))) {
                return $translation;
            }

            if ($defaultTranslation = $this->findTranslationByLocale($this->getDefaultLocale(), false)) {
                return $defaultTranslation;
            }
        }

        $class       = static::getTranslationEntityClass();
        $translation = new $class();
        $translation->setLocale($locale);

        $this->getNewTranslations()->set((string)$translation->getLocale(), $translation);
        $translation->setTranslatable($this);

        return $translation;
    }

    /**
     * Merges newly created translations into persisted translations.
     */
    public function mergeNewTranslations()
    {
        foreach ($this->getNewTranslations() as $newTranslation) {
            if (!$this->getTranslations()->contains($newTranslation)) {
                $this->addTranslation($newTranslation);
                $this->getNewTranslations()->removeElement($newTranslation);
            }
        }
    }

    /**
     * @param mixed $locale the current locale
     */
    public function setCurrentLocale($locale)
    {
        $this->currentLocale = $locale;
    }

    /**
     * @return Returns the current locale
     */
    public function getCurrentLocale()
    {
        return $this->currentLocale ?: $this->getDefaultLocale();
    }

    /**
     * @param mixed $locale the default locale
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
    }

    /**
     * @return Returns the default locale
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * An extra feature allows you to proxy translated fields of a translatable entity.
     * 
     * @param string $method
     * @param array $arguments
     * 
     * @return mixed The translated value of the field for current locale
     */
    protected function proxyCurrentLocaleTranslation($method, array $arguments = [])
    {
        return call_user_func_array(
            [$this->translate($this->getCurrentLocale()), $method],
            $arguments
        );
    }

    /**
     * Returns translation entity class name.
     *
     * @return string
     */
    public static function getTranslationEntityClass()
    {
        return __CLASS__.'Translation';
    }

    /**
     * Finds specific translation in collection by its locale.
     *
     * @param string $locale              The locale (en, ru, fr)
     * @param bool   $withNewTranslations searched in new translations too
     *
     * @return Translation|null
     */
    protected function findTranslationByLocale($locale, $withNewTranslations = true)
    {
        $translation = $this->getTranslations()->get($locale);

        if ($translation) {
            return $translation;
        }

        if ($withNewTranslations) {
            return $this->getNewTranslations()->get($locale);
        }
    }

    protected function computeFallbackLocale($locale)
    {
        if (strrchr($locale, '_') !== false) {
            return substr($locale, 0, -strlen(strrchr($locale, '_')));
        }

        return false;
    }
}
