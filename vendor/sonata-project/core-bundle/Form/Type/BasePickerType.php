<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Form\Type;

use Sonata\CoreBundle\Date\MomentFormatConverter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class BasePickerType (to factorize DatePickerType and DateTimePickerType code.
 *
 *
 * @author Hugo Briand <briand@ekino.com>
 */
abstract class BasePickerType extends AbstractType
{
    /**
     * @var MomentFormatConverter
     */
    private $formatConverter;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @param MomentFormatConverter $formatConverter
     * @param TranslatorInterface   $translator
     */
    public function __construct(MomentFormatConverter $formatConverter, TranslatorInterface $translator = null)
    {
        $this->formatConverter = $formatConverter;
        $this->translator = $translator;

        if (null !== $this->translator) {
            $this->locale = $this->translator->getLocale();
        } else {
            @trigger_error('Initializing '.__CLASS__.' without TranslatorInterface is deprecated since 2.4 and will be remove in 3.0.', E_USER_DEPRECATED);
            $this->locale = \Locale::getDefault();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $format = $options['format'];

        if (isset($options['date_format']) && is_string($options['date_format'])) {
            $format = $options['date_format'];
        } elseif (is_int($format)) {
            $timeFormat = ($options['dp_pick_time']) ? DateTimeType::DEFAULT_TIME_FORMAT : \IntlDateFormatter::NONE;
            $intlDateFormatter = new \IntlDateFormatter(\Locale::getDefault(), $format, $timeFormat, null, \IntlDateFormatter::GREGORIAN, null);
            $format = $intlDateFormatter->getPattern();
        }

        // use seconds if it's allowe in format
        $options['dp_use_seconds'] = strpos($format, 's') !== false;

        $view->vars['moment_format'] = $this->formatConverter->convert($format);

        $view->vars['type'] = 'text';

        $dpOptions = array();
        foreach ($options as $key => $value) {
            if (false !== strpos($key, 'dp_')) {
                // We remove 'dp_' and camelize the options names
                $dpKey = substr($key, 3);
                $dpKey = preg_replace_callback('/_([a-z])/', function ($c) {
                    return strtoupper($c[1]);
                }, $dpKey);

                $dpOptions[$dpKey] = $value;
            }
        }

        $view->vars['datepicker_use_button'] = empty($options['datepicker_use_button']) ? false : true;
        $view->vars['dp_options'] = $dpOptions;
    }

    /**
     * Gets base default options for the date pickers.
     *
     * @return array
     */
    protected function getCommonDefaults()
    {
        return array(
            'widget'                   => 'single_text',
            'datepicker_use_button'    => true,
            'dp_pick_time'             => true,
            'dp_use_current'           => true,
            'dp_min_date'              => '1/1/1900',
            'dp_max_date'              => null,
            'dp_show_today'            => true,
            'dp_language'              => $this->locale,
            'dp_default_date'          => '',
            'dp_disabled_dates'        => array(),
            'dp_enabled_dates'         => array(),
            'dp_icons'                 => array(
                'time' => 'fa fa-clock-o',
                'date' => 'fa fa-calendar',
                'up'   => 'fa fa-chevron-up',
                'down' => 'fa fa-chevron-down',
            ),
            'dp_use_strict'            => false,
            'dp_side_by_side'          => false,
            'dp_days_of_week_disabled' => array(),
        );
    }
}
