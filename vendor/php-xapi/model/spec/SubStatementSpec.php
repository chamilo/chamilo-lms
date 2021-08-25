<?php
/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Xabbuh\XApi\Model;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\Attachment;
use Xabbuh\XApi\Model\Context;
use Xabbuh\XApi\Model\ContextActivities;
use Xabbuh\XApi\Model\Extensions;
use Xabbuh\XApi\Model\Group;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Result;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementObject;
use Xabbuh\XApi\Model\StatementReference;
use Xabbuh\XApi\Model\SubStatement;
use Xabbuh\XApi\Model\Verb;

class SubStatementSpec extends ObjectBehavior
{
    function let()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'), LanguageMap::create(array('en-US' => 'test')));
        $object = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $this->beConstructedWith($actor, $verb, $object);
    }

    function it_is_an_xapi_object()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'), LanguageMap::create(array('en-US' => 'test')));
        $object = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $this->beConstructedWith($actor, $verb, $object);

        $this->shouldHaveType(StatementObject::class);
    }

    function its_object_can_be_an_agent()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'), LanguageMap::create(array('en-US' => 'test')));
        $object = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));
        $this->beConstructedWith($actor, $verb, $object);

        $this->getObject()->shouldBeAnInstanceOf(StatementObject::class);
        $this->getObject()->shouldBe($object);
    }

    function it_does_not_equal_another_statement_with_different_timestamp()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'), LanguageMap::create(array('en-US' => 'test')));
        $object = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));
        $this->beConstructedWith($actor, $verb, $object, null, null, new \DateTime('2014-07-23T12:34:02-05:00'));

        $otherStatement = new SubStatement($actor, $verb, $object, null, null, new \DateTime('2015-07-23T12:34:02-05:00'));

        $this->equals($otherStatement)->shouldBe(false);
    }

    function it_equals_another_statement_with_same_timestamp()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'), LanguageMap::create(array('en-US' => 'test')));
        $object = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));
        $this->beConstructedWith($actor, $verb, $object, null, null, new \DateTime('2014-07-23T12:34:02-05:00'));

        $otherStatement = new SubStatement($actor, $verb, $object, null, null, new \DateTime('2014-07-23T12:34:02-05:00'));

        $this->equals($otherStatement)->shouldBe(true);
    }

    function it_is_different_from_another_sub_statement_if_contexts_differ()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'), LanguageMap::create(array('en-US' => 'test')));
        $object = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $this->beConstructedWith($actor, $verb, $object, null, new Context());

        $subStatement = new SubStatement($actor, $verb, $object);

        $this->equals($subStatement)->shouldReturn(false);

        $context = new Context();
        $context = $context->withRegistration('16fd2706-8baf-433b-82eb-8c7fada847da')
            ->withInstructor(new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com'))))
            ->withTeam(new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest-group@tincanapi.com'))))
            ->withContextActivities(new ContextActivities(
                array(new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'))),
                array(new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'))),
                array(new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'))),
                array(new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid')))
            ))
            ->withRevision('test')
            ->withPlatform('test')
            ->withLanguage('en-US')
            ->withStatement(new StatementReference(StatementId::fromString('16fd2706-8baf-433b-82eb-8c7fada847da')))
            ->withExtensions(new Extensions())
        ;
        $subStatement = new SubStatement($actor, $verb, $object, null, $context);

        $this->equals($subStatement)->shouldReturn(false);
    }

    function it_rejects_to_hold_another_sub_statement_as_object()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'), LanguageMap::create(array('en-US' => 'test')));
        $object = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $subStatement = new SubStatement($actor, $verb, $object);

        $this->shouldThrow('\InvalidArgumentException')->during('__construct', array($actor, $verb, $subStatement));
    }

    public function it_returns_a_new_instance_with_actor()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));
        $subStatement = $this->withActor($actor);

        $subStatement->shouldNotBe($this);
        $subStatement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\SubStatement');
        $subStatement->getActor()->shouldReturn($actor);
    }

    public function it_returns_a_new_instance_with_verb()
    {
        $verb = new Verb(IRI::fromString('http://adlnet.gov/expapi/verbs/voided'));
        $subStatement = $this->withVerb($verb);

        $subStatement->shouldNotBe($this);
        $subStatement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\SubStatement');
        $subStatement->getVerb()->shouldReturn($verb);
    }

    public function it_returns_a_new_instance_with_object()
    {
        $statementReference = new StatementReference(StatementId::fromString('12345678-1234-5678-8234-567812345678'));
        $subStatement = $this->withObject($statementReference);

        $subStatement->shouldNotBe($this);
        $subStatement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\SubStatement');
        $subStatement->getObject()->shouldReturn($statementReference);
    }

    public function it_returns_a_new_instance_with_result()
    {
        $result = new Result();
        $subStatement = $this->withResult($result);

        $subStatement->shouldNotBe($this);
        $subStatement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\SubStatement');
        $subStatement->getResult()->shouldReturn($result);
    }

    public function it_returns_a_new_instance_with_context()
    {
        $context = new Context();
        $subStatement = $this->withContext($context);

        $subStatement->shouldNotBe($this);
        $subStatement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\SubStatement');
        $subStatement->getContext()->shouldReturn($context);
    }

    public function it_returns_a_new_instance_with_attachments()
    {
        $attachments = array(new Attachment(
            IRI::fromString('http://id.tincanapi.com/attachment/supporting_media'),
            'text/plain',
            18,
            'bd1a58265d96a3d1981710dab8b1e1ed04a8d7557ea53ab0cf7b44c04fd01545',
            LanguageMap::create(array('en-US' => 'Text attachment')),
            LanguageMap::create(array('en-US' => 'Text attachment description')),
            IRL::fromString('http://tincanapi.com/conformancetest/attachment/fileUrlOnly')
        ));
        $statement = $this->withAttachments($attachments);

        $statement->shouldNotBe($this);
        $statement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\SubStatement');
        $statement->getAttachments()->shouldReturn($attachments);
    }

    function it_ignores_array_keys_in_attachment_lists()
    {
        $textAttachment = new Attachment(
            IRI::fromString('http://id.tincanapi.com/attachment/supporting_media'),
            'text/plain',
            18,
            'bd1a58265d96a3d1981710dab8b1e1ed04a8d7557ea53ab0cf7b44c04fd01545',
            LanguageMap::create(array('en-US' => 'Text attachment')),
            LanguageMap::create(array('en-US' => 'Text attachment description')),
            IRL::fromString('http://tincanapi.com/conformancetest/attachment/fileUrlOnly')
        );
        $attachments = array(1 => $textAttachment);

        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'), LanguageMap::create(array('en-US' => 'test')));
        $object = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $this->beConstructedWith($actor, $verb, $object, null, null, null, $attachments);

        $this->getAttachments()->shouldBeArray();
        $this->getAttachments()->shouldHaveKeyWithValue(0, $textAttachment);

        $statement = $this->withAttachments($attachments);

        $statement->getAttachments()->shouldBeArray();
        $statement->getAttachments()->shouldHaveKeyWithValue(0, $textAttachment);
    }
}
