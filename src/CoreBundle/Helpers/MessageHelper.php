<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageAttachment;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

use const FILTER_VALIDATE_EMAIL;
use const PHP_SAPI;
use const PHP_URL_HOST;
use const UPLOAD_ERR_OK;

class MessageHelper
{
    private ?SessionInterface $session = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageRepository $messageRepository,
        private readonly UserRepository $userRepository,
        private readonly RequestStack $requestStack,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly SettingsManager $settingsManager,
        private readonly MailerInterface $mailer
    ) {
        if (PHP_SAPI !== 'cli') {
            $this->session = $this->requestStack->getSession();
        }
    }

    /**
     * Sends a simple message with optional attachments and notifications to HR users.
     */
    public function sendMessageSimple(
        int $receiverUserId,
        string $subject,
        string $message,
        int $senderId = 0,
        bool $sendCopyToDrhUsers = false,
        bool $uploadFiles = true,
        array $attachmentList = []
    ): ?int {
        $files = $_FILES ?: [];
        if (false === $uploadFiles) {
            $files = [];
        }

        if (!empty($attachmentList)) {
            $files = $attachmentList;
        }

        $result = $this->sendMessage(
            $receiverUserId,
            $subject,
            $message,
            $files,
            [],
            0,
            0,
            0,
            $senderId
        );

        if ($sendCopyToDrhUsers) {
            $accessUrl = $this->accessUrlHelper->getCurrent();
            if (null !== $accessUrl) {
                $drhList = $this->userRepository->getDrhListFromUser($receiverUserId, $accessUrl->getId());
                if (!empty($drhList)) {
                    $receiverInfo = $this->userRepository->find($receiverUserId);

                    foreach ($drhList as $drhUser) {
                        $drhMessage = \sprintf(
                            'Copy of message sent to %s',
                            $receiverInfo->getFirstname().' '.$receiverInfo->getLastname()
                        ).' <br />'.$message;

                        $this->sendMessageSimple(
                            $drhUser->getId(),
                            $subject,
                            $drhMessage,
                            $senderId
                        );
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Sends a message with attachments, forwards, and additional settings.
     */
    public function sendMessage(
        int $receiverUserId,
        string $subject,
        string $content,
        array $attachments = [],
        array $fileCommentList = [],
        int $groupId = 0,
        int $parentId = 0,
        int $editMessageId = 0,
        int $senderId = 0,
        int $forwardId = 0,
        bool $checkCurrentAudioId = false,
        bool $forceTitleWhenSendingEmail = false,
        ?int $msgType = null
    ): ?int {
        $sender = $this->userRepository->find($senderId);
        $receiver = $this->userRepository->find($receiverUserId);

        if (!$sender || !$receiver || !$receiver->isActive()) {
            return null;
        }

        $totalFileSize = 0;
        $attachmentList = $this->processAttachments($attachments, $fileCommentList, $totalFileSize);

        if ($totalFileSize > (int) $this->settingsManager->getSetting('message.message_max_upload_filesize')) {
            throw new Exception('Files size exceeds allowed limit.');
        }

        $parent = $this->messageRepository->find($parentId);

        if ($editMessageId) {
            $message = $this->messageRepository->find($editMessageId);
            if ($message) {
                $message->setTitle($subject);
                $message->setContent($content);
            }
        } else {
            $message = new Message();
            $message->setSender($sender)
                ->addReceiverTo($receiver)
                ->setTitle($subject)
                ->setContent($content)
                ->setGroup($groupId ? $this->getGroupById($groupId) : null)
                ->setParent($parent)
            ;

            if (null !== $msgType) {
                $message->setMsgType($msgType);
            }
        }

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        if ($forwardId) {
            $this->forwardAttachments($forwardId, $message);
        }

        if ($checkCurrentAudioId) {
            $this->attachAudioMessage($message);
        }

        $this->saveAttachments($attachmentList, $message);

        $this->addSenderAsReceiver($message, $sender);

        if ($forceTitleWhenSendingEmail) {
            $this->sendEmailNotification($receiver, $sender, $subject, $content, $attachmentList);
        }

        return $message->getId();
    }

    /**
     * Processes attachments, calculates total file size, and returns the attachment list.
     *
     * @param mixed $totalFileSize
     */
    private function processAttachments(array $attachments, array $fileCommentList, &$totalFileSize): array
    {
        $attachmentList = [];
        foreach ($attachments as $index => $attachment) {
            $comment = $fileCommentList[$index] ?? '';
            $size = $attachment['size'] ?? 0;

            if (\is_array($size)) {
                foreach ($size as $s) {
                    $totalFileSize += $s;
                }
            } else {
                $totalFileSize += $size;
            }

            $attachmentList[] = [
                'file' => $attachment,
                'comment' => $comment,
            ];
        }

        return $attachmentList;
    }

    /**
     * Forwards attachments from one message to another.
     */
    private function forwardAttachments(int $forwardId, Message $message): void
    {
        $forwardMessage = $this->messageRepository->find($forwardId);
        if ($forwardMessage) {
            foreach ($forwardMessage->getAttachments() as $attachment) {
                $message->addAttachment($attachment);
            }
            $this->entityManager->persist($message);
            $this->entityManager->flush();
        }
    }

    /**
     * Attaches an audio message from the current session to the message.
     */
    private function attachAudioMessage(Message $message): void
    {
        if ($this->session && $this->session->has('current_audio')) {
            $audio = $this->session->get('current_audio');

            if (!empty($audio['name'])) {
                $attachment = new MessageAttachment();
                $attachment->setFilename($audio['name'])
                    ->setComment('audio_message')
                    ->setMessage($message)
                ;

                $message->addAttachment($attachment);

                $this->entityManager->persist($attachment);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * Saves the provided attachments and links them to the message.
     */
    private function saveAttachments(array $attachments, Message $message): void
    {
        foreach ($attachments as $attachment) {
            $file = $attachment['file'];
            $comment = $attachment['comment'] ?? '';

            if ($file instanceof UploadedFile && UPLOAD_ERR_OK === $file->getError()) {
                $attachmentEntity = new MessageAttachment();
                $attachmentEntity->setFilename($file->getClientOriginalName())
                    ->setSize($file->getSize())
                    ->setPath($file->getRealPath())
                    ->setMessage($message)
                    ->setComment($comment)
                ;

                $message->addAttachment($attachmentEntity);
                $this->entityManager->persist($attachmentEntity);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * Adds the sender as a receiver in the message to keep track of the sent message.
     */
    private function addSenderAsReceiver(Message $message, User $sender): void
    {
        $messageRelUserRepository = $this->entityManager->getRepository(MessageRelUser::class);
        $existingRelation = $messageRelUserRepository->findOneBy([
            'message' => $message,
            'receiver' => $sender,
            'receiverType' => MessageRelUser::TYPE_SENDER,
        ]);

        if (!$existingRelation) {
            $messageRelUserSender = new MessageRelUser();
            $messageRelUserSender->setMessage($message)
                ->setReceiver($sender)
                ->setReceiverType(MessageRelUser::TYPE_SENDER)
            ;
            $this->entityManager->persist($messageRelUserSender);
            $this->entityManager->flush();
        }
    }

    /**
     * Sends an email notification to $receiver with given subject/content and optional attachments.
     * - Validates recipient email.
     * - Uses buildFromAddress() to construct a proper FROM (name + address).
     * - Attaches only OK-uploaded files.
     */
    private function sendEmailNotification(User $receiver, User $sender, string $subject, string $content, array $attachmentList): void
    {
        // Validate recipient email early
        $toAddress = $receiver->getEmail();
        if (!filter_var($toAddress, FILTER_VALIDATE_EMAIL)) {
            // No valid recipient → nothing to send (could log if needed)
            return;
        }

        try {
            $email = (new Email())
                ->from($this->buildFromAddress())
                ->to(new Address($toAddress, $receiver->getFullName() ?: $receiver->getUsername()))
                ->subject($subject)
                ->text($content)
                ->html($content)
            ;

            // Attach files if provided in the expected structure
            foreach ($attachmentList as $att) {
                $file = $att['file'] ?? null;
                if ($file instanceof UploadedFile && UPLOAD_ERR_OK === $file->getError()) {
                    $email->attachFromPath($file->getRealPath(), $file->getClientOriginalName());
                }
            }

            $this->mailer->send($email);
        } catch (Throwable $e) {
            // Soft-fail: log and continue
            error_log('Failed to send email: '.$e->getMessage());
        }
    }

    /**
     * Builds the FROM address used in outgoing emails.
     * Priority (name): mail.mailer_from_name → platform.site_name → "Chamilo"
     * Priority (email): mail.mailer_from_email → platform.administrator_email → noreply@{host}
     * Host resolution: AccessUrl → RequestStack → 'example.org'.
     */
    private function buildFromAddress(): Address
    {
        // Resolve display name
        $fromName = $this->settingsManager->getSetting('mail.mailer_from_name')
            ?: $this->settingsManager->getSetting('platform.site_name', true)
                ?: 'Chamilo';

        // Resolve email candidates (only existing/valid settings)
        $candidates = [
            $this->settingsManager->getSetting('mail.mailer_from_email'),
            $this->settingsManager->getSetting('platform.administrator_email'),
        ];
        foreach ($candidates as $cand) {
            if ($cand && filter_var($cand, FILTER_VALIDATE_EMAIL)) {
                return new Address($cand, $fromName);
            }
        }

        // Fallback host inference
        $host = null;
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if ($accessUrl && method_exists($accessUrl, 'getUrl')) {
            $host = parse_url((string) $accessUrl->getUrl(), PHP_URL_HOST);
        }
        if (!$host) {
            $req = $this->requestStack->getCurrentRequest();
            $host = $req?->getHost();
        }

        if (!$host || !str_contains($host, '.')) {
            $host = 'example.org';
        }

        // Last-resort fallback
        return new Address('noreply@'.$host, $fromName);
    }

    /**
     * Retrieves a user group by its ID.
     */
    private function getGroupById(int $groupId)
    {
        return $this->entityManager->getRepository(Usergroup::class)->find($groupId);
    }
}
