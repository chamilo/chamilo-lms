<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use CardGame;
use Chamilo\CoreBundle\Framework\Container;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for the CardGame plugin progress storage (table plugin_card_game): getOrCreateProgress(),
 * revealPart() and markLoss(), which read, create and update the one row of a user.
 */
final class CardGameTier3CharacterizationTest extends KernelTestCase
{
    use PluginTier3SchemaTrait;

    private const USER = 987001;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::ensurePluginTable('CardGame', 'plugin_card_game');
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new HttpSession(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/plugin/CardGame/index.php');
        $request->setSession($session);
        $request->setLocale('en_US');
        Container::setRequest($request);

        require_once api_get_path(SYS_PLUGIN_PATH).'CardGame/CardGame.php';
    }

    public function testFirstReadCreatesTheRowPlayedYesterdayWithPanOneAndNoParts(): void
    {
        $progress = CardGame::create()->getOrCreateProgress(self::USER);

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $this->assertSame(self::USER, $progress['user_id']);
        $this->assertSame(1, $progress['pan']);
        $this->assertSame($yesterday, $progress['access_date']);
        $this->assertSame([], $progress['parts']);
        $this->assertGreaterThan(0, $progress['id']);
        $this->assertSame(1, $this->rowCount());
        $row = $this->row();
        $this->assertSame($yesterday, $row['access_date']);
        $this->assertSame('', $row['parts']);
        $this->assertSame('1', (string) $row['pan']);
        $this->assertTrue(CardGame::create()->canPlayToday($progress));
    }

    public function testSecondReadReusesTheRowAndTheLatestRowOfTheUserWins(): void
    {
        $game = CardGame::create();
        $first = $game->getOrCreateProgress(self::USER);
        $this->assertSame($first['id'], $game->getOrCreateProgress(self::USER)['id']);
        $this->assertSame(1, $this->rowCount());

        $this->conn()->executeStatement("INSERT INTO plugin_card_game (user_id, pan, access_date, parts) VALUES (?, 3, '2030-01-02', '2,4')", [self::USER]);
        $latest = $game->getOrCreateProgress(self::USER);
        $this->assertGreaterThan($first['id'], $latest['id']);
        $this->assertSame(3, $latest['pan']);
        $this->assertSame('2030-01-02', $latest['access_date']);
        $this->assertSame([2, 4], $latest['parts']);
    }

    public function testOtherUsersRowsAreNotTouched(): void
    {
        $game = CardGame::create();
        $this->conn()->executeStatement("INSERT INTO plugin_card_game (user_id, pan, access_date, parts) VALUES (?, 2, '2030-01-02', '5')", [self::USER + 1]);
        $game->getOrCreateProgress(self::USER);
        $game->revealPart(self::USER, 3);

        $other = $this->conn()->fetchAssociative('SELECT pan, access_date, parts FROM plugin_card_game WHERE user_id = ?', [self::USER + 1]);
        $this->assertSame('2', (string) $other['pan']);
        $this->assertSame('2030-01-02', $other['access_date']);
        $this->assertSame('5', $other['parts']);
    }

    public function testMessyPartsAreNormalizedAndWrittenBack(): void
    {
        $this->conn()->executeStatement("INSERT INTO plugin_card_game (user_id, pan, access_date, parts) VALUES (?, 0, NULL, '9, 3;3|99')", [self::USER]);

        $progress = CardGame::create()->getOrCreateProgress(self::USER);

        $this->assertSame(1, $progress['pan'], 'A pan below 1 reads as 1');
        $this->assertNull($progress['access_date']);
        $this->assertSame([3, 9], $progress['parts']);
        $row = $this->row();
        $this->assertSame('3,9', $row['parts']);
        $this->assertNull($row['access_date'], 'A NULL access date stays NULL');
        $this->assertSame('1', (string) $row['pan'], 'The normalized pan is stored back');
        $this->assertTrue(CardGame::create()->canPlayToday($progress), 'Never played: can play today');
    }

    public function testRevealPartStoresThePartAndTodayThenBlocksASecondPlay(): void
    {
        $game = CardGame::create();
        $result = $game->revealPart(self::USER, 7);

        $this->assertTrue($result['success']);
        $this->assertSame([7], $result['parts']);
        $this->assertSame(7, $result['revealedPart']);
        $row = $this->row();
        $this->assertSame(date('Y-m-d'), $row['access_date']);
        $this->assertSame('7', $row['parts']);

        $again = $game->revealPart(self::USER, 8);
        $this->assertTrue($again['alreadyPlayed']);
        $this->assertFalse($again['success']);
        $this->assertSame('7', $this->row()['parts'], 'A second play the same day does not write');
    }

    public function testRevealPartKeepsPartsSortedAndRejectsADuplicate(): void
    {
        $this->conn()->executeStatement("INSERT INTO plugin_card_game (user_id, pan, access_date, parts) VALUES (?, 2, '2030-01-02', '9,3')", [self::USER]);

        $result = CardGame::create()->revealPart(self::USER, 5);
        $this->assertSame([3, 5, 9], $result['parts']);
        $this->assertSame('3,5,9', $this->row()['parts']);

        $this->conn()->executeStatement("UPDATE plugin_card_game SET access_date = '2030-01-02' WHERE user_id = ?", [self::USER]);
        $duplicate = CardGame::create()->revealPart(self::USER, 5);
        $this->assertTrue($duplicate['duplicatePart']);
        $this->assertFalse($duplicate['success']);
        $this->assertSame(date('Y-m-d'), $this->row()['access_date'], 'A duplicate still uses up the day');
        $this->assertSame('3,5,9', $this->row()['parts']);
    }

    public function testCompletingAPanAdvancesItAndResetsTheParts(): void
    {
        $all = implode(',', range(1, 14));
        $this->conn()->executeStatement("INSERT INTO plugin_card_game (user_id, pan, access_date, parts) VALUES (?, 2, '2030-01-02', ?)", [self::USER, $all]);

        $progress = CardGame::create()->getOrCreateProgress(self::USER);
        $missing = CardGame::TOTAL_PARTS;
        $parts = array_values(array_diff(range(1, CardGame::TOTAL_PARTS), [$missing]));
        $this->conn()->executeStatement('UPDATE plugin_card_game SET parts = ? WHERE user_id = ?', [implode(',', $parts), self::USER]);

        $result = CardGame::create()->revealPart(self::USER, $missing);

        $this->assertTrue($result['completedPan']);
        $this->assertSame(3, $result['pan']);
        $this->assertSame([], $result['parts']);
        $row = $this->row();
        $this->assertSame('3', (string) $row['pan']);
        $this->assertSame('', $row['parts']);
        $this->assertSame(date('Y-m-d'), $row['access_date']);
        $this->assertSame(2, $progress['pan']);
    }

    public function testMarkLossUsesTheDayWithoutChangingPanOrParts(): void
    {
        $this->conn()->executeStatement("INSERT INTO plugin_card_game (user_id, pan, access_date, parts) VALUES (?, 4, '2030-01-02', '1,2')", [self::USER]);

        $result = CardGame::create()->markLoss(self::USER);

        $this->assertTrue($result['success']);
        $this->assertSame(4, $result['pan']);
        $row = $this->row();
        $this->assertSame(date('Y-m-d'), $row['access_date']);
        $this->assertSame('1,2', $row['parts']);
        $this->assertSame('4', (string) $row['pan']);

        $again = CardGame::create()->markLoss(self::USER);
        $this->assertTrue($again['alreadyPlayed']);
    }

    private function conn(): Connection
    {
        return self::getContainer()->get('doctrine')->getConnection();
    }

    private function rowCount(): int
    {
        return (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM plugin_card_game WHERE user_id = ?', [self::USER]);
    }

    private function row(): array
    {
        return $this->conn()->fetchAssociative('SELECT * FROM plugin_card_game WHERE user_id = ? ORDER BY id DESC LIMIT 1', [self::USER]);
    }
}
