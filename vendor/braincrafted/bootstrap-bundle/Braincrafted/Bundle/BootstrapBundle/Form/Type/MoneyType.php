<?php
/**
 * This file is part of BraincraftedBootstrapBundle.
 * (c) 2012-2013 by Florian Eckerstorfer
 */

namespace Braincrafted\Bundle\BootstrapBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\MoneyType as BaseMoneyType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * MoneyType
 *
 * @package    BraincraftedBootstrapBundle
 * @subpackage Form
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright  2012-2013 Florian Eckerstorfer
 * @license    http://opensource.org/licenses/MIT The MIT License
 * @link       http://bootstrap.braincrafted.com Bootstrap for Symfony2
 */
class MoneyType extends BaseMoneyType
{
    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['money_pattern'] = self::getPattern($options['currency']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'money';
    }

    /**
     * Returns the pattern for this locale
     *
     * The pattern contains the placeholder "{{ widget }}" where the HTML tag should
     * be inserted
     *
     * @param string $currency
     *
     * @return string Returns the pattern
     */
    protected static function getPattern($currency)
    {
        if (!$currency) {
            return '{{ widget }}';
        }

        $locale = \Locale::getDefault();

        if (!isset(self::$patterns[$locale])) {
            self::$patterns[$locale] = array();
        }

        if (!isset(self::$patterns[$locale][$currency])) {
            $format = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
            $pattern = $format->formatCurrency('123', $currency);

            // the spacings between currency symbol and number are ignored, because
            // a single space leads to better readability in combination with input
            // fields
            // the regex also considers non-break spaces (0xC2 or 0xA0 in UTF-8)

            preg_match(
                '/^([^\s\xc2\xa0]*)[\s\xc2\xa0]*123(?:[,.]0+)?[\s\xc2\xa0]*([^\s\xc2\xa0]*)$/u',
                $pattern,
                $matches
            );

            self::$patterns[$locale][$currency] = self::parsePatternMatches($matches);
        }

        return self::$patterns[$locale][$currency];
    }

    /**
     * Parses the given pattern matches array and returns the pattern string.
     *
     * @param array $matches Pattern matches
     *
     * @return string Pattern
     */
    protected static function parsePatternMatches(array $matches)
    {
        if (!empty($matches[1])) {
            return '{{ tag_start }}'.$matches[1].'{{ tag_end }} {{ widget }}';
        }

        if (!empty($matches[2])) {
            return '{{ widget }} {{ tag_start }}'.$matches[2].'{{ tag_end }}';
        }

        // @codeCoverageIgnoreStart
        return '{{ widget }}';
        // @codeCoverageIgnoreEnd
    }
}
