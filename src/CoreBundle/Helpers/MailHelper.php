<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Exception;
use Notification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use const FILTER_VALIDATE_EMAIL;

final class MailHelper
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly BodyRendererInterface $bodyRenderer,
        private readonly ThemeHelper $themeHelper,
        private readonly ValidatorInterface $validator,
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * Resolve the platform FROM address using the configured mail settings.
     * Priority: mail.mailer_from_email > admin.administrator_email.
     */
    public function getPlatformFromAddress(): Address
    {
        $fromEmail = $this->settingsManager->getSetting('mail.mailer_from_email', true);
        $fromName = $this->settingsManager->getSetting('mail.mailer_from_name', true);

        if (empty($fromName)) {
            $fromName = $this->settingsManager->getSetting('platform.site_name') ?: 'Chamilo';
        }

        if (empty($fromEmail) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $fromEmail = $this->settingsManager->getSetting('admin.administrator_email', true);

            if (empty($fromName) || 'Chamilo' === $fromName) {
                $fromName = api_get_person_name(
                    $this->settingsManager->getSetting('admin.administrator_name', true),
                    $this->settingsManager->getSetting('admin.administrator_surname', true),
                    null,
                    PERSON_NAME_EMAIL_ADDRESS
                );
            }
        }

        return new Address((string) $fromEmail, (string) $fromName);
    }

    private function setNoreplyAndFromAddress(
        TemplatedEmail $email,
        array $sender,
        array $replyToAddress = []
    ): void {
        $emailConstraint = new Assert\Email();

        // Default values
        $notification = new Notification();
        $defaultSenderName = $notification->getDefaultPlatformSenderName();
        $defaultSenderEmail = $notification->getDefaultPlatformSenderEmail();

        // If the parameter is set, don't use the admin.
        $senderName = !empty($sender['name']) ? $sender['name'] : $defaultSenderName;
        $senderEmail = !empty($sender['email']) ? $sender['email'] : $defaultSenderEmail;

        $adminEmail = $this->settingsManager->getSetting('admin.administrator_email', true);
        $adminEmailValidation = $this->validator->validate($adminEmail, $emailConstraint);

        if (!empty($adminEmail) && 0 === $adminEmailValidation->count()) {
            $email
                ->getHeaders()
                ->addIdHeader('Errors-To', $adminEmail)
            ;
        }

        // Reply to first
        if (!empty($replyToAddress) && isset($replyToAddress['mail'], $replyToAddress['name'])) {
            $replyToEmailValidation = $this->validator->validate($replyToAddress['mail'], $emailConstraint);

            if (0 === $replyToEmailValidation->count()) {
                $email->addReplyTo(new Address($replyToAddress['mail'], $replyToAddress['name']));
            }
        }

        $email->from(new Address($senderEmail, $senderName));
    }

    public function send(
        string $recipientName,
        string $recipientEmail,
        string $subject,
        string $body,
        ?string $senderName = null,
        ?string $senderEmail = null,
        array $extra_headers = [],
        array $data_file = [],
        bool $embeddedImage = false,
        array $additionalParameters = [],
        ?string $sendErrorTo = null,
    ): bool {
        if (!api_valid_email($recipientEmail)) {
            return false;
        }

        $templatedEmail = new TemplatedEmail();

        $this->setNoreplyAndFromAddress(
            $templatedEmail,
            ['name' => $senderName, 'email' => $senderEmail],
            !empty($extra_headers['reply_to']) ? $extra_headers['reply_to'] : []
        );

        if ($sendErrorTo) {
            $templatedEmail
                ->getHeaders()
                ->addIdHeader('Errors-To', $sendErrorTo)
            ;
        }

        // Reply to first
        $replyToName = '';
        $replyToEmail = '';
        if (isset($extra_headers['reply_to'])) {
            $replyToEmail = $extra_headers['reply_to']['mail'];
            $replyToName = $extra_headers['reply_to']['name'];
        }

        try {
            $templatedEmail->subject($subject);

            $list = api_get_setting('workflows.send_all_emails_to', true);

            if (!empty($list) && isset($list['emails'])) {
                foreach ($list['emails'] as $email) {
                    $templatedEmail->cc($email);
                }
            }

            // Attachment (supports single/multiple, and legacy/new payloads)
            if (!empty($data_file)) {
                // Normalize: allow a single attachment array instead of a list
                $isSingleAttachment =
                    \is_array($data_file)
                    && (\array_key_exists('path', $data_file) || \array_key_exists('stream', $data_file))
                    && !array_is_list($data_file)
                ;

                if ($isSingleAttachment) {
                    $data_file = [$data_file];
                }

                foreach ($data_file as $file_attach) {
                    if (!\is_array($file_attach)) {
                        continue;
                    }

                    $filename = $file_attach['filename'] ?? null;

                    // 1) Attach from filesystem path(s)
                    if (!empty($file_attach['path'])) {
                        $path = $file_attach['path'];

                        if (\is_string($path)) {
                            if (!empty($filename) && \is_string($filename)) {
                                $templatedEmail->attachFromPath($path, $filename);
                            } else {
                                $templatedEmail->attachFromPath($path);
                            }
                        } elseif (\is_array($path)) {
                            // Multiple paths
                            $i = 0;
                            foreach ($path as $p) {
                                if (!\is_string($p) || '' === $p) {
                                    $i++;

                                    continue;
                                }

                                $nameForThis = null;

                                if (
                                    \is_array($filename)
                                    && isset($filename[$i])
                                    && \is_string($filename[$i])
                                    && '' !== $filename[$i]
                                ) {
                                    $nameForThis = $filename[$i];
                                } elseif (\is_string($filename) && '' !== $filename) {
                                    // Fallback: same filename for all (not ideal, but safe)
                                    $nameForThis = $filename;
                                } else {
                                    // Fallback: derive from path
                                    $nameForThis = basename($p);
                                }

                                $templatedEmail->attachFromPath($p, $nameForThis);
                                $i++;
                            }
                        }
                        // Unexpected type, ignore safely
                    }

                    // 2) Attach from stream(s)
                    if (!empty($file_attach['stream'])) {
                        $stream = $file_attach['stream'];

                        if (!empty($filename) && \is_string($filename)) {
                            if (\is_resource($stream) || \is_string($stream)) {
                                $templatedEmail->addPart(new DataPart($stream, $filename));
                            }
                        } elseif (\is_array($stream) && \is_array($filename)) {
                            $count = max(\count($stream), \count($filename));
                            for ($i = 0; $i < $count; $i++) {
                                $s = $stream[$i] ?? null;
                                $n = $filename[$i] ?? null;

                                if ((\is_resource($s) || \is_string($s)) && \is_string($n) && '' !== $n) {
                                    $templatedEmail->addPart(new DataPart($s, $n));
                                }
                            }
                        }
                    }
                }
            }

            $automaticEmailText = '<br />'.get_lang('This is an automatic email message. Please do not reply to it.');
            $charset = $this->getMailerCharset();
            $excludeJson = $this->shouldExcludeJsonLd();

            $params = [
                'mail_header_style' => api_get_setting('mail.mail_header_style'),
                'mail_content_style' => api_get_setting('mail.mail_content_style'),
                'link' => $additionalParameters['link'] ?? '',
                'automatic_email_text' => $automaticEmailText,
                'content' => $body,
                'charset' => $charset,
                'exclude_json' => $excludeJson,
            ];

            if (!empty($recipientEmail)) {
                $templatedEmail->to(new Address($recipientEmail, $recipientName));
            }

            if (!empty($replyToEmail)) {
                $templatedEmail->replyTo(new Address($replyToEmail, $replyToName));
            }

            $templatedEmail
                ->htmlTemplate('@ChamiloCore/Mailer/Default/default.html.twig')
                ->context($params)
            ;

            $this->bodyRenderer->render($templatedEmail);
            $this->applyMailerCharset($templatedEmail, $charset);

            $this->logMailerDebug('Mail message is being sent.', [
                'subject' => $subject,
                'to' => $recipientEmail,
                'from' => $this->addressesToString($templatedEmail->getFrom()),
                'reply_to' => $this->addressesToString($templatedEmail->getReplyTo()),
                'has_attachments' => !empty($data_file) ? '1' : '0',
            ]);

            $this->mailer->send($templatedEmail);

            $this->logMailerDebug('Mail message sent successfully.', [
                'subject' => $subject,
                'to' => $recipientEmail,
            ]);

            return true;
        } catch (Exception|TransportExceptionInterface $e) {
            $this->logMailerDebug('Mail message sending failed.', [
                'subject' => $subject,
                'to' => $recipientEmail,
                'error' => $e->getMessage(),
            ]);

            error_log($e->getMessage());

            return false;
        }
    }

    private function getMailerCharset(): string
    {
        $charset = trim((string) $this->settingsManager->getSetting('mail.mailer_mails_charset', true));

        if ('' === $charset) {
            return 'UTF-8';
        }

        if (!preg_match('/^[A-Za-z0-9._-]+$/', $charset)) {
            return 'UTF-8';
        }

        return $charset;
    }

    private function shouldExcludeJsonLd(): bool
    {
        $value = $this->settingsManager->getSetting('mail.mailer_exclude_json', true);

        /*
         * Legacy setting semantics:
         * false disables the LD+JSON block.
         */
        return !$this->isSettingEnabled($value);
    }

    private function isMailerDebugEnabled(): bool
    {
        return $this->isSettingEnabled(
            $this->settingsManager->getSetting('mail.mailer_debug_enable', true)
        );
    }

    private function isSettingEnabled(mixed $value): bool
    {
        if (true === $value || 1 === $value) {
            return true;
        }

        $normalized = strtolower(trim((string) $value));

        return 'true' === $normalized || '1' === $normalized;
    }

    private function applyMailerCharset(TemplatedEmail $email, string $charset): void
    {
        if ('' === trim($charset)) {
            return;
        }

        $htmlBody = $email->getHtmlBody();
        $textBody = $email->getTextBody();

        if (null !== $htmlBody) {
            $email->html($htmlBody, $charset);
        }

        if (null !== $textBody) {
            $email->text($textBody, $charset);
        }
    }

    private function logMailerDebug(string $message, array $context = []): void
    {
        if (!$this->isMailerDebugEnabled()) {
            return;
        }

        $safeContext = [];

        foreach ($context as $key => $value) {
            if (null === $value || '' === $value) {
                continue;
            }

            $safeContext[$key] = is_scalar($value) ? (string) $value : get_debug_type($value);
        }

        error_log(
            $message.' '.json_encode(
                $safeContext,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
        );
    }

    /**
     * @param Address[] $addresses
     */
    private function addressesToString(array $addresses): string
    {
        if (empty($addresses)) {
            return '';
        }

        return implode(
            ', ',
            array_map(
                static fn (Address $address): string => $address->toString(),
                $addresses
            )
        );
    }
}
