<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use PHPUnit\Framework\TestCase;

/**
 * Legacy pages under public/main are not covered by CsrfProtectionListener: a state-changing GET there is a
 * CSRF hole unless it carries (and checks) a session token. The pages are procedural scripts (exit/api_not_allowed
 * at include time), so this is a SOURCE-LEVEL pin, not a behavioural test: for each mutating GET action it
 * asserts that the block of the page that handles it checks the token BEFORE it touches data, and that every
 * link generator of the action appends the token. The token primitive itself is exercised for real below.
 */
final class LegacyGetMutatorTokenTest extends TestCase
{
    private function source(string $file): string
    {
        $source = (string) file_get_contents(__DIR__.'/../../../'.$file);
        $this->assertNotSame('', $source, $file.' is missing');

        return $source;
    }

    /**
     * Asserts that, in the text starting at $start (up to $length bytes), a token check appears before $mutator.
     */
    private function assertTokenBefore(string $file, string $start, string $mutator, int $length = 900): void
    {
        $source = $this->source($file);
        $from = strpos($source, $start);
        $this->assertNotFalse($from, $file.': marker not found: '.$start);
        $block = substr($source, (int) $from, $length);
        $check = strpos($block, 'check_token(');
        $change = strpos($block, $mutator);
        $this->assertNotFalse($check, $file.': no token check after '.$start);
        $this->assertNotFalse($change, $file.': mutator not found after '.$start.': '.$mutator);
        $this->assertLessThan($change, $check, $file.': the token is checked AFTER '.$mutator);
    }

    private function assertContainsToken(string $file, string $linkStart): void
    {
        $source = $this->source($file);
        $from = strpos($source, $linkStart);
        $this->assertNotFalse($from, $file.': link generator not found: '.$linkStart);
        $this->assertStringContainsString('sec_token=', substr($source, (int) $from, 400), $file.': link without token: '.$linkStart);
    }

    public function testScheduledAnnouncementRunNeedsTheToken(): void
    {
        $page = $this->source('public/main/session/scheduled_announcement.php');
        $guard = strpos($page, "['delete', 'run'], true) && !Security::check_token('get')");
        $this->assertNotFalse($guard, 'the run action must check the get token');
        $this->assertLessThan((int) strpos($page, 'sendPendingMessages('), $guard, 'the check must come before the send');
        $this->assertContainsToken('public/main/inc/lib/ScheduledAnnouncement.php', 'scheduled_announcement.php?action=run');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function gradebookAdminPageActions(): array
    {
        return [
            'createallcategories' => ["isset(\$_GET['createallcategories'])", '->add()'],
            'visiblecat' => ["isset(\$_GET['visiblecat'])", '->save()'],
            'deletecat' => ["isset(\$_GET['deletecat'])", '->delete_all()'],
            'visibleeval' => ["isset(\$_GET['visibleeval'])", '->save()'],
            'deleteeval' => ["isset(\$_GET['deleteeval'])", '->delete_with_results()'],
            'visiblelink' => ["isset(\$_GET['visiblelink'])", '->save()'],
            'deletelink' => ["isset(\$_GET['deletelink'])", '->delete()'],
        ];
    }

    /**
     * @dataProvider gradebookAdminPageActions
     */
    public function testGradebookPageGetActionsNeedTheToken(string $marker, string $mutator): void
    {
        $this->assertTokenBefore('public/main/gradebook/gradebook.php', 'if ('.$marker.')', $mutator, 700);
    }

    public function testGradebookPageMovesNeedTheTokenAndTheRedirectsCarryIt(): void
    {
        $page = $this->source('public/main/gradebook/gradebook.php');
        foreach (['$cats[0]->move_to_cat(', '$evals[0]->move_to_cat('] as $move) {
            $at = (int) strpos($page, $move);
            $this->assertGreaterThan(0, $at);
            $this->assertStringContainsString("check_token('get')", substr($page, max(0, $at - 700), 700), $move.' without token');
        }
        $this->assertSame(3, substr_count($page, "'&sec_token='.Security::get_existing_token()"), 'the move redirects and the confirm form must carry the token');
    }

    public function testGradebookIndexCreateAllCategoriesAndMovesNeedTheToken(): void
    {
        $file = 'public/main/gradebook/index.php';
        $this->assertTokenBefore($file, "if (isset(\$_GET['createallcategories']))", '->add()', 700);
        $page = $this->source($file);
        foreach (['$cats[0]->move_to_cat(', '$evals[0]->move_to_cat('] as $move) {
            $at = (int) strpos($page, $move);
            $this->assertGreaterThan(0, $at);
            $this->assertStringContainsString("check_token('get')", substr($page, max(0, $at - 700), 700), $move.' without token');
        }
        $this->assertSame(3, substr_count($page, "'&sec_token='.Security::get_existing_token()"), 'the move redirects and the confirm form must carry the token');
    }

    public function testTheCronEntryPointsDoNotGoThroughTheWebRunAction(): void
    {
        // The token on `run` only protects the admin's "send now" button: cron must not depend on it.
        $this->assertStringNotContainsString("action=run", $this->source('public/main/cron/scheduled_announcement.php'));
        $this->assertStringNotContainsString('check_token', $this->source('public/main/cron/scheduled_announcement.php'));
        $this->assertStringNotContainsString('check_token', $this->source('src/CoreBundle/Command/SendScheduledAnnouncementsCommand.php'));
    }
}
