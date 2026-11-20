<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\PersonalDataExport;

use Chamilo\CoreBundle\Entity\ThirdParty;
use Chamilo\CoreBundle\Entity\ThirdPartyDataExchange;
use Chamilo\CoreBundle\Entity\ThirdPartyDataExchangeUser;
use Chamilo\CoreBundle\Entity\User;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use const PHP_URL_HOST;

final class PersonalDataExportPusher
{
    public const int REQUEST_TIMEOUT_SECONDS = 10;
    public const int REQUEST_MAX_DURATION_SECONDS = 15;
    public const int RESPONSE_BODY_LIMIT = 65536;

    public function __construct(
        private readonly PersonalDataExportUrlValidator $urlValidator,
        private readonly PersonalDataExportSigner $signer,
        #[Autowire(service: 'chamilo.personal_data_export.http_client')]
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * @return array{status_code: int, third_party_data_exchange_id: int}
     */
    public function push(User $user, string $destinationUrl, string $jsonBody): array
    {
        $validated = $this->urlValidator->validate($destinationUrl);
        $signed = $this->signer->sign($jsonBody);

        try {
            $response = $this->httpClient->request('POST', $validated['url'], [
                'headers' => array_merge(
                    [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    $signed['headers']
                ),
                'body' => $jsonBody,
                'timeout' => self::REQUEST_TIMEOUT_SECONDS,
                'max_duration' => self::REQUEST_MAX_DURATION_SECONDS,
                'max_redirects' => 0,
                'resolve' => [$validated['host'] => $validated['pin_ip']],
            ]);

            $statusCode = $response->getStatusCode();
            $this->discardBodyUpToLimit($response);
        } catch (TimeoutException $exception) {
            throw new RuntimeException($this->translator->trans('The destination server did not respond in time.'), 0, $exception);
        } catch (InvalidArgumentException|TransportException|TransportExceptionInterface $exception) {
            throw new RuntimeException($this->translator->trans('The personal data could not be sent to the destination URL.'), 0, $exception);
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException($this->translator->trans('The destination server rejected the personal data transfer.'));
        }

        $exchange = $this->recordExchange($user, $validated['url']);

        return [
            'status_code' => $statusCode,
            'third_party_data_exchange_id' => (int) $exchange->getId(),
        ];
    }

    private function discardBodyUpToLimit(object $response): void
    {
        if (!\is_object($response)) {
            return;
        }

        try {
            $read = 0;
            foreach ($this->httpClient->stream($response, self::REQUEST_TIMEOUT_SECONDS) as $chunk) {
                $read += \strlen($chunk->getContent());
                if ($read > self::RESPONSE_BODY_LIMIT) {
                    $response->cancel();

                    break;
                }
            }
        } catch (TransportExceptionInterface) {
            // A truncated or timed-out body is acceptable once the status was already received.
        }
    }

    private function recordExchange(User $user, string $url): ThirdPartyDataExchange
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $thirdParty = $this->entityManager->getRepository(ThirdParty::class)->findOneBy([
            'website' => $url,
            'dataExchangeParty' => true,
        ]);

        if (!$thirdParty instanceof ThirdParty) {
            $host = parse_url($url, PHP_URL_HOST);
            $titleHost = \is_string($host) && '' !== $host ? $host : 'external service';
            $thirdParty = (new ThirdParty())
                ->setTitle('Data portability: '.$titleHost)
                ->setDescription('Created automatically for a user-requested GDPR Article 20 transfer.')
                ->setWebsite($url)
                ->setDataExchangeParty(true)
            ;
            $this->entityManager->persist($thirdParty);
        }

        $exchange = (new ThirdPartyDataExchange())
            ->setThirdParty($thirdParty)
            ->setSentAt($now)
            ->setDescription('User-requested GDPR Article 20 personal data transfer to '.$url)
            ->setAllUsers(false)
        ;
        $this->entityManager->persist($exchange);

        $exchangeUser = (new ThirdPartyDataExchangeUser())
            ->setDataExchange($exchange)
            ->setUser($user)
        ;
        $this->entityManager->persist($exchangeUser);
        $this->entityManager->flush();

        return $exchange;
    }
}
