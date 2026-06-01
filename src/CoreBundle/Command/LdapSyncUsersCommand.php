<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Helpers\UserAnonymizationHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\InvalidCredentialsException;
use Symfony\Component\Ldap\Ldap;

#[AsCommand(
    name: 'app:ldap-sync-users',
    description: 'Synchronise user accounts from LDAP: creates new users, updates existing ones, and disables/deletes users not found in LDAP.',
)]
class LdapSyncUsersCommand extends Command
{
    public function __construct(
        private readonly AuthenticationConfigHelper $authConfigHelper,
        private readonly Ldap $ldap,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly UserAnonymizationHelper $anonymizationHelper,
        private readonly ExtraFieldRepository $extraFieldRepo,
        private readonly ExtraFieldValuesRepository $extraFieldValuesRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Dry-run mode: show what would change without modifying the database'
            )
            ->addOption(
                'delete',
                null,
                InputOption::VALUE_NONE,
                'Delete users not found in LDAP (default behaviour is to disable them)'
            )
            ->addOption(
                'reenable',
                null,
                InputOption::VALUE_NONE,
                'Re-enable disabled users that are found again in LDAP'
            )
            ->addOption(
                'anonymize',
                null,
                InputOption::VALUE_NONE,
                'Anonymize user accounts that have been disabled for more than 3 years'
            )
            ->addOption(
                'skip',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Usernames to skip even if absent from LDAP (e.g. --skip=admin --skip=anonymous)',
                []
            )
            ->addOption(
                'search_extra_field',
                null,
                InputOption::VALUE_REQUIRED,
                'Extra field variable name used to match LDAP entries to existing Chamilo users when the username does not match (e.g. --search_extra_field=matricule)'
            )
            ->addOption(
                'force_adding_extra_authsource',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Add an extra auth source to every created or updated user (e.g. --force_adding_extra_authsource=oauth2). Can be repeated for multiple sources.',
                []
            )
            ->setHelp(<<<'HELP'
                This command synchronizes user accounts between Chamilo and one or more LDAP directories.

                  — Creates user accounts found in LDAP but not in Chamilo.
                    When multiple access URLs are configured, each user is created under the URL whose LDAP directory contained them.
                  — Updates existing accounts found in LDAP (name, e-mail, and other mapped fields).
                    Use <info>--reenable</info> to also re-activate accounts that were previously disabled.
                  — Disables accounts present in Chamilo but absent from all configured LDAP directories.
                    Use <info>--delete</info> to permanently delete them instead (only affects users whose auth source is LDAP).
                  — Optionally anonymizes accounts that have been disabled for more than 3 years (<info>--anonymize</info>).

                The LDAP connection and field mapping are read from the authentication configuration
                (<comment>config/packages/security.yaml</comment> or the equivalent per-URL override).
                The username field (<comment>uid_key</comment>) is used to match LDAP entries to Chamilo accounts.

                Use <info>--skip</info> to protect specific accounts from being disabled or deleted even when absent from LDAP
                (e.g., the built-in admin or the anonymous user):

                  <info>php bin/console app:ldap-sync-users --skip=admin --skip=anonymous</info>

                Use <info>--search_extra_field</info> to match LDAP entries to existing Chamilo users via an extra field
                when the username does not match. The extra field variable name must correspond to an <comment>extra_*</comment>
                key in <comment>data_correspondence</comment>. When a match is found the existing user is updated and their
                username is set to the LDAP value so future syncs recognise them directly:

                  <info>php bin/console app:ldap-sync-users --search_extra_field=matricule</info>

                Use <info>--force_adding_extra_authsource</info> to add an extra authentication source to every user
                created or updated by the sync (idempotent — already-assigned sources are not duplicated).
                Useful to allow OAuth2 login alongside LDAP:

                  <info>php bin/console app:ldap-sync-users --force_adding_extra_authsource=oauth2</info>

                The option can be repeated to add several sources at once:

                  <info>php bin/console app:ldap-sync-users --force_adding_extra_authsource=oauth2 --force_adding_extra_authsource=azure</info>

                Run in dry-run mode first to preview all changes without writing to the database:

                  <info>php bin/console app:ldap-sync-users --dry-run -v</info>
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $testMode = (bool) $input->getOption('dry-run');
        $deleteNotInLdap = (bool) $input->getOption('delete');
        $reenableFound = (bool) $input->getOption('reenable');
        $anonymizeLongDisabled = (bool) $input->getOption('anonymize');

        /** @var string[] $skipList */
        $skipList = $input->getOption('skip');

        /** @var string|null $searchExtraField */
        $searchExtraField = $input->getOption('search_extra_field') ?: null;

        /** @var string[] $extraAuthSources */
        $extraAuthSources = $input->getOption('force_adding_extra_authsource');

        if ($testMode) {
            $io->note('Running in TEST mode — no changes will be written to the database.');
        }

        // ── 1. Load all database users (id > 1) indexed by lowercase username ──

        $dbUsers = [];

        foreach ($this->userRepository->findAll() as $user) {
            if ($user->getId() > 1) {
                $dbUsers[mb_strtolower($user->getUsername())] = $user;
            }
        }

        $io->info(\sprintf('%d users loaded from the database.', \count($dbUsers)));

        $rootUser = $this->userRepository->getRootUser();

        // ── 2. Iterate all access URLs and sync each enabled LDAP ──

        $accessUrls = $this->accessUrlRepository->findAll();
        $ldapUsernames = [];
        $atLeastOneEnabled = false;

        foreach ($accessUrls as $accessUrl) {
            $ldapConfig = $this->authConfigHelper->getLdapConfig($accessUrl);

            if (!$ldapConfig['enabled']) {
                continue;
            }

            $atLeastOneEnabled = true;
            $io->section(\sprintf('Access URL: %s', $accessUrl->getUrl()));

            try {
                $this->ldap->bind($ldapConfig['search_dn'], $ldapConfig['search_password']);
            } catch (InvalidCredentialsException $e) {
                $io->error(\sprintf('LDAP bind failed for "%s": %s', $accessUrl->getUrl(), $e->getMessage()));

                continue;
            }

            $uidKey = $ldapConfig['uid_key'];
            $baseFilter = '(objectClass='.$ldapConfig['object_class'].')';
            $filter = !empty($ldapConfig['filter'])
                ? '(&'.$baseFilter.'('.$ldapConfig['filter'].'))'
                : $baseFilter;

            $ldapEntries = $this->ldap
                ->query($ldapConfig['base_dn'], $filter)
                ->execute()
                ->toArray()
            ;

            $io->info(\sprintf('%d entries found in LDAP.', \count($ldapEntries)));

            /** @var array<string, string> $dataCorrespondence */
            $dataCorrespondence = array_filter($ldapConfig['data_correspondence']);

            // ── 3. Create / update users found in this LDAP ──

            /** @var array<array{User, Entry}> $pendingExtraFields */
            $pendingExtraFields = [];

            foreach ($ldapEntries as $entry) {
                $uidValues = $entry->getAttribute($uidKey);

                if (null === $uidValues || 0 === \count($uidValues)) {
                    $io->warning(\sprintf('Entry "%s" has no "%s" attribute — skipping.', $entry->getDn(), $uidKey));

                    continue;
                }

                $username = mb_strtolower($uidValues[0]);
                $ldapUsernames[$username] = true;

                $isNew = !isset($dbUsers[$username]);

                // When the username is unknown, try to match via an extra field value.
                if ($isNew && null !== $searchExtraField) {
                    $ldapAttrConfig = $dataCorrespondence['extra_'.$searchExtraField] ?? null;
                    if (null !== $ldapAttrConfig) {
                        $ldapAttrStr = (string) $ldapAttrConfig;
                        $extraFieldValue = str_starts_with($ldapAttrStr, '=')
                            ? substr($ldapAttrStr, 1)
                            : ($entry->getAttribute($ldapAttrStr) ?? [])[0] ?? null;

                        if (null !== $extraFieldValue) {
                            $extraField = $this->extraFieldRepo->findByVariable(ExtraField::USER_FIELD_TYPE, $searchExtraField);
                            if (null !== $extraField) {
                                $match = $this->extraFieldValuesRepo->findByVariableAndValue($extraField, $extraFieldValue);
                                if ($match instanceof ExtraFieldValues) {
                                    $matchedUser = $this->userRepository->find($match->getItemId());
                                    if (null !== $matchedUser) {
                                        $isNew = false;
                                        $oldUsername = mb_strtolower($matchedUser->getUsername());
                                        unset($dbUsers[$oldUsername]);
                                        $dbUsers[$username] = $matchedUser;
                                        $io->writeln(\sprintf(
                                            '%sMatched LDAP entry to existing user "%s" via extra field "%s"="%s" — username will be updated to "%s".',
                                            $testMode ? '[TEST] ' : '',
                                            $matchedUser->getUsername(),
                                            $searchExtraField,
                                            $extraFieldValue,
                                            $username,
                                        ));
                                    }
                                }
                            }
                        }
                    }
                }

                if ($testMode) {
                    $io->writeln(\sprintf('[TEST] Would %s user: %s', $isNew ? 'create' : 'update', $username));

                    continue;
                }

                if ($isNew) {
                    $user = (new User())
                        ->setCreatorId($rootUser->getId())
                        ->addAuthSourceByAuthentication(UserAuthSource::LDAP, $accessUrl)
                    ;
                    $dbUsers[$username] = $user;
                } else {
                    $user = $dbUsers[$username];
                }

                $this->applyLdapFields($user, $entry, $dataCorrespondence, $isNew, (bool) $ldapConfig['synch_user_role_on_update']);
                $user->setUsername($username);

                foreach ($extraAuthSources as $authSource) {
                    $user->addAuthSourceByAuthentication($authSource, $accessUrl);
                }

                if (!$user->isActive() && $reenableFound) {
                    $user->setActive(1);
                    $io->writeln(\sprintf('Re-enabled user: %s', $username));
                }

                $this->userRepository->updateUser($user, false);
                $accessUrl->addUser($user);

                $pendingExtraFields[] = [$user, $entry];

                if ($isNew) {
                    $io->writeln(\sprintf('Created user: %s', $username));
                } elseif ($output->isVerbose()) {
                    $io->writeln(\sprintf('Updated user: %s', $username));
                }
            }

            if (!$testMode) {
                $this->entityManager->flush();

                foreach ($pendingExtraFields as [$user, $entry]) {
                    $this->syncExtraFields($user, $entry, $dataCorrespondence);
                }
            }
        }

        if (!$atLeastOneEnabled) {
            $io->error('LDAP is not enabled in any access URL configuration.');

            return Command::FAILURE;
        }

        // ── 4. Disable or delete users absent from LDAP ──

        $now = new DateTime();

        foreach ($dbUsers as $username => $user) {
            if (isset($ldapUsernames[$username]) || \in_array($username, $skipList, true)) {
                continue;
            }

            if ($deleteNotInLdap) {
                if ($testMode) {
                    $io->writeln(\sprintf('[TEST] Would delete user: %s', $username));
                } else {
                    $this->userRepository->deleteUser($user);
                    $io->writeln(\sprintf('Deleted user: %s', $username));
                }
            } elseif ($testMode) {
                $io->writeln(\sprintf('[TEST] Would disable user: %s', $username));
            } elseif ($user->isActive()) {
                $user->setActive(0);
                $this->entityManager->persist($user);

                $track = (new TrackEDefault())
                    ->setDefaultUserId(1)
                    ->setDefaultDate($now)
                    ->setDefaultEventType('user_disable')
                    ->setDefaultValueType('user_id')
                    ->setDefaultValue((string) $user->getId())
                ;
                $this->entityManager->persist($track);

                $io->writeln(\sprintf('Disabled user: %s', $username));
            }
        }

        if (!$testMode && !$deleteNotInLdap) {
            $this->entityManager->flush();
        }

        // ── 5. Anonymize users disabled for more than 3 years ──

        if ($anonymizeLongDisabled) {
            $this->anonymizeLongDisabledUsers($io, $testMode);
        }

        $io->success('LDAP synchronisation completed.');

        return Command::SUCCESS;
    }

    /**
     * Maps LDAP entry attributes onto a User entity using the data_correspondence config.
     * Extra field mappings (extra_* keys) are handled separately by syncExtraFields().
     *
     * @param array<string, string> $dataCorrespondence
     */
    private function applyLdapFields(User $user, Entry $entry, array $dataCorrespondence, bool $isNew = true, bool $synchUserRoleOnUpdate = true): void
    {
        $fieldsMap = [
            'firstname' => 'setFirstname',
            'lastname' => 'setLastname',
            'email' => 'setEmail',
            'active' => 'setActive',
            'role' => 'setRoles',
            'locale' => 'setLocale',
            'phone' => 'setPhone',
        ];

        foreach ($fieldsMap as $key => $setter) {
            if (!isset($dataCorrespondence[$key])) {
                continue;
            }

            $attrConfig = $dataCorrespondence[$key];
            if (str_starts_with($attrConfig, '=')) {
                $value = substr($attrConfig, 1);
            } else {
                $value = ($entry->getAttribute($attrConfig) ?? [])[0] ?? '';
            }

            if ('active' === $key) {
                $user->{$setter}((int) $value);
            } elseif ('role' === $key) {
                if ($isNew || $synchUserRoleOnUpdate) {
                    $user->{$setter}([$value]);
                }
            } else {
                $user->{$setter}($value);
            }
        }
    }

    /**
     * Saves extra_* data_correspondence entries as user extra field values.
     * Must be called after flush() so the user already has a valid ID.
     *
     * @param array<string, string> $dataCorrespondence
     */
    private function syncExtraFields(User $user, Entry $entry, array $dataCorrespondence): void
    {
        foreach ($dataCorrespondence as $key => $ldapAttr) {
            if (!str_starts_with($key, 'extra_') || '' === (string) $ldapAttr) {
                continue;
            }

            $variable = substr($key, \strlen('extra_'));
            $extraField = $this->extraFieldRepo->findByVariable(ExtraField::USER_FIELD_TYPE, $variable);

            if (null === $extraField) {
                continue;
            }

            $ldapAttrStr = (string) $ldapAttr;
            $value = str_starts_with($ldapAttrStr, '=')
                ? substr($ldapAttrStr, 1)
                : ($entry->getAttribute($ldapAttrStr) ?? [])[0] ?? null;
            $this->extraFieldValuesRepo->updateItemData($extraField, $user, $value);
        }
    }

    /**
     * @throws Exception
     */
    private function anonymizeLongDisabledUsers(SymfonyStyle $io, bool $testMode): void
    {
        $conn = $this->entityManager->getConnection();

        /** @var string[] $longDisabledIds */
        $longDisabledIds = $conn
            ->executeQuery(
                "SELECT default_value
                 FROM track_e_default
                 WHERE default_event_type = 'user_disable' AND default_value_type = 'user_id'
                 GROUP BY default_value
                 HAVING MAX(default_date) < DATE_SUB(NOW(), INTERVAL 3 YEAR)"
            )
            ->fetchFirstColumn()
        ;

        if ([] === $longDisabledIds) {
            return;
        }

        /** @var string[] $alreadyAnonymizedIds */
        $alreadyAnonymizedIds = $conn
            ->executeQuery(
                "SELECT DISTINCT default_value
                 FROM track_e_default
                 WHERE default_event_type = 'user_anonymized'
                   AND default_value_type = 'user_id'"
            )
            ->fetchFirstColumn()
        ;

        foreach (array_diff($longDisabledIds, $alreadyAnonymizedIds) as $userId) {
            $user = $this->userRepository->find((int) $userId);

            if (null === $user || $user->isActive()) {
                continue;
            }

            if ($testMode) {
                $io->writeln(\sprintf('[TEST] Would anonymize user ID: %d', $userId));

                continue;
            }

            $this->anonymizationHelper->anonymize($user);

            $io->writeln(\sprintf('Anonymized user ID: %d', $userId));
        }
    }
}
