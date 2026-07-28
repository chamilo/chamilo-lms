<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Push;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\MobilePushInstallationRepository;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class MobileMessagePushSender
{
    /**
     * @param iterable<MobilePushProviderInterface> $providers
     */
    public function __construct(
        private MobilePushInstallationRepository $installationRepository,
        #[AutowireIterator('chamilo.mobile_push_provider')]
        private iterable $providers,
    ) {}

    public function send(Message $message, AccessUrl $accessUrl): void
    {
        if (Message::MESSAGE_TYPE_INBOX !== $message->getMsgType() || null === $message->getId()) {
            return;
        }

        $recipients = [];

        foreach ($message->getReceivers() as $relation) {
            if (
                MessageRelUser::TYPE_SENDER === $relation->getReceiverType()
                || $relation->isDeleted()
            ) {
                continue;
            }

            $receiver = $relation->getReceiver();

            if (
                !$receiver instanceof User
                || $receiver->getId() === $message->getSender()?->getId()
                || null === $receiver->getId()
            ) {
                continue;
            }

            $recipients[(int) $receiver->getId()] = $receiver;
        }

        if ([] === $recipients) {
            return;
        }

        $installations = $this->installationRepository->findForRecipientsAndAccessUrl(
            array_values($recipients),
            $accessUrl
        );

        foreach ($installations as $installation) {
            foreach ($this->providers as $provider) {
                if (!$provider->isConfigured() || !$provider->supports($installation->getPlatform())) {
                    continue;
                }

                $delivery = $provider->send($installation, $message->getId());

                if ($delivery->invalidToken) {
                    $this->installationRepository->removeInvalidInstallation($installation);
                }

                break;
            }
        }
    }
}
