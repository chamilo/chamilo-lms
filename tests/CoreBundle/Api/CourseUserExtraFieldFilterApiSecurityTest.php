<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regression test for the extra-field filter of GET /api/course-users/available.
 *
 * A teacher may narrow the users to subscribe by an extra field, but only by one the
 * administrator marked as filterable for users. Any other field turns the filter into an
 * exact-match oracle: probing values and watching which users remain reveals "user U has
 * value V in field F", even for fields hidden from other users.
 */
final class CourseUserExtraFieldFilterApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const string PREFIX = 'cuxff_';

    private Course $course;
    private string $token;
    private User $alice;
    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setSetting('workflows.allow_user_course_subscription_by_course_admin', true);
        $this->setSetting('course.profiling_filter_adding_users', true);

        $this->course = $this->createCourse('Extra field filter');
        $teacher = $this->createUser(self::PREFIX.'teacher_'.uniqid());
        $this->course->addUserAsTeacher($teacher);
        self::getContainer()->get(CourseRepository::class)->update($this->course);

        // The listing only returns users with a persisted platform role.
        $this->alice = $this->createUser(self::PREFIX.'alice', '', '', 'ROLE_STUDENT');
        $this->bob = $this->createUser(self::PREFIX.'bob', '', '', 'ROLE_STUDENT');

        $this->token = $this->getUserTokenFromUser($teacher);
    }

    public function testFilterableUserFieldNarrowsTheList(): void
    {
        $field = $this->createField(ExtraField::USER_FIELD_TYPE, true);
        $this->setValue($field, $this->alice, 'north');
        $this->setValue($field, $this->bob, 'south');

        $this->assertSame([self::PREFIX.'alice'], $this->availableUsernames($field, 'north'));
    }

    public function testNonFilterableFieldMatchesNobody(): void
    {
        $field = $this->createField(ExtraField::USER_FIELD_TYPE, false);
        $this->setValue($field, $this->alice, 'secret-a');
        $this->setValue($field, $this->bob, 'secret-b');

        $this->assertNoOracle($field, 'secret-a');
    }

    public function testFieldOfAnotherItemTypeMatchesNobody(): void
    {
        // A course field whose value happens to be stored under a user's id.
        $field = $this->createField(ExtraField::COURSE_FIELD_TYPE, true);
        $this->setValue($field, $this->alice, 'course-value');

        $this->assertNoOracle($field, 'course-value');
    }

    public function testFilterIsIgnoredWhenProfilingIsDisabled(): void
    {
        $field = $this->createField(ExtraField::USER_FIELD_TYPE, true);
        $this->setValue($field, $this->alice, 'east');
        $this->setSetting('course.profiling_filter_adding_users', false);

        $this->assertSame(
            [self::PREFIX.'alice', self::PREFIX.'bob'],
            $this->availableUsernames($field, 'east'),
            'The interface offers no extra-field filter when profiling is off, so the API must not apply one.'
        );
    }

    /**
     * A field that may not filter matches nobody, whatever the value: the probe for the real value
     * answers exactly like the probe for a value no user has, so it reveals nothing.
     */
    private function assertNoOracle(ExtraField $field, string $realValue): void
    {
        $this->assertSame(
            $this->availableUsernames($field, 'no-user-has-this-value'),
            $this->availableUsernames($field, $realValue),
            'The filter must not reveal which user holds a value in a field that may not filter.'
        );
        $this->assertSame([], $this->availableUsernames($field, $realValue));
    }

    /**
     * @return list<string>
     */
    private function availableUsernames(ExtraField $field, string $value): array
    {
        $response = $this->createClientWithCredentials($this->token)->request('GET', '/api/course-users/available', [
            'query' => [
                'cid' => $this->course->getId(),
                'search' => self::PREFIX,
                'extraFieldId' => $field->getId(),
                'extraFieldValue' => $value,
                'itemsPerPage' => 100,
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $usernames = array_values(array_filter(
            array_column($response->toArray()['items'], 'username'),
            static fn (string $username): bool => \in_array($username, [self::PREFIX.'alice', self::PREFIX.'bob'], true),
        ));
        sort($usernames);

        return $usernames;
    }

    private function createField(int $itemType, bool $filter): ExtraField
    {
        $field = (new ExtraField())
            ->setItemType($itemType)
            ->setValueType(ExtraField::FIELD_TYPE_TEXT)
            ->setVariable(self::PREFIX.uniqid())
            ->setDisplayText('Extra field filter')
            ->setFilter($filter)
        ;

        $em = $this->getEntityManager();
        $em->persist($field);
        $em->flush();

        return $field;
    }

    private function setValue(ExtraField $field, User $user, string $value): void
    {
        $fieldValue = (new ExtraFieldValues())
            ->setField($field)
            ->setItemId((int) $user->getId())
            ->setFieldValue($value)
        ;

        $em = $this->getEntityManager();
        $em->persist($fieldValue);
        $em->flush();
    }

    private function setSetting(string $name, bool $enabled): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $enabled ? 'true' : 'false');
    }
}
