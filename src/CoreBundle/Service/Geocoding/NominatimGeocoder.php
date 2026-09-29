<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Geocoding;

use Chamilo\CoreBundle\Helpers\SafeHttpClientHelper;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;

/**
 * Address -> coordinates lookup via OpenStreetMap's free Nominatim API, used
 * only when Chamilo has no other geocoder configured (currently: Google
 * Maps, via GOOGLE_MAPS_API_KEY). See config/packages/chamilo_geocoding.yaml.
 */
final class NominatimGeocoder
{
    private const string SEARCH_URL = 'https://nominatim.openstreetmap.org/search';
    private const float REQUEST_TIMEOUT_SECONDS = 5.0;

    public function __construct(
        #[Autowire(param: 'chamilo.geocoding.nominatim_enabled')]
        private readonly bool $nominatimEnabled,
        #[Autowire(env: 'GOOGLE_MAPS_API_KEY')]
        private readonly string $googleMapsApiKey,
        private readonly NominatimRateLimiter $rateLimiter,
    ) {}

    public function isAvailable(): bool
    {
        return $this->nominatimEnabled && '' === trim($this->googleMapsApiKey);
    }

    /**
     * @return array{lat: float, lon: float, displayName: string}|null null when the address matched nothing
     */
    public function search(string $address): ?array
    {
        if (!$this->isAvailable()) {
            throw new LogicException('Nominatim geocoding is not available.');
        }

        $this->rateLimiter->throttle();

        $client = SafeHttpClientHelper::create();

        try {
            $response = $client->request('GET', self::SEARCH_URL, [
                'query' => [
                    'q' => $address,
                    'format' => 'jsonv2',
                    'limit' => 1,
                ],
                'headers' => [
                    // Required by Nominatim's usage policy instead of a generic User-Agent.
                    'User-Agent' => 'Chamilo LMS geocoding (https://chamilo.org)',
                ],
                'timeout' => self::REQUEST_TIMEOUT_SECONDS,
            ]);

            $results = $response->toArray(false);
        } catch (HttpExceptionInterface) {
            return null;
        }

        if (!\is_array($results) || !isset($results[0]) || !\is_array($results[0])) {
            return null;
        }

        $first = $results[0];

        if (!isset($first['lat'], $first['lon'])) {
            return null;
        }

        return [
            'lat' => (float) $first['lat'],
            'lon' => (float) $first['lon'],
            'displayName' => (string) ($first['display_name'] ?? $address),
        ];
    }
}
