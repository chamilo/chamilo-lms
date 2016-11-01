<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Command;

use Sonata\BlockBundle\Block\BlockServiceManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class BaseCommand extends ContainerAwareCommand
{
    /**
     * @return BlockServiceManagerInterface
     */
    public function getBlockServiceManager()
    {
        return $this->getContainer()->get('sonata.block.manager');
    }
}
