<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Icu;

use Symfony\Component\Intl\ResourceBundle\LanguageBundle;
use Symfony\Component\Intl\ResourceBundle\Reader\StructuredBundleReaderInterface;

/**
 * An ICU-specific implementation of {@link \Symfony\Component\Intl\ResourceBundle\LanguageBundleInterface}.
 *
 * This class normalizes the data of the ICU .res files to satisfy the contract
 * defined in {@link \Symfony\Component\Intl\ResourceBundle\LanguageBundleInterface}.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class IcuLanguageBundle extends LanguageBundle
{
    public function __construct(StructuredBundleReaderInterface $reader)
    {
        parent::__construct(realpath(IcuData::getResourceDirectory() . '/lang'), $reader);
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguageName($lang, $region = null, $locale = null)
    {
        if ('mul' === $lang) {
            return null;
        }

        return parent::getLanguageName($lang, $region, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguageNames($locale = null)
    {
        if (null === $locale) {
            $locale = \Locale::getDefault();
        }

        $languages = parent::getLanguageNames($locale);

        $collator = new \Collator($locale);
        $collator->asort($languages);

        // "mul" is the code for multiple languages
        unset($languages['mul']);

        return $languages;
    }

    /**
     * {@inheritdoc}
     */
    public function getScriptNames($locale = null)
    {
        if (null === $locale) {
            $locale = \Locale::getDefault();
        }

        $scripts = parent::getScriptNames($locale);

        $collator = new \Collator($locale);
        $collator->asort($scripts);

        return $scripts;
    }
}
