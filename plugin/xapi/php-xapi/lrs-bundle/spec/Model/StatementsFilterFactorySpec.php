<?php

namespace spec\XApi\LrsBundle\Model;

use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpFoundation\ParameterBag;
use Xabbuh\XApi\DataFixtures\ActorFixtures;
use Xabbuh\XApi\DataFixtures\UuidFixtures;
use Xabbuh\XApi\Model\StatementsFilter;
use Xabbuh\XApi\Serializer\ActorSerializerInterface;
use XApi\Fixtures\Json\ActorJsonFixtures;

class StatementsFilterFactorySpec extends ObjectBehavior
{
    function let(ActorSerializerInterface $actorSerializer)
    {
        $this->beConstructedWith($actorSerializer);
    }

    function it_sets_default_filter_when_parameters_are_empty()
    {
        $filter = $this->createFromParameterBag(new ParameterBag())->getFilter();

        $filter->shouldNotHaveKey('agent');
        $filter->shouldNotHaveKey('verb');
        $filter->shouldNotHaveKey('activity');
        $filter->shouldNotHaveKey('registration');
        $filter->shouldNotHaveKey('since');
        $filter->shouldNotHaveKey('until');
        $filter->shouldHaveKeyWithValue('related_activities', 'false');
        $filter->shouldHaveKeyWithValue('related_agents', 'false');
        $filter->shouldHaveKeyWithValue('ascending', 'false');
        $filter->shouldHaveKeyWithValue('limit', 0);
    }

    function it_sets_an_agent_filter(ActorSerializerInterface $actorSerializer)
    {
        $json = ActorJsonFixtures::getTypicalAgent();
        $actor = ActorFixtures::getTypicalAgent();

        $actorSerializer->deserializeActor($json)->shouldBeCalled()->willReturn($actor);

        $this->beConstructedWith($actorSerializer);

        $parameters = new ParameterBag();
        $parameters->set('agent', $json);

        /** @var StatementsFilter $filter */
        $filter = $this->createFromParameterBag($parameters);

        $filter->getFilter()->shouldHaveKeyWithValue('agent', $actor);
    }

    function it_sets_a_verb_filter()
    {
        $verbId = 'http://tincanapi.com/conformancetest/verbid';
        $parameters = new ParameterBag();
        $parameters->set('verb', $verbId);

        /** @var StatementsFilter $filter */
        $filter = $this->createFromParameterBag($parameters);

        $filter->getFilter()->shouldHaveKeyWithValue('verb', $verbId);
    }

    function it_sets_an_activity_filter()
    {
        $activityId = 'http://tincanapi.com/conformancetest/activityid';
        $parameters = new ParameterBag();
        $parameters->set('activity', $activityId);

        /** @var StatementsFilter $filter */
        $filter = $this->createFromParameterBag($parameters);

        $filter->getFilter()->shouldHaveKeyWithValue('activity', $activityId);
    }

    function it_sets_a_registration_filter()
    {
        $registration = UuidFixtures::getGoodUuid();
        $parameters = new ParameterBag();
        $parameters->set('registration', $registration);

        /** @var StatementsFilter $filter */
        $filter = $this->createFromParameterBag($parameters);

        $filter->getFilter()->shouldHaveKeyWithValue('registration', $registration);
    }

    function it_sets_a_related_activities_filter()
    {
        $parameters = new ParameterBag();
        $parameters->set('related_activities', true);

        /** @var StatementsFilter $filter */
        $filter = $this->createFromParameterBag($parameters);

        $filter->getFilter()->shouldHaveKeyWithValue('related_activities', 'true');
    }

    function it_sets_a_related_agents_filter()
    {
        $parameters = new ParameterBag();
        $parameters->set('related_agents', true);

        /** @var StatementsFilter $filter */
        $filter = $this->createFromParameterBag($parameters);

        $filter->getFilter()->shouldHaveKeyWithValue('related_agents', 'true');
    }

    function it_sets_a_since_filter()
    {
        $now = new \DateTime();

        $parameters = new ParameterBag();
        $parameters->set('since', $now->format(\DateTime::ATOM));

        /** @var StatementsFilter $filter */
        $filter = $this->createFromParameterBag($parameters);

        $filter->getFilter()->shouldHaveKeyWithValue('since', $now->format('c'));
    }

    function it_sets_an_until_filter()
    {
        $now = new \DateTime();

        $parameters = new ParameterBag();
        $parameters->set('until', $now->format(\DateTime::ATOM));

        /** @var StatementsFilter $filter */
        $filter = $this->createFromParameterBag($parameters);

        $filter->getFilter()->shouldHaveKeyWithValue('until', $now->format('c'));
    }

    function it_sets_an_ascending_filter()
    {
        $parameters = new ParameterBag();
        $parameters->set('ascending', true);

        /** @var StatementsFilter $filter */
        $filter = $this->createFromParameterBag($parameters);

        $filter->getFilter()->shouldHaveKeyWithValue('ascending', 'true');
    }

    function it_sets_a_limit_filter()
    {
        $parameters = new ParameterBag();
        $parameters->set('limit', 10);

        /** @var StatementsFilter $filter */
        $filter = $this->createFromParameterBag($parameters);

        $filter->getFilter()->shouldHaveKeyWithValue('limit', 10);
    }
}
