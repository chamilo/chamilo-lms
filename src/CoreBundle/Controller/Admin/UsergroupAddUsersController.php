<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Chamilo\CoreBundle\Helpers\CsvFormulaEscaper;
use Chamilo\CoreBundle\Helpers\UsergroupHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/usergroups/{id}/add-users-data', requirements: ['id' => '\d+'])]
class UsergroupAddUsersController extends AbstractController
{
    private const array ALLOWED_RELATION_TYPES = [
        Usergroup::GROUP_USER_PERMISSION_ADMIN,
        Usergroup::GROUP_USER_PERMISSION_READER,
        Usergroup::GROUP_USER_PERMISSION_PENDING_INVITATION,
        Usergroup::GROUP_USER_PERMISSION_MODERATOR,
        Usergroup::GROUP_USER_PERMISSION_HRM,
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SettingsManager $settingsManager,
        private readonly UsergroupHelper $usergroupHelper,
    ) {}

    #[Route('', name: 'admin_usergroup_add_users_data', methods: ['GET'])]
    public function data(Request $request, int $id): JsonResponse
    {
        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $isSocialGroup = Usergroup::SOCIAL_CLASS === $usergroup->getGroupType();
        $relationType = (int) $request->query->get('relation', (string) Usergroup::GROUP_USER_PERMISSION_READER);
        if (!\in_array($relationType, self::ALLOWED_RELATION_TYPES, true)) {
            $relationType = Usergroup::GROUP_USER_PERMISSION_READER;
        }

        // Get users already in the group for this relation
        $membersQb = $this->em->createQueryBuilder()
            ->select('IDENTITY(ru.user) AS userId')
            ->from(UsergroupRelUser::class, 'ru')
            ->where('ru.usergroup = :ugId')
            ->setParameter('ugId', $id, Types::INTEGER)
        ;

        if ($isSocialGroup) {
            $membersQb->andWhere('ru.relationType = :rel')
                ->setParameter('rel', $relationType, Types::INTEGER)
            ;
        }
        // A normal class has no per-user "role" — every UsergroupRelUser row is a member,
        // whichever screen or CSV import created it, regardless of its relationType. Filtering
        // by Usergroup::GROUP_USER_PERMISSION_READER here (a Doctrine-entity constant that,
        // unlike every other GROUP_USER_PERMISSION_* value, does not match the legacy global
        // constant of the same name — legacy uses 0, this constant is 2) hid every member
        // added via CSV import, which persists with the legacy value; see save() below.

        $memberRows = $membersQb->getQuery()->getArrayResult();
        $memberIds = array_map(static fn (array $r): int => (int) $r['userId'], $memberRows);

        // Search filters
        $keyword = trim((string) $request->query->get('keyword', ''));
        $firstLetter = trim((string) $request->query->get('firstLetter', ''));

        $orderByOfficialCode = 'true' === $this->settingsManager->getSetting(
            'display.order_user_list_by_official_code',
            true,
        );

        $qb = $this->em->createQueryBuilder()
            ->select('u.id, u.firstname, u.lastname, u.username, u.officialCode')
            ->from(User::class, 'u')
            ->where('u.status != :anonymous')
            ->setParameter('anonymous', User::ANONYMOUS, Types::INTEGER)
            ->andWhere('u.active != :softDeleted')
            ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
        ;

        if ($orderByOfficialCode) {
            $qb
                ->orderBy('u.officialCode', 'ASC')
                ->addOrderBy('u.lastname', 'ASC')
                ->addOrderBy('u.firstname', 'ASC')
            ;
        } else {
            $qb
                ->orderBy('u.lastname', 'ASC')
                ->addOrderBy('u.firstname', 'ASC')
            ;
        }

        if ('' !== $keyword) {
            $qb->andWhere(
                '(u.firstname LIKE :kw OR u.lastname LIKE :kw OR u.username LIKE :kw OR u.email LIKE :kw OR u.officialCode LIKE :kw)'
            )
                ->setParameter('kw', '%'.$keyword.'%')
            ;
        }

        if ('' !== $firstLetter && '%' !== $firstLetter) {
            $qb->andWhere('u.lastname LIKE :fl')
                ->setParameter('fl', $firstLetter.'%')
            ;
        }

        $allUsers = $qb->getQuery()->getArrayResult();

        $usersInGroup = [];
        $usersNotInGroup = [];

        foreach ($allUsers as $user) {
            $userId = (int) $user['id'];
            $firstName = (string) $user['firstname'];
            $lastName = (string) $user['lastname'];
            $username = (string) $user['username'];
            $officialCode = trim((string) ($user['officialCode'] ?? ''));
            $label = $lastName.', '.$firstName.' ('.$username.')';

            if ('' !== $officialCode) {
                $label = $orderByOfficialCode
                    ? $officialCode.' - '.$label
                    : $label.' - '.$officialCode;
            }

            $userData = [
                'id' => $userId,
                'label' => $label,
                'officialCode' => $officialCode,
                'lastName' => $lastName,
                'firstName' => $firstName,
                'username' => $username,
            ];

            if (\in_array($userId, $memberIds, true)) {
                $usersInGroup[] = $userData;
            } else {
                $usersNotInGroup[] = $userData;
            }
        }

        return $this->json([
            'groupId' => $id,
            'groupTitle' => $usergroup->getTitle(),
            'isSocialGroup' => $isSocialGroup,
            'relationType' => $relationType,
            'orderByOfficialCode' => $orderByOfficialCode,
            'usersInGroup' => $usersInGroup,
            'usersNotInGroup' => $usersNotInGroup,
            'searchableExtraFields' => $this->getSearchableUserExtraFields(),
        ]);
    }

    #[Route('/extra-field-matches', name: 'admin_usergroup_extra_field_matches', methods: ['GET'])]
    public function extraFieldMatches(Request $request, int $id): JsonResponse
    {
        if (null === $this->em->find(Usergroup::class, $id)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $fieldId = $request->query->getInt('fieldId');
        $value = trim((string) $request->query->get('value', ''));
        if ($fieldId <= 0 || '' === $value || mb_strlen($value) > 255) {
            return $this->json(['error' => 'Invalid extra field filter'], Response::HTTP_BAD_REQUEST);
        }

        /** @var ExtraField|null $field */
        $field = $this->em->getRepository(ExtraField::class)->findOneBy([
            'id' => $fieldId,
            'itemType' => ExtraField::USER_FIELD_TYPE,
            'filter' => true,
        ]);
        if (null === $field) {
            return $this->json(['error' => 'Extra field is not searchable'], Response::HTTP_BAD_REQUEST);
        }

        $fieldType = $field->getValueType();
        $choiceTypes = [
            ExtraField::FIELD_TYPE_RADIO,
            ExtraField::FIELD_TYPE_SELECT,
            ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
            ExtraField::FIELD_TYPE_TAG,
        ];

        // Do not accept arbitrary values for choice fields, even when the caller skips the UI.
        if (\in_array($fieldType, $choiceTypes, true)) {
            $allowed = array_column($this->getExtraFieldOptions($field), 'value');
            if (!\in_array($value, $allowed, true)) {
                return $this->json(['error' => 'Invalid extra field option'], Response::HTTP_BAD_REQUEST);
            }
        } elseif (ExtraField::FIELD_TYPE_CHECKBOX === $fieldType && !\in_array($value, ['0', '1'], true)) {
            return $this->json(['error' => 'Invalid checkbox value'], Response::HTTP_BAD_REQUEST);
        }

        $qb = $this->em->createQueryBuilder()
            ->select('DISTINCT u.id')
            ->from(User::class, 'u')
            ->innerJoin(ExtraFieldValues::class, 'efv', Join::WITH, 'efv.itemId = u.id AND efv.field = :field')
            ->where('u.status != :anonymous')
            ->andWhere('u.active != :softDeleted')
            ->setParameter('field', $field)
            ->setParameter('anonymous', User::ANONYMOUS, Types::INTEGER)
            ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
        ;

        if (ExtraField::FIELD_TYPE_SELECT_MULTIPLE === $fieldType) {
            // Multi-select extra field values are stored as semicolon-delimited option values.
            // Matching with both delimiters avoids confusing e.g. "1" with "11".
            $qb->andWhere("LOCATE(:optionToken, CONCAT(';', CONCAT(efv.fieldValue, ';'))) > 0")
                ->setParameter('optionToken', ';'.$value.';')
            ;
        } elseif (\in_array($fieldType, $choiceTypes, true) || ExtraField::FIELD_TYPE_CHECKBOX === $fieldType) {
            $qb->andWhere('efv.fieldValue = :value')
                ->setParameter('value', $value)
            ;
        } else {
            // LOCATE treats %, _ and other user input as literal characters, not SQL wildcards.
            $qb->andWhere('LOCATE(LOWER(:value), LOWER(efv.fieldValue)) > 0')
                ->setParameter('value', $value)
            ;
        }

        $rows = $qb->getQuery()->getArrayResult();

        return $this->json([
            'userIds' => array_map(static fn (array $row): int => (int) $row['id'], $rows),
        ]);
    }

    /**
     * @return array<int, array{id: int, label: string, type: int, options: array}>
     */
    private function getSearchableUserExtraFields(): array
    {
        $fields = $this->em->getRepository(ExtraField::class)->findBy(
            ['itemType' => ExtraField::USER_FIELD_TYPE, 'filter' => true],
            ['fieldOrder' => 'ASC', 'id' => 'ASC'],
        );

        return array_map(fn (ExtraField $field): array => [
            'id' => (int) $field->getId(),
            'label' => $field->getDisplayText() ?: $field->getVariable(),
            'type' => $field->getValueType(),
            'options' => $this->getExtraFieldOptions($field),
        ], $fields);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getExtraFieldOptions(ExtraField $field): array
    {
        if (ExtraField::FIELD_TYPE_CHECKBOX === $field->getValueType()) {
            return [
                ['value' => '1', 'label' => 'Yes'],
                ['value' => '0', 'label' => 'No'],
            ];
        }

        if (!\in_array($field->getValueType(), [
            ExtraField::FIELD_TYPE_RADIO,
            ExtraField::FIELD_TYPE_SELECT,
            ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
            ExtraField::FIELD_TYPE_TAG,
        ], true)) {
            return [];
        }

        $options = $this->em->getRepository(ExtraFieldOptions::class)->findBy(
            ['field' => $field],
            ['optionOrder' => 'ASC', 'id' => 'ASC'],
        );

        $result = [];
        foreach ($options as $option) {
            $value = ExtraField::FIELD_TYPE_TAG === $field->getValueType()
                ? (string) $option->getId()
                : $option->getValue();
            if (null === $value || '' === $value) {
                continue;
            }

            $result[] = [
                'value' => (string) $value,
                'label' => $option->getDisplayText() ?: (string) $value,
            ];
        }

        return $result;
    }

    #[Route('', name: 'admin_usergroup_add_users_save', methods: ['POST'])]
    public function save(Request $request, int $id): JsonResponse
    {
        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $isSocialGroup = Usergroup::SOCIAL_CLASS === $usergroup->getGroupType();
        $relationType = (int) $request->request->get('relationType', Usergroup::GROUP_USER_PERMISSION_READER);

        if ($isSocialGroup) {
            if (!\in_array($relationType, self::ALLOWED_RELATION_TYPES, true)) {
                return $this->json(['error' => 'Invalid relation type'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            // Must match every other class-membership writer (CSV imports, legacy
            // ClassId user import) so subscribeUsers()'s own "current members" diff — which
            // reads every relation_type back — doesn't wipe members those wrote. See the
            // matching comment in data() above for why this isn't
            // Usergroup::GROUP_USER_PERMISSION_READER.
            $relationType = 0;
        }

        $rawIds = $request->request->all('userIds');
        $userIds = array_map('intval', (array) $rawIds);

        // Delegates to the legacy workflow: diffs against the current membership,
        // (un)subscribes users to/from every session and course linked to this
        // usergroup, persists the usergroup_rel_user rows, and re-syncs any
        // course groups linked to the usergroup.
        $this->usergroupHelper->subscribeUsers($id, $userIds, true, $relationType);

        return $this->json(['success' => true]);
    }

    #[Route('/export', name: 'admin_usergroup_add_users_export', methods: ['GET'])]
    public function export(int $id): StreamedResponse
    {
        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup) {
            return new StreamedResponse(static function (): void {}, Response::HTTP_NOT_FOUND);
        }

        $rows = $this->em->createQueryBuilder()
            ->select('u.username')
            ->from(UsergroupRelUser::class, 'ru')
            ->join('ru.user', 'u')
            ->where('ru.usergroup = :ugId')
            ->setParameter('ugId', $id, Types::INTEGER)
            ->orderBy('u.lastname', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $groupTitle = $usergroup->getTitle();
        $filename = 'export_user_class_'.date('Y-m-d_H-i-s').'.csv';

        $response = new StreamedResponse(static function () use ($rows, $groupTitle): void {
            $handle = fopen('php://output', 'w');
            if (false === $handle) {
                return;
            }
            fputcsv($handle, CsvFormulaEscaper::escapeRow(['UserName', 'ClassName']));
            foreach ($rows as $row) {
                fputcsv($handle, CsvFormulaEscaper::escapeRow([$row['username'], $groupTitle]));
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
