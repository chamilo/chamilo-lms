<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Block\Service;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;

class FakeTemplating implements EngineInterface
{
    public $view;

    public $parameters;

    public $response;

    public $name;

    public function render($name, array $parameters = array())
    {
        $this->name  = $name;
        $this->parameters = $parameters;
    }

    public function renderResponse($view, array $parameters = array(), Response $response = null)
    {
        $this->view   = $view;
        $this->parameters = $parameters;

        if ($response) {
            return $response;
        }

        return new Response;
    }

    public function supports($name)
    {
        return true;
    }

    public function exists($name)
    {
        return true;
    }
}
