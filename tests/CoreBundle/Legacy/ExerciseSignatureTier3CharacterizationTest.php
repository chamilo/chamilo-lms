<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Doctrine\DBAL\Connection;
use ExerciseSignaturePlugin;
use ExtraField;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for the signature storage of the ExerciseSignature plugin, which reads and writes the
 * extra_field_values row of the "signature" field of a track_exercise item: saveSignatureValueDirectly() and
 * getSignatureValueDirectly() (private statics, called by reflection; the public save/get paths go through them).
 */
final class ExerciseSignatureTier3CharacterizationTest extends KernelTestCase
{
    private const PNG = 'data:image/png;base64,iVBORw0KGgo=';
    private const PNG2 = 'data:image/png;base64,AAAAAAAA';

    private Connection $conn;
    private int $fieldId;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new HttpSession(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/plugin/ExerciseSignature/index.php');
        $request->setSession($session);
        $request->setLocale('en_US');
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();

        require_once api_get_path(SYS_PLUGIN_PATH).'ExerciseSignature/lib/ExerciseSignature.php';

        $extraField = new ExtraField('track_exercise');
        $info = $extraField->get_handler_field_info_by_field_variable('signature');
        if (empty($info['id'])) {
            $extraField->save([
                'field_type' => 2,
                'value_type' => 2,
                'variable' => 'signature',
                'display_text' => 'Signature',
                'default_value' => '',
                'field_order' => 0,
                'visible_to_self' => 0,
                'changeable' => 1,
                'filter' => 0,
                'visible' => 1,
            ]);
            $info = $extraField->get_handler_field_info_by_field_variable('signature');
        }
        $this->fieldId = (int) $info['id'];
    }

    public function testFirstSaveInsertsTheRowAndTheValueReadsBack(): void
    {
        $this->assertSame('', $this->read(970001));

        $this->assertTrue($this->save(970001, self::PNG));

        $this->assertSame(self::PNG, $this->read(970001));
        $rows = $this->rows(970001);
        $this->assertCount(1, $rows);
        $this->assertSame(self::PNG, $rows[0]['field_value']);
        $this->assertNotEmpty($rows[0]['created_at']);
        $this->assertNotEmpty($rows[0]['updated_at']);
    }

    public function testSecondSaveUpdatesTheSameRowAndKeepsCreatedAt(): void
    {
        $this->save(970002, self::PNG);
        $before = $this->rows(970002)[0];
        $this->conn->executeStatement("UPDATE extra_field_values SET created_at = '2030-01-02 03:04:05', updated_at = '2030-01-02 03:04:05' WHERE id = ?", [$before['id']]);

        $this->assertTrue($this->save(970002, self::PNG2));

        $rows = $this->rows(970002);
        $this->assertCount(1, $rows, 'Updated in place');
        $this->assertSame($before['id'], $rows[0]['id']);
        $this->assertSame(self::PNG2, $rows[0]['field_value']);
        $this->assertSame('2030-01-02 03:04:05', $rows[0]['created_at']);
        $this->assertNotSame('2030-01-02 03:04:05', $rows[0]['updated_at'], 'updated_at is refreshed');
        $this->assertSame(self::PNG2, $this->read(970002));
    }

    public function testTheLatestRowWinsWhenSeveralExistAndOnlyItIsUpdated(): void
    {
        $this->row(970003, 'old');
        $new = $this->row(970003, 'newer');

        $this->assertSame('newer', $this->read(970003), 'Reads the highest id');

        $this->assertTrue($this->save(970003, self::PNG));
        $this->assertSame('old', $this->conn->fetchOne('SELECT field_value FROM extra_field_values WHERE field_id = ? AND item_id = ? ORDER BY id ASC LIMIT 1', [$this->fieldId, 970003]));
        $this->assertSame(self::PNG, $this->conn->fetchOne('SELECT field_value FROM extra_field_values WHERE id = ?', [$new]));
    }

    public function testRowsOfOtherItemsAndFieldsAreNotTouched(): void
    {
        $this->row(970004, 'mine');
        $other = $this->row(970005, 'other item');
        $this->conn->executeStatement(
            "INSERT INTO extra_field_values (field_id, item_id, field_value, created_at, updated_at) VALUES (?, 970004, 'other field', '2030-01-02 03:04:05', '2030-01-02 03:04:05')",
            [$this->otherFieldId()]
        );

        $this->save(970004, self::PNG);

        $this->assertSame('other item', $this->conn->fetchOne('SELECT field_value FROM extra_field_values WHERE id = ?', [$other]));
        $this->assertSame('other field', $this->conn->fetchOne('SELECT field_value FROM extra_field_values WHERE item_id = 970004 AND field_id <> ?', [$this->fieldId]));
        $this->assertSame(self::PNG, $this->read(970004));
    }

    public function testASaveThatDoesNotStoreADataUrlReportsFailure(): void
    {
        $this->assertFalse($this->save(970006, 'not a data url'));
    }

    private function otherFieldId(): int
    {
        return (int) $this->conn->fetchOne('SELECT id FROM extra_field WHERE id <> ? LIMIT 1', [$this->fieldId]);
    }

    private function save(int $exeId, string $file): bool
    {
        $method = new ReflectionMethod(ExerciseSignaturePlugin::class, 'saveSignatureValueDirectly');
        $method->setAccessible(true);

        return $method->invoke(null, $exeId, $file);
    }

    private function read(int $exeId): string
    {
        $method = new ReflectionMethod(ExerciseSignaturePlugin::class, 'getSignatureValueDirectly');
        $method->setAccessible(true);

        return $method->invoke(null, $exeId);
    }

    private function row(int $item, string $value): int
    {
        $this->conn->executeStatement(
            "INSERT INTO extra_field_values (field_id, item_id, field_value, created_at, updated_at) VALUES (?, ?, ?, '2030-01-02 03:04:05', '2030-01-02 03:04:05')",
            [$this->fieldId, $item, $value]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function rows(int $item): array
    {
        return $this->conn->fetchAllAssociative('SELECT * FROM extra_field_values WHERE field_id = ? AND item_id = ? ORDER BY id', [$this->fieldId, $item]);
    }
}
