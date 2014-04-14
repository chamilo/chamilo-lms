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

use Symfony\Component\Intl\ResourceBundle\CurrencyBundle;
use Symfony\Component\Intl\ResourceBundle\Reader\StructuredBundleReaderInterface;

/**
 * An ICU-specific implementation of {@link \Symfony\Component\Intl\ResourceBundle\CurrencyBundleInterface}.
 *
 * This class normalizes the data of the ICU .res files to satisfy the contract
 * defined in {@link \Symfony\Component\Intl\ResourceBundle\CurrencyBundleInterface}.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class IcuCurrencyBundle extends CurrencyBundle
{
    const INDEX_SYMBOL = 0;

    const INDEX_NAME = 1;

    const INDEX_FRACTION_DIGITS = 0;

    const INDEX_ROUNDING_INCREMENT = 1;

    public function __construct(StructuredBundleReaderInterface $reader)
    {
        parent::__construct(realpath(IcuData::getResourceDirectory() . '/curr'), $reader);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocales()
    {
        return $this->readEntry('misc', array('Locales'));
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrencyNames($locale = null)
    {
        if (null === $locale) {
            $locale = \Locale::getDefault();
        }

        $names = parent::getCurrencyNames($locale);

        $collator = new \Collator($locale);
        $collator->asort($names);

        return $names;
    }

    /**
     * {@inheritdoc}
     */
    public function getFractionDigits($currency)
    {
        $entry = $this->readEntry('misc', array('CurrencyMeta'));

        if (!isset($entry[$currency][self::INDEX_FRACTION_DIGITS])) {
            // The 'DEFAULT' key contains the fraction digits and the rounding
            // increment that are common for a lot of currencies.
            // Only currencies with different values are added to the icu-data
            // (e.g: CHF and JPY)
            return $entry['DEFAULT'][self::INDEX_FRACTION_DIGITS];
        }

        return $entry[$currency][self::INDEX_FRACTION_DIGITS];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoundingIncrement($currency)
    {
        $entry = $this->readEntry('misc', array('CurrencyMeta'));

        if (!isset($entry[$currency][self::INDEX_ROUNDING_INCREMENT])) {
            // The 'DEFAULT' key contains the fraction digits and the rounding
            // increment that are common for a lot of currencies.
            // Only currencies with different values are added to the icu-data
            // (e.g: CHF and JPY)
            return $entry['DEFAULT'][self::INDEX_ROUNDING_INCREMENT];
        }

        return $entry[$currency][self::INDEX_ROUNDING_INCREMENT];
    }
}
