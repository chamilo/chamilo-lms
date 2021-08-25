<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Xabbuh\XApi\Model\Interaction;

use Xabbuh\XApi\Model\Interaction\InteractionComponent;
use Xabbuh\XApi\Model\Interaction\LikertInteractionDefinition;

class LikertInteractionDefinitionSpec extends InteractionDefinitionSpec
{
    public function it_returns_a_new_instance_with_scale()
    {
        $scale = array(new InteractionComponent('test'));
        $interaction = $this->withScale($scale);

        $this->getScale()->shouldBeNull();

        $interaction->shouldNotBe($this);
        $interaction->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Interaction\LikertInteractionDefinition');
        $interaction->getScale()->shouldReturn($scale);
    }

    function it_is_not_equal_if_only_other_interaction_has_scale()
    {
        $interaction = $this->createEmptyDefinition();
        $interaction = $interaction->withScale(array(new InteractionComponent('test')));

        $this->equals($interaction)->shouldReturn(false);
    }

    function it_is_not_equal_if_only_this_interaction_has_scale()
    {
        $this->beConstructedWith(null, null, null, null, null, null, array(new InteractionComponent('test')));

        $this->equals($this->createEmptyDefinition())->shouldReturn(false);
    }

    function it_is_not_equal_if_number_of_scale_differs()
    {
        $this->beConstructedWith(null, null, null, null, null, null, array(new InteractionComponent('test')));

        $interaction = $this->createEmptyDefinition();
        $interaction = $interaction->withScale(array(new InteractionComponent('test'), new InteractionComponent('foo')));

        $this->equals($interaction)->shouldReturn(false);
    }

    function it_is_not_equal_if_scale_differ()
    {
        $this->beConstructedWith(null, null, null, null, null, null, array(new InteractionComponent('foo')));

        $interaction = $this->createEmptyDefinition();
        $interaction = $interaction->withScale(array(new InteractionComponent('bar')));

        $this->equals($interaction)->shouldReturn(false);
    }

    function it_is_equal_if_scales_are_equal()
    {
        $this->beConstructedWith(null, null, null, null, null, null, array(new InteractionComponent('test')));

        $interaction = $this->createEmptyDefinition();
        $interaction = $interaction->withScale(array(new InteractionComponent('test')));

        $this->equals($interaction)->shouldReturn(true);
    }

    protected function createEmptyDefinition()
    {
        return new LikertInteractionDefinition();
    }
}
