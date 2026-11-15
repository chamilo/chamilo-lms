<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\SessionUserImportUsernameHelper;
use PHPUnit\Framework\TestCase;

/**
 * Session user import must accept both the documented "username" header and
 * the "UserName" header written by user_export.php (issue #4984).
 */
final class SessionUserImportUsernameHelperTest extends TestCase
{
    /**
     * @dataProvider acceptedRows
     *
     * @param array<array-key, mixed> $row
     */
    public function testItReadsTheUsernameRegardlessOfHeaderCase(array $row, string $expected): void
    {
        $this->assertSame($expected, SessionUserImportUsernameHelper::fromCsvRow($row));
    }

    /**
     * @return array<string, array{array<array-key, mixed>, string}>
     */
    public static function acceptedRows(): array
    {
        return [
            'documented lowercase header' => [['username' => 'jmontoya'], 'jmontoya'],
            'user_export.php UserName header' => [['UserName' => 'jmontoya'], 'jmontoya'],
            'Username capitalization' => [['Username' => 'jmontoya'], 'jmontoya'],
            'all-caps header' => [['USERNAME' => 'jmontoya'], 'jmontoya'],
            'trims surrounding whitespace' => [['UserName' => "  jmontoya  \t"], 'jmontoya'],
            'UTF-8 BOM on the UserName header' => [["\xEF\xBB\xBFUserName" => 'jmontoya'], 'jmontoya'],
            'UserName wins over an empty username column' => [
                ['username' => '', 'UserName' => 'jmontoya'],
                'jmontoya',
            ],
            'other columns are ignored' => [
                ['OfficialCode' => 'X', 'UserName' => 'jmontoya', 'Email' => 'a@b.c'],
                'jmontoya',
            ],
        ];
    }

    /**
     * @dataProvider rejectedRows
     *
     * @param array<array-key, mixed> $row
     */
    public function testItReturnsNullWhenTheUsernameIsMissingOrBlank(array $row): void
    {
        $this->assertNull(SessionUserImportUsernameHelper::fromCsvRow($row));
    }

    /**
     * @return array<string, array{array<array-key, mixed>}>
     */
    public static function rejectedRows(): array
    {
        return [
            'empty row' => [[]],
            'no username column' => [['Email' => 'a@b.c']],
            'empty string' => [['UserName' => '']],
            'whitespace only' => [['username' => "  \t  "]],
            'null value' => [['UserName' => null]],
            'non-scalar value' => [['UserName' => ['jmontoya']]],
        ];
    }

    public function testTheDocumentedLowercaseHeaderIsPreferredWhenBothAreFilled(): void
    {
        $this->assertSame(
            'from_username',
            SessionUserImportUsernameHelper::fromCsvRow([
                'username' => 'from_username',
                'UserName' => 'from_UserName',
            ])
        );
    }
}
