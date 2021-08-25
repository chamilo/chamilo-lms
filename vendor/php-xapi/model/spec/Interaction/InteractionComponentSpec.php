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

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\Interaction\InteractionComponent;
use Xabbuh\XApi\Model\LanguageMap;

class InteractionComponentSpec extends ObjectBehavior
{
    function its_properties_can_be_read()
    {
        $description = LanguageMap::create(array('en-US' => 'test'));
        $this->beConstructedWith('test', $description);

        $this->getId()->shouldReturn('test');
        $this->getDescription()->shouldReturn($description);
    }

    function it_is_not_equal_with_other_interaction_component_if_ids_differ()
    {
        $description = LanguageMap::create(array('en-US' => 'test'));
        $this->beConstructedWith('test', $description);

        $interactionComponent = new InteractionComponent('Test', $description);

        $this->equals($interactionComponent)->shouldReturn(false);
    }

    function it_is_not_equal_with_other_interaction_component_if_descriptions_differ()
    {
        $this->beConstructedWith('test', LanguageMap::create(array('en-US' => 'test')));

        $interactionComponent = new InteractionComponent('test', LanguageMap::create(array('en-GB' => 'test')));

        $this->equals($interactionComponent)->shouldReturn(false);
    }

    function it_is_not_equal_with_other_interaction_component_if_other_interaction_component_does_not_have_a_description()
    {
        $this->beConstructedWith('test', LanguageMap::create(array('en-US' => 'test')));

        $interactionComponent = new InteractionComponent('test');

        $this->equals($interactionComponent)->shouldReturn(false);
    }

    function it_is_not_equal_with_other_interaction_component_if_only_the_other_interaction_component_does_have_a_description()
    {
        $this->beConstructedWith('test');

        $interactionComponent = new InteractionComponent('test', LanguageMap::create(array('en-US' => 'test')));

        $this->equals($interactionComponent)->shouldReturn(false);
    }

    function it_is_equal_with_other_interaction_component_if_ids_and_descriptions_are_equal()
    {
        $this->beConstructedWith('test', LanguageMap::create(array('en-US' => 'test')));

        $interactionComponent = new InteractionComponent('test', LanguageMap::create(array('en-US' => 'test')));

        $this->equals($interactionComponent)->shouldReturn(true);
    }

    function it_is_equal_with_other_interaction_component_if_ids_are_equal_and_descriptions_are_not_present()
    {
        $this->beConstructedWith('test');

        $this->equals(new InteractionComponent('test'))->shouldReturn(true);
    }
}
