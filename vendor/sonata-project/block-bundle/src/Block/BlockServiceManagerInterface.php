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

use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\CoreBundle\Validator\ErrorElement;

interface BlockServiceManagerInterface
{
    /**
     * @param string $name
     * @param string $service
     * @param array  $contexts
     */
    public function add($name, $service, $contexts = []);

    /**
     * Return the block service linked to the link.
     *
     * @param BlockInterface $block
     *
     * @return BlockServiceInterface
     */
    public function get(BlockInterface $block);

    /**
     * NEXT_MAJOR: remove this method.
     *
     * @deprecated will be removed in 2.4, use the add method instead
     *
     * @param array $blockServices
     */
    public function setServices(array $blockServices);

    /**
     * @return array
     */
    public function getServices();

    /**
     * @param string $name
     * @param bool   $includeContainers
     *
     * @return array
     */
    public function getServicesByContext($name, $includeContainers = true);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * @param string $name
     *
     * @return BlockServiceInterface
     */
    public function getService($name);

    /**
     * NEXT_MAJOR: remove this method.
     *
     * @deprecated will be removed in 2.4
     *
     * @return array
     */
    public function getLoadedServices();

    /**
     * @param ErrorElement   $errorElement
     * @param BlockInterface $block
     */
    public function validate(ErrorElement $errorElement, BlockInterface $block);
}
