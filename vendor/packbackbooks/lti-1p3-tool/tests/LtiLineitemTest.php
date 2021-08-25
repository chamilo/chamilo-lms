<?php namespace Tests;

use PHPUnit\Framework\TestCase;

use Packback\Lti1p3\LtiLineitem;

class LtiLineitemTest extends TestCase
{
    public function setUp(): void
    {
        $this->lineItem = new LtiLineitem;
    }

    public function testItInstantiates()
    {
        $this->assertInstanceOf(LtiLineitem::class, $this->lineItem);
    }

    public function testItCreatesANewInstance()
    {
        $grade = LtiLineitem::new();

        $this->assertInstanceOf(LtiLineitem::class, $grade);
    }

    public function testItGetsId()
    {
        $expected = 'expected';
        $grade = new LtiLineitem([ 'id' => $expected ]);

        $result = $grade->getId();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsId()
    {
        $expected = 'expected';

        $this->lineItem->setId($expected);

        $this->assertEquals($expected, $this->lineItem->getId());
    }

    public function testItGetsScoreMaximum()
    {
        $expected = 'expected';
        $grade = new LtiLineitem([ 'scoreMaximum' => $expected ]);

        $result = $grade->getScoreMaximum();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsScoreMaximum()
    {
        $expected = 'expected';

        $this->lineItem->setScoreMaximum($expected);

        $this->assertEquals($expected, $this->lineItem->getScoreMaximum());
    }

    public function testItGetsLabel()
    {
        $expected = 'expected';
        $grade = new LtiLineitem([ 'label' => $expected ]);

        $result = $grade->getLabel();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsLabel()
    {
        $expected = 'expected';

        $this->lineItem->setLabel($expected);

        $this->assertEquals($expected, $this->lineItem->getLabel());
    }

    public function testItGetsResourceId()
    {
        $expected = 'expected';
        $grade = new LtiLineitem([ 'resourceId' => $expected ]);

        $result = $grade->getResourceId();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsResourceId()
    {
        $expected = 'expected';

        $this->lineItem->setResourceId($expected);

        $this->assertEquals($expected, $this->lineItem->getResourceId());
    }

    public function testItGetsResourceLinkId()
    {
        $expected = 'expected';
        $grade = new LtiLineitem([ 'resourceLinkId' => $expected ]);

        $result = $grade->getResourceLinkId();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsResourceLinkId()
    {
        $expected = 'expected';

        $this->lineItem->setResourceLinkId($expected);

        $this->assertEquals($expected, $this->lineItem->getResourceLinkId());
    }

    public function testItGetsTag()
    {
        $expected = 'expected';
        $grade = new LtiLineitem([ 'tag' => $expected ]);

        $result = $grade->getTag();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsTag()
    {
        $expected = 'expected';

        $this->lineItem->setTag($expected);

        $this->assertEquals($expected, $this->lineItem->getTag());
    }

    public function testItGetsStartDateTime()
    {
        $expected = 'expected';
        $grade = new LtiLineitem([ 'startDateTime' => $expected ]);

        $result = $grade->getStartDateTime();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsStartDateTime()
    {
        $expected = 'expected';

        $this->lineItem->setStartDateTime($expected);

        $this->assertEquals($expected, $this->lineItem->getStartDateTime());
    }

    public function testItGetsEndDateTime()
    {
        $expected = 'expected';
        $grade = new LtiLineitem([ 'endDateTime' => $expected ]);

        $result = $grade->getEndDateTime();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsEndDateTime()
    {
        $expected = 'expected';

        $this->lineItem->setEndDateTime($expected);

        $this->assertEquals($expected, $this->lineItem->getEndDateTime());
    }


    public function testItCastsFullObjectToString()
    {
        $expected = [
            'id' => 'Id',
            'scoreMaximum' => 'ScoreMaximum',
            'label' => 'Label',
            'resourceId' => 'ResourceId',
            'tag' => 'Tag',
            'startDateTime' => 'StartDateTime',
            'endDateTime' => 'EndDateTime',
        ];

        $lineItem = new LtiLineitem($expected);

        $this->assertEquals(json_encode($expected), (string) $lineItem);
    }

    public function testItCastsEmptyObjectToString()
    {
        $this->assertEquals('[]', (string) $this->lineItem);
    }
}
