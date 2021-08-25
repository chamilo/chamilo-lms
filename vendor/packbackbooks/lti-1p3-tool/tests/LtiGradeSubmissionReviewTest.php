<?php namespace Tests;

use PHPUnit\Framework\TestCase;

use Packback\Lti1p3\LtiGradeSubmissionReview;

class LtiGradeSubmissionReviewTest extends TestCase
{
    public function setUp(): void
    {
        $this->gradeReview = new LtiGradeSubmissionReview;
    }

    public function testItInstantiates()
    {
        $this->assertInstanceOf(LtiGradeSubmissionReview::class, $this->gradeReview);
    }

    public function testItGetsReviewableStatus()
    {
        $expected = 'ReviewableStatus';
        $gradeReview = new LtiGradeSubmissionReview(['reviewableStatus' => 'ReviewableStatus']);

        $result = $gradeReview->getReviewableStatus();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsReviewableStatus()
    {
        $expected = 'expected';

        $this->gradeReview->setReviewableStatus($expected);

        $this->assertEquals($expected, $this->gradeReview->getReviewableStatus());
    }

    public function testItGetsLabel()
    {
        $expected = 'Label';
        $gradeReview = new LtiGradeSubmissionReview(['label' => 'Label']);

        $result = $gradeReview->getLabel();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsLabel()
    {
        $expected = 'expected';

        $this->gradeReview->setLabel($expected);

        $this->assertEquals($expected, $this->gradeReview->getLabel());
    }

    public function testItGetsUrl()
    {
        $expected = 'Url';
        $gradeReview = new LtiGradeSubmissionReview(['url' => 'Url']);

        $result = $gradeReview->getUrl();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsUrl()
    {
        $expected = 'expected';

        $this->gradeReview->setUrl($expected);

        $this->assertEquals($expected, $this->gradeReview->getUrl());
    }

    public function testItGetsCustom()
    {
        $expected = 'Custom';
        $gradeReview = new LtiGradeSubmissionReview(['custom' => 'Custom']);

        $result = $gradeReview->getCustom();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsCustom()
    {
        $expected = 'expected';

        $this->gradeReview->setCustom($expected);

        $this->assertEquals($expected, $this->gradeReview->getCustom());
    }

    public function testItCastsFullObjectToString()
    {
        $expected = [
            'reviewableStatus' => 'ReviewableStatus',
            'label' => 'Label',
            'url' => 'Url',
            'custom' => 'Custom',
        ];

        $gradeReview = new LtiGradeSubmissionReview($expected);

        $this->assertEquals(json_encode($expected), (string) $gradeReview);
    }

    public function testItCastsEmptyObjectToString()
    {
        $this->assertEquals('[]', (string) $this->gradeReview);
    }
}
