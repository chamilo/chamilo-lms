<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use CoursesAndSessionsCatalog;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for CoursesAndSessionsCatalog::getCoursesToAvoid()
 * (public/main/inc/lib/CoursesAndSessionsCatalog.class.php). Fixtures start with "cat3_".
 */
final class CoursesAndSessionsCatalogTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/CoursesAndSessionsCatalog.class.php');
        $request->setSession($session);
        Container::setRequest($request);
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function field(int $itemType, string $variable): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO extra_field (item_type, value_type, variable, display_text, field_order, visible_to_self,
                visible_to_others, changeable, filter, created_at, auto_remove)
             VALUES (?, 1, ?, ?, 0, 1, 1, 1, 0, \'2020-01-01 00:00:00\', 0)',
            [$itemType, $variable, $variable.' label']
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function value(int $fieldId, int $itemId, ?string $value): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO extra_field_values (field_id, item_id, field_value, created_at, updated_at)
             VALUES (?, ?, ?, \'2020-01-01 00:00:00\', \'2020-01-01 00:00:00\')',
            [$fieldId, $itemId, $value]
        );
    }

    public function testCoursesHiddenFromTheCatalogAreListedAfterTheSpecialCourses(): void
    {
        $this->conn()->executeStatement('DELETE FROM extra_field WHERE variable = ?', ['hide_from_catalog']);
        $sticky = $this->createCourse('cat3_sticky');
        $hidden = $this->createCourse('cat3_hidden');
        $shown = $this->createCourse('cat3_shown');
        $this->conn()->executeStatement('UPDATE course SET sticky = 1 WHERE id = ?', [$sticky->getId()]);
        $field = $this->field(ExtraField::COURSE_FIELD_TYPE, 'hide_from_catalog');
        $this->value($field, (int) $hidden->getId(), '1');
        $this->value($field, (int) $shown->getId(), '0');

        $list = CoursesAndSessionsCatalog::getCoursesToAvoid();

        $this->assertContains((int) $sticky->getId(), array_map('intval', $list));
        $this->assertContains((int) $hidden->getId(), array_map('intval', $list));
        $this->assertNotContains((int) $shown->getId(), array_map('intval', $list));
        $ints = array_map('intval', $list);
        $this->assertLessThan(array_search((int) $hidden->getId(), $ints, true), array_search((int) $sticky->getId(), $ints, true));
    }

    public function testOnlyTheHideFromCatalogFieldOfCoursesCounts(): void
    {
        $this->conn()->executeStatement('DELETE FROM extra_field WHERE variable IN (?, ?)', ['hide_from_catalog', 'cat3_other']);
        $viaOtherVariable = $this->createCourse('cat3_other_var');
        $viaUserField = $this->createCourse('cat3_user_field');
        $empty = $this->createCourse('cat3_empty');
        $loose = $this->createCourse('cat3_loose');
        $other = $this->field(ExtraField::COURSE_FIELD_TYPE, 'cat3_other');
        $this->value($other, (int) $viaOtherVariable->getId(), '1');
        $userField = $this->field(ExtraField::USER_FIELD_TYPE, 'hide_from_catalog');
        $this->value($userField, (int) $viaUserField->getId(), '1');
        $courseField = $this->field(ExtraField::COURSE_FIELD_TYPE, 'hide_from_catalog');
        $this->value($courseField, (int) $empty->getId(), null);
        // the text value is compared with the number 1, so MySQL reads "1abc" as 1
        $this->value($courseField, (int) $loose->getId(), '1abc');

        $ints = array_map('intval', CoursesAndSessionsCatalog::getCoursesToAvoid());

        $this->assertNotContains((int) $viaOtherVariable->getId(), $ints);
        $this->assertNotContains((int) $viaUserField->getId(), $ints);
        $this->assertNotContains((int) $empty->getId(), $ints);
        $this->assertContains((int) $loose->getId(), $ints);
    }

    public function testTheCourseIdsKeepTheirDatabaseType(): void
    {
        $this->conn()->executeStatement('DELETE FROM extra_field WHERE variable = ?', ['hide_from_catalog']);
        $hidden = $this->createCourse('cat3_type');
        $field = $this->field(ExtraField::COURSE_FIELD_TYPE, 'hide_from_catalog');
        $this->value($field, (int) $hidden->getId(), '1');

        $list = CoursesAndSessionsCatalog::getCoursesToAvoid();

        $rawType = \gettype($this->conn()->fetchOne('SELECT item_id FROM extra_field_values WHERE field_id = ?', [$field]));
        $this->assertSame([$rawType], array_values(array_unique(array_map('gettype', array_slice($list, -1)))));
    }
}
