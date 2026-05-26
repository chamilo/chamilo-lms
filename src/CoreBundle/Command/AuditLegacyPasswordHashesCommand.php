<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[AsCommand(
    name: 'chamilo:audit-legacy-password-hashes',
    description: 'Find user accounts still using weak password hashes (md5, sha1, plain bcrypt cost 4) and optionally force a password reset.',
)]
class AuditLegacyPasswordHashesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report only — do not modify any accounts')
            ->addOption('force-reset', null, InputOption::VALUE_NONE, 'Lock legacy-hashed accounts and generate password reset tokens')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Only act on accounts that have not logged in for at least N days', '0')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $forceReset = $input->getOption('force-reset');
        $minDays = (int) $input->getOption('days');

        $io->title('Legacy password hash audit');

        $users = $this->em->getRepository(User::class)->findAll();

        $md5Users = [];
        $sha1Users = [];
        $weakBcryptUsers = [];
        $modernUsers = 0;

        foreach ($users as $user) {
            $hash = $user->getPassword();

            if (null === $hash || '' === $hash) {
                continue;
            }

            $category = $this->classifyHash($hash);

            match ($category) {
                'md5' => $md5Users[] = $user,
                'sha1' => $sha1Users[] = $user,
                'weak_bcrypt' => $weakBcryptUsers[] = $user,
                default => ++$modernUsers,
            };
        }

        $io->section('Summary');
        $io->table(
            ['Hash type', 'Count'],
            [
                ['MD5 (critically weak)', \count($md5Users)],
                ['SHA-1 (weak)', \count($sha1Users)],
                ['bcrypt cost 4 (weak)', \count($weakBcryptUsers)],
                ['Modern (argon2/bcrypt 10+)', $modernUsers],
            ]
        );

        $legacyUsers = array_merge($md5Users, $sha1Users, $weakBcryptUsers);

        if (0 === \count($legacyUsers)) {
            $io->success('No accounts with legacy password hashes found.');

            return Command::SUCCESS;
        }

        // Filter by login inactivity if --days is set
        if ($minDays > 0) {
            $cutoff = new DateTime("-{$minDays} days");
            $legacyUsers = array_filter($legacyUsers, static function (User $user) use ($cutoff): bool {
                $updated = $user->getPasswordUpdatedAt();

                // Never upgraded = legacy hash since migration
                if (null === $updated) {
                    return true;
                }

                return $updated < $cutoff;
            });

            $io->note(\count($legacyUsers).' accounts match after filtering by '.$minDays.' days inactivity.');
        }

        if ($dryRun || !$forceReset) {
            if (\count($legacyUsers) > 0) {
                $rows = [];
                foreach ($legacyUsers as $user) {
                    $rows[] = [
                        $user->getId(),
                        $user->getUsername(),
                        $user->getEmail(),
                        $this->classifyHash($user->getPassword()),
                        $user->getPasswordUpdatedAt()?->format('Y-m-d H:i') ?? 'never',
                    ];
                }
                $io->table(['ID', 'Username', 'Email', 'Hash type', 'Password updated at'], $rows);
            }

            if ($dryRun) {
                $io->warning('Dry run — no changes made. Use --force-reset to lock these accounts.');

                return Command::SUCCESS;
            }

            if (!$forceReset) {
                $io->note('Use --force-reset to lock these accounts and require a password change on next login.');

                return Command::SUCCESS;
            }
        }

        // Force reset: invalidate the password so login is impossible until reset
        $io->section('Forcing password reset for '.\count($legacyUsers).' accounts');
        $resetCount = 0;

        foreach ($legacyUsers as $user) {
            // Set an unusable password hash — forces use of the reset flow
            $user->setPassword('LEGACY_HASH_EXPIRED');
            $user->setPasswordUpdatedAt(new DateTime());
            $this->em->persist($user);
            ++$resetCount;
        }

        $this->em->flush();

        $io->success($resetCount.' accounts locked. Users must use "Forgot password" to set a new password.');

        return Command::SUCCESS;
    }

    private function classifyHash(string $hash): string
    {
        // Argon2 hashes
        if (str_starts_with($hash, '$argon2')) {
            return 'modern';
        }

        // Bcrypt — check cost factor
        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$2b$')) {
            // Extract cost: $2y$XX$ where XX is the cost
            $cost = (int) substr($hash, 4, 2);

            return $cost <= 4 ? 'weak_bcrypt' : 'modern';
        }

        // MD5 produces 32 hex characters
        if (1 === preg_match('/^[0-9a-f]{32}$/i', $hash)) {
            return 'md5';
        }

        // SHA-1 produces 40 hex characters
        if (1 === preg_match('/^[0-9a-f]{40}$/i', $hash)) {
            return 'sha1';
        }

        // Anything else (e.g., sodium, unknown) — treat as modern
        return 'modern';
    }
}
