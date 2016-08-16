<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Block;

use Symfony\Component\HttpFoundation\Response;

interface BlockRendererInterface
{
    /**
     * @param BlockContextInterface $name
     * @param null|Response         $response
     *
     * @return Response
     */
    public function render(BlockContextInterface $name, Response $response = null);
}
