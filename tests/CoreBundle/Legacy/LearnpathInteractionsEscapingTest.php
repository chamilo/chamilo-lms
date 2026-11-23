<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use learnpath;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * SCORM interactions and objectives are written by the learner's SCORM runtime and rendered as HTML in the
 * learning path tracking reports, so the getters feeding those reports must return them HTML-escaped.
 */
final class LearnpathInteractionsEscapingTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE_ID = 1;
    // c_lp_iv_interaction/c_lp_iv_objective have no foreign key on lp_iv_id.
    private const ITEM_VIEW_ID = 987654321;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/lp/lp_controller.php');
        $request->setSession($session);
        Container::setRequest($request);
    }

    public function testInteractionValuesAreEscaped(): void
    {
        Database::getManager()->getConnection()->executeStatement(
            "INSERT INTO c_lp_iv_interaction (c_id, order_id, lp_iv_id, interaction_id, interaction_type, weighting,
                completion_time, correct_responses, student_response, result, latency)
             VALUES (?, 0, ?, ?, ?, 0, ?, '', ?, ?, ?)",
            [
                self::COURSE_ID,
                self::ITEM_VIEW_ID,
                '%3Cb%3Eid%3C%2Fb%3E',
                '<i>type</i>',
                '<u>t</u>',
                '%3Cimg src=x onerror=alert(1)%3E',
                '<s>wrong</s>',
                '<q>PT1S',
            ]
        );

        $list = learnpath::get_iv_interactions_array(self::ITEM_VIEW_ID, self::COURSE_ID);

        $this->assertCount(2, $list);
        $interaction = $list[1];
        $this->assertSame('&lt;b&gt;id&lt;/b&gt;', $interaction['id']);
        $this->assertSame('&lt;i&gt;type&lt;/i&gt;', $interaction['type']);
        $this->assertSame('&lt;u&gt;t&lt;/u&gt;', $interaction['time']);
        $this->assertSame('&lt;img src=x onerror=alert(1)&gt;', $interaction['student_response_formatted']);
        $this->assertSame('&lt;s&gt;wrong&lt;/s&gt;', $interaction['result']);
        $this->assertSame('&lt;q&gt;PT1S', $interaction['latency']);
        // Kept raw on purpose: the single attempt view escapes it after urldecode(), so escaping it here too
        // would show entities to the teacher.
        $this->assertSame('%3Cimg src=x onerror=alert(1)%3E', $interaction['student_response']);
    }

    public function testPlainInteractionResponseIsKept(): void
    {
        Database::getManager()->getConnection()->executeStatement(
            "INSERT INTO c_lp_iv_interaction (c_id, order_id, lp_iv_id, interaction_id, interaction_type, weighting,
                completion_time, correct_responses, student_response, result, latency)
             VALUES (?, 0, ?, 'q1', 'choice', 0, '10:00:00', '', 'a__|b__|c', 'correct', 'PT1S')",
            [self::COURSE_ID, self::ITEM_VIEW_ID]
        );

        $list = learnpath::get_iv_interactions_array(self::ITEM_VIEW_ID, self::COURSE_ID);

        $this->assertSame('a,b', $list[1]['student_response_formatted']);
        $this->assertSame('choice', $list[1]['type']);
        $this->assertSame('correct', $list[1]['result']);
    }

    public function testObjectiveValuesAreEscaped(): void
    {
        Database::getManager()->getConnection()->executeStatement(
            'INSERT INTO c_lp_iv_objective (c_id, lp_iv_id, order_id, objective_id, score_raw, score_max, score_min, status)
             VALUES (?, ?, 0, ?, 0, 0, 0, ?)',
            [self::COURSE_ID, self::ITEM_VIEW_ID, '%3Cb%3Eobj%3C%2Fb%3E', '<q>passed']
        );

        $list = learnpath::get_iv_objectives_array(self::ITEM_VIEW_ID, self::COURSE_ID);

        $this->assertCount(2, $list);
        $this->assertSame('&lt;b&gt;obj&lt;/b&gt;', $list[1]['objective_id']);
        $this->assertSame('&lt;q&gt;passed', $list[1]['status']);
    }
}
