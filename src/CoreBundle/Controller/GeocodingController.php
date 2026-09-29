<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Service\Geocoding\NominatimGeocoder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/geocoding')]
final class GeocodingController extends AbstractController
{
    private const int MAX_ADDRESS_LENGTH = 250;

    public function __construct(
        private readonly NominatimGeocoder $geocoder,
    ) {}

    /**
     * Whether a geocoding provider (currently: Nominatim) is available, so
     * the frontend knows whether to offer the "find coordinates" action.
     */
    #[Route('/status', name: 'chamilo_core_geocoding_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return $this->json(['available' => $this->geocoder->isAvailable()]);
    }

    #[Route('/lookup', name: 'chamilo_core_geocoding_lookup', methods: ['GET'])]
    public function lookup(Request $request): JsonResponse
    {
        if (!$this->geocoder->isAvailable()) {
            return $this->json(['error' => 'Geocoding is not available.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $address = trim((string) $request->query->get('address', ''));

        if ('' === $address || mb_strlen($address) > self::MAX_ADDRESS_LENGTH) {
            return $this->json(['error' => 'Invalid address.'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->geocoder->search($address);

        if (null === $result) {
            return $this->json(['error' => 'Address not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($result);
    }
}
