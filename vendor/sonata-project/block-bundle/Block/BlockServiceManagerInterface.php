<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Block;

use Sonata\AdminBundle\Validator\ErrorElement;
use Sonata\BlockBundle\Model\BlockInterface;

interface BlockServiceManagerInterface
{
    /**
     * @param string $name
     * @param string $service
     * @param array  $contexts
     */
    public function add($name, $service, $contexts = array());

    /**
     * Return the block service linked to the link.
     *
     * @param BlockInterface $block
     *
     * @return BlockServiceInterface
     */
    public function get(BlockInterface $block);

    /**
     * @deprecated will be remove in 2.4, use the add method instead
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
     * @param $name
     *
     * @return BlockServiceInterface
     */
    public function getService($name);

    /**
     * @deprecated will be remove in 2.4
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
