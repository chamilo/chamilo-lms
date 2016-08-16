<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Block\Service;

use Sonata\BlockBundle\Block\BlockContext;
use Sonata\BlockBundle\Block\Service\TextBlockService;
use Sonata\BlockBundle\Model\Block;
use Sonata\BlockBundle\Test\AbstractBlockServiceTestCase;
use Sonata\BlockBundle\Util\OptionsResolver;

class TextBlockServiceTest extends AbstractBlockServiceTestCase
{
    public function testService()
    {
        $service = new TextBlockService('sonata.page.block.text', $this->templating);

        $block = new Block();
        $block->setType('core.text');
        $block->setSettings(array(
            'content' => 'my text',
        ));

        $optionResolver = new OptionsResolver();
        $service->setDefaultSettings($optionResolver);

        $blockContext = new BlockContext($block, $optionResolver->resolve($block->getSettings()));

        $formMapper = $this->getMock('Sonata\\AdminBundle\\Form\\FormMapper', array(), array(), '', false);
        $formMapper->expects($this->exactly(2))->method('add');

        $service->buildCreateForm($formMapper, $block);
        $service->buildEditForm($formMapper, $block);

        $response = $service->execute($blockContext);

        $this->assertEquals('my text', $this->templating->parameters['settings']['content']);
    }
}
