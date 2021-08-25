<?php

namespace spec\Xabbuh\XApi\Model;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\Context;
use Xabbuh\XApi\Model\ContextActivities;
use Xabbuh\XApi\Model\Extensions;
use Xabbuh\XApi\Model\Group;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementReference;

class ContextSpec extends ObjectBehavior
{
    public function its_properties_are_empty_by_default()
    {
        $this->getRegistration()->shouldBeNull();
        $this->getInstructor()->shouldBeNull();
        $this->getTeam()->shouldBeNull();
        $this->getContextActivities()->shouldBeNull();
        $this->getRevision()->shouldBeNull();
        $this->getPlatform()->shouldBeNull();
        $this->getLanguage()->shouldBeNull();
        $this->getStatement()->shouldBeNull();
        $this->getExtensions()->shouldBeNull();
    }

    public function it_returns_a_new_instance_with_registration()
    {
        $context = $this->withRegistration('12345678-1234-5678-8234-567812345678');

        $this->getRegistration()->shouldBeNull();

        $context->shouldNotBe($this);
        $context->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Context');
        $context->getRegistration()->shouldReturn('12345678-1234-5678-8234-567812345678');
    }

    public function it_returns_a_new_instance_with_instructor()
    {
        $instructor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $context = $this->withInstructor($instructor);

        $this->getInstructor()->shouldBeNull();

        $context->shouldNotBe($this);
        $context->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Context');
        $context->getInstructor()->shouldReturn($instructor);
    }

    public function it_returns_a_new_instance_with_team()
    {
        $team = new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')), 'team');
        $context = $this->withTeam($team);

        $this->getTeam()->shouldBeNull();

        $context->shouldNotBe($this);
        $context->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Context');
        $context->getTeam()->shouldReturn($team);
    }

    public function it_returns_a_new_instance_with_context_activities()
    {
        $contextActivities = new ContextActivities();
        $context = $this->withContextActivities($contextActivities);

        $this->getContextActivities()->shouldBeNull();

        $context->shouldNotBe($this);
        $context->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Context');
        $context->getContextActivities()->shouldReturn($contextActivities);
    }

    public function it_returns_a_new_instance_with_revision()
    {
        $context = $this->withRevision('test');

        $this->getRevision()->shouldBeNull();

        $context->shouldNotBe($this);
        $context->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Context');
        $context->getRevision()->shouldReturn('test');
    }

    public function it_returns_a_new_instance_with_platform()
    {
        $context = $this->withPlatform('test');

        $this->getPlatform()->shouldBeNull();

        $context->shouldNotBe($this);
        $context->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Context');
        $context->getPlatform()->shouldReturn('test');
    }

    public function it_returns_a_new_instance_with_language()
    {
        $context = $this->withLanguage('en-US');

        $this->getLanguage()->shouldBeNull();

        $context->shouldNotBe($this);
        $context->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Context');
        $context->getLanguage()->shouldReturn('en-US');
    }

    public function it_returns_a_new_instance_with_statement_reference()
    {
        $statementReference = new StatementReference(StatementId::fromString('16fd2706-8baf-433b-82eb-8c7fada847da'));
        $context = $this->withStatement($statementReference);

        $this->getStatement()->shouldBeNull();

        $context->shouldNotBe($this);
        $context->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Context');
        $context->getStatement()->shouldReturn($statementReference);
    }

    public function it_returns_a_new_instance_with_extensions()
    {
        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/topic'), 'Conformance Testing');
        $extensions = new Extensions($extensions);
        $context = $this->withExtensions($extensions);

        $this->getExtensions()->shouldBeNull();

        $context->shouldNotBe($this);
        $context->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Context');
        $context->getExtensions()->shouldReturn($extensions);
    }

    function it_is_not_equal_to_other_context_if_only_this_context_has_a_team()
    {
        $context = $this->withTeam(new Group());

        $context->equals(new Context())->shouldReturn(false);
    }

    function it_is_not_equal_to_other_context_if_only_the_other_context_has_a_team()
    {
        $otherContext = $this->withTeam(new Group());

        $this->equals($otherContext)->shouldReturn(false);
    }

    function it_is_not_equal_to_other_context_if_teams_are_not_equal()
    {
        $context = $this->withTeam(new Group());

        $otherContext = new Context();
        $otherContext = $otherContext->withTeam(new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest-group@tincanapi.com'))));

        $context->equals($otherContext)->shouldReturn(false);
    }

    function it_is_not_equal_to_other_context_if_only_this_context_has_a_statement_reference()
    {
        $context = $this->withStatement(new StatementReference(StatementId::fromString('16fd2706-8baf-433b-82eb-8c7fada847da')));

        $context->equals(new Context())->shouldReturn(false);
    }

    function it_is_not_equal_to_other_context_if_only_the_other_context_has_a_statement_reference()
    {
        $otherContext = $this->withStatement(new StatementReference(StatementId::fromString('16fd2706-8baf-433b-82eb-8c7fada847da')));

        $this->equals($otherContext)->shouldReturn(false);
    }

    function it_is_not_equal_to_other_context_if_statement_references_are_not_equal()
    {
        $context = $this->withStatement(new StatementReference(StatementId::fromString('16fd2706-8baf-433b-82eb-8c7fada847da')));

        $otherContext = new Context();
        $otherContext = $otherContext->withStatement(new StatementReference(StatementId::fromString('39e24cc4-69af-4b01-a824-1fdc6ea8a3af')));

        $context->equals($otherContext)->shouldReturn(false);
    }

    function it_is_not_equal_to_other_context_if_only_this_context_has_extensions()
    {
        $context = $this->withExtensions(new Extensions());

        $context->equals(new Context())->shouldReturn(false);
    }

    function it_is_not_equal_to_other_context_if_only_the_other_context_has_extensions()
    {
        $otherContext = $this->withExtensions(new Extensions());

        $this->equals($otherContext)->shouldReturn(false);
    }

    function it_is_not_equal_to_other_context_if_extensions_are_not_equal()
    {
        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/subject'), 'Conformance Testing');
        $context = $this->withExtensions(new Extensions($extensions));

        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/topic'), 'Conformance Testing');
        $otherContext = new Context();
        $otherContext = $otherContext->withExtensions(new Extensions($extensions));

        $context->equals($otherContext)->shouldReturn(false);
    }
}
