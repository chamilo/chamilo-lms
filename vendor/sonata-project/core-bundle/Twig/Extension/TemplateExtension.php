<?php

/*
 * This file is part of sonata-project.
 *
 * (c) 2010 Thomas Rabaix
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Twig\Extension;

use Sonata\CoreBundle\Model\Adapter\AdapterInterface;
use Sonata\CoreBundle\Twig\TokenParser\TemplateBoxTokenParser;
use Symfony\Component\Translation\TranslatorInterface;

class TemplateExtension extends \Twig_Extension
{
    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var AdapterInterface
     */
    protected $modelAdapter;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Constructor
     *
     * @param bool                $debug        Is Symfony debug enabled?
     * @param TranslatorInterface $translator   Symfony Translator service
     * @param AdapterInterface    $modelAdapter A Sonata model adapter
     */
    public function __construct($debug, TranslatorInterface $translator, AdapterInterface $modelAdapter)
    {
        $this->debug        = $debug;
        $this->translator   = $translator;
        $this->modelAdapter = $modelAdapter;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return array(
            'sonata_slugify'    => new \Twig_Filter_Method($this, 'slugify'),
            'sonata_urlsafeid'  => new \Twig_Filter_Method($this, 'getUrlsafeIdentifier'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return array(
            new TemplateBoxTokenParser($this->debug, $this->translator),
        );
    }

    /**
     * Slugify a text
     *
     * @param $text
     *
     * @return string
     */
    public function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        if (function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        return $text;
    }

    /**
     * @param $model
     *
     * @return string
     */
    public function getUrlsafeIdentifier($model)
    {
        return $this->modelAdapter->getUrlsafeIdentifier($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sonata_core_template';
    }
}
