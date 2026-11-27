<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Social;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Repository\AccessUrlRelPluginRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

final readonly class SocialMapService
{
    public function __construct(
        private AccessUrlHelper $accessUrlHelper,
        private PluginHelper $pluginHelper,
        private AccessUrlRelPluginRepository $pluginRepository,
        private ExtraFieldRepository $fieldRepository,
        private SettingsManager $settingsManager,
        private EntityManagerInterface $entityManager,
    ) {}

    public function getConfiguration(User $viewer): array
    {
        $configuration = [
            'enabled' => false,
            'provider' => null,
            'apiKey' => null,
            'defaultCenter' => ['lat' => 20.0, 'lng' => 0.0],
            'defaultZoom' => 2,
        ];
        $portal = $this->getViewerPortal($viewer);
        if (null === $portal || !self::enabled($this->settingsManager->getSetting('social.allow_social_tool', true)) || [] === $this->getAllowedFields()) {
            return $configuration;
        }

        $relation = $this->pluginRepository->createQueryBuilder('relation')
            ->innerJoin('relation.plugin', 'plugin')
            ->andWhere('LOWER(plugin.title) = :title AND plugin.installed = true AND relation.active = true AND relation.url = :portal')
            ->setParameter('title', 'googlemaps')
            ->setParameter('portal', $portal)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        if (null === $relation) {
            return $configuration;
        }

        // Preserve the plugin's portal-specific settings and configuration overrides.
        $settings = array_merge($relation->getConfiguration() ?? [], $this->pluginHelper->getPluginOverrides($relation->getPlugin()->getTitle()));
        $provider = self::pluginValue($settings, 'map_provider');
        $provider = 'openstreetmap' === $provider ? 'openstreetmap' : 'google_maps';
        $key = self::stringValue(self::pluginValue($settings, 'api_key'));
        if ('google_maps' === $provider && (!self::enabled(self::pluginValue($settings, 'enable_api')) || '' === $key)) {
            return $configuration;
        }

        return [
            'enabled' => true,
            'provider' => $provider,
            // Only the browser Maps JavaScript API key is exposed, never the full plugin configuration.
            'apiKey' => 'google_maps' === $provider ? $key : null,
            'defaultCenter' => [
                'lat' => self::boundedNumber(self::pluginValue($settings, 'default_latitude'), -90, 90) ?? 20.0,
                'lng' => self::boundedNumber(self::pluginValue($settings, 'default_longitude'), -180, 180) ?? 0.0,
            ],
            'defaultZoom' => (int) max(1, min(20, is_numeric(self::pluginValue($settings, 'default_zoom')) ? (float) self::pluginValue($settings, 'default_zoom') : 2)),
        ];
    }

    public function getMarkers(User $viewer): array
    {
        $result = ['markers' => [], 'missingFields' => []];
        if (!$this->getConfiguration($viewer)['enabled']) {
            return $result;
        }
        $portal = $this->getViewerPortal($viewer);
        if (null === $portal) {
            return $result;
        }

        $visibility = self::decodeSetting($this->settingsManager->getSetting('profile.profile_fields_visibility', true))['options'] ?? [];
        if (!\is_array($visibility)) {
            return $result;
        }
        $showFirstName = self::enabled($visibility['firstname'] ?? true);
        $showLastName = self::enabled($visibility['lastname'] ?? true);
        if (!$showFirstName && !$showLastName) {
            return $result;
        }

        $fields = [];
        foreach ($this->getAllowedFields() as $name) {
            $field = $this->fieldRepository->findOneBy(['variable' => $name, 'itemType' => ExtraField::USER_FIELD_TYPE]);
            if (null === $field) {
                $result['missingFields'][] = $name;

                continue;
            }
            if (!$field->isVisibleToOthers() || (\array_key_exists($name, $visibility) && !self::enabled($visibility[$name]))) {
                continue;
            }
            if (!\in_array($field->getValueType(), [ExtraField::FIELD_TYPE_TEXT, ExtraField::FIELD_TYPE_TEXTAREA, ExtraField::FIELD_TYPE_GEOLOCALIZATION, ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES], true)) {
                continue;
            }
            $fields[$field->getId()] = $field;
        }
        if ([] === $fields) {
            return $result;
        }

        // Scope the query itself to this portal; do not load private or foreign-portal values.
        $rows = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT u.id AS userId, u.firstname AS firstname, u.lastname AS lastname, IDENTITY(v.field) AS fieldId, v.fieldValue AS rawValue')
            ->from(ExtraFieldValues::class, 'v')
            ->innerJoin(User::class, 'u', Join::ON, 'u.id = v.itemId')
            ->innerJoin('u.portals', 'membership')
            ->andWhere('membership.url = :portal AND u.active = :active AND u.status NOT IN (:excludedStatuses)')
            ->andWhere('v.field IN (:fields) AND v.fieldValue IS NOT NULL')
            ->setParameter('portal', $portal)
            ->setParameter('active', User::ACTIVE)
            ->setParameter('excludedStatuses', [User::ANONYMOUS, User::ROLE_FALLBACK])
            ->setParameter('fields', array_keys($fields))
            ->orderBy('u.id', 'ASC')
            ->addOrderBy('fieldId', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
        foreach ($rows as $row) {
            $location = self::parseLocation($row['rawValue']);
            if (null === $location) {
                continue;
            }
            $name = trim(($showFirstName ? $row['firstname'] : '').' '.($showLastName ? $row['lastname'] : ''));
            if ('' === $name) {
                continue;
            }
            $field = $fields[$row['fieldId']];
            $result['markers'][] = $location + [
                'userId' => (int) $row['userId'],
                'userName' => $name,
                'profileUrl' => '/social?id='.(int) $row['userId'],
                'field' => $field->getVariable(),
                'fieldLabel' => $field->getDisplayText() ?: $field->getVariable(),
            ];
        }

        return $result;
    }

    private function getViewerPortal(User $viewer): ?AccessUrl
    {
        if (!$viewer->isActive() || \in_array($viewer->getStatus(), [User::ANONYMOUS, User::ROLE_FALLBACK], true)) {
            return null;
        }
        $portal = $this->accessUrlHelper->getCurrent();
        if (null === $portal) {
            return null;
        }
        foreach ($viewer->getPortals() as $membership) {
            if ($membership->getUrl()?->getId() === $portal->getId()) {
                return $portal;
            }
        }

        return null;
    }

    private function getAllowedFields(): array
    {
        $fields = self::decodeSetting($this->settingsManager->getSetting('profile.allow_social_map_fields', true))['fields'] ?? [];
        if (!\is_array($fields)) {
            return [];
        }

        return array_values(array_unique(array_filter($fields, static fn ($name): bool => \is_string($name) && 1 === preg_match('/^[A-Za-z0-9_-]+$/D', $name))));
    }

    private static function decodeSetting(mixed $value): array
    {
        $decoded = \is_string($value) ? json_decode($value, true) : $value;

        return \is_array($decoded) ? $decoded : [];
    }

    private static function pluginValue(array $settings, string $key): mixed
    {
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        foreach ($settings as $group) {
            if (\is_array($group) && isset($group[$key])) {
                return $group[$key];
            }
        }

        return null;
    }

    private static function enabled(mixed $value): bool
    {
        return \in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true);
    }

    private static function stringValue(mixed $value): string
    {
        return \is_string($value) ? trim($value) : '';
    }

    private static function boundedNumber(mixed $value, int $min, int $max): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }
        $number = (float) $value;

        return is_finite($number) && $number >= $min && $number <= $max ? $number : null;
    }

    public static function parseLocation(?string $raw): ?array
    {
        $raw = trim((string) $raw);
        if ('' === $raw) {
            return null;
        }
        $label = '';
        $address = '';
        $lat = null;
        $lng = null;
        if (str_starts_with($raw, '{') || str_starts_with($raw, '[')) {
            $data = json_decode($raw, true);
            if (!\is_array($data)) {
                return null;
            }
            $label = self::stringValue($data['label'] ?? $data['name'] ?? null);
            $address = self::stringValue($data['address'] ?? $data['formatted_address'] ?? null);
            $lat = $data['lat'] ?? $data['latitude'] ?? null;
            $lng = $data['lng'] ?? $data['long'] ?? $data['longitude'] ?? null;
        } elseif (str_contains($raw, '::')) {
            [$label, $coordinates] = array_map('trim', explode('::', $raw, 2));
            [$lat, $lng] = array_pad(array_map('trim', explode(',', $coordinates, 2)), 2, null);
            $address = $label;
        } else {
            $parts = array_map('trim', explode(',', $raw));
            if (2 === \count($parts) && is_numeric($parts[0]) && is_numeric($parts[1])) {
                [$lat, $lng] = $parts;
            } else {
                $address = $raw;
            }
        }
        $lat = self::boundedNumber($lat, -90, 90);
        $lng = self::boundedNumber($lng, -180, 180);
        if (null === $lat || null === $lng) {
            $lat = $lng = null;
        }
        if ('' === $label && '' === $address && null === $lat) {
            return null;
        }
        $address = $address ?: ($label ?: $lat.', '.$lng);

        return ['label' => $label ?: $address, 'address' => $address, 'lat' => $lat, 'lng' => $lng];
    }
}
