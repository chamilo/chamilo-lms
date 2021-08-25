<?php namespace Tests;

use PHPUnit\Framework\TestCase;

use Packback\Lti1p3\LtiGrade;

class LtiGradeTest extends TestCase
{
    public function setUp(): void
    {
        $this->grade = new LtiGrade;
    }

    public function testItInstantiates()
    {
        $this->assertInstanceOf(LtiGrade::class, $this->grade);
    }

    public function testItCreatesANewInstance()
    {
        $grade = LtiGrade::new();

        $this->assertInstanceOf(LtiGrade::class, $grade);
    }

    public function testItGetsScoreGiven()
    {
        $expected = 'expected';
        $grade = new LtiGrade([ 'scoreGiven' => $expected ]);

        $result = $grade->getScoreGiven();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsScoreGiven()
    {
        $expected = 'expected';

        $this->grade->setScoreGiven($expected);

        $this->assertEquals($expected, $this->grade->getScoreGiven());
    }

    public function testItGetsScoreMaximum()
    {
        $expected = 'expected';
        $grade = new LtiGrade([ 'scoreMaximum' => $expected ]);

        $result = $grade->getScoreMaximum();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsScoreMaximum()
    {
        $expected = 'expected';

        $this->grade->setScoreMaximum($expected);

        $this->assertEquals($expected, $this->grade->getScoreMaximum());
    }

    public function testItGetsComment()
    {
        $expected = 'expected';
        $grade = new LtiGrade([ 'comment' => $expected ]);

        $result = $grade->getComment();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsComment()
    {
        $expected = 'expected';

        $this->grade->setComment($expected);

        $this->assertEquals($expected, $this->grade->getComment());
    }

    public function testItGetsActivityProgress()
    {
        $expected = 'expected';
        $grade = new LtiGrade([ 'activityProgress' => $expected ]);

        $result = $grade->getActivityProgress();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsActivityProgress()
    {
        $expected = 'expected';

        $this->grade->setActivityProgress($expected);

        $this->assertEquals($expected, $this->grade->getActivityProgress());
    }

    public function testItGetsGradingProgress()
    {
        $expected = 'expected';
        $grade = new LtiGrade([ 'gradingProgress' => $expected ]);

        $result = $grade->getGradingProgress();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsGradingProgress()
    {
        $expected = 'expected';

        $this->grade->setGradingProgress($expected);

        $this->assertEquals($expected, $this->grade->getGradingProgress());
    }

    public function testItGetsTimestamp()
    {
        $expected = 'expected';
        $grade = new LtiGrade([ 'timestamp' => $expected ]);

        $result = $grade->getTimestamp();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsTimestamp()
    {
        $expected = 'expected';

        $this->grade->setTimestamp($expected);

        $this->assertEquals($expected, $this->grade->getTimestamp());
    }

    public function testItGetsUserId()
    {
        $expected = 'expected';
        $grade = new LtiGrade([ 'userId' => $expected ]);

        $result = $grade->getUserId();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsUserId()
    {
        $expected = 'expected';

        $this->grade->setUserId($expected);

        $this->assertEquals($expected, $this->grade->getUserId());
    }

    public function testItGetsSubmissionReview()
    {
        $expected = 'expected';
        $grade = new LtiGrade([ 'submissionReview' => $expected ]);

        $result = $grade->getSubmissionReview();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsSubmissionReview()
    {
        $expected = 'expected';

        $this->grade->setSubmissionReview($expected);

        $this->assertEquals($expected, $this->grade->getSubmissionReview());
    }

    public function testItGetsCanvasExtension()
    {
        $expected = 'expected';
        $grade = new LtiGrade([ 'https://canvas.instructure.com/lti/submission' => $expected ]);

        $result = $grade->getCanvasExtension();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsCanvasExtension()
    {
        $expected = 'expected';

        $this->grade->setCanvasExtension($expected);

        $this->assertEquals($expected, $this->grade->getCanvasExtension());
    }

    public function testItCastsFullObjectToString()
    {
        $expected = [
            'scoreGiven' => 5,
            'scoreMaximum' => 10,
            'comment' => 'Comment',
            'activityProgress' => 'ActivityProgress',
            'gradingProgress' => 'GradingProgress',
            'timestamp' => 'Timestamp',
            'userId' => 'UserId',
            'submissionReview' => 'SubmissionReview',
            'https://canvas.instructure.com/lti/submission' => 'CanvasExtension'
        ];

        $grade = new LtiGrade($expected);

        $this->assertEquals(json_encode($expected), (string) $grade);
    }

    public function testItCastsEmptyObjectToString()
    {
        $this->assertEquals('[]', (string) $this->grade);
    }
}
