<?php

namespace spec\Xabbuh\XApi\Model;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\Extensions;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;

class ExtensionsSpec extends ObjectBehavior
{
    function let()
    {
        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/topic'), 'Conformance Testing');
        $this->beConstructedWith($extensions);
    }

    function its_extensions_can_be_read()
    {
        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/topic'), 'Conformance Testing');
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/color'), array(
            'model' => 'RGB',
            'value' => '#FFFFFF',
        ));
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/starting-position'), 1);
        $this->beConstructedWith($extensions);

        $this->offsetExists(IRI::fromString('http://id.tincanapi.com/extension/topic'))->shouldReturn(true);
        $this->offsetGet(IRI::fromString('http://id.tincanapi.com/extension/topic'))->shouldReturn('Conformance Testing');

        $this->offsetExists(IRI::fromString('http://id.tincanapi.com/extension/color'))->shouldReturn(true);
        $this->offsetGet(IRI::fromString('http://id.tincanapi.com/extension/color'))->shouldReturn(array(
            'model' => 'RGB',
            'value' => '#FFFFFF',
        ));

        $this->offsetExists(IRI::fromString('http://id.tincanapi.com/extension/starting-position'))->shouldReturn(true);
        $this->offsetGet(IRI::fromString('http://id.tincanapi.com/extension/starting-position'))->shouldReturn(1);

        $returnedExtensions = $this->getExtensions();
        $returnedExtensions->shouldBeAnInstanceOf('\SplObjectStorage');
        $returnedExtensions->count()->shouldReturn(3);
    }

    function it_throws_exception_when_keys_are_passed_that_are_not_iri_instances_during_instantiation()
    {
        $extensions = new \SplObjectStorage();
        $extensions->attach(IRL::fromString('http://id.tincanapi.com/extension/topic'), 'Conformance Testing');
        $this->beConstructedWith($extensions);
        $this->shouldThrow('\InvalidArgumentException')->duringInstantiation();
    }

    function it_throws_exception_when_keys_are_passed_that_are_not_iri_instances()
    {
        $this->shouldThrow('\InvalidArgumentException')->during('offsetExists', array('http://id.tincanapi.com/extension/topic'));
        $this->shouldThrow('\InvalidArgumentException')->during('offsetGet', array('http://id.tincanapi.com/extension/topic'));
    }

    function it_throws_exception_when_not_existing_extension_is_being_read()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringOffsetGet(IRI::fromString('z'));
    }

    function its_extensions_cannot_be_manipulated()
    {
        $this->shouldThrow('\Xabbuh\XApi\Common\Exception\UnsupportedOperationException')->duringOffsetSet(IRI::fromString('z'), 'baz');
        $this->shouldThrow('\Xabbuh\XApi\Common\Exception\UnsupportedOperationException')->duringOffsetUnset(IRI::fromString('x'));
    }

    function its_not_equal_to_other_extensions_with_a_different_number_of_entries()
    {
        $this->equals(new Extensions())->shouldReturn(false);

        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/topic'), 'Conformance Testing');
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/starting-position'), 1);
        $this->equals(new Extensions($extensions))->shouldReturn(false);
    }

    function its_not_equal_to_other_extensions_if_extension_keys_differ()
    {
        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/subject'), 'Conformance Testing');

        $this->equals(new Extensions($extensions))->shouldReturn(false);
    }

    function its_not_equal_to_other_extensions_if_extension_values_differ()
    {
        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/topic'), 'Conformance Tests');

        $this->equals(new Extensions($extensions))->shouldReturn(false);
    }

    function its_equal_to_other_extensions_even_if_extension_names_are_in_different_order()
    {
        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/topic'), 'Conformance Testing');
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/color'), array(
            'model' => 'RGB',
            'value' => '#FFFFFF',
        ));
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/starting-position'), 1);

        $this->beConstructedWith($extensions);

        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/starting-position'), 1);
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/color'), array(
            'model' => 'RGB',
            'value' => '#FFFFFF',
        ));
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/topic'), 'Conformance Testing');

        $this->equals(new Extensions($extensions))->shouldReturn(true);
    }
}
