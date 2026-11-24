<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use BuyCoursesNanoAddress;
use BuyCoursesNanoAmount;
use BuyCoursesNanoBinding;
use BuyCoursesNanoBlake2b;
use BuyCoursesNanoClaims;
use BuyCoursesNanoClient;
use BuyCoursesNanoVerifier;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../public/plugin/BuyCourses/src/nano_client.php';

/**
 * The Nano (XNO) gateway of BuyCourses: address checksum, raw amounts derived from the sale price, the binding of
 * a send block to one sale, the block checks, and the claim table that refuses a replayed block after a restart.
 * Everything here runs without a database server and without the network (the claim table is SQLite on disk).
 */
final class BuyCoursesNanoPaymentTest extends TestCase
{
    // Published vectors: the genesis account and the all-zero account, with their known public keys.
    private const GENESIS = 'nano_3t6k35gi95xu6tergt6p69ck76ogmitsa8mnijtpxm9fkcm736xtoncuohr3';
    private const GENESIS_KEY = 'E89208DD038FBB269987689621D52292AE9C35941A7484756ECCED92A65093BA';
    private const BURN = 'nano_1111111111111111111111111111111111111111111111111111hifc8npp';
    private const SRC = __DIR__.'/../../../public/plugin/BuyCourses/src/';

    private string $claimsFile = '';

    protected function tearDown(): void
    {
        if ('' !== $this->claimsFile && is_file($this->claimsFile)) {
            unlink($this->claimsFile);
        }
    }

    public function testBlake2bMatchesTheRfc7693VectorForAbc(): void
    {
        self::assertSame(
            'ba80a53f981c4d0d6a2797b69f12f6e94c212f14685ac4b74b12bb6fdbffa2d1'
            .'7d87c5392aab792dc252d5de4533cc9518d38aa8dbf1925ab92386edd4009923',
            bin2hex(BuyCoursesNanoBlake2b::hash('abc', 64))
        );
    }

    public function testAddressDecodesToItsPublishedKeyAndBack(): void
    {
        self::assertSame(self::GENESIS_KEY, BuyCoursesNanoAddress::publicKey(self::GENESIS));
        self::assertSame(str_repeat('0', 64), BuyCoursesNanoAddress::publicKey(self::BURN));
        self::assertSame(self::GENESIS, BuyCoursesNanoAddress::fromPublicKey(self::GENESIS_KEY));
        self::assertTrue(BuyCoursesNanoAddress::matches(self::GENESIS, 'xrb_'.substr(self::GENESIS, 5)));
    }

    public function testAddressWithABadChecksumOrShapeIsRefused(): void
    {
        $lastChanged = substr(self::GENESIS, 0, -1).('3' === substr(self::GENESIS, -1) ? '4' : '3');
        self::assertFalse(BuyCoursesNanoAddress::isValid($lastChanged));
        self::assertFalse(BuyCoursesNanoAddress::isValid(''));
        self::assertFalse(BuyCoursesNanoAddress::isValid('nano_'));
        self::assertFalse(BuyCoursesNanoAddress::isValid(substr(self::GENESIS, 0, -1)));
        self::assertFalse(BuyCoursesNanoAddress::isValid('btc_'.substr(self::GENESIS, 5)));
    }

    public function testRawIsDerivedFromThePriceAndTheRateWithoutFloats(): void
    {
        self::assertSame('1000000000000000000000000000000', BuyCoursesNanoAmount::xnoToRaw('1'));
        self::assertSame('500000000000000000000000000000', BuyCoursesNanoAmount::xnoToRaw('0.5'));
        // 19.99 at 1.25 XNO per unit = 24.9875 XNO.
        self::assertSame(BuyCoursesNanoAmount::xnoToRaw('24.9875'), BuyCoursesNanoAmount::rawForCents(1999, '1.25'));
        // 0.01 at the finest rate allowed (28 decimals) is exactly one raw.
        self::assertSame('1', BuyCoursesNanoAmount::rawForCents(1, '0.'.str_repeat('0', 27).'1'));
        self::assertSame('24.9875', BuyCoursesNanoAmount::rawToXno(BuyCoursesNanoAmount::rawForCents(1999, '1.25')));
    }

    public function testTheSaleRawUsesTheSameCentsAsTheOtherGateways(): void
    {
        $client = new BuyCoursesNanoClient(self::GENESIS, 'https://node.example.org', '2');
        // 19.99 * 100 is 1998.9999999999998 as a float; the cents are still 1999.
        self::assertSame(BuyCoursesNanoAmount::xnoToRaw('39.98'), $client->rawFor(['price' => '19.99']));
        self::assertSame(BuyCoursesNanoAmount::xnoToRaw('39.98'), $client->rawFor(['price' => 19.99]));
    }

    public function testRatesAndAmountsThatCannotBeExactAreRefused(): void
    {
        self::assertFalse(BuyCoursesNanoAmount::isValidRate('0'));
        self::assertFalse(BuyCoursesNanoAmount::isValidRate('-1'));
        self::assertFalse(BuyCoursesNanoAmount::isValidRate('1e3'));
        self::assertFalse(BuyCoursesNanoAmount::isValidRate('0.'.str_repeat('0', 28).'1'));
        self::assertTrue(BuyCoursesNanoAmount::isValidRate('0.08'));

        $this->expectException(InvalidArgumentException::class);
        BuyCoursesNanoAmount::rawForCents(0, '1');
    }

    public function testAnAmountFinerThanOneRawIsRefusedNotTruncated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        BuyCoursesNanoAmount::xnoToRaw('0.'.str_repeat('0', 30).'1');
    }

    public function testTheRepresentativeBindsOneSaleAtOnePrice(): void
    {
        $raw = BuyCoursesNanoAmount::xnoToRaw('0.5');
        $sale = ['id' => 7, 'reference' => 'ABC123'];
        $key = BuyCoursesNanoBinding::saleKey($sale);
        $rep = BuyCoursesNanoBinding::representativeAccount($key, self::GENESIS, $raw);

        self::assertTrue(BuyCoursesNanoAddress::isValid($rep));
        self::assertSame($rep, BuyCoursesNanoBinding::representativeAccount($key, self::GENESIS, $raw));
        self::assertNotSame($rep, BuyCoursesNanoBinding::representativeAccount(
            BuyCoursesNanoBinding::saleKey(['id' => 8, 'reference' => 'ABC123']),
            self::GENESIS,
            $raw
        ));
        self::assertNotSame($rep, BuyCoursesNanoBinding::representativeAccount($key, self::GENESIS, BuyCoursesNanoAmount::xnoToRaw('0.6')));
        self::assertNotSame($rep, BuyCoursesNanoBinding::representativeAccount($key, self::BURN, $raw));
    }

    public function testAConfirmedBoundSendOfThePriceToTheAccountIsAccepted(): void
    {
        [$client, $sale] = $this->clientAndSale();

        self::assertSame('', $client->refusalFor($sale, $this->block($client, $sale)));

        // Paying more than the price is accepted (the buyer's wallet may round up).
        $more = $this->block($client, $sale, ['amount' => BuyCoursesNanoAmount::xnoToRaw('25')]);
        self::assertSame('', $client->refusalFor($sale, $more));
    }

    public function testEveryOtherBlockIsRefused(): void
    {
        [$client, $sale] = $this->clientAndSale();
        $rep = $client->representativeFor($sale);
        $otherSaleRep = $client->representativeFor(['id' => 43, 'reference' => 'REF-42', 'price' => '19.99']);

        $cases = [
            'unconfirmed' => ['confirmed' => 'false'],
            'confirmed missing' => ['confirmed' => null],
            'a receive' => ['subtype' => 'receive'],
            'one raw short' => ['amount' => '24987499999999999999999999999999'],
            'a float amount' => ['amount' => '24.9875'],
            'another destination' => ['contents' => ['representative' => $rep, 'link_as_account' => self::BURN]],
            'bound to another sale' => ['contents' => ['representative' => $otherSaleRep, 'link_as_account' => self::GENESIS]],
            'no representative' => ['contents' => ['link_as_account' => self::GENESIS]],
        ];

        foreach ($cases as $name => $override) {
            self::assertNotSame('', $client->refusalFor($sale, $this->block($client, $sale, $override)), $name);
        }
        self::assertNotSame('', BuyCoursesNanoVerifier::refusal([], self::GENESIS, '1', $rep));
    }

    public function testAClaimedBlockIsRefusedForAnotherSaleEvenAfterARestart(): void
    {
        $this->claimsFile = (string) tempnam(sys_get_temp_dir(), 'bcnano');
        $hash = str_repeat('A', 64);

        $claims = new BuyCoursesNanoClaims($this->openClaims(true), 'nano_claim');
        self::assertTrue($claims->claim($hash, 1));
        // The same sale reloading its page keeps its claim; another sale cannot use the block.
        self::assertTrue($claims->claim(strtolower($hash), 1));
        self::assertFalse($claims->claim($hash, 2));

        // A new connection to the same file: the claim survived the process.
        $afterRestart = new BuyCoursesNanoClaims($this->openClaims(false), 'nano_claim');
        self::assertFalse($afterRestart->claim($hash, 2));
        // A sale already paid by one block cannot be paid by a second one.
        self::assertFalse($afterRestart->claim(str_repeat('B', 64), 1));
        self::assertTrue($afterRestart->claim(str_repeat('B', 64), 2));
        // Malformed input never becomes a claim.
        self::assertFalse($afterRestart->claim('not-a-hash', 3));
        self::assertFalse($afterRestart->claim(str_repeat('C', 64), 0));
    }

    public function testTheGatewayIsOffUntilEverySettingIsValid(): void
    {
        $valid = ['account' => self::GENESIS, 'node_url' => 'https://node.example.org', 'xno_per_unit' => '1.25'];

        self::assertTrue(BuyCoursesNanoClient::isConfiguredParams($valid));
        self::assertFalse(BuyCoursesNanoClient::isConfiguredParams([]));
        self::assertFalse(BuyCoursesNanoClient::isConfiguredParams(['account' => '', 'node_url' => '', 'xno_per_unit' => '']));
        self::assertFalse(BuyCoursesNanoClient::isConfiguredParams(array_merge($valid, ['account' => substr(self::GENESIS, 0, -1).'1'])));
        self::assertFalse(BuyCoursesNanoClient::isConfiguredParams(array_merge($valid, ['xno_per_unit' => '0'])));

        self::assertTrue(BuyCoursesNanoClient::isValidNodeUrl('http://127.0.0.1:7076'));
        self::assertFalse(BuyCoursesNanoClient::isValidNodeUrl('http://node.example.org'));
        self::assertFalse(BuyCoursesNanoClient::isValidNodeUrl('https://user:pass@node.example.org'));
        self::assertFalse(BuyCoursesNanoClient::isValidNodeUrl('file:///etc/passwd'));
    }

    public function testTheCheckoutChecksAndClaimsTheBlockBeforeCompletingTheSale(): void
    {
        $source = (string) file_get_contents(self::SRC.'process_confirm.php');
        $start = strpos($source, 'case BuyCoursesPlugin::PAYMENT_TYPE_NANO:');
        self::assertTrue(false !== $start, 'process_confirm.php has a Nano case');

        $end = strpos($source, 'case BuyCoursesPlugin::', $start + 1);
        $case = substr($source, $start, false === $end ? null : $end - $start);
        $check = strpos($case, '->refusalFor(');
        $claim = strpos($case, '->claimNanoBlock(');
        $complete = strpos($case, '->completeSale(');

        self::assertTrue(false !== $check && false !== $claim && false !== $complete);
        self::assertTrue($check < $claim, 'the block is checked before it is claimed');
        self::assertTrue($claim < $complete, 'the block is claimed before the sale is completed');
    }

    private function clientAndSale(): array
    {
        return [
            new BuyCoursesNanoClient(self::GENESIS, 'https://node.example.org', '1.25'),
            ['id' => 42, 'reference' => 'REF-42', 'price' => '19.99'],
        ];
    }

    private function block(BuyCoursesNanoClient $client, array $sale, array $override = []): array
    {
        return array_replace([
            'confirmed' => 'true',
            'subtype' => 'send',
            'block_account' => self::BURN,
            'amount' => $client->rawFor($sale),
            'contents' => [
                'representative' => $client->representativeFor($sale),
                'link_as_account' => self::GENESIS,
            ],
        ], $override);
    }

    private function openClaims(bool $create): PDO
    {
        $pdo = new PDO('sqlite:'.$this->claimsFile, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        if ($create) {
            // The same keys as plugin_buycourses_nano_claim: the block hash is the primary key, the sale is unique.
            $pdo->exec('CREATE TABLE nano_claim (block_hash CHAR(64) NOT NULL PRIMARY KEY, sale_id INTEGER NOT NULL UNIQUE, claimed_at INTEGER NOT NULL)');
        }

        return $pdo;
    }
}
