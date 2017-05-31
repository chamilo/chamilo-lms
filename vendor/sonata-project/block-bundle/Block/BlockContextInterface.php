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

interface BlockContextInterface
{
    /**
     * @return BlockInterface
     */
    public function getBlock();

    /**
     * @return array
     */
    public function getSettings();

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getSetting($name);

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return BlockContextInterface
     */
    public function setSetting($name, $value);

    /**
     * @return string
     */
    public function getTemplate();
}
