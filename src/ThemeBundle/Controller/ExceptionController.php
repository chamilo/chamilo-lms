<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseExceptionController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ExceptionController.
 *
 * @package Chamilo\ThemeBundle\Controller
 */
class ExceptionController extends BaseExceptionController
{
    /**
     * @param Request $request
     * @param string  $format
     * @param int     $code
     * @param bool    $debug
     *
     * @return TemplateReference
     */
    protected function findTemplate(Request $request, $format, $code, $debug)
    {
        // Only show custom error when APP_DEBUG = 0
        if ($debug) {
            return parent::findTemplate($request, $format, $code, $debug);
        }

        /*if (strpos($request->getPathInfo(), '/admin') !== 0) {
            return parent::findTemplate($request, $format, $code, $debug);
        }*/

        $name = $debug ? 'exception' : 'error';
        if ($debug && 'html' == $format) {
            $name = 'exception_full';
        }

        // when not in debug, try to find a template for the specific HTTP status code and format
        if (!$debug) {
            $template = new TemplateReference('ChamiloThemeBundle', 'Exception', $name.$code, $format, 'twig');
            if ($this->templateExists($template)) {
                return $template;
            }
        }

        // try to find a template for the given format
        $template = new TemplateReference('ChamiloThemeBundle', 'Exception', $name, $format, 'twig');
        if ($this->templateExists($template)) {
            return $template;
        }

        // default to a generic HTML exception
        $request->setRequestFormat('html');

        $template = new TemplateReference('ChamiloThemeBundle', 'Exception', $name, 'html', 'twig');
        if ($this->templateExists($template)) {
            return $template;
        }

        return parent::findTemplate($request, $format, $code, $debug);
    }
}
