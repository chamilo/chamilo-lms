<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Erases all personal data from a user account and replaces it with anonymous placeholders.
 *
 * This is the Symfony equivalent of the legacy UserManager::anonymize() method.
 */
readonly class UserAnonymizationHelper
{
    /**
     * Extra-field variable names that must be deleted as part of the anonymization
     * (GDPR consent and deletion-request records).
     */
    private const CONSENT_FIELDS = [
        'legal_accept',
        'request_for_legal_agreement_consent_removal',
        'request_for_legal_agreement_consent_removal_justification',
        'request_for_delete_account_justification',
        'request_for_delete_account',
    ];

    /**
     * Tracking tables that store IP addresses, keyed by table name and the column
     * used to identify the user.
     *
     * @var array<string, array{user_column: string, ip_column: string}>
     */
    private const IP_TABLES = [
        'track_e_access' => [
            'user_column' => 'access_user_id',
            'ip_column' => 'user_ip',
        ],
        'track_e_course_access' => [
            'user_column' => 'user_id',
            'ip_column' => 'user_ip',
        ],
        'track_e_exercises' => [
            'user_column' => 'exe_user_id',
            'ip_column' => 'user_ip',
        ],
        'track_e_login' => [
            'user_column' => 'login_user_id',
            'ip_column' => 'user_ip',
        ],
        'track_e_online' => [
            'user_column' => 'login_user_id',
            'ip_column' => 'user_ip',
        ],
        'c_wiki' => [
            'user_column' => 'user_id',
            'ip_column' => 'user_ip',
        ],
        'ticket_message' => [
            'user_column' => 'sys_insert_user_id',
            'ip_column' => 'ip_address',
        ],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private IllustrationRepository $illustrationRepository,
        private EventLoggerHelper $eventLoggerHelper,
    ) {}

    /**
     * Anonymizes the given user: erases personal fields, deletes the profile picture,
     * removes consent extra fields, wipes IP addresses from tracking tables,
     * and logs the event.
     *
     * @param bool $anonymizeIp When true (default), replaces all stored IP addresses with 127.0.0.1
     *
     * @throws Exception
     */
    public function anonymize(User $user, bool $anonymizeIp = true): bool
    {
        $uniqueId = uniqid('anon', true);

        $user
            ->setFirstname($uniqueId)
            ->setLastname($uniqueId)
            ->setEmail($uniqueId.'@example.com')
            ->setEmailCanonical($uniqueId.'@example.com')
            ->setUsername($uniqueId)
            ->setUsernameCanonical($uniqueId)
            ->setPhone('')
            ->setOfficialCode('')
            ->setBiography('')
            ->setAddress('')
            ->setDateOfBirth(null)
            ->setCompetences('')
            ->setDiplomas('')
            ->setOpenarea('')
            ->setTeach('')
            ->setProductions(null)
            ->setOpenid('')
        ;

        $this->illustrationRepository->deleteIllustration($user);

        $this->deleteConsentExtraFields($user);
        $this->deleteAutoRemoveExtraFields($user);

        if ($anonymizeIp) {
            $this->anonymizeIpAddresses($user->getId());
        }

        $this->entityManager->flush();

        $this->eventLoggerHelper->addEvent('user_anonymized', 'user_id', $user->getId(), userId: 1);

        return true;
    }

    private function deleteConsentExtraFields(User $user): void
    {
        $extraFieldRepo = $this->entityManager->getRepository(ExtraField::class);
        $extraFieldValuesRepo = $this->entityManager->getRepository(ExtraFieldValues::class);

        foreach (self::CONSENT_FIELDS as $variable) {
            $field = $extraFieldRepo->findOneBy([
                'extraFieldType' => ExtraField::USER_FIELD_TYPE,
                'variable' => $variable,
            ]);

            if (null === $field) {
                continue;
            }

            $value = $extraFieldValuesRepo->findOneBy([
                'field' => $field,
                'itemId' => $user->getId(),
            ]);

            if (null !== $value) {
                $this->entityManager->remove($value);
            }
        }
    }

    private function deleteAutoRemoveExtraFields(User $user): void
    {
        $extraFieldRepo = $this->entityManager->getRepository(ExtraField::class);
        $extraFieldValuesRepo = $this->entityManager->getRepository(ExtraFieldValues::class);

        $autoRemoveFields = $extraFieldRepo->findBy([
            'autoRemove' => true,
            'extraFieldType' => ExtraField::USER_FIELD_TYPE,
        ]);

        foreach ($autoRemoveFields as $field) {
            $value = $extraFieldValuesRepo->findOneBy([
                'field' => $field,
                'itemId' => $user->getId(),
            ]);

            if (null !== $value) {
                $this->entityManager->remove($value);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function anonymizeIpAddresses(int $userId): void
    {
        $conn = $this->entityManager->getConnection();

        foreach (self::IP_TABLES as $table => $columns) {
            $conn->executeStatement(
                \sprintf(
                    'UPDATE %s SET %s = :ip WHERE %s = :userId',
                    $table,
                    $columns['ip_column'],
                    $columns['user_column'],
                ),
                ['ip' => '127.0.0.1', 'userId' => $userId],
            );
        }
    }
}
