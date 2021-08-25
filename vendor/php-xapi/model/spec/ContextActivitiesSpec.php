<?php

namespace spec\Xabbuh\XApi\Model;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\IRI;

class ContextActivitiesSpec extends ObjectBehavior
{
    public function its_properties_are_empty_by_default()
    {
        $this->getParentActivities()->shouldBeNull();
        $this->getGroupingActivities()->shouldBeNull();
        $this->getCategoryActivities()->shouldBeNull();
        $this->getOtherActivities()->shouldBeNull();
    }

    public function it_returns_a_new_instance_with_parent_activities()
    {
        $activity = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $contextActivities = $this->withAddedParentActivity($activity);

        $this->getParentActivities()->shouldBeNull();

        $contextActivities->shouldNotBe($this);
        $contextActivities->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\ContextActivities');

        $parentActivities = $contextActivities->getParentActivities();
        $parentActivities->shouldBeArray();
        $parentActivities->shouldHaveCount(1);
        $parentActivities->shouldHaveKeyWithValue(0, $activity);
    }

    public function it_returns_a_new_instance_with_parent_activities_removed()
    {
        $activity = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));

        $this->beConstructedWith(array($activity));

        $contextActivities = $this->withoutParentActivities();

        $parentActivities = $this->getParentActivities();
        $parentActivities->shouldBeArray();
        $parentActivities->shouldHaveCount(1);
        $parentActivities->shouldHaveKeyWithValue(0, $activity);

        $contextActivities->shouldNotBe($this);
        $contextActivities->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\ContextActivities');
        $contextActivities->getParentActivities()->shouldBeNull();
    }

    public function it_returns_a_new_instance_with_grouping_activities()
    {
        $activity = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $contextActivities = $this->withAddedGroupingActivity($activity);

        $this->getGroupingActivities()->shouldBeNull();

        $contextActivities->shouldNotBe($this);
        $contextActivities->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\ContextActivities');

        $groupingActivities = $contextActivities->getGroupingActivities();
        $groupingActivities->shouldBeArray();
        $groupingActivities->shouldHaveCount(1);
        $groupingActivities->shouldHaveKeyWithValue(0, $activity);
    }

    public function it_returns_a_new_instance_with_grouping_activities_removed()
    {
        $activity = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));

        $this->beConstructedWith(null, array($activity));

        $contextActivities = $this->withoutGroupingActivities();

        $groupingActivities = $this->getGroupingActivities();
        $groupingActivities->shouldBeArray();
        $groupingActivities->shouldHaveCount(1);
        $groupingActivities->shouldHaveKeyWithValue(0, $activity);

        $contextActivities->shouldNotBe($this);
        $contextActivities->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\ContextActivities');
        $contextActivities->getGroupingActivities()->shouldBeNull();
    }

    public function it_returns_a_new_instance_with_category_activities()
    {
        $activity = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $contextActivities = $this->withAddedCategoryActivity($activity);

        $this->getCategoryActivities()->shouldBeNull();

        $contextActivities->shouldNotBe($this);
        $contextActivities->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\ContextActivities');

        $CategoryActivities = $contextActivities->getCategoryActivities();
        $CategoryActivities->shouldBeArray();
        $CategoryActivities->shouldHaveCount(1);
        $CategoryActivities->shouldHaveKeyWithValue(0, $activity);
    }

    public function it_returns_a_new_instance_with_category_activities_removed()
    {
        $activity = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));

        $this->beConstructedWith(null, null, array($activity));

        $contextActivities = $this->withoutCategoryActivities();

        $categoryActivities = $this->getCategoryActivities();
        $categoryActivities->shouldBeArray();
        $categoryActivities->shouldHaveCount(1);
        $categoryActivities->shouldHaveKeyWithValue(0, $activity);

        $contextActivities->shouldNotBe($this);
        $contextActivities->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\ContextActivities');
        $contextActivities->getCategoryActivities()->shouldBeNull();
    }

    public function it_returns_a_new_instance_with_other_activities()
    {
        $activity = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $contextActivities = $this->withAddedOtherActivity($activity);

        $this->getOtherActivities()->shouldBeNull();

        $contextActivities->shouldNotBe($this);
        $contextActivities->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\ContextActivities');

        $otherActivities = $contextActivities->getOtherActivities();
        $otherActivities->shouldBeArray();
        $otherActivities->shouldHaveCount(1);
        $otherActivities->shouldHaveKeyWithValue(0, $activity);
    }

    public function it_returns_a_new_instance_with_other_activities_removed()
    {
        $activity = new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));

        $this->beConstructedWith(null, null, null, array($activity));

        $contextActivities = $this->withoutOtherActivities();

        $otherActivities = $this->getOtherActivities();
        $otherActivities->shouldBeArray();
        $otherActivities->shouldHaveCount(1);
        $otherActivities->shouldHaveKeyWithValue(0, $activity);

        $contextActivities->shouldNotBe($this);
        $contextActivities->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\ContextActivities');
        $contextActivities->getOtherActivities()->shouldBeNull();
    }
}
