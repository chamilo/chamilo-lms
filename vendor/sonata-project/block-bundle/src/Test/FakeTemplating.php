<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Test;

use Sonata\BlockBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mocking class for template usage.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class FakeTemplating implements EngineInterface
{
    /**
     * @var string
     */
    public $view;

    /**
     * @var array
     */
    public $parameters;

    /**
     * @var Response|null
     */
    public $response;

    /**
     * @var string
     */
    public $name;

    /**
     * {@inheritdoc}
     */
    public function render($name, array $parameters = [])
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function renderResponse($view, array $parameters = [], Response $response = null)
    {
        $this->view = $view;
        $this->parameters = $parameters;
        $this->response = $response;

        if ($response) {
            return $response;
        }

        return new Response();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($name)
    {
        return true;
    }
}
