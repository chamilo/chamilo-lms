<?php

namespace spec\Xabbuh\XApi\Model;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\Context;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\Result;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\Verb;

class StatementFactorySpec extends ObjectBehavior
{
    function it_creates_a_statement()
    {
        $this->withActor(new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com'))));
        $this->withVerb(new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid')));
        $this->withObject(new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid')));

        $this->createStatement()->shouldBeAnInstanceOf('\Xabbuh\Xapi\Model\Statement');
    }

    function it_configures_all_statement_properties()
    {
        $id = StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af');
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'));
        $object = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $result = new Result();
        $context = new Context();
        $created = new \DateTime('2014-07-23T12:34:02-05:00');
        $stored = new \DateTime('2014-07-24T12:34:02-05:00');
        $authority = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));

        $this->withId($id);
        $this->withActor($actor);
        $this->withVerb($verb);
        $this->withObject($object);
        $this->withResult($result);
        $this->withContext($context);
        $this->withCreated($created);
        $this->withStored($stored);
        $this->withAuthority($authority);

        $statement = $this->createStatement();

        $statement->getId()->shouldBe($id);
        $statement->getActor()->shouldBe($actor);
        $statement->getVerb()->shouldBe($verb);
        $statement->getObject()->shouldBe($object);
        $statement->getResult()->shouldBe($result);
        $statement->getContext()->shouldBe($context);
        $statement->getCreated()->shouldBe($created);
        $statement->getStored()->shouldBe($stored);
        $statement->getAuthority()->shouldBe($authority);
    }

    function it_throws_an_exception_when_a_statement_is_created_without_an_actor()
    {
        $this->withVerb(new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid')));
        $this->withObject(new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid')));

        $this->shouldThrow('\Xabbuh\XApi\Model\Exception\InvalidStateException')->during('createStatement');
    }

    function it_throws_an_exception_when_a_statement_is_created_without_a_verb()
    {
        $this->withActor(new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com'))));
        $this->withObject(new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid')));

        $this->shouldThrow('\Xabbuh\XApi\Model\Exception\InvalidStateException')->during('createStatement');
    }

    function it_throws_an_exception_when_a_statement_is_created_without_an_object()
    {
        $this->withActor(new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com'))));
        $this->withVerb(new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid')));

        $this->shouldThrow('\Xabbuh\XApi\Model\Exception\InvalidStateException')->during('createStatement');
    }

    function it_can_reset_the_result()
    {
        $this->configureAllProperties();
        $this->withResult(null);
        $statement = $this->createStatement();

        $statement->getResult()->shouldReturn(null);
    }

    function it_can_reset_the_context()
    {
        $this->configureAllProperties();
        $this->withContext(null);
        $statement = $this->createStatement();

        $statement->getContext()->shouldReturn(null);
    }

    function it_can_reset_the_created()
    {
        $this->configureAllProperties();
        $this->withCreated(null);
        $statement = $this->createStatement();

        $statement->getCreated()->shouldReturn(null);
    }

    function it_can_reset_the_stored()
    {
        $this->configureAllProperties();
        $this->withStored(null);
        $statement = $this->createStatement();

        $statement->getStored()->shouldReturn(null);
    }

    function it_can_reset_the_authority()
    {
        $this->configureAllProperties();
        $this->withAuthority(null);
        $statement = $this->createStatement();

        $statement->getAuthority()->shouldReturn(null);
    }

    private function configureAllProperties()
    {
        $id = StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af');
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'));
        $object = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $result = new Result();
        $context = new Context();
        $created = new \DateTime('2014-07-23T12:34:02-05:00');
        $stored = new \DateTime('2014-07-24T12:34:02-05:00');
        $authority = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));

        $this->withId($id);
        $this->withActor($actor);
        $this->withVerb($verb);
        $this->withObject($object);
        $this->withResult($result);
        $this->withContext($context);
        $this->withCreated($created);
        $this->withStored($stored);
        $this->withAuthority($authority);
    }
}
