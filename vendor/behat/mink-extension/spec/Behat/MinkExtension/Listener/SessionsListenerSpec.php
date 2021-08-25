<?php

namespace spec\Behat\MinkExtension\Listener;

use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Mink\Mink;
use Behat\Testwork\ServiceContainer\Exception\ProcessingException;
use Behat\Testwork\Suite\Exception\SuiteConfigurationException;
use Behat\Testwork\Suite\Suite;
use PhpSpec\ObjectBehavior;

class SessionsListenerSpec extends ObjectBehavior
{
    function let(Mink $mink, ScenarioTested $event, FeatureNode $feature, ScenarioNode $scenario, Suite $suite)
    {
        $this->beConstructedWith($mink, 'goutte', 'selenium2', array('selenium2', 'sahi'));

        $event->getSuite()->willReturn($suite);
        $event->getFeature()->willReturn($feature);
        $event->getScenario()->willReturn($scenario);

        $suite->hasSetting('mink_session')->willReturn(false);
        $suite->getName()->willReturn('default');

        $feature->hasTag('insulated')->willReturn(false);
        $feature->getTags()->willReturn(array());
        $scenario->hasTag('insulated')->willReturn(false);
        $scenario->getTags()->willReturn(array());
    }

    function it_is_an_event_subscriber()
    {
        $this->shouldHaveType('Symfony\Component\EventDispatcher\EventSubscriberInterface');
    }

    function it_resets_the_default_session_before_scenarios($event, $mink)
    {
        $mink->resetSessions()->shouldBeCalled();
        $mink->setDefaultSessionName('goutte')->shouldBeCalled();

        $this->prepareDefaultMinkSession($event);
    }

    function it_supports_changing_the_default_session_per_suite($event, $mink, $suite)
    {
        $suite->hasSetting('mink_session')->willReturn(true);
        $suite->getSetting('mink_session')->willReturn('test');

        $mink->resetSessions()->shouldBeCalled();
        $mink->setDefaultSessionName('test')->shouldBeCalled();

        $this->prepareDefaultMinkSession($event);
    }

    function it_fails_for_non_string_default_suite_session($event, $suite)
    {
        $suite->hasSetting('mink_session')->willReturn(true);
        $suite->getSetting('mink_session')->willReturn(array());

        $this->shouldThrow(new SuiteConfigurationException('`mink_session` setting of the "default" suite is expected to be a string, array given.', 'default'))
            ->duringPrepareDefaultMinkSession($event);
    }

    function it_switches_to_the_javascript_session_for_tagged_scenarios($event, $mink, $scenario, $suite)
    {
        $suite->hasSetting('mink_javascript_session')->willReturn(false);
        $scenario->getTags()->willReturn(array('javascript'));
        $mink->resetSessions()->shouldBeCalled();
        $mink->setDefaultSessionName('selenium2')->shouldBeCalled();

        $this->prepareDefaultMinkSession($event);
    }

    function it_switches_to_the_javascript_session_for_tagged_features($event, $mink, $feature, $suite)
    {
        $suite->hasSetting('mink_javascript_session')->willReturn(false);
        $feature->getTags()->willReturn(array('javascript'));
        $mink->resetSessions()->shouldBeCalled();
        $mink->setDefaultSessionName('selenium2')->shouldBeCalled();

        $this->prepareDefaultMinkSession($event);
    }

    function it_supports_changing_the_default_javascript_session_per_suite($event, $mink, $scenario, $suite)
    {
        $suite->hasSetting('mink_javascript_session')->willReturn(true);
        $suite->getSetting('mink_javascript_session')->willReturn('sahi');

        $scenario->getTags()->willReturn(array('javascript'));
        $mink->resetSessions()->shouldBeCalled();
        $mink->setDefaultSessionName('sahi')->shouldBeCalled();

        $this->prepareDefaultMinkSession($event);
    }

    function it_fails_for_non_string_javascript_suite_session($event, $scenario, $suite)
    {
        $suite->hasSetting('mink_javascript_session')->willReturn(true);
        $suite->getSetting('mink_javascript_session')->willReturn(array());

        $scenario->getTags()->willReturn(array('javascript'));

        $this->shouldThrow(new SuiteConfigurationException('`mink_javascript_session` setting of the "default" suite is expected to be a string, array given.', 'default'))
            ->duringPrepareDefaultMinkSession($event);
    }

    function it_fails_for_invalid_javascript_suite_session($event, $scenario, $suite)
    {
        $suite->hasSetting('mink_javascript_session')->willReturn(true);
        $suite->getSetting('mink_javascript_session')->willReturn('test');

        $scenario->getTags()->willReturn(array('javascript'));

        $this->shouldThrow(new SuiteConfigurationException('`mink_javascript_session` setting of the "default" suite is not a javascript session. test given but expected one of selenium2, sahi.', 'default'))
            ->duringPrepareDefaultMinkSession($event);
    }

    function it_fails_when_the_javascript_session_is_used_but_not_defined($event, $mink, $feature, $suite)
    {
        $suite->hasSetting('mink_javascript_session')->willReturn(false);
        $this->beConstructedWith($mink, 'goutte', null);
        $feature->getTags()->willReturn(array('javascript'));

        $this->shouldThrow(new ProcessingException('The @javascript tag cannot be used without enabling a javascript session'))
            ->duringPrepareDefaultMinkSession($event);
    }

    function it_switches_to_a_named_session($event, $mink, $scenario)
    {
        $scenario->getTags()->willReturn(array('mink:test'));
        $mink->resetSessions()->shouldBeCalled();
        $mink->setDefaultSessionName('test')->shouldBeCalled();

        $this->prepareDefaultMinkSession($event);
    }

    function it_prefers_the_scenario_over_the_feature($event, $mink, $scenario, $feature, $suite)
    {
        $suite->hasSetting('mink_javascript_session')->willReturn(false);
        $scenario->getTags()->willReturn(array('mink:test'));
        $feature->getTags()->willReturn(array('javascript'));
        $mink->resetSessions()->shouldBeCalled();
        $mink->setDefaultSessionName('test')->shouldBeCalled();

        $this->prepareDefaultMinkSession($event);
    }

    function it_stops_the_sessions_for_insulated_scenarios($event, $mink, $scenario)
    {
        $scenario->hasTag('insulated')->willReturn(true);
        $mink->stopSessions()->shouldBeCalled();
        $mink->setDefaultSessionName('goutte')->shouldBeCalled();

        $this->prepareDefaultMinkSession($event);
    }

    function it_stops_the_sessions_for_insulated_features($event, $mink, $feature)
    {
        $feature->hasTag('insulated')->willReturn(true);
        $mink->stopSessions()->shouldBeCalled();
        $mink->setDefaultSessionName('goutte')->shouldBeCalled();

        $this->prepareDefaultMinkSession($event);
    }

    function it_stops_the_sessions_at_the_end_of_the_exercise($mink)
    {
        $mink->stopSessions()->shouldBeCalled();

        $this->tearDownMinkSessions();
    }
}
