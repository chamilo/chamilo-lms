<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

/**
 * Nano (XNO) payments for BuyCourses.
 *
 * The buyer sends XNO from their own wallet to the account the administrator configured, then pastes the hash
 * of that send block. The plugin reads the block from a Nano node the administrator configured and completes
 * the sale only when the block is a confirmed send of at least the sale price in raw to that account, bound to
 * this sale, and not already claimed by another sale. No key or seed is ever held: the plugin only reads.
 *
 * The classes in this file use nothing but the PHP standard library (no Chamilo, no Composer package), so they
 * are unit tested on their own (tests/CoreBundle/Legacy/BuyCoursesNanoPaymentTest.php). The plugin class only
 * reads its settings and calls them.
 */

/**
 * Unkeyed BLAKE2b (RFC 7693) at any digest length from 1 to 64 bytes.
 *
 * ext-sodium only produces 16 to 64 byte digests, and a shorter BLAKE2b digest is not a truncation of a longer
 * one (the length is mixed into the initial state). A Nano address checksum is 5 bytes, so it is computed here,
 * on 32-bit halves so it never depends on integer overflow.
 */
final class BuyCoursesNanoBlake2b
{
    private const MASK = 0xFFFFFFFF;

    // High and low halves of the IV, interleaved.
    private const IV = [
        0x6A09E667, 0xF3BCC908, 0xBB67AE85, 0x84CAA73B, 0x3C6EF372, 0xFE94F82B, 0xA54FF53A, 0x5F1D36F1,
        0x510E527F, 0xADE682D1, 0x9B05688C, 0x2B3E6C1F, 0x1F83D9AB, 0xFB41BD6B, 0x5BE0CD19, 0x137E2179,
    ];

    private const SIGMA = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],
        [14, 10, 4, 8, 9, 15, 13, 6, 1, 12, 0, 2, 11, 7, 5, 3],
        [11, 8, 12, 0, 5, 2, 15, 13, 10, 14, 3, 6, 7, 1, 9, 4],
        [7, 9, 3, 1, 13, 12, 11, 14, 2, 6, 5, 10, 4, 0, 15, 8],
        [9, 0, 5, 7, 2, 4, 10, 15, 14, 1, 11, 12, 6, 8, 3, 13],
        [2, 12, 6, 10, 0, 11, 8, 3, 4, 13, 7, 5, 15, 14, 1, 9],
        [12, 5, 1, 15, 14, 13, 4, 10, 0, 7, 6, 3, 9, 2, 8, 11],
        [13, 11, 7, 14, 12, 1, 3, 9, 5, 0, 15, 4, 8, 6, 2, 10],
        [6, 15, 14, 9, 11, 3, 0, 8, 12, 2, 13, 7, 1, 4, 10, 5],
        [10, 2, 8, 4, 7, 6, 1, 5, 15, 11, 9, 14, 3, 12, 13, 0],
    ];

    private array $v = [];
    private array $m = [];

    public static function hash(string $data, int $length): string
    {
        if ($length < 1 || $length > 64) {
            throw new InvalidArgumentException('BLAKE2b digest length must be between 1 and 64 bytes.');
        }

        $state = new self();
        $h = self::IV;
        $h[1] ^= 0x01010000 ^ $length;

        $blocks = '' === $data ? [''] : str_split($data, 128);
        $last = count($blocks) - 1;
        $counter = 0;

        foreach ($blocks as $index => $block) {
            $counter += strlen($block);
            $h = $state->compress($h, str_pad($block, 128, "\0"), $counter, $index === $last);
        }

        $digest = '';
        for ($word = 0; $word < 8; $word++) {
            $digest .= pack('V2', $h[2 * $word + 1], $h[2 * $word]);
        }

        return substr($digest, 0, $length);
    }

    private function compress(array $h, string $block, int $counter, bool $final): array
    {
        $this->v = array_merge($h, self::IV);
        $this->v[25] ^= $counter & self::MASK;
        $this->v[24] ^= ($counter >> 32) & self::MASK;

        if ($final) {
            $this->v[28] ^= self::MASK;
            $this->v[29] ^= self::MASK;
        }

        $words = unpack('V32', $block);
        for ($word = 0; $word < 16; $word++) {
            $this->m[2 * $word] = $words[2 * $word + 2];
            $this->m[2 * $word + 1] = $words[2 * $word + 1];
        }

        for ($round = 0; $round < 12; $round++) {
            $s = self::SIGMA[$round % 10];
            $this->mix(0, 4, 8, 12, $s[0], $s[1]);
            $this->mix(1, 5, 9, 13, $s[2], $s[3]);
            $this->mix(2, 6, 10, 14, $s[4], $s[5]);
            $this->mix(3, 7, 11, 15, $s[6], $s[7]);
            $this->mix(0, 5, 10, 15, $s[8], $s[9]);
            $this->mix(1, 6, 11, 12, $s[10], $s[11]);
            $this->mix(2, 7, 8, 13, $s[12], $s[13]);
            $this->mix(3, 4, 9, 14, $s[14], $s[15]);
        }

        for ($i = 0; $i < 16; $i++) {
            $h[$i] ^= $this->v[$i] ^ $this->v[$i + 16];
        }

        return $h;
    }

    private function mix(int $a, int $b, int $c, int $d, int $x, int $y): void
    {
        $this->add($a, $b, 2 * $x);
        $this->xorRotate($d, $a, 32);
        $this->add($c, $d);
        $this->xorRotate($b, $c, 24);
        $this->add($a, $b, 2 * $y);
        $this->xorRotate($d, $a, 16);
        $this->add($c, $d);
        $this->xorRotate($b, $c, 63);
    }

    private function add(int $a, int $b, ?int $message = null): void
    {
        $low = $this->v[2 * $a + 1] + $this->v[2 * $b + 1];
        $high = $this->v[2 * $a] + $this->v[2 * $b];

        if (null !== $message) {
            $low += $this->m[$message + 1];
            $high += $this->m[$message];
        }

        $this->v[2 * $a] = ($high + ($low >> 32)) & self::MASK;
        $this->v[2 * $a + 1] = $low & self::MASK;
    }

    private function xorRotate(int $a, int $b, int $bits): void
    {
        $high = $this->v[2 * $a] ^ $this->v[2 * $b];
        $low = $this->v[2 * $a + 1] ^ $this->v[2 * $b + 1];

        if (32 === $bits) {
            [$high, $low] = [$low, $high];
        } elseif (63 === $bits) {
            [$high, $low] = [(($high << 1) | ($low >> 31)) & self::MASK, (($low << 1) | ($high >> 31)) & self::MASK];
        } else {
            [$high, $low] = [
                (($high >> $bits) | ($low << (32 - $bits))) & self::MASK,
                (($low >> $bits) | ($high << (32 - $bits))) & self::MASK,
            ];
        }

        $this->v[2 * $a] = $high;
        $this->v[2 * $a + 1] = $low;
    }
}

/**
 * Nano account addresses: "nano_" (or the legacy "xrb_") followed by the 256-bit public key and a 40-bit BLAKE2b
 * checksum in Nano's base32 alphabet. The checksum catches a mistyped account, so it is always verified.
 */
final class BuyCoursesNanoAddress
{
    private const ALPHABET = '13456789abcdefghijkmnopqrstuwxyz';

    public static function isValid(string $address): bool
    {
        return null !== self::publicKey($address);
    }

    /**
     * The account's public key as uppercase hex, or null when the address is malformed or its checksum fails.
     */
    public static function publicKey(string $address): ?string
    {
        if (1 !== preg_match('/^(?:nano|xrb)_([13][13-9a-km-uw-z]{59})$/', trim($address), $matches)) {
            return null;
        }

        $bits = '';
        foreach (str_split($matches[1]) as $character) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $character)), 5, '0', STR_PAD_LEFT);
        }

        // 52 characters carry 260 bits: four zero bits of padding, then the key; then 8 characters of checksum.
        $key = self::bytes(substr($bits, 4, 256));
        $checksum = self::bytes(substr($bits, 260, 40));

        if (!hash_equals(strrev(BuyCoursesNanoBlake2b::hash($key, 5)), $checksum)) {
            return null;
        }

        return strtoupper(bin2hex($key));
    }

    /**
     * Encode a 32-byte public key (hex) as a "nano_" account with its checksum.
     */
    public static function fromPublicKey(string $publicKeyHex): string
    {
        if (1 !== preg_match('/^[0-9a-fA-F]{64}$/', $publicKeyHex)) {
            throw new InvalidArgumentException('A Nano public key is 32 bytes of hex.');
        }

        $key = hex2bin(strtolower($publicKeyHex));
        $checksum = strrev(BuyCoursesNanoBlake2b::hash($key, 5));

        $bits = '0000';
        foreach (str_split($key.$checksum) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $account = '';
        foreach (str_split($bits, 5) as $chunk) {
            $account .= self::ALPHABET[(int) bindec($chunk)];
        }

        return 'nano_'.$account;
    }

    /**
     * Compare two addresses by the key they encode, so the "xrb_" and "nano_" forms of one account match.
     */
    public static function matches(?string $a, ?string $b): bool
    {
        if (null === $a || null === $b) {
            return false;
        }

        $key = self::publicKey($a);

        return null !== $key && $key === self::publicKey($b);
    }

    private static function bytes(string $bits): string
    {
        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            $bytes .= chr((int) bindec($byte));
        }

        return $bytes;
    }
}

/**
 * Nano amounts in raw, the integer unit the protocol settles in: 1 XNO = 10^30 raw. Every amount is derived
 * here from the sale price (in cents, as BuyCoursesPlugin::priceToCents() gives it) and the administrator's
 * rate, with integer string arithmetic only, so no amount is ever rounded through a float.
 */
final class BuyCoursesNanoAmount
{
    public const RAW_DECIMALS = 30;

    /**
     * A rate has at most 28 decimals, so that cents (2 decimals) times the rate stays within one raw.
     */
    public const RATE_DECIMALS = 28;

    /**
     * Raw for a decimal XNO amount ("0.5" -> "500000000000000000000000000000"). More than 30 decimals is below
     * one raw and is refused rather than truncated.
     */
    public static function xnoToRaw(string $xno): string
    {
        if (1 !== preg_match('/^(\d+)(?:\.(\d+))?$/', trim($xno), $m)) {
            throw new InvalidArgumentException('An XNO amount is a non-negative decimal.');
        }

        $fraction = $m[2] ?? '';
        if (strlen($fraction) > self::RAW_DECIMALS) {
            throw new InvalidArgumentException('An XNO amount cannot be finer than one raw (10^-30 XNO).');
        }

        return self::trimInteger($m[1].str_pad($fraction, self::RAW_DECIMALS, '0'));
    }

    /**
     * Decimal XNO for a raw amount, for display only ("500000000000000000000000000000" -> "0.5").
     */
    public static function rawToXno(string $raw): string
    {
        $raw = str_pad(self::normalizeRaw($raw), self::RAW_DECIMALS + 1, '0', STR_PAD_LEFT);
        $integer = self::trimInteger(substr($raw, 0, -self::RAW_DECIMALS));
        $fraction = rtrim(substr($raw, -self::RAW_DECIMALS), '0');

        return '' === $fraction ? $integer : $integer.'.'.$fraction;
    }

    public static function isValidRate(string $rate): bool
    {
        if (1 !== preg_match('/^(\d+)(?:\.(\d{1,'.self::RATE_DECIMALS.'}))?$/', trim($rate), $m)) {
            return false;
        }

        return '0' !== self::trimInteger($m[1].($m[2] ?? ''));
    }

    /**
     * Raw owed for a price in cents at a rate of XNO per one unit of the sale currency.
     */
    public static function rawForCents(int $cents, string $xnoPerUnit): string
    {
        if ($cents <= 0) {
            throw new InvalidArgumentException('A Nano payment needs a positive price.');
        }
        if (!self::isValidRate($xnoPerUnit)) {
            throw new InvalidArgumentException('The XNO rate is a positive decimal with at most 28 decimals.');
        }

        preg_match('/^(\d+)(?:\.(\d+))?$/', trim($xnoPerUnit), $m);
        $rateFraction = $m[2] ?? '';
        // cents * rate / 100, as an integer number of raw: scale both to raw, then divide by 100 exactly.
        $rateRaw = self::trimInteger($m[1].str_pad($rateFraction, self::RAW_DECIMALS, '0'));
        $product = self::multiplyIntegers((string) $cents, $rateRaw);

        // The rate has at most 28 decimals, so rateRaw is a multiple of 100 and this division is exact.
        if (strlen($product) <= 2) {
            throw new InvalidArgumentException('The Nano amount for this price is below one raw.');
        }

        return self::trimInteger(substr($product, 0, -2));
    }

    public static function isRaw(string $value): bool
    {
        return 1 === preg_match('/^\d{1,39}$/', trim($value));
    }

    public static function normalizeRaw(string $value): string
    {
        if (!self::isRaw($value)) {
            throw new InvalidArgumentException('A raw amount is an integer of 1 to 39 digits.');
        }

        return self::trimInteger(trim($value));
    }

    /**
     * True when the raw amount $paid is at least $required.
     */
    public static function atLeast(string $paid, string $required): bool
    {
        $paid = self::normalizeRaw($paid);
        $required = self::normalizeRaw($required);

        if (strlen($paid) !== strlen($required)) {
            return strlen($paid) > strlen($required);
        }

        return strcmp($paid, $required) >= 0;
    }

    private static function multiplyIntegers(string $a, string $b): string
    {
        $a = self::trimInteger($a);
        $b = self::trimInteger($b);
        if ('0' === $a || '0' === $b) {
            return '0';
        }

        $result = array_fill(0, strlen($a) + strlen($b), 0);
        $aDigits = array_reverse(str_split($a));
        $bDigits = array_reverse(str_split($b));

        foreach ($aDigits as $i => $da) {
            foreach ($bDigits as $j => $db) {
                $result[$i + $j] += (int) $da * (int) $db;
                $result[$i + $j + 1] += intdiv($result[$i + $j], 10);
                $result[$i + $j] %= 10;
            }
        }

        return self::trimInteger(implode('', array_reverse($result)));
    }

    private static function trimInteger(string $digits): string
    {
        $digits = ltrim($digits, '0');

        return '' === $digits ? '0' : $digits;
    }
}

/**
 * Binds a send block to one sale (option C of the xno-binding v1 draft): the buyer sets the representative of
 * the send block to an account derived from the sale, the pay-to account and the raw amount.
 *
 *   commitment = SHA256("xno-binding/v1/rep|" + saleKey + "|" + payTo + "|" + raw)
 *
 * A Nano send carries no memo, so a bare block hash only proves that an amount reached an account; the
 * representative makes the block belong to exactly one sale at exactly one price.
 */
final class BuyCoursesNanoBinding
{
    private const DOMAIN = 'xno-binding/v1/rep|';

    /**
     * The key of a sale in the commitment: its id and its reference, both stored on the sale row.
     */
    public static function saleKey(array $sale): string
    {
        return 'buycourses-sale:'.(int) ($sale['id'] ?? 0).':'.(string) ($sale['reference'] ?? '');
    }

    public static function representativeAccount(string $saleKey, string $payTo, string $raw): string
    {
        $commitment = hash('sha256', self::DOMAIN.$saleKey.'|'.$payTo.'|'.BuyCoursesNanoAmount::normalizeRaw($raw));

        return BuyCoursesNanoAddress::fromPublicKey($commitment);
    }
}

/**
 * Decides whether a node's block_info reply (json_block=true) pays one sale. No network and no key: the caller
 * reads the block from the configured node and passes the reply here.
 */
final class BuyCoursesNanoVerifier
{
    /**
     * @return string '' when the block pays the sale, otherwise the reason it does not
     */
    public static function refusal(array $blockInfo, string $payTo, string $raw, string $representative): string
    {
        $contents = is_array($blockInfo['contents'] ?? null) ? $blockInfo['contents'] : [];
        $amount = is_string($blockInfo['amount'] ?? null) ? $blockInfo['amount'] : '';
        $subtype = $blockInfo['subtype'] ?? ($contents['subtype'] ?? null);

        return match (true) {
            !in_array($blockInfo['confirmed'] ?? null, [true, 'true'], true) => 'The send block is not confirmed yet.',
            'send' !== $subtype || !is_string($blockInfo['block_account'] ?? null) => 'The block is not a send.',
            !BuyCoursesNanoAmount::isRaw($amount) => 'The block amount is not a raw integer.',
            !BuyCoursesNanoAddress::matches($contents['link_as_account'] ?? null, $payTo) => 'The send does not pay the configured account.',
            !BuyCoursesNanoAmount::atLeast($amount, $raw) => 'The send is less than the sale price in raw.',
            !BuyCoursesNanoAddress::matches($contents['representative'] ?? null, $representative) => 'The send is not bound to this sale.',
            default => '',
        };
    }
}

/**
 * Durable replay refusal. A send block that completed one sale must never complete another, even after a
 * restart: its hash goes into a table whose block_hash is the primary key and whose sale_id is unique, before
 * the sale is completed. The claim lives in the platform database, so it survives a restart.
 */
final class BuyCoursesNanoClaims
{
    public function __construct(private readonly PDO $pdo, private readonly string $table)
    {
    }

    public static function isBlockHash(string $hash): bool
    {
        return 1 === preg_match('/^[0-9A-Fa-f]{64}$/', trim($hash));
    }

    /**
     * True when this block is newly claimed for this sale, or was already claimed by this same sale (a page
     * reload after a completed claim). False when the block or the sale is already claimed by something else.
     */
    public function claim(string $blockHash, int $saleId): bool
    {
        if (!self::isBlockHash($blockHash) || $saleId <= 0) {
            return false;
        }
        $blockHash = strtoupper(trim($blockHash));

        try {
            $insert = $this->pdo->prepare(
                "INSERT INTO {$this->table} (block_hash, sale_id, claimed_at) VALUES (:hash, :sale, :at)"
            );
            $insert->execute([':hash' => $blockHash, ':sale' => $saleId, ':at' => time()]);

            return true;
        } catch (PDOException $exception) {
            $select = $this->pdo->prepare("SELECT sale_id FROM {$this->table} WHERE block_hash = :hash");
            $select->execute([':hash' => $blockHash]);
            $owner = $select->fetchColumn();

            if (false === $owner) {
                $select = $this->pdo->prepare("SELECT block_hash FROM {$this->table} WHERE sale_id = :sale");
                $select->execute([':sale' => $saleId]);
                if (false === $select->fetchColumn()) {
                    throw $exception;
                }

                return false;
            }

            return (int) $owner === $saleId;
        }
    }
}

/**
 * Settings and node access. A configuration is complete only with a valid account, a node URL (HTTPS, or HTTP
 * to the loopback interface for a node on the same host) and a positive rate.
 */
final class BuyCoursesNanoClient
{
    private const MAX_RESPONSE_BYTES = 65536;

    public function __construct(
        public readonly string $account,
        public readonly string $nodeUrl,
        public readonly string $xnoPerUnit,
    ) {
    }

    public static function fromParams(array $params): self
    {
        if (!self::isConfiguredParams($params)) {
            throw new InvalidArgumentException('The Nano payment settings are incomplete or invalid.');
        }

        return new self(
            trim((string) $params['account']),
            trim((string) $params['node_url']),
            trim((string) $params['xno_per_unit'])
        );
    }

    public static function isConfiguredParams(array $params): bool
    {
        return BuyCoursesNanoAddress::isValid((string) ($params['account'] ?? ''))
            && self::isValidNodeUrl((string) ($params['node_url'] ?? ''))
            && BuyCoursesNanoAmount::isValidRate((string) ($params['xno_per_unit'] ?? ''));
    }

    public static function isConfigured(BuyCoursesPlugin $plugin): bool
    {
        return self::isConfiguredParams($plugin->getNanoParams());
    }

    public static function isValidNodeUrl(string $url): bool
    {
        $url = trim($url);
        if ('' === $url || false === filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        return 'https' === $scheme
            || ('http' === $scheme && in_array($host, ['127.0.0.1', 'localhost', '[::1]'], true));
    }

    public function rawFor(array $sale): string
    {
        // The same cents as BuyCoursesPlugin::priceToCents(), which every other gateway of this plugin compares with.
        return BuyCoursesNanoAmount::rawForCents((int) round((float) ($sale['price'] ?? 0) * 100), $this->xnoPerUnit);
    }

    public function representativeFor(array $sale): string
    {
        return BuyCoursesNanoBinding::representativeAccount(
            BuyCoursesNanoBinding::saleKey($sale),
            $this->account,
            $this->rawFor($sale)
        );
    }

    /**
     * '' when the block pays this sale, otherwise the reason.
     */
    public function refusalFor(array $sale, array $blockInfo): string
    {
        return BuyCoursesNanoVerifier::refusal($blockInfo, $this->account, $this->rawFor($sale), $this->representativeFor($sale));
    }

    /**
     * Reads a block from the configured node (RPC action block_info). Null when the node cannot be read.
     */
    public function blockInfo(string $hash): ?array
    {
        if (!BuyCoursesNanoClaims::isBlockHash($hash)) {
            return null;
        }

        $body = json_encode(['action' => 'block_info', 'json_block' => 'true', 'hash' => strtoupper(trim($hash))]);
        $received = '';
        $curl = curl_init($this->nodeUrl);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$received): int {
                if (strlen($received) + strlen($chunk) > self::MAX_RESPONSE_BYTES) {
                    return 0;
                }
                $received .= $chunk;

                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if (false === $ok || 200 !== $status) {
            return null;
        }

        $decoded = json_decode($received, true);

        return is_array($decoded) && !isset($decoded['error']) ? $decoded : null;
    }
}
