<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Database;
use DateTime;
use DateInterval;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use MessageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Contracts\Translation\TranslatorInterface;
use UserManager;

class ProcessUserDataRequestsCommand extends Command
{
    protected static $defaultName = 'app:process-user-data-requests';

    public function __construct(
        private readonly Connection $connection,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly SettingsManager $settingsManager,
        private readonly MailerInterface $mailer,
        private readonly EntityManager $em,
        private readonly TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Process user data requests for personal data actions.')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug mode')
            ->setHelp('This command processes user data requests that require administrative action.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Database::setManager($this->em);

        $container = $this->getApplication()->getKernel()->getContainer();
        Container::setContainer($container);

        $io = new SymfonyStyle($input, $output);
        $debug = $input->getOption('debug');

        if ($debug) {
            $io->note('Debug mode activated');
        }

        $defaultSenderId = 1;
        $accessUrl = $this->accessUrlHelper->getCurrent();
        $numberOfDays = 7;
        $date = new DateTime();
        $date->sub(new DateInterval('P' . $numberOfDays . 'D'));
        $dateToString = $date->format('Y-m-d H:i:s');

        if ($accessUrl) {
            $message = $this->processUrlData($accessUrl->getId(), $defaultSenderId, $dateToString, $io, $debug);
            if ($debug) {
                $io->success($message);
            }
        }

        return Command::SUCCESS;
    }

    private function processUrlData(
        int $accessUrlId,
        int $defaultSenderId,
        string $dateToString,
        SymfonyStyle $io,
        bool $debug
    ): string {

        $sql = "
            SELECT u.id, v.updated_at
            FROM user AS u
            INNER JOIN extra_field_values AS v ON u.id = v.item_id
            WHERE (v.field_id IN (:deleteLegal, :deleteAccount))
            AND v.field_value = 1
            AND u.active <> :userSoftDeleted
            AND v.updated_at < :dateToString
        ";

        if ($this->accessUrlHelper->isMultiple()) {
            $sql .= " AND EXISTS (
                        SELECT 1 FROM access_url_rel_user rel
                        WHERE u.id = rel.user_id
                        AND rel.access_url_id = :accessUrlId)";
        }

        $extraFields = UserManager::createDataPrivacyExtraFields();
        $params = [
            'deleteLegal' => $extraFields['delete_legal'],
            'deleteAccount' => $extraFields['delete_account_extra_field'],
            'userSoftDeleted' => User::SOFT_DELETED,
            'dateToString' => $dateToString,
            'accessUrlId' => $accessUrlId
        ];

        $result = $this->connection->fetchAllAssociative($sql, $params);
        $usersToBeProcessed = [];

        foreach ($result as $user) {
            $usersToBeProcessed[] = $user;
        }

        if (empty($usersToBeProcessed)) {
            return "No users waiting for data actions for Access URL ID: {$accessUrlId}";
        }

        return $this->processUsers($usersToBeProcessed, $defaultSenderId, $io, $debug);
    }

    private function processUsers(
        array $users,
        int $defaultSenderId,
        SymfonyStyle $io,
        bool $debug
    ): string {

        $administrator = [
            'completeName' => $this->settingsManager->getSetting('admin.administrator_name'),
            'email' => $this->settingsManager->getSetting('admin.administrator_email'),
        ];

        $rootweb = $this->settingsManager->getSetting('platform.institution_url');
        $link = $rootweb . '/main/admin/user_list_consent.php';
        $subject = $this->translator->trans('A user is waiting for an action about his/her personal data request');
        $email = $this->settingsManager->getSetting('profile.data_protection_officer_email');
        $message = '';

        foreach ($users as $user) {
            $userId = $user['id'];
            $userInfo = $this->connection->fetchAssociative("SELECT * FROM user WHERE id = ?", [$userId]);
            $userInfo['complete_name'] = $userInfo['firstname'] . ' ' . $userInfo['lastname'];
            $userInfo['complete_name_with_username'] = $userInfo['complete_name'].' ('.$userInfo['username'].')';

            if (!$userInfo) {
                continue;
            }

            $content = $this->translator->trans(
                'The user %name% is waiting for an action about his/her personal data request. To manage personal data requests you can follow this link: %link%',
                ['%name%' => $userInfo['complete_name'], '%link%' => $link]
            );

            if ($email) {
                $emailMessage = (new TemplatedEmail())
                    ->from($administrator['email'])
                    ->to($email)
                    ->subject($subject)
                    ->html($content);

                $this->mailer->send($emailMessage);
            } else {
                MessageManager::sendMessageToAllAdminUsers($defaultSenderId, $subject, $content);
            }

            $date = (new DateTime($user['updated_at']))->format('Y-m-d H:i:s');
            $message .= sprintf(
                "User %s is waiting for an action since %s \n",
                $userInfo['complete_name_with_username'],
                $date
            );

            if ($debug) {
                $io->note("Processed user {$userInfo['complete_name']} with ID: {$userId}");
            }
        }

        return $message;
    }
}
