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
     *
     * @return void
     */
    public function add($name, $service);

    /**
     * Return the block service linked to the link
     *
     * @param BlockInterface $block
     *
     * @return BlockServiceInterface
     */
    public function get(BlockInterface $block);

    /**
     * @param array $blockServices
     *
     * @return void
     */
    public function setServices(array $blockServices);

    /**
     * @return array
     */
    public function getServices();

    /**
     *
     * @param string $name
     *
     * @return boolean
     */
    public function has($name);

    /**
     * @param $name
     *
     * @return BlockServiceInterface
     */
    public function getService($name);

    /**
     * @return array
     */
    public function getLoadedServices();

    /**
     * @param ErrorElement   $errorElement
     * @param BlockInterface $block
     */
    public function validate(ErrorElement $errorElement, BlockInterface $block);
}
