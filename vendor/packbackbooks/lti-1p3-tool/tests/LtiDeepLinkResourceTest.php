<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use Mockery;

use Packback\Lti1p3\LtiLineitem;
use Packback\Lti1p3\LtiDeepLinkResource;

class LtiDeepLinkResourceTest extends TestCase
{
    public function setUp(): void
    {
        $this->deepLinkResource = new LtiDeepLinkResource();
    }

    public function testItInstantiates()
    {
        $this->assertInstanceOf(LtiDeepLinkResource::class, $this->deepLinkResource);
    }

    public function testItCreatesANewInstance()
    {
        $deepLinkResource = LtiDeepLinkResource::new();

        $this->assertInstanceOf(LtiDeepLinkResource::class, $deepLinkResource);
    }

    public function testItGetsType()
    {
        $result = $this->deepLinkResource->getType();

        $this->assertEquals('ltiResourceLink', $result);
    }

    public function testItSetsType()
    {
        $expected = 'expected';

        $this->deepLinkResource->setType($expected);

        $this->assertEquals($expected, $this->deepLinkResource->getType());
    }

    public function testItGetsTitle()
    {
        $result = $this->deepLinkResource->getTitle();

        $this->assertNull($result);
    }

    public function testItSetsTitle()
    {
        $expected = 'expected';

        $this->deepLinkResource->setTitle($expected);

        $this->assertEquals($expected, $this->deepLinkResource->getTitle());
    }

    public function testItGetsText()
    {
        $result = $this->deepLinkResource->getText();

        $this->assertNull($result);
    }

    public function testItSetsText()
    {
        $expected = 'expected';

        $this->deepLinkResource->setText($expected);

        $this->assertEquals($expected, $this->deepLinkResource->getText());
    }

    public function testItGetsUrl()
    {
        $result = $this->deepLinkResource->getUrl();

        $this->assertNull($result);
    }

    public function testItSetsUrl()
    {
        $expected = 'expected';

        $this->deepLinkResource->setUrl($expected);

        $this->assertEquals($expected, $this->deepLinkResource->getUrl());
    }

    public function testItGetsLineitem()
    {
        $result = $this->deepLinkResource->getLineitem();

        $this->assertNull($result);
    }

    public function testItSetsLineitem()
    {
        $expected = Mockery::mock(LtiLineitem::class);

        $this->deepLinkResource->setLineitem($expected);

        $this->assertEquals($expected, $this->deepLinkResource->getLineitem());
    }

    public function testItGetsCustomParams()
    {
        $result = $this->deepLinkResource->getCustomParams();

        $this->assertEquals([], $result);
    }

    public function testItSetsCustomParams()
    {
        $expected = 'expected';

        $this->deepLinkResource->setCustomParams($expected);

        $this->assertEquals($expected, $this->deepLinkResource->getCustomParams());
    }

    public function testItGetsTarget()
    {
        $result = $this->deepLinkResource->getTarget();

        $this->assertEquals('iframe', $result);
    }

    public function testItSetsTarget()
    {
        $expected = 'expected';

        $this->deepLinkResource->setTarget($expected);

        $this->assertEquals($expected, $this->deepLinkResource->getTarget());
    }

    public function testItCastsToArray()
    {
        $expected = [
            "type" => 'ltiResourceLink',
            "title" => 'a_title',
            "text" => 'a_text',
            "url" => 'a_url',
            "presentation" => [
                "documentTarget" => 'iframe',
            ],
            "custom" => [],
            "lineItem" => [
                "scoreMaximum" => 80,
                "label" => 'lineitem_label',
            ]
        ];
        $lineitem = Mockery::mock(LtiLineitem::class);
        $lineitem->shouldReceive('getScoreMaximum')
            ->once()->andReturn($expected['lineItem']['scoreMaximum']);
        $lineitem->shouldReceive('getLabel')
            ->once()->andReturn($expected['lineItem']['label']);

        $this->deepLinkResource->setTitle($expected['title']);
        $this->deepLinkResource->setText($expected['text']);
        $this->deepLinkResource->setUrl($expected['url']);
        $this->deepLinkResource->setLineitem($lineitem);

        $result = $this->deepLinkResource->toArray();

        $this->assertEquals($expected, $result);
    }
}
