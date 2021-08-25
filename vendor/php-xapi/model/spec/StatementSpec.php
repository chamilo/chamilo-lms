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
use Xabbuh\XApi\Model\Group;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Result;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementObject;
use Xabbuh\XApi\Model\StatementReference;
use Xabbuh\XApi\Model\Verb;

class StatementSpec extends ObjectBehavior
{
    function let()
    {
        $id = StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af');
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'), LanguageMap::create(array('en-US' => 'test')));
        $object = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $this->beConstructedWith($id, $actor, $verb, $object);
    }

    function its_default_version_is_null()
    {
        $this->getVersion()->shouldReturn(null);
    }

    function it_creates_reference_to_itself()
    {
        $reference = $this->getStatementReference();
        $reference->shouldBeAnInstanceOf(StatementReference::class);
        $reference->getStatementId()->equals(StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af'))->shouldReturn(true);
    }

    function it_creates_statement_voiding_itself()
    {
        $voidingActor = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));
        $voidingStatement = $this->getVoidStatement($voidingActor);
        $voidingStatement->getActor()->shouldBe($voidingActor);
        $voidingStatement->getVerb()->isVoidVerb()->shouldReturn(true);

        $voidedStatement = $voidingStatement->getObject();
        $voidedStatement->shouldBeAnInstanceOf(StatementReference::class);
        $voidedStatement->getStatementId()->equals(StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af'))->shouldReturn(true);
    }

    function it_can_be_authorized()
    {
        $authority = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));
        $authorizedStatement = $this->withAuthority($authority);
        $authorizedStatement->getAuthority()->shouldReturn($authority);

        $authorizedStatement->shouldBeAnInstanceOf(Statement::class);
        $authorizedStatement->getActor()->equals($this->getActor())->shouldBe(true);
        $authorizedStatement->getVerb()->equals($this->getVerb())->shouldBe(true);
        $authorizedStatement->getObject()->equals($this->getObject())->shouldBe(true);
    }

    function it_overrides_existing_authority_when_it_is_authorized()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'), LanguageMap::create(array('en-US' => 'test')));
        $object = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $authority = new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $this->beConstructedWith(StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af'), $actor, $verb, $object, null, $authority);

        $authority = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));
        $authorizedStatement = $this->withAuthority($authority);
        $authorizedStatement->getAuthority()->shouldReturn($authority);

        $authorizedStatement->shouldBeAnInstanceOf(Statement::class);
        $authorizedStatement->getActor()->equals($this->getActor())->shouldBe(true);
        $authorizedStatement->getVerb()->equals($this->getVerb())->shouldBe(true);
        $authorizedStatement->getObject()->equals($this->getObject())->shouldBe(true);
        $authorizedStatement->getAuthority()->equals($this->getAuthority())->shouldBe(false);
    }

    function its_object_can_be_an_agent()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'), LanguageMap::create(array('en-US' => 'test')));
        $object = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));
        $this->beConstructedWith(StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af'), $actor, $verb, $object);

        $this->getObject()->shouldBeAnInstanceOf(StatementObject::class);
        $this->getObject()->shouldBe($object);
    }

    function it_does_not_equal_another_statement_with_different_timestamp()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'), LanguageMap::create(array('en-US' => 'test')));
        $object = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));
        $this->beConstructedWith(StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af'), $actor, $verb, $object, null, null, new \DateTime('2014-07-23T12:34:02-05:00'));

        $otherStatement = new Statement(StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af'), $actor, $verb, $object, null, null, new \DateTime('2015-07-23T12:34:02-05:00'));

        $this->equals($otherStatement)->shouldBe(false);
    }

    function it_equals_another_statement_with_same_timestamp()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $verb = new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid'), LanguageMap::create(array('en-US' => 'test')));
        $object = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));
        $this->beConstructedWith(StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af'), $actor, $verb, $object, null, null, new \DateTime('2014-07-23T12:34:02-05:00'));

        $otherStatement = new Statement(StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af'), $actor, $verb, $object, null, null, new \DateTime('2014-07-23T12:34:02-05:00'));

        $this->equals($otherStatement)->shouldBe(true);
    }

    public function it_returns_a_new_instance_with_id()
    {
        $id = StatementId::fromString('12345678-1234-5678-8234-567812345678');
        $statement = $this->withId($id);

        $statement->shouldNotBe($this);
        $statement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Statement');
        $statement->getId()->shouldReturn($id);
    }

    public function it_returns_a_new_instance_with_actor()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));
        $statement = $this->withActor($actor);

        $statement->shouldNotBe($this);
        $statement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Statement');
        $statement->getActor()->shouldReturn($actor);
    }

    public function it_returns_a_new_instance_with_verb()
    {
        $verb = new Verb(IRI::fromString('http://adlnet.gov/expapi/verbs/voided'));
        $statement = $this->withVerb($verb);

        $statement->shouldNotBe($this);
        $statement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Statement');
        $statement->getVerb()->shouldReturn($verb);
    }

    public function it_returns_a_new_instance_with_object()
    {
        $statementReference = new StatementReference(StatementId::fromString('12345678-1234-5678-8234-567812345678'));
        $statement = $this->withObject($statementReference);

        $statement->shouldNotBe($this);
        $statement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Statement');
        $statement->getObject()->shouldReturn($statementReference);
    }

    public function it_returns_a_new_instance_with_result()
    {
        $result = new Result();
        $statement = $this->withResult($result);

        $statement->shouldNotBe($this);
        $statement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Statement');
        $statement->getResult()->shouldReturn($result);
    }

    public function it_returns_a_new_instance_with_authority()
    {
        $authority = new Agent(InverseFunctionalIdentifier::withOpenId('http://openid.tincanapi.com'));
        $statement = $this->withAuthority($authority);

        $statement->shouldNotBe($this);
        $statement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Statement');
        $statement->getAuthority()->shouldReturn($authority);
    }

    public function it_returns_a_new_instance_with_stored()
    {
        $stored = new \DateTime('2014-07-23T12:34:02-05:00');
        $statement = $this->withStored($stored);

        $statement->shouldNotBe($this);
        $statement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Statement');
        $statement->getStored()->shouldReturn($stored);
    }

    public function it_returns_a_new_instance_with_context()
    {
        $context = new Context();
        $statement = $this->withContext($context);

        $statement->shouldNotBe($this);
        $statement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Statement');
        $statement->getContext()->shouldReturn($context);
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
        $statement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Statement');
        $statement->getAttachments()->shouldReturn($attachments);
    }

    function it_returns_a_new_instance_with_version()
    {
        $statement = $this->withVersion('1.0.1');

        $statement->shouldNotBe($this);
        $statement->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Statement');
        $statement->getVersion()->shouldReturn('1.0.1');
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
        $this->beConstructedWith(null, $actor, $verb, $object, null, null, null, null, null, $attachments);

        $this->getAttachments()->shouldBeArray();
        $this->getAttachments()->shouldHaveKeyWithValue(0, $textAttachment);

        $statement = $this->withAttachments($attachments);

        $statement->getAttachments()->shouldBeArray();
        $statement->getAttachments()->shouldHaveKeyWithValue(0, $textAttachment);
    }

    function it_is_not_equal_with_other_statement_if_only_this_statement_has_an_id()
    {
        $this->equals($this->withId(null))->shouldReturn(false);
    }

    function it_is_not_equal_with_other_statement_if_only_the_other_statement_has_an_id()
    {
        $statement = $this->withId(null);
        $otherStatement = $statement->withId(StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af'));

        $statement->equals($otherStatement)->shouldReturn(false);
    }

    function it_is_not_equal_with_other_statement_if_ids_differ()
    {
        $statement = $this->withId(StatementId::fromString('12345678-1234-5678-8234-567812345678'));

        $this->equals($statement)->shouldReturn(false);
    }

    function it_is_not_equal_with_other_statement_if_only_this_statement_has_context()
    {
        $statement = $this->withContext(new Context());

        $statement->equals($statement->withContext(null))->shouldReturn(false);
    }

    function it_is_not_equal_with_other_statement_if_only_the_other_statement_has_context()
    {
        $statement = $this->withContext(new Context());

        $this->equals($statement)->shouldReturn(false);
    }

    function it_is_not_equal_with_other_statement_if_contexts_differ()
    {
        $context = new Context();
        $revisionContext = $context->withRevision('test');
        $platformContext = $context->withPlatform('test');
        $statement = $this->withContext($revisionContext);

        $this->withContext($platformContext)->equals($statement)->shouldReturn(false);
    }

    function it_is_not_equal_with_other_statement_if_only_this_statement_has_attachments()
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

        $statement->equals($this->withAttachments(null))->shouldReturn(false);
    }

    function it_is_not_equal_with_other_statement_if_only_the_other_statement_has_attachments()
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

        $this->equals($statement)->shouldReturn(false);
    }

    function it_is_not_equal_with_other_statement_if_number_of_attachments_differs()
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
        $jsonAttachment = new Attachment(
            IRI::fromString('http://id.tincanapi.com/attachment/supporting_media'),
            'application/json',
            60,
            'f4135c31e2710764604195dfe4e225884d8108467cc21670803e384b80df88ee',
            LanguageMap::create(array('en-US' => 'JSON attachment')),
            null,
            IRL::fromString('http://tincanapi.com/conformancetest/attachment/fileUrlOnly')
        );
        $statement = $this->withAttachments(array($textAttachment, $jsonAttachment));

        $statement->equals($statement->withAttachments(array($textAttachment)))->shouldReturn(false);
    }

    function it_is_not_equal_with_other_statement_if_attachments_differ()
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
        $jsonAttachment = new Attachment(
            IRI::fromString('http://id.tincanapi.com/attachment/supporting_media'),
            'application/json',
            60,
            'f4135c31e2710764604195dfe4e225884d8108467cc21670803e384b80df88ee',
            LanguageMap::create(array('en-US' => 'JSON attachment')),
            null,
            IRL::fromString('http://tincanapi.com/conformancetest/attachment/fileUrlOnly')
        );
        $statement = $this->withAttachments(array($textAttachment));

        $statement->equals($statement->withAttachments(array($jsonAttachment)))->shouldReturn(false);
    }

    function it_is_equal_with_other_statement_even_if_versions_differ()
    {
        $statement = $this->withVersion('1.0.0');
        $otherStatement = $this->withVersion('1.0.1');

        $statement->equals($otherStatement)->shouldReturn(true);
    }
}
