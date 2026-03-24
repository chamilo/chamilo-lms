<?php

/* For licensing terms, see /license.txt */

/**
 * CardGame plugin service class.
 */
class CardGame extends Plugin
{
    public const TOTAL_PARTS = 15;

    /**
     * CardGame constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '2.0',
            'Damien Renou / C2 adaptation'
        );
    }

    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function install()
    {
        $queries = [
            "CREATE TABLE IF NOT EXISTS plugin_card_game (
                id INT NOT NULL AUTO_INCREMENT,
                user_id INT NOT NULL,
                pan INT NOT NULL DEFAULT 1,
                access_date DATE DEFAULT NULL,
                parts TEXT NOT NULL,
                PRIMARY KEY (id),
                INDEX idx_card_game_user_id (user_id),
                INDEX idx_card_game_access_date (access_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "ALTER TABLE plugin_card_game MODIFY user_id INT NOT NULL",
            "ALTER TABLE plugin_card_game MODIFY pan INT NOT NULL DEFAULT 1",
            "ALTER TABLE plugin_card_game MODIFY access_date DATE DEFAULT NULL",
            "ALTER TABLE plugin_card_game MODIFY parts TEXT NOT NULL",
        ];

        foreach ($queries as $sql) {
            try {
                Database::query($sql);
            } catch (Exception $exception) {
                // Ignore migration errors to keep install idempotent.
            }
        }
    }

    public function uninstall()
    {
        $sql = 'DROP TABLE IF EXISTS plugin_card_game';
        Database::query($sql);
    }

    public function getOrCreateProgress(int $userId): array
    {
        $userId = (int) $userId;
        $sql = "SELECT id, user_id, pan, access_date, parts
                FROM plugin_card_game
                WHERE user_id = $userId
                ORDER BY id DESC
                LIMIT 1";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        if (!$row) {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $insertSql = "INSERT INTO plugin_card_game (user_id, pan, access_date, parts)
                          VALUES ($userId, 1, '$yesterday', '')";
            Database::query($insertSql);
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
        }

        $pan = max(1, (int) ($row['pan'] ?? 1));
        $accessDate = $row['access_date'] ?? null;
        $parts = self::parseParts((string) ($row['parts'] ?? ''));
        $serializedParts = self::serializeParts($parts);

        if ($serializedParts !== (string) ($row['parts'] ?? '')) {
            $this->updateUserState($userId, $pan, $accessDate, $serializedParts);
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'user_id' => $userId,
            'pan' => $pan,
            'access_date' => $accessDate,
            'parts' => $parts,
        ];
    }

    public function canPlayToday(array $progress): bool
    {
        return ($progress['access_date'] ?? null) !== $this->getTodayDate();
    }

    public function revealPart(int $userId, int $part): array
    {
        $userId = (int) $userId;
        $part = max(1, min(self::TOTAL_PARTS, $part));
        $today = $this->getTodayDate();
        $progress = $this->getOrCreateProgress($userId);

        if (!$this->canPlayToday($progress)) {
            return [
                'success' => false,
                'alreadyPlayed' => true,
                'duplicatePart' => false,
                'completedPan' => false,
                'canPlayToday' => false,
                'pan' => (int) $progress['pan'],
                'parts' => $progress['parts'],
            ];
        }

        $parts = $progress['parts'];
        $pan = (int) $progress['pan'];

        if (in_array($part, $parts, true)) {
            $this->updateUserState($userId, $pan, $today, self::serializeParts($parts));

            return [
                'success' => false,
                'alreadyPlayed' => false,
                'duplicatePart' => true,
                'completedPan' => false,
                'canPlayToday' => false,
                'pan' => $pan,
                'parts' => $parts,
            ];
        }

        $parts[] = $part;
        sort($parts, SORT_NUMERIC);
        $completedPan = count($parts) >= self::TOTAL_PARTS;

        if ($completedPan) {
            $pan++;
            $parts = [];
        }

        $this->updateUserState($userId, $pan, $today, self::serializeParts($parts));

        return [
            'success' => true,
            'alreadyPlayed' => false,
            'duplicatePart' => false,
            'completedPan' => $completedPan,
            'canPlayToday' => false,
            'pan' => $pan,
            'parts' => $parts,
            'revealedPart' => $part,
        ];
    }

    public function markLoss(int $userId): array
    {
        $userId = (int) $userId;
        $today = $this->getTodayDate();
        $progress = $this->getOrCreateProgress($userId);

        if (!$this->canPlayToday($progress)) {
            return [
                'success' => false,
                'alreadyPlayed' => true,
                'duplicatePart' => false,
                'completedPan' => false,
                'canPlayToday' => false,
                'pan' => (int) $progress['pan'],
                'parts' => $progress['parts'],
            ];
        }

        $this->updateUserState(
            $userId,
            (int) $progress['pan'],
            $today,
            self::serializeParts($progress['parts'])
        );

        return [
            'success' => true,
            'alreadyPlayed' => false,
            'duplicatePart' => true,
            'completedPan' => false,
            'canPlayToday' => false,
            'pan' => (int) $progress['pan'],
            'parts' => $progress['parts'],
        ];
    }

    public function getDisplayPan(int $pan): int
    {
        return max(1, min(4, $pan));
    }

    public static function parseParts(string $parts): array
    {
        if ('' === trim($parts)) {
            return [];
        }

        preg_match_all('/(?:^|[^0-9])(1[0-5]|[1-9])(?=[^0-9]|$)/', $parts, $matches);
        $numbers = array_map('intval', $matches[1] ?? []);
        $numbers = array_values(array_unique(array_filter($numbers, static function ($value) {
            return $value >= 1 && $value <= self::TOTAL_PARTS;
        })));
        sort($numbers, SORT_NUMERIC);

        return $numbers;
    }

    public static function serializeParts(array $parts): string
    {
        $parts = array_values(array_unique(array_map('intval', $parts)));
        $parts = array_values(array_filter($parts, static function ($value) {
            return $value >= 1 && $value <= self::TOTAL_PARTS;
        }));
        sort($parts, SORT_NUMERIC);

        return implode(',', $parts);
    }

    private function updateUserState(int $userId, int $pan, ?string $accessDate, string $parts): void
    {
        $userId = (int) $userId;
        $pan = max(1, (int) $pan);
        $parts = Database::escape_string($parts);
        $accessDateSql = null === $accessDate
            ? 'NULL'
            : "'".Database::escape_string($accessDate)."'";

        $sql = "UPDATE plugin_card_game
                SET pan = $pan,
                    access_date = $accessDateSql,
                    parts = '$parts'
                WHERE user_id = $userId";
        Database::query($sql);
    }

    private function getTodayDate(): string
    {
        return date('Y-m-d');
    }
}
