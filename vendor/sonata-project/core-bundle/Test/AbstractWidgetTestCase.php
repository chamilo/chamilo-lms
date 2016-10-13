<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Test;

use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Bridge\Twig\Form\TwigRendererEngineInterface;
use Symfony\Bridge\Twig\Tests\Extension\Fixtures\StubFilesystemLoader;
use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTranslator;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Base class for tests checking rendering of form widgets.
 *
 * @author Christian Gripp <mail@core23.de>
 */
abstract class AbstractWidgetTestCase extends TypeTestCase
{
    /**
     * @var FormExtensionInterface
     */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        // NEXT_MAJOR: Remove BC hack when dropping symfony 2.4 support
        $csrfProviderClasses = array_filter(array(
            // symfony <=2.4
            'Symfony\Component\Security\Csrf\CsrfTokenManagerInterface',
            // symfony >=2.4
            'Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface',
        ), 'interface_exists');

        $renderer = new TwigRenderer($this->getRenderingEngine(), $this->getMock(current($csrfProviderClasses)));

        $this->extension = new FormExtension($renderer);
        $this->extension->initRuntime($this->getEnvironment());
    }

    /**
     * @return \Twig_Environment
     */
    protected function getEnvironment()
    {
        $loader = new StubFilesystemLoader($this->getTemplatePaths());

        $environment = new \Twig_Environment($loader, array(
            'strict_variables' => true,
        ));
        $environment->addExtension(new TranslationExtension(new StubTranslator()));
        $environment->addExtension($this->extension);

        return $environment;
    }

    /**
     * Returns a list of template paths.
     *
     * @return string[]
     */
    protected function getTemplatePaths()
    {
        // this is an workaround for different composer requirements and different TwigBridge installation directories
        $twigPaths = array_filter(array(
            // symfony/twig-bridge (running from this bundle)
            __DIR__.'/../vendor/symfony/twig-bridge/Resources/views/Form',
            // symfony/twig-bridge (running from other bundles)
            __DIR__.'/../../../symfony/twig-bridge/Resources/views/Form',
            // NEXT_MAJOR: Remove BC hacks when dropping symfony 2.3 support
            // symfony/twig-bridge 2.3 (running from this bundle)
            __DIR__.'/../vendor/symfony/twig-bridge/Symfony/Bridge/Twig/Resources/views/Form',
            // symfony/twig-bridge 2.3 (running from other bundles)
            __DIR__.'/../../../symfony/twig-bridge/Symfony/Bridge/Twig/Resources/views/Form',
            // symfony/symfony (running from this bundle)
            __DIR__.'/../vendor/symfony/symfony/src/Symfony/Bridge/Twig/Resources/views/Form',
            // symfony/symfony (running from other bundles)
            __DIR__.'/../../../symfony/symfony/src/Symfony/Bridge/Twig/Resources/views/Form',
        ), 'is_dir');

        $twigPaths[] = __DIR__.'/../Resources/views/Form';

        return $twigPaths;
    }

    /**
     * @return TwigRendererEngineInterface
     */
    protected function getRenderingEngine()
    {
        return new TwigRendererEngine(array(
            'form_div_layout.html.twig',
        ));
    }

    /**
     * Renders widget from FormView, in SonataAdmin context, with optional view variables $vars. Returns plain HTML.
     *
     * @param FormView $view
     * @param array    $vars
     *
     * @return string
     */
    final protected function renderWidget(FormView $view, array $vars = array())
    {
        return (string) $this->extension->renderer->searchAndRenderBlock($view, 'widget', $vars);
    }

    /**
     * Helper method to strip newline and space characters from html string to make comparing easier.
     *
     * @param string $html
     *
     * @return string
     */
    final protected function cleanHtmlWhitespace($html)
    {
        return preg_replace_callback('/\s*>([^<]+)</', function ($value) {
            return '>'.trim($value[1]).'<';
        }, $html);
    }

    /**
     * @param string $html
     *
     * @return string
     */
    final protected function cleanHtmlAttributeWhitespace($html)
    {
        return preg_replace_callback('~<([A-Z0-9]+) \K(.*?)>~i', function ($m) {
            return preg_replace('~\s*~', '', $m[0]);
        }, $html);
    }
}
