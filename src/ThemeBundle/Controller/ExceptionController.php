<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Controller;

use Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseExceptionController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\TemplateReference;

/**
 * Class ExceptionController.
 */
class ExceptionController extends BaseExceptionController
{
    /**
     * @param string $format
     * @param int    $code
     * @param bool   $showException
     *
     * @return TemplateReference
     */
    protected function findTemplate(Request $request, $format, $code, $showException)
    {
        // Only show custom error when APP_DEBUG = 0
        if ($showException) {
            return parent::findTemplate($request, $format, $code, $showException);
        }

        $name = $showException ? 'exception' : 'error';
        if ($showException && 'html' == $format) {
            $name = 'exception_full';
        }

        // when not in debug, try to find a template for the specific HTTP status code and format
        if (!$showException) {
            $template = sprintf('@ChamiloTheme/Exception/%s%s.%s.twig', $name, $code, $format);
            if ($this->templateExists($template)) {
                return $template;
            }
        }

        // try to find a template for the given format
        $template = sprintf('@ChamiloTheme/Exception/%s.%s.twig', $name, $format);
        if ($this->templateExists($template)) {
            return $template;
        }

        // default to a generic HTML exception
        $request->setRequestFormat('html');

        return sprintf('@ChamiloTheme/Exception/%s.html.twig', $showException ? 'exception_full' : $name);
    }
}
