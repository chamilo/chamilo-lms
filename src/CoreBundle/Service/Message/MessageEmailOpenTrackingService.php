<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Message;

use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class MessageEmailOpenTrackingService
{
    private const int TOKEN_BYTES = 32;

    private ?int $preparedMessageId = null;
    private ?int $preparedReceiverId = null;
    private ?string $preparedBaseUrl = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsManager $settingsManager,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly RequestStack $requestStack,
    ) {}

    public function isEnabled(): bool
    {
        return 'true' === (string) $this->settingsManager->getSetting('mail.enable_email_open_tracking');
    }

    public function prepareForMessageRecipient(
        int $messageId,
        int $receiverId,
        ?string $baseUrl = null,
    ): void {
        $this->clearPreparedPixel();

        if (!$this->isEnabled() || $messageId <= 0 || $receiverId <= 0) {
            return;
        }

        $this->preparedMessageId = $messageId;
        $this->preparedReceiverId = $receiverId;
        $this->preparedBaseUrl = $baseUrl;
    }

    public function clearPreparedPixel(): void
    {
        $this->preparedMessageId = null;
        $this->preparedReceiverId = null;
        $this->preparedBaseUrl = null;
    }

    public function appendPreparedPixel(string $html): string
    {
        if (null === $this->preparedMessageId || null === $this->preparedReceiverId) {
            return $html;
        }

        return $this->appendTrackingPixelForMessageRecipient(
            $this->preparedMessageId,
            $this->preparedReceiverId,
            $html,
            $this->preparedBaseUrl,
        );
    }

    public function appendTrackingPixelForMessageRecipient(
        int $messageId,
        int $receiverId,
        string $html,
        ?string $baseUrl = null,
    ): string {
        if (!$this->isEnabled() || $messageId <= 0 || $receiverId <= 0) {
            return $html;
        }

        $relation = $this->findTrackableRelation($messageId, $receiverId);
        if (!$relation instanceof MessageRelUser) {
            return $html;
        }

        $token = $relation->getMailTrackingToken();
        if (null === $token || '' === $token) {
            $token = $this->createUniqueToken();
            $relation->setMailTrackingToken($token);
            $this->entityManager->persist($relation);
            $this->entityManager->flush();
        }

        $resolvedBaseUrl = $this->resolveBaseUrl($baseUrl);
        if (null === $resolvedBaseUrl) {
            return $html;
        }

        $pixelUrl = $resolvedBaseUrl.'/mail/open/'.$token.'.gif';
        $escapedUrl = htmlspecialchars($pixelUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return $html
            .'\n<img src="'.$escapedUrl.'" alt="" width="1" height="1" '
            .'style="display:block;width:1px;height:1px;border:0" aria-hidden="true">';
    }

    public function registerOpen(string $token): bool
    {
        if (!$this->isEnabled() || 1 !== preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return false;
        }

        $relation = $this->entityManager
            ->getRepository(MessageRelUser::class)
            ->findOneBy(['mailTrackingToken' => $token])
        ;

        if (!$relation instanceof MessageRelUser) {
            return false;
        }

        if (null === $relation->getMailOpenedAt()) {
            $relation->setMailOpenedAt(new DateTime('now'));
            $this->entityManager->flush();
        }

        return true;
    }

    private function findTrackableRelation(int $messageId, int $receiverId): ?MessageRelUser
    {
        $relation = $this->entityManager
            ->getRepository(MessageRelUser::class)
            ->createQueryBuilder('relation')
            ->innerJoin('relation.message', 'message')
            ->innerJoin('relation.receiver', 'receiver')
            ->andWhere('message.id = :messageId')
            ->andWhere('receiver.id = :receiverId')
            ->andWhere('relation.receiverType IN (:receiverTypes)')
            ->setParameter('messageId', $messageId)
            ->setParameter('receiverId', $receiverId)
            ->setParameter('receiverTypes', [MessageRelUser::TYPE_TO, MessageRelUser::TYPE_CC])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $relation instanceof MessageRelUser ? $relation : null;
    }

    private function createUniqueToken(): string
    {
        $repository = $this->entityManager->getRepository(MessageRelUser::class);

        do {
            $token = bin2hex(random_bytes(self::TOKEN_BYTES));
        } while (null !== $repository->findOneBy(['mailTrackingToken' => $token]));

        return $token;
    }

    private function resolveBaseUrl(?string $baseUrl): ?string
    {
        $baseUrl = trim((string) $baseUrl);
        if ('' !== $baseUrl) {
            return rtrim($baseUrl, '/');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null !== $accessUrl && method_exists($accessUrl, 'getUrl')) {
            $url = trim((string) $accessUrl->getUrl());
            if ('' !== $url) {
                return rtrim($url, '/');
            }
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        return rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');
    }
}
