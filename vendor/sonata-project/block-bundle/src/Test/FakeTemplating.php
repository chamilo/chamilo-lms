<?php

declare(strict_types=1);

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

@trigger_error(
    'The '.__NAMESPACE__.'\FakeTemplating class is deprecated since 3.17 '.
    'and will be removed in version 4.0.',
    E_USER_DEPRECATED
);

/**
 * Mocking class for template usage.
 *
 * NEXT_MAJOR: Remove this class.
 *
 * @deprecated since sonata-project/block-bundle 3.17, will be removed in version 4.0.
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

    public function render($name, array $parameters = [])
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }

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

    public function supports($name)
    {
        return true;
    }

    public function exists($name)
    {
        return true;
    }
}
